import { File, Paths } from 'expo-file-system';
import { Platform } from 'react-native';

import {
  apiAdminAuditEvents,
  type AdminAuditRequestParams,
} from '@/features/admin/audit-admin-api';
import { apiDownload } from '@/lib/api/client';

async function loadSharingModule() {
  if (Platform.OS === 'web') {
    throw new Error('CSV audit izvoz je dostupan u mobilnoj aplikaciji.');
  }
  return import('expo-sharing');
}

// MOBILE_V1_0_AUDIT_CSV_EXPORT_PARITY_BATCH29
export async function openAdminAuditExport(
  params: AdminAuditRequestParams = {},
): Promise<void> {
  const response = await apiDownload(apiAdminAuditEvents.exportPath(params));
  if (response.bytes.byteLength === 0) {
    throw new Error('Server je vratio prazan audit CSV.');
  }
  if (response.contentLength !== null && response.contentLength !== response.bytes.byteLength) {
    throw new Error('Audit CSV nije preuzet u celosti.');
  }

  const contentType = (response.contentType ?? '').split(';', 1)[0]?.trim().toLowerCase() ?? '';
  if (contentType !== 'text/csv') {
    throw new Error('Server nije vratio ispravan audit CSV.');
  }

  const file = new File(Paths.cache, `admin-audit-${Date.now()}.csv`);
  file.create({ overwrite: true, intermediates: true });
  file.write(response.bytes);
  if (!file.exists || file.size !== response.bytes.byteLength) {
    if (file.exists) file.delete();
    throw new Error('Audit CSV nije moguće bezbedno sačuvati u privremeni prostor aplikacije.');
  }

  const Sharing = await loadSharingModule();
  if (!await Sharing.isAvailableAsync()) {
    throw new Error('Sistemsko otvaranje CSV fajla nije dostupno na ovom uređaju.');
  }
  await Sharing.shareAsync(file.uri, {
    dialogTitle: 'Audit i bezbednost · CSV',
    mimeType: 'text/csv',
  });
}