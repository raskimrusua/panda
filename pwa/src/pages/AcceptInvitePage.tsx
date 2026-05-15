import { useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslation } from 'react-i18next';
import { teamApi } from '@/api/team';
import { tokenStore } from '@/api/client';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Label, FieldError } from '@/components/ui/Label';
import { LanguageSwitcher } from '@/components/LanguageSwitcher';
import { acceptInvitationSchema, type AcceptInvitationValues } from '@/lib/zodSchemas';

export function AcceptInvitePage() {
  const { t } = useTranslation();
  const [params] = useSearchParams();
  const token = params.get('token') ?? '';
  const [submitError, setSubmitError] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<AcceptInvitationValues>({
    resolver: zodResolver(acceptInvitationSchema),
    defaultValues: { name: '', password: '', password_confirmation: '' },
  });

  const onSubmit = async (values: AcceptInvitationValues) => {
    setSubmitError(null);
    try {
      const res = await teamApi.accept(token, values);
      tokenStore.set(res.token);
      // Hard reload so AuthProvider rehydrates with the new session.
      window.location.replace('/');
    } catch (err: unknown) {
      const e = err as { response?: { status?: number; data?: { message?: string } } };
      if (e?.response?.status === 410) {
        setSubmitError(t('team.invite_expired'));
      } else {
        setSubmitError(e?.response?.data?.message ?? t('team.accept_failed'));
      }
    }
  };

  if (!token) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4">
        <div className="w-full max-w-md text-center space-y-4">
          <h1 className="text-2xl font-semibold text-danger-600">{t('team.invite_invalid')}</h1>
          <p className="text-gray-600">{t('team.invite_invalid_body')}</p>
          <Link to="/login" className="text-brand-700 hover:underline font-medium">
            {t('auth.continue_to_signin')}
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4">
      <div className="w-full max-w-md">
        <div className="flex justify-end mb-2">
          <LanguageSwitcher />
        </div>
        <div className="text-center mb-6">
          <h1 className="text-3xl font-semibold text-brand-700">Panda</h1>
          <p className="mt-1 text-gray-600">{t('team.accept_title')}</p>
        </div>

        <form
          onSubmit={handleSubmit(onSubmit)}
          className="rounded-lg bg-white p-6 shadow-sm border border-gray-200 space-y-4"
        >
          <p className="text-sm text-gray-600">{t('team.accept_subtitle')}</p>
          <div>
            <Label htmlFor="name">{t('auth.your_name')}</Label>
            <Input id="name" autoComplete="name" {...register('name')} />
            <FieldError message={errors.name?.message} />
          </div>
          <div>
            <Label htmlFor="password">{t('auth.password')}</Label>
            <Input id="password" type="password" autoComplete="new-password" {...register('password')} />
            <FieldError message={errors.password?.message} />
          </div>
          <div>
            <Label htmlFor="password_confirmation">{t('auth.confirm_password')}</Label>
            <Input
              id="password_confirmation"
              type="password"
              autoComplete="new-password"
              {...register('password_confirmation')}
            />
            <FieldError message={errors.password_confirmation?.message} />
          </div>
          {submitError && <p className="text-sm text-danger-600">{submitError}</p>}
          <Button type="submit" loading={isSubmitting} className="w-full">
            {t('team.accept_button')}
          </Button>
        </form>
      </div>
    </div>
  );
}
