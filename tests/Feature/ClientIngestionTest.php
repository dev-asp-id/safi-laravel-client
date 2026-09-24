<?php

namespace Devaspid\Safi\Tests\Feature;

use Devaspid\Safi\Facades\Safi;
use Devaspid\Safi\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class ClientIngestionTest extends TestCase
{
    public function test_can_send_aggregated_payload_successfully(): void
    {
        Http::fake([
            'https://mock-safi.test/api/v1/sync/ingest' => Http::response([
                'success' => true,
                'message' => 'Data ingested successfully',
                'records_processed' => 15,
            ], 200),
        ]);

        $result = Safi::ingestAggregated([
            [
                'channel_code' => 'TEST-01',
                'channel_name' => 'Test Store',
                'hourly_summary' => [
                    ['hour' => 10, 'revenue' => 1000000, 'transactions' => 10],
                ],
            ]
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(15, $result['records_processed']);

        Http::assertSent(function ($request) {
            return $request->hasHeader('X-Api-Key', 'safi_test_token_123')
                && $request['payload_type'] === 'aggregated';
        });
    }

    public function test_throws_authentication_exception_when_api_key_missing(): void
    {
        config(['safi.api_key' => '']);

        $this->expectException(\Devaspid\Safi\Exceptions\SafiAuthenticationException::class);

        Safi::ingestAggregated([['channel_code' => 'TEST-01']]);
    }

    public function test_throws_api_exception_on_server_error(): void
    {
        Http::fake([
            'https://mock-safi.test/api/v1/sync/ingest' => Http::response(['error' => 'Server Error'], 500),
        ]);

        $this->expectException(\Devaspid\Safi\Exceptions\SafiApiException::class);

        Safi::ingestAggregated([['channel_code' => 'TEST-01']]);
    }
}
