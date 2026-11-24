// MOBILE_BATCH664_ROLE_AWARE_FLOATING_BOTTOM_NAV
// Pure navigation policy: keep role routing testable without rendering React Native UI.
// Owner decision 2026-11-16 (Home raised in the middle):
//   SuperAdmin: Katalog, Porudžbine, Početna, Administracija, Nalog
//   Admin:      Katalog, Porudžbine, Početna, Potraživanja, Nalog
//   SubAgent:   Katalog, Korpa, Početna, Porudžbine, Provizije
// Notifications live in the page header bell; Administracija is also on Home.
export type BottomNavKey =
  | 'catalog' | 'orders' | 'home' | 'secondary' | 'account' | 'cart' | 'commissions' | 'receivables';
export type BottomNavRoute =
  | '/catalog'
  | '/cart'
  | '/orders'
  | '/admin/orders'
  | '/home'
  | '/commissions'
  | '/admin/receivables'
  | '/admin'
  | '/notifications'
  | '/account';

export type BottomNavItem = {
  key: BottomNavKey;
  label: string;
  route: BottomNavRoute;
  glyph: 'catalog' | 'cart' | 'orders' | 'home' | 'commission' | 'receivables' | 'admin' | 'bell' | 'account';
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
  cartEnabled: boolean;
  commissionsEnabled: boolean;
  receivablesEnabled: boolean;
  unread: number;
};

export function hasBottomNavAdminRole(roleSlug?: string | null): boolean {
  return roleSlug === 'admin' || roleSlug === 'superadmin';
}

// The SubAgent role has slug 'user' (CoreAccessSeeder).
export function hasBottomNavSubAgentRole(roleSlug?: string | null): boolean {
  return roleSlug === 'user';
}

function catalogItem(input: BottomNavContext): BottomNavItem {
  return { key: 'catalog', label: 'Katalog', route: '/catalog', glyph: 'catalog', enabled: input.catalogEnabled };
}
const HOME_ITEM: BottomNavItem = { key: 'home', label: 'Početna', route: '/home', glyph: 'home', enabled: true, center: true };
const ACCOUNT_ITEM: BottomNavItem = { key: 'account', label: 'Nalog', route: '/account', glyph: 'account', enabled: true };

export function buildBottomNavItems(input: BottomNavContext): BottomNavItem[] {
  const unread = Number.isInteger(input.unread) && input.unread > 0 ? input.unread : 0;
  const adminOrders: BottomNavItem = {
    key: 'orders', label: 'Porudžbine', route: '/admin/orders', glyph: 'orders', enabled: input.canManageOrders,
  };

  if (input.roleSlug === 'superadmin') {
    return [
      catalogItem(input),
      adminOrders,
      HOME_ITEM,
      { key: 'secondary', label: 'Administracija', route: '/admin', glyph: 'admin', enabled: true },
      ACCOUNT_ITEM,
    ];
  }
  if (input.roleSlug === 'admin') {
    return [
      catalogItem(input),
      adminOrders,
      HOME_ITEM,
      { key: 'receivables', label: 'Potraživanja', route: '/admin/receivables', glyph: 'receivables', enabled: input.receivablesEnabled },
      ACCOUNT_ITEM,
    ];
  }
  if (hasBottomNavSubAgentRole(input.roleSlug)) {
    return [
      catalogItem(input),
      { key: 'cart', label: 'Korpa', route: '/cart', glyph: 'cart', enabled: input.cartEnabled },
      HOME_ITEM,
      { key: 'orders', label: 'Porudžbine', route: '/orders', glyph: 'orders', enabled: input.ordersEnabled },
      { key: 'commissions', label: 'Provizije', route: '/commissions', glyph: 'commission', enabled: input.commissionsEnabled },
    ];
  }
  // Roles without an owner decision keep the previous layout: personal orders and notification inbox.
  return [
    catalogItem(input),
    { key: 'orders', label: 'Porudžbine', route: '/orders', glyph: 'orders', enabled: input.ordersEnabled },
    HOME_ITEM,
    {
      key: 'secondary',
      label: 'Notifikacije',
      route: '/notifications',
      glyph: 'bell',
      enabled: input.notificationsEnabled,
      badge: unread || undefined,
    },
    ACCOUNT_ITEM,
  ];
}

function matches(pathname: string, prefix: string): boolean {
  return pathname === prefix || pathname.startsWith(prefix + '/');
}

export function activeBottomNavKey(pathname: string, roleSlug?: string | null): BottomNavKey | null {
  const subAgent = hasBottomNavSubAgentRole(roleSlug);
  if (pathname === '/' || matches(pathname, '/home')) return 'home';
  if (subAgent && (matches(pathname, '/cart') || matches(pathname, '/checkout'))) return 'cart';
  if (subAgent && matches(pathname, '/commissions')) return 'commissions';
  if (roleSlug === 'admin' && matches(pathname, '/admin/receivables')) return 'receivables';
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
  // The header bell opens notifications; only the fallback layout has a tab for it.
  if (matches(pathname, '/notifications')) return hasBottomNavAdminRole(roleSlug) || subAgent ? null : 'secondary';
  if (matches(pathname, '/admin')) return roleSlug === 'superadmin' ? 'secondary' : null;
  if (
    matches(pathname, '/after-sales')
    || matches(pathname, '/warranties')
    || matches(pathname, '/commissions')
    || matches(pathname, '/portal/messages')
  ) return 'home';
  return null;
}
