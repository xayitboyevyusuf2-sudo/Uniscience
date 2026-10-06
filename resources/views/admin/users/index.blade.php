@extends('layouts.app')
@section('content')
<div class="lg:flex lg:items-start lg:gap-5">
<div class="mb-4 lg:mb-0 lg:w-56 lg:shrink-0 lg:sticky lg:top-20">@include('admin.partials.sidebar')</div>
<div class="flex-1 min-w-0">
<h1 class="mb-4 font-serif text-2xl">Foydalanuvchilar</h1>
<div class="card"><form method="get" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
<input class="i" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Ism, email, username, student ID" maxlength="190">
<select class="i" name="role" aria-label="Rol"><option value="">Rol (barchasi)</option>@foreach(['student','moderator','admin','rahbariyat'] as $role)<option value="{{ $role }}" @selected(($filters['role'] ?? '')===$role)>{{ $role }}</option>@endforeach</select>
<select class="i" name="category" aria-label="Toifa"><option value="">Toifa (barchasi)</option>@foreach($categories as $category)<option value="{{ $category }}" @selected(($filters['category'] ?? '')===$category)>{{ $category }}</option>@endforeach</select>
<select class="i" name="approval_status" aria-label="Tasdiq holati"><option value="">Holat (barchasi)</option>@foreach(['pending'=>'Kutilmoqda','approved'=>'Tasdiqlangan','rejected'=>'Rad etilgan'] as $key=>$label)<option value="{{ $key }}" @selected(($filters['approval_status'] ?? '')===$key)>{{ $label }}</option>@endforeach</select>
<select class="i" name="faculty" aria-label="Fakultet"><option value="">Fakultet (barchasi)</option>@foreach($faculties as $faculty)<option value="{{ $faculty }}" @selected(($filters['faculty'] ?? '')===$faculty)>{{ $faculty }}</option>@endforeach</select>
<div class="sm:col-span-2 lg:col-span-5"><button class="btn">Filtrlash</button></div></form></div>
<div class="card overflow-x-auto"><table class="w-full text-sm"><tr><th>F.I.Sh.</th><th>Email</th><th>Rol</th><th>Toifa</th><th>Holat</th><th><span class="sr-only">Amallar</span></th></tr>
@forelse($users as $user)<tr><td><a class="underline" href="/talaba/{{ $user->id }}">{{ $user->fullName() ?: $user->name }}</a><br><span class="text-xs text-slate-500">{{ $user->student_id ?: $user->username }}</span></td><td>{{ $user->email }}</td><td>{{ $user->role }}</td><td>{{ $user->category ?: '—' }}</td>
<td>{{ $user->approval_status }}{{ $user->blocked ? ' · bloklangan' : '' }}</td>
<td class="whitespace-nowrap"><a class="underline" href="/admin/foydalanuvchi/{{ $user->id }}/tahrir">Tahrirlash</a>
@if($user->approval_status==='pending')<form method="post" action="/admin/foydalanuvchi/{{ $user->id }}/tasdiq" class="inline">@csrf<button class="ml-2 underline" name="decision" value="approve">Tasdiqlash</button><button class="ml-2 text-red-700 underline" name="decision" value="reject">Rad</button></form>@endif</td></tr>
@empty<tr><td colspan="6" class="text-slate-500">Foydalanuvchilar topilmadi.</td></tr>@endforelse</table>{{ $users->links() }}</div>
<div class="card"><h2 class="font-serif text-lg">Xodim hisobi yaratish</h2><p class="text-sm text-slate-500">Admin, moderator yoki rahbariyat uchun: email tasdiqlangan bo‘ladi, toifa bo‘sh qoladi.</p>
<form method="post" action="/admin/foydalanuvchilar/xodim" class="mt-2 grid gap-3 sm:grid-cols-2">@csrf
<div><label for="staff_name">F.I.Sh.</label><input class="i" id="staff_name" name="name" value="{{ old('name') }}" required maxlength="190"></div>
<div><label for="staff_email">Email</label><input class="i" id="staff_email" type="email" name="email" value="{{ old('email') }}" required maxlength="190"></div>
<div><label for="staff_role">Rol</label><select class="i" id="staff_role" name="role" required>@foreach(['admin','moderator','rahbariyat'] as $role)<option value="{{ $role }}">{{ $role }}</option>@endforeach</select></div>
<div><label for="staff_password">Parol</label><input class="i" id="staff_password" type="password" name="password" required autocomplete="new-password"></div>
<button class="btn sm:col-span-2">Hisob yaratish</button></form></div>
</div></div>
@endsection
