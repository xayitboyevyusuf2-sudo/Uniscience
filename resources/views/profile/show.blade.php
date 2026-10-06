@extends('layouts.app')
@section('content')
@php($categoryStyles = ['bakalavr' => 'from-blue-600 to-blue-800', 'magistr' => 'from-indigo-600 to-indigo-800', 'tadqiqotchi' => 'from-teal-600 to-teal-800', 'professor' => 'from-amber-600 to-amber-800'])
@php($categoryLabels = ['bakalavr' => 'Bakalavr', 'magistr' => 'Magistr', 'tadqiqotchi' => 'Tadqiqotchi', 'professor' => 'Professor'])
<div class="card-hero {{ $categoryStyles[$u->category] ?? 'from-lapis to-[#0e2440]' }} flex flex-wrap gap-5 items-center">
@include('partials.avatar',['u'=>$u,'size'=>'w-24 h-24 ring-4 ring-white/30'])
<div class="flex-1 min-w-[12rem]"><span class="badge-gold mb-2">{{ $categoryLabels[$u->category] ?? ucfirst($u->category ?? '—') }}</span><h1 class="font-serif text-2xl">{{ $u->fullName() }}</h1>
<p class="text-blue-100">{{ $u->university }} · {{ $u->faculty }} · {{ $u->direction }}</p>
@if($u->isStudent())<p class="text-blue-100">{{ $u->student_id }} · {{ $u->course }}-kurs · {{ $u->group_name }} · GPA {{ $u->gpa }}</p>@endif
@if($u->isMentor())<p class="text-blue-100">{{ $u->academic_degree }} · {{ $u->position_title }} · {{ $u->department }}</p>@endif
@if($u->interests)<p class="mt-1 text-sm"><span class="text-blue-200">Qiziqishlari:</span> {{ $u->interests }}</p>@endif</div>
<div class="sm:text-right"><p class="text-sm text-blue-200">Reyting balli</p><p class="font-serif text-4xl">{{ number_format($s['total'],2) }}</p>
<p class="mt-1 text-sm text-blue-100">O‘rin: guruh {{ $rating->rank_group ?? '—' }} · fakultet {{ $rating->rank_faculty ?? '—' }} · universitet {{ $rating->rank_university ?? '—' }}</p>
@if(auth()->id()===$u->id)<a class="btn-cta inline-block mt-2" href="/profil">Profilni tahrirlash</a>@endif</div></div>
<div class="card"><h2 class="font-serif text-lg mb-2">Ro‘yxat ma’lumotlari</h2><dl class="grid gap-2 sm:grid-cols-2">
<div><dt class="text-sm text-slate-500">Toifa</dt><dd>{{ $categoryLabels[$u->category] ?? ucfirst($u->category ?? '—') }}</dd></div>
<div><dt class="text-sm text-slate-500">Tug‘ilgan sana</dt><dd>{{ $u->birth_date?->format('Y-m-d') ?? '—' }}</dd></div>
<div><dt class="text-sm text-slate-500">Telefon</dt><dd>{{ $u->phone ?: '—' }}</dd></div>
<div><dt class="text-sm text-slate-500">Telegram</dt><dd>{{ $u->telegram ?: '—' }}</dd></div>
@if($u->isMentor())<div><dt class="text-sm text-slate-500">Ilmiy daraja</dt><dd>{{ $u->academic_degree }}</dd></div><div><dt class="text-sm text-slate-500">Lavozim</dt><dd>{{ $u->position_title }}</dd></div><div><dt class="text-sm text-slate-500">Kafedra</dt><dd>{{ $u->department }}</dd></div>@endif
</dl>
<div class="mt-3 flex flex-wrap gap-3"><a class="underline" href="/bildirishnomalar">Bildirishnomalar</a>@if(auth()->user()->role==='admin')<a class="underline" href="/admin/foydalanuvchi/{{ $u->id }}/tahrir">Admin tahriri</a>@endif</div></div>
@if($u->bio)<div class="card"><h2 class="font-serif text-lg mb-2">Men haqimda</h2><p class="whitespace-pre-line">{{ $u->bio }}</p></div>
@elseif(auth()->id()===$u->id)<div class="card text-slate-500">Hali o‘zingiz haqingizda yozmagansiz. <a class="text-lapis underline" href="/profil">Qo‘shish</a></div>@endif
<div class="card overflow-x-auto"><h2 class="font-serif text-lg mb-2">Tasdiqlangan maqolalar</h2><table class="w-full text-sm"><tr><th>Maqola</th><th>Jurnal</th><th>Daraja</th><th>Ball</th></tr>
@forelse($rows as $r)<tr><td>{{ $r['article']->title }}</td><td>{{ $r['article']->journal->name }}</td><td>{{ $r['article']->journal->tier }}</td><td>{{ number_format($r['pts'],2) }}</td></tr>
@empty<tr><td colspan="4" class="text-slate-500">Hozircha tasdiqlangan maqola yo‘q.</td></tr>@endforelse</table></div>
@include('profile.partials.supervision')
@include('profile.partials.leisure')
@endsection
