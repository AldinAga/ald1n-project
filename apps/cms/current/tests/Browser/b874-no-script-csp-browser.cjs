'use strict';

/**
 * B874: real disposable Chromium enforcement of the no-script public policy.
 * Separate from unchanged B799/B812 legacy browser suites.
 * No external requests, real users, payment, SMTP or production target.
 */
const assert=require('node:assert/strict');
const fs=require('node:fs');
const path=require('node:path');

const base=process.env.B874_BROWSER_BASE_URL;
const root=process.env.PLAYWRIGHT_NODE_ROOT;
const out=process.env.B874_BROWSER_OUT;
if(process.env.APP_ENV!=='testing'||
   process.env.DB_HOST!=='127.0.0.1'||
   process.env.DB_DATABASE!=='ald1n_test'||
   process.env.MAIL_MAILER!=='array'||
   process.env.SESSION_SECURE_COOKIE!=='false'||
   base!=='http://127.0.0.1:8124'||!root||!out){
  throw new Error('HOLD_B874_NOT_DISPOSABLE_LOCALHOST');
}
const {chromium}=require(path.join(root,'node_modules','playwright'));
fs.mkdirSync(out,{recursive:true});
const pages=[
  ['/katalog','catalog'],
  ['/katalog/b812-browser-laptop','product'],
  ['/korpa','cart'],
];
const observed=[];
function header(response,key){return (response.headers()[key]||'').toLowerCase();}
async function assertStrict(page,where,label){
  const response=await page.goto(base+where,{waitUntil:'networkidle',timeout:30000});
  assert.equal(response.status(),200,label+' HTTP 200');
  const policy=header(response,'content-security-policy');
  for(const directive of [
    "default-src 'none'","script-src 'none'","script-src-elem 'none'",
    "script-src-attr 'none'","frame-ancestors 'none'","base-uri 'none'",
    "style-src 'self'","img-src 'self' data:","form-action 'self'",
  ])assert(policy.includes(directive),label+' missing enforced CSP '+directive);
  assert.equal(header(response,'x-frame-options'),'deny');
  assert(!policy.includes("'unsafe-inline'"));
  assert(!policy.includes("'unsafe-eval'"));
  const assets=await page.evaluate(()=>({
    styles:document.styleSheets.length,
    viewport:window.innerWidth,
    scroll:document.documentElement.scrollWidth,
  }));
  assert(assets.styles>0,label+' stylesheet did not load');
  assert(assets.scroll<=assets.viewport+2,label+' unexpected horizontal overflow '+JSON.stringify(assets));
  // Intentionally insert a harmless LOCAL marker, not an exploit against a host.
  // Chromium must prevent the DOM-injected inline script from executing.
  await page.evaluate(()=>{
    window.__b874Marker=false;
    window.__b874PolicyViolations=[];
    document.addEventListener('securitypolicyviolation',e=>{
      window.__b874PolicyViolations.push(e.effectiveDirective);
    });
    const node=document.createElement('script');
    node.textContent='window.__b874Marker=true';
    document.body.appendChild(node);
  });
  await page.waitForTimeout(100);
  const result=await page.evaluate(()=>({
    executed:window.__b874Marker,
    violations:window.__b874PolicyViolations,
  }));
  assert.equal(result.executed,false,label+' inline script EXECUTED');
  assert(result.violations.some(x=>x==='script-src-elem'||x==='script-src'),
    label+' no actual Chromium CSP block event '+JSON.stringify(result));
  observed.push({page:label,status:200,strict_csp:true,css_loaded:true,
    marker_blocked:true,inline_violation:true,viewport:assets.viewport});
  return response;
}
(async()=>{
  const browser=await chromium.launch({headless:true});
  try{
    for(const width of [1440,390]){
      const ctx=await browser.newContext({viewport:{width,height:900}});
      try{
        const page=await ctx.newPage();
        for(const [url,label] of pages){
          await assertStrict(page,url,label+'-'+width);
          await page.screenshot({path:path.join(out,'b874-'+label+'-'+width+'.png'),animations:'disabled'});
        }
        if(width===1440){
          const register=await page.goto(base+'/registracija',{waitUntil:'networkidle'});
          assert.equal(register.status(),200);
          assert(!header(register,'content-security-policy').includes("script-src 'none'"),
            'Turnstile-capable registration must not inherit no-script scope');
          assert.equal(header(register,'x-frame-options'),'sameorigin');
          const login=await page.goto(base+'/login',{waitUntil:'networkidle'});
          assert.equal(login.status(),200);
          assert(!header(login,'content-security-policy').includes("script-src 'none'"),
            'legacy auth inline theme must be outside strict public policy');
          observed.push({page:'registration-and-login-exempt',status:200,
            preserved_global_csp:true});
        }
      }finally{await ctx.close();}
    }
  }finally{await browser.close();}
  assert.equal(observed.length,7);
  fs.writeFileSync(path.join(out,'b874-csp-browser-evidence.json'),
    JSON.stringify({scope:'DISPOSABLE_LOCALHOST_ONLY',
      observed,global_asvs_complete:false,production_mutated:false,
      owasp_complete_passes:0,release:'HOLD'},null,2)+'\n');
  console.log('B874_CHROMIUM_NO_SCRIPT=PASS_6_VIEWS_INLINE_BLOCKED_CSS_LOADED_2_VIEWPORTS');
  console.log('B874_BROWSER_RELEASE=HOLD_GLOBAL_ASVS_PENTEST_INCOMPLETE');
})().catch(error=>{
  console.error('B874_CHROMIUM_HOLD '+String(error.message).replace(/[a-f0-9]{64,}/g,'[REDACTED]'));
  process.exitCode=1;
});
