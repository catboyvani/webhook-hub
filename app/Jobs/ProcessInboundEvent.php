<?php

namespace App\Jobs;

use App\Models\InboundEvent;
use App\Models\NormalizedEvent;
use App\Models\Task;
use App\Services\Contacts\ContactResolver;
use App\Services\Webhooks\WebhookAdapterRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessInboundEvent implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    // Держим "в уникальном" состоянии на время, разумное для одной обработки: если этот же
    // inbound_event_id придёт в очередь второй раз (повторная доставка вебхука + replay
    // почти одновременно), второй диспатч будет просто проигнорирован, а не встанет
    // в очередь параллельно первому.
    public int $uniqueFor = 300;

    public function __construct(public readonly int $inboundEventId)
    {
    }

    public function uniqueId(): string
    {
        return (string) $this->inboundEventId;
    }

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(WebhookAdapterRegistry $registry, ContactResolver $contacts): void
    {
        $event = InboundEvent::find($this->inboundEventId);

        if (!$event) {
            Log::warning('ProcessInboundEvent: событие не найдено, пропускаем', [
                'inbound_event_id' => $this->inboundEventId,
            ]);
            return;
        }

        if (!$event->signature_valid) {
            Log::warning('ProcessInboundEvent: подпись невалидна, обработка пропущена', [
                'inbound_event_id' => $event->id,
            ]);
            return;
        }

        $adapter = $registry->get($event->source);

        if (!$adapter) {
            Log::error('ProcessInboundEvent: адаптер для источника не зарегистрирован', [
                'inbound_event_id' => $event->id,
                'source' => $event->source,
            ]);
            $event->update(['status' => InboundEvent::STATUS_FAILED]);
            return;
        }

        $data = $adapter->normalize($event);

        DB::transaction(function () use ($event, $data, $contacts) {
            $contact = $contacts->resolve(
                $data['contact_type'] === 'phone' ? $data['contact_value'] : null,
                $data['contact_type'] === 'email' ? $data['contact_value'] : null,
                $data['contact_name'] ?? null,
            );

            $normalized = NormalizedEvent::updateOrCreate(
                ['inbound_event_id' => $event->id],
                [
                    'type' => $data['type'],
                    'subject' => $data['subject'],
                    'contact_value' => $data['contact_value'],
                    'contact_id' => $contact?->id,
                    'body' => $data['body'],
                    'metadata' => $data['metadata'],
                    'occurred_at' => $data['occurred_at'],
                ]
            );

            $duration = (int) ($data['metadata']['duration'] ?? 0);

            if ($data['type'] === NormalizedEvent::TYPE_CALL_ENDED && $duration > 0 && $contact?->email) {
                Task::updateOrCreate(
                    ['normalized_event_id' => $normalized->id],
                    [
                        'contact_id' => $contact->id,
                        'title' => 'Перезвонить: ' . ($contact->name ?? $contact->phone),
                        'description' => sprintf('Завершён звонок длительностью %d сек.', $duration),
                        'status' => 'open',
                    ]
                );
            }

            $event->update(['status' => InboundEvent::STATUS_PROCESSED, 'processed_at' => now()]);
        });
    }
}
