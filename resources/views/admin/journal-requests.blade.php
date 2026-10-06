@extends('layouts.app')
@section('content')
<div class="lg:flex lg:items-start lg:gap-5">
<div class="mb-4 lg:mb-0 lg:w-56 lg:shrink-0 lg:sticky lg:top-20">@include('admin.partials.sidebar')</div>
<div class="flex-1 min-w-0">
<h1 class="mb-4 font-serif text-2xl">Jurnal arizalari</h1>
@forelse($requests as $journalRequest)<div class="card"><div class="flex flex-wrap items-start justify-between gap-3"><div>
<b>{{ $journalRequest->journal_name }}</b>@if($journalRequest->issn) · ISSN {{ $journalRequest->issn }}@endif
<p class="text-sm text-slate-600"><a class="underline" href="/moderator/maqola/{{ $journalRequest->article_id }}">{{ $journalRequest->article?->title }}</a> · {{ $journalRequest->user?->fullName() ?: $journalRequest->user?->name }}</p>
@if($journalRequest->note)<p class="mt-1 text-sm text-slate-500">{{ $journalRequest->note }}</p>@endif</div></div>
<details class="mt-3"><summary class="cursor-pointer text-sm underline">Jurnalni biriktirish yoki yangi yaratish</summary>
<form method="post" action="/admin/jurnal-arizalari/{{ $journalRequest->id }}/hal" class="mt-3 space-y-3">@csrf
<label for="journal_id_{{ $journalRequest->id }}">Mavjud jurnal ID’si (bo‘sh qoldirsangiz yangi yaratiladi)</label>
<input class="i max-w-xs" id="journal_id_{{ $journalRequest->id }}" name="journal_id" type="number" min="1" placeholder="Jurnal ID">
<div class="grid gap-3 sm:grid-cols-2">
<div><label>Yangi nomi</label><input class="i" name="new_name" maxlength="255" value="{{ $journalRequest->journal_name }}"></div>
<div><label>Yangi ISSN (ixtiyoriy)</label><input class="i" name="new_issn" maxlength="9" placeholder="0000-0000" value="{{ $journalRequest->issn }}"></div>
<div><label>Soha (bir nechta)</label><select class="i" name="new_field[]" multiple size="5">@foreach($fields as $field)<option value="{{ $field }}">{{ $field }}</option>@endforeach</select></div>
<div><label>Daraja</label><select class="i" name="new_tier">@foreach($tiers as $tier)<option value="{{ $tier }}">{{ $tier }}</option>@endforeach</select>
<label class="mt-2">Ro‘yxatga olingan sana</label><input class="i" type="date" name="new_listed_from"></div></div>
<button class="btn">Biriktirish va qayta tekshirish</button></form></details>
<form method="post" action="/admin/jurnal-arizalari/{{ $journalRequest->id }}/rad" class="mt-3 flex flex-wrap items-center gap-2">@csrf
<input class="i max-w-md" name="note" placeholder="Rad etish sababi (majburiy)" maxlength="500" required>
<button class="btn bg-red-700!">Rad etish</button></form></div>
@empty<div class="card text-slate-500">Ochiq jurnal arizalari yo‘q.</div>@endforelse
{{ $requests->links() }}
</div></div>
@endsection
