#!/usr/bin/env node
// Dependency-free, read-only ECC instruction contract verification in disposable CI.
import {readFileSync} from 'node:fs';
import {resolve} from 'node:path';
import {fileURLToPath} from 'node:url';
const root=resolve(fileURLToPath(new URL('../../',import.meta.url)));
export const skills=['ald1n-ecc-engineering','ecc-browser-qa','ecc-laravel-verification','ecc-react-native-patterns'];
export const roles=['explorer','reviewer','docs-researcher'];
const required=['AGENTS.md','docs/operations/000-LATEST.md','docs/ECC-CLOUDLINUX-CONTRACT.md','incoming/','docs/operations/','CloudLinux','HOLD'];
const rules=[
 ['python3',/\bpython3\b/i],['file-descriptor',/\/dev\/fd\b/],['process-substitution',/[<>]\s*\(/],
 ['destructive-git',/\bgit\s+(?:pull|reset|clean)\b/i],['expo-install-fixer',/\bexpo\s+install\s+--fix\b/i],
 ['global-npm',/\bnpm\s+(?:install|i)\s+(?:-g|--global)\b/i],
 ['artisan-migrate',/\b(?:php\s+)?artisan\s+migrate(?::\S+)?\b/i],
 ['artisan-cache-mutation',/\b(?:php\s+)?artisan\s+(?:optimize:clear|(?:config|route|view):cache)\b/i],
 ['eas-release',/\beas\s+(?:build|update|submit)(?=\s|$)/i],
 ['upstream-install',/\b(?:bash|sh)\s+\S*(?:install\.sh|sync-ecc-to-codex\.sh)\b/i],
];
export function commandBlocks(src){
 return [...src.matchAll(/^\x60{3}(?:bash|sh|shell|console|zsh)\s*\n([\s\S]*?)^\x60{3}/gm)].map(m=>m[1]);
}
export function unsafeShellRecipes(src){
 const issues=[];
 for(const block of commandBlocks(src))for(const [kind,regex] of rules)if(regex.test(block))issues.push(kind);
 return [...new Set(issues)];
}
export function auditSkill(name,src){
 const fail=[];const front=src.match(/^---\r?\n([\s\S]*?)\r?\n---\r?\n/);
 if(!front)return ['missing-frontmatter'];
 if(!front[1].split(/\r?\n/).some(line=>line.trim()==='name: '+name))fail.push('wrong-name');
 if(!/^description:\s*.{35,}/m.test(front[1]))fail.push('missing-description');
 for(const line of front[1].split(/\r?\n/))if(line.trim()&&!/^(?:name|description):/.test(line))fail.push('invalid-frontmatter');
 for(const field of required)if(!src.includes(field))fail.push('missing-authority:'+field);
 for(const kind of unsafeShellRecipes(src))fail.push('unsafe-shell:'+kind);
 return fail;
}
export function audit(read=(p)=>readFileSync(resolve(root,p),'utf8')){
 const fail=[];
 for(const name of skills){
  const p='.agents/skills/'+name+'/SKILL.md';
  let src;try{src=read(p)}catch{fail.push('missing:'+p);continue}
  for(const e of auditSkill(name,src))fail.push(p+':'+e);
  try{const meta=read('.agents/skills/'+name+'/agents/openai.yaml');
   if(!meta.includes('display_name:')||!meta.includes('short_description:'))fail.push(name+':metadata-invalid');
  }catch{fail.push(name+':metadata-missing')}
 }
 const cfg=read('.codex/config.toml');
 if(!/max_threads\s*=\s*3/.test(cfg)||!/max_depth\s*=\s*1/.test(cfg))fail.push('unbounded-agents');
 if(/\[(?:hooks|mcp_servers)\b/.test(cfg))fail.push('unreviewed-hooks-mcp');
 for(const name of roles){
  const p='.codex/agents/'+name+'.toml';let src;
  try{src=read(p)}catch{fail.push('missing:'+p);continue}
  if(!cfg.includes('agents/'+name+'.toml'))fail.push('role-unmapped:'+name);
  if(!/^sandbox_mode\s*=\s*"read-only"\s*$/m.test(src))fail.push('role-not-readonly:'+name);
  if(!src.includes('AGENTS.md')||!src.includes('CloudLinux'))fail.push('role-authority:'+name);
 }
 const contract=read('docs/ECC-CLOUDLINUX-CONTRACT.md');
 for(const marker of ['10.11.19','eas-cli@25.0.0','e8c5cfdacd1da503142e8e29a8ba5fd017776224','HOLD_AWAITING_SEPARATE_OWNER_DEPLOY_AUTHORIZATION','a31c945f8981c7d20f49a85a5d1b1450e710f19a25136356b04666f637595146']){
  if(!contract.includes(marker))fail.push('missing-contract:'+marker);
 }
 return fail;
}
if(process.argv[1]&&resolve(process.argv[1])===fileURLToPath(import.meta.url)){
 const issues=audit();
 for(const item of issues)console.error('ECC_CLOUDLINUX_FAIL '+item);
 if(issues.length)process.exitCode=1;
 else console.log('ECC_CLOUDLINUX_POLICY=PASS skills='+skills.length+' agents='+roles.length);
}
