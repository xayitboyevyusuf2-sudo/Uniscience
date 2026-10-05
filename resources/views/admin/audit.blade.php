@extends('layouts.app')
@section('content')
<section class="card">
<div class="flex flex-wrap items-center justify-between gap-3"><h1 class="font-serif text-2xl">Admin audit jurnali</h1><a class="underline" href="/admin">Boshqaruvga qaytish</a></div>
<form method="get" action="/admin/audit" class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
<label class="mt-0">Foydalanuvchi<select class="i mt-1" name="user_id"><option value="">Barchasi</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected((string) ($filters['user_id'] ?? '') === (string) $user->id)>{{ $user->name }} — {{ $user->email }}</option>@endforeach</select></label>
<label class="mt-0">Amal<input class="i mt-1" name="action" value="{{ $filters['action'] ?? '' }}" maxlength="190"></label>
<label class="mt-0">Sanadan<input class="i mt-1" type="date" name="from" value="{{ $filters['from'] ?? '' }}"></label>
<label class="mt-0">Sanagacha<input class="i mt-1" type="date" name="to" value="{{ $filters['to'] ?? '' }}"></label>
<div class="flex gap-2"><button class="btn">Filtrlash</button><a class="self-center text-sm underline" href="/admin/audit">Tozalash</a></div>
</form>
<div class="mt-4 overflow-x-auto"><table class="w-full min-w-[850px] text-sm"><thead><tr><th>Vaqt</th><th>Foydalanuvchi</th><th>Amal</th><th>Mavzu</th><th>Oldingi qiymat</th><th>Yangi qiymat</th><th>IP</th></tr></thead><tbody>
@forelse($logs as $entry)<tr><td>{{ $entry->created_at }}</td><td>{{ $entry->actor_name ?? 'O‘chirilgan foydalanuvchi' }}<br><span class="text-slate-500">{{ $entry->actor_email ?? '' }}</span></td><td>{{ $entry->action }}</td><td>{{ $entry->subject_type ? class_basename($entry->subject_type).' #'.$entry->subject_id : '—' }}</td><td><code>{{ $entry->old ? json_encode(json_decode($entry->old, true), JSON_UNESCAPED_UNICODE) : '—' }}</code></td><td><code>{{ $entry->new ? json_encode(json_decode($entry->new, true), JSON_UNESCAPED_UNICODE) : '—' }}</code></td><td>{{ $entry->ip ?? '—' }}</td></tr>
@empty<tr><td colspan="7" class="py-5 text-center text-slate-500">Audit yozuvlari topilmadi.</td></tr>
@endforelse
</tbody></table></div>
{{ $logs->links() }}
</section>
@endsection
