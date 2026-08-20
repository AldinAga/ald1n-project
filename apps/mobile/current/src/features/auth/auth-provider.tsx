import { useQueryClient } from '@tanstack/react-query';
import * as Device from 'expo-device';
import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { api } from '@/lib/api/endpoints';
import { setUnauthorizedHandler } from '@/lib/api/client';
import { clearServerDeviceId, tokenStore } from '@/lib/storage';
import { clearGoogleCredentialState, getGoogleIdToken } from '@/features/auth/google-auth';
import type { BootstrapData, User } from '@/types/api';

type AuthStatus = 'hydrating' | 'anonymous' | 'authenticated';
export type GoogleSignInOutcome = { status: 'authenticated' } | { status: 'pending'; message: string };

type AuthContextValue = {
  status: AuthStatus;
  bootstrap: BootstrapData | null;
  signIn: (login: string, password: string) => Promise<void>;
  signInWithGoogle: () => Promise<GoogleSignInOutcome>;
  signOut: () => Promise<void>;
  refreshBootstrap: () => Promise<void>;
  replaceBootstrapUser: (user: User) => void;
  setNotificationUnreadCount: (unread: number) => void;
  requireReauthentication: () => Promise<void>;
  can: (permission: string) => boolean;
  hasFeature: (feature: string) => boolean;
};

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const queryClient = useQueryClient();
  const [status, setStatus] = useState<AuthStatus>('hydrating');
  const [bootstrap, setBootstrap] = useState<BootstrapData | null>(null);

  const clearSession = useCallback(async () => {
    await Promise.all([tokenStore.clear(), clearServerDeviceId()]);
    setBootstrap(null);
    setStatus('anonymous');
    queryClient.clear();
  }, [queryClient]);

  const refreshBootstrap = useCallback(async () => {
    const next = await api.auth.bootstrap();
    setBootstrap(next);
  }, []);

  const replaceBootstrapUser = useCallback((user: User) => {
    setBootstrap((current) => current ? { ...current, user } : current);
  }, []);

  const setNotificationUnreadCount = useCallback((unread: number) => {
    const safeUnread = Number.isInteger(unread) && unread >= 0 ? unread : 0;

    setBootstrap((current) => current ? {
      ...current,
      notification_counts: {
        ...current.notification_counts,
        unread: safeUnread
      }
    } : current);
  }, []);

  const requireReauthentication = useCallback(async () => {
    await clearSession();
    try {
      await clearGoogleCredentialState();
    } catch {
      // API sesija je vec bezbedno uklonjena.
    }
  }, [clearSession]);

  const finishTokenLogin = useCallback(async (token: string) => {
    await tokenStore.set(token);
    try {
      const data = await api.auth.bootstrap();
      setBootstrap(data);
      setStatus('authenticated');
    } catch (error) {
      await tokenStore.clear();
      setBootstrap(null);
      setStatus('anonymous');
      throw error;
    }
  }, []);

  useEffect(() => {
    setUnauthorizedHandler(clearSession);
    return () => setUnauthorizedHandler(null);
  }, [clearSession]);

  useEffect(() => {
    let active = true;
    void (async () => {
      const token = await tokenStore.get();
      if (!active) return;
      if (!token) {
        setStatus('anonymous');
        return;
      }
      setStatus('authenticated');
      try {
        const data = await api.auth.bootstrap();
        if (active) setBootstrap(data);
      } catch {
        // Mrežna greška ne briše validnu lokalnu sesiju; 401 handler je briše.
      }
    })();
    return () => { active = false; };
  }, []);

  const signIn = useCallback(async (login: string, password: string) => {
    const deviceName = Device.modelName ?? Device.deviceName ?? 'Ald1n mobile device';
    const response = await api.auth.login({ login, password, device_name: deviceName });
    await finishTokenLogin(response.token);
  }, [finishTokenLogin]);

  const signInWithGoogle = useCallback(async (): Promise<GoogleSignInOutcome> => {
    const idToken = await getGoogleIdToken();
    const deviceName = Device.modelName ?? Device.deviceName ?? 'Ald1n mobile device';
    const response = await api.auth.google({ id_token: idToken, device_name: deviceName });

    if (!('token' in response)) {
      return { status: 'pending', message: response.message };
    }

    await finishTokenLogin(response.token);
    return { status: 'authenticated' };
  }, [finishTokenLogin]);

  const signOut = useCallback(async () => {
    try {
      await api.auth.logout();
    } finally {
      await Promise.all([clearSession(), clearGoogleCredentialState()]);
    }
  }, [clearSession]);

  const can = useCallback((permission: string) => Boolean(bootstrap?.permissions.includes(permission)), [bootstrap]);
  const hasFeature = useCallback((feature: string) => Boolean(bootstrap?.features[feature]), [bootstrap]);

  const value = useMemo<AuthContextValue>(() => ({
    status, bootstrap, signIn, signInWithGoogle, signOut, refreshBootstrap,
    replaceBootstrapUser, setNotificationUnreadCount, requireReauthentication, can, hasFeature
  }), [
    bootstrap, can, hasFeature, refreshBootstrap, replaceBootstrapUser, setNotificationUnreadCount, requireReauthentication,
    signIn, signInWithGoogle, signOut, status
  ]);

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const value = useContext(AuthContext);
  if (!value) throw new Error('useAuth mora biti korišćen unutar AuthProvider-a.');
  return value;
}
