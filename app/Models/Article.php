<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $guarded = [];

    protected $casts = ['published_at' => 'date', 'decided_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function authors()
    {
        return $this->hasMany(ArticleAuthor::class)->orderBy('sort');
    }

    public function decider()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
