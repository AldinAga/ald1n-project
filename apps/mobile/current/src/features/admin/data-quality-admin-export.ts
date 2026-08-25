import { File, Paths } from 'expo-file-system';
import { Platform } from 'react-native';

import { apiAdminDataQuality } from '@/features/admin/data-quality-admin-api';
import { apiDownload } from '@/lib/api/client';

async function loadSharingModule() {
  if (Platform.OS === 'web') throw new Error('Data Quality JSON izvoz je dostupan u mobilnoj aplikaciji.');
  return import('expo-sharing');
}

// MOBILE_V1_0_CATALOG_ADVANCED_PARITY_BATCH35
export async function openAdminDataQualityExport(): Promise<void> {
  const response = await apiDownload(apiAdminDataQuality.exportPath());
  if (response.bytes.byteLength === 0) throw new Error('Server je vratio prazan Data Quality JSON.');
  if (response.contentLength !== null && response.contentLength !== response.bytes.byteLength) {
    throw new Error('Data Quality JSON nije preuzet u celosti.');
  }
  const contentType = (response.contentType ?? '').split(';', 1)[0]?.trim().toLowerCase() ?? '';
  if (contentType !== 'application/json') throw new Error('Server nije vratio ispravan Data Quality JSON.');

  const file = new File(Paths.cache, `data-quality-${Date.now()}.json`);
  file.create({ overwrite: true, intermediates: true });
  file.write(response.bytes);
  if (!file.exists || file.size !== response.bytes.byteLength) {
    if (file.exists) file.delete();
    throw new Error('Data Quality JSON nije moguće bezbedno sačuvati u privremeni prostor aplikacije.');
  }
  const Sharing = await loadSharingModule();
  if (!await Sharing.isAvailableAsync()) throw new Error('Sistemsko otvaranje JSON fajla nije dostupno.');
  await Sharing.shareAsync(file.uri, { dialogTitle: 'Data Quality · JSON', mimeType: 'application/json' });
}
