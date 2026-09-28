<?php

namespace Devaspid\Safi\Contracts;

interface SafiClientInterface
{
    /**
     * Send aggregated payload to SAFI Hub.
     */
    public function ingestAggregated(array $channels, array $customers = []): array;

    /**
     * Send raw transactions payload to SAFI Hub.
     */
    public function ingestRaw(array $transactions, ?array $channel = null): array;

    /**
     * Dispatch raw transactions asynchronously to background queue.
     */
    public function dispatchRawAsync(array $transactions, ?array $channel = null): void;

    /**
     * Test connection to SAFI Hub.
     */
    public function testConnection(): bool;

    /**
     * Create a pull responder instance for PULL endpoint (GET /api/safi/sync).
     */
    public function pullResponder(?\Illuminate\Http\Request $request = null): \Devaspid\Safi\Responder\SafiPullResponder;
}
