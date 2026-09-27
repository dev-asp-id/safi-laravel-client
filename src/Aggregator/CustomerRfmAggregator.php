<?php

namespace Devaspid\Safi\Aggregator;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class CustomerRfmAggregator
{
    /**
     * Mengelompokkan transaksi pelanggan menjadi metrik RFM (Recency, Frequency, Monetary).
     *
     * @param Collection $orders
     * @param string|null $referenceDate
     * @param string $customerIdField
     * @param string $dateField
     * @param string $amountField
     * @return array
     */
    public static function aggregate(
        Collection $orders,
        ?string $referenceDate = null,
        string $customerIdField = 'customer_id',
        string $dateField = 'created_at',
        string $amountField = 'grand_total'
    ): array {
        $refDate = $referenceDate ? Carbon::parse($referenceDate) : Carbon::now();
        $grouped = $orders->groupBy($customerIdField);

        $results = [];

        foreach ($grouped as $customerId => $customerOrders) {
            if (empty($customerId)) {
                continue;
            }

            $lastOrder = $customerOrders->sortByDesc($dateField)->first();
            $lastOrderDate = Carbon::parse($lastOrder->{$dateField});
            $recencyDays = (int) $lastOrderDate->diffInDays($refDate);
            $frequency = $customerOrders->count();
            $monetary = (float) $customerOrders->sum($amountField);

            $results[] = [
                'source_customer_id' => $customerId,
                'recency_days' => $recencyDays,
                'frequency' => $frequency,
                'monetary' => $monetary,
                'last_transaction_at' => $lastOrderDate->toIso8601String(),
            ];
        }

        return $results;
    }
}
