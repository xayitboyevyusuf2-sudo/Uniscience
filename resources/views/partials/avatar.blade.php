@php $ini = mb_strtoupper(mb_substr($u->first_name ?? $u->name, 0, 1).mb_substr($u->last_name ?? '', 0, 1)); @endphp
@if($u->photo_path)<img src="/foto/{{ $u->id }}?v={{ $u->updated_at?->timestamp }}" alt="" class="{{ $size ?? 'w-20 h-20' }} rounded-full object-cover border border-slate-200">
@else<span class="{{ $size ?? 'w-20 h-20' }} rounded-full bg-lapis text-white inline-flex items-center justify-center font-serif text-2xl">{{ $ini }}</span>@endif
