<?php

namespace App\Http\Middleware;

use App\Models\InboundEvent;
use App\Services\Webhooks\WebhookAdapterRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookSignature
{
    public function __construct(private readonly WebhookAdapterRegistry $registry)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $source = (string) $request->route('source');
        $adapter = $this->registry->get($source);

        if (!$adapter) {
            abort(404);
        }

        $payload = (array) ($request->json()->all() ?? []);
        $externalId = $adapter->extractExternalId($payload);
        $isValid = $adapter->verifySignature($request);

        if (!$isValid) {
            InboundEvent::query()->upsert([[
                'source' => $source,
                'external_id' => $externalId,
                'payload' => json_encode($payload),
                'signature_valid' => false,
                'status' => InboundEvent::STATUS_FAILED,
                'received_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]], ['source', 'external_id'], ['payload', 'signature_valid', 'status', 'received_at', 'updated_at']);

            return response()->json(['error' => 'invalid signature'], 401);
        }

        $request->attributes->set('webhook_adapter', $adapter);
        $request->attributes->set('webhook_external_id', $externalId);
        $request->attributes->set('webhook_payload', $payload);

        return $next($request);
    }
}