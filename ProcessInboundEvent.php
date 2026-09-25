<?php

namespace App\Jobs;

use App\Models\InboundEvent;
use App\Models\NormalizedEvent;
use App\Models\Task;
use App\Services\Contacts\ContactResolver;
use App\Services\Webhooks\WebhookAdapterRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessInboundEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $inboundEventId)
    {
    }

    public function handle(WebhookAdapterRegistry $registry, ContactResolver $contacts): void
    {
        $event = InboundEvent::find($this->inboundEventId);

        if (!$event || !$event->signature_valid) {
            return;
        }

        $adapter = $registry->get($event->source);

        if (!$adapter) {
            $event->update(['status' => InboundEvent::STATUS_FAILED]);
            return;
        }

        $data = $adapter->normalize($event);

        DB::transaction(function () use ($event, $data, $contacts) {
            $contact = $contacts->resolve(
                $data['contact_type'] === 'phone' ? $data['contact_value'] : null,
                $data['contact_type'] === 'email' ? $data['contact_value'] : null,
                $data['subject'],
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