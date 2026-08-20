import { File, Paths } from 'expo-file-system';
import { Platform } from 'react-native';

import { apiAdminCommissions, type AdminCommissionListParams } from '@/features/admin/commissions-admin-api';
import { apiDownload } from '@/lib/api/client';

export type AdminCommissionExportFormat = 'csv' | 'pdf';

function hasPdfSignature(bytes: Uint8Array): boolean {
  return bytes.length >= 5
    && bytes[0] === 0x25
    && bytes[1] === 0x50
    && bytes[2] === 0x44
    && bytes[3] === 0x46
    && bytes[4] === 0x2d;
}

async function loadSharingModule() {
  if (Platform.OS === 'web') {
    throw new Error('Izvoz fajla je dostupan u mobilnoj aplikaciji.');
  }
  return import('expo-sharing');
}

export async function openAdminCommissionExport(
  format: AdminCommissionExportFormat,
  params: AdminCommissionListParams,
): Promise<void> {
  const response = await apiDownload(apiAdminCommissions.exportPath(format, params));
  if (response.bytes.byteLength === 0) {
    throw new Error('Server je vratio prazan izvoz provizija.');
  }
  if (response.contentLength !== null && response.contentLength !== response.bytes.byteLength) {
    throw new Error('Izvoz provizija nije preuzet u celosti.');
  }

  const contentType = (response.contentType ?? '').split(';', 1)[0]?.trim().toLowerCase() ?? '';
  if (format === 'pdf' && (contentType !== 'application/pdf' || !hasPdfSignature(response.bytes))) {
    throw new Error('Server nije vratio ispravan PDF izveštaj provizija.');
  }
  if (format === 'csv' && contentType !== 'text/csv') {
    throw new Error('Server nije vratio ispravan CSV izveštaj provizija.');
  }

  const file = new File(Paths.cache, `admin-provizije-${Date.now()}.${format}`);
  file.create({ overwrite: true, intermediates: true });
  file.write(response.bytes);
  if (!file.exists || file.size !== response.bytes.byteLength) {
    if (file.exists) file.delete();
    throw new Error('Izvoz nije moguće bezbedno sačuvati u privremeni prostor aplikacije.');
  }

  const Sharing = await loadSharingModule();
  if (!await Sharing.isAvailableAsync()) {
    throw new Error('Sistemsko otvaranje fajla nije dostupno na ovom uređaju.');
  }
  await Sharing.shareAsync(file.uri, {
    dialogTitle: format === 'pdf' ? 'Izveštaj provizija' : 'CSV provizija',
    mimeType: format === 'pdf' ? 'application/pdf' : 'text/csv',
  });
}
