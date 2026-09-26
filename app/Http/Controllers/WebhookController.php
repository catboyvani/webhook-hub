<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessInboundEvent;
use App\Services\Webhooks\InboundEventRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function __invoke(Request $request, string $source, InboundEventRecorder $recorder): JsonResponse
    {
        $externalId = (string) $request->attributes->get('webhook_external_id');
        $payload = (array) $request->attributes->get('webhook_payload', []);

        $event = $recorder->record($source, $externalId, $payload, signatureValid: true);

        ProcessInboundEvent::dispatch($event->id);

        return response()->json(['id' => $event->id], 202);
    }
}
