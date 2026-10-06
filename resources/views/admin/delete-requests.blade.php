@extends('layouts.app')
@section('content')
<div class="lg:flex lg:items-start lg:gap-5">
<div class="mb-4 lg:mb-0 lg:w-56 lg:shrink-0 lg:sticky lg:top-20">@include('admin.partials.sidebar')</div>
<div class="card flex-1"><h1 class="font-serif text-2xl">O‘chirish so‘rovlari</h1>
<p class="mt-2 text-slate-500">Hozircha o‘chirish so‘rovi mexanizmi yo‘q. Jurnallar hech qachon o‘chirilmaydi — «ro‘yxatdan chiqarish» <code>listed_to</code> orqali bajariladi (<a class="underline" href="/admin/jurnallar">Jurnallar</a>).</p></div>
</div>
@endsection
