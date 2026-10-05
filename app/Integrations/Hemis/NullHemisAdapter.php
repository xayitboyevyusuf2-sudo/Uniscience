<?php

namespace App\Integrations\Hemis;

use RuntimeException;

class NullHemisAdapter implements HemisAuthAdapter
{
    public function authorizationUrl(): string
    {
        throw new RuntimeException('HEMIS integratsiyasi 2-bosqichda');
    }

    public function handleCallback(array $query): string
    {
        throw new RuntimeException('HEMIS integratsiyasi 2-bosqichda');
    }

    public function fetchProfile(string $token): array
    {
        throw new RuntimeException('HEMIS integratsiyasi 2-bosqichda');
    }
}
