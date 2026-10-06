<?php

namespace App\Services;

use App\Models\Journal;

/** CSV columns: issn (may be empty), name, field, tier, listed_from, listed_to, country, publisher. Never deletes. */
class JournalImporter
{
    public static function fromFile(string $path): int
    {
        $f = fopen($path, 'r');
        $h = null;
        $n = 0;
        while (($row = fgetcsv($f)) !== false) {
            if (! $h) {
                $h = array_map(fn ($x) => trim(preg_replace('/^\xEF\xBB\xBF/', '', $x)), $row);

                continue;
            }
            $d = array_combine($h, array_slice(array_pad($row, count($h), null), 0, count($h)));
            if (empty($d['name']) || empty($d['listed_from']) || ! in_array($d['tier'] ?? '', ['A', 'B', 'C', 'D', 'E', 'X'])) {
                continue;
            }
            $issn = trim($d['issn'] ?? '') ?: null;
            $key = $issn ? ['issn' => $issn, 'listed_from' => $d['listed_from']] : ['name_norm' => Verifier::norm($d['name']), 'listed_from' => $d['listed_from']];
            $vals = ['name' => $d['name'], 'field' => $d['field'] ?? '', 'tier' => $d['tier'], 'listed_to' => ($d['listed_to'] ?? '') ?: null, 'country' => $d['country'] ?? null, 'publisher' => $d['publisher'] ?? null, 'source' => Journal::sourceForTier($d['tier'])];
            if ($issn) {
                $vals['issn'] = $issn;
            } // never erase an ISSN learned earlier
            Journal::updateOrCreate($key, $vals);
            $n++;
        }
        fclose($f);

        return $n;
    }

    /**
     * JSON rows: [{issn?, name, field, tier, listed_from, listed_to?, country?, publisher?, source?}]. Never deletes.
     *
     * @return array{imported: int, skipped: int, errors: array<int, string>}
     */
    public static function fromJson(string $json): array
    {
        $rows = json_decode($json, true);
        if (! is_array($rows)) {
            return ['imported' => 0, 'skipped' => 1, 'errors' => ['JSON formati noto‘g‘ri: yozuvlar massivi kutilgan.']];
        }

        $imported = 0;
        $errors = [];
        foreach (array_values($rows) as $i => $row) {
            $line = $i + 1;
            if (! is_array($row)) {
                $errors[] = "$line-qator: yozuv obyekt bo‘lishi kerak.";

                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            $tier = $row['tier'] ?? null;
            $listedFrom = trim((string) ($row['listed_from'] ?? ''));
            $listedTo = trim((string) ($row['listed_to'] ?? '')) ?: null;
            $issn = trim((string) ($row['issn'] ?? '')) ?: null;
            $source = trim((string) ($row['source'] ?? '')) ?: null;

            if ($name === '') {
                $errors[] = "$line-qator: name bo‘sh.";

                continue;
            }
            if (! in_array($tier, ['A', 'B', 'C', 'D', 'E', 'X'], true)) {
                $errors[] = "$line-qator: tier noto‘g‘ri (A, B, C, D, E yoki X bo‘lishi kerak).";

                continue;
            }
            if (! self::isDate($listedFrom)) {
                $errors[] = "$line-qator: listed_from sanasi noto‘g‘ri (YYYY-MM-DD).";

                continue;
            }
            if ($listedTo !== null && ! self::isDate($listedTo)) {
                $errors[] = "$line-qator: listed_to sanasi noto‘g‘ri (YYYY-MM-DD).";

                continue;
            }
            if ($issn !== null && ! preg_match('/^\d{4}-\d{3}[\dXx]$/', $issn)) {
                $errors[] = "$line-qator: ISSN formati noto‘g‘ri (0000-0000).";

                continue;
            }
            if ($source !== null && ! in_array($source, Journal::SOURCES, true)) {
                $errors[] = "$line-qator: source noto‘g‘ri (".implode(', ', Journal::SOURCES).').';

                continue;
            }

            $key = $issn ? ['issn' => $issn, 'listed_from' => $listedFrom] : ['name_norm' => Verifier::norm($name), 'listed_from' => $listedFrom];
            $vals = [
                'name' => $name,
                'field' => trim((string) ($row['field'] ?? '')),
                'tier' => $tier,
                'listed_to' => $listedTo,
                'country' => trim((string) ($row['country'] ?? '')) ?: null,
                'publisher' => trim((string) ($row['publisher'] ?? '')) ?: null,
                'source' => $source ?? Journal::sourceForTier($tier),
            ];
            if ($issn) {
                $vals['issn'] = $issn;
            } // never erase an ISSN learned earlier
            Journal::updateOrCreate($key, $vals);
            $imported++;
        }

        return ['imported' => $imported, 'skipped' => count($errors), 'errors' => $errors];
    }

    private static function isDate(string $value): bool
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }
        [$year, $month, $day] = array_map('intval', explode('-', $value));

        return checkdate($month, $day, $year);
    }
}
