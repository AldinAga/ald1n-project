import test,{before,after} from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import {createHash} from 'node:crypto';
import {spawnSync} from 'node:child_process';
import {verifySignature} from './verify-signature.mjs';
let root,good,expectedCert;
const sh=(cmd,args,opts={})=>{const r=spawnSync(cmd,args,{encoding:'utf8',...opts});if(r.status!==0)throw new Error(`${cmd} failed: ${r.stderr||r.stdout}`);return r.stdout};
const hash=f=>createHash('sha256').update(fs.readFileSync(f)).digest('hex');
before(()=>{
 root=fs.mkdtempSync(path.join(os.tmpdir(),'ald1n25-signature-'));
 const src=path.join(root,'src');fs.mkdirSync(path.join(src,'base','dex'),{recursive:true});
 fs.mkdirSync(path.join(src,'base','manifest'),{recursive:true});
 fs.writeFileSync(path.join(src,'BundleConfig.pb'),'bundle config fixture');
 fs.writeFileSync(path.join(src,'base','dex','classes.dex'),'test payload');
 fs.writeFileSync(path.join(src,'base','manifest','AndroidManifest.xml'),'test manifest');
 good=path.join(root,'signed.aab');
 sh('jar',['-cf',good,'BundleConfig.pb','base'],{cwd:src});
 const keystore=path.join(root,'upload.p12');
 sh('keytool',['-genkeypair','-alias','upload','-keyalg','RSA','-keysize','2048','-storetype','PKCS12','-keystore',keystore,'-storepass','changeit','-keypass','changeit','-dname','CN=Local QA','-validity','180','-noprompt']);
 sh('jarsigner',['-keystore',keystore,'-storepass','changeit','-keypass','changeit',good,'upload']);
 const text=sh('keytool',['-printcert','-jarfile',good]);
 expectedCert=text.match(/SHA256:\s*([0-9a-f:]+)/i)?.[1];
 assert.ok(expectedCert);
});
after(()=>{if(root)fs.rmSync(root,{recursive:true,force:true})});
test('accepts_valid_signed_fixture',async()=>{
 const r=await verifySignature({sealedAabPath:good,expectedSha256:hash(good),expectedUploadCertSha256:expectedCert});
 assert.equal(r.ok,true,JSON.stringify(r));
});
test('tampered_signed_payload_rejected_even_with_valid_zip_crc',async()=>{
 const modified=path.join(root,'tampered.aab');fs.copyFileSync(good,modified);
 const src=path.join(root,'src');fs.writeFileSync(path.join(src,'base','dex','classes.dex'),'payload changed and crc updated');
 sh('zip',['-q','-u',modified,'base/dex/classes.dex'],{cwd:src});
 sh('unzip',['-tqq',modified]);
 const r=await verifySignature({sealedAabPath:modified,expectedSha256:hash(modified),expectedUploadCertSha256:expectedCert});
 assert.equal(r.ok,false);
});
test('rejects_wrong_upload_certificate',async()=>{
 const r=await verifySignature({sealedAabPath:good,expectedSha256:hash(good),expectedUploadCertSha256:'AA:'.repeat(31)+'AA'});
 assert.equal(r.ok,false);assert.match(r.reason,/SIGNER/);
});
test('rejects_unsigned_entries_or_signature_error',async()=>{
 const modified=path.join(root,'extra.aab');fs.copyFileSync(good,modified);fs.writeFileSync(path.join(root,'src','base','dex','extra.dex'),'unsigned');
 sh('zip',['-q',modified,'base/dex/extra.dex'],{cwd:path.join(root,'src')});
 const r=await verifySignature({sealedAabPath:modified,expectedSha256:hash(modified),expectedUploadCertSha256:expectedCert});
 assert.equal(r.ok,false);
});
test('rejects_apk_or_empty_bundle',async()=>{
 const apk=path.join(root,'other.apk');fs.copyFileSync(good,apk);
 assert.equal((await verifySignature({sealedAabPath:apk,expectedSha256:hash(apk),expectedUploadCertSha256:expectedCert})).ok,false);
 const empty=path.join(root,'empty.aab');fs.writeFileSync(empty,'');
 assert.equal((await verifySignature({sealedAabPath:empty,expectedSha256:hash(empty),expectedUploadCertSha256:expectedCert})).ok,false);
});
test('rejects_symlink_and_nonregular_file',async()=>{
 const l=path.join(root,'link.aab');fs.symlinkSync(good,l);
 const r=await verifySignature({sealedAabPath:l,expectedSha256:hash(good),expectedUploadCertSha256:expectedCert});
 assert.equal(r.ok,false);
});
test('rejects_precomputed_hash_mismatch',async()=>{
 assert.equal((await verifySignature({sealedAabPath:good,expectedSha256:'0'.repeat(64),expectedUploadCertSha256:expectedCert})).ok,false);
});
