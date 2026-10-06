@extends('layouts.app')
@section('content')
<div class="lg:flex lg:items-start lg:gap-5">
<div class="mb-4 lg:mb-0 lg:w-56 lg:shrink-0 lg:sticky lg:top-20">@include('admin.partials.sidebar')</div>
<div class="flex-1 min-w-0 space-y-4">
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
<div class="card"><div class="flex flex-wrap items-center justify-between gap-3"><h2 class="font-serif text-xl">Sozlamalar</h2><span class="flex gap-3 text-sm"><a class="underline" href="/admin/audit">Admin audit jurnali</a><a class="underline" href="/admin/yangiliklar">Yangiliklar boshqaruvi</a><a class="underline" href="/admin/yoriqnoma">Yo‘riqnoma hujjatlari</a><a class="underline" href="/admin/videolar">Video darslar</a></span></div><form method="post" action="/admin/sozlamalar">@csrf
<label>Yiliga hisobga olinadigan maqolalar</label><input class="i" type="number" name="yearly_limit" value="{{ $limit }}"><button class="btn mt-2">Saqlash</button></form>
@foreach($log as $l)<p class="text-xs text-slate-500">{{ $l->created_at }}: {{ $l->key }} {{ $l->old }} → {{ $l->new }}</p>@endforeach</div>
<div class="card"><h2 class="font-serif text-xl">Ball koeffitsientlari (FR-53)</h2><p class="text-sm text-slate-500">Bo‘sh qoldirilgan maydon standart qiymatni saqlaydi. Saqlangach barcha reytinglar qayta hisoblanadi.</p>
<form method="post" action="/admin/sozlamalar/koeffitsientlar" class="mt-3 grid gap-3 sm:grid-cols-3">@csrf
@foreach($scoring['tier'] as $tier => $value)<div><label for="k_tier_{{ $tier }}">Daraja {{ $tier }}</label><input class="i" id="k_tier_{{ $tier }}" name="tier[{{ $tier }}]" type="number" step="1" min="0" max="100" value="{{ $value }}" placeholder="{{ $scoringDefaults['tier'][$tier] }}"></div>@endforeach
@foreach($scoring['position'] as $position => $value)<div><label for="k_pos_{{ $position }}">Muallif: {{ config('uniscience.position_labels.'.$position, $position) }}</label><input class="i" id="k_pos_{{ $position }}" name="position[{{ $position }}]" type="number" step="0.1" min="0" max="1" value="{{ $value }}" placeholder="{{ $scoringDefaults['position'][$position] }}"></div>@endforeach
@foreach($scoring['date'] as $key => $value)<div><label for="k_date_{{ $key }}">Sana: {{ ['y1'=>'≤1 yil','y2'=>'≤2 yil','y3'=>'≤3 yil','older'=>'eski'][$key] }}</label><input class="i" id="k_date_{{ $key }}" name="date[{{ $key }}]" type="number" step="0.1" min="0" max="1" value="{{ $value }}" placeholder="{{ $scoringDefaults['date'][$key] }}"></div>@endforeach
@foreach($scoring['field'] as $key => $value)<div><label for="k_field_{{ $key }}">Soha: {{ ['same'=>'mos','related'=>'qarindosh','other'=>'boshqa'][$key] }}</label><input class="i" id="k_field_{{ $key }}" name="field[{{ $key }}]" type="number" step="0.1" min="0" max="1" value="{{ $value }}" placeholder="{{ $scoringDefaults['field'][$key] }}"></div>@endforeach
<div><label for="k_diversity">Xilma-xillik koeffitsienti</label><input class="i" id="k_diversity" name="diversity_factor" type="number" step="0.1" min="0" max="1" value="{{ $scoring['diversity_factor'] }}" placeholder="0.8"></div>
<div><label for="k_monthly">Oylik bayroq chegarasi</label><input class="i" id="k_monthly" name="monthly_flag_threshold" type="number" step="1" min="1" max="100" value="{{ $scoring['monthly_flag_threshold'] }}" placeholder="5"></div>
<div class="sm:col-span-3"><button class="btn">Koeffitsientlarni saqlash</button></div></form>
<h3 class="mt-4 font-semibold">O‘zgartirish tarixi</h3><div class="mt-2 overflow-x-auto"><table class="w-full text-xs"><tr><th>Vaqt</th><th>Kim</th><th>Kalit</th><th>Eski → Yangi</th></tr>
@forelse($scoringHistory as $entry)<tr><td>{{ $entry->created_at }}</td><td>{{ $entry->user_name ?? '—' }}</td><td>{{ $entry->key }}</td><td>{{ $entry->old ?? '—' }} → {{ $entry->new ?? '—' }}</td></tr>
@empty<tr><td colspan="4" class="text-slate-500">Tarix yozuvlari yo‘q.</td></tr>@endforelse</table></div></div>
<div class="card"><h2 class="font-serif text-xl">OAK ro‘yxatini import (CSV)</h2><p class="text-sm text-slate-500">Ustunlar: issn,name,field,tier,listed_from,listed_to. Eski yozuvlar o‘chirilmaydi.</p>
<form method="post" action="/admin/import" enctype="multipart/form-data">@csrf<input class="i" type="file" name="csv" accept=".csv,text/csv"><button class="btn mt-2">Import</button></form></div>
<div class="card overflow-x-auto"><h2 class="font-serif text-xl">Foydalanuvchilar</h2><table class="w-full text-sm">
@foreach($users as $u)<tr><td>{{ $u->name }}<br><span class="text-slate-500">{{ $u->email }}</span>@if($u->approval_status==='pending')<br><span class="text-amber-700">Tasdiq kutilmoqda</span>@endif</td><td><form method="post" action="/admin/foydalanuvchi/{{ $u->id }}" class="flex gap-2 items-center">@csrf
<select class="i" name="role">@foreach(['student','moderator','admin'] as $r)<option @selected($u->role===$r)>{{ $r }}</option>@endforeach</select>
<label class="m-0 whitespace-nowrap font-normal"><input type="checkbox" name="blocked" value="1" @checked($u->blocked)> blok</label><button class="btn">OK</button>
<details class="mt-2"><summary class="cursor-pointer text-sm underline">Moderator fakultetlari</summary><select class="i mt-2" name="faculty_ids[]" multiple size="3" aria-label="Moderator fakultetlari">@foreach($facultyOptions as $faculty)<option value="{{ $faculty }}" @selected(in_array($faculty,$facultyAssignments[$u->id] ?? [],true))>{{ $faculty }}</option>@endforeach</select></details></form>
@if(auth()->user()->role==='admin')<a class="mt-2 inline-block text-sm underline" href="/admin/foydalanuvchi/{{ $u->id }}/tahrir">Tahrirlash</a>@endif
@if($u->approval_status==='pending')<div class="flex gap-2 mt-2"><form method="post" action="/admin/foydalanuvchi/{{ $u->id }}/tasdiq">@csrf<input type="hidden" name="decision" value="approve"><button class="btn">Tasdiqlash</button></form><form method="post" action="/admin/foydalanuvchi/{{ $u->id }}/tasdiq">@csrf<input type="hidden" name="decision" value="reject"><button class="btn bg-red-700!">Rad etish</button></form></div>@endif
</td></tr>@endforeach</table></div>
<div class="card overflow-x-auto"><div class="flex flex-wrap items-center justify-between gap-3"><h2 class="font-serif text-xl">Jurnallar</h2><a class="text-sm underline" href="/admin/jurnallar">Jurnallar ma’lumotnomasi (CRUD)</a></div><table class="w-full text-sm"><tr><th>ISSN</th><th>Nomi</th><th>Soha</th><th>Daraja</th><th>Ro‘yxatda</th></tr>
@foreach($journals as $j)<tr><td>{{ $j->issn }}</td><td>{{ $j->name }}</td><td>{{ $j->field }}</td><td>{{ $j->tier }}</td><td>{{ $j->listed_from->format('Y-m-d') }} — {{ $j->listed_to?->format('Y-m-d') ?? 'hozirgacha' }}</td></tr>@endforeach</table>{{ $journals->links() }}</div>
@endif
</div></div>
@endsection
