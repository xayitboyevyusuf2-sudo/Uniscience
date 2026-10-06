<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MentorRequest extends Model
{
    public const STATUSES = ['pending', 'accepted', 'rejected', 'cancelled'];

    protected $guarded = [];

    protected $casts = ['responded_at' => 'datetime'];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function slot()
    {
        return $this->belongsTo(TimeSlot::class, 'slot_id');
    }
}
