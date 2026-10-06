<?php

namespace App\Http\Controllers;

use App\Models\Guide;
use App\Models\Video;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Read-only guides/videos for any authenticated user; one view counted per user per item. */
class GuideController extends Controller
{
    public function index(Request $request): View
    {
        $term = trim((string) $request->query('q', ''));
        $guides = Guide::query()->withCount('views')->when($term !== '', fn ($q) => $q->where(fn ($x) => $x->where('title', 'like', "%{$term}%")->orWhere('description', 'like', "%{$term}%")))
            ->orderBy('sort')->orderBy('title')->get();
        $videos = Video::query()->withCount('views')->when($term !== '', fn ($q) => $q->where(fn ($x) => $x->where('title', 'like', "%{$term}%")->orWhere('description', 'like', "%{$term}%")))
            ->orderBy('sort')->orderBy('title')->get();

        return view('guides.index', [
            'guidesByCategory' => $guides->groupBy(fn ($g) => $g->category ?: 'Boshqa'),
            'videosByCategory' => $videos->groupBy(fn ($v) => $v->category ?: 'Boshqa'),
            'term' => $term,
        ]);
    }

    public function showGuide(Guide $guide)
    {
        abort_unless(Storage::disk('local')->exists($guide->file_path), 404);
        $this->recordView($guide);
        $path = Storage::disk('local')->path($guide->file_path);

        if ($guide->mime === 'application/pdf') {
            return response()->file($path, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="guide.pdf"']);
        }

        return Storage::disk('local')->download($guide->file_path, $guide->original_name, ['Content-Type' => $guide->mime]);
    }

    public function downloadGuide(Guide $guide)
    {
        abort_unless(Storage::disk('local')->exists($guide->file_path), 404);

        return Storage::disk('local')->download($guide->file_path, $guide->original_name, ['Content-Type' => $guide->mime]);
    }

    public function showVideo(Video $video): View
    {
        $this->recordView($video);

        return view('guides.video', ['video' => $video->loadCount('views')]);
    }

    /** Range-capable streaming via BinaryFileResponse so seeking works in browsers. */
    public function streamVideo(Video $video)
    {
        abort_unless(Storage::disk('local')->exists($video->file_path), 404);

        return response()->file(Storage::disk('local')->path($video->file_path), ['Content-Type' => 'video/mp4']);
    }

    public function downloadVideo(Video $video)
    {
        abort_unless(Storage::disk('local')->exists($video->file_path), 404);

        return Storage::disk('local')->download($video->file_path, $video->title.'.mp4', ['Content-Type' => 'video/mp4']);
    }

    private function recordView(Model $model): void
    {
        $model->views()->firstOrCreate(['user_id' => auth()->id()]);
    }
}
