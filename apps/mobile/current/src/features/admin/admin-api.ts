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
  | 'settings'
  | 'customer_portal';

export type AdminFoundationModule = {
  key: AdminModuleKey;
  label: string;
  description: string;
  enabled: boolean;
  permissions: string[];
};

export type AdminInventoryValuation = {
  purchase_value_rsd: number;
  sale_value_rsd: number;
  expected_profit_rsd: number;
  missing_cost_items: number;
  missing_cost_total_items: number;
  missing_sale_value_items: number;
  valuation_complete: boolean;
  eur_rsd_rate: number | null;
};

export type AdminFoundation = {
  api_namespace: '/api/v1/admin';
  enabled_module_count: number;
  inventory_valuation: AdminInventoryValuation | null;
  modules: AdminFoundationModule[];
};

export const apiAdmin = {
  foundation: async (): Promise<AdminFoundation> => {
    const response = await apiRequest<{ data: AdminFoundation }> ('admin/foundation');
    return response.data;
  },
};
