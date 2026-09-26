<?php

namespace App\Services\Webhooks;

use App\Contracts\WebhookAdapterContract;
use App\Models\InboundEvent;
use App\Models\NormalizedEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TelephonyAdapter implements WebhookAdapterContract
{
    public function __construct(private readonly string $secret)
    {
    }

    public function source(): string
    {
        return 'telephony';
    }

    public function extractExternalId(array $payload): string
    {
        return (string) ($payload['call_id'] ?? '');
    }

    public function verifySignature(Request $request): bool
    {
        $signature = (string) $request->header('X-Signature', '');
        $expected = hash_hmac('sha256', $request->getContent(), $this->secret);

        return $signature !== '' && hash_equals($expected, $signature);
    }

    public function normalize(InboundEvent $event): array
    {
        $payload = $event->payload;
        $type = ($payload['event'] ?? null) === 'call.ended'
            ? NormalizedEvent::TYPE_CALL_ENDED
            : NormalizedEvent::TYPE_CALL_STARTED;

        $occurredAt = $payload['ended_at'] ?? $payload['started_at'] ?? null;

        return [
            'type' => $type,
            'subject' => $payload['from'] ?? null,
            // Телефония не сообщает имя звонящего - только номер, поэтому имя контакта
            // не заполняем, чтобы не подменить его номером телефона.
            'contact_name' => null,
            'contact_value' => $payload['from'] ?? null,
            'contact_type' => 'phone',
            'body' => null,
            'metadata' => [
                'to' => $payload['to'] ?? null,
                'direction' => $payload['direction'] ?? null,
                'duration' => (int) ($payload['duration'] ?? 0),
            ],
            'occurred_at' => $occurredAt ? Carbon::parse($occurredAt) : now(),
        ];
    }
}
