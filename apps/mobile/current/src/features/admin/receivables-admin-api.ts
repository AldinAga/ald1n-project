import { File, Paths } from 'expo-file-system';
import { Platform } from 'react-native';

import { apiDownload, apiRequest, queryString } from '@/lib/api/client';

export type AdminReceivableUser = {
  id: number;
  name: string;
  email: string | null;
};

export type AdminReceivableOrder = {
  id: number;
  order_number: string;
  status: string;
  payment_state: string | null;
  subtotal_rsd: number;
  paid_total_rsd: number;
  payment_due_at: string | null;
  customer: AdminReceivableUser | null;
  supplier: AdminReceivableUser | null;
};

export type AdminReceivableSummary = {
  id: number;
  case_number: string;
  status: string;
  status_label: string;
  collection_stage: number;
  assigned_to: AdminReceivableUser | null;
  next_action_at: string | null;
  promised_payment_at: string | null;
  last_contact_at: string | null;
  last_reminder_stage: number | null;
  last_reminder_at: string | null;
  closed_at: string | null;
  order: AdminReceivableOrder | null;
  remaining_rsd: number;
  days_overdue: number;
  aging_bucket: string;
  created_at: string | null;
  updated_at: string | null;
};

export type AdminReceivableInstallment = {
  id: number;
  sequence_no: number;
  due_at: string | null;
  amount_rsd: number;
  paid_amount_rsd: number;
  status: string;
  paid_at: string | null;
  note: string | null;
};

export type AdminReceivableContact = {
  id: number;
  channel: string;
  direction: string;
  subject: string | null;
  note: string | null;
  visible_to_customer: boolean;
  is_automatic: boolean;
  contacted_at: string | null;
  user: AdminReceivableUser | null;
};

export type AdminReceivableDetail = AdminReceivableSummary & {
  internal_note: string | null;
  created_by: AdminReceivableUser | null;
  updated_by: AdminReceivableUser | null;
  installments: AdminReceivableInstallment[];
  contacts: AdminReceivableContact[];
};

export type AdminReceivableStats = {
  active: number;
  promised: number;
  plans: number;
  actions_overdue: number;
};

export type AdminReceivableSettings = {
  receivables_enabled: string;
  receivables_auto_create_cases: string;
  receivables_auto_reminders_enabled: string;
  receivables_due_soon_days: string;
  receivables_reminder_stages: string;
  receivables_pause_on_promise: string;
  receivables_attach_document: string;
  receivables_send_creator: string;
  receivables_send_supplier: string;
  receivables_custom_recipients: string;
};

export type AdminReceivableListParams = {
  q?: string;
  status?: string;
  assigned_to?: number;
  action?: 'overdue' | 'today' | 'promised';
  aging?: 'current' | '1_7' | '8_15' | '16_30' | '31_60' | '61_90' | '90_plus';
  page?: number;
  per_page?: number;
};

export type AdminReceivableListResponse = {
  data: AdminReceivableSummary[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    stats: AdminReceivableStats;
  };
  filters: Record<string, string | number | null>;
  filter_options: {
    statuses: Record<string, string>;
    actions: string[];
    aging: string[];
    assignees: AdminReceivableUser[];
  };
  settings: AdminReceivableSettings;
  capabilities: {
    can_manage: boolean;
    can_export_csv: boolean;
    can_run_scan: boolean;
    can_update_settings: boolean;
  };
};

export type AdminReceivableDetailResponse = {
  data: AdminReceivableDetail;
  options: {
    statuses: Record<string, string>;
    assignees: AdminReceivableUser[];
    settings: AdminReceivableSettings;
    max_installments: number;
  };
  capabilities: {
    can_update: boolean;
    can_replace_plan: boolean;
    can_add_contact: boolean;
    can_send_reminder: boolean;
  };
};

export type AdminReceivableMutationResponse = {
  data: AdminReceivableDetail;
  meta?: { invalidates?: string[] };
};

export type AdminReceivableUpdateInput = {
  status?: string;
  assigned_to?: number | null;
  next_action_at?: string | null;
  promised_payment_at?: string | null;
  internal_note?: string | null;
};

export type AdminReceivablePlanInput = {
  installments: Array<{
    due_at: string;
    amount_rsd: number;
    note?: string | null;
  }>;
};

export type AdminReceivableContactInput = {
  channel: string;
  direction: string;
  subject?: string | null;
  note: string;
  visible_to_customer: boolean;
};

export type AdminReceivableReminderInput = {
  message?: string | null;
};

export type AdminReceivableSettingsInput = {
  receivables_enabled: boolean;
  receivables_auto_create_cases: boolean;
  receivables_auto_reminders_enabled: boolean;
  receivables_due_soon_days: number;
  receivables_reminder_stages: string;
  receivables_pause_on_promise: boolean;
  receivables_attach_document: string;
  receivables_send_creator: boolean;
  receivables_send_supplier: boolean;
  receivables_custom_recipients: string;
};

export type AdminReceivableSettingsResponse = {
  data: AdminReceivableSettings;
  message: string;
};

export type AdminReceivableScanResponse = {
  data: {
    examined: number;
    cases_created: number;
    reminders: number;
    closed: number;
    skipped_promises: number;
  };
  message: string;
};

export const apiAdminReceivables = {
  list: (params: AdminReceivableListParams = {}) =>
    apiRequest<AdminReceivableListResponse> (`admin/receivables${queryString({
      q: params.q,
      status: params.status,
      assigned_to: params.assigned_to,
      action: params.action,
      aging: params.aging,
      page: params.page,
      per_page: params.per_page,
    })}`),
  detail: (receivableId: number) =>
    apiRequest<AdminReceivableDetailResponse> (`admin/receivables/${receivableId}`),
  update: (receivableId: number, input: AdminReceivableUpdateInput) =>
    apiRequest<AdminReceivableMutationResponse> (`admin/receivables/${receivableId}`, {
      method: 'PATCH',
      body: input,
    }),
  replacePlan: (receivableId: number, input: AdminReceivablePlanInput) =>
    apiRequest<AdminReceivableMutationResponse> (`admin/receivables/${receivableId}/plan`, {
      method: 'PUT',
      body: input,
    }),
  addContact: (receivableId: number, input: AdminReceivableContactInput) =>
    apiRequest<AdminReceivableMutationResponse> (`admin/receivables/${receivableId}/contacts`, {
      method: 'POST',
      body: input,
    }),
  sendReminder: (receivableId: number, input: AdminReceivableReminderInput = {}) =>
    apiRequest<AdminReceivableMutationResponse> (`admin/receivables/${receivableId}/reminder`, {
      method: 'POST',
      body: input,
    }),
  updateSettings: (input: AdminReceivableSettingsInput) =>
    apiRequest<AdminReceivableSettingsResponse> ('admin/receivables/settings', {
      method: 'PUT',
      body: input,
    }),
  scan: () => apiRequest<AdminReceivableScanResponse> ('admin/receivables/scan', { method: 'POST' }),
  csvPath: () => '/api/v1/admin/receivables/export.csv',
};

function normalizedContentType(value: string | null): string {
  return (value ?? '').split(';', 1)[0]?.trim().toLowerCase() ?? '';
}

export async function shareAdminReceivablesCsv(): Promise<void> {
  if (Platform.OS === 'web') {
    throw new Error('CSV izvoz je dostupan u Android/iOS aplikaciji.');
  }

  const response = await apiDownload(apiAdminReceivables.csvPath());
  if (response.bytes.byteLength <= 0) throw new Error('CSV izvoz je prazan.');
  if (response.contentLength !== null && response.contentLength !== response.bytes.byteLength) {
    throw new Error('CSV izvoz nije preuzet u celosti.');
  }

  const mime = normalizedContentType(response.contentType);
  if (mime !== 'text/csv' && mime !== 'application/csv' && mime !== 'application/octet-stream') {
    throw new Error('Server je vratio neočekivan tip CSV izvoza.');
  }

  const file = new File(Paths.cache, `admin-receivables-${Date.now()}.csv`);
  file.create({ overwrite: true, intermediates: true });
  file.write(response.bytes);
  if (!file.exists || file.size !== response.bytes.byteLength) {
    if (file.exists) file.delete();
    throw new Error('CSV izvoz nije moguće bezbedno sačuvati u privremeni prostor aplikacije.');
  }

  const Sharing = await import('expo-sharing');
  if (!(await Sharing.isAvailableAsync())) {
    throw new Error('Sistemsko deljenje CSV fajla nije dostupno na ovom uređaju.');
  }
  await Sharing.shareAsync(file.uri, {
    dialogTitle: 'Potraživanja CSV',
    mimeType: 'text/csv',
  });
}
