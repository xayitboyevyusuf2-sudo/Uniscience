@extends('layouts.app')
@section('content')
<h1 class="font-serif text-2xl mb-3">Mening reytingim</h1>
<div class="card"><p class="text-sm text-slate-500">Umumiy ball</p><p class="font-serif text-4xl text-lapis">{{ number_format((float) $rating->score, 2) }}</p>
<dl class="mt-3 grid gap-2 sm:grid-cols-3 text-sm">
<div><dt class="text-slate-500">Guruhdagi o‘rin</dt><dd class="text-xl font-semibold">{{ $rating->rank_group ?? '—' }}</dd></div>
<div><dt class="text-slate-500">Fakultetdagi o‘rin</dt><dd class="text-xl font-semibold">{{ $rating->rank_faculty ?? '—' }}</dd></div>
<div><dt class="text-slate-500">Universitetdagi o‘rin</dt><dd class="text-xl font-semibold">{{ $rating->rank_university ?? '—' }}</dd></div>
</dl><p class="mt-2 text-sm text-slate-500">Toifa: {{ ucfirst($rating->category) }} · Yangilangan: {{ $rating->computed_at?->format('Y-m-d H:i') }}</p></div>
<div class="card overflow-x-auto"><h2 class="font-serif text-lg mb-2">Maqolalar bo‘yicha koeffitsientlar</h2>
<table class="w-full text-sm"><tr><th>Maqola</th><th>W_soha</th><th>W_daraja</th><th>W_muallif</th><th>W_sana</th><th>Ball</th><th>Holat</th></tr>
@forelse($rating->items as $item)<tr><td><a class="underline" href="/maqola/{{ $item->article_id }}">{{ $item->article?->title ?? 'Maqola #'.$item->article_id }}</a></td><td>{{ number_format((float) $item->w_soha, 2) }}</td><td>{{ number_format((float) $item->w_daraja, 2) }}</td><td>{{ number_format((float) $item->w_muallif, 2) }}</td><td>{{ number_format((float) $item->w_sana, 2) }}</td><td>{{ number_format((float) $item->points, 2) }}</td><td>@if($item->counted)Hisobga olingan@else<span class="text-amber-700">Yillik chegaradan oshgan — hisobga olinmadi</span>@endif</td></tr>
@empty<tr><td colspan="7" class="text-slate-500">Hozircha maqolalar yo‘q.</td></tr>@endforelse</table></div>
@endsection
