@extends('layouts.app')
@section('content')
<h1 class="mb-4 font-serif text-2xl">Yangiliklar</h1>
<nav class="mb-3 flex flex-wrap gap-1 text-sm" aria-label="Tab">
<a class="px-3 py-1.5 rounded-md {{ $tab==='faol' ? 'bg-lapis text-white font-semibold' : 'bg-white border border-slate-200 hover:bg-slate-100' }}" href="/yangiliklar?tab=faol">Faol</a>
<a class="px-3 py-1.5 rounded-md {{ $tab==='arxiv' ? 'bg-lapis text-white font-semibold' : 'bg-white border border-slate-200 hover:bg-slate-100' }}" href="/yangiliklar?tab=arxiv">Arxiv</a>
</nav>
<div class="card"><form method="get" class="flex flex-wrap gap-3">
<input type="hidden" name="tab" value="{{ $tab }}">
<input class="i max-w-xs" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Sarlavha yoki matn" maxlength="190">
<select class="i" name="type" aria-label="Tur"><option value="">Barcha turlar</option>@foreach($types as $key => $label)<option value="{{ $key }}" @selected(($filters['type'] ?? '')===$key)>{{ $label }}</option>@endforeach</select>
<button class="btn">Filtrlash</button></form></div>
@forelse($news as $item)<article class="card"><div class="flex flex-wrap items-start justify-between gap-3"><div>
@if($item->pinned)<span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">Yopishtirilgan</span>@endif
<span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-700">{{ $types[$item->type] ?? $item->type }}</span>
<h2 class="mt-2 font-serif text-xl">{{ $item->title }}</h2>
<p class="mt-1 whitespace-pre-line text-slate-700">{{ $item->body }}</p>
<p class="mt-2 text-sm text-slate-500">
@if($item->event_date)Sana: {{ $item->event_date->format('Y-m-d') }} · @endif
@if($item->deadline)Muddat: {{ $item->deadline->format('Y-m-d') }} · @endif
{{ $item->created_at->format('Y-m-d') }}</p></div></div>
<div class="mt-2 flex gap-4 text-sm">
@if($item->link)<a class="text-lapis underline" href="{{ $item->link }}" rel="noopener" target="_blank">Batafsil havola</a>@endif
@if($item->attachment_path)<a class="text-lapis underline" href="/yangiliklar/{{ $item->id }}/ilova">Ilovani yuklab olish{{ $item->attachment_name ? ' ('.$item->attachment_name.')' : '' }}</a>@endif
</div></article>
@empty<div class="card text-slate-500">{{ $tab==='arxiv' ? 'Arxivda yangiliklar yo‘q.' : 'Faol yangiliklar yo‘q.' }}</div>@endforelse
{{ $news->links() }}
@endsection
