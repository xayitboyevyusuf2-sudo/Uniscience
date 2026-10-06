@if($mentorSlots->isNotEmpty())
<div class="card"><h2 class="font-serif text-lg mb-2">Bo‘sh vaqt</h2>
@foreach($mentorSlots as $slot)<div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 py-2 text-sm">
<span>{{ $slot->slot_date->format('Y-m-d') }} · {{ substr($slot->start_time, 0, 5) }}–{{ substr($slot->end_time, 0, 5) }} · {{ $slot->location }}@if($slot->note) · {{ $slot->note }}@endif</span>
<span class="text-slate-500">{{ $slot->requests_count }} ta so‘rov</span></div>@endforeach</div>
@endif
@if($incomingRequests->isNotEmpty())
<div class="card"><h2 class="font-serif text-lg mb-2">Kelgan so‘rovlar</h2>
@foreach($incomingRequests as $request)<div class="border-t border-slate-200 py-2 text-sm">
<b>{{ $request->student->fullName() ?: $request->student->name }}</b> · reyting {{ number_format((float) ($request->student->rating?->score ?? 0), 2) }} · {{ $request->slot->slot_date->format('Y-m-d') }} {{ substr($request->slot->start_time, 0, 5) }}
<p class="text-slate-600">{{ $request->message }}</p>
<form method="post" action="/matching/sorovlar/{{ $request->id }}/javob" class="mt-2 flex flex-wrap items-center gap-2">@csrf
<input class="i max-w-md" name="response_note" placeholder="Izoh (ixtiyoriy)" maxlength="500">
<button class="btn" name="decision" value="accept">Qabul</button>
<button class="btn bg-red-700!" name="decision" value="reject">Rad</button></form></div>
@endforeach</div>
@endif
