import { apiRequest, queryString } from '@/lib/api/client';

// MOBILE_V0_9_GLOBAL_BRAND_MANAGER_BATCH3
export type AdminBrandProductType = {
  id: number;
  name: string;
  slug: string;
  status: string;
  category_id: number | null;
  category_name: string | null;
};

export type AdminBrandLine = {
  id: number;
  name: string;
  slug: string;
  status: string;
  sort_order: number;
  product_type_ids: number[];
};

export type AdminManagedBrand = {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  website_url: string | null;
  status: 'active' | 'inactive';
  sort_order: number;
  product_type_ids: number[];
  product_types: AdminBrandProductType[];
  lines: AdminBrandLine[];
  line_groups: Record<string, AdminBrandLine[]>;
};

export type AdminBrandManagerData = {
  brands: AdminManagedBrand[];
  product_types: AdminBrandProductType[];
  filters: { q: string; product_type_id: number | null };
  capabilities: { create: boolean; update: boolean; delete: false; max_lines_per_type: 3 };
};

export type AdminBrandOptionsData = {
  product_types: AdminBrandProductType[];
  statuses: Array<{ value: 'active' | 'inactive'; label: string }>;
  max_lines_per_type: 3;
};

export type AdminBrandInput = {
  name: string;
  description: string | null;
  website_url: string | null;
  status: 'active' | 'inactive';
  sort_order: number;
  product_type_ids: number[];
  line_names_by_type: Record<string, string[]>;
};

export type AdminBrandMutationResponse = { message: string; data: AdminManagedBrand };

export const apiAdminBrands = {
  list: (params: { q?: string; product_type_id?: number } = {}) =>
    apiRequest<{ data: AdminBrandManagerData }> (`admin/catalog/brands${queryString(params)}`),
  options: () => apiRequest<{ data: AdminBrandOptionsData }> ('admin/catalog/brands/options'),
  create: (input: AdminBrandInput) => apiRequest<AdminBrandMutationResponse> ('admin/catalog/brands', {
    method: 'POST',
    body: input,
  }),
  update: (brandId: number, input: AdminBrandInput) => apiRequest<AdminBrandMutationResponse> (`admin/catalog/brands/${brandId}`, {
    method: 'PUT',
    body: input,
  }),
};
