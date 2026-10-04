@extends('layouts.app')
@section('content')
@php $max = max(1, collect($s['rows'])->max('pts') ?? 1); @endphp
<div class="card"><div class="flex flex-wrap justify-between items-center gap-3"><div><p class="text-sm text-slate-500">Reyting balli</p><div class="font-serif text-5xl text-lapis">{{ number_format($s['total'],2) }}</div></div>
<form method="post" action="/malumotnoma">@csrf<button class="btn">Stipendiya ma’lumotnomasi yaratish</button></form></div>
<p class="text-slate-500 text-sm mt-2">Ball = Σ(W_soha × W_daraja × W_muallif × W_sana){{ $s['diversity'] ? ' × 0.8 (bitta jurnal)' : '' }}</p></div>
@if(collect($s['rows'])->where('pts','>',0)->count())
<div class="card"><h2 class="font-serif text-lg mb-3">Maqolalar bo‘yicha ball</h2>
@foreach($s['rows'] as $r)@if($r['pts']>0)<div class="mb-2"><div class="flex justify-between text-sm"><span class="truncate pr-3">{{ $r['article']->title }}</span><span class="font-semibold">{{ number_format($r['pts'],2) }}</span></div>
<div class="h-2.5 rounded-full bg-slate-100"><div class="h-2.5 rounded-full bg-tl" style="width: {{ round($r['pts']/$max*100) }}%"></div></div></div>@endif @endforeach</div>
@endif
<div class="card overflow-x-auto"><table class="w-full text-sm"><tr><th>Maqola</th><th>Holat</th><th>Ball</th></tr>
@forelse($articles as $a)<tr><td><a class="text-lapis underline" href="/maqola/{{ $a->id }}">{{ $a->title }}</a><br><span class="text-slate-500">{{ $a->journal_name }} · {{ $a->published_at->format('Y-m-d') }}</span></td>
<td>@include('partials.status',['s'=>$a->status])<br><span class="text-slate-500">{{ $a->reason }}</span></td>
<td>{{ isset($s['rows'][$a->id]) ? number_format($s['rows'][$a->id]['pts'],2) : '—' }}</td></tr>
@empty<tr><td colspan="3">Hozircha maqola yo‘q. <a class="text-lapis underline" href="/yuklash">Birinchi maqolani yuklang</a>.</td></tr>@endforelse</table></div>
@endsection
