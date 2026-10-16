import type { PaginatedResponse, Product } from '@/types/api';

/**
 * The API limits each response, not the number of products in the catalogue.
 * Stop strictly at Laravel's pagination metadata so short/empty results do not
 * cause an onEndReached request loop.
 */
export function nextCatalogPage(lastPage: PaginatedResponse<Product>): number | undefined {
  const meta = lastPage.meta;
  if (!meta || !lastPage.data.length) return undefined;
  if (!Number.isSafeInteger(meta.current_page) || !Number.isSafeInteger(meta.last_page)) return undefined;
  if (meta.current_page < 1 || meta.current_page >= meta.last_page) return undefined;
  return meta.current_page + 1;
}

/**
 * Preserve the API order and avoid duplicate React keys if adjacent pages
 * overlap after an item is added/updated while the user is scrolling.
 */
export function catalogProductsFromPages(pages: PaginatedResponse<Product>[]): Product[] {
  const seen = new Set<number>();
  const products: Product[] = [];
  for (const page of pages) {
    for (const product of page.data) {
      if (seen.has(product.id)) continue;
      seen.add(product.id);
      products.push(product);
    }
  }
  return products;
}
