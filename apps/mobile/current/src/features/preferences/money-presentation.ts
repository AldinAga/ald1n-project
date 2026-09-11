import { useCallback } from 'react';

import { useAuth } from '@/features/auth/auth-provider';
import {
  useAppPreferences,
  type PriceDisplayMode,
} from '@/features/preferences/app-preferences';
import { formatMoney } from '@/lib/formatters';

export function convertPresentationAmount(
  amount: number,
  sourceCurrency: string,
  targetCurrency: PriceDisplayMode,
  eurRsdRate: number | null,
): { amount: number; currency: string; converted: boolean } {
  const source = sourceCurrency.toUpperCase();
  if (targetCurrency === 'source') return { amount, currency: source, converted: false };
  if (source === targetCurrency) return { amount, currency: source, converted: false };
  if (!eurRsdRate || !Number.isFinite(eurRsdRate) || eurRsdRate <= 0) {
    return { amount, currency: source, converted: false };
  }
  if (source === 'RSD' && targetCurrency === 'EUR') {
    return { amount: amount / eurRsdRate, currency: 'EUR', converted: true };
  }
  if (source === 'EUR' && targetCurrency === 'RSD') {
    return { amount: amount * eurRsdRate, currency: 'RSD', converted: true };
  }
  return { amount, currency: source, converted: false };
}

export function useMoneyPresentation() {
  const { bootstrap } = useAuth();
  const { priceDisplayMode, primaryCurrency } = useAppPreferences();
  const contract = bootstrap?.app.currency;
  const rate = typeof contract?.eur_rsd_rate === 'number' ? contract.eur_rsd_rate : null;

  const formatPrimaryMoney = useCallback((amount: number, sourceCurrency = 'RSD') => {
    const presented = convertPresentationAmount(amount, sourceCurrency, priceDisplayMode, rate);
    return formatMoney(presented.amount, presented.currency);
  }, [priceDisplayMode, rate]);

  return {
    priceDisplayMode,
    primaryCurrency,
    eurRsdRate: rate,
    rateLabel: contract?.rate_label ?? 'Komercijalni prodajni',
    rateProvider: contract?.provider ?? null,
    rateProviderDate: contract?.provider_date ?? null,
    rateIsStale: contract?.is_stale ?? true,
    formatPrimaryMoney,
  } as const;
}