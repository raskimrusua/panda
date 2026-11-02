import { useCallback, useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { CheckCircle2, CreditCard, Loader2, TriangleAlert } from 'lucide-react';
import {
  fetchPlans,
  fetchSubscription,
  initiateSubscriptionPayment,
  type Plan,
  type Subscription,
} from '../api/billing';

/**
 * BillingPage — current plan + plan picker + M-Pesa STK initiation.
 * Payment completion happens on the phone; the plan flips server-side when
 * the Safaricom callback lands (see SubscriptionBillingService).
 */
export function BillingPage() {
  const { t } = useTranslation();
  const [plans, setPlans] = useState<Plan[]>([]);
  const [subscription, setSubscription] = useState<Subscription | null>(null);
  const [phone, setPhone] = useState('');
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [busyPlan, setBusyPlan] = useState<string | null>(null);

  const load = useCallback(async () => {
    try {
      const [planList, current] = await Promise.all([fetchPlans(), fetchSubscription()]);
      setPlans(planList);
      setSubscription(current);
    } catch {
      setError(t('billing.loadFailed'));
    }
  }, [t]);

  useEffect(() => {
    void load();
  }, [load]);

  const handlePlan = async (plan: Plan) => {
    setMessage(null);
    setError(null);

    if (plan.price_kes <= 0) {
      setBusyPlan(plan.slug);
      try {
        const result = await initiateSubscriptionPayment(phone || '0700000000', plan.slug);
        if (result.success) {
          setMessage(result.message);
        } else {
          setError(result.message);
        }
      } catch {
        setError(t('billing.paymentFailed'));
      } finally {
        setBusyPlan(null);
        void load();
      }
      return;
    }

    if (!phone.trim()) {
      setError(t('billing.phoneRequired'));
      return;
    }

    setBusyPlan(plan.slug);
    try {
      const result = await initiateSubscriptionPayment(phone, plan.slug);
      if (result.success) {
        setMessage(result.message);
      } else {
        setError(result.message);
      }
    } catch {
      setError(t('billing.paymentFailed'));
    } finally {
      setBusyPlan(null);
      void load();
    }
  };

  const currentSlug = subscription?.plan.slug ?? null;

  return (
    <div className="mx-auto max-w-4xl space-y-6 p-4 md:p-6 print:hidden">
      <div className="flex items-center gap-2">
        <CreditCard className="h-5 w-5 text-brand-700" />
        <h1 className="text-xl font-semibold text-brand-700">{t('billing.title')}</h1>
      </div>

      <div className="rounded-lg border border-gray-200 bg-white p-4">
        <p className="text-sm text-gray-500">{t('billing.current')}</p>
        <p className="text-lg font-semibold text-brand-700">
          {subscription ? subscription.plan.name : '…'}
          {subscription?.current_period_end && (
            <span className="ml-2 text-sm font-normal text-gray-400">
              {t('billing.validUntil')}{' '}
              {new Date(subscription.current_period_end).toLocaleDateString()}
            </span>
          )}
        </p>
      </div>

      <div className="rounded-lg border border-gray-200 bg-white p-4">
        <label htmlFor="billing-phone" className="block text-sm font-medium text-gray-700">
          {t('billing.phoneLabel')}
        </label>
        <input
          id="billing-phone"
          type="tel"
          inputMode="tel"
          value={phone}
          onChange={(e) => setPhone(e.target.value)}
          placeholder={t('billing.phonePlaceholder')}
          className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none"
        />
      </div>

      {message && (
        <div className="flex items-start gap-2 rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-800">
          <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0" />
          <span>{message}</span>
        </div>
      )}
      {error && (
        <div className="flex items-start gap-2 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-800">
          <TriangleAlert className="mt-0.5 h-4 w-4 shrink-0" />
          <span>{error}</span>
        </div>
      )}

      <div className="grid gap-4 md:grid-cols-3">
        {plans.map((plan) => {
          const isCurrent = plan.slug === currentSlug;
          const busy = busyPlan === plan.slug;
          return (
            <div
              key={plan.slug}
              className={`flex flex-col rounded-lg border p-4 ${
                isCurrent ? 'border-brand-500 bg-brand-50' : 'border-gray-200 bg-white'
              }`}
            >
              <div className="mb-1 flex items-baseline justify-between">
                <h3 className="text-lg font-semibold text-brand-700">{plan.name}</h3>
                {isCurrent && (
                  <span className="text-xs font-medium text-brand-700">{t('billing.currentBadge')}</span>
                )}
              </div>
              <p className="mb-3 text-2xl font-semibold text-brand-700">
                {plan.price_kes === 0 ? 'KES 0' : `KES ${plan.price_kes.toLocaleString()}`}
                {plan.price_kes > 0 && (
                  <span className="text-sm font-normal text-gray-400">{t('billing.perMonth')}</span>
                )}
              </p>
              <ul className="mb-4 flex-1 space-y-1 text-sm text-gray-600">
                {(plan.features ?? []).map((feature) => (
                  <li key={feature}>• {feature}</li>
                ))}
              </ul>
              {!isCurrent && (
                <button
                  type="button"
                  disabled={busy}
                  onClick={() => void handlePlan(plan)}
                  className="rounded-md bg-brand-700 px-3 py-2 text-sm font-semibold text-white transition-colors hover:bg-brand-800 disabled:opacity-60"
                >
                  {busy && <Loader2 className="mr-1 inline h-4 w-4 animate-spin" />}
                  {plan.price_kes === 0 ? t('billing.switchFree') : t('billing.pay')}
                </button>
              )}
            </div>
          );
        })}
      </div>
    </div>
  );
}
