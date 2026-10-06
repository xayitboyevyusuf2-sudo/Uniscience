<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RatingItem extends Model
{
    protected $guarded = [];

    protected $casts = ['counted' => 'boolean'];

    public function rating()
    {
        return $this->belongsTo(Rating::class);
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
