import { apiRequest } from '@/lib/api/client';

// MOBILE_V1_0_ADMIN_CATALOG_DICTIONARIES_BATCH22
export type AdminDictionaryResource = 'categories' | 'product-lines' | 'product-types' | 'specification-fields';
export type AdminDictionaryStatus = 'active' | 'inactive';
export type AdminDictionaryDataType = 'text' | 'integer' | 'decimal' | 'select' | 'boolean';
export type AdminDictionaryFilterType = 'none' | 'select' | 'range' | 'boolean' | 'text';

export type AdminDictionaryReference = {
  id: number;
  name: string;
  status: string;
  parent_id?: number | null;
  slug?: string;
  data_type?: string;
};

export type AdminDictionaryUsage = { types: number; products: number; children: number };

export type AdminDictionaryItem = {
  id: number;
  name: string;
  slug: string;
  status: AdminDictionaryStatus;
  sort_order: number;
  parent_id?: number | null;
  description?: string | null;
  brand_id?: number;
  brand?: { id: number; name: string } | null;
  category?: { id: number; name: string } | null;
  fields_count?: number;
  products_count?: number;
  data_type?: AdminDictionaryDataType;
  filter_type?: AdminDictionaryFilterType;
  unit?: string | null;
  placeholder?: string | null;
  help_text?: string | null;
  options_text?: string | null;
  min_value?: number | null;
  max_value?: number | null;
  parent_field_id?: number | null;
  parent_field?: { id: number; name: string } | null;
  dependency_map_text?: string;
  detail_input_enabled?: boolean;
  detail_label?: string | null;
  detail_placeholder?: string | null;
  usage?: AdminDictionaryUsage;
};

export type AdminDictionaryIndexData = {
  resource: AdminDictionaryResource;
  label: string;
  items: AdminDictionaryItem[];
  references: {
    categories: AdminDictionaryReference[];
    brands: AdminDictionaryReference[];
    selectable_fields: AdminDictionaryReference[];
  };
  options: {
    statuses: Array<{ value: AdminDictionaryStatus; label: string }>;
    data_types: AdminDictionaryDataType[];
    filter_types: AdminDictionaryFilterType[];
    default_product_statuses: Array<'draft' | 'active' | 'inactive'>;
  };
  capabilities: {
    create: boolean;
    update: boolean;
    deactivate: boolean;
    reorder: boolean;
    purge: boolean;
    product_type_detail: boolean;
  };
};

export type AdminDictionaryInput = {
  name: string;
  slug?: string | null;
  status: AdminDictionaryStatus;
  sort_order?: number;
  parent_id?: number | null;
  description?: string | null;
  brand_id?: number;
  data_type?: AdminDictionaryDataType;
  filter_type?: AdminDictionaryFilterType;
  unit?: string | null;
  placeholder?: string | null;
  help_text?: string | null;
  options_text?: string | null;
  min_value?: number | null;
  max_value?: number | null;
  parent_field_id?: number | null;
  dependency_map_text?: string | null;
  detail_input_enabled?: boolean;
  detail_label?: string | null;
  detail_placeholder?: string | null;
  name_template?: string | null;
  auto_name_enabled?: boolean;
  minimum_completeness_percent?: number;
  default_product_status?: 'draft' | 'active' | 'inactive';
  required_core_fields?: string[];
  field_config?: Record<string, AdminProductTypeFieldConfigInput>;
};

export type AdminProductTypeFieldConfigInput = {
  enabled: boolean;
  is_required?: boolean;
  is_filterable?: boolean;
  show_in_summary?: boolean;
  include_in_name?: boolean;
  sort_order?: number;
  completeness_weight?: number;
  default_value?: string | null;
  default_detail?: string | null;
};

export type AdminProductTypeField = {
  id: number;
  name: string;
  slug: string;
  data_type: string;
  unit: string | null;
  status: string;
  detail_input_enabled: boolean;
  enabled: boolean;
  is_required: boolean;
  is_filterable: boolean;
  show_in_summary: boolean;
  include_in_name: boolean;
  sort_order: number;
  completeness_weight: number;
  default_value: string | null;
  default_detail: string | null;
};

export type AdminProductTypeDetailData = {
  product_type: {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    status: AdminDictionaryStatus;
    sort_order: number;
    category: { id: number; name: string } | null;
    name_template: string | null;
    auto_name_enabled: boolean;
    minimum_completeness_percent: number;
    default_product_status: 'draft' | 'active' | 'inactive';
    required_core_fields: string[];
    products_count: number;
  };
  fields: AdminProductTypeField[];
  required_core_options: Array<{ value: string; label: string }>;
  capabilities: { update: boolean; reorder_fields: boolean };
};

export const apiAdminDictionaries = {
  list: async (resource: AdminDictionaryResource) => {
    const response = await apiRequest<{ data: AdminDictionaryIndexData }> (`admin/catalog/dictionaries/${resource}`);
    return response.data;
  },
  create: (resource: AdminDictionaryResource, input: AdminDictionaryInput) =>
    apiRequest<{ message: string; data: { id: number; resource: string } }> (`admin/catalog/dictionaries/${resource}`, {
      method: 'POST',
      body: input,
    }),
  update: (resource: AdminDictionaryResource, itemId: number, input: AdminDictionaryInput) =>
    apiRequest<{ message: string; data: { id: number; resource: string } }> (`admin/catalog/dictionaries/${resource}/${itemId}`, {
      method: 'PUT',
      body: input,
    }),
  deactivate: (resource: AdminDictionaryResource, itemId: number) =>
    apiRequest<{ message: string; data: { id: number; resource: string; status: 'inactive' } }> (`admin/catalog/dictionaries/${resource}/${itemId}`, {
      method: 'DELETE',
    }),
  reorderBrands: (ids: number[]) =>
    apiRequest<{ message: string; data: { ids: number[] } }> ('admin/catalog/dictionaries/brands/reorder', {
      method: 'PATCH',
      body: { ids },
    }),
  reorder: (resource: AdminDictionaryResource, ids: number[]) =>
    apiRequest<{ message: string; data: { ids: number[] } }> (`admin/catalog/dictionaries/${resource}/reorder`, {
      method: 'PATCH',
      body: { ids },
    }),
  productType: async (productTypeId: number) => {
    const response = await apiRequest<{ data: AdminProductTypeDetailData }> (`admin/catalog/dictionaries/product-types/${productTypeId}`);
    return response.data;
  },
  reorderProductTypeFields: (productTypeId: number, ids: number[]) =>
    apiRequest<{ message: string; data: { ids: number[] } }> (`admin/catalog/dictionaries/product-types/${productTypeId}/fields/reorder`, {
      method: 'PATCH',
      body: { ids },
    }),
  purgeSpecificationField: (itemId: number, confirmName: string) =>
    apiRequest<{ message: string; data: { deleted_id: number; cleanup: Record<string, number> } }> (`admin/catalog/dictionaries/specification-fields/${itemId}/purge`, {
      method: 'DELETE',
      body: { confirm_name: confirmName },
    }),
};
