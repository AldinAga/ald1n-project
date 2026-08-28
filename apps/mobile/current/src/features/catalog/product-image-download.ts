import { File, Paths } from 'expo-file-system';
import { Platform } from 'react-native';

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
  url: string;
  productName: string;
  productSku: string;
  imageNumber: number;
};

function sanitizeFilePart(value: string): string {
  return value
    .trim()
    .replace(/[\\/:*?"<>|]/g, '-')
    .replace(/[\u0000-\u001f\u007f]/g, '-')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-')
    .replace(/^-|-$/g, '')
    .slice(0, 80) || 'artikal';
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
    throw new Error('URL fotografije nije ispravan.');
  }

  if (parsed.protocol !== 'https:' && parsed.protocol !== 'http:') {
    throw new Error('Fotografija mora koristiti HTTP ili HTTPS adresu.');
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

export async function downloadAndShareProductImage(
  input: ProductImageDownloadInput,
): Promise<void> {
  assertRemoteImageUrl(input.url);

  const response = await fetch(input.url, { method: 'GET' });
  if (!response.ok) {
    throw new Error(`Preuzimanje fotografije nije uspelo (${response.status}).`);
  }

  const contentLengthHeader = response.headers.get('content-length');
  const contentLength = contentLengthHeader ? Number(contentLengthHeader) : null;
  if (
    contentLength !== null
    && Number.isFinite(contentLength)
    && contentLength > MAX_PRODUCT_IMAGE_BYTES
  ) {
    throw new Error('Fotografija je veća od dozvoljenih 30 MB.');
  }

  const rawContentType = response.headers
    .get('content-type')
    ?.split(';', 1)[0]
    ?.trim()
    .toLowerCase() ?? '';
  const urlExtension = extensionFromUrl(input.url);
  const mimeExtension = MIME_EXTENSIONS[rawContentType] ?? null;
  const extension = mimeExtension ?? urlExtension ?? 'jpg';
  const mimeType = rawContentType.startsWith('image/')
    ? rawContentType
    : EXTENSION_MIME[extension] ?? 'image/jpeg';

  if (
    rawContentType !== ''
    && !rawContentType.startsWith('image/')
    && rawContentType !== 'application/octet-stream'
    && urlExtension === null
  ) {
    throw new Error('Server nije vratio fotografiju.');
  }

  const bytes = new Uint8Array(await response.arrayBuffer());
  if (bytes.byteLength === 0) {
    throw new Error('Preuzeta fotografija je prazna.');
  }
  if (bytes.byteLength > MAX_PRODUCT_IMAGE_BYTES) {
    throw new Error('Fotografija je veća od dozvoljenih 30 MB.');
  }

  const safeSku = sanitizeFilePart(input.productSku || input.productName);
  const safeNumber = Math.max(1, Math.floor(input.imageNumber));
  const file = new File(
    Paths.cache,
    `${safeSku}-slika-${safeNumber}.${extension}`,
  );

  file.create({ overwrite: true, intermediates: true });
  file.write(bytes);

  if (!file.exists || file.size !== bytes.byteLength) {
    if (file.exists) file.delete();
    throw new Error('Fotografiju nije moguće bezbedno pripremiti za čuvanje.');
  }

  const Sharing = await loadSharingModule();
  const available = await Sharing.isAvailableAsync();
  if (!available) {
    throw new Error('Sistemski meni za čuvanje fotografije nije dostupan.');
  }

  await Sharing.shareAsync(file.uri, {
    mimeType,
    dialogTitle: `${input.productName} - slika ${safeNumber}`,
  });
}
