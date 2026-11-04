import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import test from 'node:test';

const root = fileURLToPath(new URL('../', import.meta.url));

// Exercise the actual complete validator under both path representations.
// The preload only changes platform output and supplies a read-only negative
// fixture; it never writes application files or skips validator checks.
function runValidator(flavor, forbiddenConsumer = false) {
  const scratch = fs.mkdtempSync(path.join(os.tmpdir(), 'ald1n-validator-paths-'));
  try {
    const preload = path.join(scratch, 'platform.cjs');
    fs.writeFileSync(preload, String.raw`
      const path = require('node:path');
      const fs = require('node:fs');
      const originalRelative = path.relative;
      path.relative = (...args) => originalRelative(...args).replace(/[\\/]/g,
        process.env.ALD1N_QA_PATH_FLAVOR === 'win32' ? '\\' : '/');
      if (process.env.ALD1N_QA_FORBIDDEN_CONSUMER === '1') {
        const originalRead = fs.readFileSync;
        fs.readFileSync = function (file, ...args) {
          const result = originalRead.call(this, file, ...args);
          if (String(file).replace(/\\/g, '/').endsWith('/src/lib/api/client.ts')) {
            const extra = '\nconst qaForbiddenColor = colors.__qaForbidden;\n';
            return typeof result === 'string' ? result + extra : Buffer.concat([result, Buffer.from(extra)]);
          }
          return result;
        };
      }
    `);
    const result = spawnSync(process.execPath,
      ['--require', preload, path.join(root, 'scripts/validate-project.mjs')], {
        cwd: root,
        env: { ...process.env, ALD1N_QA_PATH_FLAVOR: flavor,
          ALD1N_QA_FORBIDDEN_CONSUMER: forbiddenConsumer ? '1' : '0' },
        encoding: 'utf8', maxBuffer: 4 * 1024 * 1024, timeout: 60000,
      });
    assert.ifError(result.error);
    const output = result.stdout + result.stderr;
    return { status: result.status, output,
      diagnostics: output.split('\n').filter(line => /^(FAIL|Ukupno FAIL)/.test(line)).join('\n') };
  } finally {
    fs.unlinkSync(path.join(scratch, 'platform.cjs'));
    fs.rmdirSync(scratch);
  }
}

for (const flavor of ['win32', 'posix']) {
  test(`complete validator accepts the permitted theme adapter with ${flavor} paths`, () => {
    const result = runValidator(flavor);
    assert.equal(result.status, 0, result.diagnostics);
    assert.match(result.output, /Ukupno FAIL: 0/);
  });
}

test('Windows normalization still rejects colors usage outside the adapter', () => {
  const result = runValidator('win32', true);
  assert.equal(result.status, 1, result.diagnostics);
  assert.match(result.output, /Ukupno FAIL: 1/);
  assert.match(result.output, /FAIL Legacy colors\.\* usage postoji: src\/lib\/api\/client\.ts/);
  assert.doesNotMatch(result.diagnostics, /src[\\/]constants[\\/]theme\.ts/);
});
