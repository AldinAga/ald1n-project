import { apiRequest } from '@/lib/api/client';

export type AdminModuleSettingKey =
  | 'commissions'
  | 'after_sales'
  | 'field_operations'
  | 'service_parts'
  | 'warranties'
  | 'receivables'
  | 'reports'
  | 'inventory'
  | 'automation'
  | 'system_health'
  | 'audit'
  | 'customer_portal'
  | 'notifications';

export type AdminModuleSetting = {
  key: AdminModuleSettingKey;
  label: string;
  description: string;
  enabled: boolean;
};

export type AdminModuleSettingsData = {
  modules: AdminModuleSetting[];
  core_modules: string[];
  capabilities: {
    update: boolean;
  };
};

export type AdminModuleSettingsResponse = {
  message?: string;
  data: AdminModuleSettingsData;
};

export const apiAdminModuleSettings = {
  state: () => apiRequest('admin/settings/modules') as Promise<AdminModuleSettingsResponse>,
  update: (modules: Record<string, boolean>) => apiRequest('admin/settings/modules', {
    method: 'PUT',
    body: { modules },
  }) as Promise<AdminModuleSettingsResponse>,
};