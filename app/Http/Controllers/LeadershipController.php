<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Leadership dashboard (rahbariyat + admin only) with one-click CSV/PDF export. */
class LeadershipController extends Controller
{
    public function index(Request $request): View
    {
        $this->guard();
        $data = DashboardService::build(...$this->filters($request));

        return view('rahbariyat.index', $data);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->guard();
        $data = DashboardService::build(...$this->filters($request));

        return response()->streamDownload(function () use ($data): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Davr', $data['period'], 'Yil', $data['year'], 'Fakultet', $data['faculty'] ?: 'Barchasi']);
            fputcsv($out, []);
            fputcsv($out, ['Fakultet', 'Yuklangan', 'Tasdiqlangan']);
            foreach ($data['facultyRows'] as $row) {
                fputcsv($out, [$row['faculty'], $row['uploaded'], $row['approved']]);
            }
            fputcsv($out, []);
            fputcsv($out, ['Oy', 'Tasdiqlangan']);
            foreach ($data['dynamics'] as $row) {
                fputcsv($out, [$row['month'], $row['approved']]);
            }
            fputcsv($out, []);
            fputcsv($out, ['Daraja', 'Soni']);
            foreach ($data['tierDistribution'] as $tier => $count) {
                fputcsv($out, [$tier, $count]);
            }
            foreach ([['Top talabalar', $data['topStudents']], ['Top magistrlar', $data['topMasters']], ['Top professorlar', $data['topProfessors']]] as [$title, $rows]) {
                fputcsv($out, []);
                fputcsv($out, [$title]);
                fputcsv($out, ['Ism', 'Fakultet', 'Ball', 'Fakultet o‘rni', 'Universitet o‘rni']);
                foreach ($rows as $row) {
                    fputcsv($out, [$row->name, $row->faculty, $row->score, $row->rank_faculty, $row->rank_university]);
                }
            }
            fputcsv($out, []);
            fputcsv($out, ['Toifa', 'Tasdiqlangan']);
            foreach (['bakalavr' => 'Bakalavr', 'magistr' => 'Magistr', 'tadqiqotchi' => 'Tadqiqotchi', 'professor' => 'Professor'] as $key => $label) {
                fputcsv($out, [$label, $data['byCategory'][$key] ?? 0]);
            }
            fputcsv($out, []);
            fputcsv($out, ['Kafedra', 'Tasdiqlangan']);
            foreach ($data['byDepartment'] as $department => $count) {
                fputcsv($out, [$department, $count]);
            }
            fputcsv($out, []);
            fputcsv($out, ['Kurs', 'Tasdiqlangan']);
            foreach ($data['byCourse'] as $course => $count) {
                fputcsv($out, [$course.'-kurs', $count]);
            }
            fclose($out);
        }, 'rahbariyat.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportPdf(Request $request): Response
    {
        $this->guard();
        $data = DashboardService::build(...$this->filters($request));

        return Pdf::setOptions(['defaultFont' => 'DejaVu Sans'])
            ->loadView('rahbariyat.export-pdf', $data)
            ->download('rahbariyat.pdf');
    }

    private function guard(): void
    {
        $user = auth()->user();
        abort_unless($user->isLeadership() || $user->isAdmin(), 403);
    }

    /** @return array{0: string, 1: ?int, 2: ?int, 3: ?string} */
    private function filters(Request $request): array
    {
        $f = $request->validate([
            'period' => ['nullable', Rule::in(['oy', 'chorak', 'yil'])],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'faculty' => ['nullable', 'string', 'max:190'],
        ]);

        return [$f['period'] ?? 'yil', $f['month'] ?? null, $f['year'] ?? null, $f['faculty'] ?? null];
    }
}
