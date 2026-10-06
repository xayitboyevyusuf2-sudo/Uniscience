<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Services\AuditLog;
use App\Services\JournalImporter;
use App\Services\Verifier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Admin journal reference CRUD. Journals are NEVER deleted: delisting sets listed_to (date-range rule handles history). */
class AdminJournalController extends Controller
{
    private const AUDITED_FIELDS = ['issn', 'name', 'field', 'tier', 'listed_from', 'listed_to', 'country', 'publisher', 'source', 'warning_text'];

    public function index(Request $request): View
    {
        $this->guard();
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'field' => ['nullable', 'string', 'max:190'],
            'tier' => ['nullable', Rule::in(array_keys(config('uniscience.tiers')))],
            'source' => ['nullable', Rule::in(Journal::SOURCES)],
            'holat' => ['nullable', Rule::in(['royxatda', 'chiqarilgan'])],
        ]);

        $query = Journal::query()->orderBy('name');
        if ($term = trim((string) ($filters['q'] ?? ''))) {
            $normalized = Verifier::norm($term);
            $query->where(fn ($q) => $q->where('name_norm', 'like', '%'.$normalized.'%')->orWhere('issn', 'like', '%'.$term.'%'));
        }
        if ($field = $filters['field'] ?? null) {
            $query->where('field', 'like', '%'.$field.'%');
        }
        if ($tier = $filters['tier'] ?? null) {
            $query->where('tier', $tier);
        }
        if ($source = $filters['source'] ?? null) {
            $query->where('source', $source);
        }
        if (($filters['holat'] ?? null) === 'royxatda') {
            $query->whereNull('listed_to');
        }
        if (($filters['holat'] ?? null) === 'chiqarilgan') {
            $query->whereNotNull('listed_to');
        }

        return view('admin.journals.index', [
            'journals' => $query->paginate(25)->withQueryString(),
            'filters' => $filters,
            'fields' => config('uniscience.fields'),
            'tiers' => array_keys(config('uniscience.tiers')),
            'sources' => Journal::SOURCES,
        ]);
    }

    public function create(): View
    {
        $this->guard();

        return view('admin.journals.form', $this->formData(new Journal));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->guard();
        $data = $this->validateData($request);
        if ($error = $this->duplicateError($data)) {
            return back()->withInput()->withErrors($error);
        }

        $journal = DB::transaction(fn () => Journal::create($data));
        AuditLog::record('journal.created', $journal, null, $journal->only(self::AUDITED_FIELDS));

        return redirect('/admin/jurnallar')->with('ok', 'Jurnal qo‘shildi.');
    }

    public function edit(Journal $journal): View
    {
        $this->guard();

        return view('admin.journals.form', $this->formData($journal));
    }

    public function update(Request $request, Journal $journal): RedirectResponse
    {
        $this->guard();
        $data = $this->validateData($request);
        if ($error = $this->duplicateError($data, $journal)) {
            return back()->withInput()->withErrors($error);
        }

        $old = $journal->only(self::AUDITED_FIELDS);
        DB::transaction(fn () => $journal->update($data));
        AuditLog::record('journal.updated', $journal, $old, $journal->fresh()->only(self::AUDITED_FIELDS));

        return redirect('/admin/jurnallar')->with('ok', 'Jurnal yangilandi.');
    }

    public function delist(Journal $journal): RedirectResponse
    {
        $this->guard();
        $old = $journal->only(['listed_to']);
        $journal->update(['listed_to' => now()->toDateString()]);
        AuditLog::record('journal.delisted', $journal, $old, $journal->fresh()->only(['listed_to']));

        return back()->with('ok', 'Jurnal ro‘yxatdan chiqarildi (listed_to = '.now()->toDateString().').');
    }

    public function importJson(Request $request): RedirectResponse
    {
        $this->guard();
        $request->validate(['json' => ['required', 'file', 'max:10240']]);
        $report = JournalImporter::fromJson($request->file('json')->get());
        AuditLog::record('journals.imported', null, null, ['format' => 'json', 'count' => $report['imported'], 'skipped' => $report['skipped']]);

        return back()
            ->with('ok', $report['imported'].' ta yozuv import qilindi, '.$report['skipped'].' ta o‘tkazib yuborildi.')
            ->with('import_errors', $report['errors']);
    }

    private function guard(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }

    /** @return array<string, mixed> */
    private function formData(Journal $journal): array
    {
        return [
            'journal' => $journal,
            'fields' => config('uniscience.fields'),
            'tiers' => array_keys(config('uniscience.tiers')),
            'sources' => Journal::SOURCES,
            'selectedFields' => array_filter(array_map('trim', explode(';', (string) $journal->field))),
            'tierXWarning' => Journal::TIER_X_WARNING,
        ];
    }

    /** @return array<string, mixed> */
    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'issn' => ['nullable', 'regex:/^\d{4}-\d{3}[\dXx]$/'],
            'name' => ['required', 'string', 'max:255'],
            'field' => ['required', 'array', 'min:1'],
            'field.*' => ['string', Rule::in(config('uniscience.fields'))],
            'tier' => ['required', Rule::in(array_keys(config('uniscience.tiers')))],
            'listed_from' => ['required', 'date'],
            'listed_to' => ['nullable', 'date', 'after_or_equal:listed_from'],
            'country' => ['nullable', 'string', 'max:190'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', Rule::in(Journal::SOURCES)],
            'warning_text' => [Rule::requiredIf($request->input('tier') === 'X'), 'nullable', 'string', 'max:2000'],
        ], [
            'issn.regex' => 'ISSN formati noto‘g‘ri (0000-0000).',
            'field.required' => 'Kamida bitta sohani tanlang.',
            'field.min' => 'Kamida bitta sohani tanlang.',
            'warning_text.required' => 'Xavfli (X) jurnal uchun ogohlantirish matni majburiy.',
        ]);

        $data['field'] = collect($data['field'])->unique()->implode('; ');
        $data['issn'] = ($data['issn'] ?? null) ?: null;
        $data['listed_to'] = ($data['listed_to'] ?? null) ?: null;
        $data['country'] = ($data['country'] ?? null) ?: null;
        $data['publisher'] = ($data['publisher'] ?? null) ?: null;
        $data['warning_text'] = ($data['warning_text'] ?? null) ?: null;
        $data['source'] = ($data['source'] ?? null) ?: Journal::sourceForTier($data['tier']);

        return $data;
    }

    /** @return array<string, string>|null */
    private function duplicateError(array $data, ?Journal $except = null): ?array
    {
        if (! $data['issn']) {
            return null;
        }
        $exists = Journal::where('issn', $data['issn'])->where('listed_from', $data['listed_from'])
            ->when($except, fn ($q) => $q->where('id', '!=', $except->id))->exists();

        return $exists ? ['issn' => 'Bu ISSN va ro‘yxatga olish sanasiga ega jurnal allaqachon mavjud.'] : null;
    }
}
