@extends('layouts.app')
@section('content')
<form method="post" action="/parolni-tiklash/yangi" class="card">@csrf<h1 class="font-serif text-2xl">Yangi parol</h1>
<input type="hidden" name="token" value="{{ $token }}">
<label>Email</label><input class="i" type="email" name="email" value="{{ old('email',$email) }}" required>
<div class="flex items-end gap-2"><div class="flex-1"><label for="password">Yangi parol (kamida 8 belgi)</label><input class="i" id="password" type="password" name="password" required></div><button class="btn mb-0" type="button" data-password-toggle aria-controls="password" aria-label="Parolni ko‘rsatish" aria-pressed="false">Ko‘rsatish</button></div>
<div class="flex items-end gap-2"><div class="flex-1"><label for="password_confirmation">Parolni takrorlang</label><input class="i" id="password_confirmation" type="password" name="password_confirmation" required></div><button class="btn mb-0" type="button" data-password-toggle aria-controls="password_confirmation" aria-label="Parolni ko‘rsatish" aria-pressed="false">Ko‘rsatish</button></div>
<button class="btn mt-4">Saqlash</button></form>
@endsection
