<?php

namespace App\Integrations\Oak;

use App\Models\Article;

interface OakSyncAdapter
{
    public function sync(Article $article): void;
}
