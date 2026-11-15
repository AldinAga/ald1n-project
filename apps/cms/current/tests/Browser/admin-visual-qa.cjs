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

const inPageContrast = require('./lib-inpage-contrast.cjs');
const WIDTHS = (process.env.ADMIN_VISUAL_WIDTHS || '375,430,768,1280').split(',').map(Number);
// Shell pages: 360 (smallest phone), 768 (sheet menu), 1200 (first desktop width) and 1280 are added to the matrix.
const SHELL_WIDTHS = (process.env.ADMIN_VISUAL_WIDTHS ? WIDTHS : [360, 375, 430, 768, 1200, 1280]);
const SHELL_EXTRA_WIDTHS = (process.env.ADMIN_VISUAL_WIDTHS ? WIDTHS : [360, 768, 1280]);
const DESKTOP_FROM = 1200; // keep in sync with ald1n-admin.css and layouts/app.blade.php
const HEIGHTS = { 360: 740, 375: 812, 430: 932, 768: 1024, 1200: 800, 1280: 800, 1440: 900 };
const foldMetrics = []; // measured bottoms (px from the top of the page) of the dashboard's first-look blocks
const THEMES = ['light', 'dark'];
const AXE_RULES = ['color-contrast', 'document-title', 'html-has-lang', 'label', 'button-name', 'link-name', 'image-alt'];
const ONLY = (process.env.ADMIN_VISUAL_ONLY || '').split(',').filter(Boolean);
const shotDir = path.join(artifactDir, 'screenshots', browserName);
fs.mkdirSync(shotDir, { recursive: true });

// Masked in screenshots (not in axe): values that change every run or depend on "today".
const MASK_SELECTORS = [
  '[data-exchange-rate-value]',      // EUR/RSD rate in header and dashboard, changes with the rate source
  '.a1-rate',                       // header rate badge incl. stale marker
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
// Pointer devices open a group on hover and a click on an open group closes it, so desktop cells hover; the sheet menu clicks.
async function openGroup(page, summary) {
  if (await page.locator(toggle).isVisible()) await summary.click(); else await summary.hover();
}
const prepare = {
  async navDropdown(page) {
    await openMobileMenuIfNeeded(page);
    const summary = page.locator('.a1-header__nav details.a1-nav-dd > summary').first();
    await openGroup(page, summary);
    await page.waitForFunction(() => !!document.querySelector('.a1-header__nav details.a1-nav-dd[open]'), null, { timeout: 5000 });
  },
  async searchDialog(page) {
    await page.locator('[data-header-product-search-toggle]').click();
    await page.waitForFunction(() => { const l = document.querySelector('#headerProductSearch'); return l && l.open; }, null, { timeout: 5000 });
    await page.locator('[data-header-product-search-input]').fill('Admin');
    // Wait for results (or the empty state) so the result rows are part of the measured state.
    await page.waitForFunction(() => !document.querySelector('[data-header-product-search-results]').hidden
      || /Nema rezultata|nije dostupna/.test(document.querySelector('[data-header-product-search-status]').textContent), null, { timeout: 6000 }).catch(() => {});
    await page.waitForTimeout(300);
  },
  async userMenu(page) {
    await openMobileMenuIfNeeded(page);
    await openGroup(page, page.locator('.a1-header__user > summary'));
    await page.waitForFunction(() => !!document.querySelector('.a1-header__user[open]'), null, { timeout: 5000 });
  },
  async lastDropdown(page) {
    await openMobileMenuIfNeeded(page);
    await openGroup(page, page.locator('.a1-header__nav details.a1-nav-dd > summary').last());
    await page.waitForFunction(() => { const all = document.querySelectorAll('.a1-header__nav details.a1-nav-dd'); return all[all.length - 1].open; }, null, { timeout: 5000 });
  },
  // States the seed data does not produce on its own: an active nav item (the dashboard), count badges, the stale rate.
  // The classes are the real ones; the server-rendered markup is covered by AdminShellNavigationTest.
  async states(page) {
    await openMobileMenuIfNeeded(page);
    await page.evaluate(() => {
      document.querySelectorAll('.a1-header__nav .a1-nav__link').forEach((a, i) => { if (a.tagName === 'A' && i < 3) a.insertAdjacentHTML('beforeend', ' <span class="a1-count">12</span>'); });
      document.querySelectorAll('.a1-header__quick').forEach((a) => a.insertAdjacentHTML('beforeend', '<span class="a1-count a1-count--float" aria-hidden="true">3</span>'));
      const rate = document.querySelector('.a1-rate');
      if (rate) { rate.classList.add('is-stale'); const l = rate.querySelector('span'); if (l) l.textContent = 'EUR/RSD · PROVERI'; }
    });
    if (!(await page.locator('.a1-nav__link--active[aria-current="page"]').count())) throw new Error('no active nav item on the dashboard');
  },
  async flashOk(page) {
    await page.locator('form[action$="/account/notifications"]').evaluate((f) => { f.noValidate = true; f.requestSubmit(); });
    await page.waitForSelector('[data-a1-flash] .a1-alert--ok', { timeout: 15000 });
    await page.waitForTimeout(500);
    const live = await page.locator('[data-a1-flash-live]').innerText();
    if (!live.trim()) throw new Error('flash status was not announced through the live region');
  },
  async flashError(page) {
    await page.locator('form[action$="/account/password"]').evaluate((f) => { f.noValidate = true; f.requestSubmit(); });
    await page.waitForSelector('[data-a1-flash] .a1-alert--danger', { timeout: 15000 });
    await page.waitForTimeout(300);
  },
  // Page rendered without any <script>: the sheet menu must be in normal flow and logout reachable.
  async noJsLogout(page) {
    if (await page.locator('[data-mobile-menu-toggle]').isVisible()) throw new Error('menu toggle is visible without JavaScript');
    await page.locator('.a1-header__user > summary').click();
    const logout = page.locator('form[action$="/logout"] button[type="submit"]');
    await logout.waitFor({ state: 'visible', timeout: 5000 });
    if (!(await logout.isEnabled())) throw new Error('logout button is not enabled without JavaScript');
  },
  async mobileMenu(page) {
    if (!(await openMobileMenuIfNeeded(page))) return 'skip: menu toggle hidden at this width (desktop navigation)';
    // Focus trap: with the sheet open, Tab and Shift+Tab never leave the header.
    for (const key of ['Tab', 'Shift+Tab']) {
      for (let i = 0; i < 70; i++) {
        await page.keyboard.press(key);
        const inside = await page.evaluate(() => !!document.activeElement?.closest('.a1-header'));
        if (!inside) { fail('FOCUS_TRAP', `${browserName} ${key} step ${i + 1}: focus left the open mobile menu`); return null; }
      }
    }
    return null;
  },
  // Dashboard: open every <details> (custom period, exact trend values, module list) so their text is part of the measured state.
  async dashboardOpenAll(page) {
    await page.evaluate(() => document.querySelectorAll('.a1-dash details').forEach((d) => { d.open = true; }));
  },
  // First look: the custom-range disclosure is open when a custom period is selected; the fold is measured with it closed (the state of a first visit).
  async dashboardFold(page) {
    await page.evaluate(() => document.querySelectorAll('.a1-dash details').forEach((d) => { d.open = false; }));
  },
  // Dashboard with long amounts: the same real elements, their text replaced by a 12.345.678,90 value.
  async dashboardLongNumbers(page) {
    await prepare.dashboardOpenAll(page);
    await page.evaluate(() => {
      const long = '12.345.678,90';
      document.querySelectorAll('.a1-dash .a1-kpi__value').forEach((el) => {
        const unit = el.querySelector('.a1-kpi__unit');
        el.textContent = long + ' ';
        el.classList.remove('a1-kpi__value--na');
        if (unit) el.appendChild(unit); else { const u = document.createElement('span'); u.className = 'a1-kpi__unit'; u.textContent = 'RSD'; el.appendChild(u); }
      });
      document.querySelectorAll('.a1-dash .a1-item__money').forEach((el) => { el.textContent = long + ' RSD'; });
      document.querySelectorAll('.a1-dash .a1-mix__value').forEach((el) => { el.textContent = long + ' RSD'; });
      document.querySelectorAll('.a1-dash .a1-item--order .a1-item__end .a1-item__sub').forEach((el) => { el.textContent = 'Preostalo ' + long; });
    });
  },
  // Dashboard brand mix on the second metric (Komadi), like a user pressing the toggle.
  async dashboardUnits(page) {
    await page.locator('.a1-dash [data-d3-mode="units"]').click();
    await page.waitForFunction(() => document.querySelector('[data-d3-view="units"]')?.hidden === false
      && document.querySelector('[data-d3-view="revenue"]')?.hidden === true
      && document.querySelector('[data-d3-mode="units"]')?.getAttribute('aria-pressed') === 'true', null, { timeout: 5000 });
    await prepare.dashboardOpenAll(page);
  },
};

const DASH_DATA = '/?period=custom&date_from=2026-01-01&date_to=2026-01-31';
// Planned rollout order. module = unit that later flips to strict.
const PAGES = [
  { module: 'shell', key: 'shell-plain', path: '/account', widths: SHELL_WIDTHS },
  { module: 'shell', key: 'shell-nav-dropdown', path: '/account', prepare: prepare.navDropdown, widths: SHELL_WIDTHS },
  { module: 'shell', key: 'shell-search-dialog', path: '/account', prepare: prepare.searchDialog, widths: SHELL_WIDTHS },
  { module: 'shell', key: 'shell-mobile-menu', path: '/account', prepare: prepare.mobileMenu, widths: SHELL_WIDTHS },
  { module: 'shell', key: 'shell-user-menu', path: '/account', prepare: prepare.userMenu, widths: SHELL_EXTRA_WIDTHS },
  { module: 'shell', key: 'shell-last-dropdown', path: '/account', prepare: prepare.lastDropdown, widths: SHELL_EXTRA_WIDTHS },
  { module: 'shell', key: 'shell-states', path: '/', prepare: prepare.states, widths: SHELL_EXTRA_WIDTHS },
  { module: 'shell', key: 'shell-flash-ok', path: '/account', prepare: prepare.flashOk, widths: SHELL_EXTRA_WIDTHS },
  { module: 'shell', key: 'shell-flash-error', path: '/account', prepare: prepare.flashError, widths: SHELL_EXTRA_WIDTHS },
  // Scripts are stripped from the document, so the page is what a visitor gets with JavaScript off. Theme stays at the HTML default.
  { module: 'shell', key: 'shell-no-js', path: '/account', prepare: prepare.noJsLogout, stripScripts: true, widths: [375, 1280] },
  // Module 2 (strict): the seed has one open order and, archived (so they never reach the lists), three completed
  // orders in January 2026. Default period = empty states, custom January = data, plus the Komadi metric, the
  // invalid-period alert, every <details> open, and the customer role's dashboard.
  { module: 'dashboard', key: 'dashboard', path: '/', determinism: true, widths: SHELL_WIDTHS },
  { module: 'dashboard', key: 'dashboard-open', path: '/', prepare: prepare.dashboardOpenAll, widths: SHELL_WIDTHS },
  { module: 'dashboard', key: 'dashboard-data', path: DASH_DATA, prepare: prepare.dashboardOpenAll, widths: SHELL_WIDTHS },
  // First look: at 1280x800 and 1440x900 the whole priorities strip and the entire first KPI row are above the fold (measured, not estimated).
  { module: 'dashboard', key: 'dashboard-fold', path: DASH_DATA, fold: true, prepare: prepare.dashboardFold, widths: [1280, 1440] },
  { module: 'dashboard', key: 'dashboard-units', path: DASH_DATA, prepare: prepare.dashboardUnits, widths: SHELL_WIDTHS },
  { module: 'dashboard', key: 'dashboard-error', path: '/?period=custom&date_from=2026-02-30&date_to=2026-03-01', widths: SHELL_WIDTHS },
  { module: 'dashboard', key: 'dashboard-customer', path: '/', persona: 'customer', prepare: prepare.dashboardOpenAll, widths: SHELL_WIDTHS },
  // Administrator role (business dashboard without superadmin blocks), desktop and phone. The superadmin is the operator role.
  { module: 'dashboard', key: 'dashboard-administrator', path: '/', persona: 'administrator', prepare: prepare.dashboardOpenAll, widths: [360, 1440] },
  // Long amounts (12.345.678,90 RSD) in KPI tiles, the brand mix and order rows: wraps, never overflows or clips.
  { module: 'dashboard', key: 'dashboard-long-numbers', path: DASH_DATA, prepare: prepare.dashboardLongNumbers, widths: [360, 1280] },
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
const selfTests = [];
const fail = (code, detail) => { infra.push(code + ' :: ' + detail); console.error('INFRA ' + code + ' :: ' + detail); };

function contextOptions(theme, extra = {}) {
  return {
    viewport: { width: 1280, height: 800 }, deviceScaleFactor: 1, colorScheme: theme,
    reducedMotion: 'reduce', locale: 'sr-RS', timezoneId: 'Europe/Belgrade', bypassCSP: true, ...extra,
  };
}
async function newThemedContext(browser, theme, storageState, extra = {}) {
  const ctx = await browser.newContext(contextOptions(theme, storageState ? { storageState, ...extra } : extra));
  // The admin's own mechanism: layouts/app.blade.php reads localStorage ald1n-theme-mode in <head>.
  await ctx.addInitScript((mode) => { try { localStorage.setItem('ald1n-theme-mode', mode); } catch (e) { /* storage may be blocked */ } }, theme);
  return ctx;
}
async function login(browser, persona) {
  const ctx = await newThemedContext(browser, 'light');
  const page = await ctx.newPage();
  const resp = await page.goto(baseUrl + '/login', { waitUntil: 'load', timeout: 30000 });
  if (!resp || resp.status() !== 200) throw new Error('LOGIN_PAGE HTTP ' + (resp && resp.status()));
  const cred = fixture[persona === 'admin' && browserName === 'webkit' ? 'admin_webkit' : persona];
  await page.locator('input[name="login"]').fill(cred.login);
  await page.locator('input[name="password"]').fill(cred.password);
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
    // An inner scroll container (table wrapper) legitimately clips; body/html do not count as such,
    // because body{overflow-x:hidden} hides a broken layout instead of fixing it.
    const clipped = (el) => {
      for (let n = el.parentElement; n && n !== document.body; n = n.parentElement) {
        if (getComputedStyle(n).overflowX !== 'visible') return true;
      }
      return false;
    };
    const sel = (el) => el.tagName.toLowerCase() + (el.id ? '#' + el.id : '')
      + [...el.classList].slice(0, 3).map((c) => '.' + c).join('');
    const offenders = [];
    for (const el of document.body.querySelectorAll('*')) {
      const r = el.getBoundingClientRect();
      if (r.width === 0 || r.right <= vw + 1) continue;
      const cs = getComputedStyle(el);
      if (cs.position === 'fixed' || cs.position === 'sticky' || cs.visibility === 'hidden' || cs.opacity === '0' || cs.display === 'none' || clipped(el)) continue;
      offenders.push({ s: sel(el), over: Math.round(r.right - vw) });
    }
    offenders.sort((a, b) => b.over - a.over);
    return {
      vw, scrollWidth, offenders: offenders.slice(0, 4).map((o) => o.s + ' (+' + o.over + 'px)'), count: offenders.length,
      bodyOverflowX: getComputedStyle(document.body).overflowX, htmlOverflowX: getComputedStyle(document.documentElement).overflowX,
    };
  });
}
// Shell pages (module "shell") measure the shell only: header, search dialog, skip link, flash region and footer
// carry .a1-shell. The page content of /account is old-styled and belongs to a later module; document-level rules
// (title, lang) still run on the whole document.
const SHELL_SCOPE = '.a1-shell';
const DOCUMENT_RULES = ['document-title', 'html-has-lang'];
async function runAxe(page, scoped) {
  if (!(await page.evaluate(() => typeof window.axe === 'object'))) await page.addScriptTag({ content: axeSource });
  const run = (rules, context) => page.evaluate(async ({ rules, context }) => {
    const r = await window.axe.run(context ? { include: [[context]] } : document, { runOnly: { type: 'rule', values: rules }, resultTypes: ['violations'] });
    return r.violations.map((v) => ({ rule: v.id, count: v.nodes.length, selectors: v.nodes.slice(0, 3).map((n) => n.target.join(' ')) }));
  }, { rules, context });
  if (!scoped) return run(AXE_RULES, null);
  return [...await run(AXE_RULES.filter((r) => !DOCUMENT_RULES.includes(r)), SHELL_SCOPE), ...await run(DOCUMENT_RULES, null)];
}
// Shell-only gates, recorded as findings (strict mode for "shell" fails on any):
//  - computed-contrast: flattened background chain, worst case over gradients; axe leaves text on glass as "incomplete".
//  - nav-wraps / nav-overflows: from DESKTOP_FROM the primary navigation is one row (all top-level items share one offsetTop).
//  - shell-edge-misaligned: the header's inner container lines up with the content container (.page-shell).
async function shellChecks(page, pg, width, theme) {
  const out = [];
  const base = { module: pg.module, page: pg.key, browser: browserName, width, theme };
  await page.evaluate(async () => {
    const t = Date.now();
    while (document.getAnimations().some((a) => a.playState === 'running' && a.effect && a.effect.getComputedTiming().endTime !== Infinity) && Date.now() - t < 3000) await new Promise((r) => setTimeout(r, 50));
  });
  const c = await page.evaluate(inPageContrast, SHELL_SCOPE);
  if (c.out.length) out.push({ ...base, rule: 'computed-contrast', count: c.out.length, selectors: c.out.slice(0, 3) });
  // Sanity: the gate must have looked at something (at phone widths the closed header only has icons; the footer text is always there).
  if (c.checked < 1 && !pg.stripScripts) out.push({ ...base, rule: 'computed-contrast-too-few-nodes', count: 1, selectors: ['checked ' + c.checked] });
  const geo = await page.evaluate((from) => {
    const nav = document.querySelector('.a1-header__nav .a1-nav');
    const desktop = window.innerWidth >= from;
    const tops = nav ? [...nav.children].filter((li) => li.getClientRects().length).map((li) => Math.round(li.getBoundingClientRect().top)) : [];
    const wrap = document.querySelector('.a1-header .a1-wrap')?.getBoundingClientRect();
    const shell = document.querySelector('.page-shell')?.getBoundingClientRect();
    return {
      desktop, rows: new Set(tops).size, items: tops.length,
      navOverflow: nav ? nav.scrollWidth - nav.clientWidth : 0,
      edge: wrap && shell ? [Math.round(wrap.left - shell.left), Math.round(wrap.right - shell.right)] : null,
    };
  }, DESKTOP_FROM);
  if (geo.desktop && geo.items > 0 && geo.rows !== 1) out.push({ ...base, rule: 'nav-wraps', count: 1, selectors: [`${geo.items} items on ${geo.rows} rows`] });
  if (geo.desktop && geo.navOverflow > 1) out.push({ ...base, rule: 'nav-overflows', count: 1, selectors: ['+' + geo.navOverflow + 'px'] });
  if (geo.edge && (Math.abs(geo.edge[0]) > 2 || Math.abs(geo.edge[1]) > 2)) out.push({ ...base, rule: 'shell-edge-misaligned', count: 1, selectors: ['left ' + geo.edge[0] + 'px, right ' + geo.edge[1] + 'px vs .page-shell'] });
  return out;
}
// Dashboard-only gates (module 2), recorded as findings (strict mode for "dashboard" fails on any):
//  - computed-contrast: same method as the shell cells, scoped to the dashboard root .a1-dash.
//  - small-target: a visible link, button, summary, select or checkbox label under 44x44 px (inline links in a sentence excepted).
//  - unlabeled-control: a visible control without an accessible name (axe covers label/button-name/link-name; summary and wrapped checkboxes are added here).
//  - dashboard-structure: the root exists, exactly one <h1>, every pressed-state button carries aria-pressed.
const DASH_SCOPE = '.a1-dash';
async function dashboardChecks(page, pg, width, theme) {
  const out = [];
  const base = { module: pg.module, page: pg.key, browser: browserName, width, theme };
  await page.evaluate(async () => {
    const t = Date.now();
    while (document.getAnimations().some((a) => a.playState === 'running' && a.effect && a.effect.getComputedTiming().endTime !== Infinity) && Date.now() - t < 3000) await new Promise((r) => setTimeout(r, 50));
  });
  const c = await page.evaluate(inPageContrast, DASH_SCOPE);
  if (c.out.length) out.push({ ...base, rule: 'computed-contrast', count: c.out.length, selectors: c.out.slice(0, 3) });
  if (c.checked < 30) out.push({ ...base, rule: 'computed-contrast-too-few-nodes', count: 1, selectors: ['checked ' + c.checked] });
  const dom = await page.evaluate((scope) => {
    const root = document.querySelector(scope);
    if (!root) return { missing: true };
    const label = (e) => [...e.classList].slice(0, 3).map((x) => '.' + x).join('') || e.tagName.toLowerCase();
    const shown = (e) => e.getClientRects().length > 0 && getComputedStyle(e).visibility !== 'hidden';
    const controls = [...root.querySelectorAll('a[href], button, summary, select, input:not([type=hidden]), .a1-check')].filter(shown);
    const small = controls
      .filter((e) => !e.matches('.a1-link--inline, .a1-check__input'))
      .filter((e) => { const r = e.getBoundingClientRect(); return r.width < 43.5 || r.height < 43.5; })
      .map((e) => e.tagName.toLowerCase() + label(e) + ' ' + Math.round(e.getBoundingClientRect().width) + 'x' + Math.round(e.getBoundingClientRect().height));
    const nameOf = (e) => (e.getAttribute('aria-label') || '').trim()
      || (e.getAttribute('aria-labelledby') ? [...e.getAttribute('aria-labelledby').split(/\s+/)].map((id) => document.getElementById(id)?.textContent || '').join(' ').trim() : '')
      || (e.labels && e.labels.length ? [...e.labels].map((l) => l.textContent).join(' ').trim() : '')
      || (e.textContent || '').trim() || (e.getAttribute('title') || '').trim();
    const unlabeled = controls.filter((e) => !e.matches('.a1-check') && !nameOf(e)).map((e) => e.tagName.toLowerCase() + label(e));
    // Toggle buttons (brand metric) carry aria-pressed; the period buttons navigate and carry aria-current on the current one.
    const pressed = [...root.querySelectorAll('[data-d3-mode]')].filter((b) => !b.hasAttribute('aria-pressed')).map(label);
    const dates = [...root.querySelectorAll('input[type=date]')].filter(shown)
      .filter((e) => Math.abs(e.getBoundingClientRect().height - 48) > 1)
      .map((e) => 'date ' + Math.round(e.getBoundingClientRect().height) + 'px (control is 48px)');
    const clipped = [...root.querySelectorAll('*')]
      .filter((e) => shown(e) && !e.closest('.a1-sr, .a1-tablewrap') && !e.matches('input, select, textarea, svg, svg *'))
      .filter((e) => { const o = getComputedStyle(e).overflowX; return o !== 'visible' && e.scrollWidth > e.clientWidth + 1; })
      .map((e) => label(e) + ' ' + e.scrollWidth + '>' + e.clientWidth);
    return { missing: false, small, unlabeled, pressed, dates, clipped, h1: document.querySelectorAll('h1').length, checkedControls: controls.length };
  }, DASH_SCOPE);
  if (dom.missing) { out.push({ ...base, rule: 'dashboard-structure', count: 1, selectors: ['.a1-dash missing'] }); return out; }
  if (pg.fold) {
    const f = await page.evaluate(() => {
      window.scrollTo(0, 0);
      const bottom = (s) => { const e = document.querySelector(s); return e ? Math.round(e.getBoundingClientRect().bottom) : null; };
      const top = (s) => { const e = document.querySelector(s); return e ? Math.round(e.getBoundingClientRect().top) : null; };
      return { vh: window.innerHeight, scrollY: window.scrollY, headerBottom: bottom('.a1-header'),
        strip: bottom('.a1-strip'), revenue: bottom('[data-dashboard-d1-kpi="revenue"]'), gross: bottom('[data-dashboard-d1-kpi="gross"]'),
        net: bottom('[data-dashboard-d1-kpi="net"]'), netTop: top('[data-dashboard-d1-kpi="net"]'), sale: bottom('[data-dashboard-d1-kpi="sale"]') };
    });
    foldMetrics.push({ browser: browserName, width, theme, ...f });
    console.log('FOLD ' + browserName + ' ' + width + 'x' + f.vh + ' ' + theme + ' ' + JSON.stringify(f));
    const rowBottom = Math.max(f.revenue || 0, f.gross || 0, f.net || 0);
    const missed = [];
    if (f.strip === null || f.strip > f.vh) missed.push('priorities strip bottom ' + f.strip + ' > ' + f.vh);
    if (!rowBottom || rowBottom > f.vh) missed.push('first KPI row bottom ' + rowBottom + ' > ' + f.vh);
    if (missed.length) out.push({ ...base, rule: 'above-the-fold', count: missed.length, selectors: missed });
  }
  if (dom.small.length) out.push({ ...base, rule: 'small-target', count: dom.small.length, selectors: dom.small.slice(0, 3) });
  if (dom.unlabeled.length) out.push({ ...base, rule: 'unlabeled-control', count: dom.unlabeled.length, selectors: dom.unlabeled.slice(0, 3) });
  if (dom.dates.length) out.push({ ...base, rule: 'control-height', count: dom.dates.length, selectors: dom.dates.slice(0, 3) });
  if (dom.clipped.length) out.push({ ...base, rule: 'clipped-text', count: dom.clipped.length, selectors: dom.clipped.slice(0, 3) });
  if (dom.pressed.length || dom.h1 !== 1) out.push({ ...base, rule: 'dashboard-structure', count: dom.pressed.length + (dom.h1 === 1 ? 0 : 1), selectors: [...dom.pressed, 'h1 x' + dom.h1].slice(0, 3) });
  if (dom.checkedControls < 5) out.push({ ...base, rule: 'dashboard-structure', count: 1, selectors: ['only ' + dom.checkedControls + ' controls found'] });
  return out;
}
// Freeze text that changes between runs (dates, times, relative times, rate) - after axe, text only.
async function freezeDynamic(page) {
  await page.addStyleTag({ content: '*,*::before,*::after{animation:none!important;transition:none!important;caret-color:transparent!important;scroll-behavior:auto!important}' });
  await page.evaluate(async () => {
    if (document.fonts && document.fonts.ready) await document.fonts.ready;
    const months = '(?:januar|februar|mart|april|maj|jun|jul|avgust|septembar|oktobar|novembar|decembar|jan|feb|mar|apr|avg|sep|sept|okt|nov|dec)[a-zčćšđž]*\\.?';
    const rules = [
      [/\b\d+\.\d{5,}\b/g, '0.0'],   // computed day counts such as 302.98242551367 dana
      [new RegExp('(?:ponedeljak|utorak|sreda|četvrtak|petak|subota|nedelja),?\\s*', 'gi'), ''],
      [new RegExp('\\b\\d{1,2}\\.\\s*' + months + '\\s*\\d{4}\\.?', 'gi'), '01.01.2026.'],
      [new RegExp('\\b' + months + '\\s+\\d{4}\\.?', 'gi'), 'januar 2026'],
      [/\b\d{4}-\d{2}-\d{2}T[\d:.]+Z?\b/g, '2026-01-01'],
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
// Names the elements whose pixels differ between two consecutive captures (up to 6).
async function diffElements(page, mask) {
  const count = await page.evaluate(() => {
    const els = [...document.querySelectorAll('.page-shell *')].filter((e) => e.getBoundingClientRect().height > 8).slice(0, 150);
    els.forEach((e, i) => e.setAttribute('data-vqa-i', String(i)));
    return els.length;
  });
  const out = [];
  for (let i = 0; i < count && out.length < 6; i++) {
    const loc = page.locator(`[data-vqa-i="${i}"]`);
    try {
      const a = await loc.screenshot({ animations: 'disabled', caret: 'hide', mask, maskColor: '#7f7f7f', timeout: 3000 });
      const b = await loc.screenshot({ animations: 'disabled', caret: 'hide', mask, maskColor: '#7f7f7f', timeout: 3000 });
      if (Buffer.compare(a, b) !== 0) out.push(await loc.evaluate((e) => e.tagName.toLowerCase() + (e.id ? '#' + e.id : '') + [...e.classList].slice(0, 3).map((c) => '.' + c).join('')));
    } catch (e) { /* element not capturable */ }
  }
  return out;
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
  const targetPath = target.split('?')[0]; // cells may carry a query string (dashboard period); the redirect check compares the path only
  page.on('console', (m) => {
    // "Failed to load resource" duplicates the response/requestfailed records captured below.
    if (m.type() === 'error' && !/Failed to load resource/.test(m.text())) local.console.push(m.text().slice(0, 200));
  });
  page.on('pageerror', (e) => local.console.push('pageerror: ' + String(e.message).slice(0, 200)));
  page.on('response', (r) => {
    const u = new URL(r.url());
    if (u.origin !== baseUrl || r.status() < 400) return;
    const isMain = u.pathname === targetPath && r.request().resourceType() === 'document';
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
    if (pg.stripScripts) {
      // The document arrives without any <script> element: the JavaScript-off experience, on every engine.
      await page.route(baseUrl + target, async (route) => {  // shortcut: exact URL incl. query, fine for the no-JS cells (no query)
        if (route.request().resourceType() !== 'document') return route.continue();
        const upstream = await route.fetch();
        // Scripts removed; <noscript> unwrapped, because the browser still has scripting enabled and would otherwise ignore it.
        const body = (await upstream.text()).replace(/<script\b[\s\S]*?<\/script>/gi, '').replace(/<\/?noscript>/gi, '');
        const headers = { ...upstream.headers() };
        delete headers['content-length'];
        await route.fulfill({ status: upstream.status(), headers, body });
      });
    }
    const resp = await page.goto(baseUrl + target, { waitUntil: 'load', timeout: 30000 });
    const want = pg.status || 200;
    if (!resp || resp.status() !== want) throw new Error(`HTTP ${resp && resp.status()} expected ${want}`);
    if (!pg.status && new URL(page.url()).pathname !== targetPath) throw new Error('redirected to ' + new URL(page.url()).pathname);
    await settle(page);
    const resolved = await page.evaluate(() => document.documentElement.dataset.theme);
    if (resolved !== theme && !pg.stripScripts) {
      if (!pg.themeFixed) throw new Error(`theme mechanism did not apply ${theme}, got ${resolved}`);
      findings.push({ module: pg.module, page: pg.key, browser: browserName, width, theme, rule: 'theme-not-applied', count: 1, selectors: [`html[data-theme=${resolved}]`] });
    }
    if (pg.prepare) {
      const skipped = await pg.prepare(page);
      if (typeof skipped === 'string') { cell.status = skipped; return; }
      await settle(page);
    }
    const overflow = await measureOverflow(page);
    if (overflow.scrollWidth > overflow.vw + 1 || overflow.count > 0) {
      findings.push({ module: pg.module, page: pg.key, browser: browserName, width, theme, rule: 'horizontal-overflow',
        count: Math.max(1, overflow.count),
        detail: `scrollWidth ${overflow.scrollWidth} vs viewport ${overflow.vw}; body overflow-x ${overflow.bodyOverflowX}, html ${overflow.htmlOverflowX}`,
        selectors: overflow.offenders });
    }
    for (const v of await runAxe(page, pg.module === 'shell')) findings.push({ module: pg.module, page: pg.key, browser: browserName, width, theme, ...v });
    if (pg.module === 'shell') findings.push(...await shellChecks(page, pg, width, theme));
    if (pg.module === 'dashboard') findings.push(...await dashboardChecks(page, pg, width, theme));
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
    // Visible text after freezing, so a pixel difference between two runs can be traced to a string.
    const textDir = path.join(artifactDir, 'text', browserName);
    fs.mkdirSync(textDir, { recursive: true });
    fs.writeFileSync(path.join(textDir, file.replace(/\.png$/, '.txt')), await page.evaluate(() => document.body.innerText));
    if (pg.determinism && width === 1280 && theme === 'light') {
      const again = await page.screenshot({ fullPage: true, animations: 'disabled', caret: 'hide', mask: shotOpts.mask, maskColor: '#7f7f7f' });
      const identical = Buffer.compare(buf, again) === 0;
      const entry = { page: pg.key, browser: browserName, identicalInRun: identical };
      if (!identical) entry.differing = await diffElements(page, shotOpts.mask);
      determinism.push(entry);
    }
    // Harness self-test, once per run: the overflow detector must see a deliberately too-wide element.
    if (pg.key === 'shell-plain' && width === 375 && theme === 'light') {
      await page.evaluate(() => { const d = document.createElement('div'); d.id = 'vqa-selftest'; d.style.cssText = 'width:3000px;height:4px'; document.body.appendChild(d); });
      const probe = await measureOverflow(page);
      await page.evaluate(() => document.getElementById('vqa-selftest').remove());
      selfTests.push({ overflowDetector: probe.offenders.length > 0, probe });
      if (!probe.offenders.length) fail('SELFTEST', 'overflow detector did not see a 3000px element: ' + JSON.stringify(probe));
    }
  } catch (e) {
    cell.status = 'infra-failure';
    fail('CELL', tag + ': ' + e.message.split('\n')[0]);
  } finally {
    cells.push(cell);
    await page.close().catch(() => {});
  }
}

// Every top-level header dropdown (nav groups and the user menu) of the longest persona (superadmin), opened one by one at the
// common desktop viewports. A dropdown must show all its items without internal scrolling, inside the viewport, with comfortable rows.
// Regression guard for the "Podešavanja" menu that was one 260px column with its own scrollbar and a cut-off last item.
const MENU_VIEWPORTS = [[1200, 800], [1280, 720], [1280, 800], [1440, 900], [1920, 1080]];
const MENU_SHOTS = { 'Podešavanja': 'settings', 'Administracija': 'administration' };
async function runMenuMatrix(browser, state) {
  const inPageMenu = require('./lib-inpage-menu.cjs');
  const seen = { dropdowns: 0, measured: 0, settingsItems: 0, coarseConfirmed: false };
  // Coarse pointer (touch) is emulated on Chromium only; WebKit has no equivalent switch.
  const pointers = browserName === 'chromium' ? ['fine', 'coarse'] : ['fine'];
  for (const pointer of pointers) {
    for (const theme of THEMES) {
      const ctx = await newThemedContext(browser, theme, state, pointer === 'coarse' ? { hasTouch: true } : {});
      const page = await ctx.newPage();
      try {
        for (const [width, height] of MENU_VIEWPORTS) {
          await page.setViewportSize({ width, height });
          await page.goto(baseUrl + '/account', { waitUntil: 'load', timeout: 30000 });
          await settle(page);
          const count = await page.evaluate(() => { document.querySelectorAll('.a1-header details.a1-nav-dd').forEach((d, i) => d.setAttribute('data-vqa-dd', String(i))); return document.querySelectorAll('[data-vqa-dd]').length; });
          seen.dropdowns = Math.max(seen.dropdowns, count);
          for (let i = 0; i < count; i++) {
            const sel = `[data-vqa-dd="${i}"]`;
            const summary = page.locator(`${sel} > summary`);
            if (pointer === 'coarse') await summary.click(); else await summary.hover();
            await page.waitForFunction((s) => document.querySelector(s)?.open === true, sel, { timeout: 5000 });
            await page.waitForTimeout(350); // hover-intent timers of the previously open menu
            const m = await page.evaluate(inPageMenu, sel);
            const base = { module: 'shell', page: 'shell-menus-open', browser: browserName, width, theme };
            const where = `${pointer} pointer ${width}x${height}`;
            if (m.error) { fail('MENU', `${browserName} ${theme} ${where}: ${m.error}`); continue; }
            seen.measured++;
            if (pointer === 'coarse' && m.coarse) seen.coarseConfirmed = true;
            if (pointer === 'coarse' && !m.coarse) fail('MENU', `${browserName} ${theme} ${where}: pointer:coarse emulation did not apply`);
            if (m.label.startsWith('Podešavanja')) seen.settingsItems = Math.max(seen.settingsItems, m.items);
            const add = (rule, detail) => findings.push({ ...base, rule, count: 1, detail: `${m.label} (${where}): ${detail}`, selectors: [m.label] });
            if (m.items < 1) add('menu-no-items', 'no item rendered');
            if (m.scrollOver > 1) add('menu-internal-scroll', `scrollHeight exceeds clientHeight by ${m.scrollOver}px`);
            if (Object.values(m.edges).some((v) => v > 0)) add('menu-outside-viewport', `panel leaves the viewport (left ${m.edges.left}, top ${m.edges.top}, right ${m.edges.right}, bottom ${m.edges.bottom}px)`);
            if (m.lastHidden > 1) add('menu-last-item-hidden', `last item is cut off by ${m.lastHidden}px`);
            if (m.docOverflow > 1) add('menu-page-overflow', `page scrollWidth exceeds the viewport by ${m.docOverflow}px`);
            if (m.alpha < 0.99) add('menu-panel-see-through', `panel fill alpha ${m.alpha}, page content behind it can show through (need an opaque surface, >= 0.99)`);
            const want = m.coarse ? 44 : 36;
            if (m.minItem < want) add('menu-item-too-small', `smallest item row ${m.minItem}px, need ${want}px`);
            const c = await page.evaluate(inPageContrast, SHELL_SCOPE);
            if (c.out.length) findings.push({ ...base, rule: 'computed-contrast', count: c.out.length, detail: `${m.label} open (${where})`, selectors: c.out.slice(0, 3) });
            if (pointer === 'fine' && width === 1280 && height === 800 && MENU_SHOTS[m.label]) {
              const file = `shell__menu-open-${MENU_SHOTS[m.label]}__${browserName}__${width}__${theme}.png`;
              await page.screenshot({ path: path.join(shotDir, file), animations: 'disabled', caret: 'hide', mask: [page.locator(MASK_SELECTORS.join(','))], maskColor: '#7f7f7f' });
            }
          }
        }
      } catch (e) {
        fail('MENU', `${browserName} ${theme} ${pointer}: ${e.message.split('\n')[0]}`);
      } finally {
        await ctx.close();
      }
    }
  }
  // Floors: the detector must have looked at the real superadmin menus (an empty header would make every assertion pass).
  if (seen.dropdowns < 5) fail('MENU_FLOOR', `only ${seen.dropdowns} header dropdowns found, expected at least 5`);
  if (seen.settingsItems < 12) fail('MENU_FLOOR', `Podešavanja menu had ${seen.settingsItems} items, expected at least 12 for superadmin`);
  if (pointers.includes('coarse') && !seen.coarseConfirmed) fail('MENU_FLOOR', 'no coarse-pointer measurement happened');
  return seen;
}

(async () => {
  const started = Date.now();
  const browser = await playwright[browserName].launch();
  const states = {};
  let menuMatrix = null;
  for (const persona of ['admin', 'customer', 'administrator']) {
    try {
      states[persona] = await login(browser, persona);
    } catch (e) {
      fail('LOGIN', persona + ': ' + e.message.split('\n')[0]);
    }
  }
  if (states.admin) {
    const authCtx = { admin: {}, customer: {}, administrator: {} }; const guestCtx = {};
    for (const theme of THEMES) {
      authCtx.admin[theme] = await newThemedContext(browser, theme, states.admin);
      if (states.customer) authCtx.customer[theme] = await newThemedContext(browser, theme, states.customer);
      if (states.administrator) authCtx.administrator[theme] = await newThemedContext(browser, theme, states.administrator);
      guestCtx[theme] = await newThemedContext(browser, theme);
    }
    for (const pg of PAGES) {
      for (const theme of THEMES) {
        for (const width of pg.widths || WIDTHS) {
          const ctx = pg.guest ? guestCtx[theme] : authCtx[pg.persona || 'admin'][theme];
          if (!ctx) { fail('CELL', `${pg.key} ${browserName} ${width} ${theme}: persona login failed`); continue; }
          await runCell(ctx, pg, width, theme);
        }
      }
      console.log(`done ${pg.module}/${pg.key} (${((Date.now() - started) / 60000).toFixed(1)} min)`);
    }
    try { menuMatrix = await runMenuMatrix(browser, states.admin); } catch (e) { fail('MENU', e.message.split('\n')[0]); }
    for (const c of [...Object.values(authCtx.admin), ...Object.values(authCtx.customer), ...Object.values(authCtx.administrator), ...Object.values(guestCtx)]) await c.close();
  }
  await browser.close();
  const out = {
    browser: browserName, generated_at_ci: new Date().toISOString(), rules: [...AXE_RULES, 'horizontal-overflow', 'console-error', 'failed-request'],
    widths: WIDTHS, themes: THEMES, pages: PAGES.map((p) => ({ module: p.module, key: p.key, widths: p.widths || WIDTHS })),
    infra_failures: infra, selfTests, menuMatrix, foldMetrics, cells, determinism, findings,
  };
  fs.writeFileSync(path.join(artifactDir, `admin-visual-baseline-${browserName}.json`), JSON.stringify(out, null, 1));
  console.log(`ADMIN_VISUAL ${browserName}: cells=${cells.length} findings=${findings.length} infra=${infra.length} minutes=${((Date.now() - started) / 60000).toFixed(1)}`);
  process.exit(infra.length ? 1 : 0);
})().catch((e) => { console.error('FATAL ' + e.stack); process.exit(1); });
