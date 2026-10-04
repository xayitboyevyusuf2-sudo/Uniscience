@extends('layouts.app')
@section('content')
<h1 class="font-serif text-2xl mb-3">Ochiq ilmiy baza</h1>
<form class="card grid sm:grid-cols-4 gap-3"><input class="i sm:col-span-2" name="q" placeholder="Mavzu yoki muallif" value="{{ request('q') }}">
<select class="i" name="field"><option value="">Barcha sohalar</option>@foreach(config('uniscience.fields') as $f)<option @selected(request('field')===$f)>{{ $f }}</option>@endforeach</select>
<select class="i" name="tier"><option value="">Barcha darajalar</option>@foreach(array_keys(config('uniscience.tiers')) as $t)<option @selected(request('tier')===$t)>{{ $t }}</option>@endforeach</select>
<input class="i" type="number" name="year" placeholder="Yil" value="{{ request('year') }}"><button class="btn">Qidirish</button></form>
@forelse($items as $a)<div class="card"><h2 class="font-serif text-lg">{{ $a->title }}</h2>
<p class="text-sm text-slate-600">{{ $a->user->name }}{{ $a->coauthors ? ', '.$a->coauthors : '' }} · {{ $a->journal->name }} · {{ $a->published_at->year }} </p><div class="mt-2 flex gap-2 text-xs"><span class="rounded-full bg-teal-100 text-teal-800 px-2.5 py-0.5 font-semibold">{{ \Illuminate\Support\Str::limit($a->journal->field, 40) }}</span><span class="rounded-full bg-blue-100 text-lapis px-2.5 py-0.5 font-semibold">Daraja {{ $a->journal->tier }}</span></div>
<p class="mt-2">{{ $a->abstract }}</p><a class="text-lapis underline text-sm" href="{{ $a->url }}" rel="noopener">Jurnaldagi havola</a></div>
@empty<div class="card">Hech narsa topilmadi.</div>@endforelse
{{ $items->links() }}
@endsection
