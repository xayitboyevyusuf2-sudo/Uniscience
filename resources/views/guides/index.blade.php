@extends('layouts.app')
@section('content')
<h1 class="mb-4 font-serif text-2xl">Yo‘riqnoma va video darslar</h1>
<div class="card"><form method="get" class="flex flex-wrap gap-3"><input class="i max-w-xs" name="q" value="{{ $term }}" placeholder="Qidiruv" maxlength="190"><button class="btn">Qidirish</button></form></div>
<section class="card"><h2 class="font-serif text-xl">Hujjatlar</h2>
@forelse($guidesByCategory as $category => $guides)<h3 class="mt-4 font-semibold text-slate-700">{{ $category }}</h3>
@foreach($guides as $guide)<div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 py-3"><div><b>{{ $guide->title }}</b>@if($guide->isNew()) <span class="rounded-full bg-teal-100 px-2 py-0.5 text-xs font-semibold text-teal-800">Yangi</span>@endif
@if($guide->description)<p class="text-sm text-slate-600">{{ $guide->description }}</p>@endif
<p class="text-xs text-slate-500">{{ number_format($guide->size / 1024, 0) }} KB · Ko‘rilgan: {{ $guide->views_count }}</p></div>
<div class="flex gap-3"><a class="btn" href="/yoriqnoma/hujjat/{{ $guide->id }}">Ko‘rish</a><a class="underline self-center" href="/yoriqnoma/hujjat/{{ $guide->id }}/yuklab-olish">Yuklab olish</a></div></div>
@endforeach
@empty<p class="mt-3 text-slate-500">Hujjatlar topilmadi.</p>
@endforelse</section>
<section class="card"><h2 class="font-serif text-xl">Video darslar</h2>
@forelse($videosByCategory as $category => $videos)<h3 class="mt-4 font-semibold text-slate-700">{{ $category }}</h3>
@foreach($videos as $video)<div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 py-3"><div><b>{{ $video->title }}</b>@if($video->isNew()) <span class="rounded-full bg-teal-100 px-2 py-0.5 text-xs font-semibold text-teal-800">Yangi</span>@endif
@if($video->description)<p class="text-sm text-slate-600">{{ $video->description }}</p>@endif
<p class="text-xs text-slate-500">@if($video->duration_seconds)Davomiylik: {{ gmdate('i:s', $video->duration_seconds) }} daqiqa · @endif Ko‘rilgan: {{ $video->views_count }}</p></div>
<div class="flex gap-3"><a class="btn" href="/yoriqnoma/video/{{ $video->id }}">Ko‘rish</a><a class="underline self-center" href="/yoriqnoma/video/{{ $video->id }}/yuklab-olish">Yuklab olish</a></div></div>
@endforeach
@empty<p class="mt-3 text-slate-500">Videolar topilmadi.</p>
@endforelse</section>
@endsection
