<!DOCTYPE html>
<html lang="uz"><head><meta charset="utf-8">
<style>
body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; }
h1 { font-size: 15px; } h2 { font-size: 12px; margin-top: 12px; }
table { width: 100%; border-collapse: collapse; margin-top: 4px; }
th, td { border: 1px solid #94a3b8; padding: 3px 5px; text-align: left; }
th { background: #e2e8f0; }
</style></head><body>
<h1>Rahbariyat hisoboti</h1>
<p>Davr: {{ $period }} · Yil: {{ $year }}@if($month) · Oy: {{ $month }}@endif · Fakultet: {{ $faculty ?: 'Barchasi' }}</p>
<h2>Fakultetlar kesimida</h2>
<table><tr><th>Fakultet</th><th>Yuklangan</th><th>Tasdiqlangan</th></tr>
@foreach($facultyRows as $row)<tr><td>{{ $row['faculty'] }}</td><td>{{ $row['uploaded'] }}</td><td>{{ $row['approved'] }}</td></tr>@endforeach</table>
<h2>Yillik dinamika</h2>
<table><tr><th>Oy</th><th>Tasdiqlangan</th></tr>
@foreach($dynamics as $row)<tr><td>{{ $row['month'] }}-oy</td><td>{{ $row['approved'] }}</td></tr>@endforeach</table>
<h2>Daraja taqsimoti</h2>
<table><tr><th>Daraja</th><th>Soni</th></tr>
@foreach($tierDistribution as $tier => $count)<tr><td>{{ $tier }}</td><td>{{ $count }}</td></tr>@endforeach</table>
<h2>Top ro‘yxatlar</h2>
@foreach([['Talabalar', $topStudents], ['Magistrlar', $topMasters], ['Professorlar', $topProfessors]] as [$title, $rows])
<h2>{{ $title }}</h2>
<table><tr><th>Ism</th><th>Fakultet</th><th>Ball</th></tr>
@foreach($rows as $row)<tr><td>{{ $row->name }}</td><td>{{ $row->faculty }}</td><td>{{ number_format((float) $row->score, 2) }}</td></tr>@endforeach</table>
@endforeach
<h2>Toifa bo‘yicha</h2>
<table><tr><th>Toifa</th><th>Tasdiqlangan</th></tr>
@foreach(['bakalavr' => 'Bakalavr', 'magistr' => 'Magistr', 'tadqiqotchi' => 'Tadqiqotchi', 'professor' => 'Professor'] as $key => $label)<tr><td>{{ $label }}</td><td>{{ $byCategory[$key] ?? 0 }}</td></tr>@endforeach</table>
<h2>Kafedra bo‘yicha</h2>
<table><tr><th>Kafedra</th><th>Tasdiqlangan</th></tr>
@foreach($byDepartment as $department => $count)<tr><td>{{ $department }}</td><td>{{ $count }}</td></tr>@endforeach</table>
<h2>Kurs bo‘yicha</h2>
<table><tr><th>Kurs</th><th>Tasdiqlangan</th></tr>
@foreach($byCourse as $course => $count)<tr><td>{{ $course }}-kurs</td><td>{{ $count }}</td></tr>@endforeach</table>
</body></html>
