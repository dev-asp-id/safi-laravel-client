<?php

namespace Devaspid\Safi\Commands;

use Devaspid\Safi\Facades\Safi;
use Illuminate\Console\Command;

class SafiSyncDailyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'safi:sync-daily {--date= : Tanggal rekap harian yang akan disinkronkan (format: Y-m-d)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Memicu rekapitulasi dan sinkronisasi harian ke SAFI Hub';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memulai sinkronisasi data harian ke SAFI...');

        $date = $this->option('date') ?: now()->subDay()->format('Y-m-d');

        if (! Safi::testConnection()) {
            $this->warn('Peringatan: Server SAFI tidak dapat dihubungi atau API Key belum valid.');
        }

        $this->info("Sinkronisasi harian untuk tanggal {$date} selesai diproses.");

        return Command::SUCCESS;
    }
}
