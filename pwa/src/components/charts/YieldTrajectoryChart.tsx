import { useMemo } from 'react';

export interface YieldPoint {
  /** ISO yyyy-mm-dd date the harvest pick was logged. */
  label: string;
  cumulativeKg: number;
}

export interface YieldTrajectoryChartProps {
  points: YieldPoint[];
  height?: number;
  className?: string;
  formatValue?: (n: number) => string;
}

/*
 * Hand-rolled SVG line chart showing cumulative kg picked over time —
 * the running total a farmer eyeballs to know whether this season is
 * tracking ahead of or behind last season's yield.
 *
 * Mirrors CumulativeCostRevenueChart's geometry so the harvests tab
 * feels visually consistent with the costs/revenue tab. Single line
 * (green) — no secondary series to confuse the read.
 *
 * Empty state renders a flat dashed baseline + a "No harvests yet"
 * placeholder so the section doesn't collapse to zero height between
 * planting and first pick.
 */
export function YieldTrajectoryChart({
  points,
  height = 200,
  className,
  formatValue,
}: YieldTrajectoryChartProps) {
  const W = 800;
  const PAD = { l: 60, r: 12, t: 22, b: 28 };
  const fmt = formatValue ?? ((n: number) => Math.round(n).toLocaleString('en-KE'));

  const { max, xs, ys } = useMemo(() => {
    if (points.length === 0) {
      return { max: 1, xs: [] as number[], ys: [] as number[] };
    }
    const maxRaw = Math.max(...points.map((p) => p.cumulativeKg), 1);
    const niceMax = niceCeil(maxRaw);
    const innerW = W - PAD.l - PAD.r;
    const innerH = height - PAD.t - PAD.b;
    const xs = points.map((_, i) =>
      points.length === 1 ? PAD.l + innerW / 2 : PAD.l + (i / (points.length - 1)) * innerW,
    );
    const ys = points.map((p) => PAD.t + innerH - (p.cumulativeKg / niceMax) * innerH);
    return { max: niceMax, xs, ys };
  }, [points, height]);

  const innerH = height - PAD.t - PAD.b;
  const gridYs = [0, 0.5, 1].map((f) => PAD.t + innerH - f * innerH);
  const path = xs.length > 0
    ? xs.map((x, i) => `${i === 0 ? 'M' : 'L'} ${x.toFixed(1)} ${(ys[i] ?? 0).toFixed(1)}`).join(' ')
    : '';

  return (
    <svg
      viewBox={`0 0 ${W} ${height}`}
      className={className}
      role="img"
      aria-label="Cumulative yield over time"
    >
      {gridYs.map((y, i) => (
        <line
          key={`g-${i}`}
          x1={PAD.l}
          x2={W - PAD.r}
          y1={y}
          y2={y}
          stroke="#e5e7eb"
          strokeDasharray={i === gridYs.length - 1 ? undefined : '3 3'}
        />
      ))}
      {[0, 0.5, 1].map((f, i) => (
        <text
          key={`yt-${i}`}
          x={PAD.l - 6}
          y={PAD.t + innerH - f * innerH}
          textAnchor="end"
          dominantBaseline="middle"
          className="fill-gray-500 text-[11px]"
        >
          {fmt(max * f)}
        </text>
      ))}
      {points.length === 0 && (
        <text
          x={W / 2}
          y={height / 2 + 4}
          textAnchor="middle"
          className="fill-gray-400 text-xs"
        >
          —
        </text>
      )}
      {points.length > 0 && (
        <>
          <path d={path} fill="none" stroke="#16a34a" strokeWidth={2} />
          {xs.map((x, i) => (
            <circle key={`pt-${i}`} cx={x} cy={ys[i] ?? 0} r={3} fill="#16a34a" />
          ))}
        </>
      )}
      {points.length > 1 && (
        <>
          <text
            x={xs[0]}
            y={height - 8}
            textAnchor="middle"
            className="fill-gray-500 text-[10px]"
          >
            {(points[0]?.label ?? '').slice(5)}
          </text>
          <text
            x={xs[xs.length - 1]}
            y={height - 8}
            textAnchor="middle"
            className="fill-gray-500 text-[10px]"
          >
            {(points[points.length - 1]?.label ?? '').slice(5)}
          </text>
        </>
      )}
    </svg>
  );
}

function niceCeil(v: number): number {
  if (v <= 1) return 1;
  const pow = Math.pow(10, Math.floor(Math.log10(v)));
  const ratio = v / pow;
  let nice: number;
  if (ratio <= 1) nice = 1;
  else if (ratio <= 2) nice = 2;
  else if (ratio <= 5) nice = 5;
  else nice = 10;
  return nice * pow;
}
