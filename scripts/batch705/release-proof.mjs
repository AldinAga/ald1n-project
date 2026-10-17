import fs from 'node:fs';
import path from 'node:path';

const REQUIRED = [
  'apps/cms/current/artisan',
  'apps/mobile/current/package.json',
  'packages/api-contract/openapi.yaml',
];
const ALLOWED_ROOTS = ['apps/cms/current/', 'apps/mobile/current/', 'packages/api-contract/'];
const SHA_40 = /^[a-f0-9]{40}$/;
const SHA_64 = /^[a-f0-9]{64}$/;

function isSensitivePath(name) {
  const bits = name.split('/');
  return bits.some((item) => (
    item === '.env' || (item.startsWith('.env.') && item !== '.env.example') ||
    /\.(?:pem|p12|pfx|key|keystore|jks)$/i.test(item) ||
    /^(?:GoogleService-Info\.plist|google-services\.json|service-account\.json|credentials\.json)$/i.test(item)
  ));
}

export function inspectStage(rootDir) {
  const abs = path.resolve(rootDir);
  const root = fs.lstatSync(abs);
  if (!root.isDirectory() || root.isSymbolicLink()) throw new Error('Stage root is not a regular directory');
  const files = [];
  let bytes = 0;
  function walk(relative) {
    const full = path.join(abs, relative);
    for (const item of fs.readdirSync(full, {withFileTypes:true}).sort((a,b)=>a.name.localeCompare(b.name))) {
      const itemPath=path.posix.join(relative.split(path.sep).join('/'),item.name);
      const stat=fs.lstatSync(path.join(full,item.name));
      if(stat.isSymbolicLink()) throw new Error(`Refuse stage symlink: ${itemPath}`);
      if(item.isDirectory()) {
        if (!ALLOWED_ROOTS.some(prefix => prefix.startsWith(itemPath+'/') || itemPath.startsWith(prefix))) throw new Error(`Stage scope violation: ${itemPath}`);
        walk(path.join(relative,item.name));
        continue;
      }
      if(!item.isFile() || !stat.isFile()) throw new Error(`Nonregular staged file: ${itemPath}`);
      if (!ALLOWED_ROOTS.some(prefix=>itemPath.startsWith(prefix))) throw new Error(`Stage scope violation: ${itemPath}`);
      if(itemPath === 'apps/cms/current/public/.htaccess') throw new Error('Protected production .htaccess must never be staged');
      if(isSensitivePath(itemPath)) throw new Error(`Sensitive file must not be packaged: ${itemPath}`);
      bytes += stat.size;
      files.push(itemPath);
    }
  }
  walk('');
  for(const required of REQUIRED) if (!files.includes(required)) throw new Error(`Missing required file: ${required}`);
  return {ok:true,count:files.length,bytes,paths:files};
}

export function assessReview(prHead,author,reviews) {
  if (!SHA_40.test(prHead) || typeof author !== 'string' || !Array.isArray(reviews)) return {ok:false,reason:'Invalid reviewer identity'};
  const ok = reviews.some(r=>r?.state==='APPROVED' && r.commit_id===prHead &&
    typeof r.author==='string' && r.author.toLowerCase()!==author.toLowerCase());
  return {ok,reason:ok?'Fresh independent review present':'Missing independent current-head approval'};
}

export function evaluateReleaseGate({qa,staging,reviews,backup,restore,mdl,host}={}) {
  const blockers=[];
  if(qa!=='pass') blockers.push('Exact-source QA 14/14 not verified');
  if(staging!==true) blockers.push('Staging package preview not verified');
  if(reviews!==true) blockers.push('Independent code/security approval absent');
  if(backup!==true) blockers.push('Fresh, two-backup logical integrity and backup provenance absent');
  if(restore!==true) blockers.push('Owner-deferred isolated restore drill remains unproven');
  if(mdl!==true) blockers.push('Privileged production MDL/transaction visibility absent');
  if(host!==true) blockers.push('Dirty CloudLinux host cutover and eight overrides not independently attested');
  blockers.push('Verified production deployment adapter is not installed or approved');
  return {authorized:false,blockers};
}

export function buildManifest({sha,tree,archiveSha,archiveSize,stage}) {
  if(!SHA_40.test(sha) || !SHA_40.test(tree) || !SHA_64.test(archiveSha)) throw new Error('Invalid SHA source/tree/archive identity');
  if(!Number.isSafeInteger(archiveSize) || archiveSize<=0) throw new Error('Invalid archive byte size');
  if(!stage?.ok || !Number.isSafeInteger(stage.count) || stage.count<3 || !Array.isArray(stage.paths)) throw new Error('Missing validated stage');
  return {
    schema_version:1,
    source:{repository:'AldinAga/ald1n-project',sha,ephemeral_merged_tree:tree},
    archive:{sha256:archiveSha,bytes:archiveSize,deployable:false,description:'QA preview only - NO production installation'},
    stage:{file_count:stage.count,bytes:stage.bytes,protected_htaccess_excluded:true},
    release:{authorized:false,reason:'Release blocked pending independent review, restore, MDL, backup and host gates'},
  };
}

// This CLI is strictly local/ephemeral. It performs no GitHub or production writes.
if(process.argv[1] && path.resolve(process.argv[1])===path.resolve(new URL(import.meta.url).pathname)) {
  try {
    if(process.argv[2]!=='--stage' || process.argv.length!==8) throw new Error('Usage: --stage ROOT SOURCE_SHA TREE_SHA ARCHIVE_SHA ARCHIVE_BYTES');
    const [, , ,root,sha,tree,archiveSha,archiveBytes]=process.argv;
    console.log(JSON.stringify(buildManifest({sha,tree,archiveSha,archiveSize:Number(archiveBytes),stage:inspectStage(root)}),null,2));
  } catch(e) {
    console.error(`BATCH705_STAGE_HOLD=${String(e.message).replace(/[\n\r]/g,' ')}`);
    process.exitCode=2;
  }
}
