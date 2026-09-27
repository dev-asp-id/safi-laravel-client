<?php

namespace Devaspid\Safi\Tests\Unit;

use Devaspid\Safi\DTO\AggregatedPayload;
use Devaspid\Safi\DTO\CustomerData;
use Devaspid\Safi\DTO\RawTransactionPayload;
use Devaspid\Safi\Tests\TestCase;

class DtoBuilderTest extends TestCase
{
    public function test_customer_data_to_array(): void
    {
        $cust = new CustomerData(
            sourceCustomerId: 123,
            name: 'Budi Santoso',
            phone: '08123456789',
            email: 'budi@example.com'
        );

        $array = $cust->toArray();
        $this->assertEquals(123, $array['source_customer_id']);
        $this->assertEquals('CUST-123', $array['code']);
        $this->assertEquals('Budi Santoso', $array['name']);
    }

    public function test_raw_transaction_payload_to_array(): void
    {
        $payload = new RawTransactionPayload(
            transactions: [['id' => 1, 'total_net' => 10000]],
            channel: ['code' => 'MAIN-01', 'name' => 'Main Branch'],
            sourceType: 'pos'
        );

        $array = $payload->toArray();
        $this->assertEquals('raw', $array['payload_type']);
        $this->assertEquals('pos', $array['source_type']);
        $this->assertCount(1, $array['transactions']);
        $this->assertEquals('MAIN-01', $array['channel']['code']);
    }

    public function test_aggregated_payload_to_array(): void
    {
        $payload = new AggregatedPayload(
            channels: [['channel_code' => 'MAIN-01', 'total_revenue' => 500000]],
            customers: [['customer_id' => 1, 'monetary' => 500000]],
            syncTimestamp: '2026-09-27T00:00:00+07:00'
        );

        $array = $payload->toArray();
        $this->assertEquals('aggregated', $array['payload_type']);
        $this->assertEquals('2026-09-27T00:00:00+07:00', $array['sync_timestamp']);
        $this->assertCount(1, $array['channels']);
        $this->assertCount(1, $array['customers']);
    }
}
