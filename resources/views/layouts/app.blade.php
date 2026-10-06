<!DOCTYPE html>
<html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>@yield('title','UniScience.uz — Talabaning raqamli ilmiy portfeli')</title>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600&family=Source+Serif+4:wght@600;700&display=swap" rel="stylesheet">
@vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-900 font-sans min-h-screen flex flex-col">
<header class="bg-lapis text-white sticky top-0 z-10 shadow"><div class="max-w-5xl mx-auto px-4 py-3 flex flex-wrap items-center gap-x-6 gap-y-2">
<a href="/" class="flex items-center gap-2 font-serif text-xl font-bold"><svg class="w-7 h-7 text-teal-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="5" y="5" width="14" height="14"/><rect x="5" y="5" width="14" height="14" transform="rotate(45 12 12)"/></svg>UniScience.uz</a>
@php
 $nav = [];
 if (auth()->check()) { $nav[] = ['/portfel','Portfel']; $nav[] = ['/yuklash','Yuklash']; $nav[] = ['/jurnallar','Jurnallar']; $nav[] = ['/yoriqnoma','Yo‘riqnoma']; $nav[] = ['/yangiliklar','Yangiliklar']; $nav[] = ['/matching','Matching']; $nav[] = ['/profil','Profil']; }
 $nav[] = ['/reyting','Reyting']; $nav[] = ['/baza','Baza'];
 if (auth()->check() && in_array(auth()->user()->role,['admin','moderator'])) { $nav[] = ['/admin','Boshqaruv']; $nav[] = ['/moderator/navbat','Moderator navbati']; }
@endphp
<nav class="flex flex-wrap gap-1 text-sm flex-1">@foreach($nav as [$u,$l])<a href="{{ $u }}" class="px-3 py-1.5 rounded-md {{ request()->is(ltrim($u,'/').'*') ? 'bg-white/20 font-semibold' : 'hover:bg-white/10' }}">{{ $l }}</a>@endforeach</nav>
@auth
<details class="relative shrink-0">
<summary class="relative flex size-10 list-none cursor-pointer items-center justify-center rounded-md hover:bg-white/10" aria-label="Bildirishnomalar">
<svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
@if($notificationUnreadCount > 0)<span class="absolute -right-1 -top-1 min-w-5 rounded-full bg-red-600 px-1 text-center text-xs leading-5 text-white">{{ $notificationUnreadCount > 99 ? '99+' : $notificationUnreadCount }}</span>@endif
</summary>
<div class="absolute right-0 z-30 mt-2 w-80 max-w-[calc(100vw-2rem)] overflow-hidden rounded-md border border-slate-200 bg-white text-slate-900 shadow-xl">
<div class="flex items-center justify-between gap-2 border-b border-slate-200 px-4 py-3"><b>Bildirishnomalar</b><a class="text-sm underline" href="/bildirishnomalar">Barchasi</a></div>
@if($notificationUnreadCount > 0)<form method="post" action="/bildirishnomalar/hammasi-oqildi" class="border-b border-slate-200 px-4 py-2">@csrf<button class="text-sm underline">Hammasini o‘qildi deb belgilash</button></form>@endif
<ul class="max-h-96 overflow-y-auto divide-y divide-slate-200">
@forelse($recentNotifications as $notification)
<li class="flex items-start gap-2 px-4 py-3 {{ $notification->read_at ? '' : 'bg-sky-50' }}"><a class="min-w-0 flex-1" href="{{ $notification->data['url'] ?? '/bildirishnomalar' }}"><span class="block text-sm font-semibold">{{ $notification->data['title'] ?? 'Bildirishnoma' }}</span><span class="mt-1 block text-xs text-slate-600">{{ $notification->data['body'] ?? '' }}</span></a>@if(! $notification->read_at)<form method="post" action="{{ route('notifications.read', $notification->id) }}">@csrf<button class="text-xs underline" aria-label="Bildirishnomani o‘qildi deb belgilash">O‘qildi</button></form>@endif</li>
@empty<li class="px-4 py-4 text-sm text-slate-500">Hozircha bildirishnomalar yo‘q.</li>
@endforelse
</ul>
</div>
</details>
@endauth
@auth<form method="post" action="/chiqish">@csrf<button class="text-sm px-3 py-1.5 rounded-md border border-white/40 hover:bg-white/10 cursor-pointer">Chiqish</button></form>@else<a class="text-sm px-3 py-1.5 rounded-md border border-white/40 hover:bg-white/10" href="/kirish">Kirish</a>@endauth
</div></header>
@yield('hero')
<main class="max-w-5xl mx-auto px-4 py-6 w-full flex-1">
@if(session('ok'))<div class="mb-4 p-3 rounded-md border-l-4 border-tl bg-white shadow-sm">{{ session('ok') }}</div>@endif
@if($errors->any())<div class="mb-4 p-3 rounded-md border-l-4 border-red-600 bg-white text-red-700 shadow-sm">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
@yield('content')
</main>
<footer class="bg-slate-900 text-slate-400 text-sm"><div class="max-w-5xl mx-auto px-4 py-5 flex flex-wrap justify-between gap-2"><span>UniScience.uz — talabaning raqamli ilmiy portfeli</span><span>Manba: OAK / tadqiq.uz (CC BY 4.0)</span></div></footer>
</body></html>
