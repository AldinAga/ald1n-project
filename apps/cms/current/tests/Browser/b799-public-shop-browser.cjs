'use strict';
const assert=require('node:assert/strict');
const fs=require('node:fs');
const path=require('node:path');
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
    await checkPage(page,'/katalog','catalog-start');
    await page.getByRole('button',{name:'Dodaj u korpu'}).click();
    await page.waitForURL('**/korpa');
    assert((await page.locator('body').innerText()).includes('12.500,00 RSD'));
    await page.screenshot({path:path.join(dir,'cart.png'),animations:'disabled'});
    const urls=[];
    for(let purchase=1;purchase<=2;purchase++){
      if(purchase===2){
        await page.goto(base+'/katalog');
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
      await page.getByRole('button',{name:'Potvrdi porudžbinu'}).click();
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
    console.log('B799_CHROMIUM_QA=PASS WIDTHS=1440,768,390,320 PURCHASES=2 CHECKS=10');
    await ctx.close();
  }finally{await browser.close();}
})().catch(e=>{console.error('B799_CHROMIUM_QA_FAIL '+e.message);process.exit(1)});
