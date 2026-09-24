<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SAFI Base API URL
    |--------------------------------------------------------------------------
    | URL instalasi server SAFI Anda.
    */
    'base_url' => env('SAFI_BASE_URL', 'https://safi.asp.web.id'),

    /*
    |--------------------------------------------------------------------------
    | SAFI Secret API Key
    |--------------------------------------------------------------------------
    | API key tenant yang didapatkan dari SAFI Hub / Admin Portal.
    */
    'api_key' => env('SAFI_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Source Type & Default Channel
    |--------------------------------------------------------------------------
    | Tipe platform: 'pos', 'online_shop', atau 'crowdfunding'.
    */
    'source_type' => env('SAFI_SOURCE_TYPE', 'pos'),
    'default_channel_code' => env('SAFI_DEFAULT_CHANNEL_CODE', 'MAIN-01'),
    'default_channel_name' => env('SAFI_DEFAULT_CHANNEL_NAME', 'Cabang Utama'),

    /*
    |--------------------------------------------------------------------------
    | Request Settings
    |--------------------------------------------------------------------------
    | Timeout koneksi dalam detik & jumlah retry jika terjadi network glitch.
    */
    'timeout' => (int) env('SAFI_TIMEOUT', 15),
    'retry_times' => (int) env('SAFI_RETRY_TIMES', 3),
    'retry_sleep_ms' => (int) env('SAFI_RETRY_SLEEP_MS', 500),

    /*
    |--------------------------------------------------------------------------
    | Queue Settings
    |--------------------------------------------------------------------------
    | Nama queue connection dan nama tube/queue untuk background dispatch.
    */
    'queue' => [
        'connection' => env('SAFI_QUEUE_CONNECTION', null),
        'name' => env('SAFI_QUEUE_NAME', 'default'),
    ],
];
