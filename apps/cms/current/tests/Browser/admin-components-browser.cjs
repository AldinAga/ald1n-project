'use strict';
// Admin component gallery QA (CI and local). Renders
// tests/Browser/fixtures/admin-components.html (file://, no server, no
// database) in Chromium and WebKit at 375/430/768/1280 in light and dark, in
// four views (component sheet, dashboard, orders list, order detail), and
// fails on: any axe violation (colour-contrast and the structural rules),
// horizontal scroll, an interactive element without an accessible name, a
// control without a visible keyboard focus indicator, a tap target under
// 44px, console errors. Extra states: the mobile menu sheet, an open desktop
// dropdown, a modal dialog. Chromium also renders the forced-colors,
// reduced-transparency and more-contrast emulations and requires solid
// surfaces without backdrop blur. Screenshots go to the artifact directory.
// Author: Ald1n <https://ald1n.com>
const fs = require('node:fs');
const path = require('node:path');
const http = require('node:http');

const root = process.env.PLAYWRIGHT_NODE_ROOT;
const dir = process.env.ADMIN_COMPONENTS_ARTIFACT_DIR;
if (!root || !dir) throw new Error('ADMIN_COMPONENTS_HOLD_ENV: set PLAYWRIGHT_NODE_ROOT and ADMIN_COMPONENTS_ARTIFACT_DIR');
const {chromium, webkit} = require(path.join(root, 'node_modules', 'playwright'));
const axeSource = fs.readFileSync(path.join(root, 'node_modules', 'axe-core', 'axe.min.js'), 'utf8');
fs.mkdirSync(dir, {recursive: true});

// The fixture is served by a throwaway static server on loopback (axe reads
// stylesheets through XHR, which file:// forbids). The CMS has no route to it.
const siteRoot = path.resolve(__dirname, '..', '..');
const MIME = {'.html': 'text/html; charset=utf-8', '.css': 'text/css; charset=utf-8'};
let origin = '';
const server = http.createServer((req, res) => {
  let rel;
  try { rel = decodeURIComponent(new URL(req.url, 'http://x').pathname); } catch (e) { res.writeHead(400); res.end('bad request'); return; }
  const file = path.resolve(siteRoot, '.' + rel);
  const allowed = file === path.join(siteRoot, 'tests', 'Browser', 'fixtures', 'admin-components.html')
    || (file.startsWith(path.join(siteRoot, 'public', 'assets', 'css') + path.sep) && file.endsWith('.css'));
  if (!allowed || !fs.existsSync(file)) { res.writeHead(404); res.end('not found'); return; }
  res.writeHead(200, {'content-type': MIME[path.extname(file)]});
  res.end(fs.readFileSync(file));
});
const url = hash => origin + '/tests/Browser/fixtures/admin-components.html#' + hash;

const WIDTHS = (process.env.ADMIN_COMPONENTS_WIDTHS || '360,375,430,768,1280').split(',').map(Number);
const ENGINES = (process.env.ADMIN_COMPONENTS_ENGINES || 'chromium,webkit').split(',');
const THEMES = ['light', 'dark'];
const VIEWS = [
  {key: 'sheet', hash: 'v-sheet'},
  {key: 'dashboard', hash: 'v-dashboard'},
  {key: 'orders', hash: 'v-orders'},
  {key: 'order', hash: 'v-order'},
];

const failures = [];
const notes = [];
const check = (ok, message) => { if (!ok) failures.push(message); };
const shot = name => path.join(dir, name + '.png');
const pause = ms => new Promise(resolve => setTimeout(resolve, ms));

async function setTheme(page, theme) {
  await page.evaluate(t => document.querySelector('.a1-theme__opt[data-theme="' + t + '"]').click(), theme);
  await settle(page);
}

// Colour transitions are 180-220ms; measure only after they end.
const settle = page => page.evaluate(() => new Promise(r => setTimeout(r, 420)));

async function show(page, view, theme) {
  await page.goto('about:blank');
  await page.goto(url(view.hash), {waitUntil: 'load'});
  await page.evaluate(h => { location.hash = h; window.scrollTo(0, 0); }, view.hash);
  await setTheme(page, theme);
  await page.evaluate(() => window.scrollTo(0, 0));
}

// ---- checks run inside the page ----
const INTERACTIVE = 'a[href], button, input:not([type=hidden]), select, textarea, summary, [tabindex]:not([tabindex="-1"])';

function inPageAudit(selector) {
  const rendered = el => {
    const cs = getComputedStyle(el);
    return el.getClientRects().length > 0 && cs.visibility !== 'hidden' && cs.display !== 'none';
  };
  const nameOf = el => {
    const by = el.getAttribute('aria-labelledby');
    if (by) return by.split(/\s+/).map(id => (document.getElementById(id) || {}).textContent || '').join(' ').trim();
    const al = el.getAttribute('aria-label');
    if (al && al.trim()) return al.trim();
    if (el.labels && el.labels.length) return [...el.labels].map(l => l.textContent).join(' ').trim();
    if (/^(A|BUTTON|SUMMARY)$/.test(el.tagName)) return el.textContent.trim();
    return (el.getAttribute('title') || '').trim();
  };
  const out = {nameless: [], small: [], count: 0};
  for (const el of document.querySelectorAll(selector)) {
    if (!rendered(el)) continue;
    // Skip elements inside a closed <details>.
    const closed = el.closest('details:not([open])');
    if (closed && !(el.tagName === 'SUMMARY' && el.parentElement === closed)) continue;
    out.count++;
    const label = el.tagName + '.' + String(el.className).split(' ').join('.');
    if (!nameOf(el)) out.nameless.push(label);
    if (el.closest('.a1-link--inline') || el.classList.contains('a1-link--inline') || el.classList.contains('a1-skip')) continue;
    // A hidden-overlay input is as large as its label; the 1px menu checkbox is measured through its label.
    let box = el.getBoundingClientRect();
    if (box.width < 44 && el.labels && el.labels[0]) box = el.labels[0].getBoundingClientRect();
    if (box.width < 43.5 || box.height < 43.5) out.small.push(label + ' ' + Math.round(box.width) + 'x' + Math.round(box.height));
  }
  return out;
}

function inPageFocus() {
  const el = document.activeElement;
  if (!el || el === document.body) return null;
  const cs = getComputedStyle(el);
  const hidden = parseFloat(cs.opacity) === 0;
  const targets = hidden ? [el.nextElementSibling, el.closest('label')].filter(Boolean) : [el];
  const ringOf = t => {
    const s = getComputedStyle(t);
    const w = parseFloat(s.outlineWidth);
    const alpha = /rgba\(.*,\s*0\)$/.test(s.outlineColor) ? 0 : 1;
    const outline = s.outlineStyle !== 'none' && w >= 2 && alpha > 0;
    const shadow = s.boxShadow !== 'none' && /\d+px\s+\d+px\s+\d+px\s+[1-9]/.test(s.boxShadow);
    return outline || shadow;
  };
  const type = el.tagName === 'INPUT' ? (el.getAttribute('role') === 'switch' ? 'switch' : el.type) : el.tagName.toLowerCase();
  const rect = el.getBoundingClientRect();
  return {
    type, ok: targets.some(ringOf),
    key: Array.prototype.indexOf.call(document.getElementsByTagName('*'), el),
    label: el.tagName + '.' + String(el.className).split(' ').join('.') + (el.id ? '#' + el.id : ''),
    visibleBox: rect.width > 0 && rect.height > 0,
  };
}

// WCAG 2.2 SC 2.4.11: after keyboard focus (and the scroll it causes ends) the
// focused control must not sit under a sticky header, action bar or table head.
// Tab alone usually lands mid-screen, so focusPass also runs a keyboard probe
// (Shift+Tab, scroll the control under a bar, Tab back onto it) that fails
// without scroll-padding.
async function inPageObscured() {
  const el = document.activeElement;
  if (!el || el === document.body) return null;
  const settleScroll = async () => {
    let last = NaN, still = 0;
    await new Promise(r => setTimeout(r, 120)); // smooth scrolling can start a few frames late
    while (still < 4) {
      await new Promise(r => requestAnimationFrame(r));
      still = window.scrollY === last ? still + 1 : 0;
      last = window.scrollY;
    }
  };
  const name = e => e.tagName + '.' + String(e.className).split(' ').join('.');
  const target = parseFloat(getComputedStyle(el).opacity) === 0 ? (el.closest('label') || el) : el;
  const wrap = el.closest('.a1-tablewrap--sticky');
  const bars = () => [...document.querySelectorAll('.a1-header, .a1-actionbar')].filter(e => ['sticky', 'fixed'].includes(getComputedStyle(e).position) && !e.contains(el));
  const covers = () => {
    const c = bars();
    if (wrap && !el.closest('thead') && wrap.querySelector('thead').getBoundingClientRect().width > 2) c.push(...wrap.querySelectorAll('thead th'));
    return c;
  };
  const hit = (cs) => {
    const b = target.getBoundingClientRect();
    const free = innerHeight - cs.reduce((s, c) => s + c.getBoundingClientRect().height, 0);
    if (b.height > free) return null; // taller than the free viewport (scrollable table wrapper): cannot be fully clear
    for (const c of cs) {
      const r = c.getBoundingClientRect();
      if (Math.min(b.bottom, r.bottom) - Math.max(b.top, r.top) > 1 && Math.min(b.right, r.right) - Math.max(b.left, r.left) > 1) {
        return {el: name(el), by: name(c), box: [b.top, b.bottom].map(Math.round), cover: [r.top, r.bottom].map(Math.round)};
      }
    }
    return null;
  };
  await settleScroll();
  return hit(covers());
}

// Probe set-up: the control saved in window.__a1E is scrolled (instantly) so it
// sits under the header (edge "top") or under the action bar ("bottom").
// Returns false when the page is too short or the control is in the bar itself.
function inPagePlace(edge) {
  const el = window.__a1E;
  const bar = [...document.querySelectorAll(edge === 'top' ? '.a1-header' : '.a1-actionbar')].find(e => e.getClientRects().length && getComputedStyle(e).position === 'sticky');
  if (!el || !bar || bar.contains(el) || el.closest('.a1-tablewrap--sticky')) return false;
  const r = bar.getBoundingClientRect(), b = (parseFloat(getComputedStyle(el).opacity) === 0 ? (el.closest('label') || el) : el).getBoundingClientRect();
  window.scrollBy({top: edge === 'top' ? b.top - (r.bottom - 8) : b.bottom - (r.top + 8), behavior: 'instant'});
  const a = (parseFloat(getComputedStyle(el).opacity) === 0 ? (el.closest('label') || el) : el).getBoundingClientRect();
  const nb = bar.getBoundingClientRect();
  return Math.min(a.bottom, nb.bottom) - Math.max(a.top, nb.top) > 1;
}

// Real contrast gate. axe reports colour-contrast as "incomplete", not as a
// violation, wherever the background is a gradient or a backdrop (most text on
// glass), so axe alone cannot fail those. This flattens every visible text
// element's background chain (background-color alpha composited outward to the
// first opaque layer or white), and for gradient layers takes the WORST case
// over each colour stop of that gradient (at the stop's own alpha).
function inPageContrast() {
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
  const els = [...document.body.querySelectorAll('*')].filter(e => !/^(SCRIPT|STYLE|SVG|PATH|TEMPLATE|NOSCRIPT|OPTION|OPTGROUP|PROGRESS|METER)$/i.test(e.tagName) && !e.closest('svg'));
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
}

async function contrastGate(page, label) {
  const r = await page.evaluate(inPageContrast);
  check(r.checked > 20, label + ' contrast gate looked at too few text nodes: ' + r.checked);
  check(r.out.length === 0, label + ' contrast under threshold (' + r.out.length + ') ' + JSON.stringify(r.out.slice(0, 6)));
}

async function focusPass(page, label, seenTypes) {
  await page.evaluate(() => { if (document.activeElement) document.activeElement.blur(); window.scrollTo(0, 0); });
  const total = await page.evaluate(sel => [...document.querySelectorAll(sel)].filter(e => e.getClientRects().length).length, INTERACTIVE);
  let first = null;
  for (let i = 0; i < total + 3; i++) {
    await page.keyboard.press('Tab');
    const f = await page.evaluate(inPageFocus);
    if (!f) break;
    if (first === null) first = f.key; else if (f.key === first) break;
    seenTypes.add(f.type);
    check(f.ok, label + ' no visible focus indicator on ' + f.label + ' (' + f.type + ')');
    // An open mobile menu sheet covers the page and has no focus trap (known, tracked separately); skip it there.
    const menuOpen = label.endsWith('menu-open');
    const hidden = menuOpen ? null : await page.evaluate(inPageObscured);
    check(!hidden, label + ' focused control hidden under sticky bar ' + JSON.stringify(hidden));
    if (menuOpen || i % 3 !== 0) continue;
    for (const edge of ['top', 'bottom']) {
      await page.evaluate(() => { window.__a1E = document.activeElement; });
      await page.keyboard.press('Shift+Tab');
      const placed = await page.evaluate(inPagePlace, edge);
      await page.keyboard.press('Tab');
      if (!placed) continue;
      const again = await page.evaluate(inPageObscured);
      check(!again, label + ' keyboard probe (' + edge + ') control hidden under sticky bar ' + JSON.stringify(again));
    }
  }
  return total;
}

async function axe(page, label) {
  await page.evaluate(src => { if (!window.axe) (0, eval)(src); }, axeSource);
  const result = await page.evaluate(async () => {
    const r = await window.axe.run(document, {
      runOnly: {type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa', 'best-practice']},
      resultTypes: ['violations'],
    });
    return r.violations.map(v => ({id: v.id, impact: v.impact, nodes: v.nodes.slice(0, 4).map(n => n.target.join(' ') + ' :: ' + (n.any[0] || n.all[0] || n.none[0] || {}).message)}));
  });
  for (const v of result) check(false, label + ' axe ' + v.id + ' (' + v.impact + ') ' + JSON.stringify(v.nodes));
}

async function basics(page, label, seenTypes) {
  const dims = await page.evaluate(() => ({w: window.innerWidth, sw: document.documentElement.scrollWidth}));
  check(dims.sw <= dims.w + 1, label + ' horizontal scroll ' + JSON.stringify(dims));
  const a = await page.evaluate(inPageAudit, INTERACTIVE);
  check(a.count > 0, label + ' no interactive elements found');
  check(a.nameless.length === 0, label + ' interactive without accessible name ' + JSON.stringify(a.nameless.slice(0, 6)));
  check(a.small.length === 0, label + ' tap target under 44px ' + JSON.stringify(a.small.slice(0, 8)));
  await axe(page, label);
  await contrastGate(page, label);
  await focusPass(page, label, seenTypes);
}

const SURFACES = ['.a1-header', '.a1-card', '.a1-kpi', '.a1-tablewrap', '.a1-filters', '.a1-empty', '.a1-alert', '.a1-actionbar', '.a1-dialog[open]', '.a1-page'];
async function solidSurfaces(page, label, expectGlowOff) {
  const bad = await page.evaluate(sels => {
    const out = [];
    for (const s of sels) for (const el of document.querySelectorAll(s)) {
      if (!el.getClientRects().length) continue;
      const cs = getComputedStyle(el);
      const m = /rgba?\(([^)]+)\)/.exec(cs.backgroundColor);
      const parts = m ? m[1].split(',').map(x => parseFloat(x)) : [0, 0, 0, 0];
      const alpha = parts.length > 3 ? parts[3] : 1;
      const blur = cs.backdropFilter !== 'none' && cs.backdropFilter !== undefined && cs.backdropFilter !== '';
      const wk = cs.webkitBackdropFilter && cs.webkitBackdropFilter !== 'none';
      if (alpha < 1 || blur || wk) out.push(s + ' alpha=' + alpha + ' blur=' + cs.backdropFilter);
    }
    return out.slice(0, 8);
  }, SURFACES);
  check(bad.length === 0, label + ' surfaces not solid ' + JSON.stringify(bad));
  if (expectGlowOff) {
    const glow = await page.evaluate(() => getComputedStyle(document.documentElement).getPropertyValue('--a1-glow-1').trim());
    check(glow === 'transparent', label + ' glow still on: ' + glow);
  }
}

async function emulate(page, ctx, engine, name, value) {
  // Playwright covers forced-colors, colour scheme, reduced motion; the other
  // features go through the DevTools protocol (Chromium only).
  if (name === 'forced-colors') await page.emulateMedia({forcedColors: value});
  else if (engine === 'chromium') {
    const cdp = await ctx.newCDPSession(page);
    await cdp.send('Emulation.setEmulatedMedia', {features: [{name, value}]});
  } else return false;
  return page.evaluate(([n, v]) => matchMedia('(' + n + ': ' + v + ')').matches, [name, value]);
}

async function run() {
  await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
  origin = 'http://127.0.0.1:' + server.address().port;
  const seenTypes = new Set();
  for (const engineName of ENGINES) {
    const engine = engineName === 'webkit' ? webkit : chromium;
    const browser = await engine.launch();
    try {
      for (const width of WIDTHS) {
        const ctx = await browser.newContext({viewport: {width, height: 900}, deviceScaleFactor: 1});
        const page = await ctx.newPage();
        const problems = [];
        page.on('console', msg => { if (msg.type() === 'error') problems.push('console: ' + msg.text().slice(0, 200)); });
        page.on('requestfailed', req => problems.push('request failed: ' + req.url()));
        page.on('pageerror', err => problems.push('pageerror: ' + String(err).slice(0, 200)));
        for (const theme of THEMES) {
          for (const view of VIEWS) {
            const label = `${engineName} ${width} ${theme} ${view.key}`;
            await show(page, view, theme);
            const visible = await page.evaluate(h => getComputedStyle(document.getElementById(h)).display !== 'none', view.hash);
            check(visible, label + ' view not shown');
            const res = await page.evaluate(() => getComputedStyle(document.body).backgroundColor + '|' + document.documentElement.dataset.theme);
            check(res.endsWith('|' + theme), label + ' theme not applied ' + res);
            await basics(page, label, seenTypes);
            await page.evaluate(() => window.scrollTo(0, 0));
            await page.screenshot({path: shot(`${engineName}-${view.key}-${width}-${theme}`), fullPage: true});
            check(problems.length === 0, label + ' page problems ' + JSON.stringify(problems.splice(0, 5)));
          }

          // Interactive states on the sheet view.
          const sheet = VIEWS[0];
          const base = `${engineName} ${width} ${theme}`;
          await show(page, sheet, theme);
          if (width <= 768) {
            await page.click('.a1-menu-btn');
            const open = await page.evaluate(() => {
              const p = document.querySelector('#v-sheet .a1-header__panel');
              const r = p.getBoundingClientRect();
              return {shown: getComputedStyle(p).display !== 'none', inside: r.left >= -0.5 && r.right <= innerWidth + 0.5, sw: document.documentElement.scrollWidth <= innerWidth + 1};
            });
            check(open.shown && open.inside && open.sw, base + ' mobile menu sheet ' + JSON.stringify(open));
            await page.click('#v-sheet .a1-nav-dd summary');
            await basics(page, base + ' menu-open', seenTypes);
            await page.screenshot({path: shot(`${engineName}-menu-${width}-${theme}`)});
          } else {
            await page.click('#v-sheet .a1-nav-dd summary');
            const dd = await page.evaluate(() => {
              const p = document.querySelector('#v-sheet .a1-nav-dd[open] .a1-nav-dd__panel');
              const r = p && p.getBoundingClientRect();
              return {open: !!p && r.height > 0, inside: !!r && r.left >= 0 && r.right <= innerWidth};
            });
            check(dd.open && dd.inside, base + ' dropdown ' + JSON.stringify(dd));
            await basics(page, base + ' dropdown-open', seenTypes);
            await page.screenshot({path: shot(`${engineName}-dropdown-${width}-${theme}`)});
            await page.keyboard.press('Escape');
          }
          // Modal dialog (native): focus moves inside, Escape closes.
          await show(page, sheet, theme);
          await page.evaluate(() => document.getElementById('dlg-sheet').showModal());
          const modal = await page.evaluate(() => {
            const d = document.getElementById('dlg-sheet');
            const r = d.getBoundingClientRect();
            return {open: d.open, modal: d.matches(':modal'), focusInside: d.contains(document.activeElement), inside: r.left >= 0 && r.right <= innerWidth && r.top >= 0 && r.bottom <= innerHeight};
          });
          check(modal.open && modal.modal && modal.focusInside && modal.inside, base + ' modal dialog ' + JSON.stringify(modal));
          await axe(page, base + ' modal-open');
          await page.screenshot({path: shot(`${engineName}-dialog-${width}-${theme}`)});
          await page.keyboard.press('Escape');
          await page.waitForFunction(() => !document.getElementById('dlg-sheet').open, null, {timeout: 2000}).catch(() => {});
          check(await page.evaluate(() => !document.getElementById('dlg-sheet').open), base + ' Escape did not close dialog');
        }

        // Emulations (Chromium; WebKit only where the engine honours the feature).
        const matrix = [
          ['forced-colors', 'active', 'forced', true],
          ['prefers-reduced-transparency', 'reduce', 'reduced-transparency', true],
          ['prefers-contrast', 'more', 'more-contrast', true],
        ];
        for (const [feature, value, key, solid] of matrix) {
          if (width !== 375 && width !== 1280) continue;
          for (const theme of THEMES) {
            for (const view of [VIEWS[0], VIEWS[1]]) {
              const label = `${engineName} ${width} ${theme} ${view.key} ${key}`;
              await show(page, view, theme);
              const honoured = await emulate(page, ctx, engineName, feature, value);
              if (!honoured) { notes.push(`${label}: engine does not honour ${feature}, skipped`); await page.emulateMedia({forcedColors: 'none'}); continue; }
              await settle(page);
              await solidSurfaces(page, label, feature !== 'forced-colors');
              const dims = await page.evaluate(() => ({w: innerWidth, sw: document.documentElement.scrollWidth}));
              check(dims.sw <= dims.w + 1, label + ' horizontal scroll ' + JSON.stringify(dims));
              await page.screenshot({path: shot(`${engineName}-${key}-${view.key}-${width}-${theme}`), fullPage: true});
              if (feature === 'forced-colors') await page.emulateMedia({forcedColors: 'none'});
              else { const cdp = await ctx.newCDPSession(page); await cdp.send('Emulation.setEmulatedMedia', {features: [{name: feature, value: ''}]}); }
            }
          }
        }
        await ctx.close();
      }
    } finally {
      await browser.close();
    }
  }
  for (const t of ['a', 'button', 'text', 'textarea', 'select', 'checkbox', 'radio', 'switch', 'file', 'summary', 'date', 'search']) {
    if (ENGINES.length && !process.env.ADMIN_COMPONENTS_WIDTHS) check(seenTypes.has(t), 'focus coverage: control type never focused: ' + t);
  }
  console.log('focus types seen: ' + [...seenTypes].sort().join(', '));
}

run().finally(() => server.close()).then(() => {
  for (const n of notes) console.log('NOTE ' + n);
  if (failures.length) {
    console.error(failures.length + ' admin component failures');
    for (const f of failures.slice(0, 80)) console.error('FAIL ' + f);
    process.exit(1);
  }
  console.log('ADMIN_COMPONENTS_QA_PASS');
}).catch(err => { console.error(err); process.exit(2); });
