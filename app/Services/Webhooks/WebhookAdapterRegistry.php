<?php

namespace App\Services\Webhooks;

use App\Contracts\WebhookAdapterContract;

class WebhookAdapterRegistry
{
    /** @var array<string, WebhookAdapterContract> */
    private array $adapters = [];

    public function register(WebhookAdapterContract $adapter): void
    {
        $this->adapters[$adapter->source()] = $adapter;
    }

    public function get(string $source): ?WebhookAdapterContract
    {
        return $this->adapters[$source] ?? null;
    }
}
