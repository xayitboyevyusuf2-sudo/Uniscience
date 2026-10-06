<?php

namespace App\Integrations\Oak;

use App\Models\Article;
use RuntimeException;

class NullOakSyncAdapter implements OakSyncAdapter
{
    public function sync(Article $article): void
    {
        throw new RuntimeException('OAK integratsiyasi 2-bosqichda');
    }
}
