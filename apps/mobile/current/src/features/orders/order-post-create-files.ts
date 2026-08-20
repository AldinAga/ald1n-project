import { File, Paths } from 'expo-file-system';
import { Platform } from 'react-native';

import { apiDownload } from '@/lib/api/client';
import { api } from '@/lib/api/endpoints';
import type {
  OrderDeliverySummary,
  OrderDocumentSummary,
  OrderPaymentLedgerEntry,
  OrderPaymentProofLimits,
  OrderPaymentProofUploadFile,
  OrderPrivateFile,
} from '@/types/api';

type DownloadedOrderFile = {
  uri: string;
  name: string;
  mimeType: string;
  size: number;
};

const MIME_BY_EXTENSION: Record<string, string> = {
  '.jpeg': 'image/jpeg',
  '.jpg': 'image/jpeg',
  '.pdf': 'application/pdf',
  '.png': 'image/png',
  '.webp': 'image/webp',
};

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

function normalizeMime(value: string): string {
  const normalized = value
    .split(';', 1)[0]
    ?.trim()
    .toLowerCase() ?? '';

  return normalized === 'image/jpg'
    ? 'image/jpeg'
    : normalized;
}

function pickedMime(file: File): string {
  const reported = normalizeMime(file.type);

  if (
    reported
    && reported !== 'application/octet-stream'
  ) {
    return reported;
  }

  return MIME_BY_EXTENSION[file.extension.toLowerCase()]
    ?? reported;
}

function extensionFromName(value: string): string {
  const match = value
    .trim()
    .toLowerCase()
    .match(/\.([a-z0-9]+)$/);

  return match?.[1] ?? '';
}

function hasPdfSignature(bytes: Uint8Array): boolean {
  return bytes.byteLength >= 5
    && bytes[0] === 0x25
    && bytes[1] === 0x50
    && bytes[2] === 0x44
    && bytes[3] === 0x46
    && bytes[4] === 0x2d;
}

function assertPositiveId(value: number, label: string): void {
  if (!Number.isInteger(value) || value <= 0) {
    throw new Error(`Neispravan identifikator ${label}.`);
  }
}

async function loadSharingModule() {
  if (Platform.OS === 'web') {
    throw new Error('Otvaranje privatnih fajlova je dostupno u Android/iOS aplikaciji.');
  }

  try {
    return await import('expo-sharing');
  } catch {
    throw new Error('Otvaranje fajlova zahteva noviju verziju aplikacije.');
  }
}

async function downloadPrivateFile({
  path,
  cacheName,
  expected,
  requirePdf = false,
}: {
  path: string;
  cacheName: string;
  expected?: OrderPrivateFile | null;
  requirePdf?: boolean;
}): Promise<DownloadedOrderFile> {
  const response = await apiDownload(path);

  if (response.bytes.byteLength === 0) {
    throw new Error('Preuzeti fajl je prazan.');
  }

  if (
    response.contentLength !== null
    && response.contentLength !== response.bytes.byteLength
  ) {
    throw new Error('Preuzimanje fajla nije završeno u celosti.');
  }

  if (
    expected
    && expected.size_bytes > 0
    && expected.size_bytes !== response.bytes.byteLength
  ) {
    throw new Error('Veličina preuzetog fajla se ne poklapa sa server podacima.');
  }

  const responseMime = normalizeMime(response.contentType ?? '');
  const expectedMime = normalizeMime(expected?.mime_type ?? '');

  if (
    expectedMime
    && responseMime
    && expectedMime !== responseMime
  ) {
    throw new Error('Server je vratio neočekivan tip privatnog fajla.');
  }

  if (
    requirePdf
    && (
      responseMime !== 'application/pdf'
      || !hasPdfSignature(response.bytes)
    )
  ) {
    throw new Error('Server nije vratio ispravan PDF dokument.');
  }

  const safeName = sanitizeFileName(
    expected?.original_name || cacheName,
  );
  const file = new File(
    Paths.cache,
    `order-private-${safeName}`,
  );

  file.create({
    overwrite: true,
    intermediates: true,
  });
  file.write(response.bytes);

  if (!file.exists || file.size !== response.bytes.byteLength) {
    if (file.exists) file.delete();
    throw new Error('Fajl nije moguće bezbedno sačuvati u privremeni prostor aplikacije.');
  }

  return {
    uri: file.uri,
    name: safeName,
    mimeType: requirePdf
      ? 'application/pdf'
      : expectedMime || responseMime || 'application/octet-stream',
    size: file.size,
  };
}

async function openDownloadedFile(
  downloaded: DownloadedOrderFile,
): Promise<void> {
  const Sharing = await loadSharingModule();
  const available = await Sharing.isAvailableAsync();

  if (!available) {
    throw new Error('Sistemsko otvaranje fajla nije dostupno na ovom uređaju.');
  }

  await Sharing.shareAsync(downloaded.uri, {
    dialogTitle: `Otvori / podeli ${downloaded.name}`,
    mimeType: downloaded.mimeType,
  });
}

export function formatOrderFileSize(
  bytes: number | undefined,
): string {
  if (!Number.isFinite(bytes) || !bytes || bytes <= 0) {
    return '0 B';
  }

  if (bytes < 1024) {
    return `${Math.round(bytes)} B`;
  }

  if (bytes < 1024 * 1024) {
    return `${(bytes / 1024).toFixed(1)} KB`;
  }

  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export async function pickOrderPaymentProof(
  limits: OrderPaymentProofLimits,
): Promise<OrderPaymentProofUploadFile | null> {
  const result = await File.pickFileAsync({
    mimeTypes: limits.mime_types,
  });

  if (result.canceled) {
    return null;
  }

  const file = result.result;

  if (!file.exists || file.size <= 0) {
    throw new Error('Izabrani fajl nije dostupan za čitanje.');
  }

  const mimeType = pickedMime(file);
  const extension = extensionFromName(file.name);
  const allowedExtensions = limits.extensions.map((value) =>
    value.trim().toLowerCase().replace(/^\./, ''),
  );
  const allowedMimeTypes = limits.mime_types.map(normalizeMime);

  if (
    !allowedMimeTypes.includes(mimeType)
    || !allowedExtensions.includes(extension)
  ) {
    throw new Error('Potvrda mora biti PDF, JPG, PNG ili WebP fajl dozvoljen server pravilima.');
  }

  if (file.size > limits.max_bytes) {
    throw new Error(`Potvrda ne sme biti veća od ${formatOrderFileSize(limits.max_bytes)}.`);
  }

  return {
    uri: file.uri,
    name: file.name,
    type: mimeType,
    size: file.size,
  };
}

export async function openOrderConfirmationPdf(
  orderId: number,
  orderNumber: string,
): Promise<void> {
  assertPositiveId(orderId, 'porudžbine');
  const downloaded = await downloadPrivateFile({
    path: api.orders.confirmationPdfPath(orderId),
    cacheName: `potvrda-${sanitizeFileName(orderNumber)}.pdf`,
    requirePdf: true,
  });
  await openDownloadedFile(downloaded);
}

export async function openOrderDocumentPdf(
  orderId: number,
  document: OrderDocumentSummary,
): Promise<void> {
  assertPositiveId(orderId, 'porudžbine');
  assertPositiveId(document.id, 'dokumenta');
  const downloaded = await downloadPrivateFile({
    path: api.orders.documentPdfPath(orderId, document.id),
    cacheName: `${sanitizeFileName(document.type)}-${sanitizeFileName(document.number)}.pdf`,
    requirePdf: true,
  });
  await openDownloadedFile(downloaded);
}

export async function openOrderPaymentProof(
  orderId: number,
  payment: OrderPaymentLedgerEntry,
): Promise<void> {
  assertPositiveId(orderId, 'porudžbine');
  assertPositiveId(payment.id, 'uplate');

  if (!payment.has_proof || !payment.proof) {
    throw new Error('Potvrda uplate nije dostupna.');
  }

  const downloaded = await downloadPrivateFile({
    path: api.orders.paymentProofPath(orderId, payment.id),
    cacheName: `uplata-${payment.id}-${payment.proof.original_name}`,
    expected: payment.proof,
    requirePdf: normalizeMime(payment.proof.mime_type) === 'application/pdf',
  });
  await openDownloadedFile(downloaded);
}

export async function openOrderDeliveryProof(
  orderId: number,
  delivery: OrderDeliverySummary,
): Promise<void> {
  assertPositiveId(orderId, 'porudžbine');
  assertPositiveId(delivery.id, 'isporuke');

  if (!delivery.has_proof || !delivery.proof) {
    throw new Error('Dokaz isporuke nije dostupan.');
  }

  const downloaded = await downloadPrivateFile({
    path: api.orders.deliveryProofPath(orderId),
    cacheName: `isporuka-${delivery.id}-${delivery.proof.original_name}`,
    expected: delivery.proof,
    requirePdf: normalizeMime(delivery.proof.mime_type) === 'application/pdf',
  });
  await openDownloadedFile(downloaded);
}
