<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class TimeSlot extends Model
{
    protected $guarded = [];

    protected $casts = ['slot_date' => 'date'];

    public function mentor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function requests()
    {
        return $this->hasMany(MentorRequest::class, 'slot_id');
    }

    public function acceptedRequests()
    {
        return $this->hasMany(MentorRequest::class, 'slot_id')->where('status', 'accepted');
    }

    public function startsAt(): Carbon
    {
        return $this->slot_date->copy()->setTimeFromTimeString($this->start_time);
    }

    public function endsAt(): Carbon
    {
        return $this->slot_date->copy()->setTimeFromTimeString($this->end_time);
    }
}
