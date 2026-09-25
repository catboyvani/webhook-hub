<?php

namespace App\Providers;

use App\Services\Webhooks\EmailAdapter;
use App\Services\Webhooks\MessengerAdapter;
use App\Services\Webhooks\TelephonyAdapter;
use App\Services\Webhooks\WebhookAdapterRegistry;
use Illuminate\Support\ServiceProvider;

class WebhookAdapterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WebhookAdapterRegistry::class);
    }

    public function boot(): void
    {
        $registry = $this->app->make(WebhookAdapterRegistry::class);

        $registry->register(new TelephonyAdapter(config('webhooks.telephony_secret')));
        $registry->register(new MessengerAdapter(config('webhooks.messenger_secret')));
        $registry->register(new EmailAdapter(config('webhooks.email_secret')));
    }
}