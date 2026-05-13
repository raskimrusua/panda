import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslation } from 'react-i18next';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Label, FieldError } from '@/components/ui/Label';
import { Card, CardBody } from '@/components/ui/Card';
import { cropsApi } from '@/api/crops';
import { seasonsApi } from '@/api/seasons';
import { newSeasonSchema, type NewSeasonValues } from '@/lib/zodSchemas';

export function NewSeasonPage() {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [submitError, setSubmitError] = useState<string | null>(null);

  const cropsQuery = useQuery({
    queryKey: ['crops'],
    queryFn: () => cropsApi.list(),
  });

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<NewSeasonValues>({
    resolver: zodResolver(newSeasonSchema),
    defaultValues: {
      crop_id: '',
      acreage: 1,
      planting_date: new Date().toISOString().slice(0, 10),
      irrigation_type: 'rainfed',
      status: 'planning',
    },
  });

  const createMutation = useMutation({
    mutationFn: (values: NewSeasonValues) => seasonsApi.create(values),
    onSuccess: (res) => {
      queryClient.invalidateQueries({ queryKey: ['seasons'] });
      navigate(`/seasons/${res.data.id}`);
    },
    onError: (err: unknown) => {
      const message =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        'Could not create the season.';
      setSubmitError(message);
    },
  });

  const onSubmit = (values: NewSeasonValues) => {
    setSubmitError(null);
    createMutation.mutate(values);
  };

  return (
    <div className="max-w-2xl space-y-4">
      <header>
        <h1 className="text-2xl font-semibold">{t('seasons.new_title')}</h1>
        <p className="text-gray-600">{t('seasons.new_subtitle')}</p>
      </header>

      <Card>
        <CardBody>
          <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
            <div>
              <Label htmlFor="crop_id">{t('seasons.crop')}</Label>
              <select
                id="crop_id"
                className="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                disabled={cropsQuery.isLoading}
                {...register('crop_id')}
              >
                <option value="">
                  {cropsQuery.isLoading ? t('common.loading') : t('auth.choose')}
                </option>
                {cropsQuery.data?.data.map((c) => (
                  <option key={c.id} value={c.id}>
                    {c.name_en} ({c.name_sw})
                  </option>
                ))}
              </select>
              <FieldError message={errors.crop_id?.message} />
            </div>

            <div className="grid grid-cols-2 gap-3">
              <div>
                <Label htmlFor="acreage">{t('seasons.acreage')}</Label>
                <Input
                  id="acreage"
                  type="number"
                  step="0.01"
                  min="0.01"
                  invalid={!!errors.acreage}
                  {...register('acreage', { valueAsNumber: true })}
                />
                <FieldError message={errors.acreage?.message} />
              </div>
              <div>
                <Label htmlFor="planting_date">{t('seasons.planting_date')}</Label>
                <Input
                  id="planting_date"
                  type="date"
                  invalid={!!errors.planting_date}
                  {...register('planting_date')}
                />
                <FieldError message={errors.planting_date?.message} />
              </div>
            </div>

            <div>
              <Label htmlFor="irrigation_type">{t('seasons.irrigation')}</Label>
              <select
                id="irrigation_type"
                className="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm"
                {...register('irrigation_type')}
              >
                <option value="rainfed">{t('seasons.rainfed')}</option>
                <option value="drip">{t('seasons.drip')}</option>
                <option value="furrow">{t('seasons.furrow')}</option>
                <option value="greenhouse">{t('seasons.greenhouse')}</option>
              </select>
            </div>

            {submitError && (
              <p className="text-sm text-danger-600" role="alert">
                {submitError}
              </p>
            )}

            <div className="flex justify-end gap-2 pt-2">
              <Button type="button" variant="secondary" onClick={() => navigate('/seasons')}>
                {t('common.cancel')}
              </Button>
              <Button type="submit" loading={createMutation.isPending}>
                {t('seasons.create_season')}
              </Button>
            </div>
          </form>
        </CardBody>
      </Card>
    </div>
  );
}
