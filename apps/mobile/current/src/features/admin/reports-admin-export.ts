import { File, Paths } from 'expo-file-system';
import { Platform } from 'react-native';

import {
  adminReportExportPath,
  type AdminReportExportFormat,
  type AdminReportRequestParams,
} from '@/features/admin/reports-admin-api';
import { apiDownload } from '@/lib/api/client';

export type DownloadedAdminReport = {
  uri: string;
  name: string;
  size: number;
  format: AdminReportExportFormat;
};

function hasPdfSignature(bytes: Uint8Array): boolean {
  return bytes.byteLength >= 5
    && bytes[0] === 0x25
    && bytes[1] === 0x50
    && bytes[2] === 0x44
    && bytes[3] === 0x46
    && bytes[4] === 0x2d;
}

function normalizedContentType(value: string | null): string {
  return value?.split(';', 1)[0]?.trim().toLowerCase() ?? '';
}

function validatePayload(
  format: AdminReportExportFormat,
  contentType: string,
  bytes: Uint8Array,
): void {
  if (format === 'pdf') {
    if (contentType !== 'application/pdf' || !hasPdfSignature(bytes)) {
      throw new Error('Server nije vratio ispravan PDF upravljačkog izveštaja.');
    }

    return;
  }

  if (contentType !== 'text/csv') {
    throw new Error('Server nije vratio ispravan CSV upravljačkog izveštaja.');
  }
}

async function loadSharingModule() {
  if (Platform.OS === 'web') {
    throw new Error('Otvaranje izveštaja je dostupno u Android/iOS aplikaciji.');
  }

  try {
    return await import('expo-sharing');
  } catch {
    throw new Error('Otvaranje izveštaja zahteva podržanu verziju aplikacije.');
  }
}

function fileName(
  format: AdminReportExportFormat,
  params: AdminReportRequestParams,
): string {
  const reportType = params.report_type ?? 'management_summary';
  return `upravljacki-izvestaj-${reportType}-${Date.now()}.${format}`;
}

export async function downloadAdminReportExport(
  format: AdminReportExportFormat,
  params: AdminReportRequestParams = {},
): Promise<DownloadedAdminReport> {
  const response = await apiDownload(
    adminReportExportPath(format, params),
  );

  if (response.bytes.byteLength === 0) {
    throw new Error('Preuzeti upravljački izveštaj je prazan.');
  }

  if (
    response.contentLength !== null
    && response.contentLength !== response.bytes.byteLength
  ) {
    throw new Error('Preuzimanje upravljačkog izveštaja nije završeno u celosti.');
  }

  validatePayload(
    format,
    normalizedContentType(response.contentType),
    response.bytes,
  );

  const name = fileName(format, params);
  const file = new File(Paths.cache, `admin-report-${name}`);

  file.create({
    overwrite: true,
    intermediates: true,
  });
  file.write(response.bytes);

  if (!file.exists || file.size !== response.bytes.byteLength) {
    if (file.exists) file.delete();
    throw new Error('Upravljački izveštaj nije moguće bezbedno sačuvati u privremeni prostor aplikacije.');
  }

  return {
    uri: file.uri,
    name,
    size: file.size,
    format,
  };
}

export async function openAdminReportExport(
  format: AdminReportExportFormat,
  params: AdminReportRequestParams = {},
): Promise<void> {
  const downloaded = await downloadAdminReportExport(format, params);
  const Sharing = await loadSharingModule();
  const available = await Sharing.isAvailableAsync();

  if (!available) {
    throw new Error('Sistemsko otvaranje ili deljenje izveštaja nije dostupno na ovom uređaju.');
  }

  await Sharing.shareAsync(downloaded.uri, {
    mimeType: format === 'pdf' ? 'application/pdf' : 'text/csv',
    dialogTitle: format === 'pdf'
      ? 'Upravljački izveštaj PDF'
      : 'Upravljački izveštaj CSV',
  });
}
