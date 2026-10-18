'use strict';
// Exact-source GET-only production public QA. No credentials; no business actions.
// Authenticated browser acceptance is a separate owner-operated gate.
const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
const BASE = 'https://cms.ald1n.com';
const SOURCE = 'e8c5cfdacd1da503142e8e29a8ba5fd017776224';
const root = process.env.PLAYWRIGHT_NODE_ROOT;
const out = process.env.LG2_PUBLIC_QA_ARTIFACT_DIR;
if (process.env.CI !== 'true' || process.env.LG2_PUBLIC_QA_RUN !== '1' || !root || !out) {
  console.error('HOLD: this script may run only in approved disposable CI');
  process.exit(2);
}
const { chromium } = require(path.resolve(root,'node_modules/playwright'));
const assets = [
  ['/assets/css/ald1n-liquid-glass.css','4de6437c10e2c80ec89bb089d717cf2a74995291','css'],
  ['/assets/css/vendor/liquid-glass-web.css','8b6ad29fb7b71d0a515a0a3135c635ceedb8aaa7','css'],
  ['/assets/js/ald1n-liquid-glass-r3.js','238c88d2e884eece2c9186d250a94f4ed6ecf5ae','javascript'],
  ['/assets/js/vendor/liquid-glass-web/liquid-glass.js','d47682afc2d4c0d1842bb320faac7291d0a9e299','javascript'],
  ['/assets/js/vendor/liquid-glass-web/geometry.js','75720d37efd9cc467d85cd03c7255ad9ee2a68c2','javascript'],
];
fs.mkdirSync(out,{recursive:true});
const report={sourceSha:SOURCE,baseUrl:BASE,method:'GET_ONLY',authenticatedUAT:'HOLD_OWNER_LOGIN_REQUIRED',assets:[],login:[],failures:[]};
function gitBlob(data){return crypto.createHash('sha1').update('blob '+data.length+'\0').update(data).digest('hex');}
function fail(phase,msg){report.failures.push(phase+': '+msg);console.error('FAIL '+phase+': '+msg);}
async function run(){
 let browser;
 try{
  browser=await chromium.launch({headless:true});
  const context=await browser.newContext({ignoreHTTPSErrors:false});
  for(const [p,expected,mimetype] of assets){
   try{
    const response=await context.request.get(BASE+p+'?qa='+SOURCE.slice(0,12),{timeout:25000});
    const body=await response.body();
    const actual=gitBlob(body), type=response.headers()['content-type']||'';
    report.assets.push({path:p,http:response.status(),mime:type,expectedGitBlob:expected,actualGitBlob:actual});
    if(response.status()!==200)fail('ASSET_HTTP',p+' '+response.status());
    if(actual!==expected)fail('ASSET_MISMATCH',p+' expected '+expected+' actual '+actual);
    if(!type.toLowerCase().includes(mimetype))fail('ASSET_MIME',p+' '+type);
    console.log('ASSET '+p+' HTTP='+response.status()+' BLOB='+actual);
   }catch(e){fail('ASSET_NETWORK',p+' '+e.message);}
  }
  for(const width of [1440,390]){
   const page=await context.newPage();
   const errors=[];
   page.on('pageerror',e=>errors.push(e.message));
   await page.setViewportSize({width,height:width===390?780:900});
   await page.route('**/*',route=>route.request().method()==='GET'?route.continue():route.abort('blockedbyclient'));
   try{
    const resp=await page.goto(BASE+'/login',{waitUntil:'domcontentloaded',timeout:28000});
    await page.waitForTimeout(750);
    const observed=await page.evaluate(()=>({path:location.pathname,title:document.title,
      passwordInputs:document.querySelectorAll('input[type="password"]').length,
      pageWidth:document.documentElement.scrollWidth,viewportWidth:innerWidth}));
    const status=resp?resp.status():0;
    const picture=status===200?'public-login-'+width+'.png':null;
    if(picture) await page.screenshot({path:path.join(out,picture),animations:'disabled',fullPage:false});
    const access=status===200?'PUBLIC_LOGIN_VISIBLE':status===403?'HOLD_CI_ACCESS_DENIED':'FAIL_UNEXPECTED_HTTP';
    report.login.push({width,http:status,...observed,pageErrors:errors,screenshot:picture,access});
    if(status===403) {
      // GitHub runner IP may be blocked by production WAF. Host Report750 gave
      // public login HTTP 200; do not equate 403 from this network with site outage.
      console.log('LOGIN_EXTERNAL_CI=HOLD_HTTP_403_WIDTH_'+width);
    } else {
      if(status!==200)fail('LOGIN_HTTP',width+' '+status);
      if(status===200 && !observed.passwordInputs)fail('LOGIN_FORM',width+' missing password field');
      if(status===200 && observed.pageWidth>width+3)fail('LOGIN_OVERFLOW',width+' content width '+observed.pageWidth);
      if(status===200 && errors.length)fail('LOGIN_JS',width+' '+errors.slice(0,2).join(' ; '));
    }
    console.log('LOGIN_WIDTH='+width+' HTTP='+status+' ACCESS='+access);
   }catch(e){fail('LOGIN_BROWSER',width+' '+e.message);}
   finally{await page.close();}
  }
 }catch(e){fail('BROWSER_LAUNCH',e.message);}
 finally{
  if(browser)await browser.close();
  report.assetDeliveryQa=report.failures.length?'FAIL':'PASS_EXACT_BLOBS';
  report.publicLoginQa=report.login.some(item=>item.http===403)
    ? 'HOLD_CI_NETWORK_ACCESS'
    : report.login.length===2 && report.login.every(item=>item.http===200)
      ? 'PASS_PUBLIC_LOGIN'
      : 'FAIL_OR_INCOMPLETE';
  report.publicQa=report.failures.length?'FAIL':report.publicLoginQa==='HOLD_CI_NETWORK_ACCESS'?'PASS_ASSETS_LOGIN_HOLD':'PASS_PUBLIC_ONLY';
  fs.writeFileSync(path.join(out,'production-public-qa.json'),JSON.stringify(report,null,2)+'\n');
  console.log('PUBLIC_ASSET_DELIVERY='+report.assetDeliveryQa);
  console.log('PUBLIC_LOGIN_QA='+report.publicLoginQa);
  console.log('PUBLIC_QA='+report.publicQa);
  console.log('AUTHENTICATED_UAT='+report.authenticatedUAT);
  if(report.failures.length)process.exitCode=1;
 }
}
run();
