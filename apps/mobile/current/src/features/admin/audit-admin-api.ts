import { apiRequest, queryString } from '@/lib/api/client';

export type AdminAuditPerPage = 20 | 50 | 100;

export type AdminAuditUser = {
  id: number;
  name: string;
  username: string;
};

export type AdminAuditContextScalar = string | number | boolean | null;
export type AdminAuditContextValue =
  | AdminAuditContextScalar
  | AdminAuditContextValue[]
  | { [key: string]: AdminAuditContextValue };

export type AdminAuditEventSummary = {
  id: number;
  event_type: string;
  severity: string;
  request_id: string | null;
  route_name: string | null;
  method: string | null;
  user: AdminAuditUser | null;
  created_at: string | null;
};

export type AdminAuditEventDetail = AdminAuditEventSummary & {
  ip_address: string | null;
  context: AdminAuditContextValue;
};

export type AdminAuditRequestParams = {
  action?: string;
  level?: string;
  user_id?: number;
  date_from?: string;
  date_to?: string;
  page?: number;
  per_page?: AdminAuditPerPage;
};

export type AdminAuditPagination = {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
};

export type AdminAuditNormalizedFilters = {
  action: string | null;
  level: string | null;
  user_id: number | null;
  date_from: string | null;
  date_to: string | null;
};

export type AdminAuditFilterOptions = {
  levels: string[];
  users: AdminAuditUser[];
  per_page: AdminAuditPerPage[];
};

export type AdminAuditListCapabilities = {
  detail: boolean;
  export: boolean;
  mutate: boolean;
};

export type AdminAuditDetailCapabilities = {
  export: boolean;
  mutate: boolean;
};

export type AdminAuditListResponse = {
  data: AdminAuditEventSummary[];
  pagination: AdminAuditPagination;
  filters: AdminAuditNormalizedFilters;
  filter_options: AdminAuditFilterOptions;
  capabilities: AdminAuditListCapabilities;
};

export type AdminAuditDetailResponse = {
  data: AdminAuditEventDetail;
  capabilities: AdminAuditDetailCapabilities;
};

function requestQuery(params: AdminAuditRequestParams): string {
  return queryString({
    action: params.action,
    level: params.level,
    user_id: params.user_id,
    date_from: params.date_from,
    date_to: params.date_to,
    page: params.page,
    per_page: params.per_page,
  });
}

function exportQuery(params: AdminAuditRequestParams): string {
  return queryString({
    action: params.action,
    level: params.level,
    user_id: params.user_id,
    date_from: params.date_from,
    date_to: params.date_to,
  });
}

export const apiAdminAuditEvents = {
  list: (params: AdminAuditRequestParams = {}) =>
    apiRequest<AdminAuditListResponse> (
      `admin/audit-events${requestQuery(params)}`,
    ),
  detail: (eventId: number) =>
    apiRequest<AdminAuditDetailResponse> (`admin/audit-events/${eventId}`),
  // MOBILE_V1_0_AUDIT_CSV_EXPORT_PARITY_BATCH29
  exportPath: (params: AdminAuditRequestParams = {}) =>
    `admin/audit-events.csv${exportQuery(params)}`,
};
