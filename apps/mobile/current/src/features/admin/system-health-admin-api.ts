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
  checker_name: string | null;
  checked_at: string | null;
};

export type AdminSystemHealthBackupItem = {
  id: number;
  backup_key: string;
  backup_type: string;
  status: string;
  creator_name: string | null;
  size_bytes: number;
  started_at: string | null;
  finished_at: string | null;
};

export type AdminSystemHealthSecurityEvent = {
  id: number;
  event_type: string;
  severity: string;
  actor_name: string | null;
  route_name: string | null;
  method: string | null;
  created_at: string | null;
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
  backups: AdminSystemHealthBackupItem[];
  security_events: AdminSystemHealthSecurityEvent[];
  capabilities: AdminSystemHealthCapabilities;
};

export type AdminSystemHealthRunResponse = {
  message: string;
  data: { status: string; checked_at: string | null };
};

export type AdminSystemHealthBackupResponse = {
  message: string;
  data: {
    backup_key: string | null;
    backup_type: string;
    status: string;
    size_bytes: number;
    database_only: boolean;
  };
};

export type AdminSystemHealthPruneResponse = {
  message: string;
  data: { removed: number; kept: number };
};

// MOBILE_V1_0_SYSTEM_HEALTH_MUTATIONS_PARITY_BATCH25
export const apiAdminSystemHealth = {
  current: () =>
    apiRequest<AdminSystemHealthResponse> ('admin/system-health'),
  run: () =>
    apiRequest<AdminSystemHealthRunResponse> ('admin/system-health/run', {
      method: 'POST',
    }),
  backup: (databaseOnly = false) =>
    apiRequest<AdminSystemHealthBackupResponse> ('admin/system-health/backup', {
      method: 'POST',
      body: { database_only: databaseOnly },
    }),
  prune: () =>
    apiRequest<AdminSystemHealthPruneResponse> ('admin/system-health/prune', {
      method: 'POST',
    }),
};
