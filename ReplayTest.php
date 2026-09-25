<?php

namespace Tests\Feature;

use App\Models\InboundEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReplayTest extends TestCase
{
    use RefreshDatabase;

    private function makeProcessedEvent(string $externalId, string $phone): InboundEvent
    {
        return InboundEvent::create([
            'source' => 'messenger',
            'external_id' => $externalId,
            'payload' => ['message_id' => $externalId, 'from' => $phone, 'text' => 'исходное'],
            'signature_valid' => true,
            'status' => InboundEvent::STATUS_PROCESSED,
            'received_at' => now(),
            'processed_at' => now(),
        ]);
    }

    public function test_replay_command_reprocesses_event(): void
    {
        $event = $this->makeProcessedEvent('replay-1', '+79990000010');

        $this->artisan('webhooks:replay', ['inbound_event_id' => $event->id])->assertExitCode(0);

        $this->assertDatabaseHas('normalized_events', [
            'inbound_event_id' => $event->id, 'contact_value' => '+79990000010',
        ]);

        $event->refresh();
        $this->assertSame(InboundEvent::STATUS_PROCESSED, $event->status);
        $this->assertNotNull($event->processed_at);
    }

    public function test_replay_command_fails_for_missing_event(): void
    {
        $this->artisan('webhooks:replay', ['inbound_event_id' => 999])->assertExitCode(1);
    }

    public function test_replay_endpoint_requeues_processing(): void
    {
        $event = $this->makeProcessedEvent('replay-2', '+79990000011');

        $this->postJson("/events/{$event->id}/replay")->assertStatus(200);

        $this->assertDatabaseHas('normalized_events', ['inbound_event_id' => $event->id]);
    }
}