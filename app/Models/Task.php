<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    protected $fillable = ['contact_id', 'normalized_event_id', 'title', 'description', 'status'];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function normalizedEvent(): BelongsTo
    {
        return $this->belongsTo(NormalizedEvent::class);
    }
}
