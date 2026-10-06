<?php

namespace App\Integrations\Ministry;

/**
 * FR-63 (2-bosqich): ministry panel export. Interface only — no implementation yet.
 */
interface MinistryExportAdapter
{
    /** @return array{status: string, message: string} */
    public function export(array $payload): array;
}
