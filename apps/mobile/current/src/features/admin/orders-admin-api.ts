import { File } from 'expo-file-system';

import { apiExpoMultipartRequest, apiRequest, queryString } from '@/lib/api/client';

export type AdminOrdersPerPage = 20 | 40 | 50 | 100;

export type AdminOrdersUser = {
  id: number | null;
  name: string;
  username?: string;
};

export type AdminOrderListItem = {
  id: number;
  order_number: string;
  source_system: string;
  sales_channel: string | null;
  status: string;
  is_completed: boolean;
  payment_status: string | null;
  payment_state: string | null;
  inventory_state: string | null;
  subtotal_rsd: number;
  customer: { name: string };
  supplier: AdminOrdersUser | null;
  assigned_at: string | null;
  accepted_at: string | null;
  expected_processing_at: string | null;
  expected_shipping_at: string | null;
  tracking_number: string | null;
  created_at: string | null;
  updated_at: string | null;
};

export type AdminOrdersRequestParams = {
  q?: string;
  status?: string;
  payment_status?: string;
  source_system?: string;
  supplier_user_id?: number;
  date_from?: string;
  date_to?: string;
  attention?: string;
  page?: number;
  per_page?: AdminOrdersPerPage;
};

export type AdminOrdersPagination = {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
};

export type AdminOrdersNormalizedFilters = {
  q: string | null;
  status: string | null;
  payment_status: string | null;
  source_system: string | null;
  supplier_user_id: number | null;
  date_from: string | null;
  date_to: string | null;
  attention: string | null;
  page: number;
  per_page: number;
};

export type AdminOrdersFilterOptions = {
  statuses: string[];
  payment_statuses: string[];
  source_systems: string[];
  suppliers: AdminOrdersUser[];
  attention: string[];
  per_page: AdminOrdersPerPage[];
};

export type AdminOrdersListCapabilities = {
  detail: boolean;
  workflow_mutations: boolean;
};

export type AdminOrderDetailScalar = string | number | boolean | null;
export type AdminOrderDetailValue =
  | AdminOrderDetailScalar
  | AdminOrderDetailValue[]
  | { [key: string]: AdminOrderDetailValue };
export type AdminOrderDetailRecord = { [key: string]: AdminOrderDetailValue };

export type AdminOrderDetailCapabilities = {
  read: boolean;
  workflow_mutations: boolean;
  orders_manage: boolean;
  internal_notes: boolean;
  reassign: boolean;
  payments: boolean;
  sale_price_correction: boolean;
  documents: boolean;
  confirm_delivery: boolean;
  reopen: boolean;
};

export type AdminOrdersListResponse = {
  data: AdminOrderListItem[];
  pagination: AdminOrdersPagination;
  filters: AdminOrdersNormalizedFilters;
  filter_options: AdminOrdersFilterOptions;
  attention: { [key: string]: number };
  capabilities: AdminOrdersListCapabilities;
};

export type AdminOrderDetailResponse = {
  data: AdminOrderDetailRecord;
  capabilities: AdminOrderDetailCapabilities;
};

// MOBILE_V1_0_ORDER_REPORT_OPS_PARITY_BATCH36
export type AdminArchivedOrderItem = {
  id: number;
  order_number: string;
  status: string;
  subtotal_rsd: number;
  completed_at: string | null;
  archived_at: string | null;
  archive_reason: string;
  customer: { name: string };
  supplier: { id: number | null; name: string } | null;
  can_restore: boolean;
  can_purge: boolean;
};

export type AdminArchivedOrdersResponse = {
  data: AdminArchivedOrderItem[];
  pagination: AdminOrdersPagination;
  filters: { q: string | null; page: number; per_page: number };
  capabilities: { archive: boolean; restore: boolean; purge: boolean };
};

export type AdminOrderArchiveMutationResponse = {
  message: string;
  data: { id: number; order_number: string; archived_at?: string | null } & Partial<AdminArchivedOrderItem>;
};

export type AdminOrderArchivePurgeResponse = {
  message: string;
  data: { id: number; purged: boolean };
};
// MOBILE_V1_0_ADMIN_ORDER_DOCUMENTS_INVOICE_PARITY_BATCH23
export type AdminOrderDocumentType = 'proforma' | 'invoice' | 'delivery_note';
export type AdminOrderDocumentRecord = AdminOrderDetailRecord & {
  id: number;
  type: string;
  number: string;
  status: string;
  revision_number: number;
};
export type AdminOrderDocumentMutationResponse = {
  message: string;
  data: AdminOrderDocumentRecord;
};

// MOBILE_V0_7_ORDERS_ADMIN_MUTATION_UI_BATCH7
export type AdminOrderStatus = 'new' | 'processing' | 'confirmed' | 'cancelled';
export type AdminOrderShipmentMethod = 'courier' | 'own_transport' | 'other';
export type AdminOrderDeliveryMethod = 'own_transport' | 'courier' | 'customer_pickup' | 'other';
export type AdminOrderPaymentStatus = 'pending' | 'paid' | 'cancelled';
export type AdminOrderPaymentEntryType = 'payment' | 'refund';
export type AdminOrderPaymentMethod = 'bank_transfer' | 'cash' | 'cash_on_delivery' | 'card' | 'other';

export type AdminOrderProofFile = {
  uri: string;
  name: string;
  type: string;
  size: number;
};

export type AdminOrderMutationData = {
  order_id: number;
  order_number: string;
  action: string;
  status: string;
  payment_status: string | null;
  payment_state: string | null;
  updated_at: string | null;
  [key: string]: AdminOrderDetailValue;
};

export type AdminOrderMutationResult = {
  data: AdminOrderMutationData;
};

export type AdminOrderStatusInput = {
  status: AdminOrderStatus;
  note?: string | null;
  order_version_token?: string | null;
};

export type AdminOrderReassignInput = {
  supplier_user_id: number;
  reason: string;
};

export type AdminOrderDeadlinesInput = {
  expected_processing_at?: string | null;
  expected_shipping_at?: string | null;
};

export type AdminOrderSalePriceCorrectionInput = { new_unit_price_amount: number; new_unit_price_currency: 'RSD' | 'EUR'; new_unit_price_rsd?: number; reason: string };

export type AdminOrderPaymentEntryInput = {
  entry_type: AdminOrderPaymentEntryType;
  amount_rsd: number;
  payment_method: AdminOrderPaymentMethod;
  paid_at: string;
  reference?: string | null;
  note?: string | null;
};

// MOBILE_V0_8_SHIPMENT_COURIER_DIRECTORY_BATCH11
export type AdminOrderShipmentInput = {
  order_version_token: string;
  shipment_method: AdminOrderShipmentMethod;
  courier_service_id?: number | null;
  shipped_at: string;
  recipient_name: string;
  recipient_phone?: string | null;
  tracking_number?: string | null;
  note?: string | null;
  shipment_proof?: AdminOrderProofFile | null;
};

export type AdminOrderCompletionInput = {
  delivery_method: AdminOrderDeliveryMethod;
  delivered_at: string;
  recipient_name: string;
  recipient_phone?: string | null;
  delivery_note?: string | null;
  completion_note?: string | null;
  delivery_proof?: AdminOrderProofFile | null;
};

export type AdminOrderShipmentResponse = {
  message: string;
  data: {
    id: number;
    shipment_method: string;
    courier: { id: number; name: string | null; tracking_url: string | null } | null;
    shipped_at: string | null;
    recipient_name: string;
    recipient_phone: string | null;
    tracking_number: string | null;
    note: string | null;
    has_proof: boolean;
    proof_original_name: string | null;
  };
  order: {
    id: number;
    status: string;
    tracking_number: string | null;
  };
};

function appendOptionalFormValue(body: FormData, key: string, value: string | number | null | undefined): void {
  if (value === null || value === undefined || value === '') return;
  body.append(key, String(value));
}

function adminOrderShipmentFormData(input: AdminOrderShipmentInput): FormData {
  const body = new FormData();
  body.append('order_version_token', input.order_version_token);
  body.append('shipment_method', input.shipment_method);
  body.append('shipped_at', input.shipped_at);
  body.append('recipient_name', input.recipient_name);
  appendOptionalFormValue(body, 'courier_service_id', input.courier_service_id);
  appendOptionalFormValue(body, 'recipient_phone', input.recipient_phone);
  appendOptionalFormValue(body, 'tracking_number', input.tracking_number);
  appendOptionalFormValue(body, 'note', input.note);
  if (input.shipment_proof) {
    body.append('shipment_proof', new File(input.shipment_proof.uri));
  }
  return body;
}

function adminOrderCompletionFormData(input: AdminOrderCompletionInput): FormData {
  const body = new FormData();
  body.append('delivery_method', input.delivery_method);
  body.append('delivered_at', input.delivered_at);
  body.append('recipient_name', input.recipient_name);
  appendOptionalFormValue(body, 'recipient_phone', input.recipient_phone);
  appendOptionalFormValue(body, 'delivery_note', input.delivery_note);
  appendOptionalFormValue(body, 'completion_note', input.completion_note);
  if (input.delivery_proof) {
    body.append('delivery_proof', new File(input.delivery_proof.uri));
  }
  return body;
}

function requestQuery(params: AdminOrdersRequestParams): string {
  return queryString({
    q: params.q,
    status: params.status,
    payment_status: params.payment_status,
    source_system: params.source_system,
    supplier_user_id: params.supplier_user_id,
    date_from: params.date_from,
    date_to: params.date_to,
    attention: params.attention,
    page: params.page,
    per_page: params.per_page,
  });
}

export const apiAdminOrders = {
  list: (params: AdminOrdersRequestParams = {}) =>
    apiRequest<AdminOrdersListResponse> (`admin/orders${requestQuery(params)}`),
  detail: (orderId: number) =>
    apiRequest<AdminOrderDetailResponse> (`admin/orders/${orderId}`),
  // MOBILE_V1_0_ORDER_REPORT_OPS_PARITY_BATCH36
  archived: (params: Pick<AdminOrdersRequestParams, 'q' | 'page' | 'per_page'> = {}) =>
    apiRequest<AdminArchivedOrdersResponse> (`admin/orders/archived${queryString({ q: params.q, page: params.page, per_page: params.per_page })}`),
  archive: (orderId: number, archive_reason: string) =>
    apiRequest<AdminOrderArchiveMutationResponse> (`admin/orders/${orderId}/archive`, { method: 'POST', body: { archive_reason } }),
  restoreArchived: (orderId: number) =>
    apiRequest<AdminOrderArchiveMutationResponse> (`admin/orders/archived/${orderId}/restore`, { method: 'POST' }),
  purgeArchived: (orderId: number, input: { confirmation: string; purge_reason: string }) =>
    apiRequest<AdminOrderArchivePurgeResponse> (`admin/orders/archived/${orderId}/purge`, { method: 'DELETE', body: input }),  status: (orderId: number, input: AdminOrderStatusInput) =>
    apiRequest<AdminOrderMutationResult> (`admin/orders/${orderId}/status`, { method: 'PATCH', body: input }),
  accept: (orderId: number) =>
    apiRequest<AdminOrderMutationResult> (`admin/orders/${orderId}/accept`, { method: 'POST' }),
  internalNote: (orderId: number, note: string) =>
    apiRequest<AdminOrderMutationResult> (`admin/orders/${orderId}/internal-notes`, { method: 'POST', body: { note } }),
  reassign: (orderId: number, input: AdminOrderReassignInput) =>
    apiRequest<AdminOrderMutationResult> (`admin/orders/${orderId}/reassign`, { method: 'PATCH', body: input }),
  deadlines: (orderId: number, input: AdminOrderDeadlinesInput) =>
    apiRequest<AdminOrderMutationResult> (`admin/orders/${orderId}/deadlines`, { method: 'PATCH', body: input }),
  paymentStatus: (orderId: number, payment_status: AdminOrderPaymentStatus) =>
    apiRequest<AdminOrderMutationResult> (`admin/orders/${orderId}/payment-status`, { method: 'PATCH', body: { payment_status } }),
  complete: (orderId: number, input: AdminOrderCompletionInput) =>
    apiExpoMultipartRequest<AdminOrderMutationResult> (`admin/orders/${orderId}/complete`, adminOrderCompletionFormData(input)),
  reopen: (orderId: number, reason: string) =>
    apiRequest<AdminOrderMutationResult> (`admin/orders/${orderId}/reopen`, { method: 'POST', body: { reason } }),
  salePriceCorrection: (orderId: number, input: AdminOrderSalePriceCorrectionInput) =>
    apiRequest<AdminOrderMutationResult> (`admin/orders/${orderId}/sale-price`, { method: 'PATCH', body: input }),
  paymentStore: (orderId: number, input: AdminOrderPaymentEntryInput) =>
    apiRequest<AdminOrderMutationResult> (`admin/orders/${orderId}/payments`, { method: 'POST', body: input }),
  paymentVerify: (orderId: number, paymentId: number) =>
    apiRequest<AdminOrderMutationResult> (`admin/orders/${orderId}/payments/${paymentId}/verify`, { method: 'POST' }),
  paymentReject: (orderId: number, paymentId: number, reason: string) =>
    apiRequest<AdminOrderMutationResult> (`admin/orders/${orderId}/payments/${paymentId}/reject`, { method: 'POST', body: { reason } }),
  paymentVoid: (orderId: number, paymentId: number) =>
    apiRequest<AdminOrderMutationResult> (`admin/orders/${orderId}/payments/${paymentId}/void`, { method: 'POST' }),
  shipment: (orderId: number, input: AdminOrderShipmentInput) =>
    apiExpoMultipartRequest<AdminOrderShipmentResponse> (`admin/orders/${orderId}/shipment`, adminOrderShipmentFormData(input)),
  documentIssue: (orderId: number, input: { document_type: AdminOrderDocumentType }) =>
    apiRequest<AdminOrderDocumentMutationResponse> (`admin/orders/${orderId}/documents`, { method: 'POST', body: input }),
  documentCancel: (orderId: number, documentId: number, cancellation_reason: string) =>
    apiRequest<AdminOrderDocumentMutationResponse> (`admin/orders/${orderId}/documents/${documentId}/cancel`, { method: 'POST', body: { cancellation_reason } }),
  // MOBILE_V1_0_ADMIN_ORDER_PDF_RELATIVE_PATH_HOTFIX_BATCH51_V3
  documentConfirmationPdfPath: (orderId: number) => `admin/orders/${orderId}/documents/confirmation.pdf`,
  documentPdfPath: (orderId: number, documentId: number) => `admin/orders/${orderId}/documents/${documentId}.pdf`,
  shipmentProofPath: (orderId: number) => `admin/orders/${orderId}/shipment-proof`,
};
