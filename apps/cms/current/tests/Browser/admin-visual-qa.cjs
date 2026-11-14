'use strict';

/*
 * Admin visual QA harness (Phase 0 of the redesign). Author: Ald1n <https://ald1n.com>
 *
 * Renders representative real admin pages of every module in Chromium or WebKit at
 * 375/430/768/1280, light and dark, against disposable synthetic Laravel data
 * (seed-admin-visual-qa.php). Per cell: full-page PNG, horizontal overflow check,
 * console error / failed same-origin request check, axe-core (colour-contrast plus cheap
 * structural rules). Findings go to admin-visual-baseline-<browser>.json and DO NOT fail
 * the run; only infrastructure problems do (page did not load, login failed, 5xx, wrong
 * final URL, theme not applied, screenshot missing). Strictness is applied afterwards by
 * admin-visual-qa-report.cjs (ADMIN_VISUAL_STRICT / ADMIN_VISUAL_STRICT_MODULES).
 *
 * Env: ADMIN_VISUAL_CI=1, ADMIN_VISUAL_BASE_URL, ADMIN_VISUAL_FIXTURES_PATH,
 *      ADMIN_VISUAL_ARTIFACT_DIR, PLAYWRIGHT_NODE_ROOT, ADMIN_VISUAL_BROWSER=chromium|webkit,
 *      optional ADMIN_VISUAL_ONLY (comma list of page keys) and ADMIN_VISUAL_WIDTHS.
 */
const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');

const baseUrl = process.env.ADMIN_VISUAL_BASE_URL;
const fixturesPath = process.env.ADMIN_VISUAL_FIXTURES_PATH;
const artifactDir = process.env.ADMIN_VISUAL_ARTIFACT_DIR;
const nodeRoot = process.env.PLAYWRIGHT_NODE_ROOT;
const browserName = process.env.ADMIN_VISUAL_BROWSER || 'chromium';
if (
  process.env.ADMIN_VISUAL_CI !== '1'
  || baseUrl !== 'http://127.0.0.1:8125'
  || !fixturesPath || !artifactDir || !nodeRoot
  || !['chromium', 'webkit'].includes(browserName)
) {
  console.error('HOLD: admin visual QA is restricted to loopback disposable CI.');
  process.exit(2);
}
const fixture = JSON.parse(fs.readFileSync(fixturesPath, 'utf8'));
if (fixture.sentinel !== 'ALD1N_ADMIN_VISUAL_CI_SYNTHETIC_ONLY') {
  console.error('HOLD: invalid disposable fixture sentinel.');
  process.exit(3);
}
const playwright = require(path.resolve(nodeRoot, 'node_modules/playwright'));
const axeSource = fs.readFileSync(path.resolve(nodeRoot, 'node_modules/axe-core/axe.min.js'), 'utf8');

const WIDTHS = (process.env.ADMIN_VISUAL_WIDTHS || '375,430,768,1280').split(',').map(Number);
const HEIGHTS = { 375: 812, 430: 932, 768: 1024, 1280: 800 };
const THEMES = ['light', 'dark'];
const AXE_RULES = ['color-contrast', 'document-title', 'html-has-lang', 'label', 'button-name', 'link-name', 'image-alt'];
const ONLY = (process.env.ADMIN_VISUAL_ONLY || '').split(',').filter(Boolean);
const shotDir = path.join(artifactDir, 'screenshots', browserName);
fs.mkdirSync(shotDir, { recursive: true });

// Masked in screenshots (not in axe): values that change every run or depend on "today".
const MASK_SELECTORS = [
  '[data-exchange-rate-value]',      // EUR/RSD rate in header and dashboard, changes with the rate source
  '.rate-badge',                    // header rate badge incl. stale marker
  'canvas',                         // any canvas chart
  '[class*="chart-"]',              // dashboard chart blocks computed from "today"
  '[data-exchange-rate-sync-status]',
];
const ids = fixture.ids;
const P = (p) => (typeof p === 'function' ? p(ids) : p);

const toggle = '[data-mobile-menu-toggle]';
async function openMobileMenuIfNeeded(page) {
  const t = page.locator(toggle);
  if (await t.isVisible()) {
    await t.click();
    await page.waitForFunction((s) => document.querySelector(s)?.getAttribute('aria-expanded') === 'true', toggle, { timeout: 5000 });
    return true;
  }
  return false;
}
const prepare = {
  async navDropdown(page) {
    await openMobileMenuIfNeeded(page);
    const summary = page.locator('details.nav-dropdown > summary').first();
    await summary.click();
    await page.waitForFunction(() => !!document.querySelector('details.nav-dropdown[open]'), null, { timeout: 5000 });
  },
  async searchDialog(page) {
    await page.locator('[data-header-product-search-toggle]').click();
    await page.waitForFunction(() => { const l = document.querySelector('#headerProductSearch'); return l && !l.hidden; }, null, { timeout: 5000 });
    await page.locator('[data-header-product-search-input]').fill('Admin');
    await page.waitForTimeout(1500);
  },
  async mobileMenu(page) {
    if (!(await openMobileMenuIfNeeded(page))) return 'skip: menu toggle hidden at this width (desktop navigation)';
    return null;
  },
};

// Planned rollout order. module = unit that later flips to strict.
const PAGES = [
  { module: 'shell', key: 'shell-plain', path: '/account' },
  { module: 'shell', key: 'shell-nav-dropdown', path: '/account', prepare: prepare.navDropdown },
  { module: 'shell', key: 'shell-search-dialog', path: '/account', prepare: prepare.searchDialog },
  { module: 'shell', key: 'shell-mobile-menu', path: '/account', prepare: prepare.mobileMenu },
  { module: 'dashboard', key: 'dashboard', path: '/', determinism: true },
  { module: 'orders', key: 'orders-index', path: '/admin/orders' },
  { module: 'orders', key: 'orders-show', path: (i) => `/admin/orders/${i.order}` },
  // New orders are created by the customer role (orders.create); superadmin gets 403 by design.
  { module: 'orders', key: 'orders-create', path: '/order/new', persona: 'customer' },
  // /admin/catalog redirects to the shared product list at /catalog.
  { module: 'products', key: 'products-index', path: '/catalog' },
  { module: 'products', key: 'products-form', path: (i) => `/admin/catalog/${i.product}/edit` },
  { module: 'products', key: 'inventory', path: '/admin/inventory' },
  { module: 'products', key: 'dictionary', path: '/admin/catalog-settings/categories' },
  { module: 'warranties', key: 'warranties-index', path: '/admin/warranties' },
  { module: 'warranties', key: 'warranties-show', path: (i) => `/admin/warranties/${i.warranty}` },
  { module: 'customers', key: 'users-index', path: '/admin/users' },
  { module: 'customers', key: 'user-groups', path: '/admin/user-groups' },
  { module: 'customers', key: 'customer-portal', path: '/admin/customer-portal' },
  { module: 'reports', key: 'reports-management', path: '/admin/reports' },
  { module: 'reports', key: 'receivables-index', path: '/admin/receivables' },
  { module: 'reports', key: 'commissions', path: '/admin/commissions' },
  { module: 'settings', key: 'settings-index', path: '/admin/settings' },
  { module: 'settings', key: 'settings-appearance', path: '/admin/settings/appearance' },
  { module: 'settings', key: 'settings-public-storefront', path: '/admin/settings/public-storefront' },
  { module: 'settings', key: 'settings-smtp', path: '/admin/settings/smtp' },
  { module: 'settings', key: 'settings-documents', path: '/admin/settings/documents' },
  { module: 'auth', key: 'auth-login', path: '/login', guest: true },
  { module: 'auth', key: 'auth-forgot-password', path: '/forgot-password', guest: true },
  // errors/minimal.blade.php hard-codes data-theme="dark": recorded as a finding, not an infra failure.
  { module: 'error', key: 'error-404', path: '/admin/ne-postoji-vizuelni-qa', status: 404, themeFixed: true },
].filter((p) => ONLY.length === 0 || ONLY.includes(p.key));

const infra = [];     // infrastructure failures: these fail the job
const findings = [];  // recorded design violations: these never fail the baseline job
const cells = [];
const determinism = [];
const fail = (code, detail) => { infra.push(code + ' :: ' + detail); console.error('INFRA ' + code + ' :: ' + detail); };

function contextOptions(theme, extra = {}) {
  return {
    viewport: { width: 1280, height: 800 }, deviceScaleFactor: 1, colorScheme: theme,
    reducedMotion: 'reduce', locale: 'sr-RS', timezoneId: 'Europe/Belgrade', bypassCSP: true, ...extra,
  };
}
async function newThemedContext(browser, theme, storageState) {
  const ctx = await browser.newContext(contextOptions(theme, storageState ? { storageState } : {}));
  // The admin's own mechanism: layouts/app.blade.php reads localStorage ald1n-theme-mode in <head>.
  await ctx.addInitScript((mode) => { try { localStorage.setItem('ald1n-theme-mode', mode); } catch (e) { /* storage may be blocked */ } }, theme);
  return ctx;
}
async function login(browser, persona) {
  const ctx = await newThemedContext(browser, 'light');
  const page = await ctx.newPage();
  const resp = await page.goto(baseUrl + '/login', { waitUntil: 'load', timeout: 30000 });
  if (!resp || resp.status() !== 200) throw new Error('LOGIN_PAGE HTTP ' + (resp && resp.status()));
  await page.locator('input[name="login"]').fill(fixture[persona].login);
  await page.locator('input[name="password"]').fill(fixture[persona].password);
  await page.locator('.auth-login-submit').click();
  await page.waitForURL((url) => url.origin === baseUrl && url.pathname === '/', { timeout: 15000 });
  const state = await ctx.storageState();
  await ctx.close();
  return state;
}

async function measureOverflow(page) {
  return page.evaluate(() => {
    const vw = window.innerWidth;
    const scrollWidth = document.documentElement.scrollWidth;
    const clipped = (el) => {
      for (let n = el.parentElement; n && n !== document.documentElement; n = n.parentElement) {
        const o = getComputedStyle(n).overflowX;
        if (o !== 'visible') return true;
      }
      return false;
    };
    const sel = (el) => el.tagName.toLowerCase() + (el.id ? '#' + el.id : '')
      + [...el.classList].slice(0, 3).map((c) => '.' + c).join('');
    const offenders = [];
    if (scrollWidth > vw + 1) {
      for (const el of document.body.querySelectorAll('*')) {
        const r = el.getBoundingClientRect();
        if (r.width === 0 || r.right <= vw + 1 || getComputedStyle(el).position === 'fixed' || clipped(el)) continue;
        offenders.push({ s: sel(el), over: Math.round(r.right - vw) });
      }
    }
    offenders.sort((a, b) => b.over - a.over);
    return { vw, scrollWidth, offenders: offenders.slice(0, 4).map((o) => o.s + ' (+' + o.over + 'px)'), count: offenders.length };
  });
}
async function runAxe(page) {
  if (!(await page.evaluate(() => typeof window.axe === 'object'))) await page.addScriptTag({ content: axeSource });
  return page.evaluate(async (rules) => {
    const r = await window.axe.run(document, { runOnly: { type: 'rule', values: rules }, resultTypes: ['violations'] });
    return r.violations.map((v) => ({ rule: v.id, count: v.nodes.length, selectors: v.nodes.slice(0, 3).map((n) => n.target.join(' ')) }));
  }, AXE_RULES);
}
// Freeze text that changes between runs (dates, times, relative times, rate) - after axe, text only.
async function freezeDynamic(page) {
  await page.addStyleTag({ content: '*,*::before,*::after{animation:none!important;transition:none!important;caret-color:transparent!important;scroll-behavior:auto!important}' });
  await page.evaluate(async () => {
    if (document.fonts && document.fonts.ready) await document.fonts.ready;
    const rules = [
      [/\b\d{1,2}\.\s?\d{1,2}\.\s?\d{4}\.?/g, '01.01.2026.'],
      [/\b\d{4}-\d{2}-\d{2}\b/g, '2026-01-01'],
      [/\b\d{1,2}:\d{2}(:\d{2})?\b/g, '00:00'],
      [/\bpre\s+\d+\s+\S+/gi, 'pre 1 min'],
    ];
    const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
    for (let n = walker.nextNode(); n; n = walker.nextNode()) {
      if (['SCRIPT', 'STYLE'].includes(n.parentElement?.tagName)) continue;
      let t = n.nodeValue;
      for (const [re, to] of rules) t = t.replace(re, to);
      if (t !== n.nodeValue) n.nodeValue = t;
    }
  });
}
async function settle(page) {
  await page.waitForLoadState('networkidle', { timeout: 8000 }).catch(() => {});
  await page.evaluate(() => (document.fonts && document.fonts.ready) || null).catch(() => {});
}

async function runCell(ctx, pg, width, theme) {
  const tag = `${pg.key} ${browserName} ${width} ${theme}`;
  const cell = { module: pg.module, page: pg.key, browser: browserName, width, theme, status: 'ok' };
  const page = await ctx.newPage();
  const local = { console: [], failed: [] };
  const target = P(pg.path);
  page.on('console', (m) => {
    // "Failed to load resource" duplicates the response/requestfailed records captured below.
    if (m.type() === 'error' && !/Failed to load resource/.test(m.text())) local.console.push(m.text().slice(0, 200));
  });
  page.on('pageerror', (e) => local.console.push('pageerror: ' + String(e.message).slice(0, 200)));
  page.on('response', (r) => {
    const u = new URL(r.url());
    if (u.origin !== baseUrl || r.status() < 400) return;
    const isMain = u.pathname === target && r.request().resourceType() === 'document';
    if (isMain && r.status() === (pg.status || 200)) return;
    local.failed.push(r.status() + ' ' + u.pathname);
    if (r.status() >= 500) fail('HTTP_5XX', tag + ': ' + r.status() + ' ' + u.pathname);
  });
  page.on('requestfailed', (r) => {
    const u = new URL(r.url());
    if (u.origin !== baseUrl || /ERR_ABORTED|cancelled/i.test(r.failure()?.errorText || '')) return;
    local.failed.push('requestfailed ' + u.pathname);
  });
  try {
    await page.setViewportSize({ width, height: HEIGHTS[width] || 900 });
    const resp = await page.goto(baseUrl + target, { waitUntil: 'load', timeout: 30000 });
    const want = pg.status || 200;
    if (!resp || resp.status() !== want) throw new Error(`HTTP ${resp && resp.status()} expected ${want}`);
    if (!pg.status && new URL(page.url()).pathname !== target) throw new Error('redirected to ' + new URL(page.url()).pathname);
    await settle(page);
    const resolved = await page.evaluate(() => document.documentElement.dataset.theme);
    if (resolved !== theme) {
      if (!pg.themeFixed) throw new Error(`theme mechanism did not apply ${theme}, got ${resolved}`);
      findings.push({ module: pg.module, page: pg.key, browser: browserName, width, theme, rule: 'theme-not-applied', count: 1, selectors: [`html[data-theme=${resolved}]`] });
    }
    if (pg.prepare) {
      const skipped = await pg.prepare(page);
      if (typeof skipped === 'string') { cell.status = skipped; return; }
      await settle(page);
    }
    const overflow = await measureOverflow(page);
    if (overflow.scrollWidth > overflow.vw + 1) {
      findings.push({ module: pg.module, page: pg.key, browser: browserName, width, theme, rule: 'horizontal-overflow',
        count: Math.max(1, overflow.count), detail: `scrollWidth ${overflow.scrollWidth} > viewport ${overflow.vw}`, selectors: overflow.offenders });
    }
    for (const v of await runAxe(page)) findings.push({ module: pg.module, page: pg.key, browser: browserName, width, theme, ...v });
    if (local.console.length) findings.push({ module: pg.module, page: pg.key, browser: browserName, width, theme, rule: 'console-error', count: local.console.length, selectors: local.console.slice(0, 3) });
    if (local.failed.length) findings.push({ module: pg.module, page: pg.key, browser: browserName, width, theme, rule: 'failed-request', count: local.failed.length, selectors: local.failed.slice(0, 3) });
    await freezeDynamic(page);
    const file = `${pg.module}__${pg.key}__${browserName}__${width}__${theme}.png`;
    const shotOpts = { path: path.join(shotDir, file), fullPage: true, animations: 'disabled', caret: 'hide', timeout: 30000,
      mask: [page.locator(MASK_SELECTORS.join(','))], maskColor: '#7f7f7f' };
    const buf = await page.screenshot(shotOpts);
    if (!fs.existsSync(shotOpts.path) || fs.statSync(shotOpts.path).size < 500) throw new Error('screenshot missing');
    cell.screenshot = file;
    cell.sha256 = crypto.createHash('sha256').update(buf).digest('hex');
    if (pg.determinism && width === 1280 && theme === 'light') {
      const again = await page.screenshot({ fullPage: true, animations: 'disabled', caret: 'hide', mask: shotOpts.mask, maskColor: '#7f7f7f' });
      determinism.push({ page: pg.key, browser: browserName, identicalInRun: Buffer.compare(buf, again) === 0 });
    }
  } catch (e) {
    cell.status = 'infra-failure';
    fail('CELL', tag + ': ' + e.message.split('\n')[0]);
  } finally {
    cells.push(cell);
    await page.close().catch(() => {});
  }
}

(async () => {
  const started = Date.now();
  const browser = await playwright[browserName].launch();
  const states = {};
  for (const persona of ['admin', 'customer']) {
    try {
      states[persona] = await login(browser, persona);
    } catch (e) {
      fail('LOGIN', persona + ': ' + e.message.split('\n')[0]);
    }
  }
  if (states.admin) {
    const authCtx = { admin: {}, customer: {} }; const guestCtx = {};
    for (const theme of THEMES) {
      authCtx.admin[theme] = await newThemedContext(browser, theme, states.admin);
      if (states.customer) authCtx.customer[theme] = await newThemedContext(browser, theme, states.customer);
      guestCtx[theme] = await newThemedContext(browser, theme);
    }
    for (const pg of PAGES) {
      for (const theme of THEMES) {
        for (const width of WIDTHS) {
          const ctx = pg.guest ? guestCtx[theme] : authCtx[pg.persona || 'admin'][theme];
          if (!ctx) { fail('CELL', `${pg.key} ${browserName} ${width} ${theme}: persona login failed`); continue; }
          await runCell(ctx, pg, width, theme);
        }
      }
      console.log(`done ${pg.module}/${pg.key} (${((Date.now() - started) / 60000).toFixed(1)} min)`);
    }
    for (const c of [...Object.values(authCtx.admin), ...Object.values(authCtx.customer), ...Object.values(guestCtx)]) await c.close();
  }
  await browser.close();
  const out = {
    browser: browserName, generated_at_ci: new Date().toISOString(), rules: [...AXE_RULES, 'horizontal-overflow', 'console-error', 'failed-request'],
    widths: WIDTHS, themes: THEMES, pages: PAGES.map((p) => ({ module: p.module, key: p.key })),
    infra_failures: infra, cells, determinism, findings,
  };
  fs.writeFileSync(path.join(artifactDir, `admin-visual-baseline-${browserName}.json`), JSON.stringify(out, null, 1));
  console.log(`ADMIN_VISUAL ${browserName}: cells=${cells.length} findings=${findings.length} infra=${infra.length} minutes=${((Date.now() - started) / 60000).toFixed(1)}`);
  process.exit(infra.length ? 1 : 0);
})().catch((e) => { console.error('FATAL ' + e.stack); process.exit(1); });
