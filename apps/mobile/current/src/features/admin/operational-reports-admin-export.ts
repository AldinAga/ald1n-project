import { File, Paths } from 'expo-file-system';
import { Platform } from 'react-native';

import {
  adminOperationalReportExportPath,
  type AdminOperationalReportFilters,
  type AdminOperationalReportKind,
} from '@/features/admin/reports-admin-api';
import { apiDownload } from '@/lib/api/client';

function contentType(value: string | null): string {
  return value?.split(';', 1)[0]?.trim().toLowerCase() ?? '';
}

function hasPdfSignature(bytes: Uint8Array): boolean {
  return bytes.byteLength >= 5
    && bytes[0] === 0x25
    && bytes[1] === 0x50
    && bytes[2] === 0x44
    && bytes[3] === 0x46
    && bytes[4] === 0x2d;
}

function extension(kind: AdminOperationalReportKind): 'pdf' | 'csv' {
  return kind === 'orders-pdf' ? 'pdf' : 'csv';
}

function label(kind: AdminOperationalReportKind): string {
  return ({
    'orders-pdf': 'porudzbine-pdf',
    'orders-csv': 'porudzbine-csv',
    'payments-csv': 'uplate-csv',
    'inventory-csv': 'lager-csv',
  } as const)[kind];
}

export async function openAdminOperationalReportExport(
  kind: AdminOperationalReportKind,
  filters: AdminOperationalReportFilters = {},
): Promise<void> {
  if (Platform.OS === 'web') throw new Error('Operativni izvoz je dostupan u Android/iOS aplikaciji.');

  const response = await apiDownload(adminOperationalReportExportPath(kind, filters));
  if (response.bytes.byteLength === 0) throw new Error('Preuzeti operativni izveštaj je prazan.');
  if (response.contentLength !== null && response.contentLength !== response.bytes.byteLength) {
    throw new Error('Preuzimanje operativnog izveštaja nije završeno u celosti.');
  }

  const ext = extension(kind);
  const mime = contentType(response.contentType);
  if (ext === 'pdf') {
    if (mime !== 'application/pdf' || !hasPdfSignature(response.bytes)) throw new Error('Server nije vratio ispravan PDF.');
  } else if (mime !== 'text/csv') {
    throw new Error('Server nije vratio ispravan CSV.');
  }

  const name = `${label(kind)}-${Date.now()}.${ext}`;
  const file = new File(Paths.cache, `admin-operational-report-${name}`);
  file.create({ overwrite: true, intermediates: true });
  file.write(response.bytes);
  if (!file.exists || file.size !== response.bytes.byteLength) {
    if (file.exists) file.delete();
    throw new Error('Operativni izveštaj nije moguće bezbedno sačuvati u privremeni prostor.');
  }

  const Sharing = await import('expo-sharing');
  if (!await Sharing.isAvailableAsync()) throw new Error('Sistemsko otvaranje ili deljenje nije dostupno.');
  await Sharing.shareAsync(file.uri, {
    mimeType: ext === 'pdf' ? 'application/pdf' : 'text/csv',
    dialogTitle: ext === 'pdf' ? 'Operativni izveštaj PDF' : 'Operativni izveštaj CSV',
  });
}
