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
 if (auth()->check()) { $nav[] = ['/portfel','Portfel']; $nav[] = ['/yuklash','Yuklash']; $nav[] = ['/profil','Profil']; }
 $nav[] = ['/reyting','Reyting']; $nav[] = ['/baza','Baza'];
 if (auth()->check() && in_array(auth()->user()->role,['admin','moderator'])) $nav[] = ['/admin','Boshqaruv'];
@endphp
<nav class="flex flex-wrap gap-1 text-sm flex-1">@foreach($nav as [$u,$l])<a href="{{ $u }}" class="px-3 py-1.5 rounded-md {{ request()->is(ltrim($u,'/').'*') ? 'bg-white/20 font-semibold' : 'hover:bg-white/10' }}">{{ $l }}</a>@endforeach</nav>
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
