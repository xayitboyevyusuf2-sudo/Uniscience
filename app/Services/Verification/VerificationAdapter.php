<?php

namespace App\Services\Verification;

use App\Models\Article;
use App\Models\Journal;

interface VerificationAdapter
{
    public function verify(Article $article, ?Journal $hint = null): VerificationResult;
}
