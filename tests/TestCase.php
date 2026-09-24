<?php

namespace Devaspid\Safi\Tests;

use Devaspid\Safi\SafiServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            SafiServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('safi.base_url', 'https://mock-safi.test');
        $app['config']->set('safi.api_key', 'safi_test_token_123');
    }
}
