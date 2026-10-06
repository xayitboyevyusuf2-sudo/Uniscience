@extends('layouts.app')
@section('content')
@php($categoryLabels = ['bakalavr' => 'Bakalavrlar', 'magistr' => 'Magistrlar', 'tadqiqotchi' => 'Tadqiqotchilar', 'professor' => 'Professorlar'])
<h1 class="font-serif text-2xl mb-3">Reyting</h1>
<nav class="mb-3 flex flex-wrap gap-1 text-sm" aria-label="Toifa">
@foreach($categories as $cat)<a class="px-3 py-1.5 rounded-md {{ $category===$cat ? 'bg-lapis text-white font-semibold' : 'bg-white border border-slate-200 hover:bg-slate-100' }}" href="/reyting?category={{ $cat }}&scope={{ $scope }}">{{ $categoryLabels[$cat] ?? ucfirst($cat) }}</a>@endforeach
</nav>
@auth<nav class="mb-3 flex flex-wrap gap-1 text-sm" aria-label="Ko‘lam">
@foreach(['university' => 'Universitet', 'faculty' => 'Fakultet', 'group' => 'Guruh'] as $key => $label)<a class="px-3 py-1.5 rounded-md {{ $scope===$key ? 'bg-teal-700 text-white font-semibold' : 'bg-white border border-slate-200 hover:bg-slate-100' }}" href="/reyting?category={{ $category }}&scope={{ $key }}">{{ $label }}</a>@endforeach
<a class="px-3 py-1.5 rounded-md bg-white border border-slate-200 hover:bg-slate-100" href="/reyting/mening">Mening reytingim</a>
@if($canExport)<a class="px-3 py-1.5 rounded-md bg-white border border-slate-200 hover:bg-slate-100" href="/reyting/eksport.csv">CSV</a><a class="px-3 py-1.5 rounded-md bg-white border border-slate-200 hover:bg-slate-100" href="/reyting/eksport.pdf">PDF</a>@endif
</nav>@endauth
<div class="card overflow-x-auto"><table class="w-full"><tr><th>#</th><th>Ism</th><th>Fakultet</th><th>Guruh</th><th>Ball</th></tr>
@forelse($top as $rating)<tr class="{{ $rating->user_id===auth()->id() ? 'bg-teal-50 font-semibold' : '' }}"><td><span class="inline-flex w-7 h-7 items-center justify-center rounded-full text-sm font-bold {{ [1=>'bg-amber-300',2=>'bg-slate-300',3=>'bg-orange-300'][$rating->{$rankColumn}] ?? 'bg-slate-100' }}">{{ $rating->{$rankColumn} }}</span></td><td>@auth<a class="text-lapis underline" href="/talaba/{{ $rating->user_id }}">{{ $rating->user?->fullName() ?: $rating->user?->name }}</a>@else{{ $rating->user?->fullName() ?: $rating->user?->name }}@endauth</td><td>{{ $rating->user?->faculty }}</td><td>{{ $rating->user?->group_name ?: '—' }}</td><td>{{ number_format((float) $rating->score, 2) }}</td></tr>
@empty<tr><td colspan="5" class="text-slate-500">Bu toifada hali balli ishtirokchilar yo‘q.</td></tr>@endforelse</table></div>
@if($neighborhood->isNotEmpty())<div class="card overflow-x-auto"><h2 class="font-serif text-lg mb-2">Sizning atrofingiz</h2><table class="w-full"><tr><th>#</th><th>Ism</th><th>Fakultet</th><th>Guruh</th><th>Ball</th></tr>
@foreach($neighborhood as $rating)<tr class="{{ $rating->user_id===auth()->id() ? 'bg-teal-50 font-semibold' : '' }}"><td>{{ $rating->{$rankColumn} }}</td><td>@auth<a class="text-lapis underline" href="/talaba/{{ $rating->user_id }}">{{ $rating->user?->fullName() ?: $rating->user?->name }}</a>@else{{ $rating->user?->fullName() ?: $rating->user?->name }}@endauth</td><td>{{ $rating->user?->faculty }}</td><td>{{ $rating->user?->group_name ?: '—' }}</td><td>{{ number_format((float) $rating->score, 2) }}</td></tr>@endforeach</table></div>@endif
@endsection
