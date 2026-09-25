<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\NormalizedEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdapterNormalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_telephony_call_started_is_normalized(): void
    {
        $payload = ['event' => 'call.started', 'call_id' => 'norm-1', 'from' => '+79990000005', 'to' => '+79990000006'];
        $body = json_encode($payload);
        $signature = hash_hmac('sha256', $body, config('webhooks.telephony_secret'));

        $this->call('POST', '/webhook/telephony', [], [], [], [
            'HTTP_X-Signature' => $signature, 'CONTENT_TYPE' => 'application/json',
        ], $body)->assertStatus(202);

        $this->assertDatabaseHas('normalized_events', [
            'type' => NormalizedEvent::TYPE_CALL_STARTED,
            'contact_value' => '+79990000005',
        ]);
        $this->assertDatabaseHas('contacts', ['phone' => '+79990000005']);
    }

    public function test_call_ended_with_duration_and_known_email_creates_task(): void
    {
        $contact = Contact::create(['phone' => '+79990000007', 'email' => 'client@example.com', 'name' => 'Иван']);

        $payload = [
            'event' => 'call.ended', 'call_id' => 'norm-2',
            'from' => '+79990000007', 'to' => '+79990000008', 'duration' => 120,
        ];
        $body = json_encode($payload);
        $signature = hash_hmac('sha256', $body, config('webhooks.telephony_secret'));

        $this->call('POST', '/webhook/telephony', [], [], [], [
            'HTTP_X-Signature' => $signature, 'CONTENT_TYPE' => 'application/json',
        ], $body)->assertStatus(202);

        $this->assertDatabaseHas('tasks', ['contact_id' => $contact->id]);
    }

    public function test_call_ended_without_duration_does_not_create_task(): void
    {
        Contact::create(['phone' => '+79990000009', 'email' => 'noop@example.com']);

        $payload = ['event' => 'call.ended', 'call_id' => 'norm-3', 'from' => '+79990000009', 'duration' => 0];
        $body = json_encode($payload);
        $signature = hash_hmac('sha256', $body, config('webhooks.telephony_secret'));

        $this->call('POST', '/webhook/telephony', [], [], [], [
            'HTTP_X-Signature' => $signature, 'CONTENT_TYPE' => 'application/json',
        ], $body)->assertStatus(202);

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_email_received_is_normalized(): void
    {
        $payload = ['message_id' => 'mail-norm-1', 'from' => 'sender@example.com', 'subject' => 'Вопрос', 'body' => 'Текст'];
        $body = json_encode($payload);
        $signature = base64_encode(hash_hmac('sha256', $body, config('webhooks.email_secret'), true));

        $this->call('POST', '/webhook/email', [], [], [], [
            'HTTP_X-DKIM-Signature' => $signature, 'CONTENT_TYPE' => 'application/json',
        ], $body)->assertStatus(202);

        $this->assertDatabaseHas('normalized_events', [
            'type' => NormalizedEvent::TYPE_EMAIL_RECEIVED,
            'contact_value' => 'sender@example.com',
        ]);
    }
}