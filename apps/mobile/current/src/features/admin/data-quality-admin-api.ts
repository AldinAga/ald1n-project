import { apiRequest } from '@/lib/api/client';

export type DataQualitySeverity = 'critical' | 'warning' | 'info';
export type DataQualityStatus = 'healthy' | 'attention' | 'critical';
export type DataQualityIssue = {
  key: string;
  label: string;
  severity: DataQualitySeverity;
  count: number;
  description: string;
  repairable: boolean;
  samples: Array<Record<string, unknown>>;
  catalog_filter?: string | null;
};
export type DataQualityReport = {
  generated_at: string;
  status: DataQualityStatus;
  score: number;
  summary: { critical: number; warning: number; info: number; issue_groups: number };
  metrics: Record<string, number>;
  issues: DataQualityIssue[];
  duration_ms: number;
};
export type DataQualitySnapshot = {
  id: number;
  source: string;
  status: string;
  score: number;
  created_at: string | null;
  runner?: { first_name?: string | null; last_name?: string | null; username?: string | null } | null;
};
export type DataQualityState = {
  report: DataQualityReport;
  snapshots: DataQualitySnapshot[];
  capabilities: { repair: boolean; export: boolean };
};

export const apiAdminDataQuality = {
  state: async () => {
    const response = await apiRequest<{ data: DataQualityState }>('admin/data-quality');
    return response.data;
  },
  repair: () => apiRequest<{ message: string; data: { before: DataQualityReport; after: DataQualityReport; repair: Record<string, unknown> } }>(
    'admin/data-quality/repair',
    { method: 'POST', body: { confirm_repair: true } },
  ),
  exportPath: () => 'admin/data-quality/export',
};
