<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NormalizedEvent extends Model
{
    public const TYPE_CALL_STARTED = 'call_started';
    public const TYPE_CALL_ENDED = 'call_ended';
    public const TYPE_MESSAGE_RECEIVED = 'message_received';
    public const TYPE_EMAIL_RECEIVED = 'email_received';

    protected $fillable = [
        'inbound_event_id', 'type', 'subject', 'contact_value', 'contact_id', 'body', 'metadata', 'occurred_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function inboundEvent(): BelongsTo
    {
        return $this->belongsTo(InboundEvent::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}