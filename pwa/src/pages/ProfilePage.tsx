import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslation } from 'react-i18next';
import { authApi } from '@/api/auth';
import { useAuth } from '@/auth/useAuth';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Label, FieldError } from '@/components/ui/Label';
import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import {
  changePasswordSchema,
  updateProfileSchema,
  type ChangePasswordValues,
  type UpdateProfileValues,
} from '@/lib/zodSchemas';

export function ProfilePage() {
  const { t } = useTranslation();
  const { user, refreshUser } = useAuth();
  const [profileMessage, setProfileMessage] = useState<{ kind: 'ok' | 'error'; text: string } | null>(null);
  const [passwordMessage, setPasswordMessage] = useState<{ kind: 'ok' | 'error'; text: string } | null>(null);

  const profileForm = useForm<UpdateProfileValues>({
    resolver: zodResolver(updateProfileSchema),
    defaultValues: { name: user?.name ?? '', email: user?.email ?? '' },
  });

  const passwordForm = useForm<ChangePasswordValues>({
    resolver: zodResolver(changePasswordSchema),
    defaultValues: { current_password: '', password: '', password_confirmation: '' },
  });

  const onProfileSubmit = async (values: UpdateProfileValues) => {
    setProfileMessage(null);
    try {
      const payload: { name?: string; email?: string } = {};
      if (values.name !== user?.name) payload.name = values.name;
      if (values.email !== user?.email) payload.email = values.email;
      if (Object.keys(payload).length === 0) {
        setProfileMessage({ kind: 'ok', text: t('profile.no_changes') });
        return;
      }
      await authApi.updateProfile(payload);
      await refreshUser();
      const ok = payload.email
        ? t('profile.saved_email_reverify')
        : t('profile.saved');
      setProfileMessage({ kind: 'ok', text: ok });
    } catch (err: unknown) {
      const e = err as { response?: { data?: { errors?: { email?: string[] }; message?: string } } };
      const message = e?.response?.data?.errors?.email?.[0] ?? e?.response?.data?.message ?? t('profile.save_failed');
      setProfileMessage({ kind: 'error', text: message });
    }
  };

  const onPasswordSubmit = async (values: ChangePasswordValues) => {
    setPasswordMessage(null);
    try {
      await authApi.changePassword(values);
      passwordForm.reset();
      setPasswordMessage({ kind: 'ok', text: t('profile.password_changed') });
    } catch (err: unknown) {
      const e = err as {
        response?: { data?: { errors?: { current_password?: string[]; password?: string[] }; message?: string } };
      };
      const message =
        e?.response?.data?.errors?.current_password?.[0] ??
        e?.response?.data?.errors?.password?.[0] ??
        e?.response?.data?.message ??
        t('profile.password_change_failed');
      setPasswordMessage({ kind: 'error', text: message });
    }
  };

  return (
    <div className="space-y-6 max-w-xl">
      <header>
        <h1 className="text-2xl font-semibold">{t('profile.title')}</h1>
        <p className="text-gray-600 text-sm">{t('profile.subtitle')}</p>
      </header>

      <Card>
        <CardHeader>
          <h2 className="font-semibold">{t('profile.section_account')}</h2>
        </CardHeader>
        <CardBody>
          <form onSubmit={profileForm.handleSubmit(onProfileSubmit)} className="space-y-4">
            <div>
              <Label htmlFor="name">{t('profile.field_name')}</Label>
              <Input id="name" {...profileForm.register('name')} />
              <FieldError message={profileForm.formState.errors.name?.message} />
            </div>
            <div>
              <Label htmlFor="email">{t('profile.field_email')}</Label>
              <Input id="email" type="email" autoComplete="email" {...profileForm.register('email')} />
              <FieldError message={profileForm.formState.errors.email?.message} />
              <p className="text-xs text-gray-500 mt-1">{t('profile.email_change_warning')}</p>
            </div>
            {profileMessage && (
              <p
                className={
                  profileMessage.kind === 'ok' ? 'text-sm text-brand-700' : 'text-sm text-danger-600'
                }
              >
                {profileMessage.text}
              </p>
            )}
            <Button type="submit" loading={profileForm.formState.isSubmitting}>
              {t('profile.save_account')}
            </Button>
          </form>
        </CardBody>
      </Card>

      <Card>
        <CardHeader>
          <h2 className="font-semibold">{t('profile.section_password')}</h2>
        </CardHeader>
        <CardBody>
          <form onSubmit={passwordForm.handleSubmit(onPasswordSubmit)} className="space-y-4">
            <div>
              <Label htmlFor="current_password">{t('profile.current_password')}</Label>
              <Input
                id="current_password"
                type="password"
                autoComplete="current-password"
                {...passwordForm.register('current_password')}
              />
              <FieldError message={passwordForm.formState.errors.current_password?.message} />
            </div>
            <div>
              <Label htmlFor="password">{t('profile.new_password')}</Label>
              <Input
                id="password"
                type="password"
                autoComplete="new-password"
                {...passwordForm.register('password')}
              />
              <FieldError message={passwordForm.formState.errors.password?.message} />
            </div>
            <div>
              <Label htmlFor="password_confirmation">{t('profile.confirm_password')}</Label>
              <Input
                id="password_confirmation"
                type="password"
                autoComplete="new-password"
                {...passwordForm.register('password_confirmation')}
              />
              <FieldError message={passwordForm.formState.errors.password_confirmation?.message} />
            </div>
            {passwordMessage && (
              <p
                className={
                  passwordMessage.kind === 'ok' ? 'text-sm text-brand-700' : 'text-sm text-danger-600'
                }
              >
                {passwordMessage.text}
              </p>
            )}
            <Button type="submit" loading={passwordForm.formState.isSubmitting}>
              {t('profile.save_password')}
            </Button>
          </form>
        </CardBody>
      </Card>
    </div>
  );
}
