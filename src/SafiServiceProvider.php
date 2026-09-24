<?php

namespace Devaspid\Safi;

use Devaspid\Safi\Commands\SafiSyncDailyCommand;
use Devaspid\Safi\Commands\SafiSyncHourlyCommand;
use Illuminate\Support\ServiceProvider;

class SafiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/safi.php', 'safi');

        $this->app->singleton(SafiClient::class, function ($app) {
            $config = $app['config']['safi'] ?? [];

            return new SafiClient(
                baseUrl: (string) ($config['base_url'] ?? ''),
                apiKey: (string) ($config['api_key'] ?? ''),
                sourceType: (string) ($config['source_type'] ?? 'pos'),
                timeout: (int) ($config['timeout'] ?? 15),
                retryTimes: (int) ($config['retry_times'] ?? 3),
                retrySleepMs: (int) ($config['retry_sleep_ms'] ?? 500)
            );
        });

        $this->app->alias(SafiClient::class, 'safi');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/safi.php' => $this->app->configPath('safi.php'),
            ], 'safi-config');

            $this->commands([
                SafiSyncHourlyCommand::class,
                SafiSyncDailyCommand::class,
            ]);
        }
    }
}
