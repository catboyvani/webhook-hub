<?php

namespace App\Services\Webhooks;

use App\Contracts\WebhookAdapterContract;
use App\Models\InboundEvent;
use App\Models\NormalizedEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class EmailAdapter implements WebhookAdapterContract
{
    public function __construct(private readonly string $secret)
    {
    }

    public function source(): string
    {
        return 'email';
    }

    public function extractExternalId(array $payload): string
    {
        return (string) ($payload['message_id'] ?? '');
    }

    // Полноценный DKIM требует DNS TXT-lookup публичного ключа домена отправителя.
    // Здесь это упрощено до HMAC-подписи тела письма общим секретом почтового шлюза.
    public function verifySignature(Request $request): bool
    {
        $signature = (string) $request->header('X-DKIM-Signature', '');
        $expected = base64_encode(hash_hmac('sha256', $request->getContent(), $this->secret, true));

        return $signature !== '' && hash_equals($expected, $signature);
    }

    public function normalize(InboundEvent $event): array
    {
        $payload = $event->payload;

        return [
            'type' => NormalizedEvent::TYPE_EMAIL_RECEIVED,
            'subject' => $payload['subject'] ?? null,
            'contact_value' => $payload['from'] ?? null,
            'contact_type' => 'email',
            'body' => $payload['body'] ?? null,
            'metadata' => ['to' => $payload['to'] ?? null],
            'occurred_at' => isset($payload['received_at']) ? Carbon::parse($payload['received_at']) : now(),
        ];
    }
}