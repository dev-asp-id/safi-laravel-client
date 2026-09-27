<?php

namespace Devaspid\Safi\Tests\Feature;

use Devaspid\Safi\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class ArtisanCommandTest extends TestCase
{
    public function test_safi_sync_hourly_command_runs_successfully(): void
    {
        Http::fake([
            'https://mock-safi.test/api/v1/ping' => Http::response(['pong' => true], 200),
        ]);

        $this->artisan('safi:sync-hourly', ['--date' => '2026-09-27'])
            ->expectsOutput('Memulai sinkronisasi data per jam ke SAFI...')
            ->assertSuccessful();
    }

    public function test_safi_sync_daily_command_runs_successfully(): void
    {
        Http::fake([
            'https://mock-safi.test/api/v1/ping' => Http::response(['pong' => true], 200),
        ]);

        $this->artisan('safi:sync-daily', ['--date' => '2026-09-26'])
            ->expectsOutput('Memulai sinkronisasi data harian ke SAFI...')
            ->assertSuccessful();
    }
}
