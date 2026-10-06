<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class NewsController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'type' => ['nullable', Rule::in(array_keys(config('uniscience.news_types')))],
            'q' => ['nullable', 'string', 'max:190'],
            'tab' => ['nullable', Rule::in(['faol', 'arxiv'])],
        ]);
        $tab = $filters['tab'] ?? 'faol';

        $query = News::query();
        $tab === 'arxiv' ? $query->archived() : $query->active();
        if ($type = $filters['type'] ?? null) {
            $query->where('type', $type);
        }
        if ($term = trim((string) ($filters['q'] ?? ''))) {
            $query->where(fn ($q) => $q->where('title', 'like', '%'.$term.'%')->orWhere('body', 'like', '%'.$term.'%'));
        }

        return view('news.index', [
            'news' => $query->orderByDesc('pinned')->latest()->paginate(15)->withQueryString(),
            'filters' => $filters,
            'tab' => $tab,
            'types' => config('uniscience.news_types'),
        ]);
    }

    public function attachment(News $news)
    {
        abort_unless($news->attachment_path && Storage::disk('local')->exists($news->attachment_path), 404);

        return Storage::disk('local')->download($news->attachment_path, $news->attachment_name ?: 'ilova.pdf', ['Content-Type' => 'application/pdf']);
    }
}
