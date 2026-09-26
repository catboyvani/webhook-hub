<?php

namespace App\Services\Webhooks;

use App\Contracts\WebhookAdapterContract;
use App\Models\InboundEvent;
use App\Models\NormalizedEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MessengerAdapter implements WebhookAdapterContract
{
    public function __construct(private readonly string $secret)
    {
    }

    public function source(): string
    {
        return 'messenger';
    }

    public function extractExternalId(array $payload): string
    {
        return (string) ($payload['message_id'] ?? '');
    }

    public function verifySignature(Request $request): bool
    {
        return hash_equals($this->secret, (string) $request->header('X-Webhook-Secret', ''));
    }

    public function normalize(InboundEvent $event): array
    {
        $payload = $event->payload;

        return [
            'type' => NormalizedEvent::TYPE_MESSAGE_RECEIVED,
            'subject' => $payload['sender_name'] ?? $payload['from'] ?? null,
            // Единственный источник, который реально присылает имя собеседника.
            'contact_name' => $payload['sender_name'] ?? null,
            'contact_value' => $payload['from'] ?? null,
            'contact_type' => 'phone',
            'body' => $payload['text'] ?? null,
            'metadata' => ['chat_id' => $payload['chat_id'] ?? null],
            'occurred_at' => isset($payload['timestamp']) ? Carbon::parse($payload['timestamp']) : now(),
        ];
    }
}
