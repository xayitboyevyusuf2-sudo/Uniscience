@extends('layouts.app')
@section('content')
@php($isEdit = $journal->exists)
<div class="card"><h1 class="font-serif text-2xl">{{ $isEdit ? 'Jurnalni tahrirlash' : 'Yangi jurnal' }}</h1>
<form method="post" action="{{ $isEdit ? '/admin/jurnallar/'.$journal->id : '/admin/jurnallar' }}">@csrf
<label for="issn">ISSN (ixtiyoriy)</label><input class="i" id="issn" name="issn" value="{{ old('issn', $journal->issn) }}" placeholder="0000-0000" pattern="\d{4}-\d{3}[\dXx]" maxlength="9">
<label for="name">Nomi</label><input class="i" id="name" name="name" value="{{ old('name', $journal->name) }}" required maxlength="255">
<label for="field">Soha (bir nechtasini tanlash mumkin)</label><select class="i" id="field" name="field[]" multiple size="8" required>@foreach($fields as $field)<option value="{{ $field }}" @selected(in_array($field, old('field', $selectedFields), true))>{{ $field }}</option>@endforeach</select>
<label for="tier">Daraja</label><select class="i" id="tier" name="tier" required>@foreach($tiers as $tier)<option value="{{ $tier }}" @selected(old('tier', $journal->tier)===$tier)>{{ $tier }}</option>@endforeach</select>
<div class="grid gap-x-4 sm:grid-cols-2"><div><label for="listed_from">Ro‘yxatga olingan sana</label><input class="i" id="listed_from" type="date" name="listed_from" value="{{ old('listed_from', $journal->listed_from?->format('Y-m-d')) }}" required></div>
<div><label for="listed_to">Ro‘yxatdan chiqqan sana (ixtiyoriy)</label><input class="i" id="listed_to" type="date" name="listed_to" value="{{ old('listed_to', $journal->listed_to?->format('Y-m-d')) }}"></div></div>
<div class="grid gap-x-4 sm:grid-cols-2"><div><label for="country">Davlat (ixtiyoriy)</label><input class="i" id="country" name="country" value="{{ old('country', $journal->country) }}" maxlength="190"></div>
<div><label for="publisher">Nashriyot (ixtiyoriy)</label><input class="i" id="publisher" name="publisher" value="{{ old('publisher', $journal->publisher) }}" maxlength="255"></div></div>
<label for="source">Manba (ixtiyoriy)</label><select class="i" id="source" name="source"><option value="">—</option>@foreach($sources as $source)<option value="{{ $source }}" @selected(old('source', $journal->source)===$source)>{{ $source }}</option>@endforeach</select>
<label for="warning_text">Ogohlantirish matni (X daraja uchun majburiy)</label><textarea class="i" id="warning_text" name="warning_text" rows="3" maxlength="2000" data-tier-x-warning="{{ $tierXWarning }}">{{ old('warning_text', $journal->warning_text) }}</textarea>
<p class="text-sm text-slate-500">Standart matn: «{{ $tierXWarning }}»</p>
<button class="btn mt-4">{{ $isEdit ? 'Saqlash' : 'Qo‘shish' }}</button> <a class="ml-3 underline" href="/admin/jurnallar">Bekor qilish</a></form></div>
@endsection
