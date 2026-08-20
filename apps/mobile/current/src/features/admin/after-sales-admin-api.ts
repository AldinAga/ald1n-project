import { File } from 'expo-file-system';

import { apiExpoMultipartRequest, apiRequest, queryString } from '@/lib/api/client';
import type { AfterSalesUploadFile } from '@/types/api';

export type AdminAfterSalesUser = { id: number; name: string };
export type AdminAfterSalesOptionMap = Record<string, string>;
export type AdminAfterSalesCaseCapabilities = {
  update: boolean;
  message: boolean;
  execute: boolean;
  refund: boolean;
};

export type AdminAfterSalesSummary = {
  id: number;
  case_number: string;
  order: { id: number; order_number: string | null };
  case_type: string;
  case_type_label: string;
  priority: string;
  priority_label: string;
  status: string;
  status_label: string;
  subject: string;
  opener: AdminAfterSalesUser | null;
  assignee: AdminAfterSalesUser | null;
  due_at: string | null;
  pending_actions_count: number;
  created_at: string | null;
  updated_at: string | null;
};

export type AdminAfterSalesAttachment = {
  id: number;
  original_name: string;
  mime_type: string;
  size_bytes: number;
  download_path: string;
  created_at: string | null;
};

export type AdminAfterSalesMessage = {
  id: number;
  visibility: 'public' | 'internal' | string;
  body: string;
  author: AdminAfterSalesUser | null;
  attachments: AdminAfterSalesAttachment[];
  created_at: string | null;
};

export type AdminAfterSalesHistory = {
  id: number;
  from_status: string | null;
  to_status: string | null;
  note: string | null;
  actor: AdminAfterSalesUser | null;
  created_at: string | null;
};

export type AdminAfterSalesCaseItem = {
  id: number;
  order_item_id: number | null;
  product_id: number | null;
  sku: string | null;
  name: string;
  quantity: number;
  issue_description: string | null;
};

export type AdminAfterSalesActionItem = {
  id: number;
  after_sales_case_item_id: number;
  product_id: number | null;
  sku: string | null;
  name: string;
  quantity: number;
  disposition: string | null;
  disposition_label: string | null;
  stock_effect: string | null;
};

export type AdminAfterSalesAction = {
  id: number;
  action_number: string;
  action_type: string;
  action_type_label: string;
  status: string;
  status_label: string;
  inventory_handling: string | null;
  assignee: AdminAfterSalesUser | null;
  creator: AdminAfterSalesUser | null;
  starter: AdminAfterSalesUser | null;
  completer: AdminAfterSalesUser | null;
  canceller: AdminAfterSalesUser | null;
  scheduled_at: string | null;
  due_at: string | null;
  started_at: string | null;
  completed_at: string | null;
  cancelled_at: string | null;
  amount_rsd: number | null;
  reference: string | null;
  public_note: string | null;
  internal_note: string | null;
  completion_note: string | null;
  cancellation_reason: string | null;
  items: AdminAfterSalesActionItem[];
  payment: {
    id: number;
    payment_number: string;
    entry_type: string;
    status: string;
    amount_rsd: number;
  } | null;
  work_order: {
    id: number;
    work_order_number: string;
    status: string;
    status_label: string;
    planned_start_at: string | null;
    planned_end_at: string | null;
    team: AdminAfterSalesUser | null;
  } | null;
  capabilities: {
    start: boolean;
    complete: boolean;
    cancel: boolean;
  };
};

export type AdminAfterSalesMessageLimits = {
  max_attachments: number;
  max_attachment_bytes: number;
  attachment_mime_types: string[];
};

export type AdminAfterSalesDetail = AdminAfterSalesSummary & {
  description: string;
  requested_resolution: string | null;
  requested_resolution_label: string | null;
  resolution_type: string | null;
  resolution_type_label: string | null;
  resolution_summary: string | null;
  customer_snapshot: {
    name: string | null;
    phone: string | null;
    address: string | null;
  };
  first_response_at: string | null;
  resolved_at: string | null;
  closed_at: string | null;
  items: AdminAfterSalesCaseItem[];
  messages: AdminAfterSalesMessage[];
  attachments: AdminAfterSalesAttachment[];
  history: AdminAfterSalesHistory[];
  actions: AdminAfterSalesAction[];
  options: {
    case: {
      types: AdminAfterSalesOptionMap;
      priorities: AdminAfterSalesOptionMap;
      statuses: AdminAfterSalesOptionMap;
      resolutions: AdminAfterSalesOptionMap;
    };
    actions: {
      types: AdminAfterSalesOptionMap;
      statuses: AdminAfterSalesOptionMap;
      dispositions: AdminAfterSalesOptionMap;
      inventory_handling: AdminAfterSalesOptionMap;
    };
    assignees: AdminAfterSalesUser[];
    field_teams: AdminAfterSalesUser[];
    message_limits: AdminAfterSalesMessageLimits;
  };
  capabilities: AdminAfterSalesCaseCapabilities;
};

export type AdminAfterSalesListParams = {
  q?: string;
  status?: string;
  priority?: string;
  case_type?: string;
  overdue?: boolean;
  execution_pending?: boolean;
  page?: number;
  per_page?: number;
};

export type AdminAfterSalesListResponse = {
  data: AdminAfterSalesSummary[];
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
    types: AdminAfterSalesOptionMap;
    priorities: AdminAfterSalesOptionMap;
    statuses: AdminAfterSalesOptionMap;
  };
  capabilities: AdminAfterSalesCaseCapabilities;
};

export type AdminAfterSalesDetailResponse = { data: AdminAfterSalesDetail };

export type AdminAfterSalesCaseUpdateInput = {
  status: string;
  priority: string;
  assigned_to: number | null;
  due_at: string | null;
  resolution_type: string | null;
  resolution_summary: string | null;
  note: string | null;
};

export type AdminAfterSalesMessageInput = {
  body: string;
  visibility: 'public' | 'internal';
  attachments?: AfterSalesUploadFile[];
};

export type AdminAfterSalesActionItemInput = {
  selected?: boolean;
  quantity?: number;
  disposition?: string;
};

export type AdminAfterSalesActionItemsInput = Record<string, AdminAfterSalesActionItemInput>;

export type AdminAfterSalesActionItemDraft = {
  case_item_id: number;
  selected: boolean;
  quantity: number;
  disposition?: string | null;
};

export type AdminAfterSalesActionCreateInput = {
  action_type: string;
  inventory_handling?: string | null;
  assigned_to?: number | null;
  scheduled_at?: string | null;
  scheduled_end_at?: string | null;
  field_service_team_id?: number | null;
  due_at?: string | null;
  amount_rsd?: number | null;
  reference?: string | null;
  public_note?: string | null;
  internal_note?: string | null;
  items: AdminAfterSalesActionItemsInput;
};

export type AdminAfterSalesActionCompleteInput = {
  reference?: string | null;
  completion_note?: string | null;
};

export type AdminAfterSalesMutationResponse<T = unknown> = {
  data: T;
  meta?: { invalidates?: string[] };
};

export function keyedAdminAfterSalesActionItems(
  rows: readonly AdminAfterSalesActionItemDraft[],
): AdminAfterSalesActionItemsInput {
  const result: AdminAfterSalesActionItemsInput = {};

  for (const row of rows) {
    if (!row.selected) continue;
    if (!Number.isInteger(row.case_item_id) || row.case_item_id <= 0) {
      throw new Error('Neispravan identifikator stavke postprodajnog slučaja.');
    }
    if (!Number.isInteger(row.quantity) || row.quantity <= 0) {
      throw new Error('Količina izvršne radnje mora biti pozitivan ceo broj.');
    }
    result[String(row.case_item_id)] = {
      selected: true,
      quantity: row.quantity,
      ...(row.disposition ? { disposition: row.disposition } : {}),
    };
  }

  if (Object.keys(result).length === 0) {
    throw new Error('Izaberi najmanje jednu stavku za izvršnu radnju.');
  }

  return result;
}

function messageFormData(input: AdminAfterSalesMessageInput): FormData {
  const body = new FormData();
  body.append('body', input.body);
  body.append('visibility', input.visibility);
  for (const attachment of input.attachments ?? []) {
    body.append('attachments[]', new File(attachment.uri));
  }
  return body;
}

export const apiAdminAfterSales = {
  list: (params: AdminAfterSalesListParams = {}) =>
    apiRequest<AdminAfterSalesListResponse> (`admin/after-sales${queryString({
      q: params.q,
      status: params.status,
      priority: params.priority,
      case_type: params.case_type,
      overdue: params.overdue ? '1' : undefined,
      execution_pending: params.execution_pending ? '1' : undefined,
      page: params.page,
      per_page: params.per_page,
    })}`),
  detail: (caseId: number) =>
    apiRequest<AdminAfterSalesDetailResponse> (`admin/after-sales/${caseId}`),
  update: (caseId: number, input: AdminAfterSalesCaseUpdateInput) =>
    apiRequest<AdminAfterSalesMutationResponse<AdminAfterSalesDetail>> (`admin/after-sales/${caseId}`, {
      method: 'PATCH',
      body: input,
    }),
  message: (caseId: number, input: AdminAfterSalesMessageInput) =>
    apiExpoMultipartRequest<AdminAfterSalesMutationResponse<AdminAfterSalesMessage>> (
      `admin/after-sales/${caseId}/messages`,
      messageFormData(input),
    ),
  actionCreate: (caseId: number, input: AdminAfterSalesActionCreateInput) =>
    apiRequest<AdminAfterSalesMutationResponse> (`admin/after-sales/${caseId}/actions`, {
      method: 'POST',
      body: input,
    }),
  actionStart: (caseId: number, actionId: number) =>
    apiRequest<AdminAfterSalesMutationResponse> (`admin/after-sales/${caseId}/actions/${actionId}/start`, {
      method: 'POST',
    }),
  actionComplete: (caseId: number, actionId: number, input: AdminAfterSalesActionCompleteInput) =>
    apiRequest<AdminAfterSalesMutationResponse> (`admin/after-sales/${caseId}/actions/${actionId}/complete`, {
      method: 'POST',
      body: input,
    }),
  actionCancel: (caseId: number, actionId: number, cancellation_reason: string) =>
    apiRequest<AdminAfterSalesMutationResponse> (`admin/after-sales/${caseId}/actions/${actionId}/cancel`, {
      method: 'POST',
      body: { cancellation_reason },
    }),
  attachmentPath: (attachmentId: number) => `/api/v1/admin/after-sales/attachments/${attachmentId}`,
};
