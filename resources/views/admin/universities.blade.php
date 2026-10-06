@extends('layouts.app')
@section('content')
<div class="lg:flex lg:items-start lg:gap-5">
<div class="mb-4 lg:mb-0 lg:w-56 lg:shrink-0 lg:sticky lg:top-20">@include('admin.partials.sidebar')</div>
<div class="flex-1 min-w-0">
<h1 class="mb-4 font-serif text-2xl">Universitetlar ro‘yxati</h1>
<div class="card"><h2 class="font-serif text-lg">Yangi universitet</h2>
<form method="post" action="/admin/universitetlar" class="mt-2 grid gap-3 sm:grid-cols-3">@csrf
<div><label for="name">Nomi</label><input class="i" id="name" name="name" value="{{ old('name') }}" required maxlength="190"></div>
<div><label for="short_name">Qisqa nomi (ixtiyoriy)</label><input class="i" id="short_name" name="short_name" value="{{ old('short_name') }}" maxlength="60"></div>
<div><label for="sort">Tartib</label><input class="i" id="sort" type="number" name="sort" value="{{ old('sort', 0) }}" min="0"></div>
<button class="btn sm:col-span-3">Qo‘shish</button></form></div>
<div class="card overflow-x-auto"><table class="w-full text-sm"><tr><th>Nomi</th><th>Qisqa</th><th>Faol</th><th>Tartib</th><th><span class="sr-only">Amallar</span></th></tr>
@forelse($universities as $university)<tr><td>{{ $university->name }}</td><td>{{ $university->short_name ?: '—' }}</td><td>{{ $university->is_active ? 'Ha' : 'Yo‘q' }}</td><td>{{ $university->sort }}</td>
<td><details><summary class="cursor-pointer underline">Tahrirlash</summary>
<form method="post" action="/admin/universitetlar/{{ $university->id }}" class="mt-2 grid gap-2">@csrf
<input class="i" name="name" value="{{ $university->name }}" required maxlength="190">
<input class="i" name="short_name" value="{{ $university->short_name }}" maxlength="60" placeholder="Qisqa nomi">
<input class="i" type="number" name="sort" value="{{ $university->sort }}" min="0">
<label class="font-normal"><input type="checkbox" name="is_active" value="1" @checked($university->is_active)> Faol (ro‘yxatda ko‘rinadi)</label>
<button class="btn">Saqlash</button></form>
<form method="post" action="/admin/universitetlar/{{ $university->id }}" onsubmit="return confirm('Universitet o‘chirilsinmi?')">@csrf @method('DELETE')<button class="mt-2 text-red-700 underline">O‘chirish</button></form>
</details></td></tr>
@empty<tr><td colspan="5" class="text-slate-500">Universitetlar hali yo‘q.</td></tr>@endforelse</table></div>
</div></div>
@endsection
