'use strict';

/*
 * In-page measurement of one OPEN header dropdown (runs inside page.evaluate, no closures over Node scope).
 * Author: Ald1n <https://ald1n.com>
 *
 * Returns plain numbers; the harness turns them into findings:
 *   scrollOver   panel.scrollHeight - panel.clientHeight (must be <= 1: no internal vertical scroll)
 *   edges        how far the panel box leaves the viewport on each side (all must be <= 0)
 *   lastHidden   how far the last item sticks out of the panel box or the viewport (must be <= 1)
 *   docOverflow  documentElement.scrollWidth - innerWidth (must be <= 1)
 *   minItem      smallest item height; items = how many were measured (the harness checks a floor)
 */
module.exports = function inPageMenu(detailsSelector) {
  const dd = document.querySelector(detailsSelector);
  const panel = dd && dd.querySelector(':scope > .a1-nav-dd__panel');
  if (!dd || !dd.open || !panel) return { error: 'dropdown not open: ' + detailsSelector };
  const vw = window.innerWidth;
  const vh = window.innerHeight;
  const r = panel.getBoundingClientRect();
  const items = [...panel.querySelectorAll('.a1-nav-dd__item')].filter((n) => n.getClientRects().length > 0);
  const last = items[items.length - 1];
  const lr = last ? last.getBoundingClientRect() : null;
  const pb = parseFloat(getComputedStyle(panel).borderBottomWidth) || 0;
  const hs = items.map((n) => n.getBoundingClientRect().height);
  // Column by column order check: DOM order must equal visual order (a column is a group of x within 4px).
  const cols = [...new Set(items.map((n) => Math.round(n.getBoundingClientRect().left / 4)))].length;
  // Panel fill alpha: the computed colour is normalised through a canvas (handles rgb/oklab/color-mix serialisations).
  const cx = document.createElement('canvas').getContext('2d', { willReadFrequently: true });
  cx.clearRect(0, 0, 1, 1);
  cx.fillStyle = getComputedStyle(panel).backgroundColor;
  cx.fillRect(0, 0, 1, 1);
  const alpha = Math.round((cx.getImageData(0, 0, 1, 1).data[3] / 255) * 100) / 100;
  return {
    alpha,
    label: dd.querySelector(':scope > summary').textContent.trim().slice(0, 40),
    items: items.length,
    columns: cols,
    scrollOver: Math.round(panel.scrollHeight - panel.clientHeight),
    edges: { left: Math.round(-r.left), top: Math.round(-r.top), right: Math.round(r.right - vw), bottom: Math.round(r.bottom - vh) },
    width: Math.round(r.width),
    lastHidden: lr ? Math.round(Math.max(lr.bottom - (r.bottom - pb), lr.bottom - vh, r.top - lr.top, lr.right - vw)) : 0,
    docOverflow: document.documentElement.scrollWidth - vw,
    minItem: hs.length ? Math.round(Math.min(...hs) * 10) / 10 : 0,
    coarse: window.matchMedia('(pointer: coarse)').matches,
  };
};
