{{-- Reusable skeleton loader block (FR design): animate-pulse placeholder for lists, charts, dropdowns. --}}
<div class="animate-pulse space-y-2" role="status" aria-label="Yuklanmoqda">
@for($i = 0; $i < ($rows ?? 3); $i++)<div class="h-{{ $height ?? 4 }} rounded bg-slate-200"></div>@endfor
<span class="sr-only">Yuklanmoqda…</span>
</div>
