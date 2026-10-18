'use strict';

/*
 * Read-only authenticated Chromium smoke for disposable synthetic Laravel data.
 * Must not point at production. Never clicks product/order submit or EUR sync.
 */
const fs = require('node:fs');
const path = require('node:path');
const process = require('node:process');

const baseUrl = process.env.LIQUID_GLASS_BASE_URL;
const fixturesPath = process.env.LIQUID_GLASS_FIXTURES_PATH;
const artifactDir = process.env.LIQUID_GLASS_ARTIFACT_DIR;
const nodeRoot = process.env.PLAYWRIGHT_NODE_ROOT;
if (
  process.env.LIQUID_GLASS_VISUAL_CI !== '1'
  || baseUrl !== 'http://127.0.0.1:8123'
  || !fixturesPath || !artifactDir || !nodeRoot
) {
  console.error('HOLD: browser smoke is restricted to loopback disposable CI.');
  process.exit(2);
}
const fixture = JSON.parse(fs.readFileSync(fixturesPath, 'utf8'));
if (fixture.sentinel !== 'ALD1N_LIQUID_GLASS_CI_SYNTHETIC_ONLY') {
  console.error('HOLD: invalid disposable fixture sentinel.');
  process.exit(3);
}
const { chromium } = require(path.resolve(nodeRoot, 'node_modules/playwright'));
const WIDTHS = [1440, 1250, 1024, 768, 620, 390, 320];
const MODES = ['dark', 'light', 'auto'];
const failures = [];
const samples = [];
const screenshots = [];
const pageErrors = [];
const artifactName = (name) => name.replace(/[^A-Za-z0-9_.-]/g, '_');
fs.mkdirSync(artifactDir, { recursive: true });

function check(condition, code, detail = '') {
  if (!condition) {
    const error = code + (detail ? ' :: ' + detail : '');
    failures.push(error);
    console.error('FAIL ' + error);
    return false;
  }
  return true;
}
async function snapshot(page, label) {
  const name = artifactName(label) + '.png';
  const target = path.join(artifactDir, name);
  try {
    await page.screenshot({ path: target, fullPage: false, animations: 'disabled', timeout: 10000 });
    screenshots.push(name);
  } catch (e) {
    check(false, 'SCREENSHOT_FAILED', label + ': ' + e.message);
  }
}
async function navigate(page, target, code) {
  const resp = await page.goto(baseUrl + target, { waitUntil: 'domcontentloaded', timeout: 25000 });
  check(resp && resp.status() === 200, code, target + ': HTTP ' + (resp ? resp.status() : 'NULL'));
  await page.waitForTimeout(170);
  return resp;
}
async function login(browser, persona, mode) {
  const opts = {
    viewport: { width: 1440, height: 900 },
    deviceScaleFactor: 1,
    colorScheme: mode === 'auto' ? 'light' : mode,
  };
  const ctx = await browser.newContext(opts);
  await ctx.addInitScript((themeMode) => {
    try {
      window.localStorage.setItem('ald1n-theme-mode', themeMode);
    } catch (e) {
      // Only initial opaque frames may lack storage; the localhost page must accept it.
    }
  }, mode);
  const page = await ctx.newPage();
  page.on('pageerror', (error) => {
    pageErrors.push(persona + '/' + mode + ': ' + error.message);
  });
  await navigate(page, '/login', 'LOGIN_PAGE');
  const credential = fixture[persona];
  await page.locator('input[name="login"]').fill(credential.login);
  await page.locator('input[name="password"]').fill(credential.password);
  await page.locator('.auth-login-submit').click();
  try {
    await page.waitForURL((url) => url.origin === baseUrl && url.pathname === '/', { timeout: 8500 });
  } catch (error) {
    const diagnostic = await page.evaluate(() => ({
      page_path: window.location.pathname,
      title: document.title,
      visible_alerts: [...document.querySelectorAll('.alert,.field-error')]
        .filter((element) => getComputedStyle(element).display !== 'none')
        .map((element) => (element.textContent || '').trim().slice(0, 280)).slice(0, 4),
      has_csrf_input: !!document.querySelector('form.auth-login-form input[name="_token"]'),
      auth_form_visible: !!document.querySelector('.auth-login-form'),
    }));
    await snapshot(page, 'login-failure-'+persona+'-'+mode);
    throw new Error('LOGIN_REDIRECT_FAILED '+persona+'/'+mode+' '+JSON.stringify(diagnostic)+' '+error.message);
  }
  await page.locator('.site-header').waitFor({ state: 'visible', timeout: 15000 });
  return { ctx, page };
}

async function headerGeometry(page, persona, mode, width) {
  await page.setViewportSize({ width, height: width <= 390 ? 780 : 900 });
  await page.waitForTimeout(75);
  await page.waitForFunction(() => document.querySelector('.site-header')?.getAttribute('data-lg-renderer') === 'svg', null, { timeout: 8000 });
  const state = await page.evaluate(() => {
    const selectors = {
      rate: '.site-header .header-rate-persistent',
      cart: '.site-header .header-cart-button',
      search: '.site-header [data-header-product-search-toggle]',
      hamburger: '.site-header [data-mobile-menu-toggle]',
    };
    function box(selector) {
      const element = document.querySelector(selector);
      if (!element) return null;
      const r = element.getBoundingClientRect();
      const css = getComputedStyle(element);
      return {
        left: r.left, right: r.right, top: r.top, bottom: r.bottom,
        width: r.width, height: r.height,
        display: css.display, visibility: css.visibility, opacity: css.opacity,
        text: (element.innerText || element.getAttribute('aria-label') || '').slice(0, 110),
      };
    }
    const all = Object.fromEntries(Object.entries(selectors).map(([key,selector]) => [key, box(selector)]));
    const node = Object.fromEntries(Object.entries(selectors).map(([key,selector]) => [key, document.querySelector(selector)]));
    const domOrder = Object.fromEntries(Object.entries(node).map(([key, el]) => [key, el ? [...document.querySelectorAll('.header-quick-actions > *')].indexOf(el) : -1]));
    return {
      viewport: window.innerWidth,
      htmlWidth: document.documentElement.scrollWidth,
      docTheme: document.documentElement.dataset.theme,
      themeMode: document.documentElement.dataset.themeMode,
      themeBackground: getComputedStyle(document.documentElement).getPropertyValue('--ald-background').trim(),
      exchangeText: (node.rate?.textContent || '').trim().slice(0, 80),
      elements: all,
      domOrder,
      hasDisabledCart: !!document.querySelector('.header-cart-unavailable'),
      headerPosition: getComputedStyle(document.querySelector('.site-header')).position,
      headerGlass: document.querySelector('.site-header')?.getAttribute('data-lg-renderer'),
      glassLayerPresent: !!document.querySelector('.site-header > .lg-layer'),
    };
  });
  const tag = persona + '/' + mode + '/' + width;
  const shouldCart = persona === 'superadmin';
  const visible = (box) => box && box.display !== 'none' && box.visibility !== 'hidden'
    && box.width > 25 && box.height > 25 && box.left >= -1 && box.right <= width + 1
    && box.top >= -1 && box.bottom <= 200;
  for (const key of ['rate', 'search']) {
    check(visible(state.elements[key]), 'HEADER_VISIBLE_' + key.toUpperCase(), tag + ': ' + JSON.stringify(state.elements[key]));
  }
  check(state.exchangeText.includes('EUR/RSD'), 'HEADER_EUR_LABEL', tag);
  if (shouldCart) {
    check(visible(state.elements.cart), 'HEADER_CART_VISIBLE', tag + ': ' + JSON.stringify(state.elements.cart));
    check(state.domOrder.rate < state.domOrder.cart && state.domOrder.cart < state.domOrder.search,
      'HEADER_ORIGINAL_ORDER', tag + ': ' + JSON.stringify(state.domOrder));
  } else {
    check(state.elements.cart === null && !state.hasDisabledCart, 'HEADER_NO_CART_FOR_USER', tag);
  }
  check(state.headerPosition === 'sticky', 'HEADER_STICKY_PRESERVED', tag + ': ' + state.headerPosition);
  check(state.headerGlass === 'svg' && state.glassLayerPresent, 'HEADER_GLASS_REFRACTION_ACTIVE', tag);
  // R4 optical regression: SVG initialization alone does not prove visible glass.
  // The lens must have a textured background to refract, translucent tint, and
  // an actual CSS backdrop filter referencing the registered SVG displacement.
  if (persona === 'superadmin') {
    const optics = await page.evaluate(() => {
      const shell = document.querySelector('.page-shell');
      const glass = document.querySelector('.dashboard-kpi-card.lg-host');
      const layer = glass?.querySelector(':scope > .lg-layer');
      const paint = layer ? getComputedStyle(layer) : null;
      const rgba = paint?.backgroundColor || '';
      const match = rgba.match(/^rgba?\([^)]*,\s*(0(?:\.\d+)?|1(?:\.0+)?)\)$/i);
      return {
        shellBackdrop: shell ? getComputedStyle(shell).backgroundImage : 'none',
        glassSurface: glass ? getComputedStyle(glass).backgroundColor : 'missing',
        tintAlpha: match ? Number(match[1]) : null,
        filter: paint?.backdropFilter || 'none',
        renderer: glass?.getAttribute('data-lg-renderer') || 'none',
      };
    });
    check(optics.shellBackdrop.includes('radial-gradient'), 'R4_COLORED_BACKGROUND_TO_REFRACT', tag+': '+optics.shellBackdrop.slice(0,100));
    check(optics.renderer === 'svg' && optics.filter.includes('url('), 'R4_REAL_FILTER_ACTIVE', tag+': '+optics.filter);
    check(optics.tintAlpha !== null && optics.tintAlpha <= 0.65, 'R4_TRANSLUCENT_GLASS_TINT', tag+': '+JSON.stringify(optics));
  }
  check(state.htmlWidth <= width + 2, 'HEADER_NO_PAGE_X_OVERFLOW',
    tag + ': scrollWidth=' + state.htmlWidth + ' viewport=' + width);
  check(state.themeMode === mode, 'THEME_MODE', tag + ': ' + state.themeMode);
  if (mode !== 'auto') check(state.docTheme === mode, 'THEME_RESOLVED', tag + ': ' + state.docTheme);
  const bg = (mode === 'dark') ? '#13181C' : '#F3F5F6';
  check(state.themeBackground.toUpperCase() === bg.toUpperCase(), 'THEME_MUTED_TOKEN',
    tag + ': ' + state.themeBackground);

  const keys = shouldCart ? ['rate', 'cart', 'search'] : ['rate', 'search'];
  for (let i=0; i<keys.length; i++) {
    for (let j=i+1; j<keys.length; j++) {
      const a = state.elements[keys[i]];
      const b = state.elements[keys[j]];
      if (!a || !b) continue;
      const overlapW = Math.max(0, Math.min(a.right,b.right) - Math.max(a.left,b.left));
      const overlapH = Math.max(0, Math.min(a.bottom,b.bottom) - Math.max(a.top,b.top));
      check(overlapW * overlapH <= 2, 'HEADER_CONTROLS_OVERLAP',
        tag + ': ' + keys[i] + '/' + keys[j] + ': ' + (overlapW*overlapH).toFixed(2));
    }
  }
  samples.push({ persona, mode, width, viewport:state.viewport, measured:state.elements, theme:state.docTheme, scrollWidth:state.htmlWidth });
  return state;
}

async function searchAndMenu(page, label, width) {
  await page.setViewportSize({ width, height: 900 });
  const toggle = page.locator('[data-header-product-search-toggle]');
  await toggle.click();
  const layer = page.locator('[data-header-product-search-layer]');
  await page.waitForFunction(() => document.querySelector('[data-header-product-search-layer]')?.hidden === false, null, { timeout: 7000 });
  check(await layer.isVisible(), 'SEARCH_DIALOG_OPEN', label);
  const searchInput = layer.locator('[data-header-product-search-input]');
  check(await searchInput.isVisible(), 'SEARCH_DIALOG_INPUT', label);
  const dialog = layer.locator('.header-product-search-dialog');
  await page.waitForFunction(() => document.querySelector('.header-product-search-dialog')?.getAttribute('data-lg-renderer') === 'svg', null, { timeout: 8000 });
  check(await dialog.locator(':scope > .lg-layer').count() === 1, 'SEARCH_REAL_GLASS_LAYER', label);
  const b = await dialog.boundingBox();
  check(b && b.x >= -1 && b.x+b.width <= width+1, 'SEARCH_DIALOG_IN_VIEW', label);
  await page.keyboard.press('Escape');
  await page.waitForTimeout(120);
  check(await layer.isHidden(), 'SEARCH_ESCAPE_CLOSE', label);
  if (width <= 1250) {
    const menu = page.locator('[data-mobile-menu-toggle]');
    await menu.click();
    check(await menu.getAttribute('aria-expanded') === 'true', 'HAMBURGER_OPENS', label);
    const hdr = await headerGeometry(page, 'superadmin', label.split('/')[1] || 'dark', width);
    check(hdr.elements.rate !== null && hdr.elements.cart !== null && hdr.elements.search !== null,
      'HEADER_CONTROLS_WITH_MENU', label);
    await menu.click();
    check(await menu.getAttribute('aria-expanded') === 'false', 'HAMBURGER_CLOSES', label);
  }
}

async function archiveSmoke(page, mode, width) {
  await page.setViewportSize({ width, height: width <= 390 ? 780 : 900 });
  await navigate(page, fixture.paths.archived_orders, 'ARCHIVE_PAGE');
  const open = page.locator('[data-order-purge-open]').first();
  check(await open.count() === 1, 'ARCHIVE_OPENER_PRESENT', mode + '/' + width);
  if (await open.count() === 0) return;
  await open.scrollIntoViewIfNeeded();
  await open.click();
  const dialog = page.locator('dialog.order-archive-purge-dialog[open]');
  check(await dialog.count() === 1, 'ARCHIVE_DIALOG_OPEN', mode + '/' + width);
  if (await dialog.count() === 0) return;
  check(await dialog.locator('textarea[name="purge_reason"]').count() === 1, 'ARCHIVE_REASON_FIELD', mode);
  check(await dialog.locator('input[name="confirmation"]').count() === 1, 'ARCHIVE_EXACT_CONFIRMATION', mode);
  const b = await dialog.boundingBox();
  check(b && b.x >= -1 && b.x+b.width <= width+1 && b.y >= -1,
    'ARCHIVE_DIALOG_IN_VIEW', mode + '/' + width + ': ' + JSON.stringify(b));
  const retention = await dialog.locator('.order-archive-purge-retention').evaluate((el) => {
    const rect = el.getBoundingClientRect();
    const style = getComputedStyle(el);
    return { left: rect.left, right: rect.right, marginLeft: style.marginLeft, marginRight: style.marginRight };
  });
  check(
    b && retention.left >= b.x + 12 && retention.right <= b.x + b.width - 12,
    'ARCHIVE_RETENTION_TEXT_INSET',
    mode + '/' + width + ': modal=' + JSON.stringify(b) + ' paragraph=' + JSON.stringify(retention)
  );
  if (mode !== 'auto' && (width === 1440 || width === 320)) await snapshot(page, mode+'-'+width+'-archive-danger-zone');
  await page.keyboard.press('Escape');
  await page.waitForTimeout(110);
  check(await dialog.count() === 0, 'ARCHIVE_ESC_NO_SUBMIT', mode + '/' + width);
  check(new URL(page.url()).pathname === fixture.paths.archived_orders, 'ARCHIVE_NO_NAVIGATION', mode);
  await open.click();
  await page.locator('dialog[open] [data-order-purge-close]').last().click();
  check(await page.locator('dialog.order-archive-purge-dialog[open]').count() === 0,
    'ARCHIVE_CANCEL_NO_SUBMIT', mode + '/' + width);
}

async function editorSmoke(page, mode, width, screen) {
  await page.setViewportSize({ width, height: width <= 390 ? 780 : 900 });
  await navigate(page, fixture.paths[screen], 'EDITOR_' + screen.toUpperCase());
  const form = page.locator('form.liquid-glass-product-editor-form');
  check(await form.count() === 1, 'EDITOR_FORM_EXISTS', mode+'/'+width+'/'+screen);
  if (await form.count() === 0) return;
  const bar = form.locator('[data-product-editor-action-bar]');
  await bar.scrollIntoViewIfNeeded();
  await page.waitForTimeout(95);
  const primary = bar.locator('[data-product-save-primary]');
  const draft = bar.locator('[data-product-save-draft]');
  check(await primary.count() === 1 && await draft.count() === 1, 'EDITOR_SAVE_ACTIONS_PRESENT', screen);
  check(await draft.getAttribute('formnovalidate') !== null, 'EDITOR_DRAFT_UNVALIDATED', screen);
  check(await primary.getAttribute('formnovalidate') === null, 'EDITOR_PRIMARY_VALIDATES', screen);
  check(await page.locator('[data-ux-action-dock]').count() === 0, 'EDITOR_NO_DUPLICATE_MOBILE_DOCK', mode+'/'+width);
  const b = await bar.boundingBox();
  const p = await primary.boundingBox();
  check(b && b.x >= -1 && b.x+b.width <= width + 1, 'EDITOR_BAR_FITS_VIEWPORT', mode+'/'+width+'/'+screen+': '+JSON.stringify(b));
  check(p && p.x>=-1 && p.x+p.width<=width+1 && p.height>=40, 'EDITOR_PRIMARY_FITS', mode+'/'+width+'/'+screen);
  if (screen==='product_edit') {
    const sale = bar.locator('a.product-editor-action-direct');
    check(await sale.count() === 1 && (await sale.getAttribute('href')).includes('#direct-sale-title'),
      'EDITOR_DIRECT_SALE_REMAINS_LINK', mode+'/'+width);
  } else {
    check(await bar.locator('.product-editor-action-direct').count() === 0, 'EDITOR_CREATE_NO_DIRECT_SALE', mode+'/'+width);
  }
  if (mode !== 'auto' && (width===1440 || width===320)) await snapshot(page, mode+'-'+width+'-'+screen+'-sticky-actions');
}

async function main() {
  const browser = await chromium.launch({ headless:true, args:['--disable-dev-shm-usage'] });
  try {
    for (const mode of MODES) {
      for (const persona of ['superadmin','user']) {
        const {ctx,page}=await login(browser,persona,mode);
        try {
          for (const width of WIDTHS) {
            await headerGeometry(page,persona,mode,width);
            if (persona==='superadmin' && (mode!=='auto' || width===320 || width===1440)) {
              await snapshot(page,mode+'-'+width+'-'+persona+'-dashboard');
            }
          }
          if (persona==='superadmin') {
            for (const width of [1440,320]) {
              await searchAndMenu(page,persona+'/'+mode+'/'+width,width);
            }
            for (const width of [1440,620,320]) {
              await archiveSmoke(page,mode,width);
              await editorSmoke(page,mode,width,'product_create');
              await editorSmoke(page,mode,width,'product_edit');
            }
          } else {
            await page.setViewportSize({width:320,height:780});
            await navigate(page,fixture.paths.dashboard,'USER_DASHBOARD');
            check(await page.locator('.header-cart-button').count() === 0,'USER_CART_ABSENT_AFTER_NAV',mode);
            if (mode!=='auto') await snapshot(page,mode+'-320-user-no-cart');
          }
        } catch(e) {
          check(false,'BROWSER_PERSONA_ERROR',persona+'/'+mode+': '+e.message);
          await snapshot(page,'failure-'+persona+'-'+mode);
        } finally {
          await ctx.close();
        }
      }
    }
  } finally {
    await browser.close();
  }
}

(async () => {
  try {
    await main();
  } catch(e) {
    check(false,'BROWSER_FATAL',e && e.stack ? e.stack : String(e));
  }
  for (const e of pageErrors) check(false,'BROWSER_JAVASCRIPT_EXCEPTION',e);
  const summary={
    authority:'ISOLATED_BROWSER_ONLY',
    source_sha:process.env.GITHUB_SHA||'UNVERIFIED',
    base_url:baseUrl,
    viewports:WIDTHS,
    modes:MODES,
    personas:['superadmin','user'],
    inspected_headers:samples.length,
    screenshot_count:screenshots.length,
    screenshots,
    failed_count:failures.length,
    failures,
    measured:samples,
  };
  fs.writeFileSync(path.join(artifactDir,'visual-report.json'), JSON.stringify(summary,null,2)+'\n');
  console.log('LIQUID_GLASS_VISUAL_BROWSER='+ (failures.length?'FAIL':'PASS')
    +' headers='+samples.length+' screenshots='+screenshots.length+' failed='+failures.length);
  process.exitCode=failures.length?1:0;
})();
