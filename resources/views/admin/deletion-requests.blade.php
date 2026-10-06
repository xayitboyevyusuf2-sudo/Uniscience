@extends('layouts.app')
@section('content')
<div class="lg:flex lg:items-start lg:gap-5">
<div class="mb-4 lg:mb-0 lg:w-56 lg:shrink-0 lg:sticky lg:top-20">@include('admin.partials.sidebar')</div>
<div class="flex-1 min-w-0">
<h1 class="mb-4 font-serif text-2xl">O‘chirish so‘rovlari</h1>
@forelse($requests as $request)<div class="card"><div class="flex flex-wrap items-center justify-between gap-3"><div>
<b>{{ $request->user?->fullName() ?: $request->user?->name }}</b> · {{ $request->user?->email }}
<p class="text-sm text-slate-500">{{ $request->created_at->format('Y-m-d H:i') }} · {{ $request->status }}@if($request->note) · {{ $request->note }}@endif</p></div></div>
@if($request->status==='open')<form method="post" action="/admin/ochirish-sorovlari/{{ $request->id }}" class="mt-3 flex flex-wrap items-center gap-3">@csrf
<input class="i max-w-md" name="note" placeholder="Izoh (rad etish uchun majburiy)" maxlength="500">
<button class="btn" name="decision" value="done" onclick="return confirm('Foydalanuvchi anonimlashtiriladi. Davom etilsinmi?')">Tasdiqlash (anonimlashtirish)</button>
<button class="btn bg-red-700!" name="decision" value="rejected">Rad etish</button></form>@endif</div>
@empty<div class="card text-slate-500">Ochiq o‘chirish so‘rovlari yo‘q.</div>@endforelse
{{ $requests->links() }}
</div></div>
@endsection
