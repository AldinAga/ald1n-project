'use strict';

/*
 * In-page computed contrast, copied from admin-components-browser.cjs (PR 178) so the shell cells of the
 * admin visual QA use the same method. axe reports text on glass or gradients as "incomplete", never as a
 * violation; this flattens the background chain (worst case over gradient stops) and compares to 4.5:1 (3:1 large).
 * Added: optional `scope` selector, only elements inside it are measured. Author: Ald1n <https://ald1n.com>
 * Pass to page.evaluate(inPageContrast, scope).
 */
module.exports = function inPageContrast(scope) {
  const ctx = document.createElement('canvas').getContext('2d', {willReadFrequently: true});
  const rgba = css => { ctx.clearRect(0, 0, 1, 1); ctx.fillStyle = '#000'; ctx.fillStyle = css; ctx.fillRect(0, 0, 1, 1); const d = ctx.getImageData(0, 0, 1, 1).data; return [d[0], d[1], d[2], d[3] / 255]; };
  const over = (f, b) => { const a = f[3]; return [f[0] * a + b[0] * (1 - a), f[1] * a + b[1] * (1 - a), f[2] * a + b[2] * (1 - a), 1]; };
  const lum = c => { const l = [c[0], c[1], c[2]].map(v => { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); }); return 0.2126 * l[0] + 0.7152 * l[1] + 0.0722 * l[2]; };
  const ratio = (a, b) => { const x = lum(a), y = lum(b); return (Math.max(x, y) + 0.05) / (Math.min(x, y) + 0.05); };
  const COLOUR = /(?:rgba?|color|oklab|oklch|hsla?)\([^)]*\)|#[0-9a-f]{3,8}\b/gi;
  const topLevel = s => { const parts = []; let d = 0, at = 0; for (let i = 0; i < s.length; i++) { if (s[i] === '(') d++; else if (s[i] === ')') d--; else if (s[i] === ',' && d === 0) { parts.push(s.slice(at, i).trim()); at = i + 1; } } parts.push(s.slice(at).trim()); return parts.filter(p => p && p !== 'none'); };
  // Colour a gradient lays over the surface behind the text. A vertical linear
  // gradient (button gloss, header sheen) is sampled at the TOP edge of the
  // text box (the strongest point, since these fade downward); every other
  // gradient (the app's radial glows) contributes each colour stop at its own alpha.
  const gradientColours = (g, layerBox, textBox) => {
    const stopsOf = s => (s.match(COLOUR) || []).map(rgba);
    // Chrome serialises the default direction (180deg) by omitting it.
    const dir = /^linear-gradient\(\s*(180deg|to bottom|[\d.]+(?:deg|turn|rad|grad)|to [a-z ]+)\s*,/.exec(g);
    const vertical = /^linear-gradient\(/.test(g) && (!dir || /^(180deg|to bottom)$/.test(dir[1]));
    if (!vertical) return stopsOf(g).filter(c => c[3] > 0);
    const list = topLevel(g.slice(g.indexOf('(') + 1, g.lastIndexOf(')'))).slice(dir ? 1 : 0);
    const st = list.map(p => ({c: rgba((p.match(COLOUR) || [p])[0]), p: (/([\d.]+)%\s*$/.exec(p) || [])[1]}));
    st.forEach((s, i) => { s.p = s.p === undefined ? (i === 0 ? 0 : i === st.length - 1 ? 100 : NaN) : parseFloat(s.p); });
    const t = Math.min(100, Math.max(0, (textBox.top - layerBox.top) / (layerBox.height || 1) * 100));
    let a = st[0], b = st[st.length - 1];
    for (let i = 0; i < st.length - 1; i++) if (t >= st[i].p && t <= st[i + 1].p) { a = st[i]; b = st[i + 1]; break; }
    if (st.some(s => isNaN(s.p))) return stopsOf(g).filter(c => c[3] > 0);
    const k = b.p === a.p ? 0 : Math.min(1, Math.max(0, (t - a.p) / (b.p - a.p)));
    // Fading to transparent keeps the visible end's hue; two visible ends are lerped.
    const hue = a.c[3] === 0 ? b.c : a.c, mix = a.c[3] && b.c[3] ? k : 0;
    const col = [0, 1, 2].map(i => hue[i] + (b.c[i] - hue[i]) * mix);
    return [[col[0], col[1], col[2], a.c[3] + (b.c[3] - a.c[3]) * k]].filter(c => c[3] > 0);
  };
  const out = [];
  let checked = 0;
  const els = [...document.body.querySelectorAll('*')].filter(e => (!scope || e.closest(scope)) && !/^(SCRIPT|STYLE|SVG|PATH|TEMPLATE|NOSCRIPT|OPTION|OPTGROUP|PROGRESS|METER)$/i.test(e.tagName) && !e.closest('svg'));
  for (const el of els) {
    const own = [...el.childNodes].some(n => n.nodeType === 3 && n.textContent.trim());
    const field = /^(INPUT|TEXTAREA|SELECT)$/.test(el.tagName) && !/^(checkbox|radio|file|hidden|range|color)$/.test(el.type || '') && (el.value || '').length;
    if (!own && !field) continue;
    const cs = getComputedStyle(el);
    const box = el.getBoundingClientRect();
    if (!el.getClientRects().length || cs.visibility === 'hidden' || parseFloat(cs.opacity) === 0 || box.width <= 1 || box.height <= 1) continue;
    const closed = el.closest('details:not([open])');
    if (closed && !(el.tagName === 'SUMMARY' && el.parentElement === closed) && !el.closest('summary')) continue;
    if (el.matches(':disabled') || el.closest('[aria-disabled="true"]')) continue;
    // Layers from root to element.
    const range = document.createRange();
    range.selectNodeContents(el);
    const textBox = field ? box : range.getBoundingClientRect();
    const nodes = [];
    for (let n = el; n; n = n.parentElement) nodes.unshift(n);
    const layers = nodes.map(n => getComputedStyle(n));
    let from = 0;
    for (let i = layers.length - 1; i >= 0; i--) if (rgba(layers[i].backgroundColor)[3] >= 1) { from = i; break; }
    let cands = [[255, 255, 255, 1]];
    for (let i = from; i < layers.length; i++) {
      const s = layers[i];
      const next = [];
      // shortcut: a form control's own background-image is its chevron glyph, not a surface behind the text; ignored.
      const imgs = nodes[i] === el && field ? [] : topLevel(s.backgroundImage.replace(/url\([^)]*\)/g, ''));
      const colours = imgs.flatMap(g => gradientColours(g, nodes[i].getBoundingClientRect(), textBox));
      for (const c of cands) {
        const base = over(rgba(s.backgroundColor), c);
        next.push(base);
        for (const st of colours) next.push(over(st, base));
      }
      const seen = new Set();
      cands = next.filter(c => { const k = c.slice(0, 3).map(Math.round).join(); if (seen.has(k)) return false; seen.add(k); return true; });
    }
    const fg = rgba(cs.color);
    if (fg[3] === 0) continue; // intentionally invisible text (busy button label, spinner shown instead)
    const size = parseFloat(cs.fontSize), bold = parseInt(cs.fontWeight, 10) >= 700;
    const min = size >= 24 || (size >= 18.66 && bold) ? 3 : 4.5;
    let worst = Infinity;
    for (const c of cands) worst = Math.min(worst, ratio(over(fg, c), c));
    checked++;
    if (worst < min) out.push(el.tagName + '.' + String(el.className).split(' ').join('.') + ' ' + worst.toFixed(2) + '<' + min + ' "' + (el.textContent || el.value || '').trim().slice(0, 24) + '"');
  }
  return {checked, out};
};
