import { apiRequest } from '@/lib/api/client';

// MOBILE_V1_0_GLOBAL_SEARCH_PARITY_BATCH38
export type AdminGlobalSearchSection = {
  key: string;
  label: string;
  count: number;
};

export type AdminGlobalSearchItem = {
  id: string;
  type: 'product' | 'order' | 'user' | 'warranty' | 'after_sales' | 'command' | string;
  title: string;
  name: string;
  identifier: string;
  sku: string;
  subtitle: string;
  meta: string;
  badge: string;
  status: string;
  status_label: string;
  stock_quantity: number | null;
  group_key: string;
  group: string;
  mobile_path: string;
};

export type AdminGlobalSearchPayload = {
  query: string;
  items: AdminGlobalSearchItem[];
  sections: AdminGlobalSearchSection[];
};

type AdminGlobalSearchEnvelope = {
  data: AdminGlobalSearchPayload;
};

export const apiAdminGlobalSearch = {
  search: (q: string) =>
    apiRequest<AdminGlobalSearchEnvelope>(
      `global-search?q=${encodeURIComponent(q.trim().slice(0, 80))}`,
    ),
};
