import { apiRequest } from '@/lib/api/client';

// MOBILE_V0_8_EUR_RSD_EXCHANGE_RATE_BATCH13
export type AdminExchangeRateMode = 'manual' | 'auto';
export type AdminExchangeRateHistoryStatus = 'success' | 'failed';

export type AdminExchangeRateConfiguration = {
  rate: number | null;
  mode: AdminExchangeRateMode;
  provider: string;
  source: string;
  provider_date: string | null;
  updated_at: string | null;
  last_attempt_at: string | null;
  last_error: string | null;
  stale_after_hours: number;
  is_stale: boolean;
};

export type AdminExchangeRateHistoryItem = {
  id: number;
  old_rate: number | null;
  new_rate: number | null;
  mode: AdminExchangeRateMode;
  provider: string;
  source: string;
  provider_date: string | null;
  triggered_by: string;
  status: AdminExchangeRateHistoryStatus;
  message: string | null;
  created_at: string | null;
  updated_by: number | null;
  updater: { id: number; name: string } | null;
};

export type AdminExchangeRateCapabilities = {
  manage: boolean;
  manual: boolean;
  automatic: boolean;
  refresh: boolean;
};

export type AdminExchangeRateData = {
  configuration: AdminExchangeRateConfiguration;
  history: AdminExchangeRateHistoryItem[];
  capabilities: AdminExchangeRateCapabilities;
};

export type AdminExchangeRateResponse = {
  message?: string;
  data: AdminExchangeRateData;
  sync?: {
    rate: number;
    source: string;
    provider_date: string;
  };
};

export const apiAdminExchangeRate = {
  state: () => apiRequest<AdminExchangeRateResponse> ('admin/exchange-rate'),
  manual: (rate: number) => apiRequest<AdminExchangeRateResponse> ('admin/exchange-rate/manual', {
    method: 'PUT',
    body: { rate },
  }),
  automatic: (enabled: boolean, staleAfterHours: number) => apiRequest<AdminExchangeRateResponse> ('admin/exchange-rate/automatic', {
    method: 'PUT',
    body: { enabled, stale_after_hours: staleAfterHours },
  }),
  refresh: () => apiRequest<AdminExchangeRateResponse> ('admin/exchange-rate/refresh', {
    method: 'POST',
  }),
};
