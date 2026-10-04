@extends('layouts.app')
@section('content')
<div class="card"><h1 class="font-serif text-2xl">Portfel ma’lumotnomasi</h1>
<p class="mt-2"><b>{{ $c->user->name }}</b> · {{ $c->user->faculty }} · {{ $c->user->direction }} · {{ $c->user->course }}-kurs</p>
<p class="font-serif text-4xl text-lapis my-2">{{ number_format($s['total'],2) }} <span class="text-base text-slate-500">ball</span></p>
<p class="text-sm text-slate-500">Berilgan sana: {{ $c->created_at->format('Y-m-d') }}. Bu sahifa tizimdagi haqiqiy yozuvni ko‘rsatadi.</p>
@if(class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)){!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(130)->generate(url()->current()) !!}@endif</div>
<div class="card overflow-x-auto"><table class="w-full text-sm"><tr><th>Maqola</th><th>Jurnal</th><th>Daraja</th><th>Ball</th></tr>
@foreach($s['rows'] as $r)@if($r['counted'])<tr><td>{{ $r['article']->title }}</td><td>{{ $r['article']->journal->name }}</td><td>{{ $r['article']->journal->tier }}</td><td>{{ number_format($r['pts'],2) }}</td></tr>@endif @endforeach</table></div>
@endsection
