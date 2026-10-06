<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guide extends Model
{
    protected $guarded = [];

    public function views()
    {
        return $this->morphMany(ContentView::class, 'viewable');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isNew(): bool
    {
        return $this->created_at && $this->created_at->gt(now()->subDays(7));
    }
}
