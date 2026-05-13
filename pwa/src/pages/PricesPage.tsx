import { useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import { Label } from '@/components/ui/Label';
import { PriceLineChart, type PriceLinePoint } from '@/components/PriceLineChart';
import { cropsApi } from '@/api/crops';
import { pricesApi } from '@/api/prices';
import { formatDate, formatKes } from '@/lib/utils';

export function PricesPage() {
  const [slug, setSlug] = useState<string>('tomato');

  const cropsQuery = useQuery({
    queryKey: ['crops'],
    queryFn: () => cropsApi.list(),
  });

  const latestQuery = useQuery({
    queryKey: ['prices', slug, 'latest'],
    queryFn: () => pricesApi.latest(slug),
    enabled: !!slug,
  });
  const historyQuery = useQuery({
    queryKey: ['prices', slug, 'history'],
    queryFn: () => pricesApi.history(slug),
    enabled: !!slug,
  });
  const forecastQuery = useQuery({
    queryKey: ['prices', slug, 'forecast'],
    queryFn: () => pricesApi.forecast(slug),
    enabled: !!slug,
  });

  // Build the chart points from history (one weekly average across markets)
  // followed by forecast points (dashed).
  const chartPoints: PriceLinePoint[] = useMemo(() => {
    const out: PriceLinePoint[] = [];
    if (historyQuery.data) {
      // Average across markets per observed_at to keep the line readable.
      const byDate = new Map<string, number[]>();
      for (const row of historyQuery.data.data) {
        const arr = byDate.get(row.observed_at) ?? [];
        arr.push(Number(row.price_per_kg_kes));
        byDate.set(row.observed_at, arr);
      }
      const sortedDates = [...byDate.keys()].sort();
      for (const d of sortedDates) {
        const xs = byDate.get(d) ?? [];
        const avg = xs.reduce((a, b) => a + b, 0) / xs.length;
        out.push({ label: d.slice(5), value: Math.round(avg) });
      }
    }
    if (forecastQuery.data) {
      for (const f of forecastQuery.data.data) {
        if (f.projected_price_per_kg_kes !== null) {
          out.push({
            label: f.month.slice(2),
            value: Math.round(f.projected_price_per_kg_kes),
            isForecast: true,
          });
        }
      }
    }
    return out;
  }, [historyQuery.data, forecastQuery.data]);

  return (
    <div className="space-y-4">
      <header>
        <h1 className="text-2xl font-semibold">Market prices</h1>
        <p className="text-gray-600">
          Latest prices per market, 12-month history, and a 3-month
          rule-based forecast (dashed). Trend signal only — not a guarantee.
        </p>
      </header>

      <Card>
        <CardBody>
          <Label htmlFor="crop">Crop</Label>
          <select
            id="crop"
            className="flex h-10 w-full md:w-72 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm"
            value={slug}
            onChange={(e) => setSlug(e.target.value)}
            disabled={cropsQuery.isLoading}
          >
            {cropsQuery.data?.data.map((c) => (
              <option key={c.id} value={c.slug}>{c.name_en}</option>
            ))}
          </select>
        </CardBody>
      </Card>

      <Card>
        <CardHeader>
          <div className="font-semibold">12-month history + 3-month forecast</div>
          <div className="text-xs text-gray-500 mt-1">
            Solid line: average across {latestQuery.data?.meta.market_count ?? '—'} markets.
            Dashed: rule-based forecast (monthly average, projected forward).
          </div>
        </CardHeader>
        <CardBody>
          <PriceLineChart points={chartPoints} height={240} />
        </CardBody>
      </Card>

      <Card>
        <CardHeader>
          <div className="font-semibold">Latest by market</div>
        </CardHeader>
        <CardBody className="p-0 overflow-x-auto">
          {latestQuery.isLoading && <p className="p-4 text-gray-500">Loading…</p>}
          {latestQuery.data && (
            <table className="w-full text-sm">
              <thead className="bg-gray-50 text-left">
                <tr>
                  <th className="px-4 py-2">Market</th>
                  <th className="px-4 py-2">County</th>
                  <th className="px-4 py-2">Date</th>
                  <th className="px-4 py-2 text-right">Price/kg</th>
                </tr>
              </thead>
              <tbody>
                {latestQuery.data.data.map((p) => (
                  <tr key={p.id} className="border-t border-gray-100">
                    <td className="px-4 py-2">{p.market_name}</td>
                    <td className="px-4 py-2 text-gray-600">{p.county}</td>
                    <td className="px-4 py-2 text-gray-600">{formatDate(p.observed_at)}</td>
                    <td className="px-4 py-2 text-right font-medium">
                      {formatKes(p.price_per_kg_kes)}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </CardBody>
      </Card>
    </div>
  );
}
