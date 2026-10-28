<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Panda — Disease Scan Report</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1f2937; }
        h1 { color: #047857; font-size: 22px; margin: 0 0 4px; }
        h2 { color: #065f46; font-size: 14px; margin: 18px 0 6px; border-bottom: 1px solid #e5e7eb; padding-bottom: 3px; }
        .meta { color: #6b7280; font-size: 10px; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        th, td { padding: 4px 6px; border-bottom: 1px solid #e5e7eb; text-align: left; vertical-align: top; }
        th { background: #f0fdf4; font-weight: 600; color: #065f46; }
        .diagnosis-box { border: 1px solid #a7f3d0; background: #ecfdf5; border-radius: 8px; padding: 12px 14px; margin: 8px 0 4px; }
        .diagnosis-name { font-size: 16px; font-weight: 700; color: #065f46; }
        .confidence { font-size: 12px; color: #047857; }
        .disclaimer { border: 1px solid #fcd34d; background: #fffbeb; color: #92400e; border-radius: 8px; padding: 10px 12px; margin: 14px 0; font-size: 10px; }
        .footer { margin-top: 18px; color: #9ca3af; font-size: 9px; text-align: center; }
    </style>
</head>
<body>
    <h1>Panda — Disease Scan Report</h1>
    <div class="meta">
        Farm: {{ $detection->tenant?->name ?? '—' }} ({{ $detection->tenant?->county ?? '—' }})<br>
        Crop: <strong>{{ $crop?->name_en ?? '—' }}</strong> ({{ $crop?->name_sw ?? '' }})<br>
        Scan date: {{ optional($detection->captured_at)->toDateTimeString() ?? '—' }} &middot; Provider: {{ $providerLabel }}<br>
        Generated: {{ now()->toDateTimeString() }}
    </div>

    <h2>Diagnosis</h2>
    <div class="diagnosis-box">
        <div class="diagnosis-name">{{ $detection->top_diagnosis ?? 'No diagnosis recorded' }}</div>
        @if ($confidencePct !== null)
            <div class="confidence">{{ $confidencePct }}% confidence</div>
        @else
            <div class="confidence">No confidence reported</div>
        @endif
    </div>

    <h2>Recommended treatments</h2>
    @if (empty($treatments))
        <p>No treatments recorded for this scan.</p>
    @else
        <table>
            <thead>
                <tr><th style="width: 24px;">#</th><th>Treatment</th><th>PCPB product</th><th>Application notes</th></tr>
            </thead>
            <tbody>
                @foreach ($treatments as $i => $t)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $t['generic'] ?? '—' }}</td>
                        <td>{{ $t['pcpb'] ?? '—' }}</td>
                        <td>{{ $t['application_notes'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="disclaimer">
        {{ $disclaimer }}
    </div>

    <div class="footer">
        Panda — horticulture farming OS &middot; panda.shira.farm &middot; Generated {{ now()->toDateTimeString() }}
    </div>
</body>
</html>
