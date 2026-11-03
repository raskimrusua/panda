import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/Button';
import { Card, CardBody } from '@/components/ui/Card';
import { Label } from '@/components/ui/Label';
import { offlineQueue } from '@/offline/queue';
import {
  OFFLINE_TREE,
  findTreeCrop,
  walkTree,
  type OfflineTreeCrop,
  type OfflineTreeOption,
  type OfflineTreeQuestion,
} from '@/lib/offline-tree';

/**
 * Phase B3 pilot — offline symptom-tree scan. Walks a hand-authored tree
 * for the 4-crop smoke set, shows the low-confidence guide result, and
 * queues the result for sync (idempotent client_id) instead of calling
 * the API. Always rendered alongside the photo scan as the no-connection
 * fallback.
 */
export function OfflineScanCard() {
  const { t, i18n } = useTranslation();
  const isSw = i18n.language?.toLowerCase().startsWith('sw') ?? false;

  const [crop, setCrop] = useState<OfflineTreeCrop | null>(null);
  const [question, setQuestion] = useState<OfflineTreeQuestion | null>(null);
  const [diagnosis, setDiagnosis] = useState<string | null>(null);
  const [queued, setQueued] = useState(false);

  const pickCrop = (slug: string) => {
    const c = findTreeCrop(slug);
    setCrop(c ?? null);
    setQuestion(c ? (c.questions.find((q) => q.id === c.first) ?? null) : null);
    setDiagnosis(null);
    setQueued(false);
  };

  const choose = async (option: OfflineTreeOption) => {
    if (!crop || !question) return;
    const step = walkTree(crop, option);
    if (step.nextQuestion) {
      setQuestion(step.nextQuestion);
      return;
    }
    setQuestion(null);
    setDiagnosis(step.diagnosis ?? 'uncertain');
    if (step.diagnosis && step.diagnosis !== 'uncertain') {
      await offlineQueue.enqueue({
        type: 'offline_scan',
        url: '/disease/offline-sync',
        method: 'POST',
        payload: {
          top_diagnosis: step.diagnosis,
          answers: { crop: crop.slug, flow: 'offline-tree-pilot' },
        },
        invalidate_keys: [['disease', 'history']],
      });
    }
    setQueued(true);
  };

  const label = (en: string, sw: string) => (isSw ? sw : en);

  return (
    <Card className="print:hidden">
      <CardBody className="space-y-3">
        <div>
          <div className="font-semibold">{t('disease.offline_title')}</div>
          <p className="text-sm text-gray-600">{t('disease.offline_intro')}</p>
        </div>

        <div>
          <Label htmlFor="offline_crop">Crop</Label>
          <select
            id="offline_crop"
            className="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm"
            value={crop?.slug ?? ''}
            onChange={(e) => pickCrop(e.target.value)}
          >
            <option value="">Choose…</option>
            {OFFLINE_TREE.map((c) => (
              <option key={c.slug} value={c.slug}>
                {label(c.name_en, c.name_sw)}
              </option>
            ))}
          </select>
        </div>

        {question && crop && (
          <div className="space-y-2">
            <div className="text-sm font-medium">{label(question.text_en, question.text_sw)}</div>
            <div className="grid grid-cols-1 gap-2">
              {question.options.map((o) => (
                <Button
                  key={o.label_en}
                  size="sm"
                  variant="secondary"
                  className="justify-start text-left"
                  onClick={() => void choose(o)}
                >
                  {label(o.label_en, o.label_sw)}
                </Button>
              ))}
            </div>
          </div>
        )}

        {diagnosis && (
          <div className="rounded-md bg-amber-50 border border-amber-200 px-3 py-2 text-sm text-amber-900 space-y-1">
            {diagnosis === 'uncertain' ? (
              <div>{t('disease.offline_uncertain')}</div>
            ) : (
              <>
                <div className="font-medium">{t('disease.offline_confidence')}</div>
                <div>
                  {crop
                    ? label(crop.name_en, crop.name_sw)
                    : ''}{' '}
                  — {diagnosis}
                </div>
              </>
            )}
            {queued && diagnosis !== 'uncertain' && (
              <div className="text-amber-950 font-medium">{t('disease.offline_queued')}</div>
            )}
          </div>
        )}
      </CardBody>
    </Card>
  );
}
