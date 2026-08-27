// MOBILE_V1_0_CATALOG_EDIT_HANDOFF_PERFORMANCE_BATCH45
let pendingProductId: number | null = null;

export function scheduleCatalogProductEditHandoff(productId: number): void {
  if (!Number.isInteger(productId) || productId <= 0) return;
  pendingProductId = productId;
}

export function peekCatalogProductEditHandoff(): number | null {
  return pendingProductId;
}

export function clearCatalogProductEditHandoff(productId: number): void {
  if (pendingProductId === productId) pendingProductId = null;
}
