import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { activitiesApi } from '@/api/activities';
import { Button } from '@/components/ui/Button';
import { Label, FieldError } from '@/components/ui/Label';
import { logActivityDoneSchema, type LogActivityDoneValues } from '@/lib/zodSchemas';
import { offlineQueue } from '@/offline/queue';
import { useOfflineQueue } from '@/offline/useOfflineQueue';

export interface LogActivityDoneFormProps {
  activityId: string;
  seasonId: string;
  onDone: () => void;
}

export function isNetworkError(err: unknown): boolean {
  const e = err as { code?: string; response?: { status?: number } };
  // axios sets code='ERR_NETWORK' when there's no response (offline / DNS / CORS-blocked).
  // Also treat 5xx + 408 + 429 as transient enqueue-worthy.
  if (e.code === 'ERR_NETWORK' || !e.response) return true;
  const s = e.response?.status ?? 0;
  return s >= 500 || s === 408 || s === 429;
}

export function LogActivityDoneForm({ activityId, seasonId, onDone }: LogActivityDoneFormProps) {
  const { t } = useTranslation();
  const qc = useQueryClient();
  const { online, refresh } = useOfflineQueue();
  const [submitError, setSubmitError] = useState<string | null>(null);
  const [savedOffline, setSavedOffline] = useState(false);

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<LogActivityDoneValues>({
    resolver: zodResolver(logActivityDoneSchema),
    defaultValues: { completion_notes: '' },
  });

  const enqueueOffline = async (values: LogActivityDoneValues) => {
    await offlineQueue.enqueue({
      type: 'log_activity_done',
      url: `/activities/${activityId}/log-done`,
      method: 'POST',
      payload: { ...values },
      invalidate_keys: [['seasons', seasonId, 'timeline']],
    });
    await refresh();
    setSavedOffline(true);
    setTimeout(onDone, 1200);
  };

  const mutation = useMutation({
    mutationFn: async (values: LogActivityDoneValues) => {
      if (!online) {
        await enqueueOffline(values);
        return null;
      }
      return activitiesApi.logDone(activityId, values);
    },
    onSuccess: (res) => {
      if (res === null) return;
      qc.invalidateQueries({ queryKey: ['seasons', seasonId, 'timeline'] });
      onDone();
    },
    onError: async (err: unknown, values) => {
      if (isNetworkError(err)) {
        await enqueueOffline(values);
        return;
      }
      const message =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        'Could not save.';
      setSubmitError(message);
    },
  });

  return (
    <form onSubmit={handleSubmit((v) => mutation.mutate(v))} className="space-y-4">
      <div>
        <Label htmlFor="completion_notes">{t('log_forms.notes_optional')}</Label>
        <textarea
          id="completion_notes"
          rows={3}
          className="flex w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
          placeholder={t('log_forms.what_did_you_do')}
          {...register('completion_notes')}
        />
        <FieldError message={errors.completion_notes?.message} />
      </div>

      {submitError && <p className="text-sm text-danger-600" role="alert">{submitError}</p>}
      {savedOffline && (
        <p className="text-sm text-warn-600" role="status">{t('log_forms.saved_offline')}</p>
      )}

      <div className="flex justify-end gap-2">
        <Button type="button" variant="secondary" onClick={onDone}>{t('common.cancel')}</Button>
        <Button type="submit" loading={mutation.isPending}>{t('seasons.mark_done')}</Button>
      </div>
    </form>
  );
}
