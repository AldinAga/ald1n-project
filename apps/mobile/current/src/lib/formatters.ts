export function formatMoney(amount: number | null | undefined, currency = 'RSD'): string {
  if (amount === null || amount === undefined || Number.isNaN(amount)) return '—';
  return new Intl.NumberFormat('sr-Latn-RS', {
    style: 'currency',
    currency,
    maximumFractionDigits: currency === 'RSD' ? 0 : 2
  }).format(amount);
}

export function formatDate(value: string | null | undefined, withTime = false): string {
  if (!value) return '—';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '—';
  return new Intl.DateTimeFormat('sr-Latn-RS', {
    dateStyle: 'medium',
    ...(withTime ? { timeStyle: 'short' as const } : {})
  }).format(date);
}

export function initials(name: string | null | undefined): string {
  const parts = String(name ?? '').trim().split(/\s+/).filter(Boolean);
  return (parts[0]?.[0] ?? 'A') + (parts[1]?.[0] ?? '');
}

export function humanize(value: string | null | undefined): string {
  if (!value) return '—';
  return value.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
}

export function compareVersions(left: string, right: string): number {
  const a = left.split('.').map((value) => Number.parseInt(value, 10) || 0);
  const b = right.split('.').map((value) => Number.parseInt(value, 10) || 0);
  const length = Math.max(a.length, b.length);
  for (let index = 0; index < length; index += 1) {
    const difference = (a[index] ?? 0) - (b[index] ?? 0);
    if (difference !== 0) return difference > 0 ? 1 : -1;
  }
  return 0;
}
