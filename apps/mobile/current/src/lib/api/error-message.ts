import { ApiError } from '@/lib/api/client';

/** Poruka za korisnika: prva greška polja iz API-ja, pa poruka greške, pa zadani tekst. */
export function apiErrorMessage(error: unknown, fallback: string): string {
  if (error instanceof ApiError) return error.firstFieldError() ?? error.message;
  if (error instanceof Error && error.message) return error.message;
  return fallback;
}
