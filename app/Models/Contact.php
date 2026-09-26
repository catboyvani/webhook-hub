<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    protected $fillable = ['phone', 'email', 'name'];

    public function normalizedEvents(): HasMany
    {
        return $this->hasMany(NormalizedEvent::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
