import { useMemo } from 'react';

export interface CumulativePoint {
  /** Date as ISO yyyy-mm-dd or any short label. */
  label: string;
  cumulativeCost: number;
  cumulativeRevenue: number;
}

export interface CumulativeCostRevenueChartProps {
  points: CumulativePoint[];
  height?: number;
  className?: string;
  /** Optional formatter for axis tick labels. */
  formatValue?: (n: number) => string;
}

/*
 * Two-line chart: cumulative cost (red-ish) + cumulative revenue (green).
 * Profit is implied by the gap between the lines — when revenue crosses
 * cost upward, the chart visually crosses too. Hand-rolled SVG.
 *
 * Domain mirrors PriceLineChart for visual consistency: 800x200 by default,
 * 50px left padding for axis labels, 6 sparse x-tick labels.
 *
 * The data prop is *cumulative*, not per-day. The Season detail page is
 * responsible for computing the running totals from /costs and /harvests
 * endpoints, since those return per-event rows.
 */
export function CumulativeCostRevenueChart({
  points,
  height = 220,
  className,
  formatValue,
}: CumulativeCostRevenueChartProps) {
  const W = 800;
  const PAD = { l: 60, r: 12, t: 22, b: 28 };
  const fmt = formatValue ?? ((n: number) => Math.round(n).toLocaleString('en-KE'));

  const { max, xs, costYs, revYs } = useMemo(() => {
    const maxCost = points.length ? Math.max(...points.map((p) => p.cumulativeCost)) : 0;
    const maxRev = points.length ? Math.max(...points.map((p) => p.cumulativeRevenue)) : 0;
    const maxRaw = Math.max(maxCost, maxRev, 1);
    // Round up to a "nice" axis max (next power-of-10 / 5k / 10k step).
    const niceMax = niceCeil(maxRaw);
    const innerW = W - PAD.l - PAD.r;
    const innerH = height - PAD.t - PAD.b;
    const xs = points.map((_, i) =>
      points.length === 1 ? PAD.l + innerW / 2 : PAD.l + (i / (points.length - 1)) * innerW,
    );
    const costYs = points.map((p) => PAD.t + innerH - (p.cumulativeCost / niceMax) * innerH);
    const revYs = points.map((p) => PAD.t + innerH - (p.cumulativeRevenue / niceMax) * innerH);
    return { max: niceMax, xs, costYs, revYs };
  }, [points, height]);

  if (points.length === 0) {
    return (
      <div className={className}>
        <p className="py-4 text-center text-sm text-gray-500">No data to chart yet.</p>
      </div>
    );
  }

  const costPath = xs.map((x, i) => `${i === 0 ? 'M' : 'L'} ${x ?? 0} ${costYs[i] ?? 0}`).join(' ');
  const revPath = xs.map((x, i) => `${i === 0 ? 'M' : 'L'} ${x ?? 0} ${revYs[i] ?? 0}`).join(' ');
  const yTicks = [0, max / 2, max];
  const lastIdx = points.length - 1;

  return (
    <div className={className}>
      <svg viewBox={`0 0 ${W} ${height}`} className="w-full" role="img" aria-label="Cumulative cost vs revenue">
        {/* y-axis grid + labels */}
        {yTicks.map((t, i) => {
          const y = PAD.t + (height - PAD.t - PAD.b) * (1 - t / max);
          return (
            <g key={i}>
              <line x1={PAD.l} x2={W - PAD.r} y1={y} y2={y} stroke="#e5e7eb" strokeDasharray="2 3" />
              <text x={PAD.l - 8} y={y + 4} fontSize="11" textAnchor="end" fill="#6b7280">
                {fmt(t)}
              </text>
            </g>
          );
        })}

        {/* Revenue line (savanna-sun) */}
        <path d={revPath} fill="none" stroke="#E07A2F" strokeWidth={2.25} strokeLinejoin="round" />

        {/* Cost line (highland-green) */}
        <path d={costPath} fill="none" stroke="#1B4332" strokeWidth={2.25} strokeLinejoin="round" />

        {/* x-axis labels (sparse — first, last, ~4 in between) */}
        {points.map((p, i) => {
          const showLabel = i === 0 || i === lastIdx || i % Math.ceil(points.length / 6) === 0;
          if (!showLabel) return null;
          return (
            <text key={i} x={xs[i]} y={height - 8} fontSize="10" textAnchor="middle" fill="#6b7280">
              {p.label}
            </text>
          );
        })}

        {/* Final-point dots + value labels */}
        <circle cx={xs[lastIdx]} cy={costYs[lastIdx]} r={3.5} fill="#1B4332" />
        <circle cx={xs[lastIdx]} cy={revYs[lastIdx]} r={3.5} fill="#E07A2F" />
      </svg>

      {/* Legend */}
      <div className="mt-1 flex justify-center gap-5 text-xs">
        <span className="flex items-center gap-1.5 text-rich-earth">
          <span className="inline-block h-2 w-3 rounded-sm" style={{ backgroundColor: '#1B4332' }} />
          Cost
        </span>
        <span className="flex items-center gap-1.5 text-rich-earth">
          <span className="inline-block h-2 w-3 rounded-sm" style={{ backgroundColor: '#E07A2F' }} />
          Revenue
        </span>
      </div>
    </div>
  );
}

function niceCeil(n: number): number {
  if (n <= 0) return 1;
  const exp = Math.floor(Math.log10(n));
  const mag = Math.pow(10, exp);
  const rel = n / mag;
  if (rel <= 1) return mag;
  if (rel <= 2) return 2 * mag;
  if (rel <= 5) return 5 * mag;
  return 10 * mag;
}
