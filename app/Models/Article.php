<?php

namespace App\Models;

use App\Services\Verifier;
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

    public function journalRequests()
    {
        return $this->hasMany(JournalRequest::class);
    }

    public function conferenceCertificate()
    {
        return $this->hasOne(ConferenceCertificate::class);
    }

    public function effectiveTier(): ?string
    {
        $type = config('uniscience.article_types.'.$this->type, []);

        return $type['forced_tier'] ?? $this->journal?->tier;
    }

    public function effectiveField(): ?string
    {
        return $this->field ?? $this->journal?->field;
    }

    public function venueKey(): int|string
    {
        return $this->journal_id ?? Verifier::norm($this->journal_name);
    }
}
