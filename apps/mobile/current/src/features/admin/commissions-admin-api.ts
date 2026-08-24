import { apiRequest, queryString } from '@/lib/api/client';

export type AdminCommissionStatus = 'pending' | 'approved' | 'paid' | 'cancelled';
export type AdminCommissionPaymentMethod = 'bank_transfer' | 'cash' | 'other';
export type AdminCommissionTransition = 'pending' | 'approved' | 'paid' | 'cancelled';

export type AdminCommissionPerson = {
  id: number;
  label: string;
  email: string | null;
};

export type AdminCommissionSelectOption<T extends string = string> = {
  value: T;
  label: string;
};

export type AdminCommissionPayment = {
  method: AdminCommissionPaymentMethod | null;
  method_label: string | null;
  reference: string | null;
  batch_number: string | null;
  paid_at: string | null;
};

export type AdminCommissionHistoryItem = {
  id: number;
  old_status: AdminCommissionStatus | null;
  new_status: AdminCommissionStatus;
  new_status_label: string;
  note: string | null;
  actor_name: string;
  created_at: string | null;
};

export type AdminCommission = {
  id: number;
  order: { id: number; order_number: string };
  user: { id: number; name: string; email: string | null };
  responsible_name: string;
  total_eur: number;
  status: AdminCommissionStatus;
  status_label: string;
  status_note: string | null;
  payment: AdminCommissionPayment | null;
  allowed_transitions: AdminCommissionTransition[];
  bulk_pay_eligible: boolean;
  status_updated_at: string | null;
  created_at: string | null;
  updated_at: string | null;
  history?: AdminCommissionHistoryItem[];
};

export type AdminCommissionSummary = {
  count: number;
  pending_count: number;
  pending_eur: number;
  approved_count: number;
  approved_eur: number;
  paid_count: number;
  paid_eur: number;
  cancelled_count: number;
  cancelled_eur: number;
};

export type AdminCommissionListParams = {
  q?: string;
  status?: AdminCommissionStatus;
  user_id?: number;
  supplier_user_id?: number;
  date_from?: string;
  date_to?: string;
  page?: number;
  per_page?: number;
};

export type AdminCommissionListResponse = {
  data: AdminCommission[];
  summary: AdminCommissionSummary;
  meta: { current_page: number; last_page: number; per_page: number; total: number };
  filters: {
    statuses: AdminCommissionSelectOption<AdminCommissionStatus>[];
    payment_methods: AdminCommissionSelectOption<AdminCommissionPaymentMethod>[];
    users: AdminCommissionPerson[];
    suppliers: AdminCommissionPerson[];
  };
  capabilities: {
    bulk_pay: boolean;
    exports: boolean;
    can_filter_people: boolean;
    can_cancel_paid: boolean;
  };
};

export type AdminCommissionTransitionInput = {
  status: AdminCommissionTransition;
  note?: string;
  payment_method?: AdminCommissionPaymentMethod;
  payment_reference?: string;
};

export type AdminCommissionBulkPayInput = {
  commission_ids: number[];
  payment_method: AdminCommissionPaymentMethod;
  payment_reference?: string;
  note?: string;
};

export type AdminCommissionBulkPayResponse = {
  message: string;
  data: {
    batch_number: string;
    commission_count: number;
    total_eur: number;
    payment_method: AdminCommissionPaymentMethod;
    payment_reference: string | null;
    paid_at: string | null;
  };
};

function exportParams(params: AdminCommissionListParams): AdminCommissionListParams {
  const value: AdminCommissionListParams = {};
  if (params.q) value.q = params.q;
  if (params.status) value.status = params.status;
  if (params.user_id) value.user_id = params.user_id;
  if (params.supplier_user_id) value.supplier_user_id = params.supplier_user_id;
  if (params.date_from) value.date_from = params.date_from;
  if (params.date_to) value.date_to = params.date_to;
  return value;
}

export const apiAdminCommissions = {
  list: (params: AdminCommissionListParams = {}) =>
    apiRequest<AdminCommissionListResponse> (`admin/commissions${queryString(params)}`),
  detail: async (id: number): Promise<AdminCommission> => {
    const response = await apiRequest<{ data: AdminCommission }> (`admin/commissions/${id}`);
    return response.data;
  },
  transition: async (id: number, input: AdminCommissionTransitionInput): Promise<AdminCommission> => {
    const response = await apiRequest<{ data: AdminCommission }> (`admin/commissions/${id}/status`, {
      method: 'PATCH',
      body: input,
    });
    return response.data;
  },
  bulkPay: (input: AdminCommissionBulkPayInput) =>
    apiRequest<AdminCommissionBulkPayResponse> ('admin/commissions/bulk-pay', {
      method: 'POST',
      body: input,
    }),
  exportPath: (format: 'csv' | 'pdf', params: AdminCommissionListParams = {}) =>
    `admin/commissions.${format}${queryString(exportParams(params))}`,
};
