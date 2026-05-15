import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslation } from 'react-i18next';
import { authApi } from '@/api/auth';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Label, FieldError } from '@/components/ui/Label';
import { LanguageSwitcher } from '@/components/LanguageSwitcher';
import { forgotPasswordSchema, type ForgotPasswordValues } from '@/lib/zodSchemas';

export function ForgotPasswordPage() {
  const { t } = useTranslation();
  const [sent, setSent] = useState(false);
  const [submitError, setSubmitError] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<ForgotPasswordValues>({
    resolver: zodResolver(forgotPasswordSchema),
    defaultValues: { email: '' },
  });

  const onSubmit = async (values: ForgotPasswordValues) => {
    setSubmitError(null);
    try {
      await authApi.forgotPassword(values.email);
      setSent(true);
    } catch (err: unknown) {
      const message =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        t('auth.forgot_failed');
      setSubmitError(message);
    }
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4">
      <div className="w-full max-w-md">
        <div className="flex justify-end mb-2">
          <LanguageSwitcher />
        </div>
        <div className="text-center mb-6">
          <h1 className="text-3xl font-semibold text-brand-700">Panda</h1>
          <p className="mt-1 text-gray-600">{t('auth.forgot_title')}</p>
        </div>

        {sent ? (
          <div className="rounded-lg bg-white p-6 shadow-sm border border-gray-200 space-y-4 text-center">
            <p className="text-gray-700">{t('auth.forgot_sent_body')}</p>
            <Link to="/login" className="text-brand-700 hover:underline font-medium block">
              {t('auth.back_to_signin')}
            </Link>
          </div>
        ) : (
          <form
            onSubmit={handleSubmit(onSubmit)}
            className="rounded-lg bg-white p-6 shadow-sm border border-gray-200 space-y-4"
          >
            <p className="text-sm text-gray-600">{t('auth.forgot_subtitle')}</p>
            <div>
              <Label htmlFor="email">{t('auth.email')}</Label>
              <Input id="email" type="email" autoComplete="email" {...register('email')} />
              <FieldError message={errors.email?.message} />
            </div>
            {submitError && <p className="text-sm text-danger-600">{submitError}</p>}
            <Button type="submit" loading={isSubmitting} className="w-full">
              {t('auth.send_reset_link')}
            </Button>
            <div className="text-center">
              <Link to="/login" className="text-sm text-gray-600 hover:underline">
                {t('auth.back_to_signin')}
              </Link>
            </div>
          </form>
        )}
      </div>
    </div>
  );
}
