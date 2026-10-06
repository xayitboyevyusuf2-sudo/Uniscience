@extends('layouts.app')
@section('content')
<h1 class="mb-4 font-serif text-2xl">Jurnallar ro‘yxati</h1>
<div class="card"><form method="get" class="flex flex-wrap gap-3">
<input class="i max-w-xs" name="q" value="{{ request('q') }}" placeholder="Nomi yoki ISSN" maxlength="190">
<select class="i max-w-xs" name="field" aria-label="Soha"><option value="">Soha (barchasi)</option>@foreach(config('uniscience.fields') as $field)<option value="{{ $field }}" @selected(request('field')===$field)>{{ $field }}</option>@endforeach</select>
<select class="i" name="tier" aria-label="Daraja"><option value="">Daraja (barchasi)</option>@foreach(array_keys(config('uniscience.tiers')) as $tier)<option value="{{ $tier }}" @selected(request('tier')===$tier)>{{ $tier }}</option>@endforeach</select>
<button class="btn">Qidirish</button></form></div>
<div class="card overflow-x-auto"><table class="w-full text-sm"><tr><th>Nomi</th><th>Soha</th><th>Daraja</th><th>Ro‘yxatda bo‘lgan davr</th></tr>
@forelse($journals as $journal)<tr><td>{{ $journal->name }}@if($journal->tier==='X')<p class="text-xs font-semibold text-red-700">{{ $journal->warningMessage() }}</p>@endif</td><td>{{ $journal->field }}</td><td>{{ $journal->tier }}</td><td>{{ $journal->listed_from->format('Y-m-d') }} — {{ $journal->listed_to?->format('Y-m-d') ?? 'hozirgacha' }}</td></tr>
@empty<tr><td colspan="4" class="text-slate-500">Jurnallar topilmadi.</td></tr>@endforelse</table>
{{ $journals->links() }}</div>
@endsection
