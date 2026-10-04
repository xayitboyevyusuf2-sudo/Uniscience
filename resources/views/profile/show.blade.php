@extends('layouts.app')
@section('content')
<div class="card flex flex-wrap gap-5 items-center">
@include('partials.avatar',['u'=>$u,'size'=>'w-24 h-24'])
<div class="flex-1 min-w-[12rem]"><h1 class="font-serif text-2xl">{{ $u->name }}</h1>
<p class="text-slate-600">{{ $u->faculty }} · {{ $u->direction }} · {{ $u->course }}-kurs</p>
@if($u->interests)<p class="mt-1 text-sm"><span class="text-slate-500">Qiziqishlari:</span> {{ $u->interests }}</p>@endif</div>
<div class="sm:text-right"><p class="text-sm text-slate-500">Reyting balli</p><p class="font-serif text-4xl text-lapis">{{ number_format($s['total'],2) }}</p>
@if(auth()->id()===$u->id)<a class="btn inline-block mt-2" href="/profil">Profilni tahrirlash</a>@endif</div></div>
@if($u->bio)<div class="card"><h2 class="font-serif text-lg mb-2">Men haqimda</h2><p class="whitespace-pre-line">{{ $u->bio }}</p></div>
@elseif(auth()->id()===$u->id)<div class="card text-slate-500">Hali o‘zingiz haqingizda yozmagansiz. <a class="text-lapis underline" href="/profil">Qo‘shish</a></div>@endif
<div class="card overflow-x-auto"><h2 class="font-serif text-lg mb-2">Tasdiqlangan maqolalar</h2><table class="w-full text-sm"><tr><th>Maqola</th><th>Jurnal</th><th>Daraja</th><th>Ball</th></tr>
@forelse($rows as $r)<tr><td>{{ $r['article']->title }}</td><td>{{ $r['article']->journal->name }}</td><td>{{ $r['article']->journal->tier }}</td><td>{{ number_format($r['pts'],2) }}</td></tr>
@empty<tr><td colspan="4" class="text-slate-500">Hozircha tasdiqlangan maqola yo‘q.</td></tr>@endforelse</table></div>
@endsection
