import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useAuth } from '@/auth/useAuth';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Label, FieldError } from '@/components/ui/Label';
import { registerSchema, type RegisterValues } from '@/lib/zodSchemas';

const KENYAN_COUNTIES = [
  'Meru', 'Kirinyaga', 'Nyeri', 'Embu', "Murang'a",
  'Machakos', 'Kiambu', 'Nakuru', 'Bungoma', 'Kakamega',
  'Nairobi', 'Kisii', 'Trans Nzoia', 'Uasin Gishu',
];

export function RegisterPage() {
  const { register: registerFarm } = useAuth();
  const navigate = useNavigate();
  const [submitError, setSubmitError] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<RegisterValues>({
    resolver: zodResolver(registerSchema),
    defaultValues: {
      farm_name: '',
      county: '',
      sub_county: '',
      name: '',
      email: '',
      password: '',
      password_confirmation: '',
      // Start unchecked — DPA 2019 §30 requires unambiguous consent, so the
      // user must actively tick. Schema refines enforce true at submit time.
      terms_accepted: false,
      privacy_accepted: false,
    },
  });

  const onSubmit = async (values: RegisterValues) => {
    setSubmitError(null);
    try {
      await registerFarm(values);
      navigate('/', { replace: true });
    } catch (err: unknown) {
      const message =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        'Could not create your farm. Try again.';
      setSubmitError(message);
    }
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-8">
      <div className="w-full max-w-lg">
        <div className="text-center mb-6">
          <h1 className="text-3xl font-semibold text-brand-700">Create your farm</h1>
          <p className="mt-1 text-gray-600">Plan your first season in 2 minutes.</p>
        </div>

        <form
          onSubmit={handleSubmit(onSubmit)}
          className="rounded-lg bg-white p-6 shadow-sm border border-gray-200 space-y-4"
        >
          <div>
            <Label htmlFor="farm_name">Farm name</Label>
            <Input
              id="farm_name"
              placeholder="e.g. Mwea Vegetables"
              invalid={!!errors.farm_name}
              {...register('farm_name')}
            />
            <FieldError message={errors.farm_name?.message} />
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div>
              <Label htmlFor="county">County</Label>
              <select
                id="county"
                className="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                {...register('county')}
              >
                <option value="">Choose…</option>
                {KENYAN_COUNTIES.map((c) => (
                  <option key={c} value={c}>{c}</option>
                ))}
              </select>
              <FieldError message={errors.county?.message} />
            </div>
            <div>
              <Label htmlFor="sub_county">Sub-county (optional)</Label>
              <Input
                id="sub_county"
                placeholder="e.g. Mwea"
                {...register('sub_county')}
              />
            </div>
          </div>

          <div>
            <Label htmlFor="name">Your name</Label>
            <Input id="name" invalid={!!errors.name} {...register('name')} />
            <FieldError message={errors.name?.message} />
          </div>

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

          <div className="grid grid-cols-2 gap-3">
            <div>
              <Label htmlFor="password">Password</Label>
              <Input
                id="password"
                type="password"
                autoComplete="new-password"
                invalid={!!errors.password}
                {...register('password')}
              />
              <FieldError message={errors.password?.message} />
            </div>
            <div>
              <Label htmlFor="password_confirmation">Confirm</Label>
              <Input
                id="password_confirmation"
                type="password"
                autoComplete="new-password"
                invalid={!!errors.password_confirmation}
                {...register('password_confirmation')}
              />
              <FieldError message={errors.password_confirmation?.message} />
            </div>
          </div>

          <div className="pt-2 space-y-2 border-t border-gray-100">
            <label className="flex items-start gap-2 text-sm text-gray-700">
              <input
                type="checkbox"
                className="mt-1 h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-600"
                {...register('terms_accepted')}
              />
              <span>
                I accept the{' '}
                <a
                  href="https://panda.shira.farm/terms"
                  target="_blank"
                  rel="noreferrer"
                  className="text-brand-700 font-medium underline"
                >
                  Terms of Service
                </a>
                .
              </span>
            </label>
            <FieldError message={errors.terms_accepted?.message} />

            <label className="flex items-start gap-2 text-sm text-gray-700">
              <input
                type="checkbox"
                className="mt-1 h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-600"
                {...register('privacy_accepted')}
              />
              <span>
                I accept the{' '}
                <a
                  href="https://panda.shira.farm/privacy"
                  target="_blank"
                  rel="noreferrer"
                  className="text-brand-700 font-medium underline"
                >
                  Privacy Policy
                </a>{' '}
                (Kenya DPA 2019).
              </span>
            </label>
            <FieldError message={errors.privacy_accepted?.message} />
          </div>

          {submitError && (
            <p className="text-sm text-danger-600" role="alert">
              {submitError}
            </p>
          )}

          <Button type="submit" loading={isSubmitting} className="w-full">
            Create farm
          </Button>

          <p className="text-center text-sm text-gray-600">
            Already have a farm?{' '}
            <Link to="/login" className="text-brand-700 font-medium hover:underline">
              Sign in
            </Link>
          </p>
        </form>
      </div>
    </div>
  );
}
