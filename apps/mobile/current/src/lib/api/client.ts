import Constants from 'expo-constants';
import { fetch as expoFetch } from 'expo/fetch';
import { tokenStore } from '@/lib/storage';
import type { ApiErrorEnvelope } from '@/types/api';

const configuredUrl = process.env.EXPO_PUBLIC_API_URL ?? Constants.expoConfig?.extra?.apiUrl;
export const API_URL = String(configuredUrl ?? '').replace(/\/+$/, '');

let unauthorizedHandler: (() => void | Promise<void>) | null = null;

export function setUnauthorizedHandler(handler: (() => void | Promise<void>) | null): void {
  unauthorizedHandler = handler;
}

export class ApiError extends Error {
  readonly status: number;
  readonly code: string;
  readonly errors: Record<string, string[]>;
  readonly requestId?: string;

  constructor(status: number, payload: ApiErrorEnvelope) {
    super(payload.message || 'Došlo je do greške pri komunikaciji sa serverom.');
    this.name = 'ApiError';
    this.status = status;
    this.code = payload.code ?? 'api_error';
    this.errors = payload.errors ?? {};
    this.requestId = payload.request_id;
  }

  firstFieldError(): string | null {
    for (const values of Object.values(this.errors)) {
      const first = values[0];
      if (first) return first;
    }
    return null;
  }
}

type RequestOptions = Omit<RequestInit, 'body'> & {
  body?: unknown;
  auth?: boolean;
  timeoutMs?: number;
};

function ensureApiUrl(): void {
  if (!/^https?:\/\//i.test(API_URL)) {
    throw new Error('EXPO_PUBLIC_API_URL nije validan HTTP(S) URL.');
  }
}

export async function apiRequest<T>(path: string, options: RequestOptions = {}): Promise<T> {
  ensureApiUrl();
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), options.timeoutMs ?? 20_000);

  try {
    const token = options.auth === false ? null : await tokenStore.get();
    const headers = new Headers(options.headers);
    headers.set('Accept', 'application/json');
    headers.set('X-Mobile-Client', 'ald1n-mobile/0.4.0');
    if (token) headers.set('Authorization', `Bearer ${token}`);

    let requestBody: RequestInit['body'];

    if (options.body === undefined) {
      requestBody = undefined;
    } else if (typeof FormData !== 'undefined' && options.body instanceof FormData) {
      requestBody = options.body;
    } else {
      headers.set('Content-Type', 'application/json');
      requestBody = JSON.stringify(options.body);
    }

    const response = await fetch(`${API_URL}/${path.replace(/^\//, '')}`, {
      ...options,
      headers,
      body: requestBody,
      signal: controller.signal
    });

    if (response.status === 204) return undefined as T;

    const contentType = response.headers.get('content-type') ?? '';
    const payload = contentType.includes('application/json')
      ? await response.json()
      : { message: await response.text() };

    if (!response.ok) {
      const requestId = response.headers.get('x-request-id') ?? payload?.request_id ?? undefined;
      const errorPayload: ApiErrorEnvelope = {
        message: payload?.message || `Server je vratio status ${response.status}.`,
        code: payload?.code,
        errors: payload?.errors,
        request_id: requestId
      };
      const error = new ApiError(response.status, errorPayload);
      if (response.status === 401 && options.auth !== false && unauthorizedHandler) {
        await unauthorizedHandler();
      }
      throw error;
    }

    return payload as T;
  } catch (error) {
    if (error instanceof ApiError) throw error;
    if (error instanceof Error && error.name === 'AbortError') {
      throw new ApiError(0, { message: 'Server nije odgovorio na vreme.', code: 'request_timeout' });
    }
    throw new ApiError(0, {
      message: error instanceof Error ? error.message : 'Mrežna greška. Proveri internet vezu.',
      code: 'network_error'
    });
  } finally {
    clearTimeout(timeout);
  }
}

// MOBILE_PRODUCT_IMAGE_EXPO_FILE_TRANSPORT_V06
export async function apiExpoMultipartRequest<T>
(
  path: string,
  body: FormData,
  timeoutMs = 30_000,
): Promise<T> {
  ensureApiUrl();
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), timeoutMs);

  try {
    const token = await tokenStore.get();
    const headers = new Headers();
    headers.set('Accept', 'application/json');
    headers.set('X-Mobile-Client', 'ald1n-mobile/0.4.0');
    if (token) headers.set('Authorization', 'Bearer ' + token);

    const response = await expoFetch(API_URL + '/' + path.replace(/^\//, ''), {
      method: 'POST',
      headers,
      body,
      signal: controller.signal,
    });

    const contentType = response.headers.get('content-type') ?? '';
    const payload = contentType.includes('application/json')
      ? await response.json()
      : { message: await response.text() };

    if (!response.ok) {
      const requestId = response.headers.get('x-request-id') ?? payload?.request_id ?? undefined;
      const errorPayload: ApiErrorEnvelope = {
        message: payload?.message || 'Server je vratio status ' + response.status + '.',
        code: payload?.code,
        errors: payload?.errors,
        request_id: requestId,
      };
      const error = new ApiError(response.status, errorPayload);

      if (response.status === 401 && unauthorizedHandler) {
        await unauthorizedHandler();
      }

      throw error;
    }

    return payload as T;
  } catch (error) {
    if (error instanceof ApiError) throw error;
    if (error instanceof Error && error.name === 'AbortError') {
      throw new ApiError(0, {
        message: 'Slanje fajla je isteklo. Pokusaj ponovo.',
        code: 'request_timeout',
      });
    }
    throw new ApiError(0, {
      message: error instanceof Error ? error.message : 'Mrezna greska pri slanju fajla.',
      code: 'network_error',
    });
  } finally {
    clearTimeout(timeout);
  }
}

export type ApiDownloadResult = {
  bytes: Uint8Array;
  contentType: string | null;
  contentLength: number | null;
};

export async function apiDownload(path: string, timeoutMs = 30_000): Promise<ApiDownloadResult> {
  ensureApiUrl();
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), timeoutMs);

  try {
    const token = await tokenStore.get();
    const headers = new Headers();
    headers.set('Accept', 'application/octet-stream');
    headers.set('X-Mobile-Client', 'ald1n-mobile/0.4.0');
    if (token) headers.set('Authorization', `Bearer ${token}`);

    const response = await fetch(`${API_URL}/${path.replace(/^\//, '')}`, {
      method: 'GET',
      headers,
      signal: controller.signal,
    });

    if (!response.ok) {
      const responseType = response.headers.get('content-type') ?? '';
      let payload: ApiErrorEnvelope = {
        message: `Server je vratio status ${response.status}.`,
        code: 'api_error',
      };

      try {
        if (responseType.includes('application/json')) {
          const parsed = await response.json();
          payload = {
            message: parsed?.message || payload.message,
            code: parsed?.code ?? payload.code,
            errors: parsed?.errors,
            request_id: parsed?.request_id,
          };
        } else {
          const text = await response.text();
          if (text.trim()) payload.message = text.trim();
        }
      } catch {
        // Keep the status-based fallback when an error body cannot be parsed.
      }

      const requestId = response.headers.get('x-request-id') ?? payload.request_id ?? undefined;
      const error = new ApiError(response.status, { ...payload, request_id: requestId });

      if (response.status === 401 && unauthorizedHandler) {
        await unauthorizedHandler();
      }

      throw error;
    }

    const contentLengthHeader = response.headers.get('content-length');
    const contentLengthValue = contentLengthHeader === null ? Number.NaN : Number(contentLengthHeader);
    const bytes = new Uint8Array(await response.arrayBuffer());

    return {
      bytes,
      contentType: response.headers.get('content-type'),
      contentLength: Number.isFinite(contentLengthValue) && contentLengthValue >= 0
        ? contentLengthValue
        : null,
    };
  } catch (error) {
    if (error instanceof ApiError) throw error;
    if (error instanceof Error && error.name === 'AbortError') {
      throw new ApiError(0, { message: 'Preuzimanje je isteklo. Pokušaj ponovo.', code: 'request_timeout' });
    }
    throw new ApiError(0, {
      message: error instanceof Error ? error.message : 'Mrežna greška pri preuzimanju priloga.',
      code: 'network_error',
    });
  } finally {
    clearTimeout(timeout);
  }
}
export function queryString(values: Record<string, string | number | boolean | null | undefined>): string {
  const params = new URLSearchParams();
  for (const [key, value] of Object.entries(values)) {
    if (value === null || value === undefined || value === '') continue;
    params.set(key, String(value));
  }
  const value = params.toString();
  return value ? `?${value}` : '';
}
