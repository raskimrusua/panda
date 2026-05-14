import { useMemo } from 'react';

export interface CostByCategoryDatum {
  category: string;
  total: number;
}

export interface CostByCategoryBarProps {
  data: CostByCategoryDatum[];
  /** Optional category-name resolver (e.g. for i18n or display formatting). */
  labelFor?: (category: string) => string;
  /** Optional money formatter (default: localeString with no decimals). */
  formatValue?: (n: number) => string;
  className?: string;
}

/*
 * Horizontal stacked bar showing the share of total spend per category.
 * Single bar (not a grid of bars) so it reads at a glance — categories
 * are sorted descending by total. Hand-rolled SVG, no chart library.
 *
 * Why a single stacked bar (not a pie):
 *  - Pies are bad for >5 slices; tomato seasons routinely have 6 cost
 *    categories (seed, fertiliser, chemical, labour, equipment, transport)
 *  - A bar makes "labour is 40% of cost" instantly readable
 *  - SVG with text labels doesn't need a tooltip layer to be useful
 *
 * The COLOURS array uses Shira's brand palette tones plus two greys for
 * overflow categories. If a category lacks a colour mapping, falls back
 * to a deterministic hash → greyscale.
 */

const PALETTE: Record<string, string> = {
  seed: '#52B788',          // soft-green
  fertiliser: '#1B4332',    // highland-green
  chemical: '#74C69D',      // panda-leaf
  labour: '#E07A2F',        // savanna-sun
  equipment: '#D4930D',     // harvest-gold
  transport: '#C4652E',     // terracotta
  other: '#9ca3af',         // gray-400
};

function colourFor(category: string): string {
  return PALETTE[category] ?? '#6b7280';
}

export function CostByCategoryBar({
  data,
  labelFor,
  formatValue,
  className,
}: CostByCategoryBarProps) {
  const total = useMemo(() => data.reduce((s, d) => s + d.total, 0), [data]);
  const sorted = useMemo(() => [...data].sort((a, b) => b.total - a.total), [data]);
  const fmt = formatValue ?? ((n: number) => `KES ${n.toLocaleString('en-KE', { maximumFractionDigits: 0 })}`);
  const lbl = labelFor ?? ((c: string) => c);

  if (data.length === 0 || total <= 0) {
    return (
      <div className={className}>
        <p className="py-4 text-center text-sm text-gray-500">No costs to chart yet.</p>
      </div>
    );
  }

  return (
    <div className={className}>
      {/* Stacked bar */}
      <div className="flex h-3 w-full overflow-hidden rounded-full bg-gray-100">
        {sorted.map((d) => {
          const pct = (d.total / total) * 100;
          return (
            <div
              key={d.category}
              style={{ width: `${pct}%`, backgroundColor: colourFor(d.category) }}
              role="presentation"
              aria-label={`${lbl(d.category)}: ${pct.toFixed(0)}%`}
            />
          );
        })}
      </div>

      {/* Legend */}
      <ul className="mt-3 grid grid-cols-1 gap-1 sm:grid-cols-2">
        {sorted.map((d) => {
          const pct = (d.total / total) * 100;
          return (
            <li key={d.category} className="flex items-center justify-between text-sm">
              <span className="flex items-center gap-2 text-rich-earth">
                <span
                  className="inline-block h-2.5 w-2.5 rounded-sm"
                  style={{ backgroundColor: colourFor(d.category) }}
                />
                {lbl(d.category)}
                <span className="text-gray-500">· {pct.toFixed(0)}%</span>
              </span>
              <span className="font-medium text-rich-earth">{fmt(d.total)}</span>
            </li>
          );
        })}
      </ul>
    </div>
  );
}
