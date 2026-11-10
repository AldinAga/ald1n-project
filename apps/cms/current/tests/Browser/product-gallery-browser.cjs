'use strict';
// Product photo visual QA (CI only), run after catalog-visual-browser.cjs on
// the same synthetic fixture. Catalog cards and product pages with 6, 3, 1
// and 0 photos at 375/430/768/1280 in system dark and light (Chromium), plus
// WebKit at 375/1280, more contrast and forced colours. Checks: no light
// frame in the dark theme, the photo fills its card stage (no shrunk
// poster), renditions with srcset, thumbnails and counter, arrow keys and a
// swipe gesture move the gallery, thumbnail links, the enlarge dialog (focus
// inside, Esc, close button, focus back on the photo), no horizontal
// overflow, no CSP/console/HTTP errors and axe (contrast and ARIA rules).
// Screenshots go to the same artifact directory with a "gallery-" prefix.
// Author: Ald1n <https://ald1n.com>
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const root = process.env.PLAYWRIGHT_NODE_ROOT;
const dir = process.env.CATALOG_VISUAL_ARTIFACT_DIR;
const base = process.env.CATALOG_VISUAL_BASE_URL;
if (process.env.APP_ENV !== 'testing' || base !== 'http://127.0.0.1:8124' || !dir || !root) {
  throw new Error('GALLERY_VISUAL_HOLD_LOCALHOST_ONLY');
}
const {chromium, webkit} = require(path.join(root, 'node_modules', 'playwright'));
const axeSource = fs.readFileSync(path.join(root, 'node_modules', 'axe-core', 'axe.min.js'), 'utf8');
fs.mkdirSync(dir, {recursive: true});

const pause = ms => new Promise(resolve => setTimeout(resolve, ms));
const failures = [];
const check = (ok, message) => { if (!ok) failures.push(message); };
const shot = name => path.join(dir, 'gallery-' + name + '.png');

const PRODUCTS = [
  {key: 'six', slug: 'visual-product-43', photos: 6},
  {key: 'three', slug: 'visual-product-44', photos: 3},
  {key: 'one', slug: 'visual-product-42', photos: 1},
  {key: 'none', slug: 'visual-product-40', photos: 0},
  {key: 'fallback', slug: 'visual-product-39', photos: 1},
];
const MODES = [
  {name: 'dark', scheme: 'dark'},
  {name: 'light', scheme: 'light'},
];

// Same sliding-minute pacing as the catalog script (browse 120/min, images 300/min).
const sent = {html: [], img: []};
function track(page) {
  page.on('request', req => {
    const url = req.url();
    if (!url.startsWith(base)) return;
    if (url.includes('/katalog/slika/')) sent.img.push(Date.now());
    else if (req.resourceType() === 'document') sent.html.push(Date.now());
  });
}
async function budget(html, img) {
  for (;;) {
    const now = Date.now();
    for (const key of ['html', 'img']) while (sent[key].length && now - sent[key][0] > 61000) sent[key].shift();
    if (sent.html.length + html <= 90 && sent.img.length + img <= 240) return;
    await pause(1000);
  }
}

function watch(page, problems) {
  page.on('console', msg => {
    // Playwright injects an inline <style> to freeze animations and hide the
    // caret for page.screenshot({animations: 'disabled'}). The page CSP
    // (style-src 'self') refuses it, and only WebKit logs that refusal as an
    // error. Only that inline-style message is skipped: a refused external
    // stylesheet names its URL instead of 'unsafe-inline' and still fails.
    if (page.context().browser().browserType().name() === 'webkit'
      && /Refused to apply a stylesheet because its hash, its nonce, or 'unsafe-inline'/.test(msg.text())) return;
    if (msg.type() === 'error' && !/\/favicon\.ico$/.test(msg.location().url || '')) problems.push('console: ' + msg.text().slice(0, 200));
  });
  page.on('response', res => {
    if (res.status() === 429) problems.push('rate limited: ' + res.url());
    else if (res.status() >= 400 && res.url().startsWith(base)) problems.push('HTTP ' + res.status() + ' ' + res.url());
  });
  track(page);
}

async function open(page, url, label, problems) {
  await budget(1, url === '/katalog' ? 24 : 14);
  const response = await page.goto(base + url, {waitUntil: 'networkidle', timeout: 45000});
  assert.equal(response.status(), 200, label + ' HTTP');
  await page.waitForFunction(() => [...document.images].filter(i => i.loading !== 'lazy' && i.getClientRects().length).every(i => i.complete), null, {timeout: 20000}).catch(() => {});
  const dims = await page.evaluate(() => ({w: window.innerWidth, sw: document.documentElement.scrollWidth}));
  check(dims.sw <= dims.w + 1, label + ' horizontal overflow ' + JSON.stringify(dims));
  const seen = problems.splice(0);
  check(seen.length === 0, label + ' console/CSP/HTTP problems ' + JSON.stringify(seen.slice(0, 8)));
}

const luminance = rgb => {
  const m = /rgba?\(([^)]+)\)/.exec(rgb || '');
  if (!m) return null;
  const p = m[1].split(/[\s,/]+/).filter(Boolean).map(Number);
  // A faint translucent tint over the dark glass is not a frame.
  if (p.length > 3 && p[3] < 0.25) return null;
  const lin = v => { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); };
  return 0.2126 * lin(p[0]) + 0.7152 * lin(p[1]) + 0.0722 * lin(p[2]);
};

// Catalog cards: photo fills its stage, no light bed in the dark theme,
// renditions with srcset and real dimensions, aligned rows.
async function catalogChecks(page, mode, width, tag) {
  const st = await page.evaluate(() => [...document.querySelectorAll('.shop-product')].slice(0, 8).map(card => {
    const stage = card.querySelector('.shop-image');
    const img = stage && stage.querySelector('img');
    const s = stage.getBoundingClientRect();
    const cs = getComputedStyle(stage);
    const inner = {w: s.width - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight), h: s.height - parseFloat(cs.paddingTop) - parseFloat(cs.paddingBottom)};
    const r = img ? img.getBoundingClientRect() : null;
    const title = card.querySelector('.shop-product-title').getBoundingClientRect();
    return {
      bed: cs.backgroundColor,
      imgBed: img ? getComputedStyle(img).backgroundColor : null,
      blend: img ? getComputedStyle(img).mixBlendMode : null,
      fill: r ? Math.max(r.width / inner.w, r.height / inner.h) : null,
      loaded: img ? img.complete && img.naturalWidth > 0 : null,
      src: img ? img.getAttribute('src') : null,
      srcset: img ? img.getAttribute('srcset') : null,
      current: img ? img.currentSrc : null,
      natural: img ? [img.naturalWidth, img.naturalHeight] : null,
      // Real pixel width of the file the browser chose: its srcset "w"
      // descriptor (naturalWidth is divided by the srcset density).
      fileW: img ? (() => {
        const chosen = new URL(img.currentSrc, location.href).pathname + new URL(img.currentSrc, location.href).search;
        for (const part of (img.getAttribute('srcset') || '').split(',')) {
          const [url, w] = part.trim().split(/\s+/);
          if (url === chosen && /^\d+w$/.test(w || '')) return parseInt(w, 10);
        }
        return img.naturalWidth;
      })() : null,
      rendered: r ? [Math.round(img.clientWidth), Math.round(img.clientHeight)] : null,
      ratio: (stage.className.match(/shop-ratio-(r\d+)/) || [])[1] || null,
      radius: img ? parseFloat(getComputedStyle(img).borderTopLeftRadius) : null,
      titleTop: Math.round(title.top + window.scrollY),
      top: Math.round(card.getBoundingClientRect().top + window.scrollY),
    };
  }));
  const withImg = st.filter(c => c.src);
  check(withImg.length >= 5, tag + ' cards with photos ' + withImg.length);
  for (const c of withImg) {
    if (!c.loaded) continue;
    check(c.fill >= 0.9, tag + ' photo fills its stage ' + JSON.stringify(c));
    check(c.blend === 'normal', tag + ' no multiply blend ' + c.blend);
    check(c.radius >= 8, tag + ' rounded photo corners ' + c.radius);
    // No upscaling: the photo is never drawn wider (in CSS px) than the
    // pixel width of the file the browser chose.
    check(c.rendered[0] <= c.fileW + 1, tag + ' photo not upscaled ' + JSON.stringify(c));
  }
  check(withImg.some(c => c.ratio === 'r34' || c.ratio === 'r45'), tag + ' portrait cards get a portrait stage ' + JSON.stringify(st.map(c => c.ratio)));
  // Cards use the 512px thumbnail rendition; only the fixture product
  // without renditions falls back to the gated original.
  check(withImg.every(c => /\/katalog\/slika\/\d+(\?r=thumbnail)?$/.test(c.src)) && withImg.filter(c => /\?r=thumbnail$/.test(c.src)).length >= 4,
    tag + ' card src ' + JSON.stringify(withImg.map(c => c.src)));
  check(withImg.filter(c => c.srcset).length >= 4 && withImg.filter(c => c.srcset).every(c => /\?r=thumbnail \d+w, .*\?r=display \d+w$/.test(c.srcset)), tag + ' card srcset ' + JSON.stringify(withImg.map(c => c.srcset)));
  if (mode.name === 'dark') {
    for (const c of st) {
      for (const bg of [c.bed, c.imgBed]) {
        const l = luminance(bg);
        check(l === null || l < 0.08, tag + ' no light frame in the dark theme ' + bg);
      }
    }
  }
  // Titles of cards in one row start at the same height (subgrid rows).
  const rows = new Map();
  for (const c of st) rows.set(c.top, (rows.get(c.top) || []).concat(c.titleTop));
  for (const tops of rows.values()) check(Math.max(...tops) - Math.min(...tops) <= 1, tag + ' titles aligned in a row ' + JSON.stringify(tops));
  console.log('GALLERY_CARDS ' + tag + ' ' + JSON.stringify(withImg.slice(0, 4).map(c => ({r: c.ratio, fill: c.fill && c.fill.toFixed(2), n: c.natural, px: c.rendered}))));
}

async function galleryState(page) {
  return page.evaluate(() => {
    const vp = document.querySelector('.shop-gallery-viewport');
    const slides = [...document.querySelectorAll('.shop-slide')];
    const vr = vp ? vp.getBoundingClientRect() : null;
    const visible = vp ? slides.findIndex(s => Math.abs(s.getBoundingClientRect().left - vr.left) < 4) + 1 : 0;
    const counter = visible > 0 ? (slides[visible - 1].querySelector('.shop-slide-count') || {textContent: ''}).textContent.trim() : '';
    const first = document.querySelector('.shop-slide img');
    const zoom = document.querySelector('.shop-slide-stage');
    return {
      slides: slides.length,
      thumbs: document.querySelectorAll('.shop-thumb').length,
      dialogs: document.querySelectorAll('dialog.shop-lightbox').length,
      region: vp ? vp.getAttribute('role') : null,
      visible,
      counter,
      firstLoaded: first ? first.complete && first.naturalWidth > 0 : null,
      firstSrc: first ? first.currentSrc : null,
      alts: [...document.querySelectorAll('.shop-slide img')].map(i => i.alt),
      zoomBg: zoom ? getComputedStyle(zoom).backgroundColor : null,
      zoomBox: zoom ? zoom.getBoundingClientRect().toJSON() : null,
      firstBox: first ? first.getBoundingClientRect().toJSON() : null,
      placeholder: !!document.querySelector('.shop-gallery-empty .no-image'),
      schemaBlocks: document.querySelectorAll('script[type="application/ld+json"]').length,
    };
  });
}

// Settled = the viewport offset stayed the same for 400 ms and sits on a
// slide. (Checking alignment alone passed before a smooth scroll began.)
async function waitScroll(page) {
  await pause(150);
  await page.evaluate(() => new Promise(resolve => {
    const vp = document.querySelector('.shop-gallery-viewport');
    if (!vp) return resolve();
    const started = Date.now();
    let last = -1;
    let stableSince = Date.now();
    const tick = () => {
      const x = vp.scrollLeft;
      if (x !== last) { last = x; stableSince = Date.now(); }
      const w = vp.clientWidth;
      const aligned = Math.abs(x / w - Math.round(x / w)) < 0.02;
      if ((aligned && Date.now() - stableSince >= 400) || Date.now() - started > 6000) return resolve();
      setTimeout(tick, 50);
    };
    tick();
  }));
}

async function productChecks(page, product, mode, width, engine, tag) {
  let st = await galleryState(page);
  check(st.slides === product.photos, tag + ' slide count ' + st.slides);
  check(st.dialogs === product.photos, tag + ' enlarge dialogs ' + st.dialogs);
  check(st.thumbs === (product.photos > 1 ? product.photos : 0), tag + ' thumbnails ' + st.thumbs);
  if (product.photos === 0) {
    check(st.placeholder, tag + ' placeholder for a product without photos');
    return;
  }
  check(st.firstLoaded, tag + ' first photo loaded ' + st.firstSrc);
  check(product.key === 'fallback' ? /\/katalog\/slika\/\d+$/.test(st.firstSrc) : /\?r=(display|thumbnail)$/.test(st.firstSrc), tag + ' rendition src ' + st.firstSrc);
  check(st.alts.every((a, i) => a.length > 6 && (product.photos === 1 ? !/slika \d/.test(a) : a.endsWith('slika ' + (i + 1) + ' od ' + product.photos))), tag + ' alt text ' + JSON.stringify(st.alts));
  if (mode.name === 'dark') {
    const l = luminance(st.zoomBg);
    check(l === null || l < 0.08, tag + ' gallery photo bed is dark in the dark theme ' + st.zoomBg);
  }
  // The photo uses the stage: one side fills it (within the 10% ratio rounding).
  const fill = Math.max(st.firstBox.width / st.zoomBox.width, st.firstBox.height / st.zoomBox.height);
  check(fill >= 0.9, tag + ' gallery photo fills the stage ' + fill.toFixed(2));
  if (product.photos > 1) {
    check(st.region === 'region' && st.visible === 1 && st.counter === 'Slika 1 od ' + product.photos, tag + ' initial state ' + JSON.stringify({v: st.visible, c: st.counter}));
    // Keyboard: focus the gallery, ArrowRight moves exactly one photo.
    await page.locator('.shop-gallery-viewport').focus();
    await page.keyboard.press('ArrowRight');
    await waitScroll(page);
    st = await galleryState(page);
    check(st.visible === 2 && st.counter === 'Slika 2 od ' + product.photos, tag + ' ArrowRight shows photo 2 ' + JSON.stringify({v: st.visible, c: st.counter}));
    await page.keyboard.press('ArrowLeft');
    await waitScroll(page);
    st = await galleryState(page);
    check(st.visible === 1, tag + ' ArrowLeft back to photo 1 ' + st.visible);
    // Thumbnail link to the last photo.
    const last = product.photos;
    await page.locator('.shop-thumb[href="#slika-' + last + '"]').click();
    await waitScroll(page);
    st = await galleryState(page);
    check(st.visible === last, tag + ' thumbnail shows photo ' + last + ' got ' + st.visible);
    const lazyLoaded = await page.waitForFunction(n => { const i = document.querySelector('#slika-' + n + ' img'); return i.complete && i.naturalWidth > 0; }, last, {timeout: 6000})
      .then(() => true, () => false);
    check(lazyLoaded, tag + ' lazy photo loads when shown');
    const chosen = await page.evaluate(n => getComputedStyle(document.querySelector('.shop-thumb[href="#slika-' + n + '"]')).borderTopColor
      !== getComputedStyle(document.querySelector('.shop-thumb[href="#slika-1"]')).borderTopColor, last);
    check(chosen, tag + ' chosen thumbnail is marked');
    await page.screenshot({path: shot(tag + '-thumb-' + last), animations: 'disabled'});
    // Swipe back to the first photo.
    if (engine === 'chromium') {
      const box = await page.locator('.shop-gallery-viewport').boundingBox();
      const cdp = await page.context().newCDPSession(page);
      // Real touch input: finger down in the photo, drag 60% to the right,
      // lift. (Input.synthesizeScrollGesture never moved this scroller in
      // headless Chromium, in either direction.)
      await cdp.send('Emulation.setTouchEmulationEnabled', {enabled: true, maxTouchPoints: 5});
      const y = Math.round(box.y + box.height / 2);
      const x0 = Math.round(box.x + box.width * 0.2);
      const dx = Math.round(box.width * 0.6);
      await cdp.send('Input.dispatchTouchEvent', {type: 'touchStart', touchPoints: [{x: x0, y}]});
      for (let i = 1; i <= 12; i++) {
        await cdp.send('Input.dispatchTouchEvent', {type: 'touchMove', touchPoints: [{x: x0 + Math.round(dx * i / 12), y}]});
        await pause(16);
      }
      await cdp.send('Input.dispatchTouchEvent', {type: 'touchEnd', touchPoints: []});
      await waitScroll(page);
      st = await galleryState(page);
      let how = 'touch';
      if (st.visible === last) {
        // Touch emulation did not scroll here: verify the same snap with a
        // horizontal trackpad swipe and say so in the log.
        how = 'wheel';
        await page.mouse.move(box.x + box.width / 2, y);
        await page.mouse.wheel(-Math.round(box.width * 0.7), 0);
        await waitScroll(page);
        st = await galleryState(page);
      }
      console.log('GALLERY_SWIPE ' + tag + ' via=' + how + ' visible=' + st.visible);
      check(st.visible === last - 1, tag + ' swipe right shows the previous photo ' + st.visible);
      await cdp.send('Emulation.setTouchEmulationEnabled', {enabled: false}).catch(() => {});
      await cdp.detach();
    } else {
      await page.locator('.shop-gallery-viewport').hover();
      await page.mouse.wheel(-(await page.evaluate(() => document.querySelector('.shop-gallery-viewport').clientWidth)) * 0.7, 0);
      await waitScroll(page);
      st = await galleryState(page);
      check(st.visible === last - 1, tag + ' horizontal wheel shows the previous photo ' + st.visible);
    }
  }
  // Enlarge dialog: open from the visible photo, focus moves inside,
  // Esc closes it and focus returns to the photo control.
  const n = st.visible || 1;
  const zoom = page.locator('#slika-' + n + ' .shop-slide-zoom');
  await zoom.focus();
  await page.keyboard.press('Enter');
  await pause(400);
  let dlg = await page.evaluate(k => {
    const d = document.getElementById('uvecano-' + k);
    const img = d.querySelector('img');
    const r = d.getBoundingClientRect();
    return {
      open: d.open || d.matches(':popover-open'),
      modal: d.matches(':modal'),
      focusInside: d.contains(document.activeElement),
      focused: document.activeElement ? document.activeElement.className : null,
      cover: r.width >= window.innerWidth - 1 && r.height >= window.innerHeight - 1,
      imgLoaded: img.complete && img.naturalWidth > 0,
    };
  }, n);
  check(dlg.open && dlg.cover && dlg.focusInside, tag + ' enlarge dialog open, full screen, focus inside ' + JSON.stringify(dlg));
  await page.waitForFunction(k => { const i = document.querySelector('#uvecano-' + k + ' img'); return i.complete && i.naturalWidth > 0; }, n, {timeout: 8000}).catch(() => {});
  await page.screenshot({path: shot(tag + '-lightbox'), animations: 'disabled'});
  if (dlg.modal) {
    // Modal dialog: Tab cannot leave it.
    for (let i = 0; i < 4; i++) await page.keyboard.press('Tab');
    const inside = await page.evaluate(k => document.getElementById('uvecano-' + k).contains(document.activeElement) || document.activeElement === document.body, n);
    check(inside, tag + ' focus stays in the modal dialog');
  }
  console.log('GALLERY_DIALOG ' + tag + ' ' + JSON.stringify(dlg));
  await page.keyboard.press('Escape');
  await pause(300);
  dlg = await page.evaluate(k => {
    const d = document.getElementById('uvecano-' + k);
    return {open: d.open || d.matches(':popover-open'), back: !!(document.activeElement && document.activeElement.closest && document.activeElement.closest('#slika-' + k + ' .shop-slide-zoom'))};
  }, n);
  check(!dlg.open && dlg.back, tag + ' Esc closes and returns focus ' + JSON.stringify(dlg));
  // Close button path.
  await zoom.click();
  await pause(300);
  await page.locator('#uvecano-' + n + ' .shop-lightbox-close').click();
  await pause(300);
  const closed = await page.evaluate(k => { const d = document.getElementById('uvecano-' + k); return !(d.open || d.matches(':popover-open')); }, n);
  check(closed, tag + ' close button closes the dialog');
}

async function axe(page, url, label) {
  await budget(1, 14);
  await page.goto(base + url, {waitUntil: 'networkidle', timeout: 45000});
  await page.evaluate(src => { (0, eval)(src); }, axeSource).catch(() => {});
  if (!(await page.evaluate(() => typeof window.axe === 'object'))) await page.addScriptTag({content: axeSource});
  const result = await page.evaluate(async () => {
    // The shared layout's .shop-top-rail sits outside a landmark on main
    // already (layout file, not this change); the gallery itself is checked.
    const r = await window.axe.run({include: [document], exclude: [['.shop-top-rail']]}, {runOnly: ['color-contrast', 'image-alt', 'button-name', 'link-name', 'aria-allowed-attr', 'aria-hidden-focus', 'scrollable-region-focusable', 'duplicate-id', 'list', 'listitem', 'nested-interactive', 'region']});
    return r.violations.flatMap(v => v.nodes.map(n => v.id + ' ' + n.target.join(' ') + ' :: ' + ((n.any[0] && n.any[0].message) || '')));
  });
  console.log('GALLERY_AXE ' + label + ' violations=' + result.length);
  check(result.length === 0, 'axe ' + label + ' ' + JSON.stringify(result.slice(0, 6)));
}

async function pass(browserType, engine, widths, modes, extra = {}) {
  const browser = await browserType.launch({headless: true});
  try {
    for (const mode of modes) {
      for (const width of widths) {
        const mobile = width < 768;
        const ctx = await browser.newContext({viewport: {width, height: mobile ? 820 : 900}, colorScheme: mode.scheme, deviceScaleFactor: engine === 'chromium' && width === 430 ? 2 : 1,
          hasTouch: mobile && engine === 'chromium', ...extra.context});
        const page = await ctx.newPage();
        const problems = [];
        watch(page, problems);
        const tag = [engine, extra.tag || mode.name, width].join('-');
        await open(page, '/katalog', 'catalog ' + tag, problems);
        await catalogChecks(page, mode, width, 'catalog ' + tag);
        await page.screenshot({path: shot(tag + '-catalog-fold'), animations: 'disabled'});
        await page.evaluate(() => window.scrollTo({top: 0, behavior: 'instant'}));
        for (const product of (extra.products || PRODUCTS)) {
          const label = tag + '-' + product.key;
          await open(page, '/katalog/' + product.slug, label, problems);
          await page.screenshot({path: shot(label), animations: 'disabled'});
          await page.screenshot({path: shot(label + '-full'), fullPage: true, animations: 'disabled'});
          if (!extra.skipInteraction) await productChecks(page, product, mode, width, engine, label);
          const seen = problems.splice(0);
          check(seen.length === 0, label + ' problems after interaction ' + JSON.stringify(seen.slice(0, 8)));
        }
        await ctx.close();
        if (!extra.skipAxe) {
          const axeCtx = await browser.newContext({viewport: {width, height: 900}, colorScheme: mode.scheme, bypassCSP: true, reducedMotion: 'reduce'});
          const axePage = await axeCtx.newPage();
          track(axePage);
          for (const product of PRODUCTS.slice(0, 3)) await axe(axePage, '/katalog/' + product.slug, tag + '-' + product.key);
          await axeCtx.close();
        }
      }
    }
  } finally {
    await browser.close();
  }
}

(async () => {
  // The catalog script has just used the same per-IP limiter minute.
  await pause(62000);
  await pass(chromium, 'chromium', [375, 430, 768, 1280], MODES);
  await pass(webkit, 'webkit', [375, 1280], MODES, {skipAxe: true});
  await pass(chromium, 'chromium', [1280], [MODES[0]], {tag: 'contrast-more', context: {contrast: 'more'}, products: PRODUCTS.slice(0, 1), skipAxe: true, skipInteraction: true});
  await pass(chromium, 'chromium', [1280], [MODES[1]], {tag: 'forced-colors', context: {forcedColors: 'active'}, products: PRODUCTS.slice(0, 1), skipAxe: true, skipInteraction: true});
  await pass(chromium, 'chromium', [375], [MODES[0]], {tag: 'reduced-motion', context: {reducedMotion: 'reduce'}, products: PRODUCTS.slice(0, 1), skipAxe: true});
  if (failures.length) throw new Error(failures.length + ' defects: ' + failures.join(' | '));
  console.log('GALLERY_VISUAL_QA=PASS');
})().catch(error => { console.error('GALLERY_VISUAL_QA_FAIL ' + error.message); process.exit(1); });
