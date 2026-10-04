@extends('layouts.app')
@section('content')
<h1 class="font-serif text-2xl mb-3">Reyting</h1>
@auth<p class="mb-3 text-sm"><a class="underline" href="/reyting">Universitet</a> · <a class="underline" href="/reyting?scope=faculty">Fakultet ichida</a></p>@endauth
<div class="card overflow-x-auto"><table class="w-full"><tr><th>#</th><th>Talaba</th><th>Fakultet</th><th>Ball</th></tr>
@foreach($rows as $i=>$x)<tr class="{{ $x['u']->id===auth()->id() ? 'bg-teal-50 font-semibold' : '' }}"><td><span class="inline-flex w-7 h-7 items-center justify-center rounded-full text-sm font-bold {{ [0=>'bg-amber-300',1=>'bg-slate-300',2=>'bg-orange-300'][$i] ?? 'bg-slate-100' }}">{{ $i+1 }}</span></td><td>@auth<a class="text-lapis underline" href="/talaba/{{ $x['u']->id }}">{{ $x['u']->name }}</a>@else{{ $x['u']->name }}@endauth</td><td>{{ $x['u']->faculty }}</td><td>{{ number_format($x['s'],2) }}</td></tr>@endforeach</table></div>
@endsection
