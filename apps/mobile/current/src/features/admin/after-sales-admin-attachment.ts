import { File, Paths } from 'expo-file-system';
import { Platform } from 'react-native';

import type { AdminAfterSalesAttachment } from '@/features/admin/after-sales-admin-api';
import { apiAdminAfterSales } from '@/features/admin/after-sales-admin-api';
import { apiDownload } from '@/lib/api/client';

const ALLOWED_MIME_TYPES = [
  'application/pdf',
  'image/jpeg',
  'image/png',
  'image/webp',
];

function normalizeMime(value: string | null | undefined): string {
  const normalized = (value ?? '').split(';', 1)[0]?.trim().toLowerCase() ?? '';
  return normalized === 'image/jpg' ? 'image/jpeg' : normalized;
}

function extensionForMime(mime: string): string {
  if (mime === 'application/pdf') return 'pdf';
  if (mime === 'image/png') return 'png';
  if (mime === 'image/webp') return 'webp';
  return 'jpg';
}

function safeBaseName(value: string): string {
  const cleaned = value
    .trim()
    .replace(/[\\/:*?"<>|]/g, '-')
    .replace(/[\u0000-\u001f\u007f]/g, '-')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-')
    .replace(/^-|-$/g, '')
    .replace(/\.[A-Za-z0-9]+$/, '')
    .slice(0, 120);
  return cleaned || 'after-sales-prilog';
}

export async function openAdminAfterSalesAttachment(
  attachment: Pick<AdminAfterSalesAttachment, 'id' | 'original_name'>,
): Promise<void> {
  if (!Number.isInteger(attachment.id) || attachment.id <= 0) {
    throw new Error('Neispravan identifikator priloga.');
  }
  if (Platform.OS === 'web') {
    throw new Error('Otvaranje privatnog priloga dostupno je u Android/iOS aplikaciji.');
  }

  const response = await apiDownload(apiAdminAfterSales.attachmentPath(attachment.id));
  if (response.bytes.byteLength <= 0) throw new Error('Preuzeti prilog je prazan.');
  if (response.contentLength !== null && response.contentLength !== response.bytes.byteLength) {
    throw new Error('Preuzimanje priloga nije završeno u celosti.');
  }

  const mime = normalizeMime(response.contentType);
  if (!ALLOWED_MIME_TYPES.includes(mime)) {
    throw new Error('Server je vratio neočekivan tip privatnog priloga.');
  }

  const file = new File(
    Paths.cache,
    `admin-after-sales-${attachment.id}-${safeBaseName(attachment.original_name)}.${extensionForMime(mime)}`,
  );
  file.create({ overwrite: true, intermediates: true });
  file.write(response.bytes);

  if (!file.exists || file.size !== response.bytes.byteLength) {
    if (file.exists) file.delete();
    throw new Error('Prilog nije moguće bezbedno sačuvati u privremeni prostor aplikacije.');
  }

  const Sharing = await import('expo-sharing');
  if (!(await Sharing.isAvailableAsync())) {
    throw new Error('Sistemsko otvaranje fajla nije dostupno na ovom uređaju.');
  }
  await Sharing.shareAsync(file.uri, {
    dialogTitle: attachment.original_name,
    mimeType: mime,
  });
}
