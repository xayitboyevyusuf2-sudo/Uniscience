@extends('layouts.app')
@section('content')
<h1 class="mb-4 font-serif text-2xl">Yo‘riqnoma hujjatlari (admin)</h1>
<div class="card"><h2 class="font-serif text-lg">Yangi hujjat yuklash</h2><p class="text-sm text-slate-500">PDF, DOC yoki DOCX — 50 MB gacha.</p>
<form method="post" action="/admin/yoriqnoma" enctype="multipart/form-data" class="mt-2 grid gap-3 sm:grid-cols-2">@csrf
<div><label for="title">Nomi</label><input class="i" id="title" name="title" value="{{ old('title') }}" required maxlength="190"></div>
<div><label for="category">Kategoriya (ixtiyoriy)</label><input class="i" id="category" name="category" value="{{ old('category') }}" maxlength="60"></div>
<div><label for="sort">Tartib</label><input class="i" id="sort" type="number" name="sort" value="{{ old('sort', 0) }}" min="0"></div>
<div><label for="file">Fayl</label><input class="i" id="file" type="file" name="file" accept=".pdf,.doc,.docx" required></div>
<div class="sm:col-span-2"><label for="description">Tavsif (ixtiyoriy)</label><textarea class="i" id="description" name="description" rows="2" maxlength="2000">{{ old('description') }}</textarea></div>
<button class="btn sm:col-span-2">Yuklash</button></form></div>
<div class="card overflow-x-auto"><table class="w-full text-sm"><tr><th>Nomi</th><th>Kategoriya</th><th>Tartib</th><th>Ko‘rilgan</th><th><span class="sr-only">Amallar</span></th></tr>
@forelse($guides as $guide)<tr><td>{{ $guide->title }}<br><span class="text-xs text-slate-500">{{ $guide->original_name }}</span></td><td>{{ $guide->category ?: '—' }}</td><td>{{ $guide->sort }}</td><td>{{ $guide->views_count }}</td>
<td><details><summary class="cursor-pointer underline">Tahrirlash</summary>
<form method="post" action="/admin/yoriqnoma/{{ $guide->id }}" class="mt-2 grid gap-2">@csrf
<input class="i" name="title" value="{{ $guide->title }}" required maxlength="190">
<input class="i" name="category" value="{{ $guide->category }}" maxlength="60" placeholder="Kategoriya">
<input class="i" type="number" name="sort" value="{{ $guide->sort }}" min="0">
<textarea class="i" name="description" rows="2" maxlength="2000">{{ $guide->description }}</textarea>
<button class="btn">Saqlash</button></form>
<form method="post" action="/admin/yoriqnoma/{{ $guide->id }}" onsubmit="return confirm('Hujjat va fayli o‘chirilsinmi?')">@csrf @method('DELETE')<button class="mt-2 text-red-700 underline">O‘chirish</button></form>
</details></td></tr>
@empty<tr><td colspan="5" class="text-slate-500">Hujjatlar hali yo‘q.</td></tr>@endforelse</table></div>
@endsection
