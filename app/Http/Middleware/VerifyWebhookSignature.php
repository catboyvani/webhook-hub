<?php

namespace App\Http\Middleware;

use App\Services\Webhooks\InboundEventRecorder;
use App\Services\Webhooks\WebhookAdapterRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookSignature
{
    public function __construct(
        private readonly WebhookAdapterRegistry $registry,
        private readonly InboundEventRecorder $recorder,
    ) {
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
            $this->recorder->record($source, $externalId, $payload, signatureValid: false);

            return response()->json(['error' => 'invalid signature'], 401);
        }

        $request->attributes->set('webhook_external_id', $externalId);
        $request->attributes->set('webhook_payload', $payload);

        return $next($request);
    }
}
