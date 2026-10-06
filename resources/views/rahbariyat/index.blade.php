@extends('layouts.app')
@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3"><h1 class="font-serif text-2xl">Rahbariyat dashboard</h1>
<div class="flex flex-wrap gap-2 text-sm"><a class="btn" href="/rahbariyat/eksport.csv?{{ http_build_query(request()->query()) }}">CSV eksport</a><a class="btn" href="/rahbariyat/eksport.pdf?{{ http_build_query(request()->query()) }}">PDF eksport</a><a class="btn opacity-50 cursor-not-allowed" href="/vazirlik" aria-disabled="true">Vazirlik paneli (2-bosqich)</a></div></div>
<div class="card"><form method="get" class="flex flex-wrap gap-3">
<select class="i" name="period" aria-label="Davr"><option value="oy" @selected($period==='oy')>Oy</option><option value="chorak" @selected($period==='chorak')>Chorak</option><option value="yil" @selected($period==='yil')>Yil</option></select>
<select class="i" name="month" aria-label="Oy"><option value="">Oy (barchasi)</option>@foreach(range(1,12) as $m)<option value="{{ $m }}" @selected((int)($month ?? 0)===$m)>{{ $m }}-oy</option>@endforeach</select>
<input class="i max-w-32" type="number" name="year" value="{{ $year }}" min="2000" max="2100" aria-label="Yil">
<select class="i" name="faculty" aria-label="Fakultet"><option value="">Fakultet (barchasi)</option>@foreach($faculties as $f)<option value="{{ $f }}" @selected($faculty===$f)>{{ $f }}</option>@endforeach</select>
<button class="btn">Qo‘llash</button></form></div>

<div data-dashboard='@json(['facultyRows' => $facultyRows, 'dynamics' => $dynamics, 'tierDistribution' => $tierDistribution])'>
<div class="grid gap-4 lg:grid-cols-2">
<section class="card"><h2 class="font-serif text-lg">Fakultetlar kesimida</h2><div data-skeleton>@include('partials.skeleton',['rows'=>4,'height'=>8])</div><canvas id="chart-faculty" class="mt-2" height="200"></canvas>
<table class="mt-3 w-full text-sm"><tr><th>Fakultet</th><th>Yuklangan</th><th>Tasdiqlangan</th></tr>@forelse($facultyRows as $row)<tr><td>{{ $row['faculty'] }}</td><td>{{ $row['uploaded'] }}</td><td>{{ $row['approved'] }}</td></tr>@empty<tr><td colspan="3" class="text-slate-500">Ma’lumot yo‘q.</td></tr>@endforelse</table></section>
<section class="card"><h2 class="font-serif text-lg">Yillik dinamika ({{ $year }})</h2><div data-skeleton>@include('partials.skeleton',['rows'=>4,'height'=>8])</div><canvas id="chart-dynamics" class="mt-2" height="200"></canvas>
<table class="mt-3 w-full text-sm"><tr><th>Oy</th><th>Tasdiqlangan</th></tr>@foreach($dynamics as $row)<tr><td>{{ $row['month'] }}-oy</td><td>{{ $row['approved'] }}</td></tr>@endforeach</table></section>
</div>
<div class="grid gap-4 lg:grid-cols-2">
<section class="card"><h2 class="font-serif text-lg">Daraja taqsimoti</h2><div data-skeleton>@include('partials.skeleton',['rows'=>4,'height'=>8])</div><canvas id="chart-tiers" class="mt-2" height="200"></canvas>
<table class="mt-3 w-full text-sm"><tr><th>Daraja</th><th>Soni</th></tr>@foreach($tierDistribution as $tier => $count)<tr><td>{{ $tier }}</td><td>{{ $count }}</td></tr>@endforeach</table></section>
<section class="card"><h2 class="font-serif text-lg">Top ro‘yxatlar</h2>
@foreach([['Talabalar', $topStudents], ['Magistrlar', $topMasters], ['Professorlar', $topProfessors]] as [$title, $rows])
<h3 class="mt-3 font-semibold text-sm">{{ $title }}</h3><table class="w-full text-sm"><tr><th>Ism</th><th>Fakultet</th><th>Ball</th></tr>
@forelse($rows as $row)<tr><td>{{ $row->name }}</td><td>{{ $row->faculty }}</td><td>{{ number_format((float) $row->score, 2) }}</td></tr>@empty<tr><td colspan="3" class="text-slate-500">—</td></tr>@endforelse</table>
@endforeach</section>
</div>
<div class="grid gap-4 lg:grid-cols-3">
<section class="card"><h2 class="font-serif text-lg">Toifa bo‘yicha</h2><table class="w-full text-sm"><tr><th>Toifa</th><th>Tasdiqlangan</th></tr>@foreach(['bakalavr' => 'Bakalavr', 'magistr' => 'Magistr', 'tadqiqotchi' => 'Tadqiqotchi', 'professor' => 'Professor'] as $key => $label)<tr><td>{{ $label }}</td><td>{{ $byCategory[$key] ?? 0 }}</td></tr>@endforeach</table></section>
<section class="card"><h2 class="font-serif text-lg">Kafedra bo‘yicha</h2><table class="w-full text-sm"><tr><th>Kafedra</th><th>Tasdiqlangan</th></tr>@forelse($byDepartment as $department => $count)<tr><td>{{ $department }}</td><td>{{ $count }}</td></tr>@empty<tr><td colspan="2" class="text-slate-500">—</td></tr>@endforelse</table></section>
<section class="card"><h2 class="font-serif text-lg">Kurs bo‘yicha</h2><table class="w-full text-sm"><tr><th>Kurs</th><th>Tasdiqlangan</th></tr>@forelse($byCourse as $course => $count)<tr><td>{{ $course }}-kurs</td><td>{{ $count }}</td></tr>@empty<tr><td colspan="2" class="text-slate-500">—</td></tr>@endforelse</table></section>
</div>
</div>
@push('scripts')
@vite('resources/js/dashboard.js')
@endpush
@endsection
