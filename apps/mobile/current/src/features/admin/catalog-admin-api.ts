import { apiRequest, queryString } from '@/lib/api/client';
import type {
  AdminProductCreateInput,
  AdminProductImageCollectionResponse,
  AdminProductImageMutationResponse,
} from '@/types/api';

export type AdminCatalogProductStatus = 'draft' | 'active' | 'inactive' | 'archived';

export type AdminCatalogNamedRef = {
  id: number;
  name: string;
};

export type AdminCatalogProductSummary = {
  id: number;
  sku: string;
  name: string;
  model_name: string | null;
  status: AdminCatalogProductStatus;
  is_archived: boolean;
  deleted_at: string | null;
  product_type: AdminCatalogNamedRef | null;
  brand: AdminCatalogNamedRef | null;
  product_line: AdminCatalogNamedRef | null;
  price_amount: number;
  price_currency: 'EUR' | 'RSD';
  purchase_price_rsd: number | null;
  stock_quantity: number;
  low_stock_threshold: number;
  images_count: number;
  updated_at: string | null;
};

export type AdminCatalogProductListParams = {
  q?: string;
  status?: 'draft' | 'active' | 'inactive';
  page?: number;
  per_page?: 20 | 50 | 100;
};

export type AdminCatalogProductListResponse = {
  data: AdminCatalogProductSummary[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
  };
  filters: {
    q: string | null;
    status: string | null;
    archived: boolean;
  };
  capabilities: {
    create: boolean;
    update: boolean;
    archive: boolean;
    restore: boolean;
    manage_images: boolean;
  };
};

export type AdminCatalogStructuredSpec = {
  type: string;
  capacity_gb?: number;
};

export type AdminCatalogProductDetail = {
  id: number;
  sku: string;
  name: string;
  slug: string;
  model_name: string | null;
  status: AdminCatalogProductStatus;
  is_archived: boolean;
  deleted_at: string | null;
  product_type_id: number | null;
  brand_id: number | null;
  product_line_id: number | null;
  category_ids: number[];
  description: string;
  notes: string | null;
  price_amount: number;
  price_currency: 'EUR' | 'RSD';
  purchase_price_rsd: number | null;
  manual_commission_eur: number | null;
  stock_quantity: number;
  low_stock_threshold: number;
  specs: Record<string, string | number | boolean>;
  spec_details: Record<string, string>;
  spec_structured: Record<string, AdminCatalogStructuredSpec[]>;
  images_count: number;
  capabilities: {
    update: boolean;
    archive: boolean;
    restore: boolean;
    manage_images: boolean;
    stock_adjust: boolean;
  };
  updated_at: string | null;
};

export type AdminCatalogProductUpdateInput = AdminProductCreateInput & { sku: string };

export type AdminCatalogProductMutationResponse = {
  message: string;
  data: AdminCatalogProductDetail;
  announcement_count?: number;
};

// MOBILE_V0_8_SUPERADMIN_DIRECT_SALE_BATCH10
// MOBILE_V0_9_DIRECT_SALE_DEFERRED_PAYMENT_RECEIVABLES_BATCH5B_V2
export type AdminDirectSalePaymentMethod = 'cash' | 'card' | 'bank_transfer' | 'other' | 'deferred_payment';

export type AdminDirectSaleOptions = {
  product: {
    id: number;
    sku: string;
    name: string;
    status: string;
    stock_quantity: number;
    catalog_price_amount: number;
    catalog_price_currency: 'EUR' | 'RSD';
    catalog_unit_price_rsd: number | null;
  };
  eur_rsd_rate: number | null;
  payment_methods: Array<{ value: AdminDirectSalePaymentMethod; label: string }>;
  idempotency_key: string;
  can_submit: boolean;
  blocking_reason: string | null;
};

export type AdminDirectSaleInput = {
  buyer_name?: string;
  buyer_phone?: string;
  quantity: number;
  sale_price_rsd: number;
  payment_method: AdminDirectSalePaymentMethod;
  installment_count?: number;
  payment_due_at?: string;
  idempotency_key: string;
};

export type AdminDirectSaleResponse = {
  message: string;
  data: {
    order_id: number;
    order_number: string;
    status: string;
    payment_state: string;
    subtotal_rsd: number;
    quantity: number;
    sale_price_rsd: number;
    stock_quantity_after: number;
  };
};

function listQuery(params: AdminCatalogProductListParams): string {
  return queryString({
    q: params.q,
    status: params.status,
    page: params.page,
    per_page: params.per_page,
  });
}

// MOBILE_V0_8_PRODUCT_EDIT_ARCHIVE_RESTORE_BATCH8
// MOBILE_V0_8_PRODUCT_EDIT_ARCHIVE_RESTORE_BATCH8_V2
// MOBILE_V0_8_PRODUCT_EDIT_ARCHIVE_RESTORE_BATCH8_V3
// MOBILE_V0_8_PRODUCT_EDIT_ARCHIVE_RESTORE_BATCH8_V4
export const apiAdminCatalog = {
  list: (params: AdminCatalogProductListParams = {}, archived = false) =>
    apiRequest<AdminCatalogProductListResponse> (
      `admin/catalog/products${archived ? '/archived' : ''}${listQuery(params)}`,
    ),
  detail: (productId: number) =>
    apiRequest<{ data: AdminCatalogProductDetail }> (`admin/catalog/products/${productId}`),
  update: (productId: number, input: AdminCatalogProductUpdateInput) =>
    apiRequest<AdminCatalogProductMutationResponse> (`admin/catalog/products/${productId}`, {
      method: 'PUT',
      body: input,
    }),
  archive: (productId: number) =>
    apiRequest<AdminCatalogProductMutationResponse> (`admin/catalog/products/${productId}/archive`, {
      method: 'POST',
    }),
  restore: (productId: number) =>
    apiRequest<AdminCatalogProductMutationResponse> (`admin/catalog/products/${productId}/restore`, {
      method: 'POST',
    }),
  // MOBILE_V0_8_SUPERADMIN_DIRECT_SALE_BATCH10
  directSaleOptions: async (productId: number) => {
    const response = await apiRequest<{ data: AdminDirectSaleOptions }> (
      `admin/catalog/products/${productId}/direct-sale/options`,
    );
    return response.data;
  },
  recordDirectSale: (productId: number, input: AdminDirectSaleInput) =>
    apiRequest<AdminDirectSaleResponse> (
      `admin/catalog/products/${productId}/direct-sale`,
      { method: 'POST', body: input },
    ),
  // MOBILE_V0_8_SHARED_PRODUCT_IMAGE_MANAGER_BATCH9
  images: (productId: number) =>
    apiRequest<AdminProductImageCollectionResponse> (`admin/catalog/products/${productId}/images`),
  setPrimaryImage: (productId: number, imageId: number) =>
    apiRequest<AdminProductImageMutationResponse> (
      `admin/catalog/products/${productId}/images/${imageId}/primary`,
      { method: 'POST' },
    ),
  rotateImage: (productId: number, imageId: number, degrees: 90 | 180 | 270) =>
    apiRequest<AdminProductImageMutationResponse> (
      `admin/catalog/products/${productId}/images/${imageId}/rotate`,
      { method: 'POST', body: { degrees } },
    ),
  reorderImages: (productId: number, imageIds: number[]) =>
    apiRequest<AdminProductImageMutationResponse> (
      `admin/catalog/products/${productId}/images/reorder`,
      { method: 'POST', body: { image_ids: imageIds } },
    ),
  deleteImage: (productId: number, imageId: number) =>
    apiRequest<AdminProductImageMutationResponse> (
      `admin/catalog/products/${productId}/images/${imageId}`,
      { method: 'DELETE' },
    ),
};
