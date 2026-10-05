@extends('layouts.app')
@section('content')
<section class="card">
<div class="flex flex-wrap items-center justify-between gap-3"><h1 class="font-serif text-2xl">Bildirishnomalar</h1><form method="post" action="/bildirishnomalar/hammasi-oqildi">@csrf<button class="btn">Hammasini o‘qildi deb belgilash</button></form></div>
<ul class="mt-4 divide-y divide-slate-200">
@forelse($notifications as $notification)
<li class="flex flex-wrap items-start gap-3 py-4 {{ $notification->read_at ? '' : 'bg-sky-50' }}"><a class="min-w-0 flex-1" href="{{ $notification->data['url'] ?? '/bildirishnomalar' }}"><span class="block font-semibold">{{ $notification->data['title'] ?? 'Bildirishnoma' }}</span><span class="mt-1 block text-sm text-slate-600">{{ $notification->data['body'] ?? '' }}</span></a><time class="text-xs text-slate-500">{{ $notification->created_at->format('Y-m-d H:i') }}</time>@if(! $notification->read_at)<form method="post" action="{{ route('notifications.read', $notification->id) }}">@csrf<button class="text-sm underline">O‘qildi</button></form>@endif</li>
@empty<li class="py-5 text-sm text-slate-500">Hozircha bildirishnomalar yo‘q.</li>
@endforelse
</ul>
{{ $notifications->links() }}
</section>
@endsection
