@extends('layouts.app')
@section('content')
<form method="post" class="card">@csrf<h1 class="font-serif text-2xl">Ro‘yxatdan o‘tish</h1>
<div class="grid sm:grid-cols-2 gap-x-4"><div><label>Ism</label><input class="i" name="first_name" value="{{ old('first_name') }}" required></div><div><label>Familiya</label><input class="i" name="last_name" value="{{ old('last_name') }}" required></div></div>
<label>Student ID (HEMIS)</label><input class="i" name="student_id" value="{{ old('student_id') }}" required>
<label>Fakultet</label><input class="i" name="faculty" value="{{ old('faculty') }}" required>
<label>Yo‘nalish (soha)</label><select class="i" name="direction">@foreach(config('uniscience.fields') as $f)<option>{{ $f }}</option>@endforeach</select>
<label>Kurs</label><input class="i" type="number" name="course" min="1" max="6" value="{{ old('course',3) }}">
<label>Email</label><input class="i" type="email" name="email" value="{{ old('email') }}" required>
<label>Parol (kamida 8 belgi)</label><input class="i" type="password" name="password" required>
<label>Parolni takrorlang</label><input class="i" type="password" name="password_confirmation" required>
<label class="font-normal"><input type="checkbox" name="consent" value="1"> Shaxsiy ma’lumotlarni qayta ishlashga roziman (O‘RQ-547)</label>
<button class="btn mt-4">Ro‘yxatdan o‘tish</button></form>
@endsection
