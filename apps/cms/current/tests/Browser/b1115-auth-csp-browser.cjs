'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const https = require('node:https');
const http = require('node:http');
const cp = require('node:child_process');
const base = 'https://127.0.0.1:8443';
const engine = process.env.B1115_BROWSER_ENGINE;
const proxy = process.argv[2] === '--proxy';
assert.equal(process.env.APP_ENV, 'testing', 'B1115_HOLD_TESTING_ONLY');
assert.equal(process.env.APP_URL, base, 'B1115_HOLD_EXACT_TLS_ORIGIN');
assert.equal(process.env.DB_HOST, '127.0.0.1');
assert.equal(process.env.DB_DATABASE, 'ald1n_test');
assert.equal(process.env.MAIL_MAILER, 'array');

if (proxy) {
  const server = https.createServer({
    key: fs.readFileSync(process.env.B1115_TLS_KEY),
    cert: fs.readFileSync(process.env.B1115_TLS_CERT),
    minVersion: 'TLSv1.2',
  }, (request, response) => {
    if (request.socket.remoteAddress !== '127.0.0.1' || request.headers.host !== '127.0.0.1:8443') {
      response.writeHead(403).end(); return;
    }
    const headers = {...request.headers, host: '127.0.0.1:8443', 'x-b1115-tls': 'loopback-https-fixture'};
    delete headers['x-forwarded-host']; delete headers['x-forwarded-proto']; delete headers.forwarded;
    const upstream = http.request({host: '127.0.0.1', port: 8125, path: request.url, method: request.method, headers}, result => {
      response.writeHead(result.statusCode, result.headers); result.pipe(response);
    });
    upstream.on('error', () => { response.writeHead(502).end(); });
    request.pipe(upstream);
  });
  server.listen(8443, '127.0.0.1', () => console.log('B1115_LOOPBACK_TLS_PROXY_READY'));
} else {
  assert(['chromium', 'firefox'].includes(engine), 'B1115_HOLD_UNSUPPORTED_ENGINE');
  main().catch(() => { console.error('B1115_HARNESS_ERROR'); process.exitCode = 2; });
}

async function main() {
  // Pin/validate the private fixture certificate independently of browser exception handling.
  await new Promise((resolve, reject) => {
    https.get(base+'/login', {ca: fs.readFileSync(process.env.B1115_TLS_CERT), rejectUnauthorized: true}, response => {
      assert(response.socket.encrypted && /^TLSv1\.[23]$/.test(response.socket.getProtocol()));
      response.resume(); response.on('end', resolve);
    }).on('error', reject);
  });
  const {chromium, firefox} = require(path.join(process.env.PLAYWRIGHT_NODE_ROOT, 'node_modules/playwright'));
  const fixture = JSON.parse(cp.execFileSync('php', ['tests/Browser/seed-b1115-auth-csp.php', '--fixture'], {encoding: 'utf8'}));
  const results = [];
  const browser = await ({chromium, firefox}[engine]).launch({headless: true});
  const contexts = [];
  async function check(id, fn) {
    try { await fn(); results.push({id, status: 'PASS'}); console.log('B1115_CASE '+id+' PASS'); }
    catch { results.push({id, status: 'FAIL'}); console.log('B1115_CASE '+id+' FAIL'); }
  }
  async function context() {
    const ctx = await browser.newContext({ignoreHTTPSErrors: true, viewport: {width: 1440, height: 900}});
    contexts.push(ctx);
    // Network is closed except loopback and controlled, harmless provider simulations.
    await ctx.route('**/*', async route => {
      const url = new URL(route.request().url());
      if (url.origin === base) return route.continue();
      if (url.hostname === 'foreign.synthetic.invalid') return route.fulfill({status: 200, contentType: 'application/javascript', body: 'window.__b1115_foreign = true;'});
      if (url.hostname === 'challenges.cloudflare.com' && url.pathname.endsWith('/api.js')) {
        return route.fulfill({status: 200, contentType: 'application/javascript', body:
          "window.__b1115_turnstile = true; const child = document.createElement('script'); child.nonce = document.currentScript.nonce; child.src = 'https://challenges.cloudflare.com/b1115-child.js'; document.head.appendChild(child);"});
      }
      if (url.hostname === 'challenges.cloudflare.com' && url.pathname === '/b1115-child.js') {
        return route.fulfill({status: 200, contentType: 'application/javascript', body: 'window.__b1115_turnstile_child = true;'});
      }
      if (url.hostname === 'www.youtube-nocookie.com') return route.fulfill({status: 200, contentType: 'text/html', body: '<!doctype html><title>Synthetic iframe</title>'});
      return route.abort('blockedbyclient');
    });
    await ctx.addInitScript(() => {
      window.__b1115_violations = [];
      document.addEventListener('securitypolicyviolation', event => {
        window.__b1115_violations.push({directive: event.effectiveDirective, blocked: event.blockedURI});
      });
    });
    return ctx;
  }
  async function open(page, uri) {
    const response = await page.goto(base+uri, {waitUntil: 'networkidle'});
    assert.equal(response.status(), 200);
    assert.equal(new URL(page.url()).origin, base);
    return response;
  }
  async function login(ctx, owner = true) {
    const page = await ctx.newPage();
    await open(page, '/login');
    await page.locator('[name="login"]').fill(owner ? 'b812-staff@example.test' : 'b1115-active@example.test');
    await page.locator('[name="password"]').fill(owner ? 'B812-Owner-Only123!' : 'B1115-Synthetic-Only123!');
    await Promise.all([page.waitForNavigation({waitUntil: 'networkidle'}), page.getByRole('button', {name: 'Prijavi se'}).click()]);
    assert.notEqual(new URL(page.url()).pathname, '/login');
    return page;
  }
  const script = (body, nonce = '') => '<script'+(nonce ? ' nonce="'+nonce+'"' : '')+'>'+body+'</script>';
  async function attack(ctx, uri, id, relaxed = false) {
    const page = await ctx.newPage();
    let headerNonce = null;
    let policy = '';
    await page.route(base+uri, async route => {
      const response = await route.fetch();
      const headers = response.headers();
      policy = headers['content-security-policy'] || '';
      headerNonce = /(?:^|;)\s*script-src 'nonce-([^']+)'(?:;|$)/.exec(policy)?.[1] || null;
      if (relaxed) headers['content-security-policy'] = "base-uri 'none'; object-src 'none'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://foreign.synthetic.invalid";
      const permittedProbe = script(`
        window.__b1115_permitted = true;
        try { eval('window.__b1115_eval = true'); } catch {}
        try { new Function('window.__b1115_function = true')(); } catch {}
      `, headerNonce || 'test-only-calibration');
      const probes = script('window.__b1115_inline = true')
        + '<script src="https://foreign.synthetic.invalid/probe.js"></script>'
        + '<script src="/b1115-untrusted-self.js"></script>'
        + script('window.__b1115_wrong = true', 'wrong-test-nonce')
        + script('window.__b1115_stale = true', 'stale-prior-response')
        + '<button id="b1115-handler" onclick="window.__b1115_handler = true">Synthetic probe</button>'
        + permittedProbe;
      const body = (await response.text()).replace('</body>', probes+'</body>');
      await route.fulfill({response, headers, body});
    });
    await page.route(base+'/b1115-untrusted-self.js', route => route.fulfill({status: 200, contentType: 'application/javascript', body: 'window.__b1115_self = true;'}));
    await open(page, uri);
    await page.locator('#b1115-handler').click();
    // DOM probes contain no nonce, and privileged evaluation never evaluates test code itself.
    await page.evaluate(() => {
      const dynamic = document.createElement('script'); dynamic.textContent = 'window.__b1115_dom = true'; document.body.appendChild(dynamic);
      const button = document.createElement('button'); button.setAttribute('onclick', 'window.__b1115_domhandler = true'); document.body.appendChild(button); button.click();
    });
    await page.waitForTimeout(150);
    const state = await page.evaluate(() => ({
      permitted: window.__b1115_permitted === true,
      markers: Object.fromEntries(['inline', 'foreign', 'self', 'wrong', 'stale', 'handler', 'dom', 'domhandler', 'eval', 'function'].map(key => [key, window['__b1115_'+key] === true])),
      violations: window.__b1115_violations,
      nonces: [...document.querySelectorAll('script:not([src*="b1115"])')].slice(0, 2).map(node => node.nonce),
    }));
    if (relaxed) {
      assert(Object.values(state.markers).every(Boolean), 'B1115_CALIBRATION_DETECTOR_MUST_EXECUTE_ALL_PROBES');
      await page.close(); return;
    }
    await check(id+'-header', async () => { assert(headerNonce); assert(policy.includes("script-src-attr 'none'")); });
    await check(id+'-trusted-probe', async () => { assert(state.permitted); });
    for (const [name, executed] of Object.entries(state.markers)) {
      await check(id+'-refuse-'+name, async () => {
        assert.equal(executed, false);
        const expected = ['handler', 'domhandler'].includes(name) ? 'script-src-attr' : ['eval', 'function'].includes(name) ? 'script-src' : 'script-src-elem';
        assert(state.violations.some(event => event.directive === expected), 'B1115_MATCHING_POLICY_VIOLATION_REQUIRED');
      });
    }
    await page.close();
  }
  try {
    const guest = await context();
    await check('detector-relaxed-fixture-calibration', () => attack(guest, '/login?b1115=calibration', 'calibration', true));
    for (const [id, uri] of [
      ['login', '/login?b1115=login'], ['forgot', '/forgot-password?b1115=forgot'],
      ['reset', '/reset-password/'+'R'.repeat(80)], ['activation', '/activate-account/'+'A'.repeat(80)],
    ]) await attack(guest, uri, id);
    const page = await guest.newPage();
    if (process.env.TURNSTILE_ENABLED === 'true') {
      for (const [id, uri] of [['login', '/login'], ['forgot', '/forgot-password'], ['activation', '/activate-account/'+'A'.repeat(80)]]) {
        await check('turnstile-controlled-api-and-nonce-child-'+id, async () => {
          await open(page, uri);
          assert(await page.evaluate(() => window.__b1115_turnstile === true && window.__b1115_turnstile_child === true));
        });
      }
      return;
    }
    await check('guest-theme-slideshow-and-freshness', async () => {
      const first = await open(page, '/login');
      const nonce = /script-src 'nonce-([^']+)'/.exec(first.headers()['content-security-policy'] || '')?.[1];
      assert(nonce);
      const mode = await page.locator('html').getAttribute('data-theme-mode');
      await page.locator('[data-theme-toggle]').click();
      assert.notEqual(await page.locator('html').getAttribute('data-theme-mode'), mode);
      assert.equal(await page.locator('[data-login-slide]').count(), 2);
      await page.waitForFunction(() => document.querySelectorAll('[data-login-slide]')[1]?.classList.contains('is-active'));
      const next = await page.reload({waitUntil: 'networkidle'});
      const nextNonce = /script-src 'nonce-([^']+)'/.exec(next.headers()['content-security-policy'] || '')?.[1];
      assert(nextNonce && nextNonce !== nonce);
      assert((next.headers()['cache-control'] || '').includes('no-store'));
      assert(await page.evaluate(n => [...document.scripts].every(script => script.nonce === n), nextNonce));
    });
    await check('google-oauth-rendered-entrypoint', async () => {
      await open(page, '/login');
      assert(await page.getByRole('link', {name: 'Nastavi sa Google nalogom'}).isVisible());
      const link = await page.getByRole('link', {name: 'Nastavi sa Google nalogom'}).getAttribute('href');
      assert.equal(new URL(link).pathname, '/auth/google/redirect');
      const response = await guest.request.get(link, {maxRedirects: 0});
      assert.equal(response.status(), 302);
      assert(new URL(response.headers().location).hostname === 'accounts.google.com');
      assert((response.headers()['cache-control'] || '').includes('no-store'));
      assert.equal(response.headers()['referrer-policy'], 'no-referrer');
    });
    await check('youtube-iframe-controlled-rendering', async () => {
      cp.execFileSync('php', ['tests/Browser/seed-b1115-auth-csp.php', '--youtube']);
      await open(page, '/login');
      assert(await page.locator('iframe[src^="https://www.youtube-nocookie.com/embed/"]').isVisible());
      cp.execFileSync('php', ['tests/Browser/seed-b1115-auth-csp.php', '--slideshow']);
    });
    await check('password-reset-real-http-and-one-time-use', async () => {
      await open(page, '/reset-password/'+'R'.repeat(80));
      await page.locator('[name="password"]').fill('B1115-Reset-Only456!');
      await page.locator('[name="password_confirmation"]').fill('B1115-Reset-Only456!');
      await Promise.all([page.waitForURL('**/login'), page.locator('button[type="submit"]').click()]);
      await open(page, '/reset-password/'+'R'.repeat(80));
      assert.equal(await page.locator('[name="password"]').count(), 0);
    });
    await check('activation-real-http-and-one-time-use', async () => {
      await open(page, '/activate-account/'+'A'.repeat(80));
      await page.locator('[name="password"]').fill('B1115-Activate-Only456!');
      await page.locator('[name="password_confirmation"]').fill('B1115-Activate-Only456!');
      await Promise.all([page.waitForURL('**/login'), page.locator('button[type="submit"]').click()]);
      await open(page, '/activate-account/'+'A'.repeat(80));
      assert.equal(await page.locator('[name="password"]').count(), 0);
    });
    const admin = await context();
    const adminPage = await login(admin);
    const secondAdmin = await context();
    await login(secondAdmin);
    await attack(admin, '/admin/catalog/create?b1115=admin', 'admin');
    await check('admin-menu-theme-and-local-module-chain', async () => {
      await open(adminPage, '/account');
      await adminPage.waitForSelector('.site-header.lg-host svg');
      assert(await adminPage.locator('form').first().getAttribute('data-ux-submit-ready') === '1');
      const mode = await adminPage.locator('html').getAttribute('data-theme-mode');
      await adminPage.locator('[data-theme-toggle]').first().click();
      assert.notEqual(await adminPage.locator('html').getAttribute('data-theme-mode'), mode);
      await adminPage.setViewportSize({width: 390, height: 900});
      const toggle = adminPage.locator('[data-mobile-menu-toggle]');
      await toggle.click(); assert.equal(await toggle.getAttribute('aria-expanded'), 'true');
      await adminPage.keyboard.press('Escape'); assert.equal(await toggle.getAttribute('aria-expanded'), 'false');
      await adminPage.setViewportSize({width: 1440, height: 900});
    });
    for (const trigger of ['click', 'enter', 'requestSubmit']) {
      await check('session-confirm-cancel-retry-'+trigger, async () => {
        await open(adminPage, '/account');
        const form = adminPage.locator('form[action$="/account/sessions"]');
        assert.equal(await form.count(), 1);
        let requests = 0;
        const intercept = async route => { requests++; await route.fulfill({status: 204, body: ''}); };
        await adminPage.route(base+'/account/sessions', intercept);
        let prompts = 0;
        const cancel = async dialog => { prompts++; await dialog.dismiss(); };
        adminPage.on('dialog', cancel);
        if (trigger === 'click') await form.locator('button').click();
        if (trigger === 'enter') {
          await form.evaluate(node => { const input = document.createElement('input'); input.name = 'b1115-enter'; node.prepend(input); });
          await form.locator('[name="b1115-enter"]').press('Enter');
        }
        if (trigger === 'requestSubmit') await form.evaluate(node => node.requestSubmit());
        assert.equal(prompts, 1); assert.equal(requests, 0);
        assert.equal(await form.getAttribute('aria-busy'), null);
        assert.equal(await form.locator(':disabled').count(), 0);
        adminPage.off('dialog', cancel);
        adminPage.once('dialog', dialog => dialog.accept());
        await form.evaluate(node => node.requestSubmit(node.querySelector('button')));
        await adminPage.waitForTimeout(150);
        assert.equal(requests, 1);
        assert.equal(await form.getAttribute('aria-busy'), 'true');
        await adminPage.unroute(base+'/account/sessions', intercept);
      });
    }
    await check('bulk-preview-execute-submitter-payload', async () => {
      await open(adminPage, '/admin/catalog/bulk?product_ids[]='+fixture.product);
      const form = adminPage.locator('form').filter({has: adminPage.locator('[name="price_action"]')});
      await form.locator('[name="price_action"]').selectOption('set');
      await form.locator('[name="price_value"]').fill('12500');
      let prompts = 0;
      const cancel = async dialog => { prompts++; await dialog.dismiss(); };
      adminPage.on('dialog', cancel);
      await Promise.all([adminPage.waitForNavigation({waitUntil: 'networkidle'}), form.locator('[name="mode"][value="preview"]').click()]);
      assert.equal(prompts, 0);
      const execute = adminPage.locator('[name="mode"][value="execute"]');
      await execute.evaluate(button => button.form.requestSubmit(button));
      assert.equal(prompts, 1);
      assert.equal(await execute.evaluate(button => button.form.getAttribute('aria-busy')), null);
      adminPage.off('dialog', cancel);
      let payloads = [];
      await adminPage.route(base+'/admin/catalog/bulk', async route => { payloads.push(new URLSearchParams(route.request().postData())); await route.fulfill({status: 204, body: ''}); });
      adminPage.once('dialog', dialog => dialog.accept());
      await execute.evaluate(button => button.form.requestSubmit(button));
      await adminPage.waitForTimeout(150);
      assert.equal(payloads.length, 1);
      assert.equal(payloads[0].get('mode'), 'execute');
      assert.equal(payloads[0].get('product_ids[]'), String(fixture.product));
      assert(payloads[0].get('_token'));
      await adminPage.unroute(base+'/admin/catalog/bulk');
    });
    for (const suffix of ['/edit', '/images']) await check('image-external-submit-confirm-'+suffix.slice(1), async () => {
      await open(adminPage, '/admin/catalog/'+fixture.product+suffix);
      const form = adminPage.locator('#delete-'+fixture.image);
      const button = adminPage.locator('button[form="delete-'+fixture.image+'"]');
      assert.equal(await form.count(), 1); assert.equal(await button.count(), 1);
      let prompts = 0;
      const cancel = async dialog => { prompts++; await dialog.dismiss(); };
      adminPage.on('dialog', cancel);
      await button.click(); assert.equal(prompts, 1);
      assert.equal(await form.getAttribute('aria-busy'), null);
      adminPage.off('dialog', cancel);
    });
    await check('native-validation-unchanged', async () => {
      await open(adminPage, '/admin/catalog/create');
      const form = adminPage.locator('form[data-ux-sticky-actions]').first();
      let mutations = 0;
      const listener = request => { if (request.method() === 'POST') mutations++; };
      adminPage.on('request', listener);
      await form.evaluate(node => node.requestSubmit());
      assert.equal(mutations, 0); assert.equal(await form.getAttribute('aria-busy'), null);
      assert(await form.evaluate(node => !node.checkValidity()));
      adminPage.off('request', listener);
    });
    await check('product-media-and-correlated-filter-execution', async () => {
      await open(adminPage, '/admin/catalog/'+fixture.product+'/edit');
      assert(await adminPage.locator('[data-product-edit-ready]').isVisible());
      assert(await adminPage.locator('[data-product-images-ready], [data-image-sortable]').count() > 0);
      await open(adminPage, '/catalog');
      assert(await adminPage.locator('form[data-ux-submit-ready="1"]').count() > 0);
    });
    const buyer = await context();
    const buyerPage = await login(buyer, false);
    await check('other-role-admin-denial-unchanged', async () => {
      const response = await buyerPage.goto(base+'/admin/settings/system-health', {waitUntil: 'networkidle'});
      assert.equal(response.status(), 403);
    });
    if (process.env.TURNSTILE_ENABLED === 'true') await check('turnstile-controlled-api-and-nonce-child-execute', async () => {
      await open(page, '/login');
      assert(await page.evaluate(() => window.__b1115_turnstile === true && window.__b1115_turnstile_child === true));
    });
    else await check('turnstile-disabled-has-no-provider-tag', async () => {
      await open(page, '/login');
      assert.equal(await page.locator('script[src*="challenges.cloudflare.com"]').count(), 0);
    });
  } finally {
    for (const ctx of contexts) await ctx.close();
    await browser.close();
    const output = process.env.B1115_ARTIFACT_DIR;
    fs.mkdirSync(output, {recursive: true});
    const report = {schema: 1, engine, turnstile: process.env.TURNSTILE_ENABLED === 'true' ? 'controlled-on' : 'off',
      transport: 'actual-loopback-TLS-private-certificate-Node-CA-validated-browser-exception',
      providerUat: false, source: process.env.GITHUB_SHA, results,
      passed: results.filter(result => result.status === 'PASS').length,
      failed: results.filter(result => result.status === 'FAIL').length};
    fs.writeFileSync(path.join(output, engine+'-'+report.turnstile+'.json'), JSON.stringify(report, null, 2)+'\n', {flag: 'wx'});
    console.log('B1115_RESULT '+engine+' PASS='+report.passed+' FAIL='+report.failed);
    if (report.failed) process.exitCode = 1;
  }
}
