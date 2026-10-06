@extends('layouts.app')
@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3"><h1 class="font-serif text-2xl">Jurnallar ma’lumotnomasi</h1><a class="btn" href="/admin/jurnallar/yangi">Yangi jurnal</a></div>
<div class="card"><form method="get" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
<input class="i" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nomi yoki ISSN" maxlength="190">
<select class="i" name="field" aria-label="Soha"><option value="">Soha (barchasi)</option>@foreach($fields as $field)<option value="{{ $field }}" @selected(($filters['field'] ?? '')===$field)>{{ $field }}</option>@endforeach</select>
<select class="i" name="tier" aria-label="Daraja"><option value="">Daraja (barchasi)</option>@foreach($tiers as $tier)<option value="{{ $tier }}" @selected(($filters['tier'] ?? '')===$tier)>{{ $tier }}</option>@endforeach</select>
<select class="i" name="source" aria-label="Manba"><option value="">Manba (barchasi)</option>@foreach($sources as $source)<option value="{{ $source }}" @selected(($filters['source'] ?? '')===$source)>{{ $source }}</option>@endforeach</select>
<select class="i" name="holat" aria-label="Holat"><option value="">Holat (barchasi)</option><option value="royxatda" @selected(($filters['holat'] ?? '')==='royxatda')>Ro‘yxatda</option><option value="chiqarilgan" @selected(($filters['holat'] ?? '')==='chiqarilgan')>Chiqarilgan</option></select>
<button class="btn">Filtrlash</button></form></div>
@if(session('import_errors'))<div class="card"><h2 class="font-serif text-lg">O‘tkazib yuborilgan yozuvlar</h2><ul class="mt-2 list-inside list-disc text-sm text-red-700">@foreach(session('import_errors') as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="card overflow-x-auto"><table class="w-full text-sm"><tr><th>ISSN</th><th>Nomi</th><th>Soha</th><th>Daraja</th><th>Manba</th><th>Ro‘yxatda</th><th><span class="sr-only">Amallar</span></th></tr>
@foreach($journals as $journal)<tr><td>{{ $journal->issn ?: '—' }}</td><td>{{ $journal->name }}@if($journal->tier==='X')<p class="text-xs font-semibold text-red-700">{{ $journal->warningMessage() }}</p>@endif</td><td>{{ $journal->field }}</td><td>{{ $journal->tier }}</td><td>{{ $journal->source ?: '—' }}</td><td>{{ $journal->listed_from->format('Y-m-d') }} — {{ $journal->listed_to?->format('Y-m-d') ?? 'hozirgacha' }}</td>
<td class="whitespace-nowrap"><a class="underline" href="/admin/jurnallar/{{ $journal->id }}/tahrir">Tahrirlash</a>@if(!$journal->listed_to)<form method="post" action="/admin/jurnallar/{{ $journal->id }}/chiqarish" class="inline" onsubmit="return confirm('Jurnalni ro‘yxatdan chiqarish? listed_to = bugun qo‘yiladi.')">@csrf<button class="ml-2 text-red-700 underline">Ro‘yxatdan chiqarish</button></form>@endif</td></tr>@endforeach</table>
{{ $journals->links() }}</div>
<div class="card"><h2 class="font-serif text-xl">JSON import</h2><p class="text-sm text-slate-500">Format: [{"issn","name","field","tier","listed_from","listed_to","country","publisher","source"}]. Eski yozuvlar o‘chirilmaydi; xato qatorlar sababi bilan hisobotda ko‘rsatiladi.</p>
<form method="post" action="/admin/jurnallar/import-json" enctype="multipart/form-data">@csrf<input class="i" type="file" name="json" accept=".json,application/json"><button class="btn mt-2">Import</button></form></div>
@endsection
