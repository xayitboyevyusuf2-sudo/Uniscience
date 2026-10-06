<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentView extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    public function viewable()
    {
        return $this->morphTo();
    }
}
