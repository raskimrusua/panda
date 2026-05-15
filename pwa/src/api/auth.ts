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

  async forgotPassword(email: string): Promise<{ message: string }> {
    const { data } = await apiClient.post<{ message: string }>('/auth/password/forgot', { email });
    return data;
  },

  async resetPassword(payload: {
    token: string;
    email: string;
    password: string;
    password_confirmation: string;
  }): Promise<{ message: string }> {
    const { data } = await apiClient.post<{ message: string }>('/auth/password/reset', payload);
    return data;
  },

  async sendVerification(): Promise<void> {
    await apiClient.post('/auth/email/verification-notification');
  },

  async updateProfile(payload: { name?: string; email?: string }): Promise<{ data: User }> {
    const { data } = await apiClient.patch<{ data: User }>('/auth/profile', payload);
    return data;
  },

  async changePassword(payload: {
    current_password: string;
    password: string;
    password_confirmation: string;
  }): Promise<{ message: string }> {
    const { data } = await apiClient.patch<{ message: string }>('/auth/password', payload);
    return data;
  },
};
