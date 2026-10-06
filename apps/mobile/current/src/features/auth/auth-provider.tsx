import { useQueryClient } from '@tanstack/react-query';
import * as Device from 'expo-device';
import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { api } from '@/lib/api/endpoints';
import { setUnauthorizedHandler } from '@/lib/api/client';
import { clearServerDeviceId, tokenStore } from '@/lib/storage';
import { clearGoogleCredentialState, getGoogleIdToken } from '@/features/auth/google-auth';
import { clearRestoreCredential, ensureRestoreCredential, tryRestoreSession } from '@/features/auth/restore-credentials';
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
  requireReauthentication: () => Promise<void>;
  can: (permission: string) => boolean;
  hasFeature: (feature: string) => boolean;
};

const AuthContext = createContext<AuthContextValue | null>(null);

type NotificationUnreadActionsContextValue = {
  setUnread: (unread: number) => void;
  decrementUnread: () => void;
};

const NotificationUnreadContext = createContext<number | null>(null);
const NotificationUnreadActionsContext = createContext<NotificationUnreadActionsContextValue | null>(null);
const SessionRestoreReadyContext = createContext<boolean | null>(null);

function normalizeNotificationUnread(unread: number): number {
  return Number.isInteger(unread) && unread >= 0 ? unread : 0;
}

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const queryClient = useQueryClient();
  const [status, setStatus] = useState<AuthStatus>('hydrating');
  const [bootstrap, setBootstrap] = useState<BootstrapData | null>(null);
  const [notificationUnread, setNotificationUnreadState] = useState(0);
  const [sessionRestoreReady, setSessionRestoreReady] = useState(false);

  const applyBootstrap = useCallback((next: BootstrapData | null) => {
    setBootstrap(next);
    setNotificationUnreadState(normalizeNotificationUnread(next?.notification_counts.unread ?? 0));
  }, []);

  const clearSession = useCallback(async () => {
    await Promise.all([tokenStore.clear(), clearServerDeviceId()]);
    applyBootstrap(null);
    setStatus('anonymous');
    queryClient.clear();
  }, [applyBootstrap, queryClient]);

  const refreshBootstrap = useCallback(async () => {
    const next = await api.auth.bootstrap();
    applyBootstrap(next);
  }, [applyBootstrap]);

  const replaceBootstrapUser = useCallback((user: User) => {
    setBootstrap((current) => current ? { ...current, user } : current);
  }, []);

  const setNotificationUnreadCount = useCallback((unread: number) => {
    setNotificationUnreadState(normalizeNotificationUnread(unread));
  }, []);

  const decrementNotificationUnread = useCallback(() => {
    setNotificationUnreadState((current) => Math.max(0, current - 1));
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
      applyBootstrap(data);
      setStatus('authenticated');
      void ensureRestoreCredential();
    } catch (error) {
      await tokenStore.clear();
      applyBootstrap(null);
      setStatus('anonymous');
      throw error;
    }
  }, [applyBootstrap]);

  useEffect(() => {
    setUnauthorizedHandler(async () => {
      await clearRestoreCredential();
      await clearSession();
    });
    return () => setUnauthorizedHandler(null);
  }, [clearSession]);

  useEffect(() => {
    let active = true;
    void (async () => {
      try {
        const token = await tokenStore.get();
        if (!active) return;
        if (!token) {
          const restoredToken = await tryRestoreSession();
          if (!active) return;
          if (!restoredToken) {
            setStatus('anonymous');
            return;
          }
          await tokenStore.set(restoredToken);
        }
        setStatus('authenticated');
        try {
          const data = await api.auth.bootstrap();
          if (active) {
            applyBootstrap(data);
            void ensureRestoreCredential();
          }
        } catch {
          // Mrežna greška ne briše validnu lokalnu sesiju; 401 handler je briše.
        }
      } finally {
        if (active) setSessionRestoreReady(true);
      }
    })();
    return () => { active = false; };
  }, [applyBootstrap]);

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
      await Promise.all([clearSession(), clearGoogleCredentialState(), clearRestoreCredential()]);
    }
  }, [clearSession]);

  const permissions = bootstrap?.permissions;
  const features = bootstrap?.features;
  const can = useCallback((permission: string) => Boolean(permissions?.includes(permission)), [permissions]);
  const hasFeature = useCallback((feature: string) => Boolean(features?.[feature]), [features]);

  const value = useMemo<AuthContextValue>(() => ({
    status, bootstrap, signIn, signInWithGoogle, signOut, refreshBootstrap,
    replaceBootstrapUser, requireReauthentication, can, hasFeature
  }), [
    bootstrap, can, hasFeature, refreshBootstrap, replaceBootstrapUser, requireReauthentication,
    signIn, signInWithGoogle, signOut, status
  ]);

  const notificationUnreadActions = useMemo<NotificationUnreadActionsContextValue>(() => ({
    setUnread: setNotificationUnreadCount,
    decrementUnread: decrementNotificationUnread,
  }), [decrementNotificationUnread, setNotificationUnreadCount]);

  return (
    <AuthContext.Provider value={value}>
      <SessionRestoreReadyContext.Provider value={sessionRestoreReady}>
        <NotificationUnreadActionsContext.Provider value={notificationUnreadActions}>
          <NotificationUnreadContext.Provider value={notificationUnread}>
            {children}
          </NotificationUnreadContext.Provider>
        </NotificationUnreadActionsContext.Provider>
      </SessionRestoreReadyContext.Provider>
    </AuthContext.Provider>
  );
}

export function useAuth(): AuthContextValue {
  const value = useContext(AuthContext);
  if (!value) throw new Error('useAuth mora biti korišćen unutar AuthProvider-a.');
  return value;
}

export function useSessionRestoreReady(): boolean {
  const value = useContext(SessionRestoreReadyContext);
  if (value === null) throw new Error('useSessionRestoreReady mora biti korišćen unutar AuthProvider-a.');
  return value;
}

export function useNotificationUnread(): number {
  const value = useContext(NotificationUnreadContext);
  if (value === null) throw new Error('useNotificationUnread mora biti korišćen unutar AuthProvider-a.');
  return value;
}

export function useNotificationUnreadActions(): NotificationUnreadActionsContextValue {
  const value = useContext(NotificationUnreadActionsContext);
  if (!value) throw new Error('useNotificationUnreadActions mora biti korišćen unutar AuthProvider-a.');
  return value;
}
