<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Services\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** Admin-only news management. Attachments stay on the private disk and are removed with the record. */
class AdminNewsController extends Controller
{
    public function index(): View
    {
        $this->guard();

        return view('admin.news.index', ['news' => News::query()->orderByDesc('pinned')->latest()->paginate(20)]);
    }

    public function create(): View
    {
        $this->guard();

        return view('admin.news.form', ['news' => new News, 'types' => config('uniscience.news_types')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->guard();
        $data = $this->validateData($request);
        $data['pinned'] = $request->boolean('pinned');
        $data['created_by'] = auth()->id();

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $data['attachment_path'] = $file->store('news', 'local');
            $data['attachment_name'] = $file->getClientOriginalName();
        }

        $news = News::create($data);
        AuditLog::record('news.created', $news, null, $news->only(['title', 'type', 'event_date', 'deadline', 'link', 'pinned']));

        return redirect('/admin/yangiliklar')->with('ok', 'Yangilik qo‘shildi.');
    }

    public function edit(News $news): View
    {
        $this->guard();

        return view('admin.news.form', ['news' => $news, 'types' => config('uniscience.news_types')]);
    }

    public function update(Request $request, News $news): RedirectResponse
    {
        $this->guard();
        $data = $this->validateData($request);
        $data['pinned'] = $request->boolean('pinned');

        if ($request->hasFile('attachment')) {
            if ($news->attachment_path) {
                Storage::disk('local')->delete($news->attachment_path);
            }
            $file = $request->file('attachment');
            $data['attachment_path'] = $file->store('news', 'local');
            $data['attachment_name'] = $file->getClientOriginalName();
        }

        $old = $news->only(['title', 'type', 'event_date', 'deadline', 'link', 'pinned', 'attachment_name']);
        $news->update($data);
        AuditLog::record('news.updated', $news, $old, $news->fresh()->only(['title', 'type', 'event_date', 'deadline', 'link', 'pinned', 'attachment_name']));

        return redirect('/admin/yangiliklar')->with('ok', 'Yangilik yangilandi.');
    }

    public function destroy(News $news): RedirectResponse
    {
        $this->guard();
        $old = $news->only(['title', 'type']);
        if ($news->attachment_path) {
            Storage::disk('local')->delete($news->attachment_path);
        }
        $news->delete();
        AuditLog::record('news.deleted', null, $old, null);

        return back()->with('ok', 'Yangilik o‘chirildi.');
    }

    private function guard(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }

    /** @return array<string, mixed> */
    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'body' => ['required', 'string', 'max:20000'],
            'type' => ['required', Rule::in(array_keys(config('uniscience.news_types')))],
            'event_date' => ['nullable', 'date'],
            'deadline' => ['nullable', 'date'],
            'link' => ['nullable', 'url', 'max:500'],
            'attachment' => ['nullable', 'file', 'mimetypes:application/pdf', 'max:10240'],
        ], [
            'link.url' => 'Havola to‘g‘ri URL bo‘lishi kerak.',
            'attachment.mimetypes' => 'Ilova faqat PDF bo‘lishi mumkin.',
            'attachment.max' => 'Ilova hajmi 10 MB dan oshmasligi kerak.',
        ]);
        unset($data['attachment']);

        return $data;
    }
}
