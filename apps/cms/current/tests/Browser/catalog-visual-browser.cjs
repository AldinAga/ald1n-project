'use strict';
// Catalog visual QA (CI only): renders the synthetic catalog and shared shop
// pages at 375/768/1280 in dark and light, fails on horizontal overflow,
// CSP violations, broken images or rate limiting, and saves PNG screenshots.
// Author: Ald1n <https://ald1n.com>
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const root = process.env.PLAYWRIGHT_NODE_ROOT;
const dir = process.env.CATALOG_VISUAL_ARTIFACT_DIR;
const base = process.env.CATALOG_VISUAL_BASE_URL;
if (process.env.APP_ENV !== 'testing' || base !== 'http://127.0.0.1:8124' || !dir || !root) {
  throw new Error('CATALOG_VISUAL_HOLD_LOCALHOST_ONLY');
}
const {chromium, webkit} = require(path.join(root, 'node_modules', 'playwright'));
fs.mkdirSync(dir, {recursive: true});

const widths = [375, 768, 1280];
const schemes = ['dark', 'light'];
const pause = ms => new Promise(resolve => setTimeout(resolve, ms));
// Defects are collected so every screenshot is still produced; the run fails at the end.
const failures = [];
const check = (ok, message) => { if (!ok) failures.push(message); };

async function open(page, url, label, problems, checkImages = true) {
  const response = await page.goto(base + url, {waitUntil: 'networkidle', timeout: 45000});
  assert.equal(response.status(), 200, label + ' HTTP');
  const csp = response.headers()['content-security-policy'] || '';
  assert(csp.includes("script-src 'none'"), label + ' scriptless CSP');
  // Bring every lazy image into view once so full-page shots show them.
  const height = await page.evaluate(() => document.documentElement.scrollHeight);
  for (let y = 0; y < height; y += 500) {
    await page.evaluate(top => window.scrollTo({top, behavior: 'instant'}), y);
    await pause(120);
  }
  await page.waitForLoadState('networkidle');
  await page.evaluate(() => window.scrollTo({top: 0, behavior: 'instant'}));
  await page.waitForLoadState('networkidle');
  await page.waitForFunction(() => [...document.images].every(img => img.complete), null, {timeout: 20000})
    .catch(() => {});
  const dims = await page.evaluate(() => ({w: window.innerWidth, sw: document.documentElement.scrollWidth}));
  check(dims.sw <= dims.w + 1, label + ' horizontal overflow ' + JSON.stringify(dims));
  const broken = await page.evaluate(() => [...document.images]
    .filter(img => !img.complete || img.naturalWidth === 0).map(img => img.getAttribute('src')));
  check(!checkImages || broken.length === 0, label + ' broken images ' + JSON.stringify(broken));
  const overflowing = await page.evaluate(() => [...document.querySelectorAll('.shop-product, .shop-filters, .shop-chip, .shop-pagination')]
    .filter(el => el.scrollWidth > el.clientWidth + 1).map(el => el.className));
  check(overflowing.length === 0, label + ' clipped component content ' + JSON.stringify(overflowing));
  const seen = problems.splice(0);
  check(seen.length === 0, label + ' console/CSP/rate problems ' + JSON.stringify(seen.slice(0, 8)));
  // The page must end at the footer (no extra scroll from the back-to-top
  // rail), and on pages shorter than two screens the button stays hidden.
  const end = await pageEnd(page);
  check(end.sh <= Math.max(end.ih, end.footerEnd) + 1, label + ' extra scroll below the footer ' + JSON.stringify(end));
  // Unscrolled and unfocused the button is never on screen.
  check(end.visibleTop === 0, label + ' back-to-top shows before scrolling ' + JSON.stringify(end));
  if (end.sh < 2 * end.ih - 72) {
    check(end.visible === 0, label + ' back-to-top shows on a short page ' + JSON.stringify(end));
  } else if (end.sh >= 2 * end.ih) {
    // Between the two thresholds the button is still sliding in, partly clipped.
    check(end.visible >= 40, label + ' back-to-top missing at the end of a long page ' + JSON.stringify(end));
  }
  return end;
}

// Scrolls to the very end, measures, and returns to the top.
async function pageEnd(page) {
  return page.evaluate(() => {
    const de = document.documentElement;
    if (document.activeElement && document.activeElement.blur) document.activeElement.blur();
    // Rendered visibility of the button itself: its own box against the
    // viewport, confirmed by hit-testing its centre (a clipped button
    // loses the hit test even when its box overlaps the viewport).
    const shown = () => {
      const btn = document.querySelector('.shop-back-to-top');
      if (!btn) return 0;
      const b = btn.getBoundingClientRect();
      const inView = Math.max(0, Math.min(b.bottom, window.innerHeight) - Math.max(b.top, 0));
      if (inView === 0) return 0;
      const hit = document.elementFromPoint(b.left + b.width / 2, Math.min(b.top + b.height / 2, window.innerHeight - 1));
      return hit && hit.closest('.shop-back-to-top') ? Math.round(inView) : 0;
    };
    window.scrollTo({top: 0, behavior: 'instant'});
    const visibleTop = shown();
    window.scrollTo({top: de.scrollHeight, behavior: 'instant'});
    const footer = document.querySelector('.shop-footer');
    const footerEnd = footer ? Math.ceil(footer.getBoundingClientRect().bottom + window.scrollY) : 0;
    const visible = shown();
    const out = {sh: de.scrollHeight, ih: window.innerHeight, footerEnd, visibleTop, visible};
    window.scrollTo({top: 0, behavior: 'instant'});
    return out;
  });
}

// Keyboard focus on the back-to-top link must put it, with its ring, inside the viewport.
async function focusTop(page, tag, file) {
  await page.locator('.shop-back-to-top').focus();
  await page.keyboard.press('Shift');
  await pause(150);
  const box = await page.locator('.shop-back-to-top').boundingBox();
  const ih = await page.evaluate(() => window.innerHeight);
  check(box && box.y >= 6 && box.y + box.height <= ih - 6, tag + ' focused back-to-top inside the viewport ' + JSON.stringify(box));
  const ring = await page.locator('.shop-back-to-top').evaluate(el => getComputedStyle(el).outlineStyle + ' ' + getComputedStyle(el).outlineWidth);
  check(ring.startsWith('solid 3px'), tag + ' back-to-top focus ring ' + ring);
  await page.screenshot({path: path.join(dir, file + '.png'), animations: 'disabled'});
  const vw = await page.evaluate(() => window.innerWidth);
  await page.screenshot({path: path.join(dir, file + '-zoom.png'), clip: {x: vw - 140, y: ih - 140, width: 140, height: 140}, animations: 'disabled'});
}

(async () => {
  const browser = await chromium.launch({headless: true});
  const summary = [];
  try {
    for (const scheme of schemes) {
      for (const width of widths) {
        const context = await browser.newContext({viewport: {width, height: 900}, colorScheme: scheme, deviceScaleFactor: 1});
        const page = await context.newPage();
        const problems = [];
        // The repository ships no favicon; its automatic 404 is not a page defect.
        const ignored = url => /\/favicon\.ico$/.test(url || '');
        page.on('console', msg => {
          if (msg.type() === 'error' && !ignored(msg.location().url)) problems.push('console: ' + msg.text().slice(0, 200));
        });
        page.on('response', res => {
          if (ignored(res.url())) return;
          if (res.status() === 429) problems.push('rate limited: ' + res.url());
          else if (res.status() >= 400 && res.url().startsWith(base)) problems.push('HTTP ' + res.status() + ' ' + res.url());
        });
        const tag = scheme + '-' + width;

        await open(page, '/katalog', 'catalog ' + tag, problems);
        const h1 = await page.locator('h1').count();
        check(h1 === 1, tag + ' single h1');
        const alts = await page.locator('.shop-products img').evaluateAll(imgs => imgs.map(img => img.alt));
        check(alts.length > 0 && alts.every(alt => alt.trim().length > 3), tag + ' meaningful alt text');
        await page.screenshot({path: path.join(dir, 'catalog-' + tag + '.png'), fullPage: true, animations: 'disabled'});
        await page.screenshot({path: path.join(dir, 'catalog-' + tag + '-fold.png'), animations: 'disabled'});

        // Scrolled viewport: the CSS-only back-to-top button must be visible.
        await page.evaluate(() => window.scrollTo({top: window.innerHeight * 2.6, behavior: 'instant'}));
        await pause(200);
        const top = await page.locator('.shop-back-to-top').boundingBox();
        check(top && top.y > 0 && top.y + top.height <= 900, tag + ' back-to-top visible after scrolling ' + JSON.stringify(top));
        await page.screenshot({path: path.join(dir, 'catalog-' + tag + '-scrolled.png'), animations: 'disabled'});
        // Keyboard focus ring on the back-to-top button (focus + key press => :focus-visible).
        await focusTop(page, tag, 'focus-top-' + tag);

        if (width === 1280) {
          await page.evaluate(() => window.scrollTo({top: 0, behavior: 'instant'}));
          await page.locator('.shop-product').first().hover();
          await pause(700);
          await page.screenshot({path: path.join(dir, 'catalog-' + tag + '-hover.png'), animations: 'allow'});
          await page.mouse.move(1, 1);
          const add = page.locator('.shop-product .shop-button').first();
          await add.focus();
          await page.keyboard.press('Shift');
          await pause(150);
          await page.screenshot({path: path.join(dir, 'focus-add-' + tag + '.png'), animations: 'disabled'});
        }

        if (width !== 768) {
          await open(page, '/katalog?page=3', 'catalog page 3 ' + tag, problems);
          await page.screenshot({path: path.join(dir, 'catalog-page3-' + tag + '.png'), fullPage: true, animations: 'disabled'});
          await open(page, '/katalog?q=Synthetic&brand_id=1&stock=available&sort=price_asc', 'filtered ' + tag, problems);
          await page.screenshot({path: path.join(dir, 'catalog-filtered-' + tag + '.png'), fullPage: true, animations: 'disabled'});
          const empty = await open(page, '/katalog?q=nepostojeci-artikal', 'empty ' + tag, problems);
          // footerEnd is rounded up from a fractional box edge: allow 1px.
          check(Math.abs(empty.sh - Math.max(empty.ih, empty.footerEnd)) <= 1,'empty ' + tag + ' page ends at the footer ' + JSON.stringify(empty));
          console.log('PAGE_END empty ' + tag + ' ' + JSON.stringify(empty));
          await page.screenshot({path: path.join(dir, 'catalog-empty-' + tag + '.png'), fullPage: true, animations: 'disabled'});
          await open(page, '/katalog/visual-product-45', 'detail ' + tag, problems);
          await page.screenshot({path: path.join(dir, 'detail-' + tag + '.png'), fullPage: true, animations: 'disabled'});
          const cart = await open(page, '/korpa', 'cart ' + tag, problems);
          // The empty cart fits one 900px screen: no scroll, no button.
          check(cart.sh === cart.ih && cart.visible === 0, 'cart ' + tag + ' no scroll and no back-to-top ' + JSON.stringify(cart));
          console.log('PAGE_END cart ' + tag + ' ' + JSON.stringify(cart));
          await page.screenshot({path: path.join(dir, 'cart-' + tag + '.png'), fullPage: true, animations: 'disabled'});
          await focusTop(page, 'cart ' + tag, 'focus-top-short-' + tag);
          const focusedEnd = await page.evaluate(() => document.documentElement.scrollHeight);
          check(focusedEnd === cart.ih, 'cart ' + tag + ' focusing back-to-top adds no scroll ' + focusedEnd);
          await page.keyboard.press('Tab');
          await open(page, '/pravne-informacije', 'legal ' + tag, problems);
          await page.screenshot({path: path.join(dir, 'legal-' + tag + '.png'), fullPage: true, animations: 'disabled'});
        }
        summary.push(tag);
        await context.close();
        // Stay well inside the real public image/browse limiters.
        await pause(scheme === 'dark' && width === 1280 ? 61000 : 2000);
      }
    }
    // Large phones (430px): the filter selects show their full labels.
    for (const scheme of schemes) {
      const context = await browser.newContext({viewport: {width: 430, height: 932}, colorScheme: scheme, deviceScaleFactor: 1});
      const page = await context.newPage();
      const tag = scheme + '-430';
      await open(page, '/katalog', 'catalog ' + tag, []);
      const cut = await page.evaluate(() => {
        const ctx = document.createElement('canvas').getContext('2d');
        return [...document.querySelectorAll('.shop-filters select')].filter(sel => {
          const cs = getComputedStyle(sel);
          ctx.font = cs.fontWeight + ' ' + cs.fontSize + ' ' + cs.fontFamily;
          const text = sel.options[sel.selectedIndex].text;
          const room = sel.clientWidth - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight);
          return ctx.measureText(text).width > room;
        }).map(sel => sel.id);
      });
      check(cut.length === 0, tag + ' truncated select labels ' + JSON.stringify(cut));
      await page.locator('.shop-filter-panel').scrollIntoViewIfNeeded();
      await page.screenshot({path: path.join(dir, 'catalog-' + tag + '-filters.png'), animations: 'disabled'});
      summary.push(tag);
      await context.close();
      await pause(2000);
    }

    // Measured WCAG colour contrast with axe-core. Script injection needs a
    // separate CSP-bypassing context; every CSP assertion above ran without it.
    await pause(61000);
    const axePath = path.join(root, 'node_modules', 'axe-core', 'axe.min.js');
    for (const scheme of schemes) {
      for (const width of [375, 1280]) {
        const context = await browser.newContext({viewport: {width, height: 900}, colorScheme: scheme, bypassCSP: true, reducedMotion: 'reduce'});
        const page = await context.newPage();
        for (const url of ['/katalog', '/katalog?q=Synthetic&brand_id=1&stock=available&sort=price_asc']) {
          await page.goto(base + url, {waitUntil: 'networkidle', timeout: 45000});
          await page.addScriptTag({path: axePath});
          const result = await page.evaluate(async () => {
            const r = await window.axe.run(document, {runOnly: ['color-contrast']});
            return {
              violations: r.violations.flatMap(v => v.nodes.map(n => n.target.join(' ') + ' :: ' + ((n.any[0] && n.any[0].message) || ''))),
              incomplete: r.incomplete.reduce((a, v) => a + v.nodes.length, 0),
              passes: r.passes.reduce((a, v) => a + v.nodes.length, 0),
            };
          });
          console.log('AXE_CONTRAST ' + scheme + '-' + width + ' ' + url + ' passes=' + result.passes + ' incomplete=' + result.incomplete + ' violations=' + result.violations.length);
          check(result.violations.length === 0, 'contrast ' + scheme + '-' + width + ' ' + url + ' ' + JSON.stringify(result.violations.slice(0, 6)));
        }
        await context.close();
      }
    }
    // WebKit (Safari engine): the back-to-top clip box, ring and shadow
    // without overflow-clip-margin, on a long page and on the short cart.
    await pause(61000);
    const wk = await webkit.launch({headless: true});
    try {
      for (const scheme of schemes) {
        const context = await wk.newContext({viewport: {width: 1280, height: 900}, colorScheme: scheme, deviceScaleFactor: 1});
        const page = await context.newPage();
        const problems = [];
        page.on('response', res => {
          if (/\/favicon\.ico$/.test(res.url())) return;
          if (res.status() >= 400 && res.url().startsWith(base)) problems.push('HTTP ' + res.status() + ' ' + res.url());
        });
        const tag = 'webkit-' + scheme + '-1280';
        // Image loading is covered in Chromium above. Headless WebKit defers
        // far lazy images past the quick scroll pass (no 4xx was served), so
        // this pass checks layout and the back-to-top only; any HTTP error
        // still fails through the response listener.
        await open(page, '/katalog', 'catalog ' + tag, problems, false);
        await page.evaluate(() => window.scrollTo({top: window.innerHeight * 2.6, behavior: 'instant'}));
        await pause(300);
        const top = await page.locator('.shop-back-to-top').boundingBox();
        check(top && top.y > 0 && top.y + top.height <= 900, tag + ' back-to-top visible after scrolling ' + JSON.stringify(top));
        await page.screenshot({path: path.join(dir, 'catalog-' + tag + '-scrolled.png'), animations: 'disabled'});
        await focusTop(page, tag, 'focus-top-' + tag);
        const cart = await open(page, '/korpa', 'cart ' + tag, problems);
        check(cart.sh === cart.ih && cart.visible === 0, 'cart ' + tag + ' no scroll and no back-to-top ' + JSON.stringify(cart));
        console.log('PAGE_END cart ' + tag + ' ' + JSON.stringify(cart));
        await page.screenshot({path: path.join(dir, 'cart-' + tag + '.png'), fullPage: true, animations: 'disabled'});
        await focusTop(page, 'cart ' + tag, 'focus-top-short-' + tag);
        summary.push(tag);
        await context.close();
        await pause(2000);
      }
    } finally {
      await wk.close();
    }
    if (failures.length) throw new Error(failures.length + ' defects: ' + failures.join(' | '));
    console.log('CATALOG_VISUAL_QA=PASS ' + summary.join(','));
  } finally {
    await browser.close();
  }
})().catch(error => { console.error('CATALOG_VISUAL_QA_FAIL ' + error.message); process.exit(1); });
