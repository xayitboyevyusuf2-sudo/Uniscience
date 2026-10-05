@extends('layouts.app')
@section('content')
<form method="post" action="/profil" enctype="multipart/form-data" class="card">@csrf<h1 class="font-serif text-2xl mb-3">Profilni tahrirlash</h1>
<div class="flex items-center gap-4">@include('partials.avatar',['u'=>$u,'size'=>'w-20 h-20'])
<div class="flex-1"><label class="!mt-0" for="photo">Rasm (JPG/JPEG/PNG, 5 MB gacha)</label><input class="i" id="photo" type="file" name="photo" accept="image/jpeg,image/png">
@if($u->photo_path)<label class="font-normal"><input type="checkbox" name="remove_photo" value="1"> Rasmni o‘chirish</label>@endif</div></div>
<div class="grid sm:grid-cols-2 gap-x-4"><div><label for="first_name">Ism (admin tahrirlaydi)</label><input class="i" id="first_name" value="{{ $u->first_name }}" disabled></div>
<div><label for="last_name">Familiya (admin tahrirlaydi)</label><input class="i" id="last_name" value="{{ $u->last_name }}" disabled></div></div>
<label for="patronymic">Otasining ismi (admin tahrirlaydi)</label><input class="i" id="patronymic" value="{{ $u->patronymic }}" disabled>
<label for="university">OTM (admin tahrirlaydi)</label><input class="i" id="university" value="{{ $u->university }}" disabled>
@if($u->isStudent())<div class="grid sm:grid-cols-2 gap-x-4"><div><label for="course">Kurs</label><input class="i" id="course" type="number" name="course" min="1" max="6" value="{{ old('course',$u->course) }}" required></div>
<div><label for="group_name">Guruh</label><input class="i" id="group_name" name="group_name" maxlength="40" value="{{ old('group_name',$u->group_name) }}"></div></div>@endif
<div class="grid sm:grid-cols-2 gap-x-4"><div><label for="phone">Telefon</label><input class="i" id="phone" type="tel" name="phone" maxlength="20" value="{{ old('phone',$u->phone) }}"></div>
<div><label for="telegram">Telegram</label><input class="i" id="telegram" name="telegram" maxlength="60" value="{{ old('telegram',$u->telegram) }}"></div></div>
<label>Qiziqishlar (ilmiy yo‘nalishlar)</label><input class="i" name="interests" maxlength="200" value="{{ old('interests',$u->interests) }}">
<label>Men haqimda</label><textarea class="i" name="bio" rows="5" maxlength="1000">{{ old('bio',$u->bio) }}</textarea>
<div class="mt-4 border-t border-slate-200 pt-3"><h2 class="font-semibold">Parolni o‘zgartirish</h2><div class="grid sm:grid-cols-3 gap-3">
<div><label for="current_password">Joriy parol</label><input class="i" id="current_password" type="password" name="current_password" autocomplete="current-password"></div>
<div><label for="password">Yangi parol</label><input class="i" id="password" type="password" name="password" autocomplete="new-password"></div>
<div><label for="password_confirmation">Yangi parolni takrorlang</label><input class="i" id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password"></div>
</div></div>
<p class="text-sm text-slate-500 mt-2">Fakultet va yo‘nalishni faqat administrator o‘zgartiradi. Profilga faqat tasdiqlangan foydalanuvchilar kira oladi.</p>
<button class="btn mt-4">Saqlash</button> <a class="ml-3 text-sm underline" href="/talaba/{{ $u->id }}">Bekor qilish</a></form>
@endsection
