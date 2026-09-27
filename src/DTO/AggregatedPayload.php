<?php

namespace Devaspid\Safi\DTO;

class AggregatedPayload
{
    public function __construct(
        public array $channels,
        public array $customers = [],
        public ?string $syncTimestamp = null
    ) {}

    public function toArray(): array
    {
        $payload = [
            'payload_type' => 'aggregated',
            'sync_timestamp' => $this->syncTimestamp ?: now()->toIso8601String(),
            'channels' => $this->channels,
        ];

        if (! empty($this->customers)) {
            $payload['customers'] = $this->customers;
        }

        return $payload;
    }
}
