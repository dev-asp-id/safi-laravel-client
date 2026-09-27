<?php

namespace Devaspid\Safi\Tests\Unit;

use Devaspid\Safi\Aggregator\CustomerRfmAggregator;
use Devaspid\Safi\Aggregator\HourlyTransactionAggregator;
use Devaspid\Safi\Tests\TestCase;
use Illuminate\Support\Collection;

class AggregatorTest extends TestCase
{
    public function test_hourly_transaction_aggregator_groups_hours_correctly(): void
    {
        $transactions = collect([
            (object) [
                'created_at' => '2026-09-27 10:15:00',
                'grand_total' => 150000,
                'total_cogs' => 100000,
                'total_profit' => 50000,
                'total_discount' => 0,
                'items_count' => 2,
            ],
            (object) [
                'created_at' => '2026-09-27 10:45:00',
                'grand_total' => 200000,
                'total_cogs' => 120000,
                'total_profit' => 80000,
                'total_discount' => 10000,
                'items_count' => 3,
            ],
            (object) [
                'created_at' => '2026-09-27 14:00:00',
                'grand_total' => 50000,
                'total_cogs' => 30000,
                'total_profit' => 20000,
                'total_discount' => 0,
                'items_count' => 1,
            ],
        ]);

        $hourly = HourlyTransactionAggregator::aggregate(
            transactions: $transactions,
            targetDate: '2026-09-27',
            channelOriginalId: 1
        );

        $this->assertCount(24, $hourly);

        // Hour 10
        $hour10 = $hourly[10];
        $this->assertEquals(2, $hour10['total_transactions']);
        $this->assertEquals(350000.0, $hour10['total_revenue']);
        $this->assertEquals(130000.0, $hour10['total_profit']);
        $this->assertEquals(5, $hour10['total_items_sold']);

        // Hour 14
        $hour14 = $hourly[14];
        $this->assertEquals(1, $hour14['total_transactions']);
        $this->assertEquals(50000.0, $hour14['total_revenue']);

        // Hour 0
        $hour0 = $hourly[0];
        $this->assertEquals(0, $hour0['total_transactions']);
        $this->assertEquals(0.0, $hour0['total_revenue']);
    }

    public function test_customer_rfm_aggregator_calculates_metrics(): void
    {
        $orders = collect([
            (object) [
                'customer_id' => 'CUST-1',
                'created_at' => '2026-09-20 10:00:00',
                'grand_total' => 100000,
            ],
            (object) [
                'customer_id' => 'CUST-1',
                'created_at' => '2026-09-25 12:00:00',
                'grand_total' => 200000,
            ],
        ]);

        $rfm = CustomerRfmAggregator::aggregate(
            orders: $orders,
            referenceDate: '2026-09-27 12:00:00'
        );

        $this->assertCount(1, $rfm);
        $this->assertEquals('CUST-1', $rfm[0]['source_customer_id']);
        $this->assertEquals(2, $rfm[0]['frequency']);
        $this->assertEquals(300000.0, $rfm[0]['monetary']);
        $this->assertEquals(2, $rfm[0]['recency_days']);
    }
}
