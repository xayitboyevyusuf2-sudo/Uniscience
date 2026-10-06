@extends('layouts.app')
@section('content')
@php($isEdit = $news->exists)
<div class="card"><h1 class="font-serif text-2xl">{{ $isEdit ? 'Yangilikni tahrirlash' : 'Yangi yangilik' }}</h1>
<form method="post" action="{{ $isEdit ? '/admin/yangiliklar/'.$news->id : '/admin/yangiliklar' }}" enctype="multipart/form-data">@csrf
<label for="title">Sarlavha</label><input class="i" id="title" name="title" value="{{ old('title', $news->title) }}" required maxlength="190">
<label for="body">Matn</label><textarea class="i" id="body" name="body" rows="6" required>{{ old('body', $news->body) }}</textarea>
<label for="type">Tur</label><select class="i" id="type" name="type" required>@foreach($types as $key => $label)<option value="{{ $key }}" @selected(old('type', $news->type)===$key)>{{ $label }}</option>@endforeach</select>
<div class="grid gap-x-4 sm:grid-cols-2"><div><label for="event_date">Tadbir sanasi (ixtiyoriy)</label><input class="i" id="event_date" type="date" name="event_date" value="{{ old('event_date', $news->event_date?->format('Y-m-d')) }}"></div>
<div><label for="deadline">Muddat (ixtiyoriy)</label><input class="i" id="deadline" type="date" name="deadline" value="{{ old('deadline', $news->deadline?->format('Y-m-d')) }}"></div></div>
<label for="link">Havola (ixtiyoriy)</label><input class="i" id="link" type="url" name="link" value="{{ old('link', $news->link) }}" maxlength="500" placeholder="https://…">
<label for="attachment">Ilova PDF (ixtiyoriy, 10 MB gacha)@if($news->attachment_path) — hozirgi: {{ $news->attachment_name }}@endif</label><input class="i" id="attachment" type="file" name="attachment" accept="application/pdf,.pdf">
<label class="font-normal"><input type="checkbox" name="pinned" value="1" @checked(old('pinned', $news->pinned))> Yopishtirish</label>
<button class="btn mt-2">{{ $isEdit ? 'Saqlash' : 'Qo‘shish' }}</button> <a class="ml-3 underline" href="/admin/yangiliklar">Bekor qilish</a></form></div>
@endsection
