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
  const rel = decodeURIComponent(new URL(req.url, 'http://x').pathname);
  const file = path.resolve(siteRoot, '.' + rel);
  const allowed = file === path.join(siteRoot, 'tests', 'Browser', 'fixtures', 'admin-components.html')
    || (file.startsWith(path.join(siteRoot, 'public', 'assets', 'css') + path.sep) && file.endsWith('.css'));
  if (!allowed || !fs.existsSync(file)) { res.writeHead(404); res.end('not found'); return; }
  res.writeHead(200, {'content-type': MIME[path.extname(file)]});
  res.end(fs.readFileSync(file));
});
const url = hash => origin + '/tests/Browser/fixtures/admin-components.html#' + hash;

const WIDTHS = (process.env.ADMIN_COMPONENTS_WIDTHS || '375,430,768,1280').split(',').map(Number);
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
    label: el.tagName + '.' + String(el.className).split(' ').join('.') + (el.id ? '#' + el.id : ''),
    visibleBox: rect.width > 0 && rect.height > 0,
  };
}

async function focusPass(page, label, seenTypes) {
  await page.evaluate(() => { if (document.activeElement) document.activeElement.blur(); window.scrollTo(0, 0); });
  const total = await page.evaluate(sel => [...document.querySelectorAll(sel)].filter(e => e.getClientRects().length).length, INTERACTIVE);
  let first = null;
  for (let i = 0; i < total + 3; i++) {
    await page.keyboard.press('Tab');
    const f = await page.evaluate(inPageFocus);
    if (!f) break;
    if (first === f.label + i && i > 0) break;
    seenTypes.add(f.type);
    check(f.ok, label + ' no visible focus indicator on ' + f.label + ' (' + f.type + ')');
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
