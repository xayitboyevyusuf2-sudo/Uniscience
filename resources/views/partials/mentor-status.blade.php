@php($labels = ['pending' => 'Kutilmoqda', 'accepted' => 'Qabul qilindi', 'rejected' => 'Rad etildi', 'cancelled' => 'Bekor qilindi'])
@php($colors = ['pending' => 'bg-amber-100 text-amber-800', 'accepted' => 'bg-emerald-100 text-emerald-800', 'rejected' => 'bg-red-100 text-red-800', 'cancelled' => 'bg-slate-100 text-slate-600'])
<span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $colors[$status] ?? 'bg-slate-100' }}">{{ $labels[$status] ?? $status }}</span>
