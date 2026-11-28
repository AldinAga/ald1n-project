import { randomUUID } from 'expo-crypto';
import { Directory, File, Paths } from 'expo-file-system';
import { Platform } from 'react-native';

import { imageSaveFolderStore } from '@/lib/storage';

import { OFFLINE_MESSAGE, runProductImageSave, type ProductImageSaveResult } from './product-image-save-flow';

export type { ProductImageSaveResult };

const MAX_PRODUCT_IMAGE_BYTES = 30 * 1024 * 1024;

const MIME_EXTENSIONS: Record<string, string> = {
  'image/jpeg': 'jpg',
  'image/png': 'png',
  'image/webp': 'webp',
  'image/gif': 'gif',
  'image/heic': 'heic',
  'image/heif': 'heif',
  'image/avif': 'avif',
  'image/bmp': 'bmp',
  'image/svg+xml': 'svg',
};

const EXTENSION_MIME: Record<string, string> = {
  jpg: 'image/jpeg',
  jpeg: 'image/jpeg',
  png: 'image/png',
  webp: 'image/webp',
  gif: 'image/gif',
  heic: 'image/heic',
  heif: 'image/heif',
  avif: 'image/avif',
  bmp: 'image/bmp',
  svg: 'image/svg+xml',
};

type ProductImageDownloadInput = {
  originalUrl: string;
  productName: string;
  productSku: string;
  imageNumber: number;
};

function sanitizeFilePart(value: string): string {
  return value.trim().replace(/[\\/:*?"<>|#%]/g, '-').replace(/[\u0000-\u001f\u007f]/g, '-').replace(/\s+/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '').slice(0, 72).replace(/[\uD800-\uDBFF]$/, '') || 'artikal';
}

function extensionFromUrl(url: string): string | null {
  try {
    const pathname = new URL(url).pathname;
    const match = pathname.match(/\.([a-zA-Z0-9]{2,5})$/);
    const rawExtension = match?.[1];
    if (!rawExtension) return null;
    const extension = rawExtension.toLowerCase();
    return EXTENSION_MIME[extension] ? extension : null;
  } catch {
    return null;
  }
}

function assertRemoteImageUrl(url: string): void {
  let parsed: URL;
  try {
    parsed = new URL(url);
  } catch {
    throw new Error('URL originalne fotografije nije ispravan.');
  }
  if (parsed.protocol !== 'https:' && parsed.protocol !== 'http:') {
    throw new Error('Originalna fotografija mora koristiti HTTP ili HTTPS adresu.');
  }
}

async function loadSharingModule() {
  if (Platform.OS === 'web') {
    throw new Error('Čuvanje fotografija je dostupno u Android/iOS aplikaciji.');
  }
  try {
    return await import('expo-sharing');
  } catch {
    throw new Error('Sistemsko čuvanje fotografija nije dostupno u ovoj verziji aplikacije.');
  }
}

type CachedOriginal = { file: File; bytes: Uint8Array; mimeType: string; fileName: string; discard(): void };

async function fetchOriginalToCache(input: ProductImageDownloadInput, forSharing = false): Promise<CachedOriginal> {
  assertRemoteImageUrl(input.originalUrl);

  // BATCH116 invariant: this transport accepts only the canonical original URL.
  // Optimized display/thumbnail URLs are intentionally not part of this input contract.
  let response: Response;
  try {
    response = await fetch(input.originalUrl, { method: 'GET' });
  } catch {
    // RN fetch rejects only on transport failure (no network, DNS, TLS).
    throw new Error(OFFLINE_MESSAGE);
  }
  if (!response.ok || response.status === 206) {
    throw new Error(`Preuzimanje originalne fotografije nije uspelo (HTTP ${response.status}).`);
  }

  const contentLengthHeader = response.headers.get('content-length');
  const contentEncoding = response.headers.get('content-encoding')?.trim().toLowerCase();
  const contentLength = (!contentEncoding || contentEncoding === 'identity') && contentLengthHeader && /^\d+$/.test(contentLengthHeader)
    ? Number(contentLengthHeader)
    : null;
  if (contentLength !== null && contentLength > MAX_PRODUCT_IMAGE_BYTES) {
    throw new Error('Originalna fotografija je veća od dozvoljenih 30 MB.');
  }

  const rawContentType = response.headers.get('content-type')?.split(';', 1)[0]?.trim().toLowerCase() ?? '';
  const urlExtension = extensionFromUrl(input.originalUrl);
  const mimeExtension = MIME_EXTENSIONS[rawContentType] ?? null;
  const extension = mimeExtension ?? urlExtension ?? 'jpg';
  const mimeType = rawContentType.startsWith('image/') ? rawContentType : EXTENSION_MIME[extension] ?? 'image/jpeg';

  if (rawContentType !== '' && !rawContentType.startsWith('image/') && rawContentType !== 'application/octet-stream') {
    throw new Error('Server nije vratio originalnu fotografiju.');
  }

  let buffer: ArrayBuffer;
  try {
    buffer = await response.arrayBuffer();
  } catch {
    throw new Error('Preuzimanje originalne fotografije nije završeno u celosti.');
  }
  const bytes = new Uint8Array(buffer);
  if (bytes.byteLength === 0) throw new Error('Preuzeta originalna fotografija je prazna.');
  if (bytes.byteLength > MAX_PRODUCT_IMAGE_BYTES) throw new Error('Originalna fotografija je veća od dozvoljenih 30 MB.');
  if (contentLength !== null && contentLength !== bytes.byteLength) {
    throw new Error('Preuzimanje originalne fotografije nije završeno u celosti.');
  }

  const safeSku = sanitizeFilePart(input.productSku || input.productName);
  const safeNumber = Math.max(1, Math.floor(input.imageNumber));
  const fileName = `${safeSku}-original-slika-${safeNumber}.${extension}`;
  let discard: (() => void) | undefined;
  let stage = 'create';
  let readBytes: number | null = null;
  try {
    // Sharing keeps a reusable cache path because the receiving app may still read the URI.
    const directory = forSharing ? Paths.cache : new Directory(Paths.cache, `product-image-${randomUUID()}`);
    if (!forSharing) {
      directory.create();
      discard = () => directory.delete();
    }
    const file = new File(directory, fileName);
    file.create({ overwrite: forSharing });
    discard ??= () => file.delete();
    stage = 'write';
    file.write(bytes);
    stage = 'readback';
    readBytes = (await file.bytes()).byteLength;
    if (readBytes !== bytes.byteLength) {
      throw new Error('Nepotpun privremeni fajl.');
    }
    return { file, bytes, mimeType, fileName, discard };
  } catch {
    console.warn('[product-image-cache]', stage, bytes.byteLength, readBytes);
    try {
      discard?.();
    } catch {
      // Keep the original write failure if the cache cannot be cleaned up.
    }
    throw new Error('Upis originalne fotografije u privremeni prostor nije uspeo.');
  }
}

export async function downloadAndShareProductImage(input: ProductImageDownloadInput): Promise<void> {
  const { file, mimeType } = await fetchOriginalToCache(input, true);
  const safeNumber = Math.max(1, Math.floor(input.imageNumber));

  const Sharing = await loadSharingModule();
  const available = await Sharing.isAvailableAsync();
  if (!available) throw new Error('Sistemski meni za deljenje fotografije nije dostupan.');

  await Sharing.shareAsync(file.uri, {
    mimeType,
    dialogTitle: `${input.productName} - originalna slika ${safeNumber}`,
  });
}

// Direct save without any runtime or manifest permission: the Storage Access Framework
// folder picker (expo-file-system, already in the native build) grants a persistable
// write grant for one folder the user picks. No MediaStore insert, no READ_MEDIA_* and no media library module.
export const canSaveProductImagesToDevice = Platform.OS === 'android';

// Opens the picker in Pictures so the saved photos land in the phone's gallery.
const PICTURES_TREE_URI = 'content://com.android.externalstorage.documents/document/primary%3APictures';

async function pickSaveFolder(): Promise<Directory | null> {
  try {
    return await Directory.pickDirectoryAsync(PICTURES_TREE_URI);
  } catch (error) {
    // Closing the picker is a choice, not an error.
    // Native PickerCancelledException: code ERR_PICKER_CANCELLED, message "...was cancelled by the user".
    const coded = error as { code?: unknown; message?: unknown } | null;
    if (/cancel/i.test(`${String(coded?.code ?? '')} ${String(coded?.message ?? '')}`)) return null;
    throw new Error('Izbor foldera za čuvanje nije uspeo.');
  }
}

function openSaveFolder(uri: string): Directory | null {
  try {
    const folder = new Directory(uri);
    return folder.exists ? folder : null;
  } catch {
    return null;
  }
}

async function isOriginalReachable(input: ProductImageDownloadInput): Promise<boolean> {
  try {
    await fetch(input.originalUrl, { method: 'HEAD' });
    return true;
  } catch {
    return false;
  }
}

export async function saveProductImagesToDevice(inputs: ProductImageDownloadInput[]): Promise<ProductImageSaveResult> {
  if (!canSaveProductImagesToDevice) throw new Error('Direktno čuvanje je dostupno u Android aplikaciji. Koristi Podeli.');
  return runProductImageSave(inputs, {
    readStoredFolder: () => imageSaveFolderStore.get(),
    rememberFolder: (uri) => imageSaveFolderStore.set(uri),
    forgetFolder: () => imageSaveFolderStore.clear(),
    openFolder: openSaveFolder,
    pickFolder: pickSaveFolder,
    isOnline: isOriginalReachable,
    fetchOriginal: fetchOriginalToCache,
  });
}

