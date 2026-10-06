@extends('layouts.app')
@section('content')
<div class="card"><h1 class="font-serif text-2xl">{{ $article->title }}</h1>
<p class="text-slate-600">{{ config('uniscience.article_types.'.$article->type, $article->type) }} · {{ $article->journal_name }} · ISSN {{ $article->issn ?: '—' }} · {{ $article->published_at->format('Y-m-d') }}</p>
<p class="mt-2"><b>Holat:</b> @include('partials.status',['s'=>$article->status]) {{ $article->reason }}</p>
<div class="mt-4 grid gap-4 md:grid-cols-3"><section><h2 class="font-semibold">Annotatsiya (o‘zbek)</h2><p class="whitespace-pre-line">{{ $article->annotation_uz ?? $article->abstract }}</p><p class="mt-2 text-sm"><b>Kalit so‘zlar:</b> {{ $article->keywords_uz }}</p></section>
<section><h2 class="font-semibold">Аннотация (русский)</h2><p class="whitespace-pre-line">{{ $article->annotation_ru }}</p><p class="mt-2 text-sm"><b>Ключевые слова:</b> {{ $article->keywords_ru }}</p></section>
<section><h2 class="font-semibold">Abstract (English)</h2><p class="whitespace-pre-line">{{ $article->annotation_en }}</p><p class="mt-2 text-sm"><b>Keywords:</b> {{ $article->keywords_en }}</p></section></div>
<div class="mt-4 overflow-x-auto"><h2 class="font-serif text-lg">Mualliflar</h2><table class="w-full text-sm"><tr><th>F.I.Sh.</th><th>Pozitsiya</th><th>Yuboruvchi</th></tr>@foreach($article->authors as $author)<tr><td>{{ $author->full_name }}</td><td>{{ config('uniscience.position_labels.'.$author->position, $author->position) }}</td><td>{{ $author->is_submitter ? 'Ha' : '—' }}</td></tr>@endforeach</table></div>
@if($row)<h2 class="font-serif text-lg mt-4">Ball hisobi</h2>
<p>W_soha {{ $row['f'][0] }} × W_daraja {{ $row['f'][1] }} × W_muallif {{ $row['f'][2] }} × W_sana {{ $row['f'][3] }} = <b>{{ number_format($row['pts'],2) }}</b>
@unless($row['counted'])<br><span class="text-amber-700">Yillik chegaradan oshgan — hisobga olinmadi</span>@endunless</p>@endif</div>
@endsection
