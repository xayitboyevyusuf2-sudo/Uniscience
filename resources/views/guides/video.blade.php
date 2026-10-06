@extends('layouts.app')
@section('content')
<div class="mb-4"><a class="text-sm underline" href="/yoriqnoma">← Yo‘riqnomaga qaytish</a></div>
<section class="card"><h1 class="font-serif text-2xl">{{ $video->title }}</h1>
@if($video->description)<p class="mt-1 text-slate-600">{{ $video->description }}</p>@endif
<video class="mt-4 w-full max-w-3xl rounded-md bg-black" controls preload="metadata" src="/yoriqnoma/video/{{ $video->id }}/oqim"></video>
<p class="mt-3 text-sm text-slate-500">Ko‘rilgan: {{ $video->views_count }}</p>
<a class="btn mt-3 inline-block" href="/yoriqnoma/video/{{ $video->id }}/yuklab-olish">Yuklab olish</a></section>
@endsection
