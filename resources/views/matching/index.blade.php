@extends('layouts.app')
@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3"><h1 class="font-serif text-2xl">Mentor tanlash</h1>
<p class="text-sm text-slate-600">Faol so‘rovlaringiz: {{ $activeRequests }}/{{ $maxActive }}@if(auth()->user()->isMentor()) · <a class="underline" href="/matching/slotlarim">Mening slotlarim</a> · <a class="underline" href="/matching/sorovlar">Kelgan so‘rovlar</a>@endif</p></div>
<div class="card"><form method="get" class="flex flex-wrap gap-3">
<input class="i max-w-xs" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Ism qidiruvi" maxlength="190">
<input class="i max-w-xs" name="faculty" value="{{ $filters['faculty'] ?? '' }}" placeholder="Fakultet" maxlength="190">
<input class="i max-w-xs" name="department" value="{{ $filters['department'] ?? '' }}" placeholder="Kafedra" maxlength="190">
<button class="btn">Filtrlash</button></form></div>
@forelse($mentors as $mentor)<section class="card"><div class="flex flex-wrap items-start justify-between gap-3"><div>
<b>{{ $mentor->fullName() ?: $mentor->name }}</b><p class="text-sm text-slate-600">{{ $mentor->faculty }}@if($mentor->department) · {{ $mentor->department }}@endif</p></div>
<a class="underline" href="/talaba/{{ $mentor->id }}">Profil</a></div>
@if($mentor->slots->isNotEmpty())<ul class="mt-3 space-y-2">
@foreach($mentor->slots as $slot)<li class="flex flex-wrap items-center justify-between gap-3 rounded-md border border-slate-200 p-3 text-sm">
<span>{{ $slot->slot_date->format('Y-m-d') }} · {{ substr($slot->start_time, 0, 5) }}–{{ substr($slot->end_time, 0, 5) }} · {{ $slot->location }}@if($slot->note) · {{ $slot->note }}@endif</span>
@if($slot->user_id !== auth()->id())<form method="post" action="/matching/slot/{{ $slot->id }}/sorov" class="flex flex-1 min-w-60 items-center gap-2">@csrf
<input class="i flex-1" name="message" placeholder="So‘rov matni (500 belgigacha)" maxlength="500" required>
<button class="btn">So‘rov yuborish</button></form>@else<span class="text-slate-500">Sizning slotingiz</span>@endif</li>@endforeach</ul>
@else<p class="mt-3 text-sm text-slate-500">Kelgusi bo‘sh vaqtlar yo‘q.</p>@endif</section>
@empty<div class="card text-slate-500">Mentorlar topilmadi.</div>@endforelse
@endsection
