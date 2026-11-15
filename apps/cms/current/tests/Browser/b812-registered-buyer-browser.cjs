'use strict';

/**
 * B812 disposable localhost Chromium/Firefox buyer acceptance.
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
const engine=process.env.B812_BROWSER_ENGINE||'chromium';
assert(['chromium','firefox'].includes(engine),'B812_BROWSER_HOLD_UNSUPPORTED_ENGINE');
const browserType=require(path.join(root,'node_modules','playwright'))[engine];
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
  if(where.startsWith('/katalog')){
    // First match = the card / gallery photo; the product page also holds a
    // hidden enlarge-dialog copy of the same file later in the DOM.
    const image=page.locator('img[src^="/katalog/slika/"]').first();
    await image.scrollIntoViewIfNeeded();
    await image.evaluate(img=>img.decode());
    assert.deepEqual(await image.evaluate(img=>({
      complete:img.complete,width:img.naturalWidth,height:img.naturalHeight,
    })),{complete:true,width:320,height:240},label+' decoded PNG');
    const src=await image.getAttribute('src');
    assert.match(src,/^\/katalog\/slika\/\d+$/);
    const media=await page.context().request.get(base+src);
    assert.equal(media.status(),200,label+' image HTTP');
    assert.match(media.headers()['content-type'],/^image\/png/);
    assert.match(media.headers()['cache-control'],/private/);
    assert.match(media.headers()['cache-control'],/no-store/);
  }
  return response;
}
function avoidInternalNumbers(text,label){
  assert(!text.includes('999888'),label+' leaked supplier cost');
  assert(!text.includes('777777'),label+' leaked internal commission');
}

/**
 * Registration page QA (login visual shell): 360/768/1280 in light and dark,
 * no horizontal overflow, text contrast >= 4.5 on the glass even over a
 * worst-case (white or black) background, focus order, error state.
 */
async function registrationQa(browser){
  const fields=['first_name','last_name','address','city','phone','email'];
  for(const mode of ['default','image','slideshow','youtube']){
  execFileSync('php',['tests/Browser/set-login-background.php',mode],{cwd:process.cwd(),env:process.env,encoding:'utf8',timeout:15000});
  for(const theme of ['dark','light']){
    for(const width of [360,768,1280]){
      const ctx=await browser.newContext({viewport:{width,height:900}});
      try{
        await ctx.addCookies([{name:'shop_theme',value:theme,url:base}]);
        const page=await ctx.newPage();
        const label='reg-'+mode+'-'+theme+'-'+width;
        const nav=await openOk(page,'/registracija',label);
        const csp=nav.headers()['content-security-policy']||'';
        const frames=await page.locator('iframe').count();
        const video='frame-src https://challenges.cloudflare.com https://www.youtube-nocookie.com';
        if(mode==='youtube'){assert.equal(frames,1,label+' iframe');assert(csp.includes(video),label+' csp video host');const f=page.locator('iframe');assert.equal(await f.getAttribute('tabindex'),'-1');assert.equal(await f.getAttribute('title'),'');assert.match(await f.getAttribute('src'),/^https:\/\/www\.youtube-nocookie\.com\/embed\/[A-Za-z0-9_-]{11}\?autoplay=1&mute=1&controls=0&loop=1/);}else{assert.equal(frames,0,label+' no iframe');assert(!csp.includes('youtube'),label+' csp has no video host');}
        if(mode==='image')assert.equal(await page.locator('img.reg-bg-img').count(),1);
        if(mode==='slideshow')assert.equal(await page.locator('.reg-slides img').count(),2);
        assert.equal(await page.locator('html').getAttribute('data-theme'),theme,label+' theme');
        // Every field visible with a label; touch targets >= 44px.
        for(const name of fields){
          const input=page.locator('input[name="'+name+'"]');
          assert(await input.isVisible(),label+' '+name+' visible');
          assert(await page.locator('label[for="'+await input.getAttribute('id')+'"]').isVisible(),label+' '+name+' label');
          assert((await input.boundingBox()).height>=44,label+' '+name+' height');
        }
        const submit=page.locator('button.reg-submit');
        assert((await submit.boundingBox()).height>=44,label+' submit height');
        assert.equal((await submit.innerText()).trim().replace(/\s*→$/,''),'Registruj se');
        const columns=await page.locator('.reg-grid').first().evaluate(g=>getComputedStyle(g).gridTemplateColumns.split(' ').length);
        assert.equal(columns,width>=768?2:1,label+' columns');
        // Contrast: every text element against its composited background,
        // checked over a white and a black page behind the translucent panel.
        const worst=await page.evaluate(()=>{
          const parse=c=>{const m=c.match(/rgba?\(([^)]+)\)/);if(!m)return [0,0,0,0];const p=m[1].split(/[ ,\/]+/).map(Number);return [p[0],p[1],p[2],p.length>3?p[3]:1];};
          const lum=([r,g,b])=>{const f=v=>{v/=255;return v<=.03928?v/12.92:Math.pow((v+.055)/1.055,2.4)};return .2126*f(r)+.7152*f(g)+.0722*f(b)};
          const over=(f,b)=>[0,1,2].map(i=>f[i]*f[3]+b[i]*(1-f[3])).concat(1);
          const ratio=(a,b)=>{const x=lum(a),y=lum(b);return (Math.max(x,y)+.05)/(Math.min(x,y)+.05)};
          const out=[];
          for(const base of [[255,255,255,1],[0,0,0,1]]){
            const walker=document.createTreeWalker(document.querySelector('main'),NodeFilter.SHOW_TEXT);
            let n;
            while((n=walker.nextNode())){
              if(!n.nodeValue.trim())continue;
              const el=n.parentElement;
              const cs=getComputedStyle(el);
              if(cs.visibility==='hidden'||cs.display==='none'||el.closest('[aria-hidden="true"]')||el.closest('.shop-hidden-hp')||el.closest('noscript')||!el.getClientRects().length)continue;
              const layers=[];
              for(let e=el;e&&e.tagName!=='BODY'&&e.tagName!=='HTML';e=e.parentElement){const bg=parse(getComputedStyle(e).backgroundColor);if(bg[3]>0)layers.push(bg);if(bg[3]===1)break;}
              let bg=base;for(const l of layers.reverse())bg=over(l,bg);
              let fg=parse(cs.color);fg=over(fg,bg);
              out.push([ratio(fg,bg),n.nodeValue.trim().slice(0,30)]);
            }
          }
          out.sort((a,b)=>a[0]-b[0]);return out[0];
        });
        assert(worst[0]>=4.5,label+' contrast '+worst[0].toFixed(2)+' for "'+worst[1]+'"');
        const rail=await page.locator('a.shop-back-to-top').getAttribute('aria-label');
        assert.equal(rail,'Nazad na vrh stranice');
        await page.screenshot({path:path.join(dir,'b812-registration-qa-'+mode+'-'+theme+'-'+width+'.png'),fullPage:true,animations:'disabled'});
      }finally{await ctx.close();}
    }
  }
  }
  // The fixed background layer covers the viewport at any scroll position
  // (no seam below the fold), never takes pointer events, and no opaque body
  // fill is painted over it.
  for(const mode of ['image','youtube']){
    execFileSync('php',['tests/Browser/set-login-background.php',mode],{cwd:process.cwd(),env:process.env,encoding:'utf8',timeout:15000});
    for(const theme of ['dark','light']){
      for(const [w,h] of [[1280,800],[360,740]]){
        const c=await browser.newContext({viewport:{width:w,height:h}});
        try{
          await c.addCookies([{name:'shop_theme',value:theme,url:base}]);
          const page=await c.newPage();
          const label='reg-bottom-'+mode+'-'+theme+'-'+w;
          await openOk(page,'/registracija',label);
          await page.evaluate(()=>window.scrollTo(0,document.documentElement.scrollHeight));
          const m=await page.evaluate(()=>{
            const r=document.querySelector('.reg-bg').getBoundingClientRect();
            const cs=getComputedStyle(document.querySelector('.reg-bg'));
            const body=getComputedStyle(document.body).backgroundColor;
            const f=document.querySelector('.reg-bg iframe');
            return {top:r.top,left:r.left,w:r.width,h:r.height,vw:innerWidth,vh:innerHeight,y:scrollY,max:document.documentElement.scrollHeight-innerHeight,pos:cs.position,pe:cs.pointerEvents,body,fpe:f?getComputedStyle(f).pointerEvents:'none'};
          });
          assert.equal(m.pos,'fixed',label);
          assert(m.top<=0&&m.left<=0&&m.w>=m.vw&&m.h>=m.vh,label+' bg box '+JSON.stringify(m));
          assert(m.max<=0||Math.abs(m.y-m.max)<=2,label+' scrolled to bottom');
          assert.equal(m.pe,'none',label);
          assert.equal(m.fpe,'none',label+' iframe pointer-events');
          assert(/rgba\(0, 0, 0, 0\)|transparent/.test(m.body),label+' body fill '+m.body);
          await page.screenshot({path:path.join(dir,'b812-registration-qa-bottom-'+mode+'-'+theme+'-'+w+'.png'),animations:'disabled'});
        }finally{await c.close();}
      }
    }
  }  execFileSync('php',['tests/Browser/set-login-background.php','default'],{cwd:process.cwd(),env:process.env,encoding:'utf8',timeout:15000});
  // Focus order and the error state (server validation, native checks off).
  const ctx=await browser.newContext({viewport:{width:1280,height:900}});
  try{
    const page=await ctx.newPage();
    await openOk(page,'/registracija','reg-focus');
    await page.locator('input[name="first_name"]').focus();
    const order=[];
    for(let i=0;i<8;i++){
      order.push(await page.evaluate(()=>document.activeElement.name||document.activeElement.className));
      await page.keyboard.press('Tab');
    }
    assert.deepEqual(order.slice(0,7),[...fields,'accepted_privacy'],'focus order');
    await page.locator('input[name="first_name"]').fill('B812');
    await page.locator('input[name="last_name"]').fill('Browser Err');
    await page.locator('input[name="address"]').fill('Synthetic 12');
    await page.locator('input[name="city"]').fill('Beograd');
    await page.locator('input[name="phone"]').fill('0601234567');
    await page.locator('input[name="email"]').fill('nije-email');
    await page.locator('form.reg-form').evaluate(f=>{f.noValidate=true;});
    await Promise.all([
      page.waitForResponse(r=>r.url().endsWith('/registracija')&&r.request().method()==='POST',{timeout:15000}),
      page.locator('button.reg-submit').click(),
    ]);
    await page.waitForURL('**/registracija',{timeout:15000});
    await page.waitForLoadState('networkidle');
    assert.equal(await page.locator('#reg-errors').count(),1,'error summary');
    assert.equal(await page.locator('input[name="email"]').getAttribute('aria-invalid'),'true');
    assert.equal(await page.locator('input[name="last_name"]').inputValue(),'Browser Err','old input kept');
    if(engine==='chromium'){
      assert.equal(await page.evaluate(()=>document.activeElement&&document.activeElement.id),'reg-errors','summary focused');
    }
    await page.screenshot({path:path.join(dir,'b812-registration-qa-error.png'),fullPage:true,animations:'disabled'});
  }finally{await ctx.close();}
  console.log('B812_REGISTRATION_QA_'+engine.toUpperCase()+'=PASS 360_768_1280_LIGHT_DARK_CONTRAST_OVERFLOW_FOCUS_ERROR');
}

(async()=>{
  const browser=await browserType.launch({headless:true});
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
          hasText:'Registruj se',
        }).isVisible(),'approved synthetic sender enables signup UI');
        await page.screenshot({
          path:path.join(dir,'b812-registration-'+width+'.png'),animations:'disabled',
        });
      }finally{await ctx.close();}
    }

    await registrationQa(browser);

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
      await page.locator('input[name="address"]').fill('Synthetic 12');
      await page.locator('input[name="city"]').fill('Beograd');
      await page.locator('input[name="email"]').fill(buyerEmail);
      await page.locator('input[name="phone"]').fill('0601234567');
      await page.locator('input[name="accepted_privacy"]').check();
      await Promise.all([
        page.waitForResponse(r=>r.url().endsWith('/registracija')
          && r.request().method()==='POST',{timeout:15000}),
        page.getByRole('button',{name:'Registruj se'}).click(),
      ]);
      await page.waitForURL('**/registracija',{timeout:15000});
      await page.waitForLoadState('networkidle');
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
      console.log('B812_REGISTERED_'+engine.toUpperCase()+'=PASS SIGNUP_ACTIVATION_LOGIN_CART_ORDER_REPLAY');
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
      await page.waitForURL(base+'/',{timeout:15000});
      const denied=await page.goto(base+'/porucivanje',{waitUntil:'domcontentloaded'});
      assert.equal(denied.status(),403,'staff cannot checkout as registered Kupac');
      console.log('B812_ROLE_HTTP=PASS_STAFF_DENIED');
    }finally{await staff.close();}
  }finally{await browser.close();}
})().catch(e=>{
  console.error('B812_'+engine.toUpperCase()+'_FAIL '+String(e.message).replace(/[a-f0-9]{64,}/g,'[REDACTED]'));
  process.exitCode=1;
});
