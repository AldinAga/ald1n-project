import { apiRequest, queryString } from '@/lib/api/client';

export type AdminWarrantyStatus = 'active' | 'expired' | 'void';

export type AdminWarrantyMaintenanceStatus =
  | 'due'
  | 'scheduled'
  | 'completed'
  | 'cancelled';

export type AdminWarrantyMaintenanceFilter =
  | 'due'
  | 'scheduled'
  | 'overdue';

export type AdminWarrantySelectOption<T extends string = string> = {
  value: T;
  label: string;
};

export type AdminWarrantySummary = {
  id: number;
  warranty_number: string;
  order: {
    id: number;
    order_number: string;
  };
  customer_name: string;
  product_name: string;
  product_sku: string | null;
  status: AdminWarrantyStatus;
  status_label: string;
  starts_at: string | null;
  expires_at: string | null;
  next_maintenance_at: string | null;
  created_at: string | null;
  updated_at: string | null;
};

export type AdminWarrantyMaintenanceRecord = {
  id: number;
  status: AdminWarrantyMaintenanceStatus;
  status_label: string;
  due_at: string | null;
  scheduled_at: string | null;
  completed_at: string | null;
  service_reference: string | null;
  result: string | null;
  notes: string | null;
  completer_name: string | null;
  can_schedule: boolean;
  can_complete: boolean;
};

export type AdminWarranty = AdminWarrantySummary & {
  raw_status: 'active' | 'void';
  customer: {
    name: string;
    address: string;
    postal_code: string;
    city: string;
    phone: string | null;
  };
  quantity: number;
  serial_numbers: string[];
  duration_months: number | null;
  duration_days: number | null;
  maintenance_interval_months: number | null;
  last_maintenance_at: string | null;
  terms: string | null;
  rule: {
    id: number;
    name: string;
  } | null;
  void: {
    reason: string | null;
    voided_at: string | null;
    voided_by_name: string | null;
  } | null;
  maintenance_records: AdminWarrantyMaintenanceRecord[];
  capabilities: {
    can_update: boolean;
    can_void: boolean;
    can_manage_maintenance: boolean;
  };
};

export type AdminWarrantyListParams = {
  q?: string;
  status?: AdminWarrantyStatus;
  maintenance?: AdminWarrantyMaintenanceFilter;
  page?: number;
  per_page?: number;
};

export type AdminWarrantyListResponse = {
  data: AdminWarrantySummary[];
  stats: {
    active: number;
    expiring: number;
    maintenance_due: number;
    void: number;
  };
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  filters: {
    statuses: AdminWarrantySelectOption<AdminWarrantyStatus>[];
    maintenance: AdminWarrantySelectOption<AdminWarrantyMaintenanceFilter>[];
  };
  capabilities: {
    update: boolean;
    void: boolean;
    maintenance: boolean;
    rules: boolean;
    backfill: boolean;
    pdf: boolean;
  };
};

export type AdminWarrantyRuleScope = 'global' | 'category' | 'product';

export type AdminWarrantyRule = {
  id: number;
  name: string;
  scope_type: AdminWarrantyRuleScope;
  category_id: number | null;
  category_name: string | null;
  product_id: number | null;
  product_name: string | null;
  product_sku: string | null;
  duration_months: number;
  duration_days: number;
  maintenance_interval_months: number | null;
  priority: number;
  terms: string | null;
  is_active: boolean;
  created_at: string | null;
  updated_at: string | null;
};

export type AdminWarrantyRuleInput = {
  name: string;
  scope_type: AdminWarrantyRuleScope;
  category_id?: number | null;
  product_id?: number | null;
  duration_months: number;
  duration_days: number;
  maintenance_interval_months?: number | null;
  priority: number;
  terms?: string | null;
  is_active: boolean;
};

export type AdminWarrantyRuleCategory = {
  id: number;
  name: string;
};

export type AdminWarrantyRuleProduct = {
  id: number;
  sku: string;
  name: string;
};

export type AdminWarrantyRulesResponse = {
  data: AdminWarrantyRule[];
  categories: AdminWarrantyRuleCategory[];
  products: AdminWarrantyRuleProduct[];
  capabilities: {
    create: boolean;
    update: boolean;
    backfill: boolean;
  };
};

export type AdminWarrantyBackfillResponse = {
  message: string;
  data: {
    created: number;
    limit: number;
  };
};

export type AdminWarrantyUpdateInput = {
  starts_at: string;
  expires_at: string;
  serial_numbers?: string;
  terms_snapshot?: string;
};

export type AdminWarrantyVoidInput = {
  reason: string;
};

export type AdminWarrantyScheduleInput = {
  scheduled_at: string;
  service_reference?: string;
  notes?: string;
};

export type AdminWarrantyCompleteInput = {
  completed_at: string;
  service_reference?: string;
  result: string;
  notes?: string;
};

async function unwrapWarranty(
  request: Promise<{ data: AdminWarranty }>,
): Promise<AdminWarranty> {
  const response = await request;
  return response.data;
}

export const apiAdminWarranties = {
  list: (params: AdminWarrantyListParams = {}) =>
    apiRequest<AdminWarrantyListResponse> (
      `admin/warranties${queryString(params)}`,
    ),

  rules: () =>
    apiRequest<AdminWarrantyRulesResponse> ('admin/warranties/rules'),

  createRule: async (input: AdminWarrantyRuleInput): Promise<AdminWarrantyRule> => {
    const response = await apiRequest<{ data: AdminWarrantyRule }> ('admin/warranties/rules', {
      method: 'POST',
      body: input,
    });
    return response.data;
  },

  updateRule: async (
    id: number,
    input: AdminWarrantyRuleInput,
  ): Promise<AdminWarrantyRule> => {
    const response = await apiRequest<{ data: AdminWarrantyRule }> (`admin/warranties/rules/${id}`, {
      method: 'PUT',
      body: input,
    });
    return response.data;
  },

  backfill: (limit = 500) =>
    apiRequest<AdminWarrantyBackfillResponse> ('admin/warranties/backfill', {
      method: 'POST',
      body: { limit },
    }),

  adminPdfPath: (id: number) => `admin/warranties/${id}.pdf`,

  detail: (id: number) =>
    unwrapWarranty(
      apiRequest<{ data: AdminWarranty }> (
        `admin/warranties/${id}`,
      ),
    ),

  update: (id: number, input: AdminWarrantyUpdateInput) =>
    unwrapWarranty(
      apiRequest<{ data: AdminWarranty }> (
        `admin/warranties/${id}`,
        {
          method: 'PUT',
          body: input,
        },
      ),
    ),

  void: (id: number, input: AdminWarrantyVoidInput) =>
    unwrapWarranty(
      apiRequest<{ data: AdminWarranty }> (
        `admin/warranties/${id}`,
        {
          method: 'POST',
          body: input,
        },
      ),
    ),

  scheduleMaintenance: (
    warrantyId: number,
    recordId: number,
    input: AdminWarrantyScheduleInput,
  ) =>
    unwrapWarranty(
      apiRequest<{ data: AdminWarranty }> (
        `admin/warranties/${warrantyId}/maintenance/${recordId}/schedule`,
        {
          method: 'POST',
          body: input,
        },
      ),
    ),

  completeMaintenance: (
    warrantyId: number,
    recordId: number,
    input: AdminWarrantyCompleteInput,
  ) =>
    unwrapWarranty(
      apiRequest<{ data: AdminWarranty }> (
        `admin/warranties/${warrantyId}/maintenance/${recordId}/complete`,
        {
          method: 'POST',
          body: input,
        },
      ),
    ),
};
