@extends('layouts.app')
@section('content')
<form method="post" action="/parolni-tiklash" class="card">@csrf<h1 class="font-serif text-2xl">Parolni tiklash</h1>
<label>Email</label><input class="i" type="email" name="email" value="{{ old('email') }}" required>
<button class="btn mt-4">Havola yuborish</button> <a class="ml-3 text-sm underline" href="/kirish">Kirishga qaytish</a></form>
@endsection
