@extends('layouts.app')
@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3"><h1 class="font-serif text-2xl">Yangiliklar (admin)</h1><a class="btn" href="/admin/yangiliklar/yangi">Yangi yangilik</a></div>
<div class="card overflow-x-auto"><table class="w-full text-sm"><tr><th>Sarlavha</th><th>Tur</th><th>Muddat</th><th>Holat</th><th><span class="sr-only">Amallar</span></th></tr>
@forelse($news as $item)<tr><td>{{ $item->title }}@if($item->pinned) <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">Yopishtirilgan</span>@endif</td>
<td>{{ config('uniscience.news_types.'.$item->type, $item->type) }}</td><td>{{ $item->deadline?->format('Y-m-d') ?? '—' }}</td>
<td>{{ $item->archived_at ? 'Arxivlangan ('.$item->archived_at->format('Y-m-d').')' : 'Faol' }}</td>
<td class="whitespace-nowrap"><a class="underline" href="/admin/yangiliklar/{{ $item->id }}/tahrir">Tahrirlash</a>
<form method="post" action="/admin/yangiliklar/{{ $item->id }}" class="inline" onsubmit="return confirm('Yangilik va ilovasi o‘chirilsinmi?')">@csrf @method('DELETE')<button class="ml-2 text-red-700 underline">O‘chirish</button></form></td></tr>
@empty<tr><td colspan="5" class="text-slate-500">Yangiliklar hali yo‘q.</td></tr>@endforelse</table>{{ $news->links() }}</div>
@endsection
