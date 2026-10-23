'use strict';
const assert=require('node:assert/strict');
const fs=require('node:fs');
const path=require('node:path');
const {spawn,execFile}=require('node:child_process');
const {promisify}=require('node:util');
const run=promisify(execFile);
const raceBase='http://127.0.0.1:8125';
const fixture='tests/Browser/seed-public-shop-b799.php';
const root=process.env.PLAYWRIGHT_NODE_ROOT;
const dir=process.env.B799_BROWSER_ARTIFACT_DIR;
const base=process.env.B799_BROWSER_BASE_URL;
if(process.env.APP_ENV!=='testing'||base!=='http://127.0.0.1:8124'||!dir||!root) {
  throw new Error('B799_BROWSER_HOLD_LOCALHOST_ONLY');
}
const {chromium}=require(path.join(root,'node_modules','playwright'));
fs.mkdirSync(dir,{recursive:true});
async function checkPage(page,url,label){
  const res=await page.goto(base+url,{waitUntil:'networkidle',timeout:30000});
  assert.equal(res.status(),200,label+' HTTP');
  const dims=await page.evaluate(()=>({w:window.innerWidth,sw:document.documentElement.scrollWidth}));
  assert(dims.sw<=dims.w+2,label+' horizontal overflow '+JSON.stringify(dims));
  return res;
}
async function startIndependentServer(){
  // The main Chromium workflow already serves :8124. A different PHP process
  // on :8125 is mandatory: one artisan serve instance is single-threaded.
  const proc=spawn('php',['artisan','serve','--host=127.0.0.1','--port=8125'],{
    cwd:process.cwd(),env:process.env,detached:true,stdio:'ignore'
  });
  for(let i=0;i<100;i++){
    if(proc.exitCode!==null)throw new Error('B799_RACE_SECOND_HTTP_PROCESS_EXIT');
    try {
      const response=await fetch(raceBase+'/katalog',{signal:AbortSignal.timeout(800)});
      if(response.status===200)return proc;
    }catch(_err){/* Only disposable server warmup is retried. */}
    await new Promise(resolve=>setTimeout(resolve,150));
  }
  stopIndependentServer(proc);
  throw new Error('B799_RACE_SECOND_HTTP_NOT_READY');
}
function stopIndependentServer(proc){
  if(!proc)return;
  try{process.kill(-proc.pid,'SIGTERM')}catch(_err){/* already stopped */}
}
async function prepareRaceCheckout(browserContext,origin,slug,buyer){
  const page=await browserContext.newPage();
  const show=await page.goto(origin+'/katalog/'+slug,{waitUntil:'networkidle'});
  assert.equal(show.status(),200,'race fixture product GET');
  await page.getByRole('button',{name:'Dodaj u korpu'}).click();
  await page.waitForURL('**/korpa',{timeout:15000});
  await page.getByRole('link',{name:'Nastavi na poručivanje'}).click();
  await page.waitForURL('**/porucivanje',{timeout:15000});
  for(const [name,value] of Object.entries({
    shipping_full_name:'Synthetic Race '+buyer,
    email:'race'+buyer+'@example.test',
    shipping_phone:'0601234000',
    shipping_address:'Synthetic Parallel Address '+buyer,
    shipping_city:'Beograd',
    shipping_postal_code:'11000',
  })){
    await page.locator('input[name="'+name+'"]').fill(value);
  }
  await page.locator('input[name="accepted_terms"]').check();
  const form=await page.locator('form.shop-checkout').evaluate(element =>
    Object.fromEntries(new FormData(element))
  );
  assert.match(form.checkout_key,/^[a-f0-9]{48}$/);
  assert.equal(form.accepted_terms,'1');
  await page.close();
  return form;
}
function lockedProduct(mode){
  return new Promise((resolve,reject)=>{
    const proc=spawn('php',[fixture,'--hold='+mode],{
      cwd:process.cwd(),env:process.env,stdio:['pipe','pipe','pipe']
    });
    let stdout='';
    let stderr='';
    let settled=false;
    const timeout=setTimeout(()=>{
      if(!settled){settled=true;proc.kill();reject(new Error('B799_LOCK_TIMEOUT '+mode));}
    },15000);
    proc.stdout.on('data',chunk=>{
      stdout+=chunk.toString();
      if(!settled&&stdout.includes('B799_LOCKED '+mode)){
        settled=true;
        clearTimeout(timeout);
        resolve(proc);
      }
    });
    proc.stderr.on('data',chunk=>{stderr+=chunk.toString().slice(0,200)});
    proc.on('error',err=>{
      if(!settled){settled=true;clearTimeout(timeout);reject(err);}
    });
    proc.on('exit',code=>{
      if(!settled){settled=true;clearTimeout(timeout);
        reject(new Error('B799_LOCK_ABORT mode='+mode+' rc='+code+' '+stderr));
      }
    });
  });
}
async function confirmConcurrentWaiters(mode){
  const {stdout}=await run('php',[fixture,'--waiters='+mode],{
    cwd:process.cwd(),env:process.env,timeout:18000,maxBuffer:2048
  });
  assert(stdout.includes('B799_PARALLEL_WAITER_BARRIER=PASS mode='+mode));
}
async function postRetail(context,origin,form){
  const response=await context.request.post(origin+'/porucivanje',{
    form,headers:{Accept:'application/json'},maxRedirects:0,timeout:45000
  });
  return {status:response.status(),location:response.headers().location||''};
}
function privatePath(response){
  if(response.status!==302)return null;
  const pathname=new URL(response.location,base).pathname;
  assert.match(pathname,/^\/porudzbina\/[a-f0-9]{64}$/);
  return pathname;
}
async function runRealRace(browser){
  const secondServer=await startIndependentServer();
  try {
    // Two genuinely separate HTTP processes and separate shopper sessions,
    // both blocked on the same LAST remaining stock row BEFORE release.
    const ctxA=await browser.newContext();
    const ctxB=await browser.newContext();
    try {
      const [formA,formB]=await Promise.all([
        prepareRaceCheckout(ctxA,base,'b799-race-lastunit','LastA'),
        prepareRaceCheckout(ctxB,raceBase,'b799-race-lastunit','LastB'),
      ]);
      const lock=await lockedProduct('lastunit');
      let outcomes;
      let barrierError;
      try {
        const pending=Promise.all([
          postRetail(ctxA,base,formA),postRetail(ctxB,raceBase,formB)
        ]);
        try{await confirmConcurrentWaiters('lastunit')}catch(e){barrierError=e}
        lock.stdin.end('RELEASE\n');
        outcomes=await pending;
      }finally{
        if(!lock.stdin.destroyed)lock.stdin.end('RELEASE\n');
      }
      if(barrierError)throw barrierError;
      const statuses=outcomes.map(row=>row.status).sort((a,b)=>a-b);
      assert.deepEqual(statuses,[302,422],
        'only one shopper may reserve the sole in-stock item');
      const winner=outcomes.find(row=>row.status===302);
      assert(privatePath(winner));
      console.log('B799_LAST_UNIT_CONCURRENT_HTTP=PASS SUCCESS=1 OUT_OF_STOCK=1');
    }finally{await ctxA.close();await ctxB.close();}
    // Two concurrent POSTs intentionally reuse the SAME session, cart,
    // signed form nonce and buyer details across independent PHP processes.
    const ctx=await browser.newContext();
    try{
      const form=await prepareRaceCheckout(ctx,base,'b799-race-duplicate','Duplicate');
      const lock=await lockedProduct('duplicate');
      let outcomes;
      let barrierError;
      try{
        const pending=Promise.all([
          postRetail(ctx,base,form),postRetail(ctx,raceBase,form)
        ]);
        try{await confirmConcurrentWaiters('duplicate')}catch(e){barrierError=e}
        lock.stdin.end('RELEASE\n');
        outcomes=await pending;
      }finally{
        if(!lock.stdin.destroyed)lock.stdin.end('RELEASE\n');
      }
      if(barrierError)throw barrierError;
      assert.deepEqual(outcomes.map(row=>row.status),[302,302],
        'same nonce must replay existing receipt on concurrent retry');
      assert.equal(privatePath(outcomes[0]),privatePath(outcomes[1]),
        'same receipt capability and no duplicate order');
      console.log('B799_SAME_NONCE_CONCURRENT_HTTP=PASS RESPONSES=2 ORDERS=1');
    }finally{await ctx.close();}
  }finally{stopIndependentServer(secondServer);}
}
(async()=>{
  const browser=await chromium.launch({headless:true});
  try {
    for(const w of [1440,768,390,320]){
      const ctx=await browser.newContext({viewport:{width:w,height:900}});
      const p=await ctx.newPage();
      await checkPage(p,'/katalog','catalog-'+w);
      assert((await p.locator('body').innerText()).includes('Synthetic Laptop B799'));
      assert(!(await p.locator('body').innerText()).includes('999888'));
      await p.screenshot({path:path.join(dir,'catalog-'+w+'.png'),animations:'disabled'});
      await checkPage(p,'/katalog/b799-browser-laptop','product-'+w);
      await p.screenshot({path:path.join(dir,'detail-'+w+'.png'),animations:'disabled'});
      await ctx.close();
    }
    const ctx=await browser.newContext({viewport:{width:1440,height:900}});
    const page=await ctx.newPage();
    await checkPage(page,'/katalog/b799-browser-laptop','visual-synthetic-product-start');
    await page.getByRole('button',{name:'Dodaj u korpu'}).click();
    await page.waitForURL('**/korpa');
    assert((await page.locator('body').innerText()).includes('12.500,00 RSD'));
    await page.screenshot({path:path.join(dir,'cart.png'),animations:'disabled'});
    const urls=[];
    for(let purchase=1;purchase<=2;purchase++){
      if(purchase===2){
        await page.goto(base+'/katalog/b799-browser-laptop');
        await page.getByRole('button',{name:'Dodaj u korpu'}).click();
        await page.waitForURL('**/korpa');
      }
      await page.getByRole('link',{name:'Nastavi na poručivanje'}).click();
      await page.waitForURL('**/porucivanje');
      const inputs={
        shipping_full_name:'Synthetic Buyer '+purchase,
        email:'synthetic'+purchase+'@example.test',
        shipping_phone:'0601234000',
        shipping_address:'Synthetic Address '+purchase,
        shipping_city:'Beograd',
        shipping_postal_code:'11000',
      };
      for(const [name,value] of Object.entries(inputs)){
        await page.locator('input[name="'+name+'"]').fill(value);
      }
      await page.locator('input[name="accepted_terms"]').check();
      if(purchase===1){
        await page.screenshot({path:path.join(dir,'checkout.png'),animations:'disabled'});
      }
      const [postResponse] = await Promise.all([
        page.waitForResponse(response => response.url().endsWith('/porucivanje')
            && response.request().method()==='POST',{timeout:20000}),
        page.getByRole('button',{name:'Potvrdi porudžbinu'}).click(),
      ]);
      if(!/\/porudzbina\/[a-f0-9]{64}$/.test(new URL(page.url()).pathname)){
        await page.screenshot({path:path.join(dir,'checkout-submit-diagnostic-'+purchase+'.png'),animations:'disabled'});
        const message=await page.evaluate(() => [
          ...document.querySelectorAll('[role="alert"],.shop-alert.error,li')
        ].filter(el=>el.offsetParent!==null)
         .map(el=>(el.textContent||'').trim().slice(0,160)).slice(0,5));
        const urlPath=new URL(page.url()).pathname.replace(/[a-f0-9]{64}/g,'[REDACTED]');
        throw new Error('CHECKOUT_DID_NOT_REDIRECT iteration='+purchase+
          ' status='+postResponse.status()+' path='+urlPath+' messages='+JSON.stringify(message));
      }
      await page.waitForURL(/\/porudzbina\/[a-f0-9]{64}$/,{timeout:15000});
      urls.push(page.url());
      const res=await page.reload({waitUntil:'networkidle'});
      assert.equal(res.status(),200);
      assert.equal(res.headers()['referrer-policy'],'no-referrer');
      assert((res.headers()['cache-control']||'').includes('no-store'));
      assert((await page.locator('body').innerText()).includes('Synthetic Laptop B799'));
      if(purchase===1)await page.screenshot({path:path.join(dir,'receipt.png'),animations:'disabled'});
    }
    assert.notEqual(urls[0],urls[1],'new sale must rotate capability and order');
    await runRealRace(browser);
    console.log('B799_CHROMIUM_QA=PASS WIDTHS=1440,768,390,320 PURCHASES=2 RACES=2');
    await ctx.close();
  }finally{await browser.close();}
})().catch(e=>{console.error('B799_CHROMIUM_QA_FAIL '+e.message);process.exit(1)});
