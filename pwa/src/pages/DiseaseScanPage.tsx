import { useEffect, useState, type ChangeEvent } from 'react';
import { useMutation, useQuery } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { Camera, ThumbsDown, ThumbsUp, Upload } from 'lucide-react';
import { Button } from '@/components/ui/Button';
import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import { Label } from '@/components/ui/Label';
import { cropsApi } from '@/api/crops';
import { seasonsApi } from '@/api/seasons';
import { diseaseApi, type DiseaseDetection } from '@/api/disease';
import { confidenceBand, formatDate } from '@/lib/utils';

export function DiseaseScanPage() {
  const { t, i18n } = useTranslation();
  const isSw = i18n.language?.toLowerCase().startsWith('sw') ?? false;

  const [file, setFile] = useState<File | null>(null);
  const [preview, setPreview] = useState<string | null>(null);
  const [cropId, setCropId] = useState<string>('');
  const [seasonId, setSeasonId] = useState<string>('');
  const [downloadingPdf, setDownloadingPdf] = useState(false);
  const [amounts, setAmounts] = useState<Record<number, string>>({});
  const [plannedIndex, setPlannedIndex] = useState<number | null>(null);
  const [submitError, setSubmitError] = useState<string | null>(null);
  const [latest, setLatest] = useState<DiseaseDetection | null>(null);
  const [feedbackVerdict, setFeedbackVerdict] = useState<'correct' | 'incorrect' | null>(null);
  const [correctionText, setCorrectionText] = useState('');
  const [feedbackSent, setFeedbackSent] = useState(false);
  const [feedbackError, setFeedbackError] = useState<string | null>(null);

  const cropsQuery = useQuery({
    queryKey: ['crops'],
    queryFn: () => cropsApi.list(),
  });

  const seasonsQuery = useQuery({
    queryKey: ['seasons'],
    queryFn: () => seasonsApi.list(),
  });

  // Preselect the most relevant season: active first, then planning /
  // harvesting, then the first one available.
  useEffect(() => {
    if (!seasonsQuery.data || seasonId) return;
    const seasons = seasonsQuery.data.data;
    const pick =
      seasons.find((s) => s.status === 'active') ??
      seasons.find((s) => s.status === 'planning' || s.status === 'harvesting') ??
      seasons[0];
    if (pick) setSeasonId(pick.id);
  }, [seasonsQuery.data, seasonId]);

  const historyQuery = useQuery({
    queryKey: ['disease', 'history'],
    queryFn: () => diseaseApi.history(),
  });

  const detectMutation = useMutation({
    mutationFn: () => {
      if (!file) throw new Error('Pick or capture an image first');
      return diseaseApi.detect({
        image: file,
        ...(cropId && { crop_id: cropId }),
        ...(seasonId && { season_id: seasonId }),
      });
    },
    onSuccess: (res) => {
      setLatest(res.data);
      setFile(null);
      setPreview(null);
      setPlannedIndex(null);
      setAmounts({});
      setFeedbackVerdict(null);
      setCorrectionText('');
      setFeedbackSent(false);
      setFeedbackError(null);
      historyQuery.refetch();
    },
    onError: (err: unknown) => {
      const message =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        (err as Error)?.message ??
        'Could not analyse the photo.';
      setSubmitError(message);
    },
  });

  const downloadPdf = async () => {
    if (!latest) return;
    setDownloadingPdf(true);
    try {
      const blob = await diseaseApi.report(latest.id);
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `panda-disease-scan-${latest.id}.pdf`;
      document.body.appendChild(a);
      a.click();
      a.remove();
      URL.revokeObjectURL(url);
    } catch (err) {
      console.error('PDF download failed', err);
    } finally {
      setDownloadingPdf(false);
    }
  };

  const planMutation = useMutation({
    mutationFn: ({ index }: { index: number }) => {
      if (!latest?.season_id) throw new Error('Pick a season first');
      const amount = Number(amounts[index]);
      if (!amount || amount <= 0) throw new Error('Enter a cost amount (KES)');
      return diseaseApi.planTreatment(latest.id, {
        season_id: latest.season_id,
        treatment_index: index,
        amount_kes: amount,
      });
    },
    onSuccess: (_res, { index }) => {
      setPlannedIndex(index);
    },
    onError: (err: unknown) => {
      setSubmitError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          (err as Error)?.message ??
          'Could not add to the season plan.',
      );
    },
  });

  const feedbackMutation = useMutation({
    mutationFn: ({ verdict }: { verdict: 'correct' | 'incorrect' }) => {
      if (!latest) throw new Error('No scan to give feedback on');
      return diseaseApi.feedback(latest.id, {
        verdict,
        ...(correctionText.trim() ? { correction_text: correctionText.trim() } : {}),
      });
    },
    onSuccess: () => {
      setFeedbackVerdict(null);
      setCorrectionText('');
      setFeedbackSent(true);
      setFeedbackError(null);
    },
    onError: (err: unknown) => {
      setFeedbackError(
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          (err as Error)?.message ??
          'Could not send feedback.',
      );
    },
  });

  const handleFileChange = (e: ChangeEvent<HTMLInputElement>) => {
    setSubmitError(null);
    const f = e.target.files?.[0];
    if (!f) {
      setFile(null);
      setPreview(null);
      return;
    }
    setFile(f);
    setPreview(URL.createObjectURL(f));
  };

  const card = latest?.disease_card ?? null;
  const displayName = isSw && card?.name_sw ? card.name_sw : (latest?.top_diagnosis ?? '—');
  const band = latest && latest.confidence !== null ? confidenceBand(latest.confidence) : null;
  const bandLabel =
    band === 'high'
      ? t('disease.confidence_high')
      : band === 'medium'
        ? t('disease.confidence_medium')
        : band === 'low'
          ? t('disease.confidence_low')
          : null;
  const alternates = (latest?.suggestions ?? [])
    .filter((s) => s.name !== latest?.top_diagnosis)
    .slice(0, 2);

  return (
    <div className="space-y-4 max-w-2xl">
      <header>
        <h1 className="text-2xl font-semibold">Disease scan</h1>
        <p className="text-gray-600">
          Take a clear photo of an affected leaf. Panda will suggest the likely
          disease and recommended PCPB-registered treatments.
        </p>
      </header>

      <Card className="print:hidden">
        <CardBody className="space-y-4">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <Label htmlFor="crop_id">Crop (optional)</Label>
              <select
                id="crop_id"
                className="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm"
                value={cropId}
                onChange={(e) => setCropId(e.target.value)}
                disabled={cropsQuery.isLoading}
              >
                <option value="">Choose…</option>
                {cropsQuery.data?.data.map((c) => (
                  <option key={c.id} value={c.id}>{c.name_en}</option>
                ))}
              </select>
            </div>
            <div>
              <Label htmlFor="season_id">Season (adds results to your season records)</Label>
              <select
                id="season_id"
                className="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm"
                value={seasonId}
                onChange={(e) => setSeasonId(e.target.value)}
                disabled={seasonsQuery.isLoading}
              >
                <option value="">No season</option>
                {seasonsQuery.data?.data.map((s) => (
                  <option key={s.id} value={s.id}>
                    {s.crop?.name_en ?? 'Crop'} · {formatDate(s.planting_date)} · {s.status}
                  </option>
                ))}
              </select>
            </div>
          </div>

          <div>
            <Label htmlFor="image">{t('disease.leaf_photo')}</Label>
            <div className="mt-1 rounded-md bg-amber-50 border border-amber-200 p-3 text-sm text-amber-900 space-y-1">
              <div className="font-medium text-amber-950">{t('disease.photo_guide_title')}</div>
              <div>• {t('disease.photo_guide_lighting')}</div>
              <div>• {t('disease.photo_guide_angle')}</div>
              <div>• {t('disease.photo_guide_frame')}</div>
              <div>• {t('disease.photo_guide_still')}</div>
            </div>
            <div className="grid grid-cols-2 gap-2 mt-2">
              <label className="flex items-center justify-center gap-2 h-24 border-2 border-dashed border-gray-300 rounded-md cursor-pointer hover:border-brand-600 hover:bg-brand-50">
                <Camera className="h-5 w-5" />
                <span className="text-sm">Camera</span>
                <input
                  id="image"
                  type="file"
                  accept="image/jpeg,image/png,image/webp"
                  capture="environment"
                  className="hidden"
                  onChange={handleFileChange}
                />
              </label>
              <label className="flex items-center justify-center gap-2 h-24 border-2 border-dashed border-gray-300 rounded-md cursor-pointer hover:border-brand-600 hover:bg-brand-50">
                <Upload className="h-5 w-5" />
                <span className="text-sm">Upload</span>
                <input
                  type="file"
                  accept="image/jpeg,image/png,image/webp"
                  className="hidden"
                  onChange={handleFileChange}
                />
              </label>
            </div>
            {preview && (
              <div className="mt-3">
                <img
                  src={preview}
                  alt="Selected leaf"
                  className="rounded-md max-h-48 border border-gray-200"
                />
                <p className="text-xs text-gray-500 mt-1">{file?.name}</p>
              </div>
            )}
          </div>

          {submitError && <p className="text-sm text-danger-600">{submitError}</p>}

          <Button
            onClick={() => detectMutation.mutate()}
            loading={detectMutation.isPending}
            disabled={!file}
            className="w-full"
          >
            {t('disease.analyse_photo')}
          </Button>
        </CardBody>
      </Card>

      {latest && (
        <Card>
          <CardHeader>
            <div className="flex items-center justify-between w-full">
              <div className="font-semibold">Latest result</div>
              <Button size="sm" variant="secondary" loading={downloadingPdf} onClick={downloadPdf}>
                Download PDF
              </Button>
            </div>
          </CardHeader>
          <CardBody className="space-y-3">
            <div>
              <div className="text-xl font-semibold">{displayName}</div>
              <div className="text-sm text-gray-600">
                {latest.confidence !== null
                  ? `${Math.round(latest.confidence * 100)}% confidence`
                  : 'no confidence reported'}
                {bandLabel ? ` · ${bandLabel}` : ''}{' '}
                · {latest.provider === 'mock' ? 'demo mode' : latest.provider}
              </div>
            </div>

            {card?.severity_classes && (
              <div className="flex items-start gap-2 rounded-md bg-amber-50 border border-amber-200 px-3 py-2 text-sm text-amber-900">
                <span className="font-medium shrink-0">{t('disease.severity')}:</span>
                <span>{card.severity_classes}</span>
              </div>
            )}

            {(card?.symptoms_en || card?.symptoms_sw) && (
              <div>
                <div className="text-sm font-medium mb-1">{t('disease.symptoms')}</div>
                <p className="text-sm text-gray-600">
                  {isSw && card.symptoms_sw ? card.symptoms_sw : card.symptoms_en}
                </p>
              </div>
            )}

            {alternates.length > 0 && (
              <div>
                <div className="text-xs font-medium text-gray-500 mb-1">
                  {t('disease.other_possibilities')}
                </div>
                <ul className="space-y-1">
                  {alternates.map((s, i) => (
                    <li key={i} className="text-xs text-gray-600">
                      {s.name} · {Math.round(s.probability * 100)}%
                    </li>
                  ))}
                </ul>
              </div>
            )}

            {latest.treatments && latest.treatments.length > 0 && (
              <div>
                <div className="text-sm font-medium mb-1">Recommended treatments</div>
                <ul className="space-y-3">
                  {latest.treatments.map((tr, i) => (
                    <li key={i} className="text-sm border border-gray-200 rounded-md p-3">
                      <div className="font-medium">{tr.generic}</div>
                      {tr.pcpb && (
                        <div className="text-gray-600">
                          PCPB-registered: <strong>{tr.pcpb}</strong>
                        </div>
                      )}
                      {tr.application_notes && (
                        <div className="text-gray-600 italic">{tr.application_notes}</div>
                      )}
                      {latest.season_id ? (
                        <div className="mt-2 flex flex-wrap items-center gap-2">
                          <input
                            type="number"
                            min={0}
                            placeholder="Cost (KES)"
                            className="w-28 h-9 rounded-md border border-gray-300 px-2 text-sm"
                            value={amounts[i] ?? ''}
                            onChange={(e) =>
                              setAmounts((prev) => ({ ...prev, [i]: e.target.value }))
                            }
                          />
                          <Button
                            size="sm"
                            loading={planMutation.isPending && plannedIndex !== i}
                            disabled={planMutation.isPending || !amounts[i]}
                            onClick={() => planMutation.mutate({ index: i })}
                          >
                            {plannedIndex === i ? 'Added to season ✓' : 'Add to season costs'}
                          </Button>
                        </div>
                      ) : (
                        <div className="mt-2 text-xs text-gray-500">
                          Pick a season above to record this treatment against your season costs.
                        </div>
                      )}
                    </li>
                  ))}
                </ul>
              </div>
            )}
            {(!latest.treatments || latest.treatments.length === 0) && (
              <p className="text-sm text-gray-500">
                No treatment guidance for this diagnosis yet.
              </p>
            )}

            <div className="border-t border-gray-200 pt-3 mt-3">
              {feedbackSent ? (
                <p className="text-sm text-brand-600">{t('disease.thanks_feedback')}</p>
              ) : (
                <div className="space-y-2">
                  <div className="text-sm font-medium">{t('disease.was_this_helpful')}</div>
                  <div className="flex items-center gap-2">
                    <Button
                      size="sm"
                      variant={feedbackVerdict === 'correct' ? 'primary' : 'secondary'}
                      loading={feedbackMutation.isPending && feedbackVerdict === 'correct'}
                      disabled={feedbackMutation.isPending}
                      onClick={() => {
                        setFeedbackVerdict('correct');
                        feedbackMutation.mutate({ verdict: 'correct' });
                      }}
                    >
                      <ThumbsUp className="h-4 w-4" /> {t('disease.yes')}
                    </Button>
                    <Button
                      size="sm"
                      variant={feedbackVerdict === 'incorrect' ? 'primary' : 'secondary'}
                      disabled={feedbackMutation.isPending}
                      onClick={() => {
                        setFeedbackVerdict('incorrect');
                        setFeedbackError(null);
                      }}
                    >
                      <ThumbsDown className="h-4 w-4" /> {t('disease.no')}
                    </Button>
                  </div>
                  {feedbackVerdict === 'incorrect' && (
                    <div className="space-y-2">
                      <textarea
                        rows={2}
                        placeholder={t('disease.what_did_you_see')}
                        className="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm"
                        value={correctionText}
                        onChange={(e) => setCorrectionText(e.target.value)}
                      />
                      <Button
                        size="sm"
                        loading={feedbackMutation.isPending}
                        disabled={feedbackMutation.isPending}
                        onClick={() => feedbackMutation.mutate({ verdict: 'incorrect' })}
                      >
                        {t('disease.submit_feedback')}
                      </Button>
                    </div>
                  )}
                  {feedbackError && <p className="text-sm text-danger-600">{feedbackError}</p>}
                </div>
              )}
            </div>
          </CardBody>
        </Card>
      )}

      <section>
        <h2 className="text-lg font-medium mb-2">Recent scans</h2>
        {historyQuery.isLoading && <p className="text-gray-500">Loading…</p>}
        {historyQuery.data && historyQuery.data.data.length === 0 && (
          <p className="text-gray-500">No scans yet.</p>
        )}
        <div className="space-y-2">
          {historyQuery.data?.data.map((d) => (
            <Card key={d.id}>
              <CardBody className="flex items-center gap-3">
                <img
                  src={d.image_url}
                  alt={d.top_diagnosis ?? 'scan'}
                  className="h-12 w-12 rounded object-cover bg-gray-100"
                  onError={(e) => {
                    (e.currentTarget as HTMLImageElement).style.display = 'none';
                  }}
                />
                <div className="flex-1">
                  <div className="font-medium">{d.top_diagnosis ?? '—'}</div>
                  <div className="text-xs text-gray-600">
                    {formatDate(d.captured_at)}
                    {d.confidence !== null && ` · ${Math.round(d.confidence * 100)}%`}
                  </div>
                </div>
              </CardBody>
            </Card>
          ))}
        </div>
      </section>
    </div>
  );
}
