import { File, Paths } from 'expo-file-system';
import { Platform } from 'react-native';

import { apiDownload } from '@/lib/api/client';

export type DownloadedWarrantyPdf = {
  uri: string;
  name: string;
  size: number;
};

function sanitizeWarrantyNumber(value: string): string {
  const cleaned = value
    .trim()
    .replace(/[\\/:*?"<>|]/g, '-')
    .replace(/[\u0000-\u001f\u007f]/g, '-')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-')
    .replace(/^-|-$/g, '')
    .slice(0, 100);

  return cleaned || 'garantni-list';
}

function assertWarrantyId(warrantyId: number): void {
  if (!Number.isInteger(warrantyId) || warrantyId <= 0) {
    throw new Error('Neispravan identifikator garancije.');
  }
}

function expectedWarrantyPdfPath(warrantyId: number): string {
  assertWarrantyId(warrantyId);
  return `/api/v1/warranties/${warrantyId}.pdf`;
}

function expectedAdminWarrantyPdfPath(warrantyId: number): string {
  assertWarrantyId(warrantyId);
  return `admin/warranties/${warrantyId}.pdf`;
}

function hasPdfSignature(bytes: Uint8Array): boolean {
  return bytes.byteLength >= 5
    && bytes[0] === 0x25
    && bytes[1] === 0x50
    && bytes[2] === 0x44
    && bytes[3] === 0x46
    && bytes[4] === 0x2d;
}

async function loadSharingModule() {
  if (Platform.OS === 'web') {
    throw new Error('Otvaranje garantnog lista je dostupno u Android/iOS aplikaciji.');
  }

  try {
    return await import('expo-sharing');
  } catch {
    throw new Error('Otvaranje garantnog lista zahteva noviju verziju aplikacije.');
  }
}

async function downloadWarrantyPdfFromPath(
  path: string,
  warrantyId: number,
  warrantyNumber: string,
  cachePrefix: string,
): Promise<DownloadedWarrantyPdf> {
  const response = await apiDownload(path);

  if (response.bytes.byteLength === 0) {
    throw new Error('Preuzeti garantni list je prazan.');
  }

  if (
    response.contentLength !== null
    && response.contentLength !== response.bytes.byteLength
  ) {
    throw new Error('Preuzimanje garantnog lista nije završeno u celosti.');
  }

  const contentType = response.contentType
    ?.split(';', 1)[0]
    ?.trim()
    .toLowerCase() ?? '';

  if (contentType !== 'application/pdf' || !hasPdfSignature(response.bytes)) {
    throw new Error('Server nije vratio ispravan PDF garantnog lista.');
  }

  const safeNumber = sanitizeWarrantyNumber(warrantyNumber);
  const name = `${safeNumber}.pdf`;
  const file = new File(
    Paths.cache,
    `${cachePrefix}-${warrantyId}-${name}`,
  );

  file.create({
    overwrite: true,
    intermediates: true,
  });
  file.write(response.bytes);

  if (!file.exists || file.size !== response.bytes.byteLength) {
    if (file.exists) file.delete();
    throw new Error('Garantni list nije moguće bezbedno sačuvati u privremeni prostor aplikacije.');
  }

  return {
    uri: file.uri,
    name,
    size: file.size,
  };
}

async function openDownloadedWarrantyPdf(
  downloaded: DownloadedWarrantyPdf,
  warrantyNumber: string,
): Promise<void> {
  const Sharing = await loadSharingModule();
  const available = await Sharing.isAvailableAsync();

  if (!available) {
    throw new Error('Sistemsko otvaranje PDF dokumenta nije dostupno na ovom uređaju.');
  }

  await Sharing.shareAsync(downloaded.uri, {
    mimeType: 'application/pdf',
    dialogTitle: `Garantni list ${warrantyNumber}`,
  });
}

export async function downloadWarrantyPdf(
  warrantyId: number,
  warrantyNumber: string,
): Promise<DownloadedWarrantyPdf> {
  return downloadWarrantyPdfFromPath(
    expectedWarrantyPdfPath(warrantyId),
    warrantyId,
    warrantyNumber,
    'warranty',
  );
}

export async function openWarrantyPdf(
  warrantyId: number,
  warrantyNumber: string,
): Promise<void> {
  const downloaded = await downloadWarrantyPdf(
    warrantyId,
    warrantyNumber,
  );
  await openDownloadedWarrantyPdf(downloaded, warrantyNumber);
}

export async function downloadAdminWarrantyPdf(
  warrantyId: number,
  warrantyNumber: string,
): Promise<DownloadedWarrantyPdf> {
  return downloadWarrantyPdfFromPath(
    expectedAdminWarrantyPdfPath(warrantyId),
    warrantyId,
    warrantyNumber,
    'admin-warranty',
  );
}

export async function openAdminWarrantyPdf(
  warrantyId: number,
  warrantyNumber: string,
): Promise<void> {
  const downloaded = await downloadAdminWarrantyPdf(
    warrantyId,
    warrantyNumber,
  );
  await openDownloadedWarrantyPdf(downloaded, warrantyNumber);
}
