import { File } from 'expo-file-system';

import { apiExpoMultipartRequest, apiRequest, queryString } from '@/lib/api/client';
import type { AfterSalesAttachment, AfterSalesUploadFile } from '@/types/api';

export type AdminFieldWorkOptionMap = Record<string, string>;

export type AdminFieldWorkTeam = {
  id: number;
  code: string;
  name: string;
  team_type: string | null;
  contact_person: string | null;
  phone: string | null;
  email: string | null;
  vehicle_registration: string | null;
  service_area: string | null;
  is_active: boolean;
  notes?: string | null;
};

export type AdminFieldWorkSummary = {
  id: number;
  work_order_number: string;
  status: string;
  status_label: string;
  team: AdminFieldWorkTeam | null;
  planned_start_at: string | null;
  planned_end_at: string | null;
  route_reference: string | null;
  case: { id: number; case_number: string; subject: string | null } | null;
  order: { id: number; order_number: string } | null;
  action: {
    id: number;
    action_number: string;
    action_type: string | null;
    status: string | null;
  } | null;
  created_at: string | null;
  updated_at: string | null;
};

export type AdminFieldWorkServicePart = {
  id: number;
  sku: string;
  name: string;
  stock_quantity: number | null;
  reserved_quantity: number | null;
};

export type AdminFieldWorkPart = {
  id: number;
  service_part_id: number | null;
  service_part: AdminFieldWorkServicePart | null;
  requested_quantity: number | null;
  reserved_quantity: number | null;
  consumed_quantity: number | null;
  supply_mode: string | null;
  unit_cost_snapshot_rsd: number | null;
  notes: string | null;
};

export type AdminFieldWorkAttachment = AfterSalesAttachment & {
  visibility: 'internal' | 'public' | string;
};

export type AdminFieldWorkCapabilities = {
  can_schedule: boolean;
  can_mark_en_route: boolean;
  can_mark_on_site: boolean;
  can_complete: boolean;
  can_cancel: boolean;
  can_add_parts: boolean;
  can_reserve_parts: boolean;
  can_remove_parts: boolean;
};

export type AdminFieldWorkCompletionLimits = {
  max_attachments: number;
  max_attachment_bytes: number;
  attachment_mime_types: string[];
  attachment_visibilities: string[];
};

export type AdminFieldWorkDetail = AdminFieldWorkSummary & {
  public_note: string | null;
  internal_note: string | null;
  completion_result: string | null;
  travel_km: number | null;
  travel_cost_rsd: number | null;
  labor_cost_rsd: number | null;
  parts_cost_rsd: number | null;
  en_route_at: string | null;
  on_site_at: string | null;
  completed_at: string | null;
  cancelled_at: string | null;
  cancellation_reason: string | null;
  parts: AdminFieldWorkPart[];
  attachments: AdminFieldWorkAttachment[];
  options: {
    statuses: AdminFieldWorkOptionMap;
    teams: AdminFieldWorkTeam[];
    service_parts: AdminFieldWorkServicePart[];
    completion_limits: AdminFieldWorkCompletionLimits;
  };
  capabilities: AdminFieldWorkCapabilities;
};

export type AdminFieldWorkListParams = {
  q?: string;
  status?: string;
  team_id?: number;
  date_from?: string;
  date_to?: string;
  unassigned?: boolean;
  page?: number;
  per_page?: number;
};

export type AdminFieldWorkListResponse = {
  data: AdminFieldWorkSummary[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
  };
  filters: Record<string, string | number | null>;
  filter_options: {
    statuses: AdminFieldWorkOptionMap;
    teams: AdminFieldWorkTeam[];
  };
  capabilities: {
    can_view: boolean;
    can_manage: boolean;
    can_manage_parts: boolean;
  };
};

export type AdminFieldWorkDetailResponse = { data: AdminFieldWorkDetail };
export type AdminFieldWorkMutationResponse = {
  data: AdminFieldWorkDetail;
  meta?: { invalidates?: string[] };
};
export type AdminFieldWorkPartMutationResponse = {
  data: unknown;
  meta?: { invalidates?: string[] };
};
export type AdminFieldWorkTeamsResponse = {
  data: AdminFieldWorkTeam[];
  capabilities: {
    can_manage_work_orders: boolean;
    team_mutations_available_in_mobile_api: boolean;
  };
};

export type AdminFieldWorkScheduleInput = {
  field_service_team_id: number | null;
  planned_start_at: string | null;
  planned_end_at: string | null;
  route_reference: string | null;
  public_note: string | null;
  internal_note: string | null;
};

export type AdminFieldWorkPartInput = {
  service_part_id: number;
  requested_quantity: number;
  supply_mode: 'local_stock' | 'external';
  notes: string | null;
};

export type AdminFieldWorkPartConsumptionInput = Record<string, number>;

export type AdminFieldWorkPartConsumptionDraft = {
  line_id: number;
  quantity: number;
  requested_quantity: number;
};

export type AdminFieldWorkCompleteInput = {
  route_reference?: string | null;
  completion_result: string;
  travel_km?: number | null;
  travel_cost_rsd?: number | null;
  labor_cost_rsd?: number | null;
  parts_cost_rsd?: number | null;
  part_consumption: AdminFieldWorkPartConsumptionInput;
  attachment_visibility?: 'internal' | 'public';
  attachments?: AfterSalesUploadFile[];
};

export function keyedFieldWorkPartConsumption(
  rows: readonly AdminFieldWorkPartConsumptionDraft[],
): AdminFieldWorkPartConsumptionInput {
  const result: AdminFieldWorkPartConsumptionInput = {};

  for (const row of rows) {
    if (!Number.isInteger(row.line_id) || row.line_id <= 0) {
      throw new Error('Neispravan identifikator stavke rezervnog dela.');
    }
    if (!Number.isFinite(row.quantity) || row.quantity < 0) {
      throw new Error('Stvarni utrošak dela mora biti broj jednak ili veći od nule.');
    }
    if (!Number.isFinite(row.requested_quantity) || row.requested_quantity < 0) {
      throw new Error('Neispravna tražena količina rezervnog dela.');
    }
    if (row.quantity - row.requested_quantity > 0.0001) {
      throw new Error('Stvarni utrošak dela ne može biti veći od tražene količine.');
    }
    result[String(row.line_id)] = row.quantity;
  }

  return result;
}

function appendOptional(body: FormData, key: string, value: string | number | null | undefined): void {
  if (value === null || value === undefined || value === '') return;
  body.append(key, String(value));
}

function completionFormData(input: AdminFieldWorkCompleteInput): FormData {
  const body = new FormData();
  body.append('completion_result', input.completion_result);
  appendOptional(body, 'route_reference', input.route_reference);
  appendOptional(body, 'travel_km', input.travel_km);
  appendOptional(body, 'travel_cost_rsd', input.travel_cost_rsd);
  appendOptional(body, 'labor_cost_rsd', input.labor_cost_rsd);
  appendOptional(body, 'parts_cost_rsd', input.parts_cost_rsd);
  appendOptional(body, 'attachment_visibility', input.attachment_visibility ?? 'internal');

  for (const [lineId, quantity] of Object.entries(input.part_consumption)) {
    body.append(`part_consumption[${lineId}]`, String(quantity));
  }
  for (const attachment of input.attachments ?? []) {
    body.append('attachments[]', new File(attachment.uri));
  }
  return body;
}

export const apiAdminFieldOperations = {
  list: (params: AdminFieldWorkListParams = {}) =>
    apiRequest<AdminFieldWorkListResponse> (`admin/field-work${queryString({
      q: params.q,
      status: params.status,
      team_id: params.team_id,
      date_from: params.date_from,
      date_to: params.date_to,
      unassigned: params.unassigned ? '1' : undefined,
      page: params.page,
      per_page: params.per_page,
    })}`),
  detail: (workOrderId: number) =>
    apiRequest<AdminFieldWorkDetailResponse> (`admin/field-work/${workOrderId}`),
  schedule: (workOrderId: number, input: AdminFieldWorkScheduleInput) =>
    apiRequest<AdminFieldWorkMutationResponse> (`admin/field-work/${workOrderId}/schedule`, {
      method: 'PATCH',
      body: input,
    }),
  enRoute: (workOrderId: number) =>
    apiRequest<AdminFieldWorkMutationResponse> (`admin/field-work/${workOrderId}/en-route`, {
      method: 'POST',
    }),
  onSite: (workOrderId: number) =>
    apiRequest<AdminFieldWorkMutationResponse> (`admin/field-work/${workOrderId}/on-site`, {
      method: 'POST',
    }),
  complete: (workOrderId: number, input: AdminFieldWorkCompleteInput) =>
    apiExpoMultipartRequest<AdminFieldWorkMutationResponse> (
      `admin/field-work/${workOrderId}/complete`,
      completionFormData(input),
    ),
  cancel: (workOrderId: number, cancellation_reason: string) =>
    apiRequest<AdminFieldWorkMutationResponse> (`admin/field-work/${workOrderId}/cancel`, {
      method: 'POST',
      body: { cancellation_reason },
    }),
  partAdd: (workOrderId: number, input: AdminFieldWorkPartInput) =>
    apiRequest<AdminFieldWorkPartMutationResponse> (`admin/field-work/${workOrderId}/parts`, {
      method: 'POST',
      body: input,
    }),
  partReserve: (workOrderId: number) =>
    apiRequest<AdminFieldWorkPartMutationResponse> (`admin/field-work/${workOrderId}/parts/reserve`, {
      method: 'POST',
    }),
  partRemove: (workOrderId: number, lineId: number) =>
    apiRequest<AdminFieldWorkPartMutationResponse> (`admin/field-work/${workOrderId}/parts/${lineId}`, {
      method: 'DELETE',
    }),
  teams: () => apiRequest<AdminFieldWorkTeamsResponse> ('admin/field-service-teams'),
};
