# SAFI Laravel Client SDK

[![Latest Version on Packagist](https://img.shields.io/packagist/v/devaspid/safi-laravel-client.svg?style=flat-square)](https://packagist.org/packages/devaspid/safi-laravel-client)
[![Total Downloads](https://img.shields.io/packagist/dt/devaspid/safi-laravel-client.svg?style=flat-square)](https://packagist.org/packages/devaspid/safi-laravel-client)
[![License](https://img.shields.io/packagist/l/devaspid/safi-laravel-client.svg?style=flat-square)](LICENSE.md)

Laravel Client SDK untuk terhubung dengan **SAFI by devASPid**. Package ini mempermudah pengiriman data (agregasi) ke server SAFI.

---

## 📌 Fitur Utama

- ⚡ **Dua Mode Integrasi Flexible:**
  - **Pull Mode (Rekomendasi):** Server SAFI yang menarik data transaksi & cabang secara otomatis dari aplikasi Anda.
  - **Push Mode:** Aplikasi Anda yang mengirim data transaksi (*real-time* atau *background job*) ke SAFI Server.
- 🔄 **Auto-Retry & Fault Tolerance:** Proteksi otomatis saat koneksi jaringan tidak stabil.
- 📊 **Hourly Aggregator Helper:** Mengelompokkan transaksi lokal secara otomatis per jam (0–23) dan per cabang.
- 🤹 **Multi-Platform Support:** Siap digunakan untuk Retail POS, E-Commerce, dan Crowdfunding.
- 🧪 **Compatibility:** Mendukung PHP 8.0 s/d 8.4+ dan Laravel 9.0 s/d 13.0+.

---

## 📦 Persyaratan Sistem

| Komponen | Versi yang Didukung |
|---|---|
| **PHP Runtime** | `^8.0` \| `^8.1` \| `^8.2` \| `^8.3` \| `^8.4` |
| **Laravel Framework** | `^9.0` \| `^10.0` \| `^11.0` \| `^12.0` \| `^13.0` |

---

## 🚀 Instalasi

Jalankan perintah Composer berikut di terminal proyek Laravel Anda:

```bash
composer require devaspid/safi-laravel-client
```

Publish file konfigurasi `safi.php` ke proyek Anda:

```bash
php artisan vendor:publish --tag=safi-config
```

---

## ⚙️ Konfigurasi `.env`

Tambahkan variabel lingkungan berikut di file `.env` aplikasi Anda:

```env
# URL Server SAFI Hub Anda
SAFI_BASE_URL=https://safi.domainanda.com

# Secret API Key Tenant yang didapatkan dari Admin Portal SAFI
SAFI_API_KEY=safi_live_xxxxxxxxxxxxxxxx

# Tipe Platform: 'pos', 'online_shop', atau 'crowdfunding'
SAFI_SOURCE_TYPE=pos

# Identitas Default Cabang/Channel Utama
SAFI_DEFAULT_CHANNEL_CODE=MAIN-01
SAFI_DEFAULT_CHANNEL_NAME="Cabang Utama"

# Pengaturan Timeout & Retry (Opsional)
SAFI_TIMEOUT=15
SAFI_RETRY_TIMES=3
SAFI_RETRY_SLEEP_MS=500
```

---

## 💻 Panduan Penggunaan & Integrasi

SAFI mendukung **dua mode integrasi**. Anda bisa memilih salah satu atau menggabungkan keduanya sesuai kebutuhan.

---

### 🟢 MODE 1: PULL PROVIDER (Rekomendasi Utama)

Dalam mode ini, server SAFI Hub yang akan melakukan request `GET` secara berkala ke aplikasi Anda untuk mengambil data transaksi atau daftar cabang.

#### 1. Endpoint Transaksi Agregat (`GET /api/safi/sync`)

Daftarkan route di `routes/api.php`:

```php
use App\Http\Controllers\Api\SafiSyncExportController;
use Illuminate\Support\Facades\Route;

Route::get('/safi/sync', [SafiSyncExportController::class, 'export']);
```

Buat controller `app/Http/Controllers/Api/SafiSyncExportController.php`:

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
        // 1. Verifikasi X-API-KEY dari SAFI Hub
        $apiKey = $request->header('X-API-KEY');
        if ($apiKey !== config('safi.api_key')) {
            return response()->json(['message' => 'Unauthorized: Invalid API Key'], 401);
        }

        $date = $request->query('date', now()->format('Y-m-d'));

        // 2. Query transaksi lokal pada tanggal bersangkutan
        $orders = Order::whereDate('created_at', $date)->get();

        // 3. Kembalikan format JSON sesuai standar SAFI
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

#### 2. Endpoint Tarik Cabang (`GET /api/safi/channels`)

Endpoint ini dipanggil saat tombol **"Tarik Cabang dari API"** ditekan pada Portal Admin SAFI.

Daftarkan route di `routes/api.php`:

```php
use App\Http\Controllers\Api\SafiBranchExportController;
use Illuminate\Support\Facades\Route;

Route::get('/safi/channels', [SafiBranchExportController::class, 'index']);
```

Buat controller `app/Http/Controllers/Api/SafiBranchExportController.php`:

```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch; // Sesuai entitas cabang/toko/mitra di sistem Anda
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SafiBranchExportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $providedKey = $request->header('X-API-KEY');
        if (empty($providedKey) || $providedKey !== config('safi.api_key')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $branches = Branch::where('is_active', true)
            ->get()
            ->map(fn($branch) => [
                'id' => $branch->id,
                'code' => $branch->code ?? 'USR-'.$branch->id,
                'name' => $branch->name,
                'is_active' => (bool) $branch->is_active,
            ]);

        return response()->json([
            'success' => true,
            'channels' => $branches,
        ]);
    }
}
```

---

### 🔵 MODE 2: PUSH CLIENT (Pengiriman Real-time)

Aplikasi Anda mengirimkan data transaksi secara aktif menggunakan Facade `Safi::`.

#### A. Pengiriman Langsung dari Controller / Webhook

```php
use Devaspid\Safi\Facades\Safi;

public function checkoutSuccess(Order $order)
{
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
        'code' => 'CABANG-01',
        'name' => 'Cabang Utama',
    ]);

    return response()->json(['status' => 'success']);
}
```

#### B. Pengiriman Non-Blocking via Queue Job (Livewire / Background)

```php
use Devaspid\Safi\Jobs\SyncToSafiJob;

public function processPayment()
{
    $trx = $this->saveLocalTransaction();

    // Jalankan pengiriman di background queue
    dispatch(new SyncToSafiJob(
        transactions: [$trx->toSafiArray()],
        channel: [
            'source_original_id' => auth()->user()->branch_id,
            'code' => auth()->user()->branch_code,
            'name' => auth()->user()->branch_name,
        ]
    ));
}
```

---

## 🗺️ Pemetaan Entitas per Model Bisnis

Gunakan tabel ini sebagai panduan saat memetakan kolom database Anda ke format DTO SAFI:

| Model Bisnis | Cabang / Channel (`channels`) | Produk / Item (`items`) | Transaksi (`transactions`) |
|---|---|---|---|
| **Retail POS** | Cabang / Outlet Toko | Menu / Barang Dagang | Struk Belanja Kasir |
| **E-Commerce** | Toko Online / Marketplace | Produk / SKU | Order Checkout |
| **Crowdfunding** | User / Mitra Pengelola Campaign | Program Campaign / Infaq | Donasi Masuk |

---

## 🧪 Verifikasi Koneksi

Lakukan pengujian koneksi ke server SAFI Hub dari `php artisan tinker`:

```php
use Devaspid\Safi\Facades\Safi;

Safi::testConnection(); 
// Menghasilkan true jika API Key dan SAFI_BASE_URL terkonfigurasi dengan benar.
```

---

## 🧪 Running Tests

Untuk menjalankan unit test pada package ini:

```bash
composer test
```

Untuk mengeksekusi tes beserta *code coverage*:

```bash
composer test-coverage
```

---

## 📄 Lisensi

Package ini berlisensi open-source di bawah [MIT License](LICENSE.md).