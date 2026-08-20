export const ADMIN_PERMISSION_SLUGS = [
  'catalog.manage_products',
  'catalog.manage_images',
  'catalog.manage_taxonomy',
  'catalog.audit',
  'catalog.sync_legacy',
  'orders.manage',
  'orders.reassign',
  'orders.internal_notes',
  'orders.confirm_delivery',
  'orders.reopen',
  'commissions.manage',
  'stock.view',
  'stock.adjust',
  'system.manage_users',
  'system.manage_settings',
  'reports.view',
  'reports.export',
  'reports.manage',
  'invoices.manage',
  'payments.manage',
  'inventory.receive',
  'inventory.count',
  'inventory.export',
  'automation.manage',
  'system.health',
  'backups.manage',
  'audit.export',
  'security.view',
  'after_sales.manage',
  'after_sales.execute',
  'field_operations.view',
  'field_operations.manage',
  'service_parts.view',
  'service_parts.manage',
  'service_parts.procurement',
  'warranties.manage',
  'receivables.manage',
] as const;

export const ADMIN_ROLE_SLUGS = ['admin', 'superadmin'] as const;

export type AdminPermission = (typeof ADMIN_PERMISSION_SLUGS)[number];
export type AdminRole = (typeof ADMIN_ROLE_SLUGS)[number];

export function hasAdminRole(roleSlug?: string | null): roleSlug is AdminRole {
  return ADMIN_ROLE_SLUGS.some((role) => role === roleSlug);
}

export function hasAnyAdminPermission(permissions: readonly string[]): boolean {
  const granted = new Set(permissions);
  return ADMIN_PERMISSION_SLUGS.some((permission) => granted.has(permission));
}

export function hasAdminAccess(input: {
  permissions: readonly string[];
  roleSlug?: string | null | undefined;
}): boolean {
  return hasAdminRole(input.roleSlug) || hasAnyAdminPermission(input.permissions);
}
