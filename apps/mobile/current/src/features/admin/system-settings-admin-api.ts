import { File } from 'expo-file-system';

import { apiExpoMultipartRequest, apiRequest } from '@/lib/api/client';

export type AdminSettingsMap = Record<string, string>;

export type AdminAutomationAlert = {
  id: number;
  type: string;
  severity: string;
  title: string;
  message: string;
  status: string;
  first_detected_at: string | null;
  last_detected_at: string | null;
};

export type AdminAutomationRun = {
  id: number;
  task: string;
  status: string;
  created_alerts: number;
  updated_alerts: number;
  resolved_alerts: number;
  notifications_sent: number;
  started_at: string | null;
  finished_at: string | null;
  error_message: string | null;
};

export type AdminAutomationState = {
  settings: AdminSettingsMap;
  ready: boolean;
  missing: string[];
  stats: { open: number; danger: number; warning: number; failed_runs: number };
  alerts: AdminAutomationAlert[];
  runs: AdminAutomationRun[];
  capabilities: { manage_settings: boolean; run_automation: boolean };
};

export type AdminTurnstileState = {
  settings: AdminSettingsMap;
  secret_configured: boolean;
  secret_source: string;
  site_key_source: string;
};

export type AdminAppearanceSlide = {
  slot: number;
  path: string;
  url: string | null;
  active: boolean;
  order: number;
};

export type AdminUploadLimits = { mime_types: string[]; max_bytes: number };

export type AdminAppearanceState = {
  settings: AdminSettingsMap;
  assets: {
    logo_light_url: string | null;
    logo_dark_url: string | null;
    favicon_url: string | null;
    login_background_url: string | null;
    login_fallback_url: string | null;
    slides: AdminAppearanceSlide[];
  };
  can_manage_login_background: boolean;
  upload_limits: { images: AdminUploadLimits; favicon: AdminUploadLimits };
};

export type AdminOrderEmailRow = {
  id: number;
  recipient_email: string;
  event_type: string;
  subject: string;
  status: string;
  attempt_count: number;
  scheduled_for: string | null;
  sent_at: string | null;
  last_error: string | null;
};

export type AdminOrderEmailState = {
  settings: AdminSettingsMap;
  schema_ready: boolean;
  stats: { pending: number; failed: number; sent_today: number };
  recent: AdminOrderEmailRow[];
  intervals: number[];
};

export type AdminDocumentState = {
  settings: AdminSettingsMap;
  document_logo_url: string | null;
  upload_limits: AdminUploadLimits;
};

export type AdminBankAccount = {
  id: number;
  label: string;
  recipient_name: string;
  recipient_address: string | null;
  account_number: string;
  account_number_display: string;
  payment_code: string;
  is_active: boolean;
};

export type AdminBankAccountInput = {
  label: string;
  recipient_name: string;
  recipient_address?: string | null;
  account_number: string;
  payment_code: string;
  is_active: boolean;
};

export type AdminSystemFile = {
  uri: string;
  name: string;
  type: string;
  size: number;
};

type Envelope<T> = { data: T; message?: string };

function boolValue(value: boolean): string {
  return value ? '1' : '0';
}

// MOBILE_V1_0_SYSTEM_SETTINGS_PARITY_BATCH37_V3_EXPO_FILE_PICKER_OVERLOAD
export async function pickAdminSystemFiles(
  limits: AdminUploadLimits,
  multipleFiles = false,
): Promise<AdminSystemFile[]> {
  let selected: File[];

  if (multipleFiles) {
    const result = await File.pickFileAsync({
      mimeTypes: limits.mime_types,
      multipleFiles: true,
    });
    if (result.canceled) return [];
    selected = result.result;
  } else {
    const result = await File.pickFileAsync({ mimeTypes: limits.mime_types });
    if (result.canceled) return [];
    selected = [result.result];
  }

  return selected.map((file) => {
    if (!file.exists || !Number.isFinite(file.size) || file.size <= 0) {
      throw new Error(`${file.name}: fajl nije dostupan ili je prazan.`);
    }
    if (file.size > limits.max_bytes) {
      throw new Error(`${file.name}: fajl prelazi dozvoljeni limit.`);
    }
    const type = file.type === 'image/jpg' ? 'image/jpeg' : file.type;
    return { uri: file.uri, name: file.name, type, size: file.size };
  });
}

export function appendAdminSystemFile(form: FormData, field: string, file: AdminSystemFile): void {
  form.append(field, new File(file.uri));
}

export function appendAdminSystemFiles(form: FormData, field: string, files: AdminSystemFile[]): void {
  files.forEach((file) => form.append(`${field}[]`, new File(file.uri)));
}

export const apiAdminSystemSettings = {
  automation: {
    state: () => apiRequest<Envelope<AdminAutomationState>>('admin/system-settings/automation'),
    update: (body: Record<string, string | number | boolean>) => apiRequest<Envelope<AdminAutomationState>>('admin/system-settings/automation', { method: 'PUT', body }),
    run: (digest: boolean) => apiRequest<Envelope<AdminAutomationState>>('admin/system-settings/automation/run', { method: 'POST', body: { digest } }),
    resolveAlert: (alertId: number) => apiRequest<Envelope<AdminAutomationState>>(`admin/system-settings/automation/alerts/${alertId}/resolve`, { method: 'POST' }),
  },
  turnstile: {
    state: () => apiRequest<Envelope<AdminTurnstileState>>('admin/system-settings/turnstile'),
    update: (body: { turnstile_enabled: boolean; turnstile_site_key: string; turnstile_secret_key?: string; turnstile_expected_hostname: string }) =>
      apiRequest<Envelope<AdminTurnstileState>>('admin/system-settings/turnstile', { method: 'PUT', body }),
  },
  appearance: {
    state: () => apiRequest<Envelope<AdminAppearanceState>>('admin/system-settings/appearance'),
    update: (form: FormData) => apiExpoMultipartRequest<Envelope<AdminAppearanceState>>('admin/system-settings/appearance', form, 45_000),
    removeAsset: (asset: string) => apiRequest<Envelope<AdminAppearanceState>>(`admin/system-settings/appearance/assets/${encodeURIComponent(asset)}/remove`, { method: 'POST' }),
  },
  orderEmails: {
    state: () => apiRequest<Envelope<AdminOrderEmailState>>('admin/system-settings/order-emails'),
    update: (body: Record<string, string | number | boolean>) => apiRequest<Envelope<AdminOrderEmailState>>('admin/system-settings/order-emails', { method: 'PUT', body }),
    dispatch: () => apiRequest<Envelope<AdminOrderEmailState>>('admin/system-settings/order-emails/dispatch', { method: 'POST' }),
    retry: () => apiRequest<Envelope<AdminOrderEmailState>>('admin/system-settings/order-emails/retry', { method: 'POST' }),
  },
  documents: {
    state: () => apiRequest<Envelope<AdminDocumentState>>('admin/system-settings/documents'),
    update: (form: FormData) => apiExpoMultipartRequest<Envelope<AdminDocumentState>>('admin/system-settings/documents', form, 45_000),
    removeLogo: () => apiRequest<Envelope<AdminDocumentState>>('admin/system-settings/documents/logo', { method: 'DELETE' }),
  },
  bankAccounts: {
    state: () => apiRequest<Envelope<{ accounts: AdminBankAccount[] }>>('admin/system-settings/bank-accounts'),
    create: (body: AdminBankAccountInput) => apiRequest<Envelope<{ accounts: AdminBankAccount[] }>>('admin/system-settings/bank-accounts', { method: 'POST', body: { ...body, is_active: boolValue(body.is_active) } }),
    update: (id: number, body: AdminBankAccountInput) => apiRequest<Envelope<{ accounts: AdminBankAccount[] }>>(`admin/system-settings/bank-accounts/${id}`, { method: 'PATCH', body: { ...body, is_active: boolValue(body.is_active) } }),
    remove: (id: number) => apiRequest<Envelope<{ accounts: AdminBankAccount[] }>>(`admin/system-settings/bank-accounts/${id}`, { method: 'DELETE' }),
  },
};
