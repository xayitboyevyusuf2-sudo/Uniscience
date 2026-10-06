<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class University extends Model
{
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];
}
