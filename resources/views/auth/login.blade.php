@extends('layouts.app')
@section('content')
<form method="post" class="card">@csrf<h1 class="font-serif text-2xl">Kirish</h1>
<label>Email</label><input class="i" type="email" name="email" value="{{ old('email') }}" required>
<label>Parol</label><input class="i" type="password" name="password" required>
<button class="btn mt-4">Kirish</button> <a class="ml-3 text-sm underline" href="/royxat">Ro‘yxatdan o‘tish</a> <a class="ml-3 text-sm underline" href="/parolni-tiklash">Parolni unutdingizmi?</a></form>
@endsection
