@extends('layouts.app')
@section('content')
<form method="post" class="card">@csrf<h1 class="font-serif text-2xl">Kirish</h1>
<label for="login">Email yoki username</label><input class="i" id="login" type="text" name="login" value="{{ old('login') }}" autocomplete="username" required>
<div class="flex items-end gap-2"><div class="flex-1"><label for="password">Parol</label><input class="i" id="password" type="password" name="password" autocomplete="current-password" required></div><button class="btn mb-0" type="button" data-password-toggle aria-controls="password" aria-pressed="false">Ko‘rsatish</button></div>
<label class="font-normal"><input type="checkbox" name="remember" value="1" @checked(old('remember'))> Eslab qolish</label>
<button class="btn mt-4">Kirish</button> <a class="ml-3 text-sm underline" href="/royxat">Ro‘yxatdan o‘tish</a> <a class="ml-3 text-sm underline" href="/parolni-tiklash">Parolni unutdingizmi?</a></form>
@endsection
