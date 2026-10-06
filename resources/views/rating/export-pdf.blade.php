<!DOCTYPE html>
<html lang="uz"><head><meta charset="utf-8">
<style>
body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; }
h1 { font-size: 16px; }
table { width: 100%; border-collapse: collapse; }
th, td { border: 1px solid #94a3b8; padding: 4px 6px; text-align: left; }
th { background: #e2e8f0; }
</style></head><body>
<h1>Reyting — top {{ $ratings->count() }}</h1>
<p>Sana: {{ now()->format('Y-m-d') }}</p>
<table><tr><th>#</th><th>F.I.Sh.</th><th>Toifa</th><th>Fakultet</th><th>Ball</th></tr>
@foreach($ratings as $rating)<tr><td>{{ $loop->iteration }}</td><td>{{ $rating->user?->fullName() ?: $rating->user?->name }}</td><td>{{ $rating->category }}</td><td>{{ $rating->user?->faculty }}</td><td>{{ number_format((float) $rating->score, 2) }}</td></tr>@endforeach
</table></body></html>
