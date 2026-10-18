import test from 'node:test';
import assert from 'node:assert/strict';
import {commandBlocks,unsafeShellRecipes,auditSkill,audit} from './cloudlinux-guardrails.mjs';
const fence=String.fromCharCode(96).repeat(3);
const code=(src)=>fence+'bash\n'+src+'\n'+fence;

test('warning prose does not count as executable shell',()=>{
 assert.deepEqual(unsafeShellRecipes('Never execute python3, git reset or artisan migrate'),[]);
});
test('dangerous CloudLinux commands fail closed',()=>{
 const bad=['python3 script.py','cat /dev/fd/1','diff <(ls) file','git pull','git reset --hard','git clean -fdx',
 'expo install --fix','npm install -g npm','php artisan migrate','php artisan config:cache',
 'php artisan optimize:clear','eas update --branch production','bash install.sh'];
 for(const cmd of bad)assert.ok(unsafeShellRecipes(code(cmd)).length>0,cmd);
});
test('canonical disposable QA recipes are allowed',()=>{
 assert.deepEqual(unsafeShellRecipes(code('php bin/static-check.php\nnode scripts/validate-project.mjs')),[]);
});
test('skill frontmatter and CloudLinux authorities are mandatory',()=>{
 const src='---\nname: ecc-browser-qa\ndescription: A detailed description of safe visual regression checks\n---\n';
 assert.ok(auditSkill('ecc-browser-qa',src).some(x=>x.startsWith('missing-authority')));
 assert.ok(auditSkill('ecc-laravel-verification',src).includes('wrong-name'));
});
test('all checked-in skills and roles comply with the host contract',()=>{
 assert.deepEqual(audit(),[]);
});
