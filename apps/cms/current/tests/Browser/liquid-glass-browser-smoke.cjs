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
  await page.locator('.a1-header').waitFor({ state: 'visible', timeout: 15000 });
  return { ctx, page };
}

async function headerGeometry(page, persona, mode, width) {
  await page.setViewportSize({ width, height: width <= 390 ? 780 : 900 });
  await page.waitForTimeout(75);
  const state = await page.evaluate(() => {
    const selectors = {
      rate: '.a1-header .a1-rate',
      cart: '.a1-header a[aria-label^="Korpa"]',
      search: '.a1-header [data-header-product-search-toggle]',
      hamburger: '.a1-header [data-mobile-menu-toggle]',
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
    const domOrder = Object.fromEntries(Object.entries(node).map(([key, el]) => [key, el ? [...document.querySelectorAll('.a1-header *')].indexOf(el) : -1]));
    return {
      viewport: window.innerWidth,
      htmlWidth: document.documentElement.scrollWidth,
      docTheme: document.documentElement.dataset.theme,
      themeMode: document.documentElement.dataset.themeMode,
      themeBackground: getComputedStyle(document.documentElement).getPropertyValue('--a1-surface-page').trim(),
      exchangeText: (node.rate?.textContent || '').trim().slice(0, 80),
      elements: all,
      domOrder,
      hasDisabledCart: !!document.querySelector('.header-cart-unavailable'),
      headerPosition: getComputedStyle(document.querySelector('.a1-header')).position,
      headerTop: document.querySelector('.a1-header').getBoundingClientRect().top,
      r5: (() => {
        const st = getComputedStyle(document.documentElement);
        const shell = document.querySelector('.page-shell');
        const aurora = shell ? getComputedStyle(shell, '::before') : null;
        // G7 PR B: read the Kobalt tokens the old --lg5-* aliases pointed at (same resolved values), so deleting the compat layer cannot break this check.
        const paletteTokens = {blue:'--a1-button-primary-bg',violet:'--a1-accent-link',cyan:'--a1-status-info-dot',teal:'--a1-status-ok-dot',amber:'--a1-status-warning-dot',rose:'--a1-status-danger-dot'};
        const palette = Object.fromEntries(Object.entries(paletteTokens).map(([k, token]) =>
          [k, st.getPropertyValue(token).trim().toUpperCase()]
        ));
        const color = (name) => st.getPropertyValue(name).trim().toUpperCase();
        function luminosity(hex) {
          if (!/^#[0-9A-F]{6}$/.test(hex)) return null;
          const rgb=hex.slice(1).match(/../g).map(n=>parseInt(n,16)/255).map(v=>
            v <= 0.04045 ? v / 12.92 : Math.pow((v+0.055)/1.055,2.4)
          );
          return rgb[0]*0.2126 + rgb[1]*0.7152 + rgb[2]*0.0722;
        }
        function contrast(a,b){
          const x=luminosity(a),y=luminosity(b);
          return x===null||y===null ? 0 : (Math.max(x,y)+.05)/(Math.min(x,y)+.05);
        }
        return {
          palette,
          bloomOpacity: aurora?.opacity || null,
          bloomImage: aurora?.backgroundImage || 'none',
          // --ald-text/--ald-surface and --ald-on-primary/--ald-primary resolve to these tokens; the WCAG AA gates are unchanged.
          textContrast: contrast(color('--a1-text-primary'),color('--a1-surface-solid')),
          primaryContrast: contrast(color('--a1-surface-page'),color('--a1-accent-link')),
        };
      })(),
    };
  });
  const tag = persona + '/' + mode + '/' + width;
  // The operator is not a shopper; the separate buying fixture retains cart coverage.
  const shouldCart = persona === 'buyer';
  const visible = (box) => box && box.display !== 'none' && box.visibility !== 'hidden'
    && box.width > 25 && box.height > 25 && box.left >= -1 && box.right <= width + 1
    && box.top >= -1 && box.bottom <= 200;
  // The rate badge lives in the bar from 769px and inside the sheet menu at or below 768px.
  const phone = width <= 1199;
  check(visible(state.elements.search), 'HEADER_VISIBLE_SEARCH', tag + ': ' + JSON.stringify(state.elements.search));
  if (phone) {
    check(visible(state.elements.hamburger), 'HEADER_VISIBLE_MENU_TOGGLE', tag + ': ' + JSON.stringify(state.elements.hamburger));
  } else {
    check(visible(state.elements.rate), 'HEADER_VISIBLE_RATE', tag + ': ' + JSON.stringify(state.elements.rate));
    check(state.exchangeText.includes('EUR/RSD'), 'HEADER_EUR_LABEL', tag);
    check(state.elements.hamburger === null || state.elements.hamburger.display === 'none', 'HEADER_NO_MENU_TOGGLE_ON_DESKTOP', tag);
  }
  if (shouldCart) {
    check(visible(state.elements.cart), 'HEADER_CART_VISIBLE', tag + ': ' + JSON.stringify(state.elements.cart));
    check(state.domOrder.search < state.domOrder.cart, 'HEADER_SEARCH_BEFORE_CART', tag + ': ' + JSON.stringify(state.domOrder));
  } else {
    check(state.elements.cart === null && !state.hasDisabledCart, 'HEADER_NO_CART_FOR_NON_BUYING_PERSONA', tag);
  }
  check(state.headerPosition === 'sticky', 'HEADER_STICKY_PRESERVED', tag + ': ' + state.headerPosition);
  check(Math.abs(state.headerTop) <= 1, 'HEADER_PINNED_TO_TOP', tag + ': ' + state.headerTop);
  // R4 optical regression: SVG initialization alone does not prove visible glass.
  // The lens must have viewport-wide textured paint to refract (the narrow
  // page-shell must remain transparent to avoid visible rectangular seams),
  // translucent tint, and
  // an actual CSS backdrop filter referencing the registered SVG displacement.
  if (persona === 'superadmin') {
    // Module 2: the dashboard no longer uses the SVG lens (liquid-glass-r3.js); its cards are token glass with a near-opaque fill under text.
    const optics = await page.evaluate(() => {
      const shell = document.querySelector('.page-shell');
      const card = document.querySelector('.a1-dash .a1-card');
      const rgba = card ? getComputedStyle(card).backgroundColor : '';
      const match = rgba.match(/^rgba?\([^)]*,\s*(0(?:\.\d+)?|1(?:\.0+)?)\)$/i);
      return {
        bodyBackdrop: getComputedStyle(document.body).backgroundImage,
        shellBackdrop: shell ? getComputedStyle(shell).backgroundImage : 'none',
        cardFill: card ? rgba : 'missing',
        fillAlpha: card ? (match ? Number(match[1]) : 1) : null,
        lensHosts: document.querySelectorAll('.a1-dash .lg-host').length,
      };
    });
    check(optics.bodyBackdrop.includes('radial-gradient') && optics.shellBackdrop === 'none',
      'R4_VIEWPORT_BACKGROUND_NO_RECTANGLE',
      tag+': body='+optics.bodyBackdrop.slice(0,110)+' shell='+optics.shellBackdrop.slice(0,60));
    // Card text contrast is asserted by the strict admin visual QA (computed contrast on .a1-dash, light and dark, both engines); a bare alpha check here could only fail on an explicit lower alpha, so it was dropped.
    check(optics.fillAlpha !== null, 'M2_DASHBOARD_CARD_PRESENT', tag+': '+JSON.stringify(optics));
    check(optics.lensHosts === 0, 'M2_DASHBOARD_HAS_NO_SVG_LENS', tag+': '+JSON.stringify(optics));
  }
  check(state.htmlWidth <= width + 2, 'HEADER_NO_PAGE_X_OVERFLOW',
    tag + ': scrollWidth=' + state.htmlWidth + ' viewport=' + width);
  check(state.themeMode === mode, 'THEME_MODE', tag + ': ' + state.themeMode);
  if (mode !== 'auto') check(state.docTheme === mode, 'THEME_RESOLVED', tag + ': ' + state.docTheme);
  // Kobalt G0 (23.11.2026) / G7 PR B: this reads the --a1-* tokens directly; the --ald-* / --lg5-* alias layer is no longer needed here.
  const dark = state.docTheme === 'dark';
  const bg = dark ? '#0D1216' : '#EDF0F4';
  check(state.themeBackground.toUpperCase() === bg, 'R5_THEME_BACKGROUND',
    tag + ': ' + state.themeBackground);
  for (const [name,expected] of Object.entries(dark ? {
    blue:'#3557C7',violet:'#A9C3FF',cyan:'#4FB7A3',teal:'#5FE29A',amber:'#E0B45C',rose:'#D7788F'
  } : {
    blue:'#2D50BF',violet:'#2546AD',cyan:'#2F9E88',teal:'#16A34A',amber:'#C58A1A',rose:'#C4415F'
  })) check(state.r5.palette[name] === expected, 'R5_FUNCTIONAL_COLOR_'+name.toUpperCase(), tag+': '+state.r5.palette[name]);
  // Module 2 retired R5_AMBIENT_BLOBS_40_PERCENT_REDUCTION (the old dashboard aurora on .page-shell::before) and
  // R5_KPI_VIOLET_SEMANTIC_ROLE (old KPI tiles): the dashboard paints the Kobalt page glows on the body
  // (checked as R4_VIEWPORT_BACKGROUND_NO_RECTANGLE below) and its KPI tiles read Kobalt tokens.
  check(state.r5.textContrast >= 4.5, 'R5_WCAG_AA_SURFACE_TEXT',
    tag+': '+state.r5.textContrast.toFixed(2));
  check(state.r5.primaryContrast >= 4.5, 'R5_WCAG_AA_PRIMARY_ACTION',
    tag+': '+state.r5.primaryContrast.toFixed(2));

  const keys = (phone ? [] : ['rate']).concat(shouldCart ? ['cart', 'search'] : ['search']);
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

async function mobileSheetSmoke(page, label, width) {
  // Sheet menu (<= 768px): opaque reading surface, controls inside the viewport, no horizontal scroll,
  // theme switch and currency choice reachable, dropdown groups expand in the same scroll plane.
  const pre = await page.evaluate(() => {
    const header = document.querySelector('.a1-header');
    const panel = document.querySelector('[data-mobile-menu]');
    const bounds = (node) => {
      if (!node) return null;
      const rect = node.getBoundingClientRect();
      return { left: rect.left, right: rect.right, top: rect.top, bottom: rect.bottom, width: rect.width };
    };
    const alpha = (node) => {
      if (!node) return 0;
      const bg = getComputedStyle(node).backgroundColor;
      if (bg === 'transparent') return 0;
      const match = bg.match(/^rgba?\(\s*[\d.]+\s*,\s*[\d.]+\s*,\s*[\d.]+(?:\s*,\s*([\d.]+))?\s*\)$/);
      return match ? Number(match[1] ?? '1') : 1;
    };
    return {
      // Owner feedback 2026-11-16: the theme switch left the drawer (icon button in the bar), the account menu is the avatar button
      // in the bar (left of the hamburger), the rate is a card above the menu items inside the drawer.
      panel: bounds(panel), nav: bounds(panel?.querySelector('.a1-header__nav')), themeInDrawer: bounds(panel?.querySelector('.a1-theme')),
      theme: bounds(header?.querySelector('[data-theme-cycle]')),
      rate: bounds(panel?.querySelector('.a1-drawer-rate')), user: bounds(header?.querySelector('.a1-header__user > summary')),
      panelScrollWidth: panel?.scrollWidth || 0, panelClientWidth: panel?.clientWidth || 0,
      opaqueHeader: alpha(header),
      locked: document.documentElement.classList.contains('a1-lock'),
      pageInert: !!document.querySelector('main[data-a1-page]')?.inert,
      scrimShown: !!document.querySelector('[data-a1-scrim]') && !document.querySelector('[data-a1-scrim]').hidden,
      overflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
    };
  });
  const tag = label + '/' + width;
  const fits = (r) => r && r.width > 0 && r.left >= -1 && r.right <= width + 1;
  check(pre.panel && pre.nav && pre.theme && pre.rate && pre.user, 'MOBILE_SHEET_PARTS_EXIST', tag + ' ' + JSON.stringify(pre));
  check(['panel', 'nav', 'theme', 'rate', 'user'].every((key) => fits(pre[key])), 'MOBILE_SHEET_IN_VIEWPORT', tag + ' ' + JSON.stringify(pre));
  check(pre.panelScrollWidth <= pre.panelClientWidth + 2 && pre.overflow <= 1, 'MOBILE_SHEET_NO_X_SCROLL', tag + ' ' + JSON.stringify(pre));
  check(pre.opaqueHeader >= .99, 'MOBILE_SHEET_OPAQUE_READING_SURFACE', tag + ' ' + JSON.stringify(pre));
  check(pre.locked && pre.pageInert && pre.scrimShown, 'MOBILE_SHEET_SCROLL_LOCK_INERT_SCRIM', tag + ' ' + JSON.stringify(pre));
  check(pre.themeInDrawer === null || pre.themeInDrawer.width === 0, 'MOBILE_SHEET_NO_THEME_SWITCH', tag + ' ' + JSON.stringify(pre.themeInDrawer));
  check(pre.rate && pre.nav && pre.rate.bottom <= pre.nav.top + 1, 'MOBILE_SHEET_RATE_ABOVE_MENU', tag + ' ' + JSON.stringify({ rate: pre.rate, nav: pre.nav }));
  check(await page.locator('[data-theme-cycle]').isVisible(), 'MOBILE_THEME_CYCLE_BUTTON', tag);
  const firstGroup = page.locator('[data-mobile-menu] details.a1-nav-dd').first();
  if (await firstGroup.count()) {
    await firstGroup.locator(':scope > summary').click();
    const group = await firstGroup.locator(':scope > .a1-nav-dd__panel').evaluate((el) => ({
      position: getComputedStyle(el).position, left: el.getBoundingClientRect().left, right: el.getBoundingClientRect().right,
    }));
    check(group.position === 'static' && group.left >= -1 && group.right <= width + 1, 'MOBILE_DROPDOWN_IN_FLOW', tag + ' ' + JSON.stringify(group));
    await firstGroup.locator(':scope > summary').click();
  }
  if (width === 320 || width === 390) await snapshot(page, 'mobile-sheet-' + label.replace(/\//g, '-') + '-' + width);
}

async function searchAndMenu(page, label, width) {
  await page.setViewportSize({ width, height: 900 });
  const toggle = page.locator('[data-header-product-search-toggle]');
  await toggle.click();
  const layer = page.locator('[data-header-product-search-layer]');
  await page.waitForFunction(() => document.querySelector('[data-header-product-search-layer]')?.open === true, null, { timeout: 7000 });
  check(await layer.isVisible(), 'SEARCH_DIALOG_OPEN', label);
  check(await toggle.getAttribute('aria-expanded') === 'true', 'SEARCH_TOGGLE_EXPANDED', label);
  const searchInput = layer.locator('[data-header-product-search-input]');
  check(await searchInput.isVisible(), 'SEARCH_DIALOG_INPUT', label);
  check(await page.evaluate(() => document.activeElement?.hasAttribute('data-header-product-search-input')), 'SEARCH_INPUT_FOCUSED', label);
  const b = await layer.boundingBox();
  check(b && b.x >= -1 && b.x + b.width <= width + 1, 'SEARCH_DIALOG_IN_VIEW', label);
  await page.keyboard.press('Escape');
  await page.waitForTimeout(120);
  check(await layer.isHidden(), 'SEARCH_ESCAPE_CLOSE', label);
  check(await page.evaluate(() => document.activeElement?.hasAttribute('data-header-product-search-toggle')), 'SEARCH_FOCUS_RETURNS_TO_TOGGLE', label);
  if (width <= 1199) {
    const menu = page.locator('[data-mobile-menu-toggle]');
    await menu.click();
    check(await menu.getAttribute('aria-expanded') === 'true', 'HAMBURGER_OPENS', label);
    await mobileSheetSmoke(page, label, width);
    await page.keyboard.press('Escape');
    await page.waitForTimeout(120);
    check(await menu.getAttribute('aria-expanded') === 'false', 'MENU_ESCAPE_CLOSES', label);
    check(await page.evaluate(() => document.activeElement?.hasAttribute('data-mobile-menu-toggle')), 'MENU_FOCUS_RETURNS_TO_TOGGLE', label);
    check(await page.evaluate(() => !document.documentElement.classList.contains('a1-lock')), 'MENU_SCROLL_LOCK_RELEASED', label);
    await menu.click();
    check(await menu.getAttribute('aria-expanded') === 'true', 'HAMBURGER_REOPENS', label);
    await menu.click();
    check(await menu.getAttribute('aria-expanded') === 'false', 'HAMBURGER_CLOSES', label);
  } else {
    const first = page.locator('.a1-header__nav details.a1-nav-dd > summary').first();
    // Pointer: hover opens the group; a click on the hover-opened summary closes it again (no pinning).
    await first.hover();
    check(await page.locator('.a1-header__nav details.a1-nav-dd[open]').count() === 1, 'DROPDOWN_OPENS_ON_HOVER', label);
    await first.click();
    check(await page.locator('.a1-header__nav details.a1-nav-dd[open]').count() === 0, 'DROPDOWN_CLICK_CLOSES_HOVER_OPENED', label);
    await first.click();
    check(await page.locator('.a1-header__nav details.a1-nav-dd[open]').count() === 1, 'DROPDOWN_OPENS', label);
    await page.keyboard.press('Escape');
    check(await page.locator('.a1-header__nav details.a1-nav-dd[open]').count() === 0, 'DROPDOWN_ESCAPE_CLOSES', label);
    const originalMode = await page.evaluate(() => document.documentElement.dataset.themeMode);
    const dark = page.locator('[data-theme-mode-option="dark"]');
    await dark.click();
    check(await page.evaluate(() => document.documentElement.dataset.theme === 'dark' && document.documentElement.dataset.themeMode === 'dark'), 'THEME_SWITCH_APPLIES_IMMEDIATELY', label);
    check(await dark.getAttribute('aria-pressed') === 'true', 'THEME_SWITCH_PRESSED', label);
    await page.locator('[data-theme-mode-option="' + originalMode + '"]').click();
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
  const dialog = page.locator('dialog[data-order-permanent-delete][open]');
  check(await dialog.count() === 1, 'ARCHIVE_DIALOG_OPEN', mode + '/' + width);
  if (await dialog.count() === 0) return;
  check(await dialog.locator('textarea[name="purge_reason"]').count() === 1, 'ARCHIVE_REASON_FIELD', mode);
  check(await dialog.locator('input[name="confirmation"]').count() === 1, 'ARCHIVE_EXACT_CONFIRMATION', mode);
  const b = await dialog.boundingBox();
  check(b && b.x >= -1 && b.x+b.width <= width+1 && b.y >= -1,
    'ARCHIVE_DIALOG_IN_VIEW', mode + '/' + width + ': ' + JSON.stringify(b));
  const retention = await dialog.locator('[data-order-purge-retention]').evaluate((el) => {
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
  check(await page.locator('dialog[data-order-permanent-delete][open]').count() === 0,
    'ARCHIVE_CANCEL_NO_SUBMIT', mode + '/' + width);
}

async function editorSmoke(page, mode, width, screen) {
  await page.setViewportSize({ width, height: width <= 390 ? 780 : 900 });
  await navigate(page, fixture.paths[screen], 'EDITOR_' + screen.toUpperCase());
  const form = page.locator('form[data-product-editor-form]');
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
    const sale = bar.locator('a[data-product-direct-sale]');
    check(await sale.count() === 1 && (await sale.getAttribute('href')).includes('#direct-sale-title'),
      'EDITOR_DIRECT_SALE_REMAINS_LINK', mode+'/'+width);
  } else {
    check(await bar.locator('[data-product-direct-sale]').count() === 0, 'EDITOR_CREATE_NO_DIRECT_SALE', mode+'/'+width);
  }
  if (mode !== 'auto' && (width===1440 || width===320)) await snapshot(page, mode+'-'+width+'-'+screen+'-sticky-actions');
}

async function dashboardD1Smoke(page, persona, mode) {
  const label = persona + '/' + mode;
  if (persona === 'superadmin') {
    // Module 2: the first look shows numbers. At 1440x900 and 1280x800 the whole priorities strip and the entire first
    // KPI row (Prihod, Bruto dobit, Neto doprinos) are above the fold; at 1440x900 all six are checked too.
    for (const [w, h] of [[1440, 900], [1280, 800]]) {
      await page.setViewportSize({width:w,height:h});
      await navigate(page,fixture.paths.dashboard,'D1_DESKTOP_FOLD_'+w);
      // Owner decision 2026-11-16: greeting, period bar, then the KPI number cards first; priorities and charts follow below the cards.
      const earnings = await page.locator('[data-dashboard-earnings]').boundingBox();
      check(!!earnings && earnings.y + earnings.height <= h, 'D1_EARNINGS_ROW_ABOVE_FOLD_'+w, label+': '+JSON.stringify(earnings));
    }
  }
  await page.setViewportSize({width:390,height:844});
  await navigate(page,fixture.paths.dashboard,'D1_DASHBOARD');
  if (persona === 'superadmin') {
    const positions = await page.evaluate(() => ({
      hero: document.querySelector('.a1-dash .a1-hero')?.getBoundingClientRect().top,
      focus: document.querySelector('.a1-dash [aria-labelledby="d1-priority-heading"]')?.getBoundingClientRect().top,
    }));
    check(Number.isFinite(positions.hero) && Number.isFinite(positions.focus) && positions.hero < positions.focus,
      'D1_MOBILE_HERO_BEFORE_FOCUS', label+': '+JSON.stringify(positions));
  }
  const count = await page.locator('[data-dashboard-d1-kpi]').count();
  if (persona !== 'superadmin') {
    check(count === 0, 'D1_RBAC_NO_SUPERADMIN_KPI', label+': '+count);
    return;
  }
  // Owner decision 2026-11-16: four earnings cards replace Prihod / Bruto dobit / Neto doprinos; three stock tiles remain in one row.
  check(count === 3, 'D1_THREE_STOCK_TILES', label+': '+count);
  const names = await page.locator('[data-dashboard-d1-kpi]').evaluateAll(nodes=>nodes.map(n=>n.dataset.dashboardD1Kpi));
  check(names.join(',') === 'receivables,purchase,sale',
    'D1_STOCK_TILE_ORDER', label+': '+names.join(','));
  check(await page.locator('[data-dashboard-period="default"]').count() === 1,
    'D1_DEFAULT_MTD', label);
  check((await page.locator('[data-dashboard-earnings-kpi]').count()) === 4,
    'D1_FOUR_EARNINGS_CARDS', label);
  const defaultChip = page.locator('[data-dashboard-period] .a1-seg__opt[aria-current="true"]');
  check(await defaultChip.count() === 1 && (await defaultChip.innerText()).trim() === 'Ovaj mesec',
    'D1_DEFAULT_CHIP_ACTIVE', label);
  check(await page.locator('[data-dashboard-period] input[type="date"]').count() === 2,
    'D1_CUSTOM_DATE_INPUTS', label);
  const defaultFrom = await page.locator('[data-dashboard-period] input[name="date_from"]').inputValue();
  const defaultTo = await page.locator('[data-dashboard-period] input[name="date_to"]').inputValue();
  check(/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/.test(defaultTo) && defaultFrom === defaultTo.slice(0, 7) + '-01',
    'D1_DEFAULT_MONTH_START_TO_TODAY', label+': '+defaultFrom+' to '+defaultTo);

  await navigate(page,'/?period=30d','D1_30D_GET');
  check(await page.locator('[data-dashboard-period="30d"]').count() === 1
    && (await page.locator('[data-dashboard-period] .a1-seg__opt[aria-current="true"]').innerText()).trim() === '30 dana',
    'D1_ROLLING_30D_SELECTED', label);
  await navigate(page,'/?period=default','D1_EXPLICIT_DEFAULT_GET');
  check(await page.locator('[data-dashboard-period="default"]').count() === 1,
    'D1_EXPLICIT_DEFAULT_SELECTED', label);
  await navigate(page,'/?period=7d','D1_7D_GET');
  check(await page.locator('[data-dashboard-period="7d"]').count() === 1,
    'D1_7D_SELECTED', label);
  check(await page.locator('[data-dashboard-period-label]').count() === 1,
    'D1_7D_PERIOD_LABEL', label);
  await navigate(page,'/?period=custom&date_from=2026-09-01&date_to=2026-09-30','D1_CUSTOM_GET');
  check(await page.locator('[data-dashboard-period="custom"] input[name="date_from"]').inputValue() === '2026-09-01',
    'D1_CUSTOM_DATE_FROM',label);
  check(await page.locator('[data-dashboard-period="custom"] input[name="date_to"]').inputValue() === '2026-09-30',
    'D1_CUSTOM_DATE_TO',label);
  await navigate(page,'/?period=custom&date_from=2026-02-30&date_to=2026-03-01','D1_INVALID_GET');
  check(await page.locator('[data-dashboard-period-error][role="alert"]').count() === 1,
    'D1_INVALID_VISIBLE_ERROR', label);
  check((await page.locator('[data-dashboard-earnings]').count()) === 0,
    'D1_INVALID_NOT_A_FINANCIAL_ZERO',label);
  const width=await page.evaluate(()=>document.documentElement.scrollWidth - document.documentElement.clientWidth);
  check(width <= 1, 'D1_390_NO_OVERFLOW',label+': '+width);
  await navigate(page,fixture.paths.dashboard,'D1_RESTORE_DASHBOARD');
}

async function retirementUserCardLayoutSmoke(page, mode, width) {
  const label = 'superadmin/' + mode + '/' + width;
  await page.setViewportSize({ width, height: width <= 390 ? 780 : 900 });
  await navigate(page, '/admin/users', 'RETIREMENT_USER_LIST_GET');
  const state = await page.evaluate(() => {
    const card = document.querySelector('[data-access-user]:has(> [data-user-danger-zone] > a[href$="/retirement"])');
    if (!card) return null;
    const head = card.querySelector(':scope > [data-user-head]');
    const danger = card.querySelector(':scope > [data-user-danger-zone]');
    function rect(el) {
      if (!el) return null;
      const r=el.getBoundingClientRect();
      return { x:r.x, y:r.y, left:r.left, right:r.right, top:r.top, bottom:r.bottom, width:r.width, height:r.height };
    }
    const identity = head?.querySelector(':scope > [data-user-identity]');
    const badges = head?.querySelector(':scope > [data-user-badges]');
    const action = danger?.querySelector(':scope > a[href$="/retirement"]');
    const edit = card.querySelector(':scope > [data-user-edit]');
    return {
      head: rect(head), identity: rect(identity), badges: rect(badges), action: rect(action),
      danger: rect(danger), edit: rect(edit),
      actionOutsideForm: !!action && !action.closest('form'),
      headerActions: head?.querySelectorAll('a[href$="/retirement"]').length ?? -1,
      actionCount: card.querySelectorAll('a[href$="/retirement"]').length,
      label: action?.textContent.trim() || '', href: action?.getAttribute('href') || '',
      viewport:document.documentElement.clientWidth, scrollWidth:document.documentElement.scrollWidth,
    };
  });
  check(!!state, 'RETIREMENT_USER_CARD_WITH_ACTION', label);
  if (!state) return;
  const {head,identity,badges,action,danger,edit}=state;
  check(!!head && !!identity && !!badges && !!action && !!danger && !!edit, 'RETIREMENT_IDENTITY_AND_DANGER_ZONE_PRESENT', label);
  if (!head || !identity || !badges || !action || !danger || !edit) return;
  const previewUrl = new URL(state.href, baseUrl);
  check(state.label === 'Ukloni korisnika' && previewUrl.origin === baseUrl
    && /^\/admin\/users\/[0-9]+\/retirement$/.test(previewUrl.pathname),
    'RETIREMENT_ACTION_IS_SAFE_PREVIEW_LINK', label);
  check(state.actionCount === 1 && state.headerActions === 0 && state.actionOutsideForm,
    'RETIREMENT_ONE_ACTION_SEPARATE_FROM_SAVE', label);
  check(action.width <= 240 && action.height >= 34 && action.left >= danger.left-1 && action.right <= danger.right+1
    && action.top >= danger.top-1 && action.bottom <= danger.bottom+1,
    'RETIREMENT_ACTION_COMPACT_AND_IN_BOUNDS', label + ': ' + JSON.stringify(state).slice(0,900));
  check(danger.top >= edit.bottom-1 && danger.top >= head.bottom-1,
    'RETIREMENT_DANGER_ZONE_AFTER_EDITOR', label + ': ' + JSON.stringify(state).slice(0,900));
  if (width > 600) {
    check(identity.left + 25 < badges.left && identity.top <= badges.bottom && badges.top <= identity.bottom,
      'RETIREMENT_DESKTOP_IDENTITY_BADGES_ORDER', label + ': ' + JSON.stringify(state).slice(0,900));
  } else {
    check(identity.top < badges.top && badges.bottom <= danger.top+1,
      'RETIREMENT_MOBILE_IDENTITY_BADGES_DANGER_STACK', label + ': ' + JSON.stringify(state).slice(0,900));
  }
  check(state.scrollWidth <= state.viewport + 2,
    'RETIREMENT_USER_LIST_NO_HORIZONTAL_OVERFLOW', label + ': ' + JSON.stringify(state).slice(0,900));
  if (mode !== 'auto' && [1440,320].includes(width))
    await snapshot(page, 'retirement-user-list-' + mode + '-' + width);
  // Read-only UI smoke: DO NOT submit the linked confirmation form or change any users.
}

async function retirementPreviewSmoke(page, persona, mode, width) {
  const label = persona + '/' + mode + '/' + width;
  await page.setViewportSize({ width, height: width <= 390 ? 780 : 900 });
  const url = fixture.paths.retirement_preview;
  check(typeof url === 'string' && /^\/admin\/users\/[0-9]+\/retirement$/.test(url),
    'RETIREMENT_DISPOSABLE_PREVIEW_URL', label);
  if (persona !== 'superadmin') {
    const response = await page.goto(baseUrl + url, { waitUntil: 'domcontentloaded', timeout: 25000 });
    check(response && response.status() === 403, 'RETIREMENT_NON_SUPERADMIN_FORBIDDEN', label);
    check(await page.locator('input[name="confirmation"]').count() === 0,
      'RETIREMENT_NON_SUPERADMIN_NO_FORM', label);
    return;
  }
  await navigate(page, url, 'RETIREMENT_SUPERADMIN_PREVIEW');
  check(await page.getByRole('heading', { name: 'Bezbedno uklanjanje naloga' }).count() === 1,
    'RETIREMENT_PREVIEW_HEADING', label);
  check(await page.getByRole('heading', { name: 'Istorija koja ostaje nepromenjena' }).count() === 1,
    'RETIREMENT_PRESERVED_HISTORY', label);
  check(await page.getByRole('heading', { name: 'Aktivna zaduženja koja se prenose na SuperAdministratora' }).count() === 1,
    'RETIREMENT_ACTIVE_WORK_TRANSFER', label);
  check((await page.locator('p[role="status"]').innerText()).includes('121 stranih ključeva')
    && (await page.locator('p[role="status"]').innerText()).includes('7 istorijskih kolona'),
    'RETIREMENT_EXACT_SCHEMA_PREVIEW', label);
  const confirm = page.locator('input[name="confirmation"]');
  const form = page.locator('form[action$="/retirement"]');
  check(await form.count() === 1 && await confirm.count() === 1,
    'RETIREMENT_ONE_CONFIRMATION_FORM', label);
  check(await confirm.getAttribute('required') !== null
    && await confirm.getAttribute('pattern') === 'OBRISI',
    'RETIREMENT_EXACT_CONFIRMATION_CONTRACT', label);
  await confirm.fill('obrisi');
  check(!(await confirm.evaluate(el => el.checkValidity())),
    'RETIREMENT_LOWERCASE_REJECTED', label);
  await confirm.fill('OBRISI');
  check(await confirm.evaluate(el => el.checkValidity()),
    'RETIREMENT_EXACT_PHRASE_ACCEPTED_NO_SUBMIT', label);
  const dimensions = await page.evaluate(() => ({
    viewport: document.documentElement.clientWidth,
    content: document.documentElement.scrollWidth,
  }));
  check(dimensions.content <= width + 2,
    'RETIREMENT_NO_HORIZONTAL_OVERFLOW', label + ': ' + JSON.stringify(dimensions));
  if (mode !== 'auto' && [1440, 320].includes(width))
    await snapshot(page, 'retirement-preview-' + mode + '-' + width);
  // Deliberately NEVER click submit or issue POST. Production data is never used.
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
          await dashboardD1Smoke(page,persona,mode);
          if (persona === 'superadmin') {
            for (const width of [1440,390,320])
              await retirementUserCardLayoutSmoke(page,mode,width);
          }
          for (const width of persona === 'superadmin' ? [1440, 390, 320] : [320]) {
            await retirementPreviewSmoke(page,persona,mode,width);
          }
          // Follow-on Liquid Glass optics smoke must always start on Dashboard.
          await navigate(page, fixture.paths.dashboard, 'RETIREMENT_UAT_RESTORE_DASHBOARD');
          if (persona==='superadmin') {
            for (const width of [1440,820,620,390,320]) {
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
            check(await page.locator('.a1-header a[aria-label^="Korpa"]').count() === 0,'USER_CART_ABSENT_AFTER_NAV',mode);
            if (mode!=='auto') await snapshot(page,mode+'-320-user-no-cart');
          }
        } catch(e) {
          check(false,'BROWSER_PERSONA_ERROR',persona+'/'+mode+': '+e.message);
          await snapshot(page,'failure-'+persona+'-'+mode);
        } finally {
          await ctx.close();
        }
      }
      const {ctx:buyerContext,page:buyerPage}=await login(browser,'buyer',mode);
      try {
        for (const width of [1440,390,320]) await headerGeometry(buyerPage,'buyer',mode,width);
        await navigate(buyerPage,'/cart','BUYER_CART_PAGE');
        check(await buyerPage.locator('.a1-header a[aria-label^="Korpa"]').count()===1,'BUYER_CART_PRESENT',mode);
        if (mode!=='auto') await snapshot(buyerPage,'buyer-cart-'+mode);
      } catch(e) {
        check(false,'BROWSER_BUYER_ERROR',mode+': '+e.message);
      } finally {
        await buyerContext.close();
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
    user_retirement_preview:'AUTHENTICATED_GET_ONLY_NO_POST_NO_REAL_USERS',
    source_sha:process.env.GITHUB_SHA||'UNVERIFIED',
    base_url:baseUrl,
    viewports:WIDTHS,
    modes:MODES,
    personas:['superadmin','user','buyer'],
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
