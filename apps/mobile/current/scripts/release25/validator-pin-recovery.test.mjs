import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
const mobile=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'../..');
const root=path.resolve(mobile,'../../..');
const validator=fs.readFileSync(path.join(mobile,'scripts/validate-project.mjs'),'utf8');
const helper=fs.readFileSync(path.join(mobile,'scripts/submit-android-production.mjs'),'utf8');
const eas=JSON.parse(fs.readFileSync(path.join(mobile,'eas.json'),'utf8'));
const agents=fs.readFileSync(path.join(root,'AGENTS.md'),'utf8');
test('validator helper and eas pin all match canonical AGENTS authority',()=>{
  assert.equal(eas.cli.version,'24.7.0');
  assert.match(agents,/eas-cli@24\.7\.0/);
  const pin=`const EAS_CLI_VERSION = '${eas.cli.version}'`;
  assert.ok(helper.includes(pin));
  assert.ok(validator.includes(`productionSubmitHelper505R.includes("${pin}")`));
  assert.ok(!validator.includes("const EAS_CLI_VERSION = '24.8.0'"));
});
test('legacy direct submit remains disabled and guarded',()=>{
  assert.ok(helper.includes('DIRECT_SUBMIT_DISABLED'));
  assert.ok(helper.includes("if (mode === '--execute') die("));
  assert.ok(validator.includes("'--profile', 'production'"));
  assert.ok(validator.includes("'--id', buildId"));
});
test('TypeScript status only tests syntax failures, no inherited release assertion failure',()=>{
  assert.ok(validator.includes("const syntaxFailuresBefore = failures;\nfor (const file of sourceFiles) {\n  const source = fs.readFileSync(file, 'utf8');\n  const result = ts.transpileModule(source, {"));
  assert.ok(validator.includes('assert(failures === syntaxFailuresBefore, `${sourceFiles.length} TypeScript/TSX fajlova prolazi sintaksnu proveru.`);'));
  assert.ok(validator.includes('fail(`${path.relative(root, file)} ima TypeScript sintaksnu grešku:'));
});
