import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { activitiesApi } from '@/api/activities';
import { Button } from '@/components/ui/Button';
import { Label, FieldError } from '@/components/ui/Label';
import { logActivityDoneSchema, type LogActivityDoneValues } from '@/lib/zodSchemas';

export interface LogActivityDoneFormProps {
  activityId: string;
  seasonId: string;
  onDone: () => void;
}

export function LogActivityDoneForm({ activityId, seasonId, onDone }: LogActivityDoneFormProps) {
  const qc = useQueryClient();
  const [submitError, setSubmitError] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<LogActivityDoneValues>({
    resolver: zodResolver(logActivityDoneSchema),
    defaultValues: { completion_notes: '' },
  });

  const mutation = useMutation({
    mutationFn: (values: LogActivityDoneValues) => activitiesApi.logDone(activityId, values),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['seasons', seasonId, 'timeline'] });
      onDone();
    },
    onError: (err: unknown) => {
      const message =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        'Could not save.';
      setSubmitError(message);
    },
  });

  return (
    <form onSubmit={handleSubmit((v) => mutation.mutate(v))} className="space-y-4">
      <div>
        <Label htmlFor="completion_notes">Notes (optional)</Label>
        <textarea
          id="completion_notes"
          rows={3}
          className="flex w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
          placeholder="What did you do?"
          {...register('completion_notes')}
        />
        <FieldError message={errors.completion_notes?.message} />
      </div>

      {submitError && (
        <p className="text-sm text-danger-600" role="alert">{submitError}</p>
      )}

      <div className="flex justify-end gap-2">
        <Button type="button" variant="secondary" onClick={onDone}>Cancel</Button>
        <Button type="submit" loading={mutation.isPending}>Mark done</Button>
      </div>
    </form>
  );
}
