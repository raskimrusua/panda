import { apiClient } from './client';

export interface Plan {
  id: string;
  name: string;
  slug: string;
  price_kes: number;
  billing_cycle: string;
  features: string[] | null;
}

export interface Subscription {
  id: string;
  status: string;
  billing_cycle: string;
  current_period_start: string | null;
  current_period_end: string | null;
  is_active: boolean;
  plan: Plan;
}

export interface InitiateResult {
  success: boolean;
  message: string;
  payment_id?: string;
  checkout_request_id?: string;
  plan?: { slug: string; name: string };
}

export async function fetchPlans(): Promise<Plan[]> {
  const { data } = await apiClient.get<{ data: Plan[] }>('/billing/plans');
  return data.data;
}

export async function fetchSubscription(): Promise<Subscription> {
  const { data } = await apiClient.get<{ data: Subscription }>('/billing/subscription');
  return data.data;
}

export async function initiateSubscriptionPayment(
  phone: string,
  planSlug: string,
): Promise<InitiateResult> {
  const { data } = await apiClient.post<InitiateResult>(
    '/billing/subscription/initiate-payment',
    { phone, plan_slug: planSlug },
  );
  return data;
}
