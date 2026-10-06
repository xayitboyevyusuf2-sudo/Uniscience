@extends('layouts.app')
@section('content')
<form method="post" enctype="multipart/form-data" class="card">@csrf<h1 class="font-serif text-2xl">Maqola yuklash</h1>
<label for="title">Maqola nomi</label><input class="i" id="title" name="title" value="{{ old('title') }}" required maxlength="500">
<label for="type">Nashr turi</label><select class="i" id="type" name="type" required>@foreach(config('uniscience.article_types') as $key=>$articleType)<option value="{{ $key }}" data-family="{{ $articleType['family'] }}" @selected(old('type','journal_local_oak')===$key)>{{ $articleType['label'] }}</option>@endforeach</select>
<div class="grid gap-4 md:grid-cols-3">
<div><label for="annotation_uz">Annotatsiya (o‘zbek tilida)</label><textarea class="i" id="annotation_uz" name="annotation_uz" rows="6" minlength="50" required>{{ old('annotation_uz') }}</textarea></div>
<div><label for="annotation_ru">Аннотация (на русском языке)</label><textarea class="i" id="annotation_ru" name="annotation_ru" rows="6" minlength="50" required>{{ old('annotation_ru') }}</textarea></div>
<div><label for="annotation_en">Abstract (in English)</label><textarea class="i" id="annotation_en" name="annotation_en" rows="6" minlength="50" required>{{ old('annotation_en') }}</textarea></div>
</div>
<div class="grid gap-4 md:grid-cols-3">
<div><label for="keywords_uz">Kalit so‘zlar (vergul bilan, kamida 3 ta)</label><input class="i" id="keywords_uz" name="keywords_uz" value="{{ old('keywords_uz') }}" required maxlength="500"></div>
<div><label for="keywords_ru">Ключевые слова (через запятую, минимум 3)</label><input class="i" id="keywords_ru" name="keywords_ru" value="{{ old('keywords_ru') }}" required maxlength="500"></div>
<div><label for="keywords_en">Keywords (comma-separated, at least 3)</label><input class="i" id="keywords_en" name="keywords_en" value="{{ old('keywords_en') }}" required maxlength="500"></div>
</div>
<div class="grid gap-x-4 sm:grid-cols-2"><div class="relative"><label for="journal_name">Jurnal yoki nashr nomi</label><input class="i" id="journal_name" name="journal_name" value="{{ old('journal_name') }}" autocomplete="off" required><input id="journal_id" type="hidden" name="journal_id" value="{{ old('journal_id') }}"><ul id="journal-search-results" class="absolute z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-md border border-slate-200 bg-white shadow-lg" hidden></ul><p id="dangerous-journal-warning" class="mt-2 text-sm font-semibold text-red-700" hidden>Ushbu jurnal xavfli jurnallar ro‘yxatida; maqola 0 ball oladi.</p></div>
<div><label for="issn">ISSN (ixtiyoriy)</label><input class="i" id="issn" name="issn" placeholder="0000-0000" value="{{ old('issn') }}"></div>
<div><label for="doi">DOI (ixtiyoriy)</label><input class="i" id="doi" name="doi" value="{{ old('doi') }}" maxlength="190"></div>
<div><label for="published_at">Chop etilgan sana</label><input class="i" id="published_at" type="date" name="published_at" max="{{ date('Y-m-d') }}" value="{{ old('published_at') }}" required></div>
<div><label for="url">Nashr havolasi (URL)</label><input class="i" id="url" type="url" name="url" value="{{ old('url') }}" required></div></div>
<section data-article-family="journal"><label class="font-normal"><input type="checkbox" name="journal_not_listed" value="1" @checked(old('journal_not_listed'))> Jurnal ro‘yxatda yo‘q</label></section>
<section data-article-family="scopus"><label for="scopus-field">Soha</label><select class="i" id="scopus-field" name="field" data-required><option value="">Sohani tanlang</option>@foreach(config('uniscience.fields') as $field)<option value="{{ $field }}" @selected(old('field')===$field)>{{ $field }}</option>@endforeach</select></section>
<section data-article-family="conference"><label for="conference-field">Soha</label><select class="i" id="conference-field" name="field" data-required><option value="">Sohani tanlang</option>@foreach(config('uniscience.fields') as $field)<option value="{{ $field }}" @selected(old('field')===$field)>{{ $field }}</option>@endforeach</select>
<label for="conference_certificate">Konferensiya sertifikati PDF (majburiy, 10 MB gacha)</label><input class="i" id="conference_certificate" type="file" name="conference_certificate" accept="application/pdf,.pdf" data-required></section>
<section class="mt-5 border-t border-slate-200 pt-4"><div class="flex flex-wrap items-center justify-between gap-3"><h2 class="font-serif text-lg">Mualliflar</h2><button class="btn" type="button" data-add-author>Muallif qo‘shish</button></div>
<div id="article-authors" data-next-index="1">
<fieldset class="mt-3 grid gap-3 rounded-md border border-slate-200 p-3 sm:grid-cols-2" data-author-row>
<legend class="px-1">1-muallif</legend>
<div><label for="author-name-0">F.I.Sh.</label><input class="i" id="author-name-0" name="authors[0][full_name]" value="{{ old('authors.0.full_name', auth()->user()->fullName()) }}" required maxlength="190"></div>
<div><label for="author-user-0">Tizimdagi professor/tadqiqotchi (ixtiyoriy)</label><select class="i" id="author-user-0" name="authors[0][user_id]" data-author-user><option value="">Tanlanmagan</option>@foreach($mentors as $mentor)<option value="{{ $mentor->id }}" data-name="{{ $mentor->fullName() }}" @selected((string) old('authors.0.user_id')===(string) $mentor->id)>{{ $mentor->fullName() }}</option>@endforeach</select></div>
<div><label for="author-position-0">Muallif pozitsiyasi</label><select class="i" id="author-position-0" name="authors[0][position]" required>@foreach(config('uniscience.position_labels') as $position=>$label)<option value="{{ $position }}" @selected(old('authors.0.position','yolgiz')===$position)>{{ $label }}</option>@endforeach</select></div>
<input type="hidden" name="authors[0][is_submitter]" value="1" data-submitter-value data-author-index="0">
<label class="m-0 self-end font-normal"><input type="radio" name="submitter_index" value="0" checked> Yuboruvchi</label>
</fieldset>
</div>
<template id="article-author-template"><fieldset class="mt-3 grid gap-3 rounded-md border border-slate-200 p-3 sm:grid-cols-2" data-author-row>
<legend class="px-1">Muallif __INDEX__</legend>
<div><label for="author-name-__INDEX__">F.I.Sh.</label><input class="i" id="author-name-__INDEX__" name="authors[__INDEX__][full_name]" required maxlength="190"></div>
<div><label for="author-user-__INDEX__">Tizimdagi professor/tadqiqotchi (ixtiyoriy)</label><select class="i" id="author-user-__INDEX__" name="authors[__INDEX__][user_id]" data-author-user><option value="">Tanlanmagan</option>@foreach($mentors as $mentor)<option value="{{ $mentor->id }}" data-name="{{ $mentor->fullName() }}">{{ $mentor->fullName() }}</option>@endforeach</select></div>
<div><label for="author-position-__INDEX__">Muallif pozitsiyasi</label><select class="i" id="author-position-__INDEX__" name="authors[__INDEX__][position]" required>@foreach(config('uniscience.position_labels') as $position=>$label)<option value="{{ $position }}">{{ $label }}</option>@endforeach</select></div>
<input type="hidden" name="authors[__INDEX__][is_submitter]" value="0" data-submitter-value data-author-index="__INDEX__">
<label class="m-0 self-end font-normal"><input type="radio" name="submitter_index" value="__INDEX__"> Yuboruvchi</label>
<button class="text-sm text-red-700 underline sm:col-span-2 sm:justify-self-end" type="button" data-remove-author>Muallifni olib tashlash</button>
</fieldset></template>
</section>
<label for="pdf">Maqola PDF fayli (majburiy, 20 MB gacha)</label><input class="i" id="pdf" type="file" name="pdf" accept="application/pdf,.pdf" required>
<button class="btn mt-4">Yuklash va tekshirish</button></form>
@endsection
