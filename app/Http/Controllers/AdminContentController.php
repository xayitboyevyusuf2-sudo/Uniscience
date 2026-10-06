<?php

namespace App\Http\Controllers;

use App\Models\Guide;
use App\Models\Video;
use App\Services\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Admin-only upload/edit/delete of guides and videos. Files live on the private disk with hash names. */
class AdminContentController extends Controller
{
    public function guides(): View
    {
        $this->guard();

        return view('admin.guides.index', ['guides' => Guide::query()->withCount('views')->orderBy('sort')->orderBy('title')->get()]);
    }

    public function storeGuide(Request $request): RedirectResponse
    {
        $this->guard();
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['nullable', 'string', 'max:60'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'file' => ['required', 'file', 'mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'max:51200'],
        ], ['file.max' => 'Hujjat hajmi 50 MB dan oshdi. Server yuklash limitlarini oshiring (README).', 'file.mimetypes' => 'Faqat PDF, DOC yoki DOCX hujjat yuklash mumkin.']);

        $file = $request->file('file');
        $path = $file->store('guides', 'local');
        $guide = Guide::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? null,
            'sort' => $data['sort'] ?? 0,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'created_by' => auth()->id(),
        ]);
        AuditLog::record('guide.created', $guide, null, $guide->only(['title', 'category', 'mime', 'size', 'sort']));

        return back()->with('ok', 'Hujjat yuklandi.');
    }

    public function updateGuide(Request $request, Guide $guide): RedirectResponse
    {
        $this->guard();
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['nullable', 'string', 'max:60'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);
        $old = $guide->only(['title', 'description', 'category', 'sort']);
        $guide->update($data + ['sort' => $data['sort'] ?? 0]);
        AuditLog::record('guide.updated', $guide, $old, $guide->fresh()->only(['title', 'description', 'category', 'sort']));

        return back()->with('ok', 'Hujjat yangilandi.');
    }

    public function destroyGuide(Guide $guide): RedirectResponse
    {
        $this->guard();
        $old = $guide->only(['title', 'file_path']);
        Storage::disk('local')->delete($guide->file_path);
        $guide->delete();
        AuditLog::record('guide.deleted', null, $old, null);

        return back()->with('ok', 'Hujjat o‘chirildi.');
    }

    public function videos(): View
    {
        $this->guard();

        return view('admin.videos.index', ['videos' => Video::query()->withCount('views')->orderBy('sort')->orderBy('title')->get()]);
    }

    public function storeVideo(Request $request): RedirectResponse
    {
        $this->guard();
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['nullable', 'string', 'max:60'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'file' => ['required', 'file', 'mimetypes:video/mp4,application/mp4', 'max:1048576'],
        ], ['file.max' => 'Video hajmi 1 GB dan oshdi. Server yuklash limitlarini oshiring (README).', 'file.mimetypes' => 'Faqat MP4 video yuklash mumkin.']);

        $file = $request->file('file');
        $path = $file->store('videos', 'local');
        $video = Video::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? null,
            'sort' => $data['sort'] ?? 0,
            'duration_seconds' => $data['duration_seconds'] ?? null,
            'file_path' => $path,
            'created_by' => auth()->id(),
        ]);
        AuditLog::record('video.created', $video, null, $video->only(['title', 'category', 'sort', 'duration_seconds']));

        return back()->with('ok', 'Video yuklandi.');
    }

    public function updateVideo(Request $request, Video $video): RedirectResponse
    {
        $this->guard();
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['nullable', 'string', 'max:60'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
        ]);
        $old = $video->only(['title', 'description', 'category', 'sort', 'duration_seconds']);
        $video->update($data + ['sort' => $data['sort'] ?? 0]);
        AuditLog::record('video.updated', $video, $old, $video->fresh()->only(['title', 'description', 'category', 'sort', 'duration_seconds']));

        return back()->with('ok', 'Video yangilandi.');
    }

    public function destroyVideo(Video $video): RedirectResponse
    {
        $this->guard();
        $old = $video->only(['title', 'file_path']);
        Storage::disk('local')->delete($video->file_path);
        $video->delete();
        AuditLog::record('video.deleted', null, $old, null);

        return back()->with('ok', 'Video o‘chirildi.');
    }

    private function guard(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }
}
