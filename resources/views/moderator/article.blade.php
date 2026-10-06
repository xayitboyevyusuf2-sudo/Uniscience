@extends('layouts.app')
@section('content')
<div class="mb-4"><a class="text-sm underline" href="/moderator/navbat">← Moderator navbatiga qaytish</a></div>
<section class="card">
<div class="flex flex-wrap items-start justify-between gap-3"><div><h1 class="font-serif text-2xl">{{ $article->title }}</h1><p class="mt-1 text-sm text-slate-600">{{ $article->user->fullName() }} · {{ $article->user->faculty }} · {{ config('uniscience.article_types.'.$article->type.'.label', $article->type) }}</p><p class="text-sm text-slate-600">{{ $article->journal_name }} · ISSN {{ $article->issn ?: '—' }} · {{ $article->published_at->format('Y-m-d') }}</p></div><a class="btn" href="/maqola/{{ $article->id }}/pdf">Tasdiqlangan maqola PDF</a></div>
<p class="mt-2">@include('partials.status',['s'=>$article->status]) {{ $article->reason }}</p>
</section>
<section class="card"><h2 class="font-serif text-xl">FR-23 tekshiruv ro‘yxati</h2>
<div class="mt-3 rounded-md border border-slate-200 p-3"><h3 class="font-semibold">Jurnal ro‘yxati va nashr sanasi</h3>
@if($isJournalType)
@if($journalCandidate)<p>{{ $journalCandidate->name }} · {{ $journalCandidate->tier }} · {{ $journalCandidate->issn ?: 'ISSN yo‘q' }}</p><p class="text-sm text-slate-600">Ro‘yxat davri: {{ $journalCandidate->listed_from->format('Y-m-d') }} — {{ $journalCandidate->listed_to?->format('Y-m-d') ?? 'hozirgacha' }} · Nashr sanasi: {{ $article->published_at->format('Y-m-d') }}</p><p class="font-semibold {{ $journalListedAtPublication ? 'text-emerald-700' : 'text-red-700' }}">{{ $journalListedAtPublication ? 'Nashr sanasida ro‘yxatda bo‘lgan.' : 'Nashr sanasida ro‘yxatda bo‘lmagan.' }}</p>
@else<p class="text-amber-700">Jurnal ro‘yxatda topilmadi (OAK ro‘yxati).</p>@endif
@if($verification)<p class="mt-1 text-sm text-slate-600">Avtomatik natija: {{ $verification[0] }} — {{ $verification[1] }}</p>@endif
@else<p class="text-slate-600">OAK jurnal ro‘yxati tekshiruvi ushbu nashr turiga tatbiq etilmaydi.</p>@endif
</div>
<div class="mt-3 rounded-md border border-slate-200 p-3"><h3 class="font-semibold">PDF sarlavhasi va forma</h3><p class="text-sm text-slate-600">Forma sarlavhasi: {{ $article->title }}</p><a class="text-sm underline" href="/maqola/{{ $article->id }}/pdf">PDF faylini ochish</a></div>
<div class="mt-3 rounded-md border border-slate-200 p-3"><h3 class="font-semibold">Mualliflik pozitsiyalari</h3><ul class="mt-2 list-inside list-disc text-sm">@foreach($article->authors as $author)<li>{{ $author->full_name }} · {{ config('uniscience.position_labels.'.$author->position, $author->position) }}{{ $author->is_submitter ? ' · yuboruvchi' : '' }}</li>@endforeach</ul></div>
@if(($articleType['family'] ?? null)==='conference')<div class="mt-3 rounded-md border border-slate-200 p-3"><h3 class="font-semibold">Konferensiya sertifikati</h3>@if($article->conferenceCertificate)<p class="text-sm">Holati: {{ $article->conferenceCertificate->status }}</p><a class="text-sm underline" href="/moderator/maqola/{{ $article->id }}/sertifikat/pdf">Sertifikat PDF’ni ochish</a>
@if($article->conferenceCertificate->status==='pending')<form method="post" action="/moderator/maqola/{{ $article->id }}/sertifikat" class="mt-3 flex flex-wrap items-end gap-3">@csrf<input class="i max-w-md" name="note" placeholder="Rad etish sababi"><button class="btn" name="decision" value="verify">Tasdiqlash</button><button class="btn bg-red-700!" name="decision" value="reject">Rad etish</button></form>@endif
@else<p class="text-red-700">Sertifikat fayli mavjud emas.</p>@endif</div>@endif
</section>
<section class="card"><h2 class="font-serif text-xl">Qaror</h2>
@if(($articleType['family'] ?? null)==='conference' && $article->conferenceCertificate?->status!=='verified')<p class="mb-3 font-semibold text-amber-700">Sertifikat tasdiqlanmaguncha maqolani tasdiqlab bo‘lmaydi.</p>@endif
<form method="post" action="/admin/qaror/{{ $article->id }}" class="space-y-3">@csrf
@if($isJournalType && ! $article->journal_id)<label for="journal_pick">Mos OAK jurnal</label><input class="i" id="journal_pick" name="journal_pick" list="review-journals" value="{{ old('journal_pick') }}" placeholder="Jurnal nomi yoki ID’sini kiriting"><datalist id="review-journals">@foreach($journalSuggestions as $journal)<option value="{{ $journal->id }}" label="{{ $journal->name }} · {{ $journal->issn ?: 'ISSN yo‘q' }} · {{ $journal->tier }}">@endforeach</datalist>@endif
@if(($articleType['family'] ?? null)!=='journal' && ! $article->field)<label for="field">Nashr sohasi</label><select class="i" id="field" name="field" required><option value="">Sohani tanlang</option>@foreach($fields as $field)<option value="{{ $field }}">{{ $field }}</option>@endforeach</select>@endif
<label class="font-normal"><input type="checkbox" name="pdf_title_matches" value="1"> PDF sarlavhasi forma bilan mos</label>
<label class="font-normal"><input type="checkbox" name="author_positions_checked" value="1"> Mualliflik pozitsiyasi tekshirildi</label>
<label for="note">Izoh (rad etish uchun majburiy)</label><textarea class="i" id="note" name="note" rows="3" maxlength="500"></textarea>
<button class="btn" name="decision" value="approve" @disabled(($articleType['family'] ?? null)==='conference' && $article->conferenceCertificate?->status!=='verified')>Tasdiqlash</button> <button class="btn bg-red-700!" name="decision" value="reject">Rad etish</button></form>
</section>
<section class="card"><h2 class="font-serif text-xl">Tekshiruv tarixi</h2>
@forelse($reviewHistory as $entry)<div class="border-t border-slate-200 py-3"><b>{{ $entry->reviewer_name ?? 'Tizim' }}</b> · {{ $entry->decision }} · {{ $entry->created_at }}@if($entry->note)<p class="whitespace-pre-line text-sm text-slate-600">{{ $entry->note }}</p>@endif</div>
@empty<p class="mt-3 text-sm text-slate-500">Hali tekshiruv tarixi yo‘q.</p>
@endforelse
</section>
@endsection
