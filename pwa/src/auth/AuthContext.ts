import { createContext } from 'react';
import type { User } from '@/api/types';
import type { LoginPayload, RegisterPayload } from '@/api/auth';

export interface AuthContextValue {
  user: User | null;
  isLoading: boolean;
  login: (payload: LoginPayload) => Promise<void>;
  register: (payload: RegisterPayload) => Promise<void>;
  logout: () => Promise<void>;
}

export const AuthContext = createContext<AuthContextValue | null>(null);
