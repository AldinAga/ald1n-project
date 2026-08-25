import { apiRequest } from '@/lib/api/client';

export type CatalogAdvancedNamePreviewInput = {
  product_type_id: number;
  brand_id?: number;
  product_line_id?: number;
  model_name?: string;
  sku?: string;
  specs?: Record<string, string | number | boolean>;
  spec_details?: Record<string, string>;
};

export type CatalogCloneInput = {
  name?: string;
  copy_basic: boolean;
  copy_specifications: boolean;
  copy_price: boolean;
  copy_description: boolean;
  copy_notes: boolean;
  copy_images: boolean;
  copy_warranty_rules: boolean;
  regenerate_name: boolean;
};

export type CatalogCloneResponse = {
  message: string;
  data: { id: number; sku: string; name: string; status: string; source_product_id: number | null };
};

export type CatalogBulkNamedOption = { id: number; name: string };
export type CatalogBulkLineOption = CatalogBulkNamedOption & { brand_id: number };
export type CatalogBulkTypeOption = CatalogBulkNamedOption & { category_id: number | null };
export type CatalogBulkSpecOption = CatalogBulkNamedOption & {
  data_type: string;
  unit: string | null;
  detail_input_enabled: boolean;
};
export type CatalogBulkOptions = {
  brands: CatalogBulkNamedOption[];
  lines: CatalogBulkLineOption[];
  types: CatalogBulkTypeOption[];
  specification_fields: CatalogBulkSpecOption[];
  price_actions: Array<'set' | 'increase_percent' | 'decrease_percent' | 'increase_fixed' | 'decrease_fixed'>;
  max_products: number;
};

export type CatalogBulkInput = {
  product_ids: number[];
  apply_status?: boolean;
  status?: 'draft' | 'active' | 'inactive';
  apply_brand?: boolean;
  brand_id?: number;
  apply_line?: boolean;
  product_line_id?: number;
  apply_type?: boolean;
  product_type_id?: number;
  price_action?: 'set' | 'increase_percent' | 'decrease_percent' | 'increase_fixed' | 'decrease_fixed';
  price_value?: number;
  specification_action?: 'set' | 'clear';
  specification_field_id?: number;
  specification_value?: string;
  specification_detail?: string;
  regenerate_names?: boolean;
};

export type CatalogBulkPreview = {
  count: number;
  products: Array<{ id: number; sku: string; name: string }>;
  summary: string[];
};

export const apiCatalogAdvanced = {
  namePreview: (input: CatalogAdvancedNamePreviewInput) =>
    apiRequest<{ data: { name: string } }>('admin/catalog/products/name-preview', { method: 'POST', body: input }),
  clone: (productId: number, input: CatalogCloneInput) =>
    apiRequest<CatalogCloneResponse>(`admin/catalog/products/${productId}/clone`, { method: 'POST', body: input }),
  regenerateName: (productId: number) =>
    apiRequest<{ message: string; data: { id: number; sku: string; name: string } }>(
      `admin/catalog/products/${productId}/regenerate-name`,
      { method: 'POST' },
    ),
  bulkOptions: async () => {
    const response = await apiRequest<{ data: CatalogBulkOptions }>('admin/catalog/bulk/options');
    return response.data;
  },
  bulkPreview: async (input: CatalogBulkInput) => {
    const response = await apiRequest<{ data: CatalogBulkPreview }>('admin/catalog/bulk/preview', { method: 'POST', body: input });
    return response.data;
  },
  bulkExecute: (input: CatalogBulkInput) =>
    apiRequest<{ message: string; data: { updated: number } }>('admin/catalog/bulk/execute', { method: 'POST', body: input }),
};
