<?php

namespace Devaspid\Safi\Aggregator;

use Illuminate\Support\Collection;

class DailySummaryAggregator
{
    /**
     * Mengelompokkan transaksi per cabang menjadi array daily_summary standar SAFI.
     *
     * @param Collection $transactions Koleksi record transaksi lokal
     * @param Collection $branches Koleksi record cabang/toko lokal
     * @param string $branchForeignKey Nama foreign key cabang di transaksi (misal: 'branch_id')
     * @param string $branchPrimaryKey Nama primary key di tabel cabang (misal: 'id')
     * @param string $branchCodeField Nama kolom kode cabang (misal: 'code' atau 'channel_code')
     */
    public static function aggregate(
        Collection $transactions,
        Collection $branches,
        string $branchForeignKey = 'branch_id',
        string $branchPrimaryKey = 'id',
        string $branchCodeField = 'code',
        string $amountField = 'grand_total',
        string $cogsField = 'total_cogs',
        string $profitField = 'total_profit',
        string $discountField = 'total_discount',
        string $itemsField = 'items_count',
        string $customerIdField = 'customer_id'
    ): array {
        $summaries = [];

        foreach ($branches as $branch) {
            $branchId = is_object($branch) ? ($branch->{$branchPrimaryKey} ?? 1) : ($branch[$branchPrimaryKey] ?? 1);
            $branchCode = is_object($branch) ? ($branch->{$branchCodeField} ?? null) : ($branch[$branchCodeField] ?? null);

            $branchTx = $transactions->filter(function ($tx) use ($branchForeignKey, $branchId) {
                $val = is_object($tx) ? ($tx->{$branchForeignKey} ?? null) : ($tx[$branchForeignKey] ?? null);
                return (string) $val === (string) $branchId;
            });

            $memberCount = $branchTx->filter(function ($tx) use ($customerIdField) {
                $val = is_object($tx) ? ($tx->{$customerIdField} ?? null) : ($tx[$customerIdField] ?? null);
                return ! empty($val);
            })->count();

            $nonMemberCount = $branchTx->count() - $memberCount;

            $item = [
                'channel_original_id' => $branchId,
                'total_transactions' => $branchTx->count(),
                'total_revenue' => (float) $branchTx->sum(fn ($tx) => is_object($tx) ? ($tx->{$amountField} ?? 0) : ($tx[$amountField] ?? 0)),
                'total_cogs' => (float) $branchTx->sum(fn ($tx) => is_object($tx) ? ($tx->{$cogsField} ?? 0) : ($tx[$cogsField] ?? 0)),
                'total_profit' => (float) $branchTx->sum(fn ($tx) => is_object($tx) ? ($tx->{$profitField} ?? 0) : ($tx[$profitField] ?? 0)),
                'total_discount' => (float) $branchTx->sum(fn ($tx) => is_object($tx) ? ($tx->{$discountField} ?? 0) : ($tx[$discountField] ?? 0)),
                'total_items_sold' => (int) $branchTx->sum(fn ($tx) => is_object($tx) ? ($tx->{$itemsField} ?? 1) : ($tx[$itemsField] ?? 1)),
                'member_count' => $memberCount,
                'non_member_count' => $nonMemberCount,
            ];

            if ($branchCode !== null) {
                $item['channel_code'] = $branchCode;
            }

            $summaries[] = $item;
        }

        return $summaries;
    }
}
