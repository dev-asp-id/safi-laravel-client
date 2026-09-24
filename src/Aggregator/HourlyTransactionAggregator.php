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
