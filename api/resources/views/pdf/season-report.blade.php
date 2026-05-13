<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Panda — Season Report</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1f2937; }
        h1 { color: #047857; font-size: 22px; margin: 0 0 4px; }
        h2 { color: #065f46; font-size: 14px; margin: 18px 0 6px; border-bottom: 1px solid #e5e7eb; padding-bottom: 3px; }
        .meta { color: #6b7280; font-size: 10px; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        th, td { padding: 4px 6px; border-bottom: 1px solid #e5e7eb; text-align: left; vertical-align: top; }
        th { background: #f0fdf4; font-weight: 600; color: #065f46; }
        .right { text-align: right; }
        .totals { margin-top: 6px; }
        .totals td { font-weight: 600; }
        .pill { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 9px; }
        .pill-pending { background: #f3f4f6; color: #4b5563; }
        .pill-done { background: #d1fae5; color: #065f46; }
        .pill-overdue { background: #fee2e2; color: #991b1b; }
        .pill-skipped { background: #fef3c7; color: #92400e; }
        .footer { margin-top: 18px; color: #9ca3af; font-size: 9px; text-align: center; }
    </style>
</head>
<body>
    <h1>Panda — Season Report</h1>
    <div class="meta">
        Farm: {{ $season->tenant?->name ?? '—' }} ({{ $season->tenant?->county ?? '—' }})<br>
        Crop: <strong>{{ $season->crop?->name_en ?? '—' }}</strong> ({{ $season->crop?->name_sw ?? '' }})<br>
        Acreage: {{ $season->acreage }} acres &middot;
        Planting: {{ optional($season->planting_date)->toDateString() ?? '—' }} &middot;
        Irrigation: {{ $season->irrigation_type }}<br>
        Generated: {{ now()->toDateTimeString() }}
    </div>

    <h2>Summary</h2>
    <table class="totals">
        <tr><td>Total cost</td><td class="right">KES {{ number_format($totals['cost_total_kes'], 2) }}</td></tr>
        <tr><td>Total harvest</td><td class="right">{{ number_format($totals['harvest_total_kg'], 2) }} kg</td></tr>
        <tr><td>Sold</td><td class="right">{{ number_format($totals['sold_kg'], 2) }} kg</td></tr>
        <tr><td>Revenue</td><td class="right">KES {{ number_format($totals['revenue_kes'], 2) }}</td></tr>
        <tr><td>Profit</td><td class="right">KES {{ number_format($totals['profit_kes'], 2) }}</td></tr>
    </table>

    <h2>Timeline ({{ $activities->count() }} activities)</h2>
    @if ($activities->isEmpty())
        <p>No activities yet.</p>
    @else
        <table>
            <thead>
                <tr><th>Date</th><th>Activity</th><th>Phase</th><th>Status</th></tr>
            </thead>
            <tbody>
                @foreach ($activities as $a)
                    <tr>
                        <td>{{ optional($a->ideal_date)->toDateString() }}</td>
                        <td>{{ $a->description_en }}</td>
                        <td>{{ $a->phase }}</td>
                        <td><span class="pill pill-{{ $a->status }}">{{ $a->status }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Inputs ({{ $inputs->count() }} items)</h2>
    @if ($inputs->isEmpty())
        <p>No inputs.</p>
    @else
        <table>
            <thead>
                <tr><th>Product</th><th>Type</th><th class="right">Qty</th><th>Unit</th><th class="right">Est. KES</th></tr>
            </thead>
            <tbody>
                @foreach ($inputs as $i)
                    <tr>
                        <td>{{ $i->product_name }}</td>
                        <td>{{ $i->input_type }}</td>
                        <td class="right">{{ number_format((float) $i->quantity_scaled, 2) }}</td>
                        <td>{{ $i->unit }}</td>
                        <td class="right">{{ $i->cost_estimate_kes !== null ? number_format((float) $i->cost_estimate_kes, 2) : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Costs ({{ $costs->count() }} entries)</h2>
    @if ($costs->isEmpty())
        <p>No costs logged.</p>
    @else
        <table>
            <thead>
                <tr><th>Date</th><th>Category</th><th>Description</th><th>Supplier</th><th class="right">KES</th></tr>
            </thead>
            <tbody>
                @foreach ($costs as $c)
                    <tr>
                        <td>{{ optional($c->incurred_at)->toDateString() }}</td>
                        <td>{{ $c->category }}</td>
                        <td>{{ $c->description }}</td>
                        <td>{{ $c->supplier_name ?? '—' }}</td>
                        <td class="right">{{ number_format((float) $c->amount_kes, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Harvests ({{ $harvests->count() }} logs)</h2>
    @if ($harvests->isEmpty())
        <p>No harvests yet.</p>
    @else
        <table>
            <thead>
                <tr><th>Date</th><th class="right">Picked (kg)</th><th class="right">Sold (kg)</th><th class="right">Price/kg</th><th class="right">Revenue</th><th>Buyer</th></tr>
            </thead>
            <tbody>
                @foreach ($harvests as $h)
                    <tr>
                        <td>{{ optional($h->harvested_at)->toDateString() }}</td>
                        <td class="right">{{ number_format((float) $h->quantity_kg, 2) }}</td>
                        <td class="right">{{ number_format((float) $h->sold_quantity_kg, 2) }}</td>
                        <td class="right">{{ $h->unit_price_kes !== null ? number_format((float) $h->unit_price_kes, 2) : '—' }}</td>
                        <td class="right">{{ number_format($h->revenueKes(), 2) }}</td>
                        <td>{{ $h->buyer_name ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        Panda — JAICA SHEP PLUS-inspired farm planning. Inputs and timing are recommendations, not guarantees.
    </div>
</body>
</html>
