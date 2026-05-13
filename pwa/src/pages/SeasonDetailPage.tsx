import { useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { ArrowLeft } from 'lucide-react';
import { buttonClasses } from '@/components/ui/Button';
import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import { seasonsApi } from '@/api/seasons';
import { formatDate, formatKes } from '@/lib/utils';

type Tab = 'timeline' | 'inputs';

export function SeasonDetailPage() {
  const { id = '' } = useParams<{ id: string }>();
  const [tab, setTab] = useState<Tab>('timeline');

  const seasonQuery = useQuery({
    queryKey: ['seasons', id],
    queryFn: () => seasonsApi.get(id),
    enabled: !!id,
  });
  const timelineQuery = useQuery({
    queryKey: ['seasons', id, 'timeline'],
    queryFn: () => seasonsApi.timeline(id),
    enabled: !!id,
  });
  const inputsQuery = useQuery({
    queryKey: ['seasons', id, 'inputs'],
    queryFn: () => seasonsApi.inputList(id),
    enabled: !!id,
  });

  if (seasonQuery.isLoading) return <p className="text-gray-500">Loading…</p>;
  if (seasonQuery.error) return <p className="text-danger-600">Could not load this season.</p>;
  if (!seasonQuery.data) return null;

  const season = seasonQuery.data.data;

  return (
    <div className="space-y-4">
      <Link
        to="/seasons"
        className="inline-flex items-center gap-1 text-sm text-gray-600 hover:text-gray-900"
      >
        <ArrowLeft className="h-4 w-4" /> All seasons
      </Link>

      <header>
        <h1 className="text-2xl font-semibold">
          {season.crop?.name_en ?? 'Crop'} · {Number(season.acreage)} acres
        </h1>
        <p className="text-gray-600">
          Planted {formatDate(season.planting_date)} · {season.irrigation_type} · {season.status}
        </p>
      </header>

      <div className="border-b border-gray-200 flex gap-6">
        {([
          ['timeline', 'Timeline'],
          ['inputs', 'Inputs'],
        ] as const).map(([key, label]) => (
          <button
            key={key}
            type="button"
            onClick={() => setTab(key)}
            className={`pb-2 text-sm font-medium border-b-2 -mb-px ${
              tab === key
                ? 'border-brand-700 text-brand-700'
                : 'border-transparent text-gray-600 hover:text-gray-900'
            }`}
          >
            {label}
          </button>
        ))}
      </div>

      {tab === 'timeline' && (
        <div className="space-y-2">
          {timelineQuery.isLoading && <p className="text-gray-500">Loading timeline…</p>}
          {timelineQuery.data?.data.map((a) => (
            <Card key={a.id}>
              <CardBody className="flex items-start justify-between gap-3">
                <div>
                  <div className="text-sm text-gray-500">
                    {formatDate(a.ideal_date)} · week {a.week_from_planting} · {a.phase}
                  </div>
                  <div className="font-medium">{a.description_en}</div>
                  {a.tip_en && (
                    <div className="text-sm text-gray-600 mt-1">💡 {a.tip_en}</div>
                  )}
                </div>
                <div className="text-right shrink-0">
                  {a.is_critical && (
                    <span className="text-xs bg-warn-500/10 text-warn-600 px-2 py-1 rounded-full font-medium">
                      critical
                    </span>
                  )}
                  <div className="text-xs text-gray-500 mt-1">{a.status}</div>
                </div>
              </CardBody>
            </Card>
          ))}
          {timelineQuery.data && timelineQuery.data.data.length === 0 && (
            <p className="text-gray-500">No activities yet.</p>
          )}
        </div>
      )}

      {tab === 'inputs' && (
        <Card>
          <CardHeader>
            <div className="text-sm text-gray-600">
              Quantities scaled to your acreage. Tick off as you procure.
            </div>
          </CardHeader>
          <CardBody className="p-0 overflow-x-auto">
            {inputsQuery.isLoading && <p className="text-gray-500 p-4">Loading inputs…</p>}
            {inputsQuery.data && (
              <table className="w-full text-sm">
                <thead className="bg-gray-50 text-left">
                  <tr>
                    <th className="px-4 py-2">Product</th>
                    <th className="px-4 py-2">Type</th>
                    <th className="px-4 py-2 text-right">Qty</th>
                    <th className="px-4 py-2 text-right">Est. cost</th>
                  </tr>
                </thead>
                <tbody>
                  {inputsQuery.data.data.map((i) => (
                    <tr key={i.id} className="border-t border-gray-100">
                      <td className="px-4 py-2">
                        {i.product_name}
                        {i.pcpb_registered && (
                          <span className="ml-2 text-xs bg-brand-100 text-brand-800 px-1.5 py-0.5 rounded">
                            PCPB
                          </span>
                        )}
                      </td>
                      <td className="px-4 py-2 text-gray-600">{i.input_type}</td>
                      <td className="px-4 py-2 text-right">
                        {Number(i.quantity_scaled)} {i.unit}
                      </td>
                      <td className="px-4 py-2 text-right">{formatKes(i.cost_estimate_kes)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </CardBody>
        </Card>
      )}

      <div className="pt-4">
        <a
          href={`${import.meta.env.VITE_API_URL ?? ''}/api/v1/seasons/${season.id}/report`}
          target="_blank"
          rel="noreferrer"
          className={buttonClasses('secondary', 'md')}
        >
          Download PDF report
        </a>
      </div>
    </div>
  );
}
