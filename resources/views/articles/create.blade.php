@extends('layouts.app')
@section('content')
<form method="post" enctype="multipart/form-data" class="card">@csrf<h1 class="font-serif text-2xl">Maqola yuklash</h1>
<label>Maqola nomi</label><input class="i" name="title" value="{{ old('title') }}" required>
<label>Jurnal nomi <span class="font-normal text-slate-500">(OAK ro‘yxatidagidek to‘liq yozing)</span></label><input class="i" name="journal_name" value="{{ old('journal_name') }}" required>
<div class="grid sm:grid-cols-2 gap-x-4"><div><label>ISSN (ixtiyoriy)</label><input class="i" name="issn" placeholder="0000-0000" value="{{ old('issn') }}"></div>
<div><label>Chop etilgan sana</label><input class="i" type="date" name="published_at" max="{{ date('Y-m-d') }}" value="{{ old('published_at') }}" required></div></div>
<label>Jurnal havolasi (URL)</label><input class="i" type="url" name="url" value="{{ old('url') }}" required>
<label>O‘z pozitsiyangiz</label><select class="i" name="position">@foreach(config('uniscience.position_labels') as $k=>$l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
<label>Hammualliflar</label><input class="i" name="coauthors" value="{{ old('coauthors') }}">
<label>Annotatsiya</label><textarea class="i" name="abstract" rows="3">{{ old('abstract') }}</textarea>
<label>PDF (≤20 MB)</label><input class="i" type="file" name="pdf" accept="application/pdf">
<button class="btn mt-4">Yuklash va tekshirish</button></form>
@endsection
