// MOBILE_BATCH664_ROLE_AWARE_FLOATING_BOTTOM_NAV
// Pure navigation policy: keep role routing testable without rendering React Native UI.
export type BottomNavKey = 'catalog' | 'orders' | 'home' | 'secondary' | 'account';
export type BottomNavRoute =
  | '/catalog'
  | '/orders'
  | '/admin/orders'
  | '/home'
  | '/admin'
  | '/notifications'
  | '/account';

export type BottomNavItem = {
  key: BottomNavKey;
  label: string;
  route: BottomNavRoute;
  glyph: 'catalog' | 'orders' | 'home' | 'admin' | 'bell' | 'account';
  enabled: boolean;
  badge?: number;
  center?: boolean;
};

export type BottomNavContext = {
  roleSlug?: string | null;
  catalogEnabled: boolean;
  ordersEnabled: boolean;
  notificationsEnabled: boolean;
  canManageOrders: boolean;
  unread: number;
};

export function hasBottomNavAdminRole(roleSlug?: string | null): boolean {
  return roleSlug === 'admin' || roleSlug === 'superadmin';
}

export function buildBottomNavItems(input: BottomNavContext): BottomNavItem[] {
  const admin = hasBottomNavAdminRole(input.roleSlug);
  const unread = Number.isInteger(input.unread) && input.unread > 0 ? input.unread : 0;

  return [
    { key: 'catalog', label: 'Katalog', route: '/catalog', glyph: 'catalog', enabled: input.catalogEnabled },
    {
      key: 'orders',
      label: 'Porudžbine',
      route: admin ? '/admin/orders' : '/orders',
      glyph: 'orders',
      enabled: admin ? input.canManageOrders : input.ordersEnabled,
    },
    { key: 'home', label: 'Početna', route: '/home', glyph: 'home', enabled: true, center: true },
    admin
      ? { key: 'secondary', label: 'Administracija', route: '/admin', glyph: 'admin', enabled: true }
      : {
          key: 'secondary',
          label: 'Notifikacije',
          route: '/notifications',
          glyph: 'bell',
          enabled: input.notificationsEnabled,
          badge: unread || undefined,
        },
    { key: 'account', label: 'Nalog', route: '/account', glyph: 'account', enabled: true },
  ];
}

function matches(pathname: string, prefix: string): boolean {
  return pathname === prefix || pathname.startsWith(prefix + '/');
}

export function activeBottomNavKey(pathname: string, roleSlug?: string | null): BottomNavKey | null {
  if (pathname === '/' || matches(pathname, '/home')) return 'home';
  if (
    matches(pathname, '/catalog')
    || matches(pathname, '/product')
    || matches(pathname, '/cart')
    || matches(pathname, '/checkout')
    || matches(pathname, '/admin/catalog')
  ) return 'catalog';
  if (
    matches(pathname, '/orders')
    || matches(pathname, '/order')
    || matches(pathname, '/assigned-orders')
    || matches(pathname, '/admin/orders')
  ) return 'orders';
  if (
    matches(pathname, '/account')
    || matches(pathname, '/devices')
    || matches(pathname, '/notification-settings')
    || matches(pathname, '/sessions')
  ) return 'account';
  if (matches(pathname, '/notifications')) return hasBottomNavAdminRole(roleSlug) ? null : 'secondary';
  if (matches(pathname, '/admin')) return hasBottomNavAdminRole(roleSlug) ? 'secondary' : null;
  if (
    matches(pathname, '/after-sales')
    || matches(pathname, '/warranties')
    || matches(pathname, '/commissions')
    || matches(pathname, '/portal/messages')
  ) return 'home';
  return null;
}
