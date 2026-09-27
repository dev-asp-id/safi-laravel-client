<?php

namespace Devaspid\Safi\Tests\Feature;

use Devaspid\Safi\Facades\Safi;
use Devaspid\Safi\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class FacadeTest extends TestCase
{
    public function test_can_test_connection_via_facade(): void
    {
        Http::fake([
            'https://mock-safi.test/api/v1/ping' => Http::response(['pong' => true], 200),
        ]);

        $this->assertTrue(Safi::testConnection());
    }

    public function test_can_ingest_raw_via_facade(): void
    {
        Http::fake([
            'https://mock-safi.test/api/v1/sync/ingest' => Http::response([
                'success' => true,
                'message' => 'Raw data ingested',
            ], 200),
        ]);

        $result = Safi::ingestRaw([
            [
                'channel_original_id' => 1,
                'invoice_no' => 'INV-001',
                'total_net' => 50000,
            ]
        ], [
            'source_original_id' => 1,
            'code' => 'MAIN-01',
            'name' => 'Main Branch',
        ]);

        $this->assertTrue($result['success']);
    }
}
