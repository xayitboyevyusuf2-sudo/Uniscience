<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $guarded = [];

    protected $casts = ['pinned' => 'boolean', 'archived_at' => 'datetime', 'event_date' => 'date', 'deadline' => 'date'];

    /** Active: not archived AND (no deadline OR deadline today or later). Works without waiting for the scheduler. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at')
            ->where(fn ($q) => $q->whereNull('deadline')->orWhereDate('deadline', '>=', now()->toDateString()));
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->whereNotNull('archived_at')->orWhereDate('deadline', '<', now()->toDateString()));
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
