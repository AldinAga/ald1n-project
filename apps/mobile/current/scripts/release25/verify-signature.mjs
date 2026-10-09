import fs from 'node:fs';
import path from 'node:path';
import {createHash} from 'node:crypto';
import {spawnSync} from 'node:child_process';
const SHA256=/^[0-9a-f]{64}$/i;
function canonical(v){return typeof v==='string'?v.replaceAll(':','').replaceAll(' ','').toUpperCase():''}
function shaFd(fd){
 const hash=createHash('sha256');const buf=Buffer.allocUnsafe(1024*1024);
 let pos=0;for(;;){const n=fs.readSync(fd,buf,0,buf.length,pos);if(!n)break;hash.update(buf.subarray(0,n));pos+=n;}
 return hash.digest('hex');
}
const fail=reason=>({ok:false,reason});
export async function verifySignature({sealedAabPath,expectedSha256,expectedUploadCertSha256,runTool=spawnSync}){
 if(typeof sealedAabPath!=='string'||!path.isAbsolute(sealedAabPath)||!sealedAabPath.endsWith('.aab'))return fail('NOT_AAB');
 if(!SHA256.test(expectedSha256)||!SHA256.test(canonical(expectedUploadCertSha256)))return fail('INVALID_EXPECTED_HASH_OR_SIGNER');
 let fd;
 try {
  const stat=fs.lstatSync(sealedAabPath);
  if(!stat.isFile()||stat.isSymbolicLink()||stat.size<50||stat.size>1_073_741_824||(stat.mode&0o022)!==0)return fail('UNSAFE_AAB_FILE');
  const parent=fs.statSync(path.dirname(sealedAabPath));
  if(!parent.isDirectory()||(parent.mode&0o077)!==0)return fail('UNSAFE_AAB_DIRECTORY');
  fd=fs.openSync(sealedAabPath,fs.constants.O_RDONLY|fs.constants.O_NOFOLLOW);
  const opened=fs.fstatSync(fd);
  if(opened.ino!==stat.ino||opened.dev!==stat.dev||opened.size!==stat.size)return fail('AAB_FILE_CHANGED');
  const initialHash=shaFd(fd);
  if(initialHash.toUpperCase()!==expectedSha256.toUpperCase())return fail('AAB_SHA256_MISMATCH');
  const tool=(cmd,args)=>{
   const res=runTool(cmd,args,{encoding:'utf8',maxBuffer:64*1024*1024,timeout:120000,env:{...process.env,LC_ALL:'C'}});
   if(res?.error)return {status:1,stdout:'',stderr:''};return res;
  };
  const integrity=tool('unzip',['-tqq',sealedAabPath]);
  if(integrity.status!==0)return fail('ZIP_CRC_ERROR');
  const listing=tool('unzip',['-Z1',sealedAabPath]);
  if(listing.status!==0)return fail('ZIP_LIST_ERROR');
  const entries=listing.stdout.trim().split('\n').filter(Boolean);
  if(!entries.includes('BundleConfig.pb')||!entries.some(x=>x.startsWith('base/manifest/')))return fail('INVALID_AAB_STRUCTURE');
  for(const name of entries){
   if(name.includes('\\')||name.includes('\0')||name.startsWith('/')||name.split('/').includes('..')||name.split('/').includes('.')||name.startsWith('./'))return fail('UNSAFE_ZIP_ENTRY');
  }
  const verify=tool('jarsigner',['-verify','-verbose','-certs',sealedAabPath]);
  const output=`${verify.stdout||''}\n${verify.stderr||''}`;
  if(verify.status!==0||!output.includes('jar verified.')||/jar is unsigned|contains unsigned entries|signature.*(invalid|failed)|digest error/i.test(output))return fail('SIGNATURE_CRYPTO_FAILED');
  const nonMeta=entries.filter(x=>!x.endsWith('/')&&!x.toUpperCase().startsWith('META-INF/'));
  const lines=output.split('\n');
  for(const entry of nonMeta){
   const signed=lines.some(x=>x.trimEnd().endsWith(` ${entry}`)&&/^sm\s+\d+\s+/.test(x.trimStart()));
   if(!signed)return fail('UNSIGNED_ENTRY');
  }
  const cert=tool('keytool',['-printcert','-jarfile',sealedAabPath]);
  if(cert.status!==0)return fail('SIGNER_CERT_UNAVAILABLE');
  const fingerprints=[...(cert.stdout||'').matchAll(/SHA256:\s*([0-9A-F:]{95})/gi)].map(x=>canonical(x[1]));
  if(fingerprints.length!==1||fingerprints[0]!==canonical(expectedUploadCertSha256))return fail('SIGNER_FINGERPRINT_MISMATCH');
  const latest=fs.lstatSync(sealedAabPath);
  if(latest.ino!==opened.ino||latest.dev!==opened.dev||latest.size!==opened.size||latest.mtimeMs!==opened.mtimeMs)return fail('AAB_FILE_CHANGED');
  const hashEnd=shaFd(fd);
  if(hashEnd!==initialHash)return fail('AAB_FILE_CHANGED');
  return {ok:true,reason:'NONE',sha256:hashEnd,certSha256:fingerprints[0]};
 }catch(e){return fail(`AAB_VERIFY_ERROR:${e.code||e.message?.slice(0,60)||'unknown'}`)}finally{if(fd!==undefined)fs.closeSync(fd)}
}
