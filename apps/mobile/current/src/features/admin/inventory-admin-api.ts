import { File, Paths } from 'expo-file-system';
import { Platform } from 'react-native';

import { apiDownload, apiRequest, queryString } from '@/lib/api/client';

export type AdminInventoryPagination = {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
};

export type AdminInventoryProduct = {
  id: number;
  sku: string;
  name: string;
  stock_quantity: number;
  low_stock_threshold: number;
  status: string;
  is_low_stock: boolean;
  updated_at: string | null;
};

export type AdminInventoryMovement = {
  id: number;
  product: { id: number; sku: string; name: string } | null;
  movement_type: string;
  source: string;
  quantity_change: number;
  quantity_before: number;
  quantity_after: number;
  note: string | null;
  stock_receipt_id: number | null;
  inventory_count_id: number | null;
  created_at: string | null;
};

export type AdminInventoryReceiptSummary = {
  id: number;
  receipt_number: string;
  status: string;
  supplier_name: string | null;
  supplier_document_number: string | null;
  received_on: string | null;
  total_units: number;
  created_at: string | null;
};

export type AdminInventoryCountSummary = {
  id: number;
  count_number: string;
  status: string;
  counted_on: string | null;
  total_variance: number;
  finalized_at: string | null;
  created_at: string | null;
};

export type AdminInventoryCapabilities = {
  can_view: boolean;
  can_adjust: boolean;
  can_receive: boolean;
  can_count: boolean;
  can_export: boolean;
};

export type AdminInventoryListParams = {
  q?: string;
  stock?: 'all' | 'low';
  status?: string;
  page?: number;
  per_page?: number;
};

export type AdminInventoryMovementParams = {
  q?: string;
  product_id?: number;
  movement_type?: string;
  source?: string;
  page?: number;
  per_page?: number;
};

export type AdminInventoryListResponse = {
  data: AdminInventoryProduct[];
  meta: AdminInventoryPagination;
  filters: AdminInventoryListParams;
  summary: {
    low_stock_count: number;
    recent_receipts: AdminInventoryReceiptSummary[];
    recent_counts: AdminInventoryCountSummary[];
  };
  capabilities: AdminInventoryCapabilities;
};

export type AdminInventoryMovementResponse = {
  data: AdminInventoryMovement[];
  meta: AdminInventoryPagination;
  filters: AdminInventoryMovementParams;
};

export type AdminInventoryAdjustmentInput = {
  quantity_change: number;
  note: string;
  idempotency_key: string;
};

export type AdminInventoryAdjustmentResponse = {
  data: { movement: AdminInventoryMovement; product: AdminInventoryProduct };
};

export type AdminInventoryReceiptInput = {
  received_on: string;
  supplier_name?: string | null;
  supplier_document_number?: string | null;
  note?: string | null;
  idempotency_key: string;
  items: Array<{
    product_id: number;
    quantity: number;
    unit_cost_rsd?: number | null;
    note?: string | null;
  }>;
};

export type AdminInventoryReceiptDetail = AdminInventoryReceiptSummary & {
  note: string | null;
  items: Array<{
    id: number;
    product_id: number;
    product_sku: string;
    product_name: string;
    quantity: number;
    note: string | null;
  }>;
};

export type AdminInventoryCountInput = {
  counted_on: string;
  scope_label?: string | null;
  note?: string | null;
  idempotency_key: string;
  items: Array<{
    product_id: number;
    counted_quantity: number;
    note?: string | null;
  }>;
};

export type AdminInventoryCountDetail = AdminInventoryCountSummary & {
  note: string | null;
  items: Array<{
    id: number;
    product_id: number;
    product_sku: string;
    product_name: string;
    system_quantity: number;
    counted_quantity: number;
    variance: number;
    note: string | null;
  }>;
};

export const apiAdminInventory = {
  list: (params: AdminInventoryListParams = {}) =>
    apiRequest<AdminInventoryListResponse> (`admin/inventory${queryString({
      q: params.q,
      stock: params.stock,
      status: params.status,
      page: params.page,
      per_page: params.per_page,
    })}`),
  movements: (params: AdminInventoryMovementParams = {}) =>
    apiRequest<AdminInventoryMovementResponse> (`admin/stock-movements${queryString({
      q: params.q,
      product_id: params.product_id,
      movement_type: params.movement_type,
      source: params.source,
      page: params.page,
      per_page: params.per_page,
    })}`),
  adjust: (productId: number, input: AdminInventoryAdjustmentInput) =>
    apiRequest<AdminInventoryAdjustmentResponse> (`admin/stock/${productId}/adjust`, {
      method: 'POST',
      headers: { 'Idempotency-Key': input.idempotency_key },
      body: input,
    }),
  receive: (input: AdminInventoryReceiptInput) =>
    apiRequest<{ data: AdminInventoryReceiptDetail }> ('admin/inventory/receipts', {
      method: 'POST',
      headers: { 'Idempotency-Key': input.idempotency_key },
      body: input,
    }),
  count: (input: AdminInventoryCountInput) =>
    apiRequest<{ data: AdminInventoryCountDetail }> ('admin/inventory/counts', {
      method: 'POST',
      headers: { 'Idempotency-Key': input.idempotency_key },
      body: input,
    }),
  csvPath: () => '/api/v1/admin/inventory.csv',
};

function normalizedContentType(value: string | null): string {
  return (value ?? '').split(';', 1)[0]?.trim().toLowerCase() ?? '';
}

export async function shareAdminInventoryCsv(): Promise<void> {
  if (Platform.OS === 'web') throw new Error('CSV izvoz je dostupan u Android/iOS aplikaciji.');
  const response = await apiDownload(apiAdminInventory.csvPath());
  if (response.bytes.byteLength <= 0) throw new Error('CSV izvoz je prazan.');
  if (response.contentLength !== null && response.contentLength !== response.bytes.byteLength) {
    throw new Error('CSV izvoz nije preuzet u celosti.');
  }
  const mime = normalizedContentType(response.contentType);
  if (mime !== 'text/csv' && mime !== 'application/csv' && mime !== 'application/octet-stream') {
    throw new Error('Server je vratio neočekivan tip CSV izvoza.');
  }
  const file = new File(Paths.cache, `admin-inventory-${Date.now()}.csv`);
  file.create({ overwrite: true, intermediates: true });
  file.write(response.bytes);
  if (!file.exists || file.size !== response.bytes.byteLength) {
    if (file.exists) file.delete();
    throw new Error('CSV izvoz nije moguće bezbedno sačuvati.');
  }
  const Sharing = await import('expo-sharing');
  if (!(await Sharing.isAvailableAsync())) throw new Error('Sistemsko deljenje nije dostupno na ovom uređaju.');
  await Sharing.shareAsync(file.uri, { dialogTitle: 'Lager CSV', mimeType: 'text/csv' });
}
