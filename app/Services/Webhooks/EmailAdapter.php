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

    // Настоящий DKIM - это асимметричная подпись, публичный ключ которой публикуется
    // в DNS TXT-записи домена отправителя (заголовок DKIM-Signature). Здесь используется
    // HMAC общим секретом почтового шлюза - проще для pet-проекта, но не DKIM, поэтому
    // и заголовок называется честно, без DKIM в названии.
    public function verifySignature(Request $request): bool
    {
        $signature = (string) $request->header('X-Email-Signature', '');
        $expected = base64_encode(hash_hmac('sha256', $request->getContent(), $this->secret, true));

        return $signature !== '' && hash_equals($expected, $signature);
    }

    public function normalize(InboundEvent $event): array
    {
        $payload = $event->payload;

        return [
            'type' => NormalizedEvent::TYPE_EMAIL_RECEIVED,
            // Тема письма - это подпись события в журнале, а не имя контакта.
            'subject' => $payload['subject'] ?? null,
            // Имя отправителя, если шлюз его передаёт отдельным полем (display name
            // из "From: Имя <email>"). Тему письма в имя контакта никогда не пишем.
            'contact_name' => $payload['from_name'] ?? null,
            'contact_value' => $payload['from'] ?? null,
            'contact_type' => 'email',
            'body' => $payload['body'] ?? null,
            'metadata' => ['to' => $payload['to'] ?? null],
            'occurred_at' => isset($payload['received_at']) ? Carbon::parse($payload['received_at']) : now(),
        ];
    }
}
