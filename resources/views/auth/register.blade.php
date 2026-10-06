@extends('layouts.app')
@section('content')
<form method="post" class="card">@csrf<h1 class="font-serif text-2xl">Ro‘yxatdan o‘tish</h1>
<label for="category">Toifa</label><select class="i" id="category" name="category" required>
<option value="">Toifani tanlang</option>
@foreach(['bakalavr'=>'Bakalavr','magistr'=>'Magistr','tadqiqotchi'=>'Tadqiqotchi','professor'=>'Professor'] as $value=>$label)<option value="{{ $value }}" @selected(old('category')===$value)>{{ $label }}</option>@endforeach
</select>
<div class="grid sm:grid-cols-2 gap-x-4"><div><label for="first_name">Ism</label><input class="i" id="first_name" name="first_name" value="{{ old('first_name') }}" required></div><div><label for="last_name">Familiya</label><input class="i" id="last_name" name="last_name" value="{{ old('last_name') }}" required></div></div>
<label for="patronymic">Otasining ismi</label><input class="i" id="patronymic" name="patronymic" value="{{ old('patronymic') }}" required>
<label for="university">OTM</label><select class="i" id="university" name="university" required>@foreach(\App\Models\University::where('is_active', true)->orderBy('sort')->orderBy('name')->get() as $university)<option value="{{ $university->name }}" @selected(old('university')===$university->name)>{{ $university->name }}</option>@endforeach</select>
<label for="direction">Yo‘nalish (soha)</label><select class="i" id="direction" name="direction" required>@foreach(config('uniscience.fields') as $f)<option value="{{ $f }}" @selected(old('direction')===$f)>{{ $f }}</option>@endforeach</select>
<section data-category-block="student">
<label for="student_id">Talaba ID raqami</label><input class="i" id="student_id" name="student_id" value="{{ old('student_id') }}" data-required>
<label for="faculty">Fakultet</label><input class="i" id="faculty" name="faculty" value="{{ old('faculty') }}" data-required>
<label for="course">Kurs</label><input class="i" id="course" type="number" name="course" min="1" max="6" value="{{ old('course',3) }}" data-required>
<label for="group_name">Guruh</label><input class="i" id="group_name" name="group_name" value="{{ old('group_name') }}" data-required>
<label for="gpa">GPA (0–5)</label><input class="i" id="gpa" type="number" name="gpa" min="0" max="5" step="0.01" value="{{ old('gpa') }}" data-required>
<label for="birth_date">Tug‘ilgan sana</label><input class="i" id="birth_date" type="date" name="birth_date" value="{{ old('birth_date') }}" data-required>
</section>
<section data-category-block="magistr">
<label for="bachelor_university">Bakalavr bosqichidagi OTM (ixtiyoriy)</label><input class="i" id="bachelor_university" name="bachelor_university" value="{{ old('bachelor_university') }}">
</section>
<section data-category-block="academic">
<label for="academic_degree">Ilmiy daraja</label><input class="i" id="academic_degree" name="academic_degree" value="{{ old('academic_degree') }}" data-required>
<label for="position_title">Lavozim</label><input class="i" id="position_title" name="position_title" value="{{ old('position_title') }}" data-required>
<label for="department">Kafedra yoki bo‘lim</label><input class="i" id="department" name="department" value="{{ old('department') }}" data-required>
</section>
<label for="login">Login (kirish uchun)</label><input class="i" id="login" name="username" value="{{ old('username') }}" required maxlength="190" autocomplete="username">
<label for="email">Email</label><input class="i" id="email" type="email" name="email" value="{{ old('email') }}" required>
<div class="flex items-end gap-2"><div class="flex-1"><label for="password">Parol</label><input class="i" id="password" type="password" name="password" required></div><button class="btn mb-0" type="button" data-password-toggle aria-controls="password" aria-label="Parolni ko‘rsatish" aria-pressed="false">Ko‘rsatish</button></div>
<div class="flex items-end gap-2"><div class="flex-1"><label for="password_confirmation">Parolni takrorlang</label><input class="i" id="password_confirmation" type="password" name="password_confirmation" required></div><button class="btn mb-0" type="button" data-password-toggle aria-controls="password_confirmation" aria-label="Parolni ko‘rsatish" aria-pressed="false">Ko‘rsatish</button></div>
<label class="font-normal"><input type="checkbox" name="consent" value="1" @checked(old('consent'))> Shaxsiy ma’lumotlarni qayta ishlashga roziman (O‘RQ-547) — <a class="underline" href="/maxfiylik" target="_blank">Maxfiylik siyosati</a></label>
<button class="btn mt-4">Ro‘yxatdan o‘tish</button></form>
<script>
document.addEventListener('DOMContentLoaded', () => {
	const category = document.querySelector('#category');
	const sections = document.querySelectorAll('[data-category-block]');

	const updateFields = () => {
		for (const section of sections) {
			const type = section.dataset.categoryBlock;
			const visible = type === 'student'
				? ['bakalavr', 'magistr'].includes(category.value)
				: type === 'magistr'
					? category.value === 'magistr'
					: type === 'academic'
						? ['tadqiqotchi', 'professor'].includes(category.value)
						: false;

			section.hidden = !visible;
			for (const field of section.querySelectorAll('input, select')) {
				field.disabled = !visible;
				field.required = visible && field.hasAttribute('data-required');
			}
		}
	};

	category.addEventListener('change', updateFields);
	updateFields();
});
</script>
@endsection
