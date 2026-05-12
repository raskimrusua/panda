import { useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { ArrowLeft, CheckCircle2, Plus } from 'lucide-react';
import { Button, buttonClasses } from '@/components/ui/Button';
import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import { Modal } from '@/components/ui/Modal';
import { LogActivityDoneForm } from '@/components/forms/LogActivityDoneForm';
import { NewCostForm } from '@/components/forms/NewCostForm';
import { NewHarvestForm } from '@/components/forms/NewHarvestForm';
import { seasonsApi } from '@/api/seasons';
import { costsApi } from '@/api/costs';
import { harvestsApi } from '@/api/harvests';
import { formatDate, formatKes } from '@/lib/utils';

type Tab = 'timeline' | 'inputs' | 'costs' | 'harvests';

export function SeasonDetailPage() {
  const { id = '' } = useParams<{ id: string }>();
  const [tab, setTab] = useState<Tab>('timeline');
  const [logActivityId, setLogActivityId] = useState<string | null>(null);
  const [showCostForm, setShowCostForm] = useState(false);
  const [showHarvestForm, setShowHarvestForm] = useState(false);

  const seasonQuery = useQuery({
    queryKey: ['seasons', id],
    queryFn: () => seasonsApi.get(id),
    enabled: !!id,
  });
  const timelineQuery = useQuery({
    queryKey: ['seasons', id, 'timeline'],
    queryFn: () => seasonsApi.timeline(id),
    enabled: !!id && tab === 'timeline',
  });
  const inputsQuery = useQuery({
    queryKey: ['seasons', id, 'inputs'],
    queryFn: () => seasonsApi.inputList(id),
    enabled: !!id && tab === 'inputs',
  });
  const costsQuery = useQuery({
    queryKey: ['seasons', id, 'costs'],
    queryFn: () => costsApi.forSeason(id),
    enabled: !!id && tab === 'costs',
  });
  const harvestsQuery = useQuery({
    queryKey: ['seasons', id, 'harvests'],
    queryFn: () => harvestsApi.forSeason(id),
    enabled: !!id && tab === 'harvests',
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

      <div className="border-b border-gray-200 flex gap-6 overflow-x-auto">
        {([
          ['timeline', 'Timeline'],
          ['inputs', 'Inputs'],
          ['costs', 'Costs'],
          ['harvests', 'Harvests'],
        ] as const).map(([key, label]) => (
          <button
            key={key}
            type="button"
            onClick={() => setTab(key)}
            className={`pb-2 text-sm font-medium border-b-2 -mb-px whitespace-nowrap ${
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
                <div className="flex-1">
                  <div className="text-sm text-gray-500">
                    {formatDate(a.ideal_date)} · week {a.week_from_planting} · {a.phase}
                  </div>
                  <div className="font-medium">{a.description_en}</div>
                  {a.tip_en && (
                    <div className="text-sm text-gray-600 mt-1">💡 {a.tip_en}</div>
                  )}
                </div>
                <div className="flex flex-col items-end gap-2 shrink-0">
                  {a.is_critical && (
                    <span className="text-xs bg-warn-500/10 text-warn-600 px-2 py-1 rounded-full font-medium">
                      critical
                    </span>
                  )}
                  {a.status === 'done' ? (
                    <span className="inline-flex items-center gap-1 text-xs text-brand-700 font-medium">
                      <CheckCircle2 className="h-4 w-4" /> done
                    </span>
                  ) : (
                    <Button size="sm" variant="secondary" onClick={() => setLogActivityId(a.id)}>
                      Mark done
                    </Button>
                  )}
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

      {tab === 'costs' && (
        <div className="space-y-3">
          <div className="flex items-center justify-between">
            <div className="text-sm text-gray-600">
              {costsQuery.data && (
                <>Total spent: <strong>{formatKes(costsQuery.data.totals.all_kes)}</strong></>
              )}
            </div>
            <Button onClick={() => setShowCostForm(true)} size="sm">
              <Plus className="h-4 w-4 mr-1" /> Log cost
            </Button>
          </div>
          {costsQuery.isLoading && <p className="text-gray-500">Loading costs…</p>}
          {costsQuery.data && costsQuery.data.data.length === 0 && (
            <Card><CardBody className="text-center text-gray-600">No costs logged yet.</CardBody></Card>
          )}
          {costsQuery.data?.data.map((c) => (
            <Card key={c.id}>
              <CardBody className="flex items-center justify-between">
                <div>
                  <div className="font-medium">{c.description}</div>
                  <div className="text-sm text-gray-600">
                    {formatDate(c.incurred_at)} · {c.category}
                    {c.supplier_name && ` · ${c.supplier_name}`}
                  </div>
                </div>
                <div className="font-semibold">{formatKes(c.amount_kes)}</div>
              </CardBody>
            </Card>
          ))}
        </div>
      )}

      {tab === 'harvests' && (
        <div className="space-y-3">
          <div className="flex items-center justify-between">
            <div className="text-sm text-gray-600">
              {harvestsQuery.data && (
                <>
                  Picked: <strong>{harvestsQuery.data.totals.quantity_kg} kg</strong>
                  {' · '}
                  Sold: <strong>{harvestsQuery.data.totals.sold_quantity_kg} kg</strong>
                  {' · '}
                  Revenue: <strong>{formatKes(harvestsQuery.data.totals.revenue_kes)}</strong>
                </>
              )}
            </div>
            <Button onClick={() => setShowHarvestForm(true)} size="sm">
              <Plus className="h-4 w-4 mr-1" /> Log harvest
            </Button>
          </div>
          {harvestsQuery.isLoading && <p className="text-gray-500">Loading harvests…</p>}
          {harvestsQuery.data && harvestsQuery.data.data.length === 0 && (
            <Card><CardBody className="text-center text-gray-600">No harvests yet.</CardBody></Card>
          )}
          {harvestsQuery.data?.data.map((h) => (
            <Card key={h.id}>
              <CardBody className="flex items-center justify-between">
                <div>
                  <div className="font-medium">{Number(h.quantity_kg)} kg picked</div>
                  <div className="text-sm text-gray-600">
                    {formatDate(h.harvested_at)}
                    {Number(h.sold_quantity_kg) > 0 && (
                      <> · sold {Number(h.sold_quantity_kg)} kg @ {formatKes(h.unit_price_kes)}/kg</>
                    )}
                    {h.buyer_name && ` · ${h.buyer_name}`}
                  </div>
                </div>
                <div className="font-semibold">{formatKes(h.revenue_kes)}</div>
              </CardBody>
            </Card>
          ))}
        </div>
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

      <Modal
        open={!!logActivityId}
        onClose={() => setLogActivityId(null)}
        title="Mark activity done"
      >
        {logActivityId && (
          <LogActivityDoneForm
            activityId={logActivityId}
            seasonId={id}
            onDone={() => setLogActivityId(null)}
          />
        )}
      </Modal>

      <Modal open={showCostForm} onClose={() => setShowCostForm(false)} title="Log a cost">
        <NewCostForm seasonId={id} onDone={() => setShowCostForm(false)} />
      </Modal>

      <Modal open={showHarvestForm} onClose={() => setShowHarvestForm(false)} title="Log a harvest">
        <NewHarvestForm seasonId={id} onDone={() => setShowHarvestForm(false)} />
      </Modal>
    </div>
  );
}
