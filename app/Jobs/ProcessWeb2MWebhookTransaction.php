<?php

namespace App\Jobs;

use App\Services\Web2MWebhookProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessWeb2MWebhookTransaction implements ShouldQueue, ShouldBeUnique
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;
    public int $uniqueFor = 300;

    public function __construct(
        public array $item,
        public array $rawPayload,
    ) {
        $this->onQueue('webhooks');
    }

    public function handle(Web2MWebhookProcessor $processor): void
    {
        $processor->process($this->item, $this->rawPayload);
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('web2m:' . $this->fingerprint()))
                ->expireAfter(120)
                ->releaseAfter(5),
        ];
    }

    public function uniqueId(): string
    {
        return 'web2m:' . $this->fingerprint();
    }

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Web2M webhook queue job failed.', [
            'job' => self::class,
            'queue' => $this->queue,
            'attempts' => $this->attempts(),
            'fingerprint' => $this->fingerprint(),
            'transaction_reference' => $this->transactionReference(),
            'item' => $this->item,
            'payload' => $this->rawPayload,
            'exception' => $exception?->getMessage(),
            'exception_class' => $exception ? $exception::class : null,
            'exception_code' => $exception?->getCode(),
        ]);
    }

    private function fingerprint(): string
    {
        $reference = $this->transactionReference();

        if ($reference !== '') {
            return sha1('ref:' . $reference);
        }

        $fallback = [
            'amount' => $this->item['SoTienGhiCo'] ?? $this->item['amount'] ?? null,
            'description' => $this->item['MoTa'] ?? $this->item['description'] ?? null,
            'date' => $this->item['NgayGiaoDich'] ?? $this->item['transaction_date'] ?? $this->item['date'] ?? null,
            'type' => $this->item['type'] ?? $this->item['CD'] ?? null,
        ];

        return sha1(json_encode($fallback, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function transactionReference(): string
    {
        return trim((string) (
            $this->item['SoThamChieu']
            ?? $this->item['transaction_id']
            ?? $this->item['transactionID']
            ?? $this->item['id']
            ?? ''
        ));
    }
}
