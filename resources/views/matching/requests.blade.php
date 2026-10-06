@extends('layouts.app')
@section('content')
<h1 class="mb-4 font-serif text-2xl">Kelgan mentorlik so‘rovlari</h1>
@forelse($requests as $request)<div class="card"><div class="flex flex-wrap items-start justify-between gap-3"><div>
<b>{{ $request->student->fullName() ?: $request->student->name }}</b> · reyting: {{ number_format((float) ($request->student->rating?->score ?? 0), 2) }}
<p class="text-sm text-slate-600">{{ $request->slot->slot_date->format('Y-m-d') }} · {{ substr($request->slot->start_time, 0, 5) }}–{{ substr($request->slot->end_time, 0, 5) }} · {{ $request->slot->location }}</p>
<p class="mt-1 whitespace-pre-line">{{ $request->message }}</p>
<a class="text-sm underline" href="/talaba/{{ $request->student_id }}">Portfelni ko‘rish</a></div></div>
<form method="post" action="/matching/sorovlar/{{ $request->id }}/javob" class="mt-3 flex flex-wrap items-center gap-3">@csrf
<input class="i max-w-md" name="response_note" placeholder="Izoh (ixtiyoriy)" maxlength="500">
<button class="btn" name="decision" value="accept">Qabul</button>
<button class="btn bg-red-700!" name="decision" value="reject">Rad</button></form></div>
@empty<div class="card text-slate-500">Kutilayotgan so‘rovlar yo‘q.</div>@endforelse
@endsection
