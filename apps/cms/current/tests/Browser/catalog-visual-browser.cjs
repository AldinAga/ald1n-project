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
const {chromium} = require(path.join(root, 'node_modules', 'playwright'));
fs.mkdirSync(dir, {recursive: true});

const widths = [375, 768, 1280];
const schemes = ['dark', 'light'];
const pause = ms => new Promise(resolve => setTimeout(resolve, ms));
// Defects are collected so every screenshot is still produced; the run fails at the end.
const failures = [];
const check = (ok, message) => { if (!ok) failures.push(message); };

async function open(page, url, label, problems) {
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
  check(broken.length === 0, label + ' broken images ' + JSON.stringify(broken));
  const overflowing = await page.evaluate(() => [...document.querySelectorAll('.shop-product, .shop-filters, .shop-chip, .shop-pagination')]
    .filter(el => el.scrollWidth > el.clientWidth + 1).map(el => el.className));
  check(overflowing.length === 0, label + ' clipped component content ' + JSON.stringify(overflowing));
  const seen = problems.splice(0);
  check(seen.length === 0, label + ' console/CSP/rate problems ' + JSON.stringify(seen.slice(0, 8)));
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

        if (width === 1280) {
          await page.evaluate(() => window.scrollTo({top: 0, behavior: 'instant'}));
          await page.locator('.shop-product').first().hover();
          await pause(700);
          await page.screenshot({path: path.join(dir, 'catalog-' + tag + '-hover.png'), animations: 'allow'});
          await page.mouse.move(1, 1);
        }

        if (width !== 768) {
          await open(page, '/katalog?page=3', 'catalog page 3 ' + tag, problems);
          await page.screenshot({path: path.join(dir, 'catalog-page3-' + tag + '.png'), fullPage: true, animations: 'disabled'});
          await open(page, '/katalog?q=Synthetic&brand_id=1&stock=available&sort=price_asc', 'filtered ' + tag, problems);
          await page.screenshot({path: path.join(dir, 'catalog-filtered-' + tag + '.png'), fullPage: true, animations: 'disabled'});
          await open(page, '/katalog?q=nepostojeci-artikal', 'empty ' + tag, problems);
          await page.screenshot({path: path.join(dir, 'catalog-empty-' + tag + '.png'), fullPage: true, animations: 'disabled'});
          await open(page, '/katalog/visual-product-45', 'detail ' + tag, problems);
          await page.screenshot({path: path.join(dir, 'detail-' + tag + '.png'), fullPage: true, animations: 'disabled'});
          await open(page, '/korpa', 'cart ' + tag, problems);
          await page.screenshot({path: path.join(dir, 'cart-' + tag + '.png'), fullPage: true, animations: 'disabled'});
          await open(page, '/pravne-informacije', 'legal ' + tag, problems);
          await page.screenshot({path: path.join(dir, 'legal-' + tag + '.png'), fullPage: true, animations: 'disabled'});
        }
        summary.push(tag);
        await context.close();
        // Stay well inside the real public image/browse limiters.
        await pause(scheme === 'dark' && width === 1280 ? 61000 : 2000);
      }
    }
    if (failures.length) throw new Error(failures.length + ' defects: ' + failures.join(' | '));
    console.log('CATALOG_VISUAL_QA=PASS ' + summary.join(','));
  } finally {
    await browser.close();
  }
})().catch(error => { console.error('CATALOG_VISUAL_QA_FAIL ' + error.message); process.exit(1); });
