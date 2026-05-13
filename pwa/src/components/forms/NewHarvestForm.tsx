import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { harvestsApi } from '@/api/harvests';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Label, FieldError } from '@/components/ui/Label';
import { HelpTooltip } from '@/components/ui/HelpTooltip';
import { newHarvestSchema, type NewHarvestValues } from '@/lib/zodSchemas';
import { offlineQueue } from '@/offline/queue';
import { useOfflineQueue } from '@/offline/useOfflineQueue';
import { isNetworkError } from './LogActivityDoneForm';

export interface NewHarvestFormProps {
  seasonId: string;
  onDone: () => void;
}

function buildPayload(seasonId: string, v: NewHarvestValues): Record<string, unknown> {
  return {
    season_id: seasonId,
    harvested_at: v.harvested_at,
    quantity_kg: v.quantity_kg,
    ...(v.sold_quantity_kg !== undefined && { sold_quantity_kg: v.sold_quantity_kg }),
    ...(v.unit_price_kes !== undefined && { unit_price_kes: v.unit_price_kes }),
    ...(v.buyer_name && { buyer_name: v.buyer_name }),
    ...(v.notes && { notes: v.notes }),
  };
}

export function NewHarvestForm({ seasonId, onDone }: NewHarvestFormProps) {
  const { t } = useTranslation();
  const qc = useQueryClient();
  const { online, refresh } = useOfflineQueue();
  const [submitError, setSubmitError] = useState<string | null>(null);
  const [savedOffline, setSavedOffline] = useState(false);

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<NewHarvestValues>({
    resolver: zodResolver(newHarvestSchema),
    defaultValues: {
      harvested_at: new Date().toISOString().slice(0, 10),
      quantity_kg: 0,
      sold_quantity_kg: 0,
      unit_price_kes: undefined,
      buyer_name: '',
      notes: '',
    },
  });

  const enqueueOffline = async (values: NewHarvestValues) => {
    await offlineQueue.enqueue({
      type: 'log_harvest',
      url: '/harvests',
      method: 'POST',
      payload: buildPayload(seasonId, values),
      invalidate_keys: [
        ['seasons', seasonId, 'harvests'],
        ['seasons', seasonId],
      ],
    });
    await refresh();
    setSavedOffline(true);
    setTimeout(onDone, 1200);
  };

  const mutation = useMutation({
    mutationFn: async (values: NewHarvestValues) => {
      if (!online) {
        await enqueueOffline(values);
        return null;
      }
      return harvestsApi.create(buildPayload(seasonId, values) as unknown as Parameters<typeof harvestsApi.create>[0]);
    },
    onSuccess: (res) => {
      if (res === null) return;
      qc.invalidateQueries({ queryKey: ['seasons', seasonId, 'harvests'] });
      qc.invalidateQueries({ queryKey: ['seasons', seasonId] });
      onDone();
    },
    onError: async (err: unknown, values) => {
      if (isNetworkError(err)) {
        await enqueueOffline(values);
        return;
      }
      const message =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        'Could not save the harvest.';
      setSubmitError(message);
    },
  });

  return (
    <form onSubmit={handleSubmit((v) => mutation.mutate(v))} className="space-y-3">
      <div>
        <Label htmlFor="harvested_at">{t('log_forms.date')}</Label>
        <Input
          id="harvested_at"
          type="date"
          max={new Date().toISOString().slice(0, 10)}
          invalid={!!errors.harvested_at}
          {...register('harvested_at')}
        />
        <FieldError message={errors.harvested_at?.message} />
      </div>

      <div className="grid grid-cols-2 gap-3">
        <div>
          <div className="flex items-center gap-1">
            <Label htmlFor="quantity_kg">{t('log_forms.picked_kg')}</Label>
            <HelpTooltip title={t('help.harvest_picked_title')} body={t('help.harvest_picked_body')} />
          </div>
          <Input
            id="quantity_kg"
            type="number"
            step="0.01"
            min="0.01"
            invalid={!!errors.quantity_kg}
            {...register('quantity_kg', { valueAsNumber: true })}
          />
          <FieldError message={errors.quantity_kg?.message} />
        </div>
        <div>
          <Label htmlFor="sold_quantity_kg">{t('log_forms.sold_kg')}</Label>
          <Input
            id="sold_quantity_kg"
            type="number"
            step="0.01"
            min="0"
            invalid={!!errors.sold_quantity_kg}
            {...register('sold_quantity_kg', { valueAsNumber: true })}
          />
          <FieldError message={errors.sold_quantity_kg?.message} />
        </div>
      </div>

      <div className="grid grid-cols-2 gap-3">
        <div>
          <div className="flex items-center gap-1">
            <Label htmlFor="unit_price_kes">{t('log_forms.price_per_kg')}</Label>
            <HelpTooltip title={t('help.harvest_price_title')} body={t('help.harvest_price_body')} />
          </div>
          <Input
            id="unit_price_kes"
            type="number"
            step="0.01"
            min="0"
            invalid={!!errors.unit_price_kes}
            {...register('unit_price_kes', { valueAsNumber: true })}
          />
          <FieldError message={errors.unit_price_kes?.message} />
        </div>
        <div>
          <Label htmlFor="buyer_name">{t('log_forms.buyer_optional')}</Label>
          <Input
            id="buyer_name"
            placeholder="e.g. Marikiti broker"
            {...register('buyer_name')}
          />
        </div>
      </div>

      <div>
        <Label htmlFor="notes">{t('log_forms.notes_optional')}</Label>
        <textarea
          id="notes"
          rows={2}
          className="flex w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
          {...register('notes')}
        />
      </div>

      {submitError && <p className="text-sm text-danger-600" role="alert">{submitError}</p>}
      {savedOffline && (
        <p className="text-sm text-warn-600" role="status">{t('log_forms.saved_offline')}</p>
      )}

      <div className="flex justify-end gap-2 pt-1">
        <Button type="button" variant="secondary" onClick={onDone}>{t('common.cancel')}</Button>
        <Button type="submit" loading={mutation.isPending}>{t('log_forms.save_harvest')}</Button>
      </div>
    </form>
  );
}
