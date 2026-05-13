import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { harvestsApi } from '@/api/harvests';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Label, FieldError } from '@/components/ui/Label';
import { newHarvestSchema, type NewHarvestValues } from '@/lib/zodSchemas';

export interface NewHarvestFormProps {
  seasonId: string;
  onDone: () => void;
}

export function NewHarvestForm({ seasonId, onDone }: NewHarvestFormProps) {
  const qc = useQueryClient();
  const [submitError, setSubmitError] = useState<string | null>(null);

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

  const mutation = useMutation({
    mutationFn: (values: NewHarvestValues) =>
      harvestsApi.create({
        season_id: seasonId,
        harvested_at: values.harvested_at,
        quantity_kg: values.quantity_kg,
        ...(values.sold_quantity_kg !== undefined && {
          sold_quantity_kg: values.sold_quantity_kg,
        }),
        ...(values.unit_price_kes !== undefined && { unit_price_kes: values.unit_price_kes }),
        ...(values.buyer_name && { buyer_name: values.buyer_name }),
        ...(values.notes && { notes: values.notes }),
      }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['seasons', seasonId, 'harvests'] });
      qc.invalidateQueries({ queryKey: ['seasons', seasonId] });
      onDone();
    },
    onError: (err: unknown) => {
      const message =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        'Could not save the harvest.';
      setSubmitError(message);
    },
  });

  return (
    <form onSubmit={handleSubmit((v) => mutation.mutate(v))} className="space-y-3">
      <div>
        <Label htmlFor="harvested_at">Date</Label>
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
          <Label htmlFor="quantity_kg">Picked (kg)</Label>
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
          <Label htmlFor="sold_quantity_kg">Sold (kg)</Label>
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
          <Label htmlFor="unit_price_kes">Price/kg (KES)</Label>
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
          <Label htmlFor="buyer_name">Buyer (optional)</Label>
          <Input
            id="buyer_name"
            placeholder="e.g. Marikiti broker"
            {...register('buyer_name')}
          />
        </div>
      </div>

      <div>
        <Label htmlFor="notes">Notes (optional)</Label>
        <textarea
          id="notes"
          rows={2}
          className="flex w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
          {...register('notes')}
        />
      </div>

      {submitError && <p className="text-sm text-danger-600" role="alert">{submitError}</p>}

      <div className="flex justify-end gap-2 pt-1">
        <Button type="button" variant="secondary" onClick={onDone}>Cancel</Button>
        <Button type="submit" loading={mutation.isPending}>Save harvest</Button>
      </div>
    </form>
  );
}
