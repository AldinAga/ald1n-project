import { File, Paths } from 'expo-file-system';
import { Platform } from 'react-native';

import type { AdminOrderProofFile } from '@/features/admin/orders-admin-api';
import { apiAdminOrders } from '@/features/admin/orders-admin-api';
import { apiDownload } from '@/lib/api/client';

const MAX_PROOF_BYTES = 10 * 1024 * 1024;
const ALLOWED_MIME_TYPES = [
  'application/pdf',
  'image/jpeg',
  'image/png',
  'image/webp',
];
const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];

function normalizeMime(value: string | null | undefined): string {
  const normalized = (value ?? '').split(';', 1)[0]?.trim().toLowerCase() ?? '';
  return normalized === 'image/jpg' ? 'image/jpeg' : normalized;
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
  return cleaned || 'dokaz-slanja';
}

function extensionFromMime(mime: string): string {
  if (mime === 'application/pdf') return 'pdf';
  if (mime === 'image/png') return 'png';
  if (mime === 'image/webp') return 'webp';
  return 'jpg';
}

export function formatAdminOrderProofSize(bytes: number): string {
  if (!Number.isFinite(bytes) || bytes <= 0) return '0 B';
  if (bytes < 1024) return `${Math.round(bytes)} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export async function pickAdminOrderProof(): Promise<AdminOrderProofFile | null> {
  if (Platform.OS === 'web') {
    throw new Error('Izbor privatnog dokaza je dostupan u Android/iOS aplikaciji.');
  }

  const result = await File.pickFileAsync({ mimeTypes: ALLOWED_MIME_TYPES });
  if (result.canceled) return null;

  const file = result.result;
  const mime = normalizeMime(file.type);
  const extension = file.extension.replace(/^\./, '').toLowerCase();
  const mimeAllowed = ALLOWED_MIME_TYPES.includes(mime);
  const extensionAllowed = extension === '' || ALLOWED_EXTENSIONS.includes(extension);

  if (!file.exists) throw new Error('Izabrani fajl nije dostupan za čitanje.');
  if (!Number.isFinite(file.size) || file.size <= 0) throw new Error('Veličina izabranog fajla nije ispravna.');
  if (!mimeAllowed || !extensionAllowed) throw new Error('Dozvoljeni su PDF, JPG, JPEG, PNG i WebP fajlovi.');
  if (file.size > MAX_PROOF_BYTES) throw new Error(`Dokaz može imati najviše ${formatAdminOrderProofSize(MAX_PROOF_BYTES)}.`);

  return {
    uri: file.uri,
    name: file.name,
    type: mime,
    size: file.size,
  };
}

export async function openAdminOrderShipmentProof(orderId: number, originalName = 'dokaz-slanja'): Promise<void> {
  if (!Number.isInteger(orderId) || orderId <= 0) throw new Error('Neispravan identifikator porudžbine.');
  if (Platform.OS === 'web') throw new Error('Otvaranje privatnog dokaza je dostupno u Android/iOS aplikaciji.');

  const response = await apiDownload(apiAdminOrders.shipmentProofPath(orderId));
  if (response.bytes.byteLength === 0) throw new Error('Preuzeti dokaz je prazan.');
  if (response.contentLength !== null && response.contentLength !== response.bytes.byteLength) {
    throw new Error('Preuzimanje dokaza nije završeno u celosti.');
  }

  const mime = normalizeMime(response.contentType);
  if (!ALLOWED_MIME_TYPES.includes(mime)) {
    throw new Error('Server je vratio neočekivan tip privatnog dokaza.');
  }
  const safeBase = sanitizeFileName(originalName).replace(/\.[A-Za-z0-9]+$/, '');
  const extension = extensionFromMime(mime);
  const file = new File(Paths.cache, `admin-order-${orderId}-${safeBase}.${extension}`);
  file.create({ overwrite: true, intermediates: true });
  file.write(response.bytes);

  if (!file.exists || file.size !== response.bytes.byteLength) {
    if (file.exists) file.delete();
    throw new Error('Dokaz nije moguće bezbedno sačuvati u privremeni prostor aplikacije.');
  }

  const sharing = await import('expo-sharing');
  if (!(await sharing.isAvailableAsync())) {
    throw new Error('Sistemsko otvaranje fajla nije dostupno na ovom uređaju.');
  }
  await sharing.shareAsync(file.uri, {
    dialogTitle: 'Dokaz slanja pošiljke',
    mimeType: mime,
  });
}
