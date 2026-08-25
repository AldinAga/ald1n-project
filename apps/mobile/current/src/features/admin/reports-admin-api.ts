import { apiRequest, queryString } from '@/lib/api/client';

export type AdminReportType =
  | 'management_summary'
  | 'profitability'
  | 'inventory'
  | 'receivables'
  | 'after_sales';

export type AdminReportScope = 'completed' | 'active' | 'all';

export type AdminReportGroupBy =
  | 'brand'
  | 'line'
  | 'type'
  | 'product'
  | 'admin';

export type AdminReportFilters = {
  date_from?: string;
  date_to?: string;
  scope?: AdminReportScope;
  group_by?: AdminReportGroupBy;
  supplier_user_id?: number;
  brand?: string;
  line?: string;
  type?: string;
  q?: string;
};

export type AdminReportRequestParams = AdminReportFilters & {
  report_type?: AdminReportType;
};

export type AdminReportNormalizedFilters = {
  date_from: string;
  date_to: string;
  scope: AdminReportScope;
  group_by: AdminReportGroupBy;
  supplier_user_id: number;
  brand: string;
  line: string;
  type: string;
  q: string;
};

export type AdminReportSummary = {
  orders_count: number;
  units_count: number;
  revenue_rsd: number;
  known_revenue_rsd: number;
  cogs_rsd: number;
  gross_profit_rsd: number;
  gross_margin_percent: number;
  commissions_rsd: number;
  refunds_rsd: number;
  service_cost_rsd: number;
  net_contribution_rsd: number;
  net_margin_percent: number;
  average_order_rsd: number;
  outstanding_rsd: number;
  missing_cost_lines: number;
  revenue_missing_cost_rsd: number;
  cost_coverage_percent: number;
};

export type AdminReportSegment = {
  label: string;
  orders_count: number;
  units_count: number;
  revenue_rsd: number;
  cogs_rsd: number;
  gross_profit_rsd: number;
  gross_margin_percent: number;
  cost_coverage_percent: number;
};

export type AdminReportTrendPoint = {
  period: string;
  revenue_rsd: number;
  gross_profit_rsd: number;
};

export type AdminReportInventoryRow = {
  kind: string;
  id: number;
  sku: string;
  name: string;
  quantity: number;
  unit_cost_rsd: number;
  value_rsd: number;
  has_cost: boolean;
  age_days: number;
  days_since_sale: number;
};

export type AdminReportInventoryAgeBucket =
  | '0_30'
  | '31_60'
  | '61_90'
  | '91_180'
  | 'over_180';

export type AdminReportInventory = {
  items_count: number;
  units_count: number;
  value_rsd: number;
  missing_cost_items: number;
  slow_items: number;
  aging: Partial<Record<AdminReportInventoryAgeBucket, number>> | [];
  top_value: AdminReportInventoryRow[];
  slow_stock: AdminReportInventoryRow[];
};

export type AdminReportReceivableAgeBucket =
  | 'not_due'
  | '1_7'
  | '8_15'
  | '16_30'
  | '31_60'
  | '61_90'
  | 'over_90';

export type AdminReportReceivables = {
  open_orders: number;
  outstanding_rsd: number;
  aging: Record<AdminReportReceivableAgeBucket, number>;
};

export type AdminReportAfterSales = {
  cases: number;
  open: number;
  closed: number;
  overdue: number;
  complaint_rate_percent: number;
  service_cost_rsd: number;
};

export type AdminManagementReport = {
  report_type: AdminReportType;
  filters: AdminReportNormalizedFilters;
  period_label: string;
  generated_at: string;
  summary: AdminReportSummary;
  segments: AdminReportSegment[];
  trend: AdminReportTrendPoint[];
  inventory: AdminReportInventory;
  receivables: AdminReportReceivables;
  after_sales: AdminReportAfterSales;
  teams: AdminReportSegment[];
};

export type AdminManagementReportResponse = {
  data: AdminManagementReport;
  filters: AdminReportNormalizedFilters;
  report_type: AdminReportType;
  capabilities: {
    export: boolean;
    manage_schedules: boolean;
  };
};

export type AdminReportScheduleFrequency = 'daily' | 'weekly' | 'monthly';

export type AdminReportScheduleFormat = 'pdf' | 'csv';

export type AdminReportSchedule = {
  id: number;
  name: string;
  report_type: AdminReportType;
  frequency: AdminReportScheduleFrequency;
  send_time: string;
  weekday: number | null;
  month_day: number | null;
  timezone: string;
  recipients: string[];
  filters: AdminReportRequestParams;
  formats: AdminReportScheduleFormat[];
  is_active: boolean;
  next_run_at: string | null;
  last_run_at: string | null;
  last_success_at: string | null;
  created_at: string | null;
  updated_at: string | null;
};

export type AdminReportDelivery = {
  id: number;
  report_schedule_id: number | null;
  report_type: AdminReportType;
  recipient_email: string;
  recipient_name: string | null;
  period_from: string | null;
  period_to: string | null;
  status: string;
  attempt_count: number;
  scheduled_for: string | null;
  last_attempt_at: string | null;
  sent_at: string | null;
  can_retry: boolean;
  created_at: string | null;
  updated_at: string | null;
};

export type AdminReportScheduleInput = {
  name: string;
  report_type: AdminReportType;
  frequency: AdminReportScheduleFrequency;
  send_time: string;
  weekday: number | null;
  month_day: number | null;
  timezone: string;
  recipients: string;
  filters: AdminReportRequestParams;
  formats: AdminReportScheduleFormat[];
  is_active: boolean;
};

export type AdminReportSchedulesResponse = {
  data: AdminReportSchedule[];
  deliveries: AdminReportDelivery[];
  capabilities: {
    create: boolean;
    update: boolean;
    toggle: boolean;
    run: boolean;
    delete: boolean;
    retry_delivery: boolean;
  };
};

export type AdminReportScheduleRunResult = {
  schedule_id: number;
  queued: number;
};

export type AdminReportExportFormat = 'csv' | 'pdf';

function requestQuery(params: AdminReportRequestParams): string {
  return queryString({
    date_from: params.date_from,
    date_to: params.date_to,
    scope: params.scope,
    group_by: params.group_by,
    supplier_user_id: params.supplier_user_id,
    brand: params.brand,
    line: params.line,
    type: params.type,
    q: params.q,
    report_type: params.report_type,
  });
}

export function adminReportExportPath(
  format: AdminReportExportFormat,
  params: AdminReportRequestParams = {},
): string {
  return `admin/reports/management.${format}${requestQuery(params)}`;
}

export type AdminOperationalReportKind = 'orders-pdf' | 'orders-csv' | 'payments-csv' | 'inventory-csv';
export type AdminOperationalReportFilters = {
  q?: string;
  status?: string;
  payment_status?: string;
  supplier_user_id?: number;
  date_from?: string;
  date_to?: string;
};

export function adminOperationalReportExportPath(
  kind: AdminOperationalReportKind,
  filters: AdminOperationalReportFilters = {},
): string {
  const suffix = ({
    'orders-pdf': 'orders.pdf',
    'orders-csv': 'orders.csv',
    'payments-csv': 'payments.csv',
    'inventory-csv': 'inventory.csv',
  } as const)[kind];
  const query = kind === 'orders-pdf' || kind === 'orders-csv'
    ? queryString({
        q: filters.q,
        status: filters.status,
        payment_status: filters.payment_status,
        supplier_user_id: filters.supplier_user_id,
        date_from: filters.date_from,
        date_to: filters.date_to,
      })
    : '';
  return `admin/reports/${suffix}${query}`;
}
export const apiAdminReports = {
  management: (params: AdminReportRequestParams = {}) =>
    apiRequest<AdminManagementReportResponse> (
      `admin/reports/management${requestQuery(params)}`,
    ),
  exportPath: adminReportExportPath,

  schedules: () =>
    apiRequest<AdminReportSchedulesResponse> ('admin/report-schedules'),

  createSchedule: async (
    input: AdminReportScheduleInput,
  ): Promise<AdminReportSchedule> => {
    const response = await apiRequest<{ data: AdminReportSchedule }> ('admin/report-schedules', {
      method: 'POST',
      body: input,
    });
    return response.data;
  },

  updateSchedule: async (
    id: number,
    input: AdminReportScheduleInput,
  ): Promise<AdminReportSchedule> => {
    const response = await apiRequest<{ data: AdminReportSchedule }> (
      `admin/report-schedules/${id}`,
      { method: 'PATCH', body: input },
    );
    return response.data;
  },

  toggleSchedule: async (id: number): Promise<AdminReportSchedule> => {
    const response = await apiRequest<{ data: AdminReportSchedule }> (
      `admin/report-schedules/${id}/toggle`,
      { method: 'PATCH' },
    );
    return response.data;
  },

  runSchedule: async (id: number): Promise<AdminReportScheduleRunResult> => {
    const response = await apiRequest<{ data: AdminReportScheduleRunResult }> (
      `admin/report-schedules/${id}/run`,
      { method: 'POST' },
    );
    return response.data;
  },

  deleteSchedule: async (id: number): Promise<number> => {
    const response = await apiRequest<{ data: { id: number } }> (
      `admin/report-schedules/${id}`,
      { method: 'DELETE' },
    );
    return response.data.id;
  },

  retryDelivery: async (id: number): Promise<AdminReportDelivery> => {
    const response = await apiRequest<{ data: AdminReportDelivery }> (
      `admin/report-deliveries/${id}/retry`,
      { method: 'POST' },
    );
    return response.data;
  },
};
