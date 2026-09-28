<?php

namespace Devaspid\Safi\Tests\Feature;

use Devaspid\Safi\Facades\Safi;
use Devaspid\Safi\Responder\SafiPullResponder;
use Devaspid\Safi\Tests\TestCase;
use Illuminate\Http\Request;

class PullResponderTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('safi.api_key', 'test_secret_key_123');
        $app['config']->set('safi.source_type', 'pos');
    }

    public function test_pull_responder_rejects_invalid_api_key(): void
    {
        $request = Request::create('/api/safi/sync', 'GET');
        $request->headers->set('X-API-KEY', 'wrong_key');

        $response = SafiPullResponder::forRequest($request)->respond();

        $this->assertEquals(401, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertEquals('error', $data['status']);
    }

    public function test_pull_responder_builds_full_all_branches_payload(): void
    {
        $request = Request::create('/api/safi/sync?date=2026-09-27', 'GET');
        $request->headers->set('X-API-KEY', 'test_secret_key_123');

        $branches = collect([
            (object) ['id' => 1, 'code' => 'CBG-01', 'name' => 'Cabang Utama'],
            (object) ['id' => 2, 'code' => 'CBG-02', 'name' => 'Cabang Kedua'],
        ]);

        $orders = collect([
            (object) [
                'branch_id' => 1,
                'created_at' => '2026-09-27 10:00:00',
                'grand_total' => 100000,
                'total_cogs' => 60000,
                'total_profit' => 40000,
                'total_discount' => 0,
                'items_count' => 2,
                'customer_id' => 'CUST-1',
            ],
            (object) [
                'branch_id' => 2,
                'created_at' => '2026-09-27 15:00:00',
                'grand_total' => 150000,
                'total_cogs' => 90000,
                'total_profit' => 60000,
                'total_discount' => 5000,
                'items_count' => 3,
                'customer_id' => null,
            ],
        ]);

        $response = Safi::pullResponder($request)
            ->branches($branches)
            ->orders($orders)
            ->respond();

        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);

        $this->assertEquals('success', $data['status']);
        $this->assertEquals('pos', $data['source_type']);
        $this->assertEquals('2026-09-27', $data['date']);
        $this->assertCount(2, $data['channels']);
        $this->assertCount(2, $data['daily_summary']);
        $this->assertCount(48, $data['hourly_summary']); // 2 branches x 24 hours
    }

    public function test_pull_responder_filters_by_branch_code_parameter(): void
    {
        $request = Request::create('/api/safi/sync?date=2026-09-27&branch_code=CBG-02', 'GET');
        $request->headers->set('X-API-KEY', 'test_secret_key_123');

        $branches = collect([
            (object) ['id' => 1, 'code' => 'CBG-01', 'name' => 'Cabang Utama'],
            (object) ['id' => 2, 'code' => 'CBG-02', 'name' => 'Cabang Kedua'],
        ]);

        $orders = collect([
            (object) [
                'branch_id' => 1,
                'created_at' => '2026-09-27 10:00:00',
                'grand_total' => 100000,
                'total_cogs' => 60000,
                'total_profit' => 40000,
                'total_discount' => 0,
                'items_count' => 2,
                'customer_id' => 'CUST-1',
            ],
            (object) [
                'branch_id' => 2,
                'created_at' => '2026-09-27 15:00:00',
                'grand_total' => 150000,
                'total_cogs' => 90000,
                'total_profit' => 60000,
                'total_discount' => 5000,
                'items_count' => 3,
                'customer_id' => null,
            ],
        ]);

        $response = Safi::pullResponder($request)
            ->branches($branches)
            ->orders($orders)
            ->respond();

        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);

        $this->assertCount(1, $data['channels']);
        $this->assertEquals('CBG-02', $data['channels'][0]['code']);
        $this->assertCount(1, $data['daily_summary']);
        $this->assertEquals(2, $data['daily_summary'][0]['channel_original_id']);
        $this->assertEquals(150000.0, $data['daily_summary'][0]['total_revenue']);
        $this->assertCount(24, $data['hourly_summary']); // 1 branch x 24 hours
    }
}
