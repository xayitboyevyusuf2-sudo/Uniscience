@extends('layouts.app')
@section('content')
<form method="post" action="/admin/foydalanuvchi/{{ $user->id }}/tahrir" class="card">@csrf
<h1 class="font-serif text-2xl">Foydalanuvchi ma’lumotlarini tahrirlash</h1>
<p class="mt-2 text-sm text-slate-500">{{ $user->email }}</p>
<div class="grid gap-x-4 sm:grid-cols-2"><div><label for="first_name">Ism</label><input class="i" id="first_name" name="first_name" value="{{ old('first_name',$user->first_name) }}" required></div>
<div><label for="last_name">Familiya</label><input class="i" id="last_name" name="last_name" value="{{ old('last_name',$user->last_name) }}" required></div></div>
<label for="patronymic">Otasining ismi</label><input class="i" id="patronymic" name="patronymic" value="{{ old('patronymic',$user->patronymic) }}">
<label for="university">OTM</label><input class="i" id="university" name="university" value="{{ old('university',$user->university) }}" required>
<label for="faculty">Fakultet</label><input class="i" id="faculty" name="faculty" value="{{ old('faculty',$user->faculty) }}" required>
<label for="direction">Yo‘nalish</label><select class="i" id="direction" name="direction" required>@foreach(config('uniscience.fields') as $field)<option value="{{ $field }}" @selected(old('direction',$user->direction)===$field)>{{ $field }}</option>@endforeach</select>
<label for="group_name">Guruh</label><input class="i" id="group_name" name="group_name" value="{{ old('group_name',$user->group_name) }}">
<button class="btn mt-4">Saqlash</button> <a class="ml-3 text-sm underline" href="/talaba/{{ $user->id }}">Bekor qilish</a>
</form>
@endsection
