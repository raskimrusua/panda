import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { harvestsApi, type HarvestLog } from '@/api/harvests';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Label, FieldError } from '@/components/ui/Label';
import { HelpTooltip } from '@/components/ui/HelpTooltip';
import { newHarvestSchema, type NewHarvestValues } from '@/lib/zodSchemas';

export interface EditHarvestFormProps {
  harvest: HarvestLog;
  seasonId: string;
  onDone: () => void;
}

/**
 * Edit form for an existing HarvestLog. Same pattern as EditCostForm:
 * mirrors the create form's field shape, PATCHes directly without offline
 * queue, invalidates the season's harvest query on success.
 */
export function EditHarvestForm({ harvest, seasonId, onDone }: EditHarvestFormProps) {
  const { t } = useTranslation();
  const qc = useQueryClient();
  const [submitError, setSubmitError] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<NewHarvestValues>({
    resolver: zodResolver(newHarvestSchema),
    defaultValues: {
      harvested_at: harvest.harvested_at,
      quantity_kg: Number(harvest.quantity_kg),
      sold_quantity_kg: Number(harvest.sold_quantity_kg),
      unit_price_kes: harvest.unit_price_kes !== null ? Number(harvest.unit_price_kes) : undefined,
      buyer_name: harvest.buyer_name ?? '',
      notes: harvest.notes ?? '',
    },
  });

  const mutation = useMutation({
    mutationFn: (values: NewHarvestValues) => {
      const payload: Record<string, unknown> = {
        harvested_at: values.harvested_at,
        quantity_kg: values.quantity_kg,
        ...(values.sold_quantity_kg !== undefined && { sold_quantity_kg: values.sold_quantity_kg }),
        ...(values.unit_price_kes !== undefined && { unit_price_kes: values.unit_price_kes }),
        ...(values.buyer_name && { buyer_name: values.buyer_name }),
        ...(values.notes && { notes: values.notes }),
      };
      return harvestsApi.update(harvest.id, payload as Parameters<typeof harvestsApi.update>[1]);
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['seasons', seasonId, 'harvests'] });
      onDone();
    },
    onError: (err: unknown) => {
      const message =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        'Could not save the changes.';
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

      <div className="flex justify-end gap-2 pt-1">
        <Button type="button" variant="secondary" onClick={onDone}>{t('common.cancel')}</Button>
        <Button type="submit" loading={mutation.isPending}>{t('common.save')}</Button>
      </div>
    </form>
  );
}
