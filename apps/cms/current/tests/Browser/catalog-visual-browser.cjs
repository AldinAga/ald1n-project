'use strict';
// Storefront visual QA (CI only). Renders the synthetic catalog, product
// detail, cart and legal pages at 375/430/768/1280 in four theme modes:
// system-dark, system-light, forced light (shop_theme cookie over a dark OS)
// and forced dark (cookie over a light OS); with the seeded site logo and
// without it; in Chromium and WebKit. Fails on horizontal overflow, CSP
// violations, broken images, rate limiting, wrong theme or logo variant,
// back-to-top regressions, axe colour-contrast violations and on any text
// whose measured contrast against the real rendered backdrop (pixels of a
// screenshot taken with all text made transparent) is below WCAG AA.
// B1149: the icon-only theme switch changes the theme in place (no reload,
// no request to /tema) through the one nonce'd script, the choice survives a
// reload, the no-JavaScript form still posts to /tema, and every theme icon
// keeps at least 3:1 against its rendered button background.
// Author: Ald1n <https://ald1n.com>
const assert = require('node:assert/strict');
const cp = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');

const root = process.env.PLAYWRIGHT_NODE_ROOT;
const dir = process.env.CATALOG_VISUAL_ARTIFACT_DIR;
const base = process.env.CATALOG_VISUAL_BASE_URL;
if (process.env.APP_ENV !== 'testing' || base !== 'http://127.0.0.1:8124' || !dir || !root) {
  throw new Error('CATALOG_VISUAL_HOLD_LOCALHOST_ONLY');
}
const {chromium, webkit} = require(path.join(root, 'node_modules', 'playwright'));
const axeSource = fs.readFileSync(path.join(root, 'node_modules', 'axe-core', 'axe.min.js'), 'utf8');
fs.mkdirSync(dir, {recursive: true});

const pause = ms => new Promise(resolve => setTimeout(resolve, ms));
// Defects are collected so every screenshot is still produced; the run fails at the end.
const failures = [];
const check = (ok, message) => { if (!ok) failures.push(message); };
const shot = name => path.join(dir, name + '.png');

const MODES = [
  {name: 'system-dark', scheme: 'dark', cookie: null, theme: 'system', resolved: 'dark'},
  {name: 'system-light', scheme: 'light', cookie: null, theme: 'system', resolved: 'light'},
  // A forced theme is always checked against the opposite OS preference.
  {name: 'forced-light', scheme: 'dark', cookie: 'light', theme: 'light', resolved: 'light'},
  {name: 'forced-dark', scheme: 'light', cookie: 'dark', theme: 'dark', resolved: 'dark'},
];
const PAGES = [
  {key: 'catalog', url: '/katalog'},
  {key: 'detail', url: '/katalog/visual-product-45'},
  {key: 'cart', url: '/korpa'},
  {key: 'legal', url: '/pravne-informacije'},
];

// ---- Request pacing: a sliding minute below the real public limiters
// (browse 120/min, images 300/min, theme 20/min).
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
    if (sent.html.length + html <= 100 && sent.img.length + img <= 270) return;
    await pause(1000);
  }
}
const imagesFor = url => (url.startsWith('/katalog') && !url.startsWith('/katalog/visual') ? 22 : 2);

function watch(page, problems) {
  // Without a configured favicon the browser's automatic /favicon.ico 404 is not a page defect.
  const ignored = url => /\/favicon\.ico$/.test(url || '');
  page.on('console', msg => {
    // Playwright's own screenshot helper stylesheet (caret/animation
    // hiding) is refused by the strict CSP in WebKit; that is the harness,
    // not the page.
    if (/Refused to apply a stylesheet/.test(msg.text()) && page.context().browser().browserType().name() === 'webkit') return;
    if (msg.type() === 'error' && !ignored(msg.location().url)) problems.push('console: ' + msg.text().slice(0, 200));
  });
  page.on('response', res => {
    if (ignored(res.url())) return;
    if (res.status() === 429) problems.push('rate limited: ' + res.url());
    else if (res.status() >= 400 && res.url().startsWith(base)) problems.push('HTTP ' + res.status() + ' ' + res.url());
  });
  track(page);
}

async function open(page, url, label, problems, checkImages = true) {
  await budget(1, imagesFor(url));
  const response = await page.goto(base + url, {waitUntil: 'networkidle', timeout: 45000});
  assert.equal(response.status(), 200, label + ' HTTP');
  const csp = response.headers()['content-security-policy'] || '';
  const nonce = (/(?:^|; )script-src 'nonce-([A-Za-z0-9+\/]{22}==)'; /.exec(csp) || [])[1];
  assert(nonce && csp.includes("script-src-elem 'nonce-" + nonce + "';") && csp.includes("script-src-attr 'none'")
    && !/unsafe-inline|unsafe-eval|strict-dynamic/.test(csp), label + ' strict nonce CSP ' + csp);
  if (await page.evaluate(() => typeof document.scripts === 'object').catch(() => false)) {
    const runnable = await page.evaluate(() => [...document.scripts].filter(s => s.type !== 'application/ld+json')
      .map(s => ({src: s.src, nonce: s.nonce, theme: s.textContent.includes('shop_theme')})));
    check(runnable.length === 1 && runnable[0].src === '' && runnable[0].nonce === nonce && runnable[0].theme,
      label + ' exactly one nonce theme script ' + JSON.stringify(runnable));
  }
  check((response.headers()['vary'] || '').toLowerCase().includes('cookie'), label + ' Vary: Cookie');
  // Bring every lazy image into view once so full-page shots show them.
  const height = await page.evaluate(() => document.documentElement.scrollHeight);
  for (let y = 0; y < height; y += 500) {
    await page.evaluate(top => window.scrollTo({top, behavior: 'instant'}), y);
    await pause(100);
  }
  await page.waitForLoadState('networkidle');
  await page.evaluate(() => window.scrollTo({top: 0, behavior: 'instant'}));
  await page.waitForLoadState('networkidle');
  await page.waitForFunction(() => [...document.images].every(img => img.complete), null, {timeout: 20000})
    .catch(() => {});
  const dims = await page.evaluate(() => ({w: window.innerWidth, sw: document.documentElement.scrollWidth}));
  check(dims.sw <= dims.w + 1, label + ' horizontal overflow ' + JSON.stringify(dims));
  const broken = await page.evaluate(() => [...document.images]
    .filter(img => img.getClientRects().length && (!img.complete || img.naturalWidth === 0)).map(img => img.getAttribute('src')));
  check(!checkImages || broken.length === 0, label + ' broken images ' + JSON.stringify(broken));
  const overflowing = await page.evaluate(() => [...document.querySelectorAll('.shop-product, .shop-filters, .shop-chip, .shop-pagination, .shop-nav, .shop-theme')]
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
  await page.screenshot({path: shot(file), animations: 'disabled'});
  const vw = await page.evaluate(() => window.innerWidth);
  await page.screenshot({path: shot(file + '-zoom'), clip: {x: vw - 140, y: ih - 140, width: 140, height: 140}, animations: 'disabled'});
}

// Theme attribute, resolved colour scheme, pressed switch button, and the
// logo/favicon variant that must be visible for that theme.
async function assertBranding(page, mode, logo, label) {
  const st = await page.evaluate(() => {
    const de = document.documentElement;
    const shown = el => !!el && el.getClientRects().length > 0 && getComputedStyle(el).display !== 'none';
    const imgs = sel => [...document.querySelectorAll(sel)].filter(shown).map(img => ({
      src: img.getAttribute('src'), ok: img.complete && img.naturalWidth > 0,
      h: Math.round(img.getBoundingClientRect().height), alt: img.getAttribute('alt'),
    }));
    return {
      theme: de.dataset.theme,
      scheme: getComputedStyle(de).colorScheme,
      pressed: [...document.querySelectorAll('.shop-theme-option[aria-pressed="true"]')].map(b => b.value),
      buttons: [...document.querySelectorAll('.shop-theme-option')].map(b => {
        const r = b.getBoundingClientRect();
        return Math.min(Math.round(r.width), Math.round(r.height));
      }),
      group: Math.round((document.querySelector('.shop-theme-group') || document.body).getBoundingClientRect().width),
      names: [...document.querySelectorAll('.shop-theme-option')].map(b => b.getAttribute('aria-label') + '|' + b.getAttribute('title')),
      labelsShown: [...document.querySelectorAll('.shop-theme-label')].filter(l => l.getBoundingClientRect().width > 2).length,
      logos: imgs('.shop-brand .shop-logo'),
      footer: imgs('.shop-footer-logo'),
      mark: shown(document.querySelector('.shop-brand-mark')),
      icon: (document.querySelector('link[rel="icon"]') || {getAttribute: () => null}).getAttribute('href'),
    };
  });
  check(st.theme === mode.theme, label + ' data-theme ' + st.theme);
  check(st.scheme === mode.resolved, label + ' resolved colour scheme ' + st.scheme);
  check(st.pressed.length === 1 && st.pressed[0] === mode.theme, label + ' pressed theme button ' + JSON.stringify(st.pressed));
  check(st.buttons.length === 3 && st.buttons.every(size => size >= 44), label + ' theme buttons 44px ' + JSON.stringify(st.buttons));
  // Icon only: no visible text, compact group, Serbian names and tooltips.
  check(st.labelsShown === 0 && st.group <= 150, label + ' icon-only compact theme group ' + st.group + 'px labels=' + st.labelsShown);
  check(st.names.join() === 'Sistemska tema|Sistemska tema,Svetla tema|Svetla tema,Tamna tema|Tamna tema', label + ' theme button names ' + st.names.join());
  const variant = 'visual-logo-' + mode.resolved + '.png';
  if (logo) {
    check(st.logos.length === 1 && st.logos[0].src.endsWith('/storage/site-assets/' + variant) && st.logos[0].ok
      && st.logos[0].h >= 24 && st.logos[0].h <= 44 && st.logos[0].alt === 'AP.Computers',
    label + ' header logo variant ' + JSON.stringify(st.logos));
    check(st.footer.length === 1 && st.footer[0].src.endsWith(variant) && st.footer[0].ok, label + ' footer logo ' + JSON.stringify(st.footer));
    check(!st.mark, label + ' brand mark hidden when a logo is set');
    check(st.icon === '/storage/site-assets/visual-favicon.png', label + ' favicon ' + st.icon);
  } else {
    check(st.logos.length === 0 && st.footer.length === 0 && st.mark && st.icon === null, label + ' built-in brand mark without a logo ' + JSON.stringify(st));
  }
}

// Measured text contrast against the real rendered backdrop. Collects every
// visible text run and its colour, makes all text and images transparent,
// takes a full-page screenshot and compares each text colour with the
// screenshot pixels under that text (3rd-percentile worst case). Translucent
// glass, ambient glows, gradients and the sticky header are all measured as
// painted. Inputs and selects (opaque fields) are checked analytically.
async function measureContrast(page, label) {
  await page.evaluate(() => {
    window.scrollTo({top: 0, behavior: 'instant'});
    if (document.activeElement && document.activeElement.blur) document.activeElement.blur();
  });
  const runs = await page.evaluate(() => {
    const parse = c => {
      const m = /rgba?\(([^)]+)\)/.exec(c || '');
      if (!m) return null;
      const p = m[1].split(/[\s,/]+/).filter(Boolean).map(Number);
      return [p[0], p[1], p[2], p.length > 3 ? p[3] : 1];
    };
    const out = {texts: [], fields: [], overImage: []};
    const sx = window.scrollX;
    const sy = window.scrollY;
    const images = [...document.images].filter(img => img.getClientRects().length).map(img => img.getBoundingClientRect());
    const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
    const seen = new Map();
    for (let node = walker.nextNode(); node; node = walker.nextNode()) {
      if (!node.nodeValue.trim()) continue;
      const el = node.parentElement;
      // Screen-reader-only text is clipped to 1px but its Range rects are not.
      // The skip link (off-screen until focused) and the back-to-top arrow
      // (fixed, outside the document flow) are checked by their own focus tests.
      if (!el || el.closest('script,style,noscript,option,select,textarea,title,.sr-only,.skip,.shop-back-to-top,.shop-top-rail')) continue;
      // Punctuation alone (a comma after an inline chip) has a glyph box that
      // takes in the neighbour's border; it is read as part of its sentence.
      if (!/[\p{L}\p{N}]/u.test(node.nodeValue)) continue;
      const box = el.getBoundingClientRect();
      if (box.width <= 2 || box.height <= 2) continue;
      const cs = getComputedStyle(el);
      if (cs.visibility !== 'visible' || parseFloat(cs.opacity) < 0.05) continue;
      const range = document.createRange();
      range.selectNodeContents(node);
      const rects = [...range.getClientRects()].filter(r => r.width >= 2 && r.height >= 6)
        .map(r => ({x: r.left + sx, y: r.top + sy, w: r.width, h: r.height}));
      if (!rects.length) continue;
      let colors = [parse(cs.color)];
      if (colors[0] && colors[0][3] === 0 && /text/.test(cs.backgroundClip + ' ' + cs.webkitBackgroundClip)) {
        colors = (cs.backgroundImage.match(/rgba?\([^)]+\)/g) || []).map(parse);
      }
      colors = colors.filter(c => c && c[3] > 0);
      if (!colors.length) continue;
      const size = parseFloat(cs.fontSize);
      const large = size >= 24 || (size >= 18.66 && parseInt(cs.fontWeight, 10) >= 700);
      for (const r of rects) {
        for (const img of images) {
          const ix = Math.min(r.x + r.w, img.right + sx) - Math.max(r.x, img.left + sx);
          const iy = Math.min(r.y + r.h, img.bottom + sy) - Math.max(r.y, img.top + sy);
          if (ix > 2 && iy > 2) out.overImage.push(node.nodeValue.trim().slice(0, 40));
        }
      }
      const id = seen.get(el) ?? seen.size;
      seen.set(el, id);
      const name = el.tagName.toLowerCase() + (el.className && typeof el.className === 'string' ? '.' + el.className.trim().split(/\s+/).join('.') : '');
      out.texts.push({name, text: node.nodeValue.trim().slice(0, 40), colors, need: large ? 3 : 4.5, rects});
    }
    for (const el of document.querySelectorAll('input:not([type=hidden]):not([type=checkbox]):not([type=radio]),select,textarea')) {
      if (!el.getClientRects().length) continue;
      const cs = getComputedStyle(el);
      out.fields.push({name: el.tagName.toLowerCase() + '#' + (el.id || el.name), color: parse(cs.color), bg: parse(cs.backgroundColor),
        placeholder: el.placeholder ? parse(getComputedStyle(el, '::placeholder').color) : null});
    }
    // Hide every glyph and image; layout is unchanged (colour only).
    for (const el of document.querySelectorAll('body *')) {
      el.style.setProperty('color', 'transparent', 'important');
      el.style.setProperty('-webkit-text-fill-color', 'transparent', 'important');
      el.style.setProperty('text-shadow', 'none', 'important');
      el.style.setProperty('caret-color', 'transparent', 'important');
      if (getComputedStyle(el).backgroundClip === 'text' || getComputedStyle(el).webkitBackgroundClip === 'text') {
        el.style.setProperty('background-image', 'none', 'important');
      }
      if (/^(IMG|SVG|svg)$/.test(el.tagName)) el.style.setProperty('visibility', 'hidden', 'important');
    }
    // The fixed back-to-top button would be painted over footer text in a
    // full-page capture; it is never part of the measured text backdrop.
    for (const el of document.querySelectorAll('.shop-back-to-top,.shop-top-rail')) el.style.setProperty('visibility', 'hidden', 'important');
    return out;
  });
  await pause(120);
  const png = await page.screenshot({fullPage: true, animations: 'disabled'});
  const result = await page.evaluate(async ({b64, texts}) => {
    const img = new Image();
    img.src = 'data:image/png;base64,' + b64;
    await img.decode();
    const canvas = document.createElement('canvas');
    canvas.width = img.naturalWidth;
    canvas.height = img.naturalHeight;
    const ctx = canvas.getContext('2d', {willReadFrequently: true});
    ctx.drawImage(img, 0, 0);
    const scale = img.naturalWidth / document.documentElement.clientWidth;
    const lin = v => { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); };
    const lum = (r, g, b) => 0.2126 * lin(r) + 0.7152 * lin(g) + 0.0722 * lin(b);
    const fails = [];
    let min = Infinity;
    let lowest = null;
    for (const t of texts) {
      let worst = Infinity;
      for (const c of t.colors) {
        const ratios = [];
        for (const r of t.rects) {
          const x0 = Math.max(0, Math.floor(r.x * scale));
          const y0 = Math.max(0, Math.floor(r.y * scale));
          const w = Math.min(canvas.width - x0, Math.ceil(r.w * scale));
          const h = Math.min(canvas.height - y0, Math.ceil(r.h * scale));
          if (w < 1 || h < 1) continue;
          const data = ctx.getImageData(x0, y0, w, h).data;
          for (let i = 0; i < data.length; i += 8) {
            const a = c[3];
            const fr = c[0] * a + data[i] * (1 - a);
            const fg = c[1] * a + data[i + 1] * (1 - a);
            const fb = c[2] * a + data[i + 2] * (1 - a);
            const l1 = lum(fr, fg, fb);
            const l2 = lum(data[i], data[i + 1], data[i + 2]);
            ratios.push((Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05));
          }
        }
        if (ratios.length < 4) continue;
        ratios.sort((p, q) => p - q);
        worst = Math.min(worst, ratios[Math.floor(ratios.length * 0.03)]);
      }
      if (worst === Infinity) continue;
      if (worst < min) lowest = t.name + ' "' + t.text + '"';
      min = Math.min(min, worst);
      if (worst < t.need) fails.push(t.name + ' "' + t.text + '" ' + worst.toFixed(2) + '<' + t.need);
    }
    return {checked: texts.length, min: min === Infinity ? null : Number(min.toFixed(2)), lowest, fails};
  }, {b64: png.toString('base64'), texts: runs.texts});
  const lumOf = c => [c[0], c[1], c[2]].map(v => { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); })
    .reduce((s, v, i) => s + v * [0.2126, 0.7152, 0.0722][i], 0);
  const ratio = (a, b) => { const x = lumOf(a); const y = lumOf(b); return (Math.max(x, y) + 0.05) / (Math.min(x, y) + 0.05); };
  for (const f of runs.fields) {
    if (!f.color || !f.bg || f.bg[3] < 1) { failures.push(label + ' field without an opaque background ' + f.name); continue; }
    check(ratio(f.color, f.bg) >= 4.5, label + ' field text ' + f.name + ' ' + ratio(f.color, f.bg).toFixed(2));
    if (f.placeholder) check(ratio(f.placeholder, f.bg) >= 4.5, label + ' placeholder ' + f.name + ' ' + ratio(f.placeholder, f.bg).toFixed(2));
  }
  check(runs.overImage.length === 0, label + ' text over an image ' + JSON.stringify(runs.overImage.slice(0, 5)));
  check(result.checked > 5, label + ' contrast measured too few text runs ' + result.checked);
  check(result.fails.length === 0, label + ' measured contrast ' + JSON.stringify(result.fails.slice(0, 8)));
  console.log('GLASS_CONTRAST ' + label + ' runs=' + result.checked + ' min=' + result.min + ' fails=' + result.fails.length + ' lowest=' + result.lowest);
}

// Non-text contrast of the three theme icons (WCAG 1.4.11, 3:1): the icon
// colour against the pixels of its own button with the icons hidden.
async function iconContrast(page, label) {
  await page.evaluate(() => {
    window.scrollTo({top: 0, behavior: 'instant'});
    if (document.activeElement && document.activeElement.blur) document.activeElement.blur();
  });
  await page.mouse.move(1, 1);
  const icons = await page.evaluate(() => [...document.querySelectorAll('.shop-theme-option svg')].map(svg => {
    const r = svg.getBoundingClientRect();
    const m = /rgba?\(([^)]+)\)/.exec(getComputedStyle(svg).color);
    svg.style.setProperty('visibility', 'hidden', 'important');
    return {value: svg.closest('button').value, pressed: svg.closest('button').getAttribute('aria-pressed'),
      x: r.left, y: r.top, w: r.width, h: r.height, color: m ? m[1].split(/[\s,\/]+/).filter(Boolean).map(Number) : null};
  }));
  await pause(80);
  const png = await page.screenshot({animations: 'disabled'});
  const out = await page.evaluate(async ({b64, icons}) => {
    for (const svg of document.querySelectorAll('.shop-theme-option svg')) svg.style.removeProperty('visibility');
    const img = new Image();
    img.src = 'data:image/png;base64,' + b64;
    await img.decode();
    const canvas = document.createElement('canvas');
    canvas.width = img.naturalWidth;
    canvas.height = img.naturalHeight;
    const ctx = canvas.getContext('2d', {willReadFrequently: true});
    ctx.drawImage(img, 0, 0);
    const scale = img.naturalWidth / window.innerWidth;
    const lin = v => { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); };
    const lum = (r, g, b) => 0.2126 * lin(r) + 0.7152 * lin(g) + 0.0722 * lin(b);
    return icons.map(i => {
      if (!i.color) return {value: i.value, ratio: 0};
      const a = i.color.length > 3 ? i.color[3] : 1;
      const data = ctx.getImageData(Math.floor(i.x * scale), Math.floor(i.y * scale), Math.max(1, Math.ceil(i.w * scale)), Math.max(1, Math.ceil(i.h * scale))).data;
      const ratios = [];
      for (let k = 0; k < data.length; k += 4) {
        const l1 = lum(i.color[0] * a + data[k] * (1 - a), i.color[1] * a + data[k + 1] * (1 - a), i.color[2] * a + data[k + 2] * (1 - a));
        const l2 = lum(data[k], data[k + 1], data[k + 2]);
        ratios.push((Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05));
      }
      ratios.sort((p, q) => p - q);
      return {value: i.value, pressed: i.pressed, ratio: Number(ratios[Math.floor(ratios.length * 0.03)].toFixed(2))};
    });
  }, {b64: png.toString('base64'), icons});
  console.log('ICON_CONTRAST ' + label + ' ' + out.map(o => o.value + (o.pressed === 'true' ? '*' : '') + '=' + o.ratio).join(' '));
  check(out.length === 3 && out.every(o => o.ratio >= 3), label + ' theme icon contrast ' + JSON.stringify(out));
}

// axe-core colour-contrast. Script injection needs a separate CSP-bypassing
// context; every CSP assertion runs in the strict contexts without it.
async function axeContrast(page, url, label) {
  await budget(1, imagesFor(url));
  await page.goto(base + url, {waitUntil: 'networkidle', timeout: 45000});
  await page.evaluate(src => { (0, eval)(src); }, axeSource).catch(() => {});
  if (!(await page.evaluate(() => typeof window.axe === 'object'))) await page.addScriptTag({content: axeSource});
  const result = await page.evaluate(async () => {
    const r = await window.axe.run(document, {runOnly: ['color-contrast']});
    return {
      violations: r.violations.flatMap(v => v.nodes.map(n => n.target.join(' ') + ' :: ' + ((n.any[0] && n.any[0].message) || ''))),
      incomplete: r.incomplete.reduce((a, v) => a + v.nodes.length, 0),
      passes: r.passes.reduce((a, v) => a + v.nodes.length, 0),
    };
  });
  // Incomplete nodes (text over glass, glows or gradient text) are covered
  // by the pixel measurement above, which checks every text run.
  console.log('AXE_CONTRAST ' + label + ' passes=' + result.passes + ' incomplete=' + result.incomplete + ' violations=' + result.violations.length);
  check(result.violations.length === 0, 'axe contrast ' + label + ' ' + JSON.stringify(result.violations.slice(0, 6)));
  // The icon-only theme buttons: name, ARIA state, contrast and target size.
  const icons = await page.evaluate(async () => {
    const r = await window.axe.run({include: [['.shop-theme']]}, {runOnly: ['button-name', 'aria-allowed-attr', 'aria-valid-attr-value', 'color-contrast', 'target-size', 'nested-interactive']});
    return {violations: r.violations.map(v => v.id + ':' + v.nodes.length), passes: r.passes.map(v => v.id).join(',')};
  });
  console.log('AXE_THEME_BUTTONS ' + label + ' passes=' + icons.passes + ' violations=' + icons.violations.length);
  check(icons.violations.length === 0, 'axe theme buttons ' + label + ' ' + JSON.stringify(icons.violations));
}

// B1149 theme switch. With JavaScript: a click on Svetla/Tamna/Sistem changes
// data-theme, aria-pressed and the colours in place, with no navigation and
// no request at all, writes the plain shop_theme cookie and survives a
// reload. Without JavaScript: the same buttons post to /tema (303 back to
// the same page) and the next page renders the chosen theme.
async function themeSwitch(browserType, engine) {
  const browser = await browserType.launch({headless: true});
  try {
    const context = await browser.newContext({viewport: {width: 1280, height: 900}, colorScheme: 'dark', deviceScaleFactor: 1});
    const page = await context.newPage();
    const problems = [];
    watch(page, problems);
    let navigations = 0;
    const requests = [];
    page.on('framenavigated', frame => { if (frame === page.mainFrame()) navigations++; });
    // A logo variant that becomes visible may lazy-load its image; that is
    // not a page load. Everything else (document, form post, fetch) counts.
    page.on('request', req => { if (req.resourceType() !== 'image') requests.push(req.resourceType() + ' ' + req.method() + ' ' + req.url()); });
    await budget(1, 0);
    await page.goto(base + '/korpa', {waitUntil: 'networkidle'});
    await page.evaluate(() => { window.__b1149SamePage = true; });
    const startNavs = navigations;
    const url = page.url();
    const colours = {};
    for (const theme of ['light', 'dark', 'system', 'light']) {
      const before = requests.length;
      const started = Date.now();
      await page.locator('.shop-theme-option[value="' + theme + '"]').click();
      await page.waitForFunction(t => document.documentElement.dataset.theme === t, theme, {timeout: 2000})
        .catch(() => {});
      const ms = Date.now() - started;
      await pause(300);
      const st = await page.evaluate(() => ({
        theme: document.documentElement.dataset.theme,
        pressed: [...document.querySelectorAll('.shop-theme-option[aria-pressed="true"]')].map(b => b.value),
        scheme: getComputedStyle(document.documentElement).colorScheme,
        meta: document.querySelector('meta[name="color-scheme"]').getAttribute('content'),
        text: getComputedStyle(document.body).color,
        same: window.__b1149SamePage === true,
        cookie: document.cookie,
      }));
      const resolved = theme === 'system' ? 'dark' : theme;
      colours[theme] = st.text;
      check(st.theme === theme && st.pressed.join() === theme && st.scheme === resolved,
        engine + ' instant switch to ' + theme + ' ' + JSON.stringify(st));
      check(st.same && navigations === startNavs && page.url() === url,
        engine + ' switch to ' + theme + ' reloaded or navigated ' + JSON.stringify({same: st.same, navigations, startNavs, url: page.url()}));
      check(requests.length === before, engine + ' switch to ' + theme + ' made requests ' + JSON.stringify(requests.slice(before)));
      check(st.cookie.split('; ').includes('shop_theme=' + theme), engine + ' script cookie ' + st.cookie);
      const cookie = (await context.cookies()).find(c => c.name === 'shop_theme');
      check(cookie && cookie.value === theme && !cookie.httpOnly && !cookie.secure && cookie.path === '/'
        && (engine !== 'chromium' || cookie.sameSite === 'Lax') && cookie.expires > Date.now() / 1000 + 300 * 86400,
      engine + ' shop_theme cookie after ' + theme + ' ' + JSON.stringify(cookie));
      console.log('THEME_INSTANT ' + engine + ' ' + theme + ' applied_in_ms=' + ms + ' requests=' + (requests.length - before) + ' navigations=' + (navigations - startNavs));
      if (theme === 'dark' || (theme === 'light' && colours.dark)) {
        await page.screenshot({path: shot(engine + '-theme-instant-' + theme + '-1280'), animations: 'disabled'});
      }
    }
    check(colours.light !== colours.dark, engine + ' colours changed with the theme ' + JSON.stringify(colours));
    check(!requests.some(r => r.includes('/tema')), engine + ' a request reached /tema with JavaScript on');

    // The choice is kept across a real reload (server renders it, no flash).
    await budget(1, 0);
    const reloaded = await page.reload({waitUntil: 'networkidle'});
    const html = await reloaded.text();
    const after = await page.evaluate(() => ({theme: document.documentElement.dataset.theme, same: window.__b1149SamePage === true,
      pressed: [...document.querySelectorAll('.shop-theme-option[aria-pressed="true"]')].map(b => b.value)}));
    check(html.includes('<html lang="sr" data-theme="light">') && after.theme === 'light' && after.pressed.join() === 'light' && !after.same,
      engine + ' reload keeps the light choice ' + JSON.stringify(after));
    // Keyboard: Space on the focused Tamna button switches in place too.
    await page.locator('.shop-theme-option[value="dark"]').focus();
    await page.keyboard.press('Space');
    await page.waitForFunction(() => document.documentElement.dataset.theme === 'dark', null, {timeout: 2000}).catch(() => {});
    check(await page.evaluate(() => document.documentElement.dataset.theme) === 'dark', engine + ' keyboard switch to dark');
    await page.screenshot({path: shot(engine + '-theme-focus-dark-1280'), clip: {x: 980, y: 0, width: 300, height: 90}, animations: 'disabled'});
    const seen = problems.splice(0);
    check(seen.length === 0, engine + ' theme switch console/CSP problems ' + JSON.stringify(seen.slice(0, 6)));
    await context.close();

    // JavaScript disabled: the form fallback.
    const noJs = await browser.newContext({viewport: {width: 375, height: 800}, colorScheme: 'dark', javaScriptEnabled: false, deviceScaleFactor: 1});
    const np = await noJs.newPage();
    track(np);
    await budget(1, 0);
    await np.goto(base + '/korpa', {waitUntil: 'networkidle'});
    for (const theme of ['light', 'system']) {
      await budget(2, 0);
      const posted = np.waitForResponse(res => res.url() === base + '/tema' && res.request().method() === 'POST', {timeout: 15000});
      const back = np.waitForResponse(res => res.url() === base + '/korpa' && res.request().method() === 'GET', {timeout: 15000});
      await Promise.all([posted, back, np.locator('.shop-theme-option[value="' + theme + '"]').click()]);
      const res = await posted;
      check(res.status() === 303 && res.headers()['location'] === '/korpa', engine + ' no-JS /tema answers 303 to /korpa ' + res.status() + ' ' + res.headers()['location']);
      const body = await (await back).text();
      check(body.includes('<html lang="sr" data-theme="' + theme + '">') && body.includes('value="' + theme + '" class="shop-theme-option" aria-pressed="true"'),
        engine + ' no-JS page renders ' + theme);
      await np.waitForLoadState('networkidle');
      const cookie = (await noJs.cookies()).find(c => c.name === 'shop_theme');
      check(cookie && cookie.value === theme && !cookie.httpOnly && cookie.path === '/', engine + ' no-JS cookie ' + JSON.stringify(cookie));
      if (theme === 'light') await np.screenshot({path: shot(engine + '-theme-nojs-light-375'), animations: 'disabled'});
    }
    console.log('THEME_NOJS_FALLBACK ' + engine + ' PASS');
    await noJs.close();
  } finally {
    await browser.close();
  }
}

async function context(browser, mode, width, extra = {}) {
  const ctx = await browser.newContext({viewport: {width, height: 900}, colorScheme: mode.scheme, deviceScaleFactor: 1, ...extra});
  // The same plain cookie the theme script and /tema write.
  if (mode.cookie) await ctx.addCookies([{name: 'shop_theme', value: mode.cookie, url: base, sameSite: 'Lax'}]);
  return ctx;
}

const summary = [];

// One engine, one logo state: every mode x width x page with screenshots,
// theme/logo assertions and measured contrast, then axe in bypass contexts.
async function matrix(browserType, engine, {widths, pages, logo, extras}) {
  const browser = await browserType.launch({headless: true});
  const tagLogo = logo ? 'logo' : 'nologo';
  try {
    for (const mode of MODES) {
      for (const width of widths) {
        const ctx = await context(browser, mode, width);
        const page = await ctx.newPage();
        const problems = [];
        watch(page, problems);
        const tag = [engine, tagLogo, mode.name, width].join('-');
        for (const p of pages) {
          const label = p.key + ' ' + tag;
          const end = await open(page, p.url, label, problems, engine === 'chromium');
          await assertBranding(page, mode, logo, label);
          if (p.key === 'catalog') {
            await iconContrast(page, label);
            if (width === 375) await page.screenshot({path: shot(tag + '-header-375'), clip: {x: 0, y: 0, width: 375, height: 140}, animations: 'disabled'});
            check(await page.locator('h1').count() === 1, label + ' single h1');
            const alts = await page.locator('.shop-products img').evaluateAll(imgs => imgs.map(img => img.alt));
            check(alts.length > 0 && alts.every(alt => alt.trim().length > 3), label + ' meaningful alt text');
            await page.screenshot({path: shot(tag + '-catalog-fold'), animations: 'disabled'});
          }
          await page.screenshot({path: shot(tag + '-' + p.key), fullPage: true, animations: 'disabled'});
          if (p.key === 'catalog' && end.sh >= 2 * end.ih) {
            // Scrolled viewport: the CSS-only back-to-top button and the glass header over content.
            await page.evaluate(() => window.scrollTo({top: window.innerHeight * 2.6, behavior: 'instant'}));
            await pause(250);
            const top = await page.locator('.shop-back-to-top').boundingBox();
            check(top && top.y > 0 && top.y + top.height <= 900, label + ' back-to-top visible after scrolling ' + JSON.stringify(top));
            await page.screenshot({path: shot(tag + '-catalog-scrolled'), animations: 'disabled'});
            if (extras) await focusTop(page, label, tag + '-focus-top');
          }
          if (p.key === 'cart' && extras) {
            await focusTop(page, label, tag + '-focus-top-short');
            const focusedEnd = await page.evaluate(() => document.documentElement.scrollHeight);
            check(focusedEnd === end.sh, label + ' focusing back-to-top adds no scroll ' + focusedEnd + ' vs ' + end.sh);
            await page.keyboard.press('Tab');
          }
          await measureContrast(page, label);
        }
        if (extras && (width === 375 || width === 1280)) {
          // Filled cart: one real add-to-cart from the catalog.
          await open(page, '/katalog', 'catalog for cart ' + tag, problems, engine === 'chromium');
          await budget(2, 0);
          await Promise.all([page.waitForURL(url => url.pathname === '/korpa', {timeout: 15000}),
            page.locator('.shop-product .shop-button').first().click()]);
          await page.waitForLoadState('networkidle');
          await open(page, '/korpa', 'filled cart ' + tag, problems, engine === 'chromium');
          check(await page.locator('.shop-cart-item').count() === 1, tag + ' filled cart has one row');
          await page.screenshot({path: shot(tag + '-cart-filled'), fullPage: true, animations: 'disabled'});
          await measureContrast(page, 'filled cart ' + tag);
        }
        if (extras && mode.cookie === null && width !== 768) {
          for (const [key, url] of [['page3', '/katalog?page=3'], ['filtered', '/katalog?q=Synthetic&brand_id=1&stock=available&sort=price_asc'], ['empty', '/katalog?q=nepostojeci-artikal']]) {
            const end = await open(page, url, key + ' ' + tag, problems);
            if (key === 'empty') {
              // footerEnd is rounded up from a fractional box edge: allow 1px.
              check(Math.abs(end.sh - Math.max(end.ih, end.footerEnd)) <= 1, 'empty ' + tag + ' page ends at the footer ' + JSON.stringify(end));
              console.log('PAGE_END empty ' + tag + ' ' + JSON.stringify(end));
            }
            await page.screenshot({path: shot(tag + '-catalog-' + key), fullPage: true, animations: 'disabled'});
            await measureContrast(page, key + ' ' + tag);
          }
          if (width === 1280) {
            await open(page, '/katalog', 'hover ' + tag, problems);
            await page.locator('.shop-product').first().hover();
            await pause(700);
            await page.screenshot({path: shot(tag + '-catalog-hover'), animations: 'allow'});
            await page.mouse.move(1, 1);
            for (const [selector, file] of [['.shop-product .shop-button', 'focus-add'], ['.shop-theme-option[aria-pressed="false"]', 'focus-theme']]) {
              await page.locator(selector).first().focus();
              await page.keyboard.press('Shift');
              await pause(150);
              const ring = await page.locator(selector).first().evaluate(el => getComputedStyle(el).outlineStyle + ' ' + getComputedStyle(el).outlineWidth);
              check(ring.startsWith('solid 3px'), tag + ' ' + file + ' focus ring ' + ring);
              await page.screenshot({path: shot(tag + '-' + file), animations: 'disabled'});
            }
          }
        }
        if (extras && width === 430 && mode.cookie === null) {
          // Large phones: the filter selects show their full labels.
          await open(page, '/katalog', 'filters ' + tag, problems);
          const cut = await page.evaluate(() => {
            const ctx2 = document.createElement('canvas').getContext('2d');
            return [...document.querySelectorAll('.shop-filters select')].filter(sel => {
              const cs = getComputedStyle(sel);
              ctx2.font = cs.fontWeight + ' ' + cs.fontSize + ' ' + cs.fontFamily;
              const text = sel.options[sel.selectedIndex].text;
              const room = sel.clientWidth - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight);
              return ctx2.measureText(text).width > room;
            }).map(sel => sel.id);
          });
          check(cut.length === 0, tag + ' truncated select labels ' + JSON.stringify(cut));
          await page.locator('.shop-filter-panel').scrollIntoViewIfNeeded();
          await page.screenshot({path: shot(tag + '-catalog-filters'), animations: 'disabled'});
        }
        summary.push(tag);
        await ctx.close();

        const axeCtx = await context(browser, mode, width, {bypassCSP: true, reducedMotion: 'reduce'});
        const axePage = await axeCtx.newPage();
        track(axePage);
        for (const p of pages) await axeContrast(axePage, p.url, p.key + ' ' + tag);
        await axeCtx.close();
      }
    }
  } finally {
    await browser.close();
  }
}

// Preference fallbacks: more contrast and forced colours (solid surfaces).
async function preferencePass() {
  const browser = await chromium.launch({headless: true});
  try {
    for (const [name, extra] of [['contrast-more', {contrast: 'more'}], ['forced-colors', {forcedColors: 'active'}]]) {
      for (const mode of MODES.slice(0, 2)) {
        let ctx;
        try {
          ctx = await context(browser, mode, 1280, extra);
        } catch (error) {
          console.log('PREFERENCE_PASS_SKIPPED ' + name + ' ' + error.message.slice(0, 120));
          continue;
        }
        const page = await ctx.newPage();
        const problems = [];
        watch(page, problems);
        const tag = 'chromium-logo-' + name + '-' + mode.name + '-1280';
        await open(page, '/katalog', 'catalog ' + tag, problems);
        await iconContrast(page, 'catalog ' + tag);
        await page.screenshot({path: shot(tag + '-catalog-fold'), animations: 'disabled'});
        await page.screenshot({path: shot(tag + '-theme-buttons'), clip: {x: 1000, y: 0, width: 280, height: 90}, animations: 'disabled'});
        await open(page, '/uslovi-prodaje', 'legal ' + tag, problems);
        await page.screenshot({path: shot(tag + '-legal-terms'), animations: 'disabled'});
        if (name === 'contrast-more') {
          const solid = await page.evaluate(() => getComputedStyle(document.querySelector('.legal-article')).backgroundColor);
          check(/^rgb\(/.test(solid), tag + ' opaque surfaces with more contrast ' + solid);
          await measureContrast(page, 'legal ' + tag);
        }
        summary.push(tag);
        await ctx.close();
      }
    }
  } finally {
    await browser.close();
  }
}

function branding(state) {
  const out = cp.execFileSync('php', ['tests/Browser/seed-catalog-visual.php', '--logo=' + state], {encoding: 'utf8'});
  assert(out.includes('CATALOG_VISUAL_BRANDING=' + state), 'branding toggle ' + out);
}

(async () => {
  branding('on');
  await themeSwitch(chromium, 'chromium');
  const all = [375, 430, 768, 1280];
  await matrix(chromium, 'chromium', {widths: all, pages: PAGES, logo: true, extras: true});
  await preferencePass();

  await themeSwitch(webkit, 'webkit');
  // Image loading is covered in Chromium. Headless WebKit defers far lazy
  // images past the quick scroll pass, so this pass checks layout, theme,
  // logo, back-to-top and contrast; any HTTP error still fails.
  await matrix(webkit, 'webkit', {widths: [375, 1280], pages: PAGES, logo: true, extras: false});

  branding('off');
  await matrix(chromium, 'chromium', {widths: [375, 1280], pages: [PAGES[0], PAGES[3]], logo: false, extras: false});
  await matrix(webkit, 'webkit', {widths: [375], pages: [PAGES[0]], logo: false, extras: false});
  branding('on');

  if (failures.length) throw new Error(failures.length + ' defects: ' + failures.join(' | '));
  console.log('CATALOG_VISUAL_QA=PASS ' + summary.length + ' states: ' + summary.join(','));
})().catch(error => { console.error('CATALOG_VISUAL_QA_FAIL ' + error.message); process.exit(1); });
