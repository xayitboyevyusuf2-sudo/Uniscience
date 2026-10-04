@extends('layouts.app')
@section('content')
<form method="post" action="/profil" enctype="multipart/form-data" class="card">@csrf<h1 class="font-serif text-2xl mb-3">Profilni tahrirlash</h1>
<div class="flex items-center gap-4">@include('partials.avatar',['u'=>$u,'size'=>'w-20 h-20'])
<div class="flex-1"><label class="!mt-0">Rasm (ixtiyoriy, JPG/PNG/WEBP, 2 MB gacha)</label><input class="i" type="file" name="photo" accept="image/jpeg,image/png,image/webp">
@if($u->photo_path)<label class="font-normal"><input type="checkbox" name="remove_photo" value="1"> Rasmni o‘chirish</label>@endif</div></div>
<div class="grid sm:grid-cols-2 gap-x-4"><div><label>Ism</label><input class="i" name="first_name" value="{{ old('first_name',$u->first_name) }}" required></div>
<div><label>Familiya</label><input class="i" name="last_name" value="{{ old('last_name',$u->last_name) }}" required></div></div>
<label>Kurs</label><input class="i" type="number" name="course" min="1" max="6" value="{{ old('course',$u->course) }}" required>
<label>Qiziqishlar (ilmiy yo‘nalishlar)</label><input class="i" name="interests" maxlength="200" value="{{ old('interests',$u->interests) }}">
<label>Men haqimda</label><textarea class="i" name="bio" rows="5" maxlength="1000">{{ old('bio',$u->bio) }}</textarea>
<p class="text-sm text-slate-500 mt-2">Fakultet va yo‘nalish ballga ta’sir qiladi, shuning uchun uni faqat admin o‘zgartiradi. Profil faqat tizimga kirgan foydalanuvchilarga ko‘rinadi.</p>
<button class="btn mt-4">Saqlash</button> <a class="ml-3 text-sm underline" href="/talaba/{{ $u->id }}">Bekor qilish</a></form>
@endsection
