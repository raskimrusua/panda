import { useMemo } from 'react';

export interface PriceLinePoint {
  label: string;
  value: number;
  isForecast?: boolean;
}

export interface PriceLineChartProps {
  points: PriceLinePoint[];
  height?: number;
  className?: string;
}

/**
 * Hand-rolled SVG line chart. ~80 lines instead of pulling in recharts
 * (~80 KB gzipped). Forecast points render dashed.
 */
export function PriceLineChart({ points, height = 200, className }: PriceLineChartProps) {
  const W = 800;
  const PAD = { l: 50, r: 12, t: 12, b: 28 };

  const { max, min, xs, ys, forecastFromIdx } = useMemo(() => {
    const values = points.map((p) => p.value);
    const max = Math.max(...values, 1);
    const min = Math.min(...values, 0);
    const range = max - min || 1;
    const innerW = W - PAD.l - PAD.r;
    const innerH = height - PAD.t - PAD.b;
    const xs = points.map((_, i) =>
      points.length === 1 ? PAD.l + innerW / 2 : PAD.l + (i / (points.length - 1)) * innerW,
    );
    const ys = values.map((v) => PAD.t + innerH - ((v - min) / range) * innerH);
    const forecastFromIdx = points.findIndex((p) => p.isForecast);
    return { max, min, xs, ys, forecastFromIdx };
  }, [points, height]);

  if (points.length === 0) {
    return (
      <div className={className}>
        <p className="text-sm text-gray-500 text-center py-8">No data.</p>
      </div>
    );
  }

  const realXs = forecastFromIdx === -1 ? xs : xs.slice(0, forecastFromIdx);
  const realPath = realXs
    .map((x, i) => `${i === 0 ? 'M' : 'L'} ${x ?? 0} ${ys[i] ?? 0}`)
    .join(' ');
  const forecastStart = forecastFromIdx === -1 ? -1 : Math.max(0, forecastFromIdx - 1);
  const forecastPath =
    forecastStart === -1
      ? ''
      : xs
          .slice(forecastStart)
          .map((x, i) => `${i === 0 ? 'M' : 'L'} ${x ?? 0} ${ys[forecastStart + i] ?? 0}`)
          .join(' ');

  // y-axis ticks: min, mid, max.
  const yTicks = [min, (min + max) / 2, max];

  return (
    <svg viewBox={`0 0 ${W} ${height}`} className={`w-full ${className ?? ''}`} role="img" aria-label="Price chart">
      {yTicks.map((t, i) => {
        const y = PAD.t + (height - PAD.t - PAD.b) * (1 - (t - min) / (max - min || 1));
        return (
          <g key={i}>
            <line x1={PAD.l} x2={W - PAD.r} y1={y} y2={y} stroke="#e5e7eb" strokeDasharray="2 3" />
            <text x={PAD.l - 6} y={y + 4} fontSize="11" textAnchor="end" fill="#6b7280">
              {Math.round(t)}
            </text>
          </g>
        );
      })}

      <path d={realPath} fill="none" stroke="#047857" strokeWidth={2} strokeLinejoin="round" />
      {forecastPath && (
        <path
          d={forecastPath}
          fill="none"
          stroke="#047857"
          strokeWidth={2}
          strokeDasharray="6 4"
          strokeLinejoin="round"
        />
      )}

      {points.map((p, i) => {
        const cx = xs[i] ?? 0;
        const cy = ys[i] ?? 0;
        const showLabel = i === 0 || i === points.length - 1 || i % Math.ceil(points.length / 6) === 0;
        return (
          <g key={i}>
            <circle
              cx={cx}
              cy={cy}
              r={p.isForecast ? 3 : 2.5}
              fill={p.isForecast ? '#fff' : '#047857'}
              stroke="#047857"
              strokeWidth={p.isForecast ? 1.5 : 0}
            />
            {showLabel && (
              <text x={cx} y={height - 8} fontSize="10" textAnchor="middle" fill="#6b7280">
                {p.label}
              </text>
            )}
          </g>
        );
      })}
    </svg>
  );
}
