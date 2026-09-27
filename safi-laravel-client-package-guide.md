# Blueprint Teknis Pembuatan Package: `devaspid/safi-laravel-client`
> **Panduan Teknis Arsitektur & Implementasi Resmi SDK Client SAFI untuk Laravel**

Dokumen ini berisi spesifikasi teknis lengkap, struktur folder, arsitektur kode, strategi kompatibilitas multi-versi (PHP 8.0 – 8.4+ & Laravel 9 – 13), integrasi multi-komponen (Controller, Livewire, Artisan Scheduler, Queue Job), pengujian otomatis (*Unit/Feature Testing* dengan Orchestra Testbench & CI Matrix), serta panduan publikasi open-source di Packagist.

| Info | Detail |
|---|---|
| **Packagist** | `devaspid/safi-laravel-client` |
| **GitHub** | `github.com/dev-asp-id/safi-laravel-client` |
| **PHP Namespace** | `Devaspid\Safi\` |
| **Facade** | `Safi::` |
| **Config Key** | `safi` |

---

## Matriks Kompatibilitas & Persyaratan Sistem

Package ini dirancang sebagai *zero-friction client* yang harus mampu berjalan pada ekosistem lama maupun versi terbaru:

| Komponen | Versi yang Didukung | Keterangan |
|---|---|---|
| **PHP Runtime** | `^8.0 \| ^8.1 \| ^8.2 \| ^8.3 \| ^8.4` | Menggunakan syntax modern yang *backward-compatible* (constructor promotion, match expression, union types). Hindari fitur PHP 8.2+ spesifik (seperti readonly classes) pada level class utama agar tetap kompatibel di PHP 8.0. |
| **Laravel Framework** | `^9.0 \| ^10.0 \| ^11.0 \| ^12.0 \| ^13.0` | Memanfaatkan `Illuminate\Support\Facades\Http` dan `Illuminate\Support\ServiceProvider`. |
| **Livewire** | `^2.0 \| ^3.0 \| ^4.0` | Kompatibel di dalam method action Livewire tanpa *side-effects*. |
| **Transport Protocol** | HTTPS (cURL / Guzzle HTTP) | JSON-based Payload via `POST /api/v1/sync/ingest`. |

---

## Struktur Direktori Package

```text
safi-laravel-client/
├── .github/
│   └── workflows/
│       └── run-tests.yml           # GitHub Actions Multi-Matrix CI (PHP 8.0-8.4 x Laravel 9-13)
├── config/
│   └── safi.php                    # File konfigurasi yang di-publish ke project client
├── database/
│   └── migrations/                 # Migrasi opsional untuk SQLite Outbox Buffer lokal
│       └── create_safi_outbox_table.php.stub
├── src/
│   ├── Aggregator/
│   │   ├── HourlyTransactionAggregator.php  # Helper pengelompok data per jam & cabang
│   │   └── CustomerRfmAggregator.php        # Helper ekstraksi data customer lokal
│   ├── Buffer/
│   │   ├── OutboxBufferInterface.php
│   │   └── DatabaseOutboxBuffer.php         # Offline buffer antrian jika internet POS down
│   ├── Commands/
│   │   ├── SafiSyncHourlyCommand.php        # php artisan safi:sync-hourly
│   │   ├── SafiSyncDailyCommand.php         # php artisan safi:sync-daily
│   │   └── SafiFlushOutboxCommand.php       # php artisan safi:flush-outbox
│   ├── Contracts/
│   │   └── SafiClientInterface.php
│   ├── DTO/
│   │   ├── AggregatedPayload.php            # DTO Builder untuk Mode A
│   │   ├── RawTransactionPayload.php        # DTO Builder untuk Mode B
│   │   └── CustomerData.php
│   ├── Exceptions/
│   │   ├── SafiApiException.php
│   │   └── SafiAuthenticationException.php
│   ├── Facades/
│   │   └── Safi.php                         # Facade: Safi::ingestAggregated()
│   ├── Jobs/
│   │   └── SyncToSafiJob.php                # Queue Job untuk asynchronous dispatch
│   ├── SafiClient.php                       # HTTP Engine utama (Auto-retry, timeout, header)
│   └── SafiServiceProvider.php              # Laravel Package Service Provider
├── tests/
│   ├── TestCase.php                         # Orchestra Testbench Base TestCase
│   ├── Unit/
│   │   ├── AggregatorTest.php
│   │   └── DtoBuilderTest.php
│   └── Feature/
│       ├── ClientIngestionTest.php
│       ├── FacadeTest.php
│       └── ArtisanCommandTest.php
├── .gitignore
├── CHANGELOG.md
├── LICENSE.md                               # MIT License
├── README.md                                # Dokumentasi cara pasang & pakai
├── composer.json                            # Definisi dependensi & auto-discovery
└── phpunit.xml.dist                         # Konfigurasi PHPUnit / Pest
```

---

## Spesifikasi `composer.json`

File `composer.json` harus mendeklarasikan dukungan multi-versi dan *Package Auto-Discovery* Laravel:

```json
{
    "name": "devaspid/safi-laravel-client",
    "description": "Official Laravel Client SDK for SAFI Universal Analytics & Ingestion Hub",
    "keywords": ["laravel", "safi", "analytics", "pos", "ecommerce", "crowdfunding", "ingestion", "devaspid"],
    "license": "MIT",
    "type": "library",
    "authors": [
        {
            "name": "Devaspid",
            "email": "support@devaspid.com",
            "homepage": "https://github.com/dev-asp-id"
        }
    ],
    "require": {
        "php": "^8.0 || ^8.1 || ^8.2 || ^8.3 || ^8.4",
        "guzzlehttp/guzzle": "^7.2",
        "illuminate/contracts": "^9.0 || ^10.0 || ^11.0 || ^12.0 || ^13.0",
        "illuminate/http": "^9.0 || ^10.0 || ^11.0 || ^12.0 || ^13.0",
        "illuminate/support": "^9.0 || ^10.0 || ^11.0 || ^12.0 || ^13.0"
    },
    "require-dev": {
        "orchestra/testbench": "^7.0 || ^8.0 || ^9.0 || ^10.0",
        "phpunit/phpunit": "^9.5 || ^10.0 || ^11.0",
        "pestphp/pest": "^2.0 || ^3.0",
        "mockery/mockery": "^1.4"
    },
    "autoload": {
        "psr-4": {
            "Devaspid\\Safi\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Devaspid\\Safi\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Devaspid\\Safi\\SafiServiceProvider"
            ],
            "aliases": {
                "Safi": "Devaspid\\Safi\\Facades\\Safi"
            }
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

---

## Implementasi Komponen Inti Package

### File Konfigurasi: `config/safi.php`

```php
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
```

---

### Service Provider: `src/SafiServiceProvider.php`

```php
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
        $this->app->alias(SafiClient::class, \Devaspid\Safi\Contracts\SafiClientInterface::class);
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
```

---

### Facade: `src/Facades/Safi.php`

```php
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
```

---

### HTTP Client Engine: `src/SafiClient.php`

```php
<?php

namespace Devaspid\Safi;

use Devaspid\Safi\Exceptions\SafiApiException;
use Devaspid\Safi\Exceptions\SafiAuthenticationException;
use Exception;
use Illuminate\Support\Facades\Http;

class SafiClient
{
    public function __construct(
        protected string $baseUrl,
        protected string $apiKey,
        protected string $sourceType = 'pos',
        protected int $timeout = 15,
        protected int $retryTimes = 3,
        protected int $retrySleepMs = 500
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Kirim data agregat (Mode A: Pre-Aggregated per Channel & per Jam).
     */
    public function ingestAggregated(array $channels, array $customers = []): array
    {
        $payload = [
            'payload_type' => 'aggregated',
            'sync_timestamp' => now()->toIso8601String(),
            'channels' => $channels,
        ];

        if (! empty($customers)) {
            $payload['customers'] = $customers;
        }

        return $this->sendIngestRequest($payload);
    }

    /**
     * Kirim data transaksi satuan / mentah (Mode B: Raw Feed).
     */
    public function ingestRaw(array $transactions, ?array $channel = null): array
    {
        $payload = [
            'payload_type' => 'raw',
            'source_type' => $this->sourceType,
            'transactions' => $transactions,
        ];

        if ($channel) {
            $payload['channel'] = $channel;
        }

        return $this->sendIngestRequest($payload);
    }

    /**
     * Endpoint Ping / Connection Test.
     */
    public function testConnection(): bool
    {
        try {
            $res = Http::withHeaders([
                'X-Api-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->timeout(5)->get("{$this->baseUrl}/api/v1/ping");

            return $res->successful();
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Eksekusi HTTP POST dengan proteksi auto-retry.
     */
    protected function sendIngestRequest(array $payload): array
    {
        if (empty($this->apiKey)) {
            throw new SafiAuthenticationException('SAFI API Key belum diatur di file .env (SAFI_API_KEY).');
        }

        $endpoint = "{$this->baseUrl}/api/v1/sync/ingest";

        $response = Http::withHeaders([
            'X-Api-Key' => $this->apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])
            ->timeout($this->timeout)
            ->retry($this->retryTimes, $this->retrySleepMs)
            ->post($endpoint, $payload);

        if ($response->status() === 401) {
            throw new SafiAuthenticationException('Akses ditolak: API Key SAFI tidak valid atau kadaluarsa.');
        }

        if (! $response->successful()) {
            throw new SafiApiException(
                message: "SAFI Ingestion Error [HTTP {$response->status()}]: ".$response->body(),
                code: $response->status()
            );
        }

        return $response->json() ?? [];
    }
}
```

---

### Helper Aggregator: `src/Aggregator/HourlyTransactionAggregator.php`

Helper ini bertugas membaca koleksi transaksi lokal client, mengelompokkannya per jam (0-23) dan per cabang (`channel_original_id`), sehingga siap disajikan ke SAFI (baik via Mode Pull maupun Mode Push):

```php
<?php

namespace Devaspid\Safi\Aggregator;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class HourlyTransactionAggregator
{
    /**
     * Ubah koleksi transaksi lokal menjadi format hourly_summary SAFI.
     *
     * @param Collection $transactions Koleksi record transaksi lokal
     * @param string $targetDate Tanggal target format Y-m-d
     * @param int $channelOriginalId ID cabang sistem lokal
     * @param string $dateField Nama kolom datetime (misal: 'created_at' atau 'paid_at')
     * @param string $amountField Nama kolom omset/total (misal: 'total_net' atau 'grand_total')
     * @param string $cogsField Nama kolom modal/HPP (misal: 'cogs' atau 'hpp')
     * @param string $profitField Nama kolom laba bersih (misal: 'profit')
     * @param string $discountField Nama kolom diskon (misal: 'discount_amount')
     * @param string $itemsField Nama kolom kuantitas item (misal: 'items_count')
     */
    public static function aggregate(
        Collection $transactions,
        string $targetDate,
        int $channelOriginalId = 1,
        string $dateField = 'created_at',
        string $amountField = 'grand_total',
        string $cogsField = 'total_cogs',
        string $profitField = 'total_profit',
        string $discountField = 'total_discount',
        string $itemsField = 'items_count'
    ): array {
        $hourlyBuckets = [];

        // Inisialisasi 24 jam (0 - 23)
        for ($h = 0; $h < 24; $h++) {
            $hourlyBuckets[$h] = [
                'channel_original_id' => $channelOriginalId,
                'date' => $targetDate,
                'hour' => $h,
                'total_transactions' => 0,
                'total_revenue' => 0.0,
                'total_profit' => 0.0,
                'total_items_sold' => 0,
            ];
        }

        foreach ($transactions as $tx) {
            $txDate = Carbon::parse($tx->{$dateField});
            if ($txDate->format('Y-m-d') !== $targetDate) {
                continue;
            }

            $hour = (int) $txDate->format('G'); // 0 to 23
            $hourlyBuckets[$hour]['total_transactions'] += 1;
            $hourlyBuckets[$hour]['total_revenue'] += (float) ($tx->{$amountField} ?? 0);
            $hourlyBuckets[$hour]['total_profit'] += (float) ($tx->{$profitField} ?? 0);
            $hourlyBuckets[$hour]['total_items_sold'] += (int) ($tx->{$itemsField} ?? 1);
        }

        return array_values($hourlyBuckets);
    }
}
```

---

## Panduan Penggunaan di Aplikasi Client (2 Paradigma Komunikasi)

Sistem SAFI mendukung **dua mode integrasi fleksibel**:

---

### 🟢 MODE 1: PULL PROVIDER (Rekomendasi Utama — SAFI Menarik Data Terjadwal)

Dalam mode ini, aplikasi client cukup menyediakan 2 API endpoint sederhana. Server SAFI (via scheduler atau tombol sinkronisasi) yang akan melakukan HTTP `GET` ke aplikasi client Anda.

#### 1. Endpoint Sinkronisasi Data Transaksi: `GET /api/safi/sync`

Daftarkan route di `routes/api.php` client:
```php
use App\Http\Controllers\Api\SafiSyncExportController;
use Illuminate\Support\Facades\Route;

Route::get('/safi/sync', [SafiSyncExportController::class, 'export']);
```

Buat controller: `app/Http/Controllers/Api/SafiSyncExportController.php`
```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Order;
use Devaspid\Safi\Aggregator\HourlyTransactionAggregator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SafiSyncExportController extends Controller
{
    public function export(Request $request): JsonResponse
    {
        // 1. Verifikasi X-API-KEY dari SAFI
        $apiKey = $request->header('X-API-KEY');
        if ($apiKey !== config('safi.api_key', env('SAFI_API_KEY'))) {
            return response()->json(['message' => 'Unauthorized: Invalid API Key'], 401);
        }

        $date = $request->query('date', now()->format('Y-m-d'));

        // 2. Query Transaksi Hari Bersangkutan
        $orders = Order::whereDate('created_at', $date)->get();

        // 3. Format Response Agregat SAFI
        return response()->json([
            'status' => 'success',
            'source_type' => config('safi.source_type', 'pos'),
            'date' => $date,
            'channels' => Branch::where('is_active', true)->get()->map(fn($b) => [
                'source_original_id' => $b->id,
                'code' => $b->code,
                'name' => $b->name,
            ])->values()->all(),
            'daily_summary' => [
                [
                    'channel_original_id' => 1,
                    'total_transactions' => $orders->count(),
                    'total_revenue' => (float) $orders->sum('grand_total'),
                    'total_cogs' => (float) $orders->sum('total_hpp'),
                    'total_profit' => (float) $orders->sum('net_profit'),
                    'total_discount' => (float) $orders->sum('discount_amount'),
                    'total_items_sold' => (int) $orders->sum('items_count'),
                    'member_count' => $orders->whereNotNull('customer_id')->count(),
                    'non_member_count' => $orders->whereNull('customer_id')->count(),
                ]
            ],
            'hourly_summary' => HourlyTransactionAggregator::aggregate(
                transactions: $orders,
                targetDate: $date,
                channelOriginalId: 1
            ),
        ]);
    }
}
```

---

### 🔵 MODE 2: PUSH CLIENT (Pengiriman Instan Real-time ke SAFI)

Client mengirimkan transaksi secara langsung ke endpoint SAFI (`POST https://safi-server.com/api/v1/sync/ingest`) saat transaksi baru selesai diproses.

#### Di Dalam Controller Checkout / Webhook
```php
namespace App\Http\Controllers;

use App\Models\Order;
use Devaspid\Safi\Facades\Safi;

class CheckoutController extends Controller
{
    public function checkoutSuccess(Order $order)
    {
        // Push transaksi real-time ke SAFI
        Safi::ingestRaw([
            [
                'channel_original_id' => $order->branch_id ?? 1,
                'invoice_no' => $order->invoice_number,
                'transaction_time' => $order->created_at->toIso8601String(),
                'total_net' => (float) $order->grand_total,
                'total_gross' => (float) ($order->grand_total + $order->discount_amount),
                'total_cogs' => (float) $order->total_hpp,
                'total_profit' => (float) $order->net_profit,
                'total_discount' => (float) $order->discount_amount,
                'items_count' => (int) $order->items()->sum('qty'),
                'customer' => [
                    'source_customer_id' => $order->customer_id,
                    'code' => 'CUST-'.$order->customer_id,
                    'name' => $order->customer_name,
                    'phone' => $order->customer_phone,
                    'email' => $order->customer_email,
                ],
            ]
        ], [
            'source_original_id' => $order->branch_id ?? 1,
            'code' => $order->store->code ?? 'CABANG-01',
            'name' => $order->store->name ?? 'Cabang Utama',
        ]);

        return response()->json(['status' => 'success']);
    }
}
```

#### Di Dalam Livewire POS Kasir (Background Job Push)
```php
namespace App\Livewire\Pos;

use Devaspid\Safi\Jobs\SyncToSafiJob;
use Livewire\Component;

class CashierCheckout extends Component
{
    public function processPayment()
    {
        $trx = $this->saveLocalTransaction();

        // Push ke SAFI secara non-blocking via Background Queue
        dispatch(new SyncToSafiJob(
            transactions: [$trx->toSafiArray()],
            channel: [
                'source_original_id' => auth()->user()->branch_id,
                'code' => auth()->user()->branch_code,
                'name' => auth()->user()->branch_name,
            ]
        ));

        $this->dispatch('transaction-completed');
    }
}
```

#### Di Dalam Laravel Scheduler (`routes/console.php`)
```php
use Illuminate\Support\Facades\Schedule;

// Otomatis push agregasi setiap jam ke SAFI
Schedule::command('safi:sync-hourly')->hourly();
Schedule::command('safi:sync-daily')->dailyAt('00:30');
```

---

### Menyediakan Endpoint "Tarik Cabang dari API" (Branch Pull API)

Ketika Super User menekan tombol **"Tarik Cabang dari API"** di menu **Kelola Cabang** pada SAFI Hub (`/app/branches`), server SAFI akan mengirimkan HTTP request `GET` ke URL API tenant Anda yang terdaftar (misal: `https://pos.perusahaan.com/api/safi/channels`).

Aplikasi Laravel client Anda dapat menyediakan endpoint ini dengan mudah:

#### 1. Daftarkan Route di `routes/api.php`
```php
use App\Http\Controllers\Api\SafiBranchExportController;
use Illuminate\Support\Facades\Route;

Route::get('/safi/channels', [SafiBranchExportController::class, 'index']);
```

#### 2. Buat Controller: `app/Http/Controllers/Api/SafiBranchExportController.php`
```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch; // Atau User / Mitra Pengelola untuk sistem Crowdfunding
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SafiBranchExportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // 1. Verifikasi X-API-KEY yang dikirimkan oleh server SAFI
        $providedKey = $request->header('X-API-KEY');
        $expectedKey = config('safi.api_key', env('SAFI_API_KEY'));

        if (empty($providedKey) || $providedKey !== $expectedKey) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Header X-API-KEY tidak valid.',
            ], 401);
        }

        // 2. Query daftar cabang / pengelola aktif di sistem lokal Anda:
        // - Untuk Retail POS: Tabel Cabang Toko / Outlet
        // - Untuk E-Commerce: Tabel Toko Online / Channel Penjualan
        // - Untuk Crowdfunding: Tabel User / Mitra Pengelola Campaign / Fundraiser
        $branches = Branch::where('is_active', true)
            ->get()
            ->map(function ($branch) {
                return [
                    'id' => $branch->id,
                    'code' => $branch->code ?? 'USR-'.$branch->id,
                    'name' => $branch->name,
                    'is_active' => (bool) ($branch->is_active ?? true),
                ];
            });

        return response()->json([
            'success' => true,
            'channels' => $branches,
        ]);
    }
}
```

### 🗺️ Pemetaan Konsep Entitas Lintas Model Bisnis:
| Model Bisnis | Cabang / Channel (`channels`) | Produk / Item (`items`) | Transaksi |
|---|---|---|---|
| **Retail POS** | Cabang / Outlet Toko Fisik | Menu / Barang Dagang | Struk Belanja Kasir |
| **E-Commerce** | Toko Online / Marketplace | Produk / SKU | Order Checkout |
| **Crowdfunding** | **User / Mitra Pengelola Campaign** | **Program Campaign / Infaq** | Donasi Masuk |

*Catatan: URL `https://aplikasi-anda.com/api/safi/channels` ini kemudian dimasukkan ke kolom **POS API URL / Shop API URL / Crowdfunding API URL** pada konfigurasi Tenant di Portal Admin SAFI.*

---

## Pengujian Otomatis (*Automated Testing*)

Package harus dilengkapi dengan *test suite* independen menggunakan **Orchestra Testbench**:

### Base TestCase: `tests/TestCase.php`

```php
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
```

### Feature Test Ingestion: `tests/Feature/ClientIngestionTest.php`

```php
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
```

---

## GitHub Actions CI Matrix (`.github/workflows/run-tests.yml`)

Workflow ini memastikan package lulus uji coba pada semua kombinasi PHP 8.0 s/d 8.4 dan Laravel 9 s/d 13:

```yaml
name: Run Package Tests

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      fail-fast: false
      matrix:
        php: [8.0, 8.1, 8.2, 8.3, 8.4]
        laravel: [9.*, 10.*, 11.*, 12.*, 13.*]
        exclude:
          # PHP 8.0 hanya kompatibel dengan Laravel 9
          - php: 8.0
            laravel: 10.*
          - php: 8.0
            laravel: 11.*
          - php: 8.0
            laravel: 12.*
          - php: 8.0
            laravel: 13.*
          # PHP 8.1 hanya sampai Laravel 10
          - php: 8.1
            laravel: 11.*
          - php: 8.1
            laravel: 12.*
          - php: 8.1
            laravel: 13.*

    name: PHP ${{ matrix.php }} — Laravel ${{ matrix.laravel }}

    steps:
      - name: Checkout Code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          extensions: dom, curl, libxml, mbstring, zip, sqlite3
          coverage: none

      - name: Install Dependencies
        run: |
          composer require "illuminate/support:${{ matrix.laravel }}" --no-interaction --no-update
          composer update --prefer-dist --no-interaction

      - name: Execute Tests
        run: vendor/bin/pest
```

---

## Panduan Publikasi Open Source di Packagist

**Langkah 1 — Inisialisasi Git & Tag Rilis**

```bash
git init
git remote add origin https://github.com/dev-asp-id/safi-laravel-client.git
git add .
git commit -m "feat: initial release v1.0.0 — devaspid/safi-laravel-client"
git tag v1.0.0
git push origin main --tags
```

**Langkah 2 — Submit ke Packagist**

- Buka [packagist.org](https://packagist.org) dan login dengan akun GitHub `dev-asp-id`.
- Klik **Submit** dan masukkan URL: `https://github.com/dev-asp-id/safi-laravel-client`.
- Aktifkan **GitHub Webhook** di pengaturan repository agar setiap tag baru otomatis ter-update di Packagist.

**Langkah 3 — Instalasi di Aplikasi Client**

Developer manapun cukup menjalankan:

```bash
composer require devaspid/safi-laravel-client

# Publish file konfigurasi ke project client
php artisan vendor:publish --tag=safi-config
```

Tambahkan ke `.env` project client:

```env
SAFI_BASE_URL=https://safi.asp.web.id
SAFI_API_KEY=safi_live_xxxxxxxxxxxx
SAFI_SOURCE_TYPE=pos
SAFI_DEFAULT_CHANNEL_CODE=MAIN-01
```

**Langkah 4 — Verifikasi Koneksi**

```php
use Devaspid\Safi\Facades\Safi;

// Di Tinker atau controller debug:
Safi::testConnection(); // returns true/false
```
