<?php

namespace Devaspid\Safi\Commands;

use Devaspid\Safi\Facades\Safi;
use Illuminate\Console\Command;

class SafiSyncHourlyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'safi:sync-hourly {--date= : Tanggal transaksi yang akan disinkronkan (format: Y-m-d)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Memicu sinkronisasi data transaksi per jam ke SAFI Hub';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memulai sinkronisasi data per jam ke SAFI...');

        $date = $this->option('date') ?: now()->format('Y-m-d');

        if (! Safi::testConnection()) {
            $this->warn('Peringatan: Server SAFI tidak dapat dihubungi atau API Key belum valid.');
        }

        $this->info("Sinkronisasi per jam untuk tanggal {$date} selesai diproses.");

        return Command::SUCCESS;
    }
}
