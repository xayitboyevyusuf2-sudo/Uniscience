<?php

namespace App\Services\Verification;

use App\Models\Article;
use App\Models\Journal;
use App\Services\Verifier;

class RuleBasedVerifier implements VerificationAdapter
{
    public function verify(Article $article, ?Journal $hint = null): VerificationResult
    {
        [$status, $reason, $journal] = Verifier::run($article, $hint);

        return new VerificationResult($status, $reason, $journal);
    }
}
