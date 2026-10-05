@extends('layouts.app')
@section('content')
<section class="card">
<h1 class="font-serif text-2xl">Email manzilingizni tasdiqlang</h1>
<p class="mt-3">Tasdiqlash havolasi {{ auth()->user()->email }} manziliga yuborildi. Profilingiz va boshqa himoyalangan bo‘limlarni ochish uchun emailni tasdiqlang.</p>
<form method="post" action="/email/tasdiqlash" class="mt-4">@csrf<button class="btn">Havolani qayta yuborish</button></form>
</section>
@endsection