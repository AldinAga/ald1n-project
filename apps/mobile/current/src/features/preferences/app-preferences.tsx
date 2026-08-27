import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useRef,
  useState,
  type PropsWithChildren,
} from 'react';

import {
  getUserAppPreferences,
  setUserAppPreferences,
  type StoredAppPreferences,
} from '@/lib/storage';

export type AppThemeMode = 'system' | 'light' | 'dark';
export type PrimaryCurrency = 'RSD' | 'EUR';

type AppPreferences = {
  themeMode: AppThemeMode;
  primaryCurrency: PrimaryCurrency;
};

type AppPreferencesContextValue = AppPreferences & {
  hydrated: boolean;
  userId: number | null;
  setThemeMode: (mode: AppThemeMode) => Promise<void>;
  setPrimaryCurrency: (currency: PrimaryCurrency) => Promise<void>;
};

const DEFAULT_PREFERENCES: AppPreferences = {
  themeMode: 'system',
  primaryCurrency: 'RSD',
};

const AppPreferencesContext = createContext<AppPreferencesContextValue | null> (null);

export function AppPreferencesProvider({
  userId,
  children,
}: PropsWithChildren<{ userId: number | null }>) {
  const [preferences, setPreferences] = useState<AppPreferences> (DEFAULT_PREFERENCES);
  const [hydrated, setHydrated] = useState(false);
  const preferencesRef = useRef(preferences);

  useEffect(() => {
    preferencesRef.current = preferences;
  }, [preferences]);

  useEffect(() => {
    let active = true;
    setHydrated(false);
    setPreferences(DEFAULT_PREFERENCES);
    preferencesRef.current = DEFAULT_PREFERENCES;

    if (!userId) {
      setHydrated(true);
      return () => { active = false; };
    }

    void getUserAppPreferences(userId)
      .then((stored) => {
        if (!active) return;
        const next = stored ? {
          themeMode: stored.theme_mode,
          primaryCurrency: stored.primary_currency,
        } : DEFAULT_PREFERENCES;
        setPreferences(next);
        preferencesRef.current = next;
      })
      .finally(() => {
        if (active) setHydrated(true);
      });

    return () => { active = false; };
  }, [userId]);

  const persist = useCallback(async (next: AppPreferences) => {
    if (!userId) return;
    const stored: StoredAppPreferences = {
      theme_mode: next.themeMode,
      primary_currency: next.primaryCurrency,
    };
    await setUserAppPreferences(userId, stored);
  }, [userId]);

  const setThemeMode = useCallback(async (mode: AppThemeMode) => {
    const previous = preferencesRef.current;
    const next = { ...previous, themeMode: mode };
    setPreferences(next);
    preferencesRef.current = next;
    try {
      await persist(next);
    } catch (error) {
      setPreferences(previous);
      preferencesRef.current = previous;
      throw error;
    }
  }, [persist]);

  const setPrimaryCurrency = useCallback(async (currency: PrimaryCurrency) => {
    const previous = preferencesRef.current;
    const next = { ...previous, primaryCurrency: currency };
    setPreferences(next);
    preferencesRef.current = next;
    try {
      await persist(next);
    } catch (error) {
      setPreferences(previous);
      preferencesRef.current = previous;
      throw error;
    }
  }, [persist]);

  const value = useMemo(() => ({
    ...preferences,
    hydrated,
    userId,
    setThemeMode,
    setPrimaryCurrency,
  }), [hydrated, preferences, setPrimaryCurrency, setThemeMode, userId]);

  return <AppPreferencesContext.Provider value={value}>{children}</AppPreferencesContext.Provider>;
}

export function useAppPreferences(): AppPreferencesContextValue {
  const value = useContext(AppPreferencesContext);
  if (!value) throw new Error('useAppPreferences mora biti korišćen unutar AppPreferencesProvider-a.');
  return value;
}
