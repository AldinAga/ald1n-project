'use strict';
/** B1047 real Chromium+Firefox browser acceptance on disposable MariaDB only. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {execFileSync} = require('node:child_process');

const base = 'http://127.0.0.1:8134';
const root = process.env.PLAYWRIGHT_NODE_ROOT;
const out = process.env.B1047_SCREEN_DIR;
if (process.env.APP_ENV !== 'testing' ||
    process.env.APP_URL !== base ||
    process.env.DB_HOST !== '127.0.0.1' ||
    process.env.DB_DATABASE !== 'ald1n_test' ||
    process.env.MAIL_MAILER !== 'array' ||
    process.env.PUBLIC_STOREFRONT_ENABLED !== 'true' ||
    process.env.PUBLIC_CHECKOUT_ENABLED !== 'false' || !root || !out) {
  throw new Error('HOLD_B1047_LOCALHOST_TEST_ONLY');
}
const {chromium, firefox} = require(path.join(root, 'node_modules', 'playwright'));
fs.mkdirSync(out, {recursive: true});

async function documentIsSafe(page, label) {
  const info = await page.evaluate(() => ({
    viewport: innerWidth,
    scrollWidth: document.documentElement.scrollWidth,
    styleSheets: [...document.styleSheets].some(x => String(x.href || '').includes('ald1n-public-storefront.css')),
    forms: [...document.querySelectorAll('form')].map(f => (f.getAttribute('action') || '').toLowerCase()),
    links: [...document.querySelectorAll('a')].map(a => (a.getAttribute('href') || '').toLowerCase()),
    buttons: [...document.querySelectorAll('button')].map(b => b.textContent.trim()),
    body: document.body.innerText,
  }));
  assert(info.scrollWidth <= info.viewport + 2, label + ' horizontal overflow '+JSON.stringify(info));
  assert(info.styleSheets, label + ' CSS not loaded');
  assert(!info.links.some(x => /korpa|porucivanje|registracija/.test(x)), label + ' forbidden link');
  assert(!info.forms.some(x => /korpa|porucivanje|registracija/.test(x)), label + ' forbidden form');
  assert(!info.buttons.some(x => /dodaj u korpu|kupi|naruci|naruči/i.test(x)), label + ' forbidden purchase CTA');
  assert(!info.body.includes('B1047 Secret Archived'), label + ' archived product leak');
  assert(!info.body.includes('B1047 Secret Draft'), label + ' draft product leak');
  assert(!info.body.includes('B1047 Secret Unpriced'), label + ' unpriced product leak');
  assert(!info.body.includes('8791,01') && !info.body.includes('11998,27'), label + ' cost/commission leak');
}

async function checkStatus(page, uri, status, label) {
  const response = await page.goto(base + uri, {waitUntil: 'networkidle', timeout: 30000});
  assert(response, label + ' no response');
  assert.equal(response.status(), status, label + ' unexpected HTTP status');
  return response;
}

async function runBrowser(engine, label) {
  const browser = await engine.launch({headless: true});
  try {
    for (const [name, width, height] of [['desktop',1440,900], ['mobile',390,844], ['compact',320,700]]) {
      const context = await browser.newContext({viewport: {width,height}, locale:'sr-RS'});
      const page = await context.newPage();
      const prefix = label + '_' + name;
      try {
        await checkStatus(page, '/katalog', 200, prefix+' catalog');
        await page.getByRole('heading',{name:'Pronađi svoj sledeći uređaj.'}).waitFor();
        await page.getByRole('link',{name:'B1047 Synthetic Laptop'}).first().waitFor();
        await documentIsSafe(page, prefix+' catalog');
        await page.screenshot({path: path.join(out,prefix+'-catalog.png'), fullPage:true});
        await checkStatus(page, '/katalog/b1047-laptop', 200, prefix+' detail');
        await page.getByRole('heading',{name:'B1047 Synthetic Laptop'}).waitFor();
        await documentIsSafe(page, prefix+' detail');
        const image = page.locator('.shop-detail-image img');
        assert.equal(await image.count(),1,prefix+' visible product image missing');
        const rel = await image.getAttribute('src');
        assert(/^\/katalog\/slika\/[0-9]+$/.test(rel), prefix+' unexpected media src '+rel);
        const media = await context.request.get(base + rel);
        assert.equal(media.status(),200,prefix+' image GET');
        assert((media.headers()['content-type'] || '').startsWith('image/png'),prefix+' media MIME');
        const cc = (media.headers()['cache-control'] || '').toLowerCase();
        assert(cc.includes('private') && cc.includes('no-store') && !cc.includes('public'),prefix+' media cache policy '+cc);
        await page.screenshot({path: path.join(out,prefix+'-detail.png'), fullPage:true});
        await checkStatus(page, '/katalog?q=B1047%20Synthetic%20Laptop', 200, prefix+' search');
        assert(await page.getByRole('link',{name:'B1047 Synthetic Laptop'}).count() > 0,prefix+' search missing result');
        assert.equal(await page.getByRole('link',{name:'B1047 Synthetic Monitor'}).count(),0,prefix+' search leaked unrelated');
        await checkStatus(page, '/katalog?stock=available', 200, prefix+' stock filter');
        assert.equal(await page.getByRole('link',{name:'B1047 Synthetic Monitor'}).count(),0,prefix+' stock filter leaked zero-stock item');
        for(const uri of ['/korpa','/porucivanje','/registracija']){
          await checkStatus(page,uri,404,prefix+' forbidden '+uri);
        }
        console.log('B1047_BROWSER_'+prefix.toUpperCase()+'=PASS_CATALOG_DETAIL_FILTER_IMAGE_NO_BUY');
      } finally { await context.close(); }
    }
  } finally { await browser.close(); }
}

(async () => {
  await runBrowser(chromium, 'chromium');
  await runBrowser(firefox, 'firefox');
  execFileSync('php',['tests/Browser/b1047_seed_catalog.php','--off'],{cwd:process.cwd(),env:process.env,stdio:'inherit'});
  for (const [engine,label] of [[chromium,'chromium'],[firefox,'firefox']]) {
    const browser = await engine.launch({headless:true});
    try {
      const context = await browser.newContext();
      try {
        for (const uri of ['/katalog','/katalog/b1047-laptop']) {
          const response = await context.request.get(base+uri);
          assert.equal(response.status(),404,label+' emergency OFF '+uri);
        }
        console.log('B1047_BROWSER_'+label.toUpperCase()+'_EMERGENCY_OFF=PASS_404');
      } finally {await context.close();}
    } finally {await browser.close();}
  }
  console.log('B1047_REAL_CHROMIUM_FIREFOX=PASS_6_RESPONSIVE_CASES_12_SCREENSHOTS');
})().catch(error => {console.error('HOLD_B1047_BROWSER:'+error.message);process.exitCode = 1;});
