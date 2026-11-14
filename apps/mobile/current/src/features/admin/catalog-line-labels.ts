// The backend stores a laptop charger's connector type in the product line (migration
// 2026_10_20_210000_add_laptop_chargers_catalog) and exposes only the product type name to the app.
const LAPTOP_CHARGER_TYPE_NAME = 'punjaci za laptopove';

function fold(value: string): string {
  return value.normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/gi, 'dj').trim().toLowerCase();
}

export function lineSelectorLabels(productTypeName: string | null | undefined): { label: string; none: string } {
  return productTypeName && fold(productTypeName) === LAPTOP_CHARGER_TYPE_NAME
    ? { label: 'Tip priključka', none: 'Bez tipa priključka' }
    : { label: 'Linija', none: 'Bez linije' };
}
