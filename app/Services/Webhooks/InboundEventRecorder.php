<?php

namespace App\Services\Webhooks;

use App\Models\InboundEvent;

class InboundEventRecorder
{
    /**
     * Upsert inbound-события по (source, external_id). Общая точка записи и для валидной,
     * и для невалидной подписи - раньше эта логика была продублирована в middleware
     * и контроллере с разными наборами полей.
     */
    public function record(string $source, string $externalId, array $payload, bool $signatureValid): InboundEvent
    {
        $now = now();

        InboundEvent::query()->upsert([[
            'source' => $source,
            'external_id' => $externalId,
            'payload' => json_encode($payload),
            'signature_valid' => $signatureValid,
            'status' => $signatureValid ? InboundEvent::STATUS_PENDING : InboundEvent::STATUS_FAILED,
            'received_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]], ['source', 'external_id'], ['payload', 'signature_valid', 'status', 'received_at', 'updated_at']);

        return InboundEvent::query()
            ->where('source', $source)
            ->where('external_id', $externalId)
            ->firstOrFail();
    }
}
