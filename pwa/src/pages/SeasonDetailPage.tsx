import { useMemo, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { ArrowLeft, CheckCircle2, Download, Plus } from 'lucide-react';
import { Button } from '@/components/ui/Button';
import { apiClient } from '@/api/client';
import { ActivityProgressBar } from '@/components/charts/ActivityProgressBar';
import { CostByCategoryBar } from '@/components/charts/CostByCategoryBar';
import { CumulativeCostRevenueChart, type CumulativePoint } from '@/components/charts/CumulativeCostRevenueChart';
import { YieldTrajectoryChart, type YieldPoint } from '@/components/charts/YieldTrajectoryChart';
import { ProfitSummaryCard } from '@/components/ProfitSummaryCard';
import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import { Modal } from '@/components/ui/Modal';
import { LogActivityDoneForm } from '@/components/forms/LogActivityDoneForm';
import { NewCostForm } from '@/components/forms/NewCostForm';
import { NewHarvestForm } from '@/components/forms/NewHarvestForm';
import { EditCostForm } from '@/components/forms/EditCostForm';
import { EditHarvestForm } from '@/components/forms/EditHarvestForm';
import { ConfirmDeleteModal } from '@/components/ui/ConfirmDeleteModal';
import { RowActions } from '@/components/ui/RowActions';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { seasonsApi } from '@/api/seasons';
import { costsApi, type CostEntry } from '@/api/costs';
import { harvestsApi, type HarvestLog } from '@/api/harvests';
import { formatDate, formatKes } from '@/lib/utils';

type Tab = 'timeline' | 'inputs' | 'costs' | 'harvests';

export function SeasonDetailPage() {
  const { t } = useTranslation();
  const { id = '' } = useParams<{ id: string }>();
  const [tab, setTab] = useState<Tab>('timeline');
  const [logActivityId, setLogActivityId] = useState<string | null>(null);
  const [showCostForm, setShowCostForm] = useState(false);
  const [showHarvestForm, setShowHarvestForm] = useState(false);
  const [downloadingPdf, setDownloadingPdf] = useState(false);
  const [downloadingCsv, setDownloadingCsv] = useState<null | 'costs' | 'harvests' | 'activities'>(null);
  const [editingCost, setEditingCost] = useState<CostEntry | null>(null);
  const [deletingCost, setDeletingCost] = useState<CostEntry | null>(null);
  const [editingHarvest, setEditingHarvest] = useState<HarvestLog | null>(null);
  const [deletingHarvest, setDeletingHarvest] = useState<HarvestLog | null>(null);
  const [showAbandonConfirm, setShowAbandonConfirm] = useState(false);
  const navigate = useNavigate();
  const queryClient = useQueryClient();

  const abandonMutation = useMutation({
    mutationFn: () => seasonsApi.update(id, { status: 'abandoned' }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['seasons'] });
      queryClient.invalidateQueries({ queryKey: ['seasons', id] });
      navigate('/seasons');
    },
  });

  const deleteCostFn = async () => {
    if (!deletingCost) return;
    await costsApi.destroy(deletingCost.id);
    queryClient.invalidateQueries({ queryKey: ['seasons', id, 'costs'] });
  };

  const deleteHarvestFn = async () => {
    if (!deletingHarvest) return;
    await harvestsApi.destroy(deletingHarvest.id);
    queryClient.invalidateQueries({ queryKey: ['seasons', id, 'harvests'] });
  };

  const downloadBlob = async (path: string, filename: string, mimeType: string) => {
    const res = await apiClient.get(path, { responseType: 'blob' });
    const blob = new Blob([res.data], { type: mimeType });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  };

  const handleDownloadPdf = async () => {
    setDownloadingPdf(true);
    try {
      await downloadBlob(`/seasons/${id}/report`, `panda-season-${id}.pdf`, 'application/pdf');
    } finally {
      setDownloadingPdf(false);
    }
  };

  const handleDownloadCsv = async (kind: 'costs' | 'harvests' | 'activities') => {
    setDownloadingCsv(kind);
    try {
      await downloadBlob(
        `/seasons/${id}/${kind}.csv`,
        `panda-season-${id}-${kind}.csv`,
        'text/csv',
      );
    } finally {
      setDownloadingCsv(null);
    }
  };

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
  // Costs + harvests are loaded regardless of active tab so the summary
  // card + cost/revenue chart at the top of the page always have data.
  // Both endpoints are small (a handful of KB) — total page weight is
  // unaffected.
  const costsQuery = useQuery({
    queryKey: ['seasons', id, 'costs'],
    queryFn: () => costsApi.forSeason(id),
    enabled: !!id,
  });
  const harvestsQuery = useQuery({
    queryKey: ['seasons', id, 'harvests'],
    queryFn: () => harvestsApi.forSeason(id),
    enabled: !!id,
  });

  // Derived totals + chart points (memoised so the chart doesn't re-render
  // on every tab click).
  const totals = useMemo(() => {
    const totalCost = Number(costsQuery.data?.totals.all_kes ?? 0);
    const totalRevenue = Number(harvestsQuery.data?.totals.revenue_kes ?? 0);
    return { totalCost, totalRevenue };
  }, [costsQuery.data, harvestsQuery.data]);

  const cumulativePoints = useMemo<CumulativePoint[]>(() => {
    const events: { date: string; deltaCost: number; deltaRevenue: number }[] = [];
    for (const c of costsQuery.data?.data ?? []) {
      events.push({ date: String(c.incurred_at), deltaCost: Number(c.amount_kes), deltaRevenue: 0 });
    }
    for (const h of harvestsQuery.data?.data ?? []) {
      const rev = Number(h.sold_quantity_kg ?? 0) * Number(h.unit_price_kes ?? 0);
      events.push({ date: String(h.harvested_at), deltaCost: 0, deltaRevenue: rev });
    }
    events.sort((a, b) => a.date.localeCompare(b.date));
    let runCost = 0;
    let runRev = 0;
    return events.map((e) => {
      runCost += e.deltaCost;
      runRev += e.deltaRevenue;
      return {
        label: e.date.slice(5), // mm-dd is enough for x-axis
        cumulativeCost: runCost,
        cumulativeRevenue: runRev,
      };
    });
  }, [costsQuery.data, harvestsQuery.data]);

  const costByCategory = useMemo(() => {
    const m = new Map<string, number>();
    for (const c of costsQuery.data?.data ?? []) {
      m.set(c.category, (m.get(c.category) ?? 0) + Number(c.amount_kes));
    }
    return Array.from(m.entries()).map(([category, total]) => ({ category, total }));
  }, [costsQuery.data]);

  // Cumulative kg picked over time for the yield trajectory chart on
  // the Harvests tab. Sorted by harvested_at, running sum across picks.
  const yieldPoints = useMemo<YieldPoint[]>(() => {
    const rows = [...(harvestsQuery.data?.data ?? [])].sort((a, b) =>
      String(a.harvested_at).localeCompare(String(b.harvested_at)),
    );
    let run = 0;
    return rows.map((h) => {
      run += Number(h.quantity_kg ?? 0);
      return { label: String(h.harvested_at), cumulativeKg: run };
    });
  }, [harvestsQuery.data]);

  if (seasonQuery.isLoading) return <p className="text-gray-500">{t('seasons.loading_season')}</p>;
  if (seasonQuery.error) return <p className="text-danger-600">{t('seasons.could_not_load_season')}</p>;
  if (!seasonQuery.data) return null;

  const season = seasonQuery.data.data;

  const irrigationLabel = t(`seasons.${season.irrigation_type}`, { defaultValue: season.irrigation_type });
  const statusLabel = t(`seasons.status_${season.status}`, { defaultValue: season.status });

  return (
    <div className="space-y-4">
      <Link
        to="/seasons"
        className="inline-flex items-center gap-1 text-sm text-gray-600 hover:text-gray-900"
      >
        <ArrowLeft className="h-4 w-4" /> {t('common.all_seasons')}
      </Link>

      <header className="flex items-start justify-between gap-3">
        <div>
          <h1 className="text-2xl font-semibold">
            {season.crop?.name_en ?? t('seasons.crop_fallback')} · {Number(season.acreage)} {t('seasons.acres_unit')}
          </h1>
          <p className="text-gray-600">
            {t('seasons.planted_on', { date: formatDate(season.planting_date) })} · {irrigationLabel} · {statusLabel}
          </p>
        </div>
        {season.status !== 'abandoned' && season.status !== 'complete' && (
          <Button variant="ghost" size="sm" onClick={() => setShowAbandonConfirm(true)}>
            {t('seasons.abandon_button')}
          </Button>
        )}
      </header>

      <ProfitSummaryCard totalCost={totals.totalCost} totalRevenue={totals.totalRevenue} />

      {cumulativePoints.length >= 2 && (
        <Card>
          <CardHeader>
            <div className="flex items-baseline justify-between gap-2">
              <h2 className="text-base font-semibold text-gray-900">
                {t('seasons.cost_revenue_chart_title')}
              </h2>
              <span className="text-xs text-gray-500">{t('seasons.cost_revenue_chart_subtitle')}</span>
            </div>
          </CardHeader>
          <CardBody>
            <CumulativeCostRevenueChart points={cumulativePoints} />
          </CardBody>
        </Card>
      )}

      <div className="border-b border-gray-200 flex gap-6 overflow-x-auto">
        {([
          ['timeline', t('seasons.tab_timeline')],
          ['inputs', t('seasons.tab_inputs')],
          ['costs', t('seasons.tab_costs')],
          ['harvests', t('seasons.tab_harvests')],
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
          {timelineQuery.data && timelineQuery.data.data.length > 0 && (
            <Card>
              <CardBody>
                <ActivityProgressBar
                  done={timelineQuery.data.data.filter((a) => a.status === 'done').length}
                  total={timelineQuery.data.data.length}
                />
              </CardBody>
            </Card>
          )}
          <div className="flex justify-end">
            <Button
              size="sm"
              variant="secondary"
              loading={downloadingCsv === 'activities'}
              onClick={() => handleDownloadCsv('activities')}
            >
              <Download className="h-4 w-4 mr-1" /> {t('seasons.download_csv')}
            </Button>
          </div>
          {timelineQuery.isLoading && <p className="text-gray-500">{t('seasons.loading_timeline')}</p>}
          {timelineQuery.data?.data.map((a) => (
            <Card key={a.id}>
              <CardBody className="flex items-start justify-between gap-3">
                <div className="flex-1">
                  <div className="text-sm text-gray-500">
                    {formatDate(a.ideal_date)} · {t('seasons.week_phase', { week: a.week_from_planting, phase: a.phase })}
                  </div>
                  <div className="font-medium">{a.description_en}</div>
                  {a.tip_en && (
                    <div className="text-sm text-gray-600 mt-1">💡 {a.tip_en}</div>
                  )}
                </div>
                <div className="flex flex-col items-end gap-2 shrink-0">
                  {a.is_critical && (
                    <span className="text-xs bg-warn-500/10 text-warn-600 px-2 py-1 rounded-full font-medium">
                      {t('seasons.critical')}
                    </span>
                  )}
                  {a.status === 'done' ? (
                    <span className="inline-flex items-center gap-1 text-xs text-brand-700 font-medium">
                      <CheckCircle2 className="h-4 w-4" /> {t('seasons.done').toLowerCase()}
                    </span>
                  ) : (
                    <Button size="sm" variant="secondary" onClick={() => setLogActivityId(a.id)}>
                      {t('seasons.mark_done')}
                    </Button>
                  )}
                </div>
              </CardBody>
            </Card>
          ))}
          {timelineQuery.data && timelineQuery.data.data.length === 0 && (
            <p className="text-gray-500">{t('seasons.no_activities')}</p>
          )}
        </div>
      )}

      {tab === 'inputs' && (
        <Card>
          <CardHeader>
            <div className="text-sm text-gray-600">
              {t('seasons.inputs_explainer')}
            </div>
          </CardHeader>
          <CardBody className="p-0 overflow-x-auto">
            {inputsQuery.isLoading && <p className="text-gray-500 p-4">{t('seasons.loading_inputs')}</p>}
            {inputsQuery.data && (
              <table className="w-full text-sm">
                <thead className="bg-gray-50 text-left">
                  <tr>
                    <th className="px-4 py-2">{t('seasons.col_product')}</th>
                    <th className="px-4 py-2">{t('seasons.col_type')}</th>
                    <th className="px-4 py-2 text-right">{t('seasons.col_qty')}</th>
                    <th className="px-4 py-2 text-right">{t('seasons.col_est_cost')}</th>
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
          {costByCategory.length > 0 && (
            <Card>
              <CardHeader>
                <h3 className="text-sm font-semibold text-gray-900">
                  {t('seasons.cost_by_category_title')}
                </h3>
              </CardHeader>
              <CardBody>
                <CostByCategoryBar
                  data={costByCategory}
                  labelFor={(c) => t(`log_forms.category_${c}`, { defaultValue: c })}
                />
              </CardBody>
            </Card>
          )}
          <div className="flex items-center justify-between">
            <div className="text-sm text-gray-600">
              {costsQuery.data && (
                <>{t('seasons.total_spent')}: <strong>{formatKes(costsQuery.data.totals.all_kes)}</strong></>
              )}
            </div>
            <div className="flex items-center gap-2">
              <Button
                size="sm"
                variant="secondary"
                loading={downloadingCsv === 'costs'}
                onClick={() => handleDownloadCsv('costs')}
              >
                <Download className="h-4 w-4 mr-1" /> {t('seasons.download_csv')}
              </Button>
              <Button onClick={() => setShowCostForm(true)} size="sm">
                <Plus className="h-4 w-4 mr-1" /> {t('seasons.log_cost')}
              </Button>
            </div>
          </div>
          {costsQuery.isLoading && <p className="text-gray-500">{t('seasons.loading_costs')}</p>}
          {costsQuery.data && costsQuery.data.data.length === 0 && (
            <Card><CardBody className="text-center text-gray-600">{t('seasons.no_costs')}</CardBody></Card>
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
                <div className="flex items-center gap-2">
                  <div className="font-semibold">{formatKes(c.amount_kes)}</div>
                  <RowActions onEdit={() => setEditingCost(c)} onDelete={() => setDeletingCost(c)} />
                </div>
              </CardBody>
            </Card>
          ))}
        </div>
      )}

      {tab === 'harvests' && (
        <div className="space-y-3">
          {yieldPoints.length > 0 && (
            <Card>
              <CardHeader>
                <h3 className="text-sm font-semibold text-gray-900">
                  {t('seasons.yield_trajectory_title')}
                </h3>
                <p className="text-xs text-gray-500">{t('seasons.yield_trajectory_subtitle')}</p>
              </CardHeader>
              <CardBody>
                <YieldTrajectoryChart
                  points={yieldPoints}
                  formatValue={(n) => `${Math.round(n)} kg`}
                />
              </CardBody>
            </Card>
          )}
          <div className="flex items-center justify-between">
            <div className="text-sm text-gray-600">
              {harvestsQuery.data && (
                <>
                  {t('seasons.picked')}: <strong>{harvestsQuery.data.totals.quantity_kg} kg</strong>
                  {' · '}
                  {t('seasons.sold')}: <strong>{harvestsQuery.data.totals.sold_quantity_kg} kg</strong>
                  {' · '}
                  {t('seasons.revenue')}: <strong>{formatKes(harvestsQuery.data.totals.revenue_kes)}</strong>
                </>
              )}
            </div>
            <div className="flex items-center gap-2">
              <Button
                size="sm"
                variant="secondary"
                loading={downloadingCsv === 'harvests'}
                onClick={() => handleDownloadCsv('harvests')}
              >
                <Download className="h-4 w-4 mr-1" /> {t('seasons.download_csv')}
              </Button>
              <Button onClick={() => setShowHarvestForm(true)} size="sm">
                <Plus className="h-4 w-4 mr-1" /> {t('seasons.log_harvest')}
              </Button>
            </div>
          </div>
          {harvestsQuery.isLoading && <p className="text-gray-500">{t('seasons.loading_harvests')}</p>}
          {harvestsQuery.data && harvestsQuery.data.data.length === 0 && (
            <Card><CardBody className="text-center text-gray-600">{t('seasons.no_harvests')}</CardBody></Card>
          )}
          {harvestsQuery.data?.data.map((h) => (
            <Card key={h.id}>
              <CardBody className="flex items-center justify-between">
                <div>
                  <div className="font-medium">{t('seasons.kg_picked_short', { n: Number(h.quantity_kg) })}</div>
                  <div className="text-sm text-gray-600">
                    {formatDate(h.harvested_at)}
                    {Number(h.sold_quantity_kg) > 0 && (
                      <> · {t('seasons.sold_at_price', { kg: Number(h.sold_quantity_kg), price: formatKes(h.unit_price_kes) })}</>
                    )}
                    {h.buyer_name && ` · ${h.buyer_name}`}
                  </div>
                </div>
                <div className="flex items-center gap-2">
                  <div className="font-semibold">{formatKes(h.revenue_kes)}</div>
                  <RowActions onEdit={() => setEditingHarvest(h)} onDelete={() => setDeletingHarvest(h)} />
                </div>
              </CardBody>
            </Card>
          ))}
        </div>
      )}

      <div className="pt-4">
        <Button
          variant="secondary"
          onClick={handleDownloadPdf}
          loading={downloadingPdf}
        >
          {t('seasons.download_pdf')}
        </Button>
      </div>

      <Modal
        open={!!logActivityId}
        onClose={() => setLogActivityId(null)}
        title={t('seasons.modal_mark_done')}
      >
        {logActivityId && (
          <LogActivityDoneForm
            activityId={logActivityId}
            seasonId={id}
            onDone={() => setLogActivityId(null)}
          />
        )}
      </Modal>

      <Modal open={showCostForm} onClose={() => setShowCostForm(false)} title={t('seasons.modal_log_cost')}>
        <NewCostForm seasonId={id} onDone={() => setShowCostForm(false)} />
      </Modal>

      <Modal open={showHarvestForm} onClose={() => setShowHarvestForm(false)} title={t('seasons.modal_log_harvest')}>
        <NewHarvestForm seasonId={id} onDone={() => setShowHarvestForm(false)} />
      </Modal>

      <Modal open={!!editingCost} onClose={() => setEditingCost(null)} title={t('seasons.modal_edit_cost')}>
        {editingCost && (
          <EditCostForm cost={editingCost} seasonId={id} onDone={() => setEditingCost(null)} />
        )}
      </Modal>

      <Modal open={!!editingHarvest} onClose={() => setEditingHarvest(null)} title={t('seasons.modal_edit_harvest')}>
        {editingHarvest && (
          <EditHarvestForm harvest={editingHarvest} seasonId={id} onDone={() => setEditingHarvest(null)} />
        )}
      </Modal>

      <ConfirmDeleteModal
        open={!!deletingCost}
        onClose={() => setDeletingCost(null)}
        onConfirm={deleteCostFn}
        title={t('seasons.delete_cost_title')}
        body={t('seasons.delete_cost_body')}
      />

      <ConfirmDeleteModal
        open={!!deletingHarvest}
        onClose={() => setDeletingHarvest(null)}
        onConfirm={deleteHarvestFn}
        title={t('seasons.delete_harvest_title')}
        body={t('seasons.delete_harvest_body')}
      />

      <ConfirmDeleteModal
        open={showAbandonConfirm}
        onClose={() => setShowAbandonConfirm(false)}
        onConfirm={async () => {
          await abandonMutation.mutateAsync();
        }}
        title={t('seasons.abandon_title')}
        body={t('seasons.abandon_body')}
        confirmLabel={t('seasons.abandon_button')}
      />
    </div>
  );
}
