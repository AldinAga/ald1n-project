import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
const workflow=fs.readFileSync(path.resolve(' .github/workflows/batch705-release-preview.yml'.trim()),'utf8');

test('ephemeral staging strips all .env-prefixed fixtures before packaging',()=>{
  assert.match(workflow,/find "\$STAGE" -type f -name '\.env\*' -delete/);
});
test('only the isolated stage copy loses the protected webroot override',()=>{
  assert.match(workflow,/rm -f -- "\$STAGE\/apps\/cms\/current\/public\/\.htaccess"/);
  assert.doesNotMatch(workflow,/rm -f -- "apps\/cms\/current\/public\/\.htaccess"/);
});
test('candidate archive is never uploaded as an installable package',()=>{
  assert.match(workflow,/name: batch705-release-preview/);
  assert.match(workflow,/path: \$\{\{ env\.PREVIEW_DIR \}\}/);
  assert.doesNotMatch(workflow,/path: .*ald1n-not-for-production\.tar\.gz/);
});
