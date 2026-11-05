import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { GoogleLogin, GoogleOAuthProvider } from '@react-oauth/google';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslation } from 'react-i18next';
import { useAuth } from '@/auth/useAuth';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Label, FieldError } from '@/components/ui/Label';
import { LanguageSwitcher } from '@/components/LanguageSwitcher';
import { loginSchema, type LoginValues } from '@/lib/zodSchemas';

export function LoginPage() {
  const { login, loginWithGoogle } = useAuth();
  const googleClientId = import.meta.env.VITE_GOOGLE_CLIENT_ID as string | undefined;
  const { t, i18n } = useTranslation();
  const navigate = useNavigate();
  const [submitError, setSubmitError] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<LoginValues>({
    resolver: zodResolver(loginSchema),
    defaultValues: { email: '', password: '' },
  });

  const onSubmit = async (values: LoginValues) => {
    setSubmitError(null);
    try {
      await login({ ...values, device_name: 'pwa' });
      navigate('/', { replace: true });
    } catch (err: unknown) {
      const message =
        (err as { response?: { data?: { errors?: { email?: string[] }; message?: string } } })
          ?.response?.data?.errors?.email?.[0] ??
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        t('auth.sign_in_failed');
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
          <p className="mt-1 text-gray-600">{t('auth.sign_in_title')}</p>
        </div>

        <form
          onSubmit={handleSubmit(onSubmit)}
          className="rounded-lg bg-white p-6 shadow-sm border border-gray-200 space-y-4"
        >
          <div>
            <Label htmlFor="email">{t('auth.email')}</Label>
            <Input
              id="email"
              type="email"
              autoComplete="email"
              invalid={!!errors.email}
              {...register('email')}
            />
            <FieldError message={errors.email?.message} />
          </div>

          <div>
            <Label htmlFor="password">{t('auth.password')}</Label>
            <Input
              id="password"
              type="password"
              autoComplete="current-password"
              invalid={!!errors.password}
              {...register('password')}
            />
            <FieldError message={errors.password?.message} />
          </div>

          {submitError && (
            <p className="text-sm text-danger-600" role="alert">
              {submitError}
            </p>
          )}

          {googleClientId && (
            <>
              <div className="flex items-center gap-3">
                <div className="h-px flex-1 bg-gray-200" />
                <span className="text-xs text-gray-500">{t('auth.or')}</span>
                <div className="h-px flex-1 bg-gray-200" />
              </div>
              <GoogleOAuthProvider clientId={googleClientId}>
                <GoogleLogin
                  onSuccess={(res) => {
                    loginWithGoogle(res.credential ?? '')
                      .then(() => navigate('/', { replace: true }))
                      .catch(() => setSubmitError(t('auth.google_failed')));
                  }}
                  onError={() => setSubmitError(t('auth.google_failed'))}
                  theme="outline"
                  size="large"
                  shape="rectangular"
                  width="100%"
                  locale={i18n.resolvedLanguage ?? 'en'}
                />
              </GoogleOAuthProvider>
            </>
          )}
          <Button type="submit" loading={isSubmitting} className="w-full">
            {t('auth.sign_in')}
          </Button>

          <p className="text-center text-sm">
            <Link to="/forgot-password" className="text-gray-600 hover:underline">
              {t('auth.forgot_password_link')}
            </Link>
          </p>

          <p className="text-center text-sm text-gray-600">
            {t('auth.no_account_yet')}{' '}
            <Link to="/register" className="text-brand-700 font-medium hover:underline">
              {t('auth.create_your_farm')}
            </Link>
          </p>
        </form>
      </div>
    </div>
  );
}
