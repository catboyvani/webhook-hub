<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessInboundEvent;
use App\Models\InboundEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventsController extends Controller
{
    public function index(): View
    {
        return view('events.index', [
            'sources' => ['telephony', 'messenger', 'email'],
            'statuses' => ['pending', 'processed', 'failed'],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = InboundEvent::query();

        $query->when($request->filled('source'), fn ($q) => $q->where('source', $request->string('source')));
        $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));
        $query->when($request->filled('date_from'), fn ($q) => $q->whereDate('received_at', '>=', $request->date('date_from')));
        $query->when($request->filled('date_to'), fn ($q) => $q->whereDate('received_at', '<=', $request->date('date_to')));

        $sort = in_array($request->get('sort'), ['received_at', 'status', 'source'], true) ? $request->get('sort') : 'received_at';
        $direction = $request->get('direction') === 'asc' ? 'asc' : 'desc';

        $events = $query->orderBy($sort, $direction)->paginate(20)->withQueryString();

        return response()->json($events);
    }

    public function show(InboundEvent $event): JsonResponse
    {
        $event->load('normalizedEvent.contact', 'normalizedEvent.tasks');

        return response()->json([
            'id' => $event->id,
            'source' => $event->source,
            'external_id' => $event->external_id,
            'status' => $event->status,
            'signature_valid' => $event->signature_valid,
            'payload' => $event->payload,
            'received_at' => $event->received_at,
            'processed_at' => $event->processed_at,
            'normalized_event' => $event->normalizedEvent,
        ]);
    }

    public function replay(InboundEvent $event): JsonResponse
    {
        if (!$event->signature_valid) {
            return response()->json(['error' => 'signature invalid, cannot replay'], 422);
        }

        $event->update(['status' => InboundEvent::STATUS_PENDING, 'processed_at' => null]);
        ProcessInboundEvent::dispatch($event->id);

        return response()->json(['status' => 'queued']);
    }
}
