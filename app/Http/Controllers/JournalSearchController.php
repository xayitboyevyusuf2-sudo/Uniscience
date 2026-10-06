<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Services\Verifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JournalSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ], [
            'q.required' => 'Qidiruv uchun kamida 2 ta belgi kiriting.',
            'q.min' => 'Qidiruv uchun kamida 2 ta belgi kiriting.',
            'q.max' => 'Qidiruv matni juda uzun.',
        ]);

        $term = trim($validated['q']);
        $normalized = Verifier::norm($term);
        $issnPrefix = preg_replace('/[^0-9Xx]/', '', $term);

        $journals = Journal::query()
            ->where(function ($query) use ($normalized, $term, $issnPrefix): void {
                if ($normalized !== '') {
                    $query->where('name_norm', 'like', '%'.$normalized.'%');
                }

                if (preg_match('/^[\dXx-]{2,9}$/', $term) && strlen($issnPrefix) >= 2 && strlen($issnPrefix) <= 8) {
                    $method = $normalized === '' ? 'where' : 'orWhere';
                    if ($method === 'where') {
                        $query->whereRaw("REPLACE(issn, '-', '') LIKE ?", [$issnPrefix.'%']);
                    } else {
                        $query->orWhereRaw("REPLACE(issn, '-', '') LIKE ?", [$issnPrefix.'%']);
                    }
                }
            })
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'issn', 'field', 'tier', 'warning_text']);

        return response()->json($journals);
    }
}
