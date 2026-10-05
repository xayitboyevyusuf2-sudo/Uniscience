@extends('layouts.app')
@section('content')
<div class="card"><h2 class="font-serif text-xl">Qo‘lda tekshiruv navbati</h2>
@if($all->count())<datalist id="jl">@foreach($all as $j)<option value="{{ $j->id }} · {{ $j->name }}">@endforeach</datalist>@endif
@forelse($queue as $a)<form method="post" action="/admin/qaror/{{ $a->id }}" class="border-t border-slate-200 py-3">@csrf
<b>{{ $a->title }}</b> — {{ $a->user->name }}<br><span class="text-sm text-slate-500">{{ $a->reason }}</span>
<br><span class="text-sm">Talaba yozgan: {{ $a->journal_name }} · ISSN {{ $a->issn ?: '—' }}</span>
@if(!$a->journal_id)<input class="i mt-2" name="journal_pick" list="jl" placeholder="Mos jurnalni tanlang (nomini yozing)" autocomplete="off">
@if(($suggest[$a->id] ?? collect())->count())<p class="text-xs mt-1">Takliflar: @foreach($suggest[$a->id] as $s)<button type="button" class="underline mr-2" data-v="{{ $s->id }} · {{ $s->name }}" onclick="this.form.journal_pick.value=this.dataset.v">{{ \Illuminate\Support\Str::limit($s->name,40) }}</button>@endforeach</p>@endif @endif
<input class="i mt-2" name="note" placeholder="Izoh (rad etish uchun majburiy)">
<button name="decision" value="approve" class="btn mt-2">Tasdiqlash</button> <button name="decision" value="reject" class="btn mt-2 bg-red-700!">Rad etish</button></form>
@empty<p class="text-slate-500">Navbat bo‘sh.</p>@endforelse</div>
@if(auth()->user()->role==='admin')
<div class="card"><h2 class="font-serif text-xl">Sozlamalar</h2><form method="post" action="/admin/sozlamalar">@csrf
<label>Yiliga hisobga olinadigan maqolalar</label><input class="i" type="number" name="yearly_limit" value="{{ $limit }}"><button class="btn mt-2">Saqlash</button></form>
@foreach($log as $l)<p class="text-xs text-slate-500">{{ $l->created_at }}: {{ $l->key }} {{ $l->old }} → {{ $l->new }}</p>@endforeach</div>
<div class="card"><h2 class="font-serif text-xl">OAK ro‘yxatini import (CSV)</h2><p class="text-sm text-slate-500">Ustunlar: issn,name,field,tier,listed_from,listed_to. Eski yozuvlar o‘chirilmaydi.</p>
<form method="post" action="/admin/import" enctype="multipart/form-data">@csrf<input class="i" type="file" name="csv" accept=".csv,text/csv"><button class="btn mt-2">Import</button></form></div>
<div class="card overflow-x-auto"><h2 class="font-serif text-xl">Foydalanuvchilar</h2><table class="w-full text-sm">
@foreach($users as $u)<tr><td>{{ $u->name }}<br><span class="text-slate-500">{{ $u->email }}</span>@if($u->approval_status==='pending')<br><span class="text-amber-700">Tasdiq kutilmoqda</span>@endif</td><td><form method="post" action="/admin/foydalanuvchi/{{ $u->id }}" class="flex gap-2 items-center">@csrf
<select class="i" name="role">@foreach(['student','moderator','admin'] as $r)<option @selected($u->role===$r)>{{ $r }}</option>@endforeach</select>
<label class="m-0 whitespace-nowrap font-normal"><input type="checkbox" name="blocked" value="1" @checked($u->blocked)> blok</label><button class="btn">OK</button></form>
@if($u->approval_status==='pending')<div class="flex gap-2 mt-2"><form method="post" action="/admin/foydalanuvchi/{{ $u->id }}/tasdiq">@csrf<input type="hidden" name="decision" value="approve"><button class="btn">Tasdiqlash</button></form><form method="post" action="/admin/foydalanuvchi/{{ $u->id }}/tasdiq">@csrf<input type="hidden" name="decision" value="reject"><button class="btn bg-red-700!">Rad etish</button></form></div>@endif
</td></tr>@endforeach</table></div>
<div class="card overflow-x-auto"><h2 class="font-serif text-xl">Jurnallar</h2><table class="w-full text-sm"><tr><th>ISSN</th><th>Nomi</th><th>Soha</th><th>Daraja</th><th>Ro‘yxatda</th></tr>
@foreach($journals as $j)<tr><td>{{ $j->issn }}</td><td>{{ $j->name }}</td><td>{{ $j->field }}</td><td>{{ $j->tier }}</td><td>{{ $j->listed_from->format('Y-m-d') }} — {{ $j->listed_to?->format('Y-m-d') ?? 'hozirgacha' }}</td></tr>@endforeach</table>{{ $journals->links() }}</div>
@endif
@endsection
