<?php

namespace Devaspid\Safi\DTO;

class RawTransactionPayload
{
    public function __construct(
        public array $transactions,
        public ?array $channel = null,
        public string $sourceType = 'pos'
    ) {}

    public function toArray(): array
    {
        $payload = [
            'payload_type' => 'raw',
            'source_type' => $this->sourceType,
            'transactions' => $this->transactions,
        ];

        if ($this->channel) {
            $payload['channel'] = $this->channel;
        }

        return $payload;
    }
}
