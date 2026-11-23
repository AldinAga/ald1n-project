import { File } from 'expo-file-system';
import * as ImagePicker from 'expo-image-picker';

import type {
  AdminProductImageLimits,
  AdminProductImageUploadFile,
} from '@/types/api';

export type ProductImagePickResult = {
  files: AdminProductImageUploadFile[];
  rejected: string[];
  canceled: boolean;
};

const MIME_BY_EXTENSION: Record<string, string> = {
  '.jpeg': 'image/jpeg',
  '.jpg': 'image/jpeg',
  '.png': 'image/png',
  '.webp': 'image/webp',
};

function normalizeMime(file: File): string {
  const reported = file.type.trim().toLowerCase();

  if (reported === 'image/jpg') return 'image/jpeg';
  if (reported && reported !== 'application/octet-stream') return reported;

  return MIME_BY_EXTENSION[file.extension.toLowerCase()] ?? reported;
}

function imageKey(file: Pick<AdminProductImageUploadFile, 'uri' | 'name' | 'size'>): string {
  return [file.uri, file.name, String(file.size)].join('\u0000');
}

export function formatProductImageSize(bytes: number): string {
  if (!Number.isFinite(bytes) || bytes <= 0) return '0 B';
  if (bytes < 1024) return `${Math.round(bytes)} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export async function pickProductImages(
  current: AdminProductImageUploadFile[],
  limits: AdminProductImageLimits,
): Promise<ProductImagePickResult> {
  const remaining = Math.max(0, limits.max_files - current.length);

  if (remaining === 0) {
    return {
      files: [],
      rejected: [`Možeš dodati najviše ${limits.max_files} fotografija.`],
      canceled: false,
    };
  }

  let selected: File[];

  if (remaining === 1) {
    const result = await File.pickFileAsync({ mimeTypes: limits.mime_types });
    if (result.canceled) return { files: [], rejected: [], canceled: true };
    selected = [result.result];
  } else {
    const result = await File.pickFileAsync({
      multipleFiles: true,
      mimeTypes: limits.mime_types,
    });
    if (result.canceled) return { files: [], rejected: [], canceled: true };
    selected = result.result;
  }

  return validatePickedFiles(selected, current, limits, remaining);
}

export type ProductCameraPermission = 'granted' | 'ask' | 'blocked';

// Rationale is shown by the caller only for 'ask'; 'blocked' means Android will not show the dialog again.
export async function getProductCameraPermission(): Promise<ProductCameraPermission> {
  const state = await ImagePicker.getCameraPermissionsAsync();
  if (state.granted) return 'granted';
  return state.canAskAgain ? 'ask' : 'blocked';
}

// One photo per call (owner decision 2026-11-24). Same validation and draft flow as the file picker.
export async function captureProductImage(
  current: AdminProductImageUploadFile[],
  limits: AdminProductImageLimits,
): Promise<ProductImagePickResult> {
  if (limits.max_files - current.length <= 0) {
    return {
      files: [],
      rejected: [`Možeš dodati najviše ${limits.max_files} fotografija.`],
      canceled: false,
    };
  }

  const permission = await ImagePicker.requestCameraPermissionsAsync();
  if (!permission.granted) {
    return {
      files: [],
      rejected: ['Kamera nije dozvoljena. Dozvoli kameru u podešavanjima telefona za Ald1n CMS.'],
      canceled: false,
    };
  }

  const shot = await ImagePicker.launchCameraAsync({
    mediaTypes: ['images'],
    allowsEditing: false,
    // Server limit is 10 MB per image; 0.8 keeps 50+ MP phone JPEGs under it.
    quality: 0.8,
  });
  const asset = shot.assets?.[0];
  if (shot.canceled || !asset) return { files: [], rejected: [], canceled: true };

  return validatePickedFiles([new File(asset.uri)], current, limits, 1);
}

function validatePickedFiles(
  selected: File[],
  current: AdminProductImageUploadFile[],
  limits: AdminProductImageLimits,
  remaining: number,
): ProductImagePickResult {
  const existing = new Set(current.map(imageKey));
  const accepted: AdminProductImageUploadFile[] = [];
  const rejected: string[] = [];

  for (const file of selected) {
    if (accepted.length >= remaining) {
      rejected.push(`Dostignut je limit od ${limits.max_files} fotografija.`);
      break;
    }

    const mimeType = normalizeMime(file);
    const extension = file.extension.replace(/^\./, '').toLowerCase();
    const mimeAllowed = limits.mime_types.includes(mimeType);
    // Android DocumentsProvider can expose valid images without a filename extension.
    const extensionAllowed = extension === '' || limits.extensions.includes(extension);
    const candidate: AdminProductImageUploadFile = {
      uri: file.uri,
      name: file.name,
      type: mimeType,
      size: file.size,
    };

    if (!file.exists) {
      rejected.push(`${file.name}: fajl nije dostupan za čitanje.`);
      continue;
    }

    if (!Number.isFinite(file.size) || file.size <= 0) {
      rejected.push(`${file.name}: veličina fajla nije ispravna.`);
      continue;
    }

    if (!mimeAllowed || !extensionAllowed) {
      rejected.push(`${file.name}: dozvoljeni su JPG, JPEG, PNG i WebP.`);
      continue;
    }

    if (file.size > limits.max_bytes) {
      rejected.push(`${file.name}: fajl je veći od ${formatProductImageSize(limits.max_bytes)}.`);
      continue;
    }

    const key = imageKey(candidate);
    if (existing.has(key)) {
      rejected.push(`${file.name}: fotografija je već dodata.`);
      continue;
    }

    existing.add(key);
    accepted.push(candidate);
  }

  return { files: accepted, rejected, canceled: false };
}
