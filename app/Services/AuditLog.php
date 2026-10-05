<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuditLog
{
    public static function record(string $action, ?Model $subject = null, ?array $old = null, ?array $new = null): void
    {
        DB::table('audit_logs')->insert([
            'user_id' => Auth::id(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'old' => $old === null ? null : json_encode($old, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'new' => $new === null ? null : json_encode($new, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'ip' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
