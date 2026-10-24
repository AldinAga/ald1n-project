'use strict';

/**
 * B812 disposable localhost Chromium buyer acceptance.
 * SMTP is deliberately NOT used. The activation link below is synthetic,
 * and --mock-delivery substitutes its hash only after real HTTP signup.
 * Historical B799 guest-success tests are not altered or bypassed.
 */
const assert=require('node:assert/strict');
const fs=require('node:fs');
const path=require('node:path');
const {execFileSync}=require('node:child_process');

const base=process.env.B799_BROWSER_BASE_URL;
const root=process.env.PLAYWRIGHT_NODE_ROOT;
const dir=process.env.B799_BROWSER_ARTIFACT_DIR;
if(process.env.APP_ENV!=='testing'
   ||process.env.DB_DATABASE!=='ald1n_test'
   ||base!=='http://127.0.0.1:8124'||!root||!dir){
  throw new Error('B812_BROWSER_HOLD_LOCALHOST_SYNTHETIC_ONLY');
}
const {chromium}=require(path.join(root,'node_modules','playwright'));
const fixture='tests/Browser/seed-b812-registered-buyer.php';
const buyerEmail='b812-browser-buyer@example.test';
const activationToken='B812'+'x'.repeat(76);
const password='B812-Browser-Password123!';
fs.mkdirSync(dir,{recursive:true});

function php(mode){
  const result=execFileSync('php',[fixture,mode],{
    cwd:process.cwd(),
    env:process.env,
    encoding:'utf8',
    timeout:15000,
    maxBuffer:8192,
  });
  if(!result.startsWith('B812_')) throw new Error('B812_FIXTURE_UNEXPECTED_REPLY');
  console.log(result.trim());
}
async function openOk(page,where,label){
  const response=await page.goto(base+where,{waitUntil:'networkidle',timeout:30000});
  assert.equal(response.status(),200,label+' HTTP');
  const measure=await page.evaluate(()=>({
    width:window.innerWidth,
    scrollWidth:document.documentElement.scrollWidth,
  }));
  assert(measure.scrollWidth<=measure.width+2,
    label+' horizontal overflow '+JSON.stringify(measure));
  return response;
}
function avoidInternalNumbers(text,label){
  assert(!text.includes('999888'),label+' leaked supplier cost');
  assert(!text.includes('777777'),label+' leaked internal commission');
}

(async()=>{
  const browser=await chromium.launch({headless:true});
  try{
    for(const width of [1440,390]){
      const ctx=await browser.newContext({viewport:{width,height:900}});
      try{
        const page=await ctx.newPage();
        await openOk(page,'/katalog','guest-catalog-'+width);
        avoidInternalNumbers(await page.locator('body').innerText(),'catalog');
        await page.screenshot({
          path:path.join(dir,'b812-catalog-'+width+'.png'),animations:'disabled',
        });
        await openOk(page,'/registracija','registration-'+width);
        assert(await page.locator('button[type="submit"]').filter({
          hasText:'Pošalji registraciju',
        }).isVisible(),'approved synthetic sender enables signup UI');
        await page.screenshot({
          path:path.join(dir,'b812-registration-'+width+'.png'),animations:'disabled',
        });
      }finally{await ctx.close();}
    }

    const ctx=await browser.newContext({viewport:{width:1440,height:900}});
    try{
      const page=await ctx.newPage();
      await openOk(page,'/katalog/b812-browser-laptop','guest-product');
      await page.getByRole('button',{name:'Dodaj u korpu'}).click();
      await page.waitForURL('**/korpa',{timeout:15000});
      assert((await page.locator('body').innerText()).includes('B812 Synthetic Browser Laptop'));
      assert(await page.getByRole('link',{name:'Registruj se'}).isVisible());
      const guestCheckout=await page.goto(base+'/porucivanje',{waitUntil:'domcontentloaded'});
      assert([301,302].includes(guestCheckout.status())||
        new URL(page.url()).pathname==='/login',
        'unregistered buyer must be redirected to login');
      assert.equal(new URL(page.url()).pathname,'/login');

      await openOk(page,'/registracija','guest-signup');
      await page.locator('input[name="first_name"]').fill('B812');
      await page.locator('input[name="last_name"]').fill('Browser Buyer');
      await page.locator('input[name="email"]').fill(buyerEmail);
      await page.locator('input[name="phone"]').fill('0601234567');
      await page.locator('input[name="accepted_privacy"]').check();
      await Promise.all([
        page.waitForResponse(r=>r.url().endsWith('/registracija')
          && r.request().method()==='POST',{timeout:15000}),
        page.getByRole('button',{name:'Pošalji registraciju'}).click(),
      ]);
      await page.waitForURL('**/registracija',{timeout:15000});
      // A real pending Kupac and activation token must already exist.
      // The synthetic hash substitution is strictly a disposable CI mail stand-in.
      php('--mock-delivery');

      const activation=await openOk(page,'/activate-account/'+activationToken,'activation');
      assert.equal(activation.status(),200);
      await page.locator('input[name="password"]').fill(password);
      await page.locator('input[name="password_confirmation"]').fill(password);
      await page.getByRole('button',{name:'Aktiviraj nalog'}).click();
      await page.waitForURL('**/login',{timeout:15000});
      const used=await page.goto(base+'/activate-account/'+activationToken,{
        waitUntil:'domcontentloaded',
      });
      assert.equal(used.status(),200);
      assert((await page.locator('body').innerText()).includes(
        'Aktivacioni link nije važeći'
      ),'used activation link must fail closed');

      await openOk(page,'/login','buyer-login');
      await page.locator('input[name="login"]').fill(buyerEmail);
      await page.locator('input[name="password"]').fill(password);
      await page.getByRole('button',{name:'Prijavi se'}).click();
      await page.waitForURL('**/korpa',{timeout:15000});
      assert((await page.locator('body').innerText()).includes('B812 Synthetic Browser Laptop'),
        'guest cart must survive signup, activation, and login');

      await page.getByRole('link',{name:'Nastavi na poručivanje'}).click();
      await page.waitForURL('**/porucivanje',{timeout:15000});
      const checkoutResponse=await page.reload({waitUntil:'networkidle'});
      assert.equal(checkoutResponse.status(),200);
      assert.equal(await page.locator('input[name="email"]').inputValue(),buyerEmail);
      assert.notEqual(await page.locator('input[name="email"]').getAttribute('readonly'),null);
      avoidInternalNumbers(await page.locator('body').innerText(),'checkout');
      const entries={
        shipping_full_name:'B812 Browser Buyer',
        shipping_phone:'0601234567',
        shipping_address:'Test 12',
        shipping_city:'Beograd',
        shipping_postal_code:'11000',
      };
      for(const [key,value] of Object.entries(entries)){
        await page.locator('input[name="'+key+'"]').fill(value);
      }
      await page.locator('select[name="delivery_method"]').selectOption('courier');
      await page.locator('input[name="accepted_terms"]').check();
      const submit=await page.locator('form.shop-checkout').evaluate(form=>
        Object.fromEntries(new FormData(form))
      );
      assert.equal(submit.email,buyerEmail);
      assert.match(submit.checkout_key,/^[a-f0-9]{48}$/);
      await page.screenshot({
        path:path.join(dir,'b812-checkout.png'),animations:'disabled',
      });
      await page.getByRole('button',{name:'Potvrdi porudžbinu'}).click();
      await page.waitForURL(/\/porudzbina\/[a-f0-9]{64}$/,{timeout:20000});
      const receiptPath=new URL(page.url()).pathname;
      const receipt=await page.reload({waitUntil:'networkidle'});
      assert.equal(receipt.status(),200);
      assert.equal(receipt.headers()['referrer-policy'],'no-referrer');
      assert((receipt.headers()['cache-control']||'').includes('no-store'));
      avoidInternalNumbers(await page.locator('body').innerText(),'receipt');
      await page.screenshot({
        path:path.join(dir,'b812-receipt.png'),animations:'disabled',
      });

      // Real HTTP nonce replay must return the same private capability
      // without a second order, payment, or stock decrement.
      const repeated=await ctx.request.post(base+'/porucivanje',{
        form:submit,
        maxRedirects:0,
        headers:{Accept:'text/html'},
        timeout:25000,
      });
      assert.equal(repeated.status(),302,'duplicate post must replay once-only receipt');
      const repeatedLocation=repeated.headers().location||'';
      assert.equal(new URL(repeatedLocation,base).pathname,receiptPath,
        'nonce replay must not change receipt capability');
      php('--verify');
      console.log('B812_REGISTERED_CHROMIUM=PASS SIGNUP_ACTIVATION_LOGIN_CART_ORDER_REPLAY');
    }finally{await ctx.close();}

    // Role check is a genuinely independent HTTP browser session, not an
    // anonymous guest order simulated through a privileged SuperAdmin.
    const staff=await browser.newContext();
    try{
      const page=await staff.newPage();
      await openOk(page,'/login','owner-login');
      await page.locator('input[name="login"]').fill('b812-staff@example.test');
      await page.locator('input[name="password"]').fill('B812-Owner-Only123!');
      await page.getByRole('button',{name:'Prijavi se'}).click();
      await page.waitForURL('**/dashboard',{timeout:15000});
      const denied=await page.goto(base+'/porucivanje',{waitUntil:'domcontentloaded'});
      assert.equal(denied.status(),403,'staff cannot checkout as registered Kupac');
      console.log('B812_ROLE_HTTP=PASS_STAFF_DENIED');
    }finally{await staff.close();}
  }finally{await browser.close();}
})().catch(e=>{
  console.error('B812_CHROMIUM_FAIL '+String(e.message).replace(/[a-f0-9]{64,}/g,'[REDACTED]'));
  process.exitCode=1;
});
