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
     * @return array{type:string,subject:?string,contact_value:?string,contact_type:?string,body:?string,metadata:array<string,mixed>,occurred_at:\Illuminate\Support\Carbon}
     */
    public function normalize(InboundEvent $event): array;
}