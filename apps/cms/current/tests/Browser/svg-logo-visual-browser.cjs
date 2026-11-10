'use strict';
// SVG logo visual QA (CI only), run after seed-svg-logo-visual.php.
// Storefront: the sanitized SVG logo pair renders through <img> from the
// site-assets route in light and dark (system preference), the matching
// variant is the visible one, the favicon is image/svg+xml, and the route
// answers with the sandboxed headers and no cookie. Admin: a hostile SVG
// upload is stored cleaned (no script reaches the served file), an XXE SVG
// and a PNG renamed to .svg are refused with a Serbian reason.
// Screenshots go to CATALOG_VISUAL_ARTIFACT_DIR.
// Author: Ald1n <https://ald1n.com>
const fs = require('node:fs');
const path = require('node:path');

const root = process.env.PLAYWRIGHT_NODE_ROOT;
const dir = process.env.CATALOG_VISUAL_ARTIFACT_DIR;
const base = process.env.CATALOG_VISUAL_BASE_URL;
if (process.env.APP_ENV !== 'testing' || base !== 'http://127.0.0.1:8124' || !dir || !root) {
  throw new Error('SVG_LOGO_VISUAL_HOLD_LOCALHOST_ONLY');
}
const {chromium} = require(path.join(root, 'node_modules', 'playwright'));
fs.mkdirSync(dir, {recursive: true});

const failures = [];
const check = (ok, message) => { if (!ok) failures.push(message); };
const shot = name => path.join(dir, 'svg-logo-' + name + '.png');
const CSP = "default-src 'none'; style-src 'unsafe-inline'; sandbox";

const HOSTILE = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 72" onload="alert(1)">'
  + '<script>alert("svg-visual-script")</script>'
  + '<rect x="4" y="8" width="56" height="56" fill="#b4232c" onclick="alert(2)"/>'
  + '<image href="http://127.0.0.1:9/track.png" width="1" height="1"/>'
  + '<text x="76" y="47" font-size="30" fill="#121b24">Hostile cleaned</text></svg>';
const XXE = '<?xml version="1.0"?><!DOCTYPE svg [<!ENTITY x SYSTEM "file:///etc/passwd">]>'
  + '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><text>&x;</text></svg>';
const PNG = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAHgAAAAeCAIAAABoq03CAAAARUlEQVR42u3QQQ0AAAgEoItjHGMbyxbOBxsJSPVwIApEi0a0aNEWRItGtGjRFkSLRrRo0YgWjWjRohEtGtGiRSNaNKI/WrZQlQDSSGEmAAAAAElFTkSuQmCC', 'base64');

async function routeHeaders(request, src, label) {
  const response = await request.get(new URL(src, base).toString());
  const h = response.headers();
  check(response.status() === 200, label + ' route status ' + response.status());
  check(h['content-type'] === 'image/svg+xml', label + ' content-type ' + h['content-type']);
  check(h['x-content-type-options'] === 'nosniff', label + ' nosniff');
  check(h['content-security-policy'] === CSP, label + ' csp ' + h['content-security-policy']);
  const cache = h['cache-control'] || '';
  check(cache.includes('must-revalidate') && !cache.includes('immutable'), label + ' cache-control ' + cache);
  check(!('set-cookie' in h), label + ' must not set a cookie');
  return (await response.body()).toString('utf8');
}

async function storefront(browser) {
  for (const scheme of ['light', 'dark']) {
    const ctx = await browser.newContext({viewport: {width: 1280, height: 800}, colorScheme: scheme});
    const page = await ctx.newPage();
    const errors = [];
    page.on('console', msg => { if (msg.type() === 'error') errors.push(msg.text()); });
    const response = await page.goto(base + '/katalog', {waitUntil: 'networkidle'});
    check(response.status() === 200, scheme + ' catalog status ' + response.status());
    const st = await page.evaluate(() => {
      const visible = el => { const s = getComputedStyle(el); const r = el.getBoundingClientRect(); return s.display !== 'none' && s.visibility !== 'hidden' && r.width > 0 && r.height > 0; };
      const logos = [...document.querySelectorAll('.shop-brand img.shop-logo')].map(img => ({
        cls: img.className, src: img.getAttribute('src'), ok: img.complete && img.naturalWidth > 0,
        visible: visible(img), w: Math.round(img.getBoundingClientRect().width), h: Math.round(img.getBoundingClientRect().height),
      }));
      const icon = document.querySelector('link[rel="icon"]');
      return {logos, icon: icon && {href: icon.getAttribute('href'), type: icon.getAttribute('type')}, inline: document.querySelectorAll('svg[viewBox="0 0 300 72"]').length};
    });
    const shown = st.logos.filter(l => l.visible);
    check(st.logos.length === 2, scheme + ' expected a light and a dark logo ' + JSON.stringify(st.logos));
    check(st.logos.every(l => /^\/site-assets\/[A-Za-z0-9_-]{20,64}\.svg\?v=[a-f0-9]+-[a-f0-9]+-s[0-9]+$/.test(l.src) && l.ok), scheme + ' logos load from the route ' + JSON.stringify(st.logos));
    check(shown.length === 1 && shown[0].cls.includes('shop-logo-' + scheme) && shown[0].h >= 24 && shown[0].w > shown[0].h,
      scheme + ' visible variant ' + JSON.stringify(shown));
    check(st.icon && st.icon.type === 'image/svg+xml' && st.icon.href.startsWith('/site-assets/'), scheme + ' favicon ' + JSON.stringify(st.icon));
    check(st.inline === 0, scheme + ' uploaded SVG must never be inlined');
    check(errors.length === 0, scheme + ' console errors ' + JSON.stringify(errors));
    await page.screenshot({path: shot('storefront-' + scheme), clip: {x: 0, y: 0, width: 1280, height: 220}});
    for (const logo of st.logos) await routeHeaders(ctx.request, logo.src, scheme + ' ' + logo.cls);
    await ctx.close();
  }
}

async function admin(browser) {
  const ctx = await browser.newContext({viewport: {width: 1280, height: 900}, colorScheme: 'light'});
  const page = await ctx.newPage();
  const login = await page.goto(base + '/login', {waitUntil: 'networkidle'});
  check(login.status() === 200, 'login form status ' + login.status());
  const authLogo = await page.locator('img.auth-logo').first().getAttribute('src').catch(() => null);
  check(typeof authLogo === 'string' && authLogo.includes('/site-assets/'), 'login logo from the route ' + authLogo);
  await page.locator('input[name="login"]').fill('catalog-visual-admin');
  await page.locator('input[name="password"]').fill('SyntheticOnly-NotProduction!');
  await Promise.all([page.waitForNavigation({timeout: 15000}), page.locator('button[type="submit"]').first().click()]);

  const upload = async (name, file) => {
    await page.goto(base + '/admin/settings/appearance', {waitUntil: 'networkidle'});
    await page.locator('input[name="site_logo_light"]').setInputFiles(file);
    await Promise.all([page.waitForNavigation({timeout: 15000}), page.getByRole('button', {name: 'Sačuvaj izgled'}).click()]);
    await page.waitForLoadState('networkidle');
    await page.screenshot({path: shot('admin-' + name), fullPage: false});
    return page.locator('body').innerText();
  };

  const cleaned = await upload('hostile-cleaned', {name: 'hostile.svg', mimeType: 'image/svg+xml', buffer: Buffer.from(HOSTILE)});
  check(cleaned.includes('je očišćen pre čuvanja') && cleaned.includes('skripte: 1'), 'hostile upload notice ' + cleaned.slice(0, 400));
  const preview = await page.locator('[data-svg-logo-preview] img').first().getAttribute('src');
  check(typeof preview === 'string' && preview.includes('/site-assets/'), 'preview src ' + preview);
  if (preview) {
    const served = await routeHeaders(ctx.request, preview, 'hostile served');
    for (const bad of ['<script', 'alert', 'onload', 'onclick', '<image', '127.0.0.1:9']) {
      check(!served.includes(bad), 'served hostile SVG still contains ' + bad);
    }
    check(served.includes('Hostile cleaned'), 'benign text kept in the cleaned SVG');
  }

  const xxe = await upload('xxe-rejected', {name: 'xxe.svg', mimeType: 'image/svg+xml', buffer: Buffer.from(XXE)});
  check(xxe.includes('DOCTYPE ili ENTITY'), 'XXE rejection reason ' + xxe.slice(0, 400));
  check(!xxe.includes('root:'), 'XXE must never expand');
  const renamed = await upload('png-renamed-rejected', {name: 'logo.svg', mimeType: 'image/svg+xml', buffer: PNG});
  check(renamed.includes('ne odgovara ekstenziji'), 'renamed PNG rejection reason ' + renamed.slice(0, 400));
  await ctx.close();
}

(async () => {
  const browser = await chromium.launch();
  try {
    await storefront(browser);
    await admin(browser);
  } finally {
    await browser.close();
  }
  if (failures.length) {
    console.error('SVG_LOGO_VISUAL_FAIL\n' + failures.join('\n'));
    process.exit(1);
  }
  console.log('SVG_LOGO_VISUAL=PASS');
})().catch(error => { console.error(error); process.exit(1); });
