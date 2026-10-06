@extends('layouts.app')
@section('hero')
@guest
<section class="hero text-white"><div class="relative max-w-5xl mx-auto px-4 py-14 md:py-20 grid md:grid-cols-2 gap-8 items-center">
<div><h1 class="font-serif text-4xl md:text-5xl font-bold leading-tight">Ilmiy natijalaringiz bir joyda</h1>
<p class="mt-4 text-blue-100 text-lg">Maqolangizni yuklang — tizim jurnalni OAK ro‘yxati bo‘yicha tekshiradi va reytingga qo‘shadi. Stipendiya komissiyasi uchun ma’lumotnoma bir bosishda tayyor.</p>
<div class="mt-6 flex flex-wrap gap-3"><a href="/royxat" class="btn-cta">Ro‘yxatdan o‘tish</a><a href="/baza" class="border border-white/60 rounded-md px-5 py-2.5">Ilmiy bazani ko‘rish</a></div></div>
@if(file_exists(public_path('images/logo.png')))<img src="/images/logo.png" alt="UniScience" class="hidden md:block w-full max-w-xs mx-auto rounded-xl bg-white/10 p-6">@else<svg class="hidden md:block w-full max-w-xs mx-auto text-teal-300" viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="40" y="40" width="120" height="120"/><rect x="40" y="40" width="120" height="120" transform="rotate(45 100 100)"/><rect x="62" y="62" width="76" height="76" opacity=".7"/><rect x="62" y="62" width="76" height="76" transform="rotate(45 100 100)" opacity=".7"/><circle cx="100" cy="100" r="14"/></svg>@endif
</div></section>
@endguest
@endsection
@section('content')
@auth
<div class="flex flex-wrap items-end justify-between gap-3 mb-5"><div><p class="text-slate-500">Xush kelibsiz</p><h1 class="font-serif text-3xl">{{ auth()->user()->first_name ?? auth()->user()->name }}</h1></div><a class="btn" href="/yuklash">+ Maqola yuklash</a></div>
<div class="grid sm:grid-cols-3 gap-4">
<div class="card"><p class="text-sm text-slate-500">Reyting balli</p><p class="font-serif text-4xl text-lapis mt-1">{{ number_format($score['total'],2) }}</p></div>
<div class="card"><p class="text-sm text-slate-500">Tasdiqlangan maqolalar</p><p class="font-serif text-4xl text-tl mt-1">{{ auth()->user()->articles()->where('status','approved')->count() }}</p></div>
<div class="card"><p class="text-sm text-slate-500">Tekshiruvda</p><p class="font-serif text-4xl text-amber-600 mt-1">{{ auth()->user()->articles()->whereIn('status',['pending','manual'])->count() }}</p></div></div>
<a class="text-lapis underline" href="/portfel">Portfelni ko‘rish →</a>
@if($latestNews->isNotEmpty())<section class="mt-6"><div class="mb-2 flex items-center justify-between"><h2 class="font-serif text-xl">So‘nggi yangiliklar</h2><a class="text-sm underline" href="/yangiliklar">Barchasi</a></div>
<div class="grid gap-3 sm:grid-cols-3">@foreach($latestNews as $item)<a class="card !mb-0 block hover:shadow-md" href="/yangiliklar"><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-700">{{ config('uniscience.news_types.'.$item->type, $item->type) }}</span>
<h3 class="mt-2 font-semibold">{{ $item->title }}</h3><p class="mt-1 line-clamp-3 text-sm text-slate-600">{{ $item->body }}</p>
<p class="mt-2 text-xs text-slate-500">@if($item->deadline)Muddat: {{ $item->deadline->format('Y-m-d') }} · @endif{{ $item->created_at->format('Y-m-d') }}</p></a>@endforeach</div></section>@endif
@else
<div class="grid grid-cols-3 gap-3 text-center -mt-12 relative">
@foreach([['Talabalar',$stats['students']],['Tasdiqlangan maqolalar',$stats['articles']],['OAK jurnallari',$stats['journals']]] as [$l,$n])<div class="card !mb-0 shadow-md"><p class="font-serif text-3xl text-lapis">{{ $n }}</p><p class="text-xs sm:text-sm text-slate-500">{{ $l }}</p></div>@endforeach</div>
<h2 class="font-serif text-2xl mt-10 mb-4">Nima uchun UniScience?</h2>
<div class="grid md:grid-cols-3 gap-4">
@foreach([['M12 16V4m0 0-4 4m4-4 4 4M4 20h16','Bir joyga yuklang','Maqolalaringiz Telegramda yoki kompyuterda yo‘qolib ketmaydi.'],['M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z M9 12l2 2 4-4','Soniyalarda tekshiruv','Jurnal OAK ro‘yxatida bo‘lganmi — chop etilgan sanada. Qoida bo‘yicha, sun’iy intellektsiz.'],['M4 20V10m6 10V4m6 16v-7m4 7H2','Ochiq reyting','Har bir talaba ballining qanday hisoblanganini o‘zi ko‘radi.']] as [$d,$t,$x])
<div class="card"><span class="inline-flex w-11 h-11 rounded-full bg-teal-50 text-tl items-center justify-center"><svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $d }}"/></svg></span><h3 class="font-serif text-lg mt-3">{{ $t }}</h3><p class="text-slate-600 text-sm mt-1">{{ $x }}</p></div>@endforeach</div>
<h2 class="font-serif text-2xl mt-8 mb-4">Qanday ishlaydi</h2>
<div class="grid sm:grid-cols-4 gap-4">
@foreach(['Maqolani yuklang','Tizim tekshiradi','Reytingga qo‘shiladi','Ma’lumotnoma oling'] as $i=>$s)<div class="card !mb-0"><span class="font-serif text-3xl text-lapis/30">{{ $i+1 }}</span><p class="font-medium mt-1">{{ $s }}</p></div>@endforeach</div>
@endauth
@endsection
