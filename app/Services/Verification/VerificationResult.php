<?php

namespace App\Services\Verification;

use App\Models\Journal;

class VerificationResult
{
    public function __construct(
        public string $status,
        public string $reason,
        public ?Journal $journal,
    ) {}
}
