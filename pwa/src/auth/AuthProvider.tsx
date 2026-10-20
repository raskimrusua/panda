import { useCallback, useEffect, useMemo, useState, type ReactNode } from 'react';
import { authApi, type LoginPayload, type RegisterPayload } from '@/api/auth';
import { tokenStore } from '@/api/client';
import type { User } from '@/api/types';
import { AuthContext, type AuthContextValue } from './AuthContext';

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  // On mount: if a token exists, hydrate the user. If the token is bad,
  // the response interceptor strips it; we just end up unauthenticated.
  useEffect(() => {
    const token = tokenStore.get();
    if (!token) {
      setIsLoading(false);
      return;
    }
    authApi
      .me()
      .then((res) => {
        setUser(res.data);
      })
      .catch(() => {
        setUser(null);
      })
      .finally(() => {
        setIsLoading(false);
      });
  }, []);

  const login = useCallback(async (payload: LoginPayload) => {
    const res = await authApi.login(payload);
    tokenStore.set(res.token);
    const u = 'data' in res.user ? res.user.data : res.user;
    setUser(u);
  }, []);

  const register = useCallback(async (payload: RegisterPayload) => {
    const res = await authApi.register(payload);
    tokenStore.set(res.token);
    const u = 'data' in res.user ? res.user.data : res.user;
    setUser(u);
  }, []);

  const refreshUser = useCallback(async () => {
    try {
      const res = await authApi.me();
      setUser(res.data);
    } catch {
      // 401 path is handled by the response interceptor; nothing to do here.
    }
  }, []);

  const logout = useCallback(async () => {
    try {
      await authApi.logout();
    } catch {
      // 401 already invalidated server-side; ignore so the UI still resets.
    }
    tokenStore.clear();
    setUser(null);
  }, []);

  const value: AuthContextValue = useMemo(
    () => ({ user, isLoading, login, register, logout, refreshUser }),
    [user, isLoading, login, register, logout, refreshUser],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}
