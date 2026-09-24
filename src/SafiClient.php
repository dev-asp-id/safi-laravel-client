<?php

namespace Devaspid\Safi;

use Devaspid\Safi\Exceptions\SafiApiException;
use Devaspid\Safi\Exceptions\SafiAuthenticationException;
use Exception;
use Illuminate\Support\Facades\Http;

class SafiClient
{
    public function __construct(
        protected string $baseUrl,
        protected string $apiKey,
        protected string $sourceType = 'pos',
        protected int $timeout = 15,
        protected int $retryTimes = 3,
        protected int $retrySleepMs = 500
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Kirim data agregat (Mode A: Pre-Aggregated per Channel & per Jam).
     */
    public function ingestAggregated(array $channels, array $customers = []): array
    {
        $payload = [
            'payload_type' => 'aggregated',
            'sync_timestamp' => now()->toIso8601String(),
            'channels' => $channels,
        ];

        if (! empty($customers)) {
            $payload['customers'] = $customers;
        }

        return $this->sendIngestRequest($payload);
    }

    /**
     * Kirim data transaksi satuan / mentah (Mode B: Raw Feed).
     */
    public function ingestRaw(array $transactions, ?array $channel = null): array
    {
        $payload = [
            'payload_type' => 'raw',
            'source_type' => $this->sourceType,
            'transactions' => $transactions,
        ];

        if ($channel) {
            $payload['channel'] = $channel;
        }

        return $this->sendIngestRequest($payload);
    }

    /**
     * Endpoint Ping / Connection Test.
     */
    public function testConnection(): bool
    {
        try {
            $res = Http::withHeaders([
                'X-Api-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->timeout(5)->get("{$this->baseUrl}/api/v1/ping");

            return $res->successful();
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Eksekusi HTTP POST dengan proteksi auto-retry.
     */
    protected function sendIngestRequest(array $payload): array
    {
        if (empty($this->apiKey)) {
            throw new SafiAuthenticationException('SAFI API Key belum diatur di file .env (SAFI_API_KEY).');
        }

        $endpoint = "{$this->baseUrl}/api/v1/sync/ingest";

        $response = Http::withHeaders([
            'X-Api-Key' => $this->apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])
            ->timeout($this->timeout)
            ->retry($this->retryTimes, $this->retrySleepMs)
            ->post($endpoint, $payload);

        if ($response->status() === 401) {
            throw new SafiAuthenticationException('Akses ditolak: API Key SAFI tidak valid atau kadaluarsa.');
        }

        if (! $response->successful()) {
            throw new SafiApiException(
                message: "SAFI Ingestion Error [HTTP {$response->status()}]: ".$response->body(),
                code: $response->status()
            );
        }

        return $response->json() ?? [];
    }
}