import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useAuth } from '@/auth/useAuth';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Label, FieldError } from '@/components/ui/Label';
import { loginSchema, type LoginValues } from '@/lib/zodSchemas';

export function LoginPage() {
  const { login } = useAuth();
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
        'Sign-in failed. Check your email and password.';
      setSubmitError(message);
    }
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4">
      <div className="w-full max-w-md">
        <div className="text-center mb-6">
          <h1 className="text-3xl font-semibold text-brand-700">Panda</h1>
          <p className="mt-1 text-gray-600">Sign in to your farm.</p>
        </div>

        <form
          onSubmit={handleSubmit(onSubmit)}
          className="rounded-lg bg-white p-6 shadow-sm border border-gray-200 space-y-4"
        >
          <div>
            <Label htmlFor="email">Email</Label>
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
            <Label htmlFor="password">Password</Label>
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

          <Button type="submit" loading={isSubmitting} className="w-full">
            Sign in
          </Button>

          <p className="text-center text-sm text-gray-600">
            New here?{' '}
            <Link to="/register" className="text-brand-700 font-medium hover:underline">
              Create your farm
            </Link>
          </p>
        </form>
      </div>
    </div>
  );
}
