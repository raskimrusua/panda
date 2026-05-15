import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { costsApi, type CostEntry } from '@/api/costs';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Label, FieldError } from '@/components/ui/Label';
import { HelpTooltip } from '@/components/ui/HelpTooltip';
import { newCostSchema, type NewCostValues } from '@/lib/zodSchemas';

export interface EditCostFormProps {
  cost: CostEntry;
  seasonId: string;
  onDone: () => void;
}

/**
 * Edit form for an existing CostEntry. Mirrors NewCostForm's field shape
 * + validation but PATCHes the existing row directly (no offline-queue
 * indirection — edits are uncommon enough that requiring connectivity is
 * acceptable; if the user is offline, the mutation surfaces a network
 * error and they retry).
 */
export function EditCostForm({ cost, seasonId, onDone }: EditCostFormProps) {
  const { t } = useTranslation();
  const qc = useQueryClient();
  const [submitError, setSubmitError] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<NewCostValues>({
    resolver: zodResolver(newCostSchema),
    defaultValues: {
      category: cost.category,
      description: cost.description,
      amount_kes: Number(cost.amount_kes),
      incurred_at: cost.incurred_at,
      supplier_name: cost.supplier_name ?? '',
    },
  });

  const mutation = useMutation({
    mutationFn: (values: NewCostValues) => costsApi.update(cost.id, values),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['seasons', seasonId, 'costs'] });
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
          invalid={!!errors.description}
          {...register('description')}
        />
        <FieldError message={errors.description?.message} />
      </div>

      <div className="grid grid-cols-2 gap-3">
        <div>
          <div className="flex items-center gap-1">
            <Label htmlFor="amount_kes">{t('log_forms.amount_kes')}</Label>
            <HelpTooltip title={t('help.cost_amount_title')} body={t('help.cost_amount_body')} />
          </div>
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
          {...register('supplier_name')}
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
