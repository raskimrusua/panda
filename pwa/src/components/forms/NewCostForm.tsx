import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { costsApi } from '@/api/costs';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Label, FieldError } from '@/components/ui/Label';
import { newCostSchema, type NewCostValues } from '@/lib/zodSchemas';
import { offlineQueue } from '@/offline/queue';
import { useOfflineQueue } from '@/offline/useOfflineQueue';
import { isNetworkError } from './LogActivityDoneForm';

export interface NewCostFormProps {
  seasonId: string;
  onDone: () => void;
}

export function NewCostForm({ seasonId, onDone }: NewCostFormProps) {
  const { t } = useTranslation();
  const qc = useQueryClient();
  const { online, refresh } = useOfflineQueue();
  const [submitError, setSubmitError] = useState<string | null>(null);
  const [savedOffline, setSavedOffline] = useState(false);

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<NewCostValues>({
    resolver: zodResolver(newCostSchema),
    defaultValues: {
      category: 'seed',
      description: '',
      amount_kes: 0,
      incurred_at: new Date().toISOString().slice(0, 10),
      supplier_name: '',
    },
  });

  const enqueueOffline = async (values: NewCostValues) => {
    await offlineQueue.enqueue({
      type: 'log_cost',
      url: '/costs',
      method: 'POST',
      payload: { season_id: seasonId, ...values },
      invalidate_keys: [['seasons', seasonId, 'costs']],
    });
    await refresh();
    setSavedOffline(true);
    setTimeout(onDone, 1200);
  };

  const mutation = useMutation({
    mutationFn: async (values: NewCostValues) => {
      if (!online) {
        await enqueueOffline(values);
        return null;
      }
      return costsApi.create({ season_id: seasonId, ...values });
    },
    onSuccess: (res) => {
      if (res === null) return;
      qc.invalidateQueries({ queryKey: ['seasons', seasonId, 'costs'] });
      onDone();
    },
    onError: async (err: unknown, values) => {
      if (isNetworkError(err)) {
        await enqueueOffline(values);
        return;
      }
      const message =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        'Could not save the cost.';
      setSubmitError(message);
    },
  });

  return (
    <form onSubmit={handleSubmit((v) => mutation.mutate(v))} className="space-y-3">
      <div>
        <Label htmlFor="category">{t('log_forms.category')}</Label>
        <select
          id="category"
          className="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm"
          {...register('category')}
        >
          <option value="seed">Seed</option>
          <option value="fertiliser">Fertiliser</option>
          <option value="chemical">Chemical / pesticide</option>
          <option value="labour">Labour</option>
          <option value="equipment">Equipment</option>
          <option value="transport">Transport</option>
          <option value="other">Other</option>
        </select>
      </div>

      <div>
        <Label htmlFor="description">{t('log_forms.description')}</Label>
        <Input
          id="description"
          placeholder="e.g. Tylka F1 200g pack"
          invalid={!!errors.description}
          {...register('description')}
        />
        <FieldError message={errors.description?.message} />
      </div>

      <div className="grid grid-cols-2 gap-3">
        <div>
          <Label htmlFor="amount_kes">{t('log_forms.amount_kes')}</Label>
          <Input
            id="amount_kes"
            type="number"
            step="0.01"
            min="0.01"
            invalid={!!errors.amount_kes}
            {...register('amount_kes', { valueAsNumber: true })}
          />
          <FieldError message={errors.amount_kes?.message} />
        </div>
        <div>
          <Label htmlFor="incurred_at">{t('log_forms.date')}</Label>
          <Input
            id="incurred_at"
            type="date"
            max={new Date().toISOString().slice(0, 10)}
            invalid={!!errors.incurred_at}
            {...register('incurred_at')}
          />
          <FieldError message={errors.incurred_at?.message} />
        </div>
      </div>

      <div>
        <Label htmlFor="supplier_name">{t('log_forms.supplier_optional')}</Label>
        <Input
          id="supplier_name"
          placeholder="e.g. Mwea Agrovet Centre"
          {...register('supplier_name')}
        />
      </div>

      {submitError && <p className="text-sm text-danger-600" role="alert">{submitError}</p>}
      {savedOffline && (
        <p className="text-sm text-warn-600" role="status">{t('log_forms.saved_offline')}</p>
      )}

      <div className="flex justify-end gap-2 pt-1">
        <Button type="button" variant="secondary" onClick={onDone}>{t('common.cancel')}</Button>
        <Button type="submit" loading={mutation.isPending}>{t('log_forms.save_cost')}</Button>
      </div>
    </form>
  );
}
