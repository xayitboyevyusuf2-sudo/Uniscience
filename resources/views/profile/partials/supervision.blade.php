@if($sentRequests->isNotEmpty())
<div class="card"><h2 class="font-serif text-lg mb-2">Ilmiy rahbarlik so‘rovlari</h2>
@foreach($sentRequests as $request)<div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 py-2 text-sm">
<span>{{ $request->slot?->slot_date?->format('Y-m-d') }} {{ substr((string) $request->slot?->start_time, 0, 5) }} — {{ $request->slot?->mentor?->fullName() ?: $request->slot?->mentor?->name }}</span>
<span>@include('partials.mentor-status',['status'=>$request->status])@if($request->response_note)<span class="text-slate-500"> · {{ $request->response_note }}</span>@endif</span>
@if(auth()->id()===$u->id && in_array($request->status,['pending','accepted'],true))<form method="post" action="/matching/sorov/{{ $request->id }}/bekor" onsubmit="return confirm('So‘rov bekor qilinsinmi?')">@csrf<button class="text-red-700 underline">Bekor qilish</button></form>@endif
</div>@endforeach</div>
@endif
@if($scheduledMeetings->isNotEmpty())
<div class="card"><h2 class="font-serif text-lg mb-2">Rejalashtirilgan uchrashuvlar</h2>
@foreach($scheduledMeetings as $meeting)<div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 py-2 text-sm">
<span><b>Rejalashtirilgan</b> · {{ $meeting->slot->slot_date->format('Y-m-d') }} {{ substr($meeting->slot->start_time, 0, 5) }}–{{ substr($meeting->slot->end_time, 0, 5) }} · {{ $meeting->slot->location }}</span>
<span>@if($u->isMentor()){{ $meeting->student->fullName() ?: $meeting->student->name }}@else{{ $meeting->slot->mentor->fullName() ?: $meeting->slot->mentor->name }}@endif</span>
</div>@endforeach</div>
@endif
