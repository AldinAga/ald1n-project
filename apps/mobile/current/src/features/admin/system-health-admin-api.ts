import { apiRequest } from '@/lib/api/client';

export type AdminSystemHealthScalar = string | number | boolean | null;

export type AdminSystemHealthValue =
  | AdminSystemHealthScalar
  | AdminSystemHealthValue[]
  | { [key: string]: AdminSystemHealthValue };

export type AdminSystemHealthCurrent = {
  status: string;
  checks: AdminSystemHealthValue[];
  metrics: { [key: string]: AdminSystemHealthValue };
  checked_at: string | null;
};

export type AdminSystemHealthHistoryItem = {
  id: number;
  status: string;
  checks: AdminSystemHealthValue[];
  metrics: { [key: string]: AdminSystemHealthValue };
  checked_at: string | null;
};

export type AdminSystemHealthCapabilities = {
  refresh: boolean;
  snapshot: boolean;
  backup: boolean;
  prune: boolean;
};

export type AdminSystemHealthResponse = {
  data: AdminSystemHealthCurrent;
  history: AdminSystemHealthHistoryItem[];
  capabilities: AdminSystemHealthCapabilities;
};

export const apiAdminSystemHealth = {
  current: () =>
    apiRequest<AdminSystemHealthResponse> ('admin/system-health'),
};
