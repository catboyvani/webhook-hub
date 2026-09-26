<?php

namespace Tests\Feature;

use App\Models\InboundEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeduplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_external_id_updates_instead_of_creating(): void
    {
        $payload = ['message_id' => 'dup-1', 'from' => '+79990000004', 'text' => 'первое'];

        $this->postJson('/webhook/messenger', $payload, [
            'X-Webhook-Secret' => config('webhooks.messenger_secret'),
        ])->assertStatus(202);

        $payload['text'] = 'обновлённое';

        $this->postJson('/webhook/messenger', $payload, [
            'X-Webhook-Secret' => config('webhooks.messenger_secret'),
        ])->assertStatus(202);

        $this->assertSame(1, InboundEvent::query()->where('external_id', 'dup-1')->count());

        $event = InboundEvent::query()->where('external_id', 'dup-1')->firstOrFail();
        $this->assertSame('обновлённое', $event->payload['text']);
    }
}
