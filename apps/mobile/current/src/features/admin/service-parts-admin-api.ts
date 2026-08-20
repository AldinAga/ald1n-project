import { apiRequest, queryString } from '@/lib/api/client';

export type AdminServicePartCapabilities = {
  can_view_parts: boolean;
  can_manage_parts: boolean;
  can_manage_procurement: boolean;
};

export type AdminServicePart = {
  id: number;
  sku: string;
  name: string;
  unit: string | null;
  stock_quantity: number | null;
  reserved_quantity: number | null;
  available_quantity: number | null;
  minimum_quantity: number | null;
  average_cost_rsd: number | null;
  is_active: boolean;
  notes: string | null;
  created_at: string | null;
  updated_at: string | null;
};

export type AdminServicePartMovement = {
  id: number;
  service_part_id: number | null;
  movement_type: string | null;
  stock_change: number | null;
  reserved_change: number | null;
  stock_before: number | null;
  stock_after: number | null;
  reserved_before: number | null;
  reserved_after: number | null;
  note: string | null;
  created_at: string | null;
};

export type AdminServicePartSupplier = {
  id: number;
  code: string;
  name: string;
  contact_person: string | null;
  phone: string | null;
  email: string | null;
  address: string | null;
  lead_time_days: number | null;
  notes: string | null;
  is_active: boolean;
  created_at: string | null;
  updated_at: string | null;
};

export type AdminServicePartPurchaseItem = {
  id: number;
  service_part: AdminServicePart | null;
  ordered_quantity: number | null;
  received_quantity: number | null;
  unit_cost_rsd: number | null;
  line_total_rsd: number | null;
};

export type AdminServicePartPurchase = {
  id: number;
  request_number: string;
  status: string;
  supplier: AdminServicePartSupplier | null;
  expected_at: string | null;
  submitted_at: string | null;
  ordered_at: string | null;
  received_at: string | null;
  cancelled_at: string | null;
  total_cost_rsd: number | null;
  notes: string | null;
  cancellation_reason: string | null;
  created_at: string | null;
  updated_at: string | null;
  items?: AdminServicePartPurchaseItem[];
};

export type AdminServicePartPagination = {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
};

export type AdminServicePartListParams = {
  q?: string;
  active?: boolean;
  page?: number;
  per_page?: number;
};

export type AdminServicePartListResponse = {
  data: AdminServicePart[];
  meta: AdminServicePartPagination;
  capabilities: AdminServicePartCapabilities;
};

export type AdminServicePartWrite = {
  sku: string;
  name: string;
  unit: string;
  stock_quantity?: number;
  minimum_quantity?: number;
  average_cost_rsd?: number;
  is_active: boolean;
  notes?: string | null;
};

export type AdminServicePartMutationResponse = {
  data: AdminServicePart;
  meta?: { invalidates?: string[] };
};

export type AdminServicePartAdjustmentInput = {
  quantity_change: number;
  note: string;
  idempotency_key: string;
};

export type AdminServicePartAdjustmentResponse = {
  data: AdminServicePartMovement;
  part: AdminServicePart;
  meta?: { invalidates?: string[] };
};

export type AdminServicePartSupplierListParams = {
  q?: string;
  active?: boolean;
  page?: number;
  per_page?: number;
};

export type AdminServicePartSupplierListResponse = {
  data: AdminServicePartSupplier[];
  meta: AdminServicePartPagination;
  capabilities: AdminServicePartCapabilities;
};

export type AdminServicePartSupplierWrite = {
  code: string;
  name: string;
  contact_person?: string | null;
  phone?: string | null;
  email?: string | null;
  address?: string | null;
  lead_time_days?: number | null;
  notes?: string | null;
  is_active: boolean;
};

export type AdminServicePartSupplierMutationResponse = {
  data: AdminServicePartSupplier;
  meta?: { invalidates?: string[] };
};

export type AdminServicePartPurchaseListParams = {
  q?: string;
  status?: string;
  supplier_id?: number;
  page?: number;
  per_page?: number;
};

export type AdminServicePartPurchaseListResponse = {
  data: AdminServicePartPurchase[];
  meta: AdminServicePartPagination;
  options: {
    suppliers: AdminServicePartSupplier[];
    parts: AdminServicePart[];
  };
  capabilities: AdminServicePartCapabilities;
};

export type AdminServicePartPurchaseWrite = {
  supplier_id?: number | null;
  expected_at?: string | null;
  notes?: string | null;
  items: Array<{
    service_part_id: number;
    ordered_quantity: number;
    unit_cost_rsd?: number | null;
  }>;
};

export type AdminServicePartPurchaseDetailResponse = {
  data: AdminServicePartPurchase & { items: AdminServicePartPurchaseItem[] };
  options: {
    suppliers: AdminServicePartSupplier[];
    parts: AdminServicePart[];
  };
  capabilities: AdminServicePartCapabilities & {
    can_submit: boolean;
    can_order: boolean;
    can_receive: boolean;
    can_cancel: boolean;
  };
};

export type AdminServicePartPurchaseMutationResponse = {
  data: AdminServicePartPurchase & { items?: AdminServicePartPurchaseItem[] };
  meta?: { invalidates?: string[] };
};

export const apiAdminServiceParts = {
  partsList: (params: AdminServicePartListParams = {}) =>
    apiRequest<AdminServicePartListResponse> (`admin/service-parts${queryString({
      q: params.q,
      active: params.active,
      page: params.page,
      per_page: params.per_page,
    })}`),
  partCreate: (input: AdminServicePartWrite) =>
    apiRequest<AdminServicePartMutationResponse> ('admin/service-parts', { method: 'POST', body: input }),
  partUpdate: (partId: number, input: AdminServicePartWrite) =>
    apiRequest<AdminServicePartMutationResponse> (`admin/service-parts/${partId}`, { method: 'PUT', body: input }),
  partAdjust: (partId: number, input: AdminServicePartAdjustmentInput) =>
    apiRequest<AdminServicePartAdjustmentResponse> (`admin/service-parts/${partId}/adjust`, { method: 'POST', body: input }),

  suppliersList: (params: AdminServicePartSupplierListParams = {}) =>
    apiRequest<AdminServicePartSupplierListResponse> (`admin/service-part-suppliers${queryString({
      q: params.q,
      active: params.active,
      page: params.page,
      per_page: params.per_page,
    })}`),
  supplierCreate: (input: AdminServicePartSupplierWrite) =>
    apiRequest<AdminServicePartSupplierMutationResponse> ('admin/service-part-suppliers', { method: 'POST', body: input }),
  supplierUpdate: (supplierId: number, input: AdminServicePartSupplierWrite) =>
    apiRequest<AdminServicePartSupplierMutationResponse> (`admin/service-part-suppliers/${supplierId}`, { method: 'PUT', body: input }),

  purchasesList: (params: AdminServicePartPurchaseListParams = {}) =>
    apiRequest<AdminServicePartPurchaseListResponse> (`admin/service-part-purchases${queryString({
      q: params.q,
      status: params.status,
      supplier_id: params.supplier_id,
      page: params.page,
      per_page: params.per_page,
    })}`),
  purchaseCreate: (input: AdminServicePartPurchaseWrite) =>
    apiRequest<AdminServicePartPurchaseMutationResponse> ('admin/service-part-purchases', { method: 'POST', body: input }),
  purchaseDetail: (purchaseId: number) =>
    apiRequest<AdminServicePartPurchaseDetailResponse> (`admin/service-part-purchases/${purchaseId}`),
  purchaseSubmit: (purchaseId: number) =>
    apiRequest<AdminServicePartPurchaseMutationResponse> (`admin/service-part-purchases/${purchaseId}/submit`, { method: 'POST' }),
  purchaseOrder: (purchaseId: number) =>
    apiRequest<AdminServicePartPurchaseMutationResponse> (`admin/service-part-purchases/${purchaseId}/order`, { method: 'POST' }),
  purchaseReceive: (purchaseId: number) =>
    apiRequest<AdminServicePartPurchaseMutationResponse> (`admin/service-part-purchases/${purchaseId}/receive`, { method: 'POST' }),
  purchaseCancel: (purchaseId: number, reason?: string | null) =>
    apiRequest<AdminServicePartPurchaseMutationResponse> (`admin/service-part-purchases/${purchaseId}/cancel`, {
      method: 'POST',
      body: reason?.trim() ? { reason: reason.trim() } : {},
    }),
};
