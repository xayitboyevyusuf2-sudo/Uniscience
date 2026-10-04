@extends('layouts.app')
@section('content')
<div class="card"><h1 class="font-serif text-2xl">{{ $article->title }}</h1>
<p class="text-slate-600">{{ $article->journal_name }} · ISSN {{ $article->issn }} · {{ $article->published_at->format('Y-m-d') }}</p>
<p class="mt-2"><b>Holat:</b> @include('partials.status',['s'=>$article->status]) {{ $article->reason }}</p>
@if($row)<h2 class="font-serif text-lg mt-4">Ball hisobi</h2>
<p>W_soha {{ $row['f'][0] }} × W_daraja {{ $row['f'][1] }} × W_muallif {{ $row['f'][2] }} × W_sana {{ $row['f'][3] }} = <b>{{ number_format($row['pts'],2) }}</b>
@unless($row['counted'])<br><span class="text-amber-700">Yillik chegaradan oshgan — hisobga olinmadi</span>@endunless</p>@endif</div>
@endsection
