@extends('layouts.app')
@section('content')
<h1 class="mb-4 font-serif text-2xl">Moderator navbati</h1>
<section class="card"><h2 class="font-serif text-xl">Qo‘lda ko‘rib chiqiladigan maqolalar</h2>
@forelse($articles as $article)<div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 py-3"><div><b>{{ $article->title }}</b><p class="text-sm text-slate-600">{{ $article->user->fullName() }} · {{ $article->user->faculty }} · {{ config('uniscience.article_types.'.$article->type.'.label', $article->type) }}</p><p class="text-sm text-slate-500">{{ $article->reason }}</p></div><a class="btn" href="/moderator/maqola/{{ $article->id }}">Ko‘rib chiqish</a></div>
@empty<p class="border-t border-slate-200 py-3 text-slate-500">Maqolalar navbati bo‘sh.</p>
@endforelse</section>
<section class="card"><h2 class="font-serif text-xl">Tasdiqlanmagan konferensiya sertifikatlari</h2>
@forelse($certificates as $certificate)<div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 py-3"><div><b>{{ $certificate->article->title }}</b><p class="text-sm text-slate-600">{{ $certificate->article->user->fullName() }} · {{ $certificate->article->user->faculty }}</p></div><a class="btn" href="/moderator/maqola/{{ $certificate->article_id }}">Tekshirish</a></div>
@empty<p class="border-t border-slate-200 py-3 text-slate-500">Sertifikatlar navbati bo‘sh.</p>
@endforelse</section>
<section class="card"><h2 class="font-serif text-xl">Ochiq jurnal arizalari</h2>
@forelse($journalRequests as $journalRequest)<div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 py-3"><div><b>{{ $journalRequest->journal_name }}</b><p class="text-sm text-slate-600">{{ $journalRequest->article->title }} · {{ $journalRequest->article->user->faculty }} · ISSN {{ $journalRequest->issn ?: '—' }}</p></div><a class="btn" href="/moderator/maqola/{{ $journalRequest->article_id }}">Maqolani ko‘rish</a></div>
@empty<p class="border-t border-slate-200 py-3 text-slate-500">Jurnal arizalari navbati bo‘sh.</p>
@endforelse</section>
@endsection
