<?php

namespace App\Contracts;

use App\Models\InboundEvent;
use Illuminate\Http\Request;

interface WebhookAdapterContract
{
    public function source(): string;

    public function extractExternalId(array $payload): string;

    public function verifySignature(Request $request): bool;

    /**
     * subject - человекочитаемая подпись события для журнала (номер, тема письма и т.д.),
     * contact_name - имя человека для карточки контакта, если источник его действительно
     * предоставляет (например, sender_name у мессенджера). Для звонков и писем обычно null -
     * не стоит путать тему письма или номер телефона с именем контакта.
     *
     * @return array{
     *     type:string,
     *     subject:?string,
     *     contact_name:?string,
     *     contact_value:?string,
     *     contact_type:?string,
     *     body:?string,
     *     metadata:array<string,mixed>,
     *     occurred_at:\Illuminate\Support\Carbon
     * }
     */
    public function normalize(InboundEvent $event): array;
}
