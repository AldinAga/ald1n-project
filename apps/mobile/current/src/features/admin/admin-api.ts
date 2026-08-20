import { apiRequest } from '@/lib/api/client';

export type AdminModuleKey =
  | 'orders'
  | 'commissions'
  | 'warranties'
  | 'reports'
  | 'system_health'
  | 'audit'
  | 'inventory'
  | 'service_parts'
  | 'after_sales'
  | 'field_operations'
  | 'catalog'
  | 'receivables'
  | 'users'
  | 'settings';

export type AdminFoundationModule = {
  key: AdminModuleKey;
  label: string;
  description: string;
  enabled: boolean;
  permissions: string[];
};

export type AdminFoundation = {
  api_namespace: '/api/v1/admin';
  enabled_module_count: number;
  modules: AdminFoundationModule[];
};

export const apiAdmin = {
  foundation: async (): Promise<AdminFoundation> => {
    const response = await apiRequest<{ data: AdminFoundation }> ('admin/foundation');
    return response.data;
  },
};
