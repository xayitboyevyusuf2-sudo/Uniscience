<?php

namespace App\Http\Controllers;

use App\Models\University;
use App\Services\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Admin-managed university list: only active entries appear in the registration form. */
class AdminUniversityController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return view('admin.universities', ['universities' => University::query()->orderBy('sort')->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190', 'unique:universities,name'],
            'short_name' => ['nullable', 'string', 'max:60'],
            'sort' => ['nullable', 'integer', 'min:0'],
        ]);

        $university = University::create($data + ['sort' => $data['sort'] ?? 0]);
        AuditLog::record('university.created', $university, null, $university->only(['name', 'short_name', 'is_active', 'sort']));

        return back()->with('ok', 'Universitet qo‘shildi.');
    }

    public function update(Request $request, University $university): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190', 'unique:universities,name,'.$university->id],
            'short_name' => ['nullable', 'string', 'max:60'],
            'is_active' => ['sometimes', 'boolean'],
            'sort' => ['nullable', 'integer', 'min:0'],
        ]);

        $old = $university->only(['name', 'short_name', 'is_active', 'sort']);
        $university->update($data + ['is_active' => $request->boolean('is_active'), 'sort' => $data['sort'] ?? 0]);
        AuditLog::record('university.updated', $university, $old, $university->fresh()->only(['name', 'short_name', 'is_active', 'sort']));

        return back()->with('ok', 'Universitet yangilandi.');
    }

    public function destroy(University $university): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $old = $university->only(['name', 'is_active']);
        $university->delete();
        AuditLog::record('university.deleted', null, $old, null);

        return back()->with('ok', 'Universitet o‘chirildi.');
    }
}
