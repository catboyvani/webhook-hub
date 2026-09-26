<?php

namespace Tests\Feature;

use App\Models\InboundEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookSignatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_telephony_rejects_invalid_signature(): void
    {
        $payload = ['event' => 'call.started', 'call_id' => 'call-1', 'from' => '+79990000000'];

        $this->postJson('/webhook/telephony', $payload, ['X-Signature' => 'wrong'])
            ->assertStatus(401);

        $this->assertDatabaseHas('inbound_events', [
            'source' => 'telephony',
            'external_id' => 'call-1',
            'signature_valid' => false,
            'status' => InboundEvent::STATUS_FAILED,
        ]);
    }

    public function test_telephony_accepts_valid_signature(): void
    {
        $payload = ['event' => 'call.started', 'call_id' => 'call-2', 'from' => '+79990000001'];
        $body = json_encode($payload);
        $signature = hash_hmac('sha256', $body, config('webhooks.telephony_secret'));

        $this->call('POST', '/webhook/telephony', [], [], [], [
            'HTTP_X-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $body)->assertStatus(202);

        $this->assertDatabaseHas('inbound_events', [
            'external_id' => 'call-2', 'signature_valid' => true,
        ]);
    }

    public function test_messenger_rejects_invalid_secret(): void
    {
        $payload = ['message_id' => 'msg-1', 'from' => '+79990000002', 'text' => 'привет'];

        $this->postJson('/webhook/messenger', $payload, ['X-Webhook-Secret' => 'wrong'])
            ->assertStatus(401);

        $this->assertDatabaseHas('inbound_events', ['source' => 'messenger', 'signature_valid' => false]);
    }

    public function test_messenger_accepts_valid_secret(): void
    {
        $payload = ['message_id' => 'msg-2', 'from' => '+79990000003', 'text' => 'привет'];

        $this->postJson('/webhook/messenger', $payload, [
            'X-Webhook-Secret' => config('webhooks.messenger_secret'),
        ])->assertStatus(202);

        $this->assertDatabaseHas('inbound_events', ['source' => 'messenger', 'signature_valid' => true]);
    }

    public function test_email_rejects_invalid_signature(): void
    {
        $payload = ['message_id' => 'mail-1', 'from' => 'a@example.com', 'subject' => 'тест'];

        $this->postJson('/webhook/email', $payload, ['X-Email-Signature' => 'wrong'])
            ->assertStatus(401);

        $this->assertDatabaseHas('inbound_events', ['source' => 'email', 'signature_valid' => false]);
    }

    public function test_email_accepts_valid_signature(): void
    {
        $payload = ['message_id' => 'mail-2', 'from' => 'b@example.com', 'subject' => 'тест'];
        $body = json_encode($payload);
        $signature = base64_encode(hash_hmac('sha256', $body, config('webhooks.email_secret'), true));

        $this->call('POST', '/webhook/email', [], [], [], [
            'HTTP_X-Email-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $body)->assertStatus(202);

        $this->assertDatabaseHas('inbound_events', ['source' => 'email', 'signature_valid' => true]);
    }
}
