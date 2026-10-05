<?php

namespace App\Integrations\Hemis;

interface HemisAuthAdapter
{
    public function authorizationUrl(): string;

    public function handleCallback(array $query): string;

    public function fetchProfile(string $token): array;
}
