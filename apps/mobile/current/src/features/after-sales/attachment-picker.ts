import { File } from 'expo-file-system';

import type {
  AfterSalesOptions,
  AfterSalesUploadFile,
} from '@/types/api';

type AttachmentLimits = Pick<
  AfterSalesOptions['limits'],
  | 'max_attachments'
  | 'max_attachment_bytes'
  | 'attachment_mime_types'
>;

export type AfterSalesAttachmentPickResult = {
  files: AfterSalesUploadFile[];
  rejected: string[];
  canceled: boolean;
};

const MIME_BY_EXTENSION: Record<string, string> = {
  '.jpeg': 'image/jpeg',
  '.jpg': 'image/jpeg',
  '.pdf': 'application/pdf',
  '.png': 'image/png',
  '.webp': 'image/webp',
};

function normalizeMime(file: File): string {
  const reported = file.type.trim().toLowerCase();

  if (reported === 'image/jpg') {
    return 'image/jpeg';
  }

  if (
    reported
    && reported !== 'application/octet-stream'
  ) {
    return reported;
  }

  return (
    MIME_BY_EXTENSION[
      file.extension.toLowerCase()
    ]
    ?? reported
  );
}

function attachmentKey(
  file: Pick<
    AfterSalesUploadFile,
    'uri' | 'name' | 'size'
  >,
): string {
  return [
    file.uri,
    file.name,
    String(file.size ?? -1),
  ].join('\u0000');
}

export function formatAfterSalesAttachmentSize(
  bytes: number | undefined,
): string {
  if (
    !Number.isFinite(bytes)
    || !bytes
    || bytes <= 0
  ) {
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

export async function pickAfterSalesAttachments(
  current: AfterSalesUploadFile[],
  limits: AttachmentLimits,
): Promise<AfterSalesAttachmentPickResult> {
  const remaining = Math.max(
    0,
    limits.max_attachments
      - current.length,
  );

  if (remaining === 0) {
    return {
      files: [],
      rejected: [
        `Možeš dodati najviše ${limits.max_attachments} priloga.`,
      ],
      canceled: false,
    };
  }

  let selected: File[];

  if (remaining === 1) {
    const result =
      await File.pickFileAsync({
        mimeTypes:
          limits.attachment_mime_types,
      });

    if (result.canceled) {
      return {
        files: [],
        rejected: [],
        canceled: true,
      };
    }

    selected = [result.result];
  } else {
    const result =
      await File.pickFileAsync({
        multipleFiles: true,
        mimeTypes:
          limits.attachment_mime_types,
      });

    if (result.canceled) {
      return {
        files: [],
        rejected: [],
        canceled: true,
      };
    }

    selected = result.result;
  }

  const existing = new Set(
    current.map(attachmentKey),
  );

  const accepted:
    AfterSalesUploadFile[] = [];

  const rejected: string[] = [];

  for (const file of selected) {
    if (accepted.length >= remaining) {
      rejected.push(
        `Dostignut je limit od ${limits.max_attachments} priloga.`,
      );
      break;
    }

    const mimeType =
      normalizeMime(file);

    const candidate:
      AfterSalesUploadFile = {
        uri: file.uri,
        name: file.name,
        type: mimeType,
        size: file.size,
      };

    if (!file.exists) {
      rejected.push(
        `${file.name}: fajl nije dostupan za čitanje.`,
      );
      continue;
    }

    if (
      !limits
        .attachment_mime_types
        .includes(mimeType)
    ) {
      rejected.push(
        `${file.name}: tip fajla nije dozvoljen.`,
      );
      continue;
    }

    if (
      file.size
      > limits.max_attachment_bytes
    ) {
      rejected.push(
        `${file.name}: fajl je veći od ${formatAfterSalesAttachmentSize(limits.max_attachment_bytes)}.`,
      );
      continue;
    }

    const key =
      attachmentKey(candidate);

    if (existing.has(key)) {
      rejected.push(
        `${file.name}: prilog je već dodat.`,
      );
      continue;
    }

    existing.add(key);
    accepted.push(candidate);
  }

  return {
    files: accepted,
    rejected,
    canceled: false,
  };
}
