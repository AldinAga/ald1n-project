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
export type PriceDisplayMode = 'source' | PrimaryCurrency;

type AppPreferences = {
  themeMode: AppThemeMode;
  priceDisplayMode: PriceDisplayMode;
  primaryCurrency: PrimaryCurrency;
};

type AppPreferencesContextValue = AppPreferences & {
  hydrated: boolean;
  userId: number | null;
  setThemeMode: (mode: AppThemeMode) => Promise<void>;
  setPriceDisplayMode: (mode: PriceDisplayMode) => Promise<void>;
  setPrimaryCurrency: (currency: PrimaryCurrency) => Promise<void>;
};

const DEFAULT_PREFERENCES: AppPreferences = {
  themeMode: 'system',
  priceDisplayMode: 'source',
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
        const mode = stored?.price_display_mode ?? stored?.primary_currency ?? 'source';
        const next = stored ? {
          themeMode: stored.theme_mode,
          priceDisplayMode: mode,
          primaryCurrency: mode === 'EUR' ? 'EUR' as const : 'RSD' as const,
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
      price_display_mode: next.priceDisplayMode,
      primary_currency: next.primaryCurrency,
    };
    await setUserAppPreferences(userId, stored);
  }, [userId]);

  const commitPreferences = useCallback(async (next: AppPreferences, previous: AppPreferences) => {
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

  const setThemeMode = useCallback(async (mode: AppThemeMode) => {
    const previous = preferencesRef.current;
    await commitPreferences({ ...previous, themeMode: mode }, previous);
  }, [commitPreferences]);

  const setPriceDisplayMode = useCallback(async (mode: PriceDisplayMode) => {
    const previous = preferencesRef.current;
    const primaryCurrency = mode === 'source' ? previous.primaryCurrency : mode;
    await commitPreferences({ ...previous, priceDisplayMode: mode, primaryCurrency }, previous);
  }, [commitPreferences]);

  const setPrimaryCurrency = useCallback(async (currency: PrimaryCurrency) => {
    const previous = preferencesRef.current;
    await commitPreferences({ ...previous, priceDisplayMode: currency, primaryCurrency: currency }, previous);
  }, [commitPreferences]);

  const value = useMemo(() => ({
    ...preferences,
    hydrated,
    userId,
    setThemeMode,
    setPriceDisplayMode,
    setPrimaryCurrency,
  }), [hydrated, preferences, setPriceDisplayMode, setPrimaryCurrency, setThemeMode, userId]);

  return <AppPreferencesContext.Provider value={value}>{children}</AppPreferencesContext.Provider>;
}

export function useAppPreferences(): AppPreferencesContextValue {
  const value = useContext(AppPreferencesContext);
  if (!value) throw new Error('useAppPreferences mora biti korišćen unutar AppPreferencesProvider-a.');
  return value;
}