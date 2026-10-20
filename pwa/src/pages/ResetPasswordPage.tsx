import { useState } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslation } from 'react-i18next';
import { authApi } from '@/api/auth';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Label, FieldError } from '@/components/ui/Label';
import { LanguageSwitcher } from '@/components/LanguageSwitcher';
import { resetPasswordSchema, type ResetPasswordValues } from '@/lib/zodSchemas';

export function ResetPasswordPage() {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const [params] = useSearchParams();
  const [submitError, setSubmitError] = useState<string | null>(null);

  const token = params.get('token') ?? '';
  const email = params.get('email') ?? '';

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<ResetPasswordValues>({
    resolver: zodResolver(resetPasswordSchema),
    defaultValues: { token, email, password: '', password_confirmation: '' },
  });

  const onSubmit = async (values: ResetPasswordValues) => {
    setSubmitError(null);
    try {
      await authApi.resetPassword(values);
      navigate('/login?reset=success', { replace: true });
    } catch (err: unknown) {
      const message =
        (err as { response?: { data?: { errors?: { email?: string[] }; message?: string } } })
          ?.response?.data?.errors?.email?.[0] ??
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        t('auth.reset_failed');
      setSubmitError(message);
    }
  };

  if (!token || !email) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4">
        <div className="w-full max-w-md text-center space-y-4">
          <h1 className="text-2xl font-semibold text-danger-600">{t('auth.reset_link_invalid')}</h1>
          <p className="text-gray-600">{t('auth.reset_link_invalid_body')}</p>
          <Link to="/forgot-password" className="text-brand-700 hover:underline font-medium">
            {t('auth.request_new_link')}
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
          <p className="mt-1 text-gray-600">{t('auth.reset_title')}</p>
        </div>

        <form
          onSubmit={handleSubmit(onSubmit)}
          className="rounded-lg bg-white p-6 shadow-sm border border-gray-200 space-y-4"
        >
          <input type="hidden" {...register('token')} />
          <div>
            <Label htmlFor="email">{t('auth.email')}</Label>
            <Input id="email" type="email" readOnly {...register('email')} />
            <FieldError message={errors.email?.message} />
          </div>
          <div>
            <Label htmlFor="password">{t('auth.new_password')}</Label>
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
            {t('auth.set_new_password')}
          </Button>
        </form>
      </div>
    </div>
  );
}
