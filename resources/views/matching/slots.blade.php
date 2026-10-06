@extends('layouts.app')
@section('content')
<h1 class="mb-4 font-serif text-2xl">Mening bo‘sh vaqt slotlarim</h1>
<div class="card"><h2 class="font-serif text-lg">Yangi slot</h2>
<form method="post" action="/matching/slotlarim" class="mt-2 grid gap-3 sm:grid-cols-2">@csrf
<div><label for="slot_date">Sana</label><input class="i" id="slot_date" type="date" name="slot_date" value="{{ old('slot_date', now()->toDateString()) }}" min="{{ now()->toDateString() }}" required></div>
<div><label for="location">Joylashuv (xona yoki «onlayn»)</label><input class="i" id="location" name="location" value="{{ old('location') }}" required maxlength="190"></div>
<div><label for="start_time">Boshlanish</label><input class="i" id="start_time" type="time" name="start_time" value="{{ old('start_time') }}" required></div>
<div><label for="end_time">Tugash</label><input class="i" id="end_time" type="time" name="end_time" value="{{ old('end_time') }}" required></div>
<div class="sm:col-span-2"><label for="note">Izoh (ixtiyoriy)</label><input class="i" id="note" name="note" value="{{ old('note') }}" maxlength="190"></div>
<button class="btn sm:col-span-2">Slot qo‘shish</button></form></div>
<div class="card overflow-x-auto"><table class="w-full text-sm"><tr><th>Sana</th><th>Vaqt</th><th>Joylashuv</th><th>So‘rovlar</th><th><span class="sr-only">Amallar</span></th></tr>
@forelse($slots as $slot)<tr><td>{{ $slot->slot_date->format('Y-m-d') }}</td><td>{{ substr($slot->start_time, 0, 5) }}–{{ substr($slot->end_time, 0, 5) }}</td><td>{{ $slot->location }}</td><td>{{ $slot->requests_count }} (qabul: {{ $slot->accepted_requests_count }})</td>
<td><details><summary class="cursor-pointer underline">Tahrirlash</summary>
<form method="post" action="/matching/slotlarim/{{ $slot->id }}" class="mt-2 grid gap-2">@csrf
<input class="i" type="date" name="slot_date" value="{{ $slot->slot_date->format('Y-m-d') }}" min="{{ now()->toDateString() }}" required>
<input class="i" type="time" name="start_time" value="{{ substr($slot->start_time, 0, 5) }}" required>
<input class="i" type="time" name="end_time" value="{{ substr($slot->end_time, 0, 5) }}" required>
<input class="i" name="location" value="{{ $slot->location }}" required maxlength="190">
<input class="i" name="note" value="{{ $slot->note }}" maxlength="190" placeholder="Izoh">
<button class="btn">Saqlash</button></form>
<form method="post" action="/matching/slotlarim/{{ $slot->id }}" onsubmit="return confirm('Slot o‘chirilsinmi? Faol so‘rovlar bekor qilinadi.')">@csrf @method('DELETE')<button class="mt-2 text-red-700 underline">O‘chirish</button></form>
</details></td></tr>
@empty<tr><td colspan="5" class="text-slate-500">Slotlar hali yo‘q.</td></tr>@endforelse</table></div>
@endsection
