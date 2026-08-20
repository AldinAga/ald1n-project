import { File, Paths } from 'expo-file-system';
import { Platform } from 'react-native';

import { apiDownload } from '@/lib/api/client';
import type { AfterSalesAttachment } from '@/types/api';

export type DownloadedAfterSalesAttachment = {
  uri: string;
  name: string;
  mimeType: string;
  size: number;
};

function sanitizeFileName(value: string): string {
  const cleaned = value
    .trim()
    .replace(/[\\/:*?"<>|]/g, '_')
    .replace(/[\u0000-\u001f\u007f]/g, '_')
    .replace(/\s+/g, ' ')
    .slice(0, 140);

  return cleaned || 'prilog';
}

function expectedDownloadPath(attachment: AfterSalesAttachment): string {
  if (!Number.isInteger(attachment.id) || attachment.id <= 0) {
    throw new Error('Neispravan identifikator priloga.');
  }

  const expected = new Set([
    `/api/v1/after-sales/attachments/${attachment.id}` ,
    `/api/v1/field-work-order-attachments/${attachment.id}` ,
  ]);

  if (!expected.has(attachment.download_path)) {
    throw new Error('Server je vratio neočekivanu putanju privatnog priloga.');
  }

  return attachment.download_path;
}

function attachmentCachePrefix(attachment: AfterSalesAttachment): string {
  return attachment.download_path.startsWith('/api/v1/field-work-order-attachments/')
    ? 'field-work'
    : 'after-sales';
}

async function loadSharingModule() {
  if (Platform.OS === 'web') {
    throw new Error('Otvaranje privatnih priloga je dostupno u Android/iOS aplikaciji.');
  }

  try {
    return await import('expo-sharing');
  } catch {
    throw new Error('Otvaranje priloga zahteva noviju verziju aplikacije.');
  }
}
export async function downloadAfterSalesAttachment(
  attachment: AfterSalesAttachment,
): Promise<DownloadedAfterSalesAttachment> {
  const path = expectedDownloadPath(attachment);
  const response = await apiDownload(path);

  if (response.bytes.byteLength === 0) {
    throw new Error('Preuzeti prilog je prazan.');
  }

  if (
    attachment.size_bytes > 0
    && response.bytes.byteLength !== attachment.size_bytes
  ) {
    throw new Error('Veličina preuzetog priloga se ne poklapa sa server podacima.');
  }

  if (
    response.contentLength !== null
    && response.contentLength !== response.bytes.byteLength
  ) {
    throw new Error('Preuzimanje priloga nije završeno u celosti.');
  }

  const safeName = sanitizeFileName(attachment.original_name);
  const file = new File(
    Paths.cache,
    `${attachmentCachePrefix(attachment)}-${attachment.id}-${safeName}`,
  );

  file.create({
    overwrite: true,
    intermediates: true,
  });
  file.write(response.bytes);

  if (!file.exists || file.size !== response.bytes.byteLength) {
    if (file.exists) file.delete();
    throw new Error('Prilog nije moguće bezbedno sačuvati u privremeni prostor aplikacije.');
  }

  return {
    uri: file.uri,
    name: safeName,
    mimeType: attachment.mime_type || response.contentType || 'application/octet-stream',
    size: file.size,
  };
}

export async function openAfterSalesAttachment(
  attachment: AfterSalesAttachment,
): Promise<void> {
  const Sharing = await loadSharingModule();
  const available = await Sharing.isAvailableAsync();

  if (!available) {
    throw new Error('Sistemsko otvaranje priloga nije dostupno na ovom uređaju.');
  }

  const downloaded = await downloadAfterSalesAttachment(attachment);

  await Sharing.shareAsync(downloaded.uri, {
    dialogTitle: `Otvori / podeli ${downloaded.name}`,
    mimeType: downloaded.mimeType,
  });
}
