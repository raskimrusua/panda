import { useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { ArrowLeft, CheckCircle2, Plus } from 'lucide-react';
import { Button } from '@/components/ui/Button';
import { apiClient } from '@/api/client';
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
  const { t } = useTranslation();
  const { id = '' } = useParams<{ id: string }>();
  const [tab, setTab] = useState<Tab>('timeline');
  const [logActivityId, setLogActivityId] = useState<string | null>(null);
  const [showCostForm, setShowCostForm] = useState(false);
  const [showHarvestForm, setShowHarvestForm] = useState(false);
  const [downloadingPdf, setDownloadingPdf] = useState(false);

  const handleDownloadPdf = async () => {
    setDownloadingPdf(true);
    try {
      const res = await apiClient.get(`/seasons/${id}/report`, { responseType: 'blob' });
      const blob = new Blob([res.data], { type: 'application/pdf' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `panda-season-${id}.pdf`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    } finally {
      setDownloadingPdf(false);
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

      <header>
        <h1 className="text-2xl font-semibold">
          {season.crop?.name_en ?? t('seasons.crop_fallback')} · {Number(season.acreage)} {t('seasons.acres_unit')}
        </h1>
        <p className="text-gray-600">
          {t('seasons.planted_on', { date: formatDate(season.planting_date) })} · {irrigationLabel} · {statusLabel}
        </p>
      </header>

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
          <div className="flex items-center justify-between">
            <div className="text-sm text-gray-600">
              {costsQuery.data && (
                <>{t('seasons.total_spent')}: <strong>{formatKes(costsQuery.data.totals.all_kes)}</strong></>
              )}
            </div>
            <Button onClick={() => setShowCostForm(true)} size="sm">
              <Plus className="h-4 w-4 mr-1" /> {t('seasons.log_cost')}
            </Button>
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
                  {t('seasons.picked')}: <strong>{harvestsQuery.data.totals.quantity_kg} kg</strong>
                  {' · '}
                  {t('seasons.sold')}: <strong>{harvestsQuery.data.totals.sold_quantity_kg} kg</strong>
                  {' · '}
                  {t('seasons.revenue')}: <strong>{formatKes(harvestsQuery.data.totals.revenue_kes)}</strong>
                </>
              )}
            </div>
            <Button onClick={() => setShowHarvestForm(true)} size="sm">
              <Plus className="h-4 w-4 mr-1" /> {t('seasons.log_harvest')}
            </Button>
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
                <div className="font-semibold">{formatKes(h.revenue_kes)}</div>
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
    </div>
  );
}
