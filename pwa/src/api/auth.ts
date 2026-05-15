import { apiClient } from './client';
import type { User } from './types';

export interface LoginPayload {
  email: string;
  password: string;
  device_name?: string;
}

export interface RegisterPayload {
  farm_name: string;
  county: string;
  sub_county?: string;
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  terms_accepted: boolean;
  privacy_accepted: boolean;
}

export interface ActivePolicies {
  terms: { version: string; url: string };
  privacy: { version: string; url: string };
}

export interface AcceptPoliciesPayload {
  terms_version: string;
  privacy_version: string;
  terms_accepted: boolean;
  privacy_accepted: boolean;
}

export interface AcceptPoliciesResponse {
  terms_version: string;
  privacy_version: string;
  accepted_at: string;
}

export interface AuthResponse {
  user: { data: User } | User;
  token: string;
}

export const authApi = {
  async login(payload: LoginPayload): Promise<AuthResponse> {
    const { data } = await apiClient.post<AuthResponse>('/auth/login', payload);
    return data;
  },

  async register(payload: RegisterPayload): Promise<AuthResponse> {
    const { data } = await apiClient.post<AuthResponse>('/auth/register', payload);
    return data;
  },

  async logout(): Promise<void> {
    await apiClient.post('/auth/logout');
  },

  async me(): Promise<{ data: User }> {
    const { data } = await apiClient.get<{ data: User }>('/auth/me');
    return data;
  },

  async activePolicies(): Promise<ActivePolicies> {
    const { data } = await apiClient.get<ActivePolicies>('/policies/active');
    return data;
  },

  async acceptPolicies(payload: AcceptPoliciesPayload): Promise<AcceptPoliciesResponse> {
    const { data } = await apiClient.post<AcceptPoliciesResponse>('/policies/accept', payload);
    return data;
  },
};
