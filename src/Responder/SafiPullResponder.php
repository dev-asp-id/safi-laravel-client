<?php

namespace Devaspid\Safi\Responder;

use Devaspid\Safi\Aggregator\CustomerRfmAggregator;
use Devaspid\Safi\Aggregator\DailySummaryAggregator;
use Devaspid\Safi\Aggregator\HourlyTransactionAggregator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SafiPullResponder
{
    protected Request $request;
    protected ?string $sourceType = null;
    protected ?string $apiKey = null;
    protected ?string $targetDate = null;
    protected ?string $branchCode = null;
    protected mixed $branchId = null;

    protected Collection $branches;
    protected Collection $orders;
    protected array $customCustomers = [];

    // Mapping field names
    protected string $branchForeignKey = 'branch_id';
    protected string $branchPrimaryKey = 'id';
    protected string $branchCodeField = 'code';
    protected string $branchNameField = 'name';
    protected string $dateField = 'created_at';
    protected string $amountField = 'grand_total';
    protected string $cogsField = 'total_cogs';
    protected string $profitField = 'total_profit';
    protected string $discountField = 'total_discount';
    protected string $itemsField = 'items_count';
    protected string $customerIdField = 'customer_id';

    public function __construct(?Request $request = null)
    {
        $this->request = $request ?? request();
        $this->sourceType = config('safi.source_type', 'pos');
        $this->apiKey = config('safi.api_key', env('SAFI_API_KEY'));

        $this->targetDate = $this->request->query('date', now()->format('Y-m-d'));
        $this->branchCode = $this->request->query('branch_code') ?? $this->request->query('channel_code');
        $this->branchId = $this->request->query('branch_id');

        $this->branches = collect();
        $this->orders = collect();
    }

    public static function forRequest(?Request $request = null): self
    {
        return new self($request);
    }

    public function sourceType(string $sourceType): self
    {
        $this->sourceType = $sourceType;
        return $this;
    }

    public function branches(Collection|array $branches): self
    {
        $this->branches = $branches instanceof Collection ? $branches : collect($branches);
        return $this;
    }

    public function orders(Collection|array $orders): self
    {
        $this->orders = $orders instanceof Collection ? $orders : collect($orders);
        return $this;
    }

    public function customers(array $customers): self
    {
        $this->customCustomers = $customers;
        return $this;
    }

    public function fields(
        ?string $branchForeignKey = null,
        ?string $branchPrimaryKey = null,
        ?string $branchCodeField = null,
        ?string $branchNameField = null,
        ?string $dateField = null,
        ?string $amountField = null,
        ?string $cogsField = null,
        ?string $profitField = null,
        ?string $discountField = null,
        ?string $itemsField = null,
        ?string $customerIdField = null
    ): self {
        if ($branchForeignKey) $this->branchForeignKey = $branchForeignKey;
        if ($branchPrimaryKey) $this->branchPrimaryKey = $branchPrimaryKey;
        if ($branchCodeField) $this->branchCodeField = $branchCodeField;
        if ($branchNameField) $this->branchNameField = $branchNameField;
        if ($dateField) $this->dateField = $dateField;
        if ($amountField) $this->amountField = $amountField;
        if ($cogsField) $this->cogsField = $cogsField;
        if ($profitField) $this->profitField = $profitField;
        if ($discountField) $this->discountField = $discountField;
        if ($itemsField) $this->itemsField = $itemsField;
        if ($customerIdField) $this->customerIdField = $customerIdField;

        return $this;
    }

    public function getTargetDate(): string
    {
        return $this->targetDate;
    }

    public function getRequestedBranchCode(): ?string
    {
        return $this->branchCode;
    }

    public function getRequestedBranchId(): mixed
    {
        return $this->branchId;
    }

    /**
     * Validasi Header X-API-KEY.
     */
    public function isValidApiKey(): bool
    {
        $headerKey = $this->request->header('X-API-KEY') ?? $this->request->header('X-Api-Key');
        return ! empty($headerKey) && ! empty($this->apiKey) && hash_equals((string) $this->apiKey, (string) $headerKey);
    }

    /**
     * Bangun dan kembalikan JsonResponse sesuai format standar SAFI Hub.
     */
    public function respond(): JsonResponse
    {
        if (! $this->isValidApiKey()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: Invalid or missing X-API-KEY header.',
            ], 401);
        }

        // Filter Cabang jika request meminta cabang tertentu
        $branches = $this->branches;
        if ($this->branchCode) {
            $branches = $branches->filter(function ($b) {
                $code = is_object($b) ? ($b->{$this->branchCodeField} ?? null) : ($b[$this->branchCodeField] ?? null);
                return (string) $code === (string) $this->branchCode;
            });
        } elseif ($this->branchId) {
            $branches = $branches->filter(function ($b) {
                $id = is_object($b) ? ($b->{$this->branchPrimaryKey} ?? null) : ($b[$this->branchPrimaryKey] ?? null);
                return (string) $id === (string) $this->branchId;
            });
        }

        // Filter Orders untuk cabang terpilih
        $selectedBranchIds = $branches->map(function ($b) {
            return is_object($b) ? ($b->{$this->branchPrimaryKey} ?? null) : ($b[$this->branchPrimaryKey] ?? null);
        })->filter()->all();

        $orders = $this->orders;
        if (! empty($selectedBranchIds) && ($this->branchCode || $this->branchId)) {
            $orders = $orders->filter(function ($o) use ($selectedBranchIds) {
                $fk = is_object($o) ? ($o->{$this->branchForeignKey} ?? null) : ($o[$this->branchForeignKey] ?? null);
                return in_array($fk, $selectedBranchIds);
            });
        }

        // Agregasi Daily & Hourly
        $dailySummary = DailySummaryAggregator::aggregate(
            transactions: $orders,
            branches: $branches,
            branchForeignKey: $this->branchForeignKey,
            branchPrimaryKey: $this->branchPrimaryKey,
            branchCodeField: $this->branchCodeField,
            amountField: $this->amountField,
            cogsField: $this->cogsField,
            profitField: $this->profitField,
            discountField: $this->discountField,
            itemsField: $this->itemsField,
            customerIdField: $this->customerIdField
        );

        $hourlySummary = HourlyTransactionAggregator::aggregateMultiBranch(
            transactions: $orders,
            branches: $branches,
            targetDate: $this->targetDate,
            branchForeignKey: $this->branchForeignKey,
            branchPrimaryKey: $this->branchPrimaryKey,
            dateField: $this->dateField,
            amountField: $this->amountField,
            cogsField: $this->cogsField,
            profitField: $this->profitField,
            discountField: $this->discountField,
            itemsField: $this->itemsField
        );

        $channelsPayload = $branches->map(function ($b) {
            $id = is_object($b) ? ($b->{$this->branchPrimaryKey} ?? 1) : ($b[$this->branchPrimaryKey] ?? 1);
            $code = is_object($b) ? ($b->{$this->branchCodeField} ?? "CABANG-{$id}") : ($b[$this->branchCodeField] ?? "CABANG-{$id}");
            $name = is_object($b) ? ($b->{$this->branchNameField} ?? "Cabang {$id}") : ($b[$this->branchNameField] ?? "Cabang {$id}");

            return [
                'source_original_id' => $id,
                'code' => $code,
                'name' => $name,
            ];
        })->values()->all();

        $responsePayload = [
            'status' => 'success',
            'source_type' => $this->sourceType,
            'date' => $this->targetDate,
            'channels' => $channelsPayload,
            'daily_summary' => $dailySummary,
            'hourly_summary' => $hourlySummary,
        ];

        if (! empty($this->customCustomers)) {
            $responsePayload['customers'] = $this->customCustomers;
        }

        return response()->json($responsePayload, 200);
    }
}
