<?php

namespace Devaspid\Safi\Facades;

use Devaspid\Safi\SafiClient;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array ingest(array $payload)
 * @method static array ingestAggregated(array $channels, array $customers = [])
 * @method static array ingestRaw(array $transactions, ?array $channel = null)
 * @method static void dispatchRawAsync(array $transactions, ?array $channel = null)
 * @method static bool testConnection()
 *
 * @see \Devaspid\Safi\SafiClient
 */
class Safi extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SafiClient::class;
    }
}
