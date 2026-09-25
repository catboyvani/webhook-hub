<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessInboundEvent;
use App\Models\InboundEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function __invoke(Request $request, string $source): JsonResponse
    {
        $externalId = (string) $request->attributes->get('webhook_external_id');
        $payload = (array) $request->attributes->get('webhook_payload', []);

        InboundEvent::query()->upsert([[
            'source' => $source,
            'external_id' => $externalId,
            'payload' => json_encode($payload),
            'signature_valid' => true,
            'status' => InboundEvent::STATUS_PENDING,
            'received_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]], ['source', 'external_id'], ['payload', 'signature_valid', 'status', 'received_at', 'updated_at']);

        $event = InboundEvent::query()
            ->where('source', $source)
            ->where('external_id', $externalId)
            ->firstOrFail();

        ProcessInboundEvent::dispatch($event->id);

        return response()->json(['id' => $event->id], 202);
    }
}