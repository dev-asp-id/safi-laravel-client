<?php

namespace Devaspid\Safi\Jobs;

use Devaspid\Safi\Facades\Safi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncToSafiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param array $transactions
     * @param array|null $channel
     */
    public function __construct(
        public array $transactions,
        public ?array $channel = null
    ) {
        $connection = config('safi.queue.connection');
        $queueName = config('safi.queue.name', 'default');

        if (! empty($connection)) {
            $this->onConnection($connection);
        }

        if (! empty($queueName)) {
            $this->onQueue($queueName);
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Safi::ingestRaw($this->transactions, $this->channel);
    }
}
