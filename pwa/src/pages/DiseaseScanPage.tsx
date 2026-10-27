import { useEffect, useState, type ChangeEvent } from 'react';
import { useMutation, useQuery } from '@tanstack/react-query';
import { Camera, Upload } from 'lucide-react';
import { Button } from '@/components/ui/Button';
import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import { Label } from '@/components/ui/Label';
import { cropsApi } from '@/api/crops';
import { seasonsApi } from '@/api/seasons';
import { diseaseApi, type DiseaseDetection } from '@/api/disease';
import { formatDate } from '@/lib/utils';

export function DiseaseScanPage() {
  const [file, setFile] = useState<File | null>(null);
  const [preview, setPreview] = useState<string | null>(null);
  const [cropId, setCropId] = useState<string>('');
  const [seasonId, setSeasonId] = useState<string>('');
  const [amounts, setAmounts] = useState<Record<number, string>>({});
  const [plannedIndex, setPlannedIndex] = useState<number | null>(null);
  const [submitError, setSubmitError] = useState<string | null>(null);
  const [latest, setLatest] = useState<DiseaseDetection | null>(null);

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

  return (
    <div className="space-y-4 max-w-2xl">
      <header>
        <h1 className="text-2xl font-semibold">Disease scan</h1>
        <p className="text-gray-600">
          Take a clear photo of an affected leaf. Panda will suggest the likely
          disease and recommended PCPB-registered treatments.
        </p>
      </header>

      <Card>
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
            <Label htmlFor="image">Leaf photo</Label>
            <div className="grid grid-cols-2 gap-2">
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
            Analyse photo
          </Button>
        </CardBody>
      </Card>

      {latest && (
        <Card>
          <CardHeader>
            <div className="font-semibold">Latest result</div>
          </CardHeader>
          <CardBody className="space-y-3">
            <div>
              <div className="text-xl font-semibold">{latest.top_diagnosis ?? '—'}</div>
              <div className="text-sm text-gray-600">
                {latest.confidence !== null
                  ? `${Math.round(latest.confidence * 100)}% confidence`
                  : 'no confidence reported'}{' '}
                · {latest.provider === 'mock' ? 'demo mode' : latest.provider}
              </div>
            </div>
            {latest.treatments && latest.treatments.length > 0 && (
              <div>
                <div className="text-sm font-medium mb-1">Recommended treatments</div>
                <ul className="space-y-3">
                  {latest.treatments.map((t, i) => (
                    <li key={i} className="text-sm border border-gray-200 rounded-md p-3">
                      <div className="font-medium">{t.generic}</div>
                      {t.pcpb && (
                        <div className="text-gray-600">
                          PCPB-registered: <strong>{t.pcpb}</strong>
                        </div>
                      )}
                      {t.application_notes && (
                        <div className="text-gray-600 italic">{t.application_notes}</div>
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
