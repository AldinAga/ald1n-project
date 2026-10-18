/* Liquid Glass R3: progressive enhancement for Laravel CMS.
 * External vendor: kwlck/liquid-glass-web MIT (pinned under assets/js/vendor).
 * Only decorative effects; no DOM replacement or business event handling.
 */
import { createLiquidGlass } from './vendor/liquid-glass-web/liquid-glass.js';
const reduced = window.matchMedia('(prefers-reduced-transparency: reduce)').matches;
const forced = window.matchMedia('(forced-colors: active)').matches;
const selected = [
  ['.header-product-search-dialog', { radius: 22, refraction: 65, blur: 8, light: 22 }],
  ['.nav-dropdown-menu', { radius: 18, refraction: 66, blur: 9, light: 24 }],
  ['.modern-dashboard-hero', { radius: 20, refraction: 42, blur: 7, light: 19 }],
  ['.dashboard-kpi-card', { radius: 18, refraction: 35, blur: 5, light: 20 }],
];
const live = new WeakMap();
function upgrade() {
  if (reduced || forced) return;
  for (const [selector, options] of selected) {
    for (const node of document.querySelectorAll(selector)) {
      if (live.has(node) || node.clientWidth === 0 || node.clientHeight === 0) continue;
      try { live.set(node, createLiquidGlass(node, { ...options, pointer: false, resolution: 0.5 })); }
      catch (error) { console.warn('[Ald1n Liquid Glass] Enhancement skipped', selector, error); }
    }
  }
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', upgrade, { once: true });
else upgrade();
/* Lazy dialogs are upgraded at opening without installing a global mutation observer. */
document.addEventListener('click', () => requestAnimationFrame(upgrade), { passive: true });
