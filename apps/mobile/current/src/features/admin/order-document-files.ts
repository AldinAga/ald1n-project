import { File, Paths } from 'expo-file-system';
import { Platform } from 'react-native';

import { apiAdminOrders } from '@/features/admin/orders-admin-api';
import { apiDownload } from '@/lib/api/client';

export type AdminOrderDocumentPdfRef = {
  id: number;
  type: string;
  number: string;
};

function assertPositiveId(value: number, label: string): void {
  if (!Number.isInteger(value) || value <= 0) {
    throw new Error(`Neispravan identifikator ${label}.`);
  }
}

function sanitizeFileName(value: string): string {
  const cleaned = value
    .trim()
    .replace(/[\\/:*?"<>|]/g, '-')
    .replace(/[\u0000-\u001f\u007f]/g, '-')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-')
    .replace(/^-|-$/g, '')
    .slice(0, 140);

  return cleaned || 'dokument';
}

function normalizedMime(value: string | null): string {
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

async function openPdfPath(path: string, cacheName: string, dialogTitle: string): Promise<void> {
  if (Platform.OS === 'web') {
    throw new Error('Administratorski PDF dokumenti se otvaraju kroz Android/iOS aplikaciju.');
  }

  const response = await apiDownload(path);
  const mime = normalizedMime(response.contentType);

  if (
    response.bytes.byteLength === 0
    || mime !== 'application/pdf'
    || !hasPdfSignature(response.bytes)
  ) {
    throw new Error('Server nije vratio ispravan PDF dokument.');
  }

  if (
    response.contentLength !== null
    && response.contentLength !== response.bytes.byteLength
  ) {
    throw new Error('Preuzimanje PDF dokumenta nije završeno u celosti.');
  }

  const safeName = sanitizeFileName(cacheName);
  const file = new File(Paths.cache, `admin-order-${safeName}`);
  file.create({ overwrite: true, intermediates: true });
  file.write(response.bytes);

  if (!file.exists || file.size !== response.bytes.byteLength) {
    if (file.exists) file.delete();
    throw new Error('PDF nije moguće bezbedno sačuvati u privremeni prostor aplikacije.');
  }

  const Sharing = await import('expo-sharing');
  const available = await Sharing.isAvailableAsync();
  if (!available) {
    throw new Error('Sistemsko otvaranje PDF dokumenta nije dostupno na ovom uređaju.');
  }

  await Sharing.shareAsync(file.uri, {
    dialogTitle,
    mimeType: 'application/pdf',
  });
}

export async function openAdminOrderConfirmationPdf(orderId: number): Promise<void> {
  assertPositiveId(orderId, 'porudžbine');
  await openPdfPath(
    apiAdminOrders.documentConfirmationPdfPath(orderId),
    `potvrda-${orderId}.pdf`,
    'Otvori / podeli potvrdu porudžbine',
  );
}

export async function openAdminOrderDocumentPdf(
  orderId: number,
  document: AdminOrderDocumentPdfRef,
): Promise<void> {
  assertPositiveId(orderId, 'porudžbine');
  assertPositiveId(document.id, 'dokumenta');
  await openPdfPath(
    apiAdminOrders.documentPdfPath(orderId, document.id),
    `${document.type}-${document.number}.pdf`,
    `Otvori / podeli ${document.number}`,
  );
}
