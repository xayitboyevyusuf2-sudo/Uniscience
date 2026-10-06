@php($items = [
['/admin', 'Navbat', 'admin'],
['/admin/foydalanuvchilar', 'Foydalanuvchilar', 'admin/foydalanuvchi*'],
['/admin/jurnallar', 'Jurnallar', 'admin/jurnallar*'],
['/admin/yoriqnoma', 'Kontent: hujjatlar', 'admin/yoriqnoma*'],
['/admin/videolar', 'Kontent: videolar', 'admin/videolar*'],
['/admin/yangiliklar', 'Kontent: yangiliklar', 'admin/yangiliklar*'],
['/admin/jurnal-arizalari', 'Jurnal arizalari', 'admin/jurnal-arizalari*'],
['/admin/ochirish-sorovlari', 'O‘chirish so‘rovlari', 'admin/ochirish-sorovlari*'],
['/admin/audit', 'Audit', 'admin/audit'],
])
<nav class="card !mb-0 flex flex-col gap-1 p-3 text-sm" aria-label="Admin menyusi">
@foreach($items as [$url, $label, $pattern])<a class="rounded-md px-3 py-2 {{ request()->is($pattern) ? 'bg-lapis text-white font-semibold' : 'hover:bg-slate-100' }}" href="{{ $url }}">{{ $label }}</a>@endforeach
</nav>
