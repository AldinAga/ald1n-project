import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import assert from 'node:assert/strict';
import { gunzipSync, gzipSync } from 'node:zlib';

// CI-only diagnostic. No app code, observed test, host file or dependency is repaired here.
const MAIN = 'bc42e8cd4d8777cf2d16e15a54a01403b5b679e7';
const HOST = 'b5b942e645d28d3ed5f248eaae4180ebe9a54ee6';
const PREIMAGE = '5598fb0fe52c8c0f6b039c20a9ac44cfebba6c56';
const REPORT = 'docs/operations/1073-B1109-HOST-READINESS-20261104-144325-1998023.md';
const REPORT_SHA = 'd2ba8ec3d67102c8e4409da133be32bafec30a9cb167d1a6a99c067870f2059a';
const PATCH_SHA = 'b8bf1ffe7cae991a4f7a63cf47e68dbf5a81cd819daf79f5abc4f25106c69153';
const PATCH_BYTES = 43726;
const GZIP_SHA = '6767ac0c476451290fd28ee49a317cb9b6cd9f0b03dc31e2d2548df70c99a68e';
const GZIP_BYTES = 12952;
const OLD_LOCK = '27ad4a0ca3845bc10967329ef61583f7f4a81f5d9e67ad5e5c304fbf9df7d014';
const NEW_LOCK = '4965e0f59599f68d677cd4604ded256b809ce82a52b36e355faeb6fcaffb1ff8';
const CMS = 'apps/cms/current/';
const hash = b => createHash('sha256').update(b).digest('hex');
const blob = b => createHash('sha1').update(Buffer.from(`blob ${b.length}\0`)).update(b).digest('hex');
const git = (...args) => execFileSync('git', args, { maxBuffer: 128 * 1024 * 1024 });
const json = (file, value) => fs.writeFileSync(file, JSON.stringify(value, null, 2) + '\n', { flag: 'wx' });
const validPath = p => typeof p === 'string' && !p.includes('\\') && !p.startsWith('/') &&
  p.split('/').every(s => s !== '' && s !== '.' && s !== '..' && /^[A-Za-z0-9_.-]+$/.test(s)) &&
  /^(?:(?:app|config|routes|database\/migrations|resources\/views|tests)\/.+|bootstrap\/[^/]+)\.php$/.test(p);

function decodeGzip(bytes) {
  const decoded = gunzipSync(bytes, { maxOutputLength: PATCH_BYTES });
  assert.equal(decoded.length, PATCH_BYTES); assert.equal(hash(decoded), PATCH_SHA);
  return decoded;
}

function readFixture(bytes) {
  assert.equal(bytes.length, GZIP_BYTES); assert.equal(hash(bytes), GZIP_SHA);
  return decodeGzip(bytes);
}

function fixtureBytes() {
  const file = new URL('./b1049-observed-source-UNAPPLIED.patch.gz', import.meta.url);
  const stat = fs.lstatSync(file); assert(stat.isFile()); assert.equal(stat.size, GZIP_BYTES);
  return fs.readFileSync(file);
}

function parseReport(bytes, verifyDigest = true) {
  if (verifyDigest) { assert.equal(bytes.length, 184566); assert.equal(hash(bytes), REPORT_SHA); }
  const text = bytes.toString('utf8');
  for (const marker of [
    `LOCAL_HEAD=${HOST} REMOTE_MAIN=00a97a6ca661b51cae327aec9044807becbd8017`,
    'SOURCE_CENSUS_COUNT=765', 'SOURCE_CENSUS_STABLE=PASS', 'B1109_METADATA_COMPLETE=YES',
    'PROTECTED_FOUR_UNCHANGED=PASS', 'FINAL_STATUS=HOLD_READINESS_GATES',
    'SCRIPT_RC=2 SOURCE_DB_ENV_CACHE_FPM_MUTATION=NO',
    'REPORT=/home/icaffeco/ald1n-project/' + REPORT,
  ]) assert.equal(text.split(/\r?\n/).filter(l => l === marker).length, 1, marker);
  const issues = text.match(/^SOURCE_CENSUS_ISSUES=(.+)$/m);
  assert(issues); assert.deepEqual(JSON.parse(issues[1]), { missing_indexed: [], missing_dirs: [], complete: true });
  const rows = text.split(/\r?\n/).filter(l => l.startsWith('SOURCE_FILE=')).map(l => JSON.parse(l.slice(12)));
  assert.equal(rows.length, 765); assert.equal(new Set(rows.map(r => r.path)).size, 765);
  for (const r of rows) {
    assert(validPath(r.path), `Bad source path: ${r.path}`);
    assert(/^[a-f0-9]{40}$/.test(r.git_blob)); assert(/^[a-f0-9]{64}$/.test(r.sha256));
    assert(Number.isSafeInteger(r.bytes) && r.bytes > 0); assert.equal(typeof r.indexed, 'boolean');
  }
  assert.equal(rows.filter(r => r.path.startsWith('tests/')).length, 94);
  return rows;
}

function verifyBytes(row, bytes) {
  assert.equal(bytes.length, row.bytes, row.path + ': size');
  assert.equal(hash(bytes), row.sha256, row.path + ': SHA256');
  assert.equal(blob(bytes), row.git_blob, row.path + ': raw Git blob');
}

function patchPHP(bytes, getPreimage) {
  assert.equal(bytes.length, 43726); assert.equal(hash(bytes), PATCH_SHA);
  const sections = bytes.toString('utf8').split(/(?=^diff --git )/m).filter(Boolean);
  assert.equal(sections.length, 13);
  const outputs = new Map(); const ignored = [];
  for (const section of sections) {
    const lines = section.split('\n');
    const header = lines[0].match(/^diff --git a\/(\S+) b\/\1$/); assert(header);
    const full = header[1]; assert(full.startsWith(CMS));
    const rel = full.slice(CMS.length);
    if (!rel.endsWith('.php')) { assert.equal(rel, 'public/assets/css/ald1n-public-storefront.css'); ignored.push(rel); continue; }
    assert(validPath(rel)); assert(!outputs.has(rel));
    const index = section.match(/^index ([a-f0-9]+)\.\.([a-f0-9]+)/m); assert(index);
    const isNew = section.includes('\nnew file mode ');
    const pre = isNew ? Buffer.alloc(0) : getPreimage(full);
    assert(isNew ? /^0+$/.test(index[1]) : blob(pre).startsWith(index[1]), rel + ': preimage');
    assert.equal(pre.toString('utf8').includes('\r'), false, 'Unexpected preimage newline');
    const old = pre.toString('utf8').split('\n'); if (old.at(-1) === '') old.pop();
    let cursor = 0; let hunks = 0; const out = [];
    for (let i = 0; i < lines.length; i++) {
      const h = lines[i].match(/^@@ -(\d+)(?:,(\d+))? \+(\d+)(?:,(\d+))? @@/); if (!h) continue;
      hunks++;
      const oldN = h[2] === undefined ? 1 : Number(h[2]); const newN = h[4] === undefined ? 1 : Number(h[4]);
      const offset = Number(h[1]) - (oldN === 0 ? 0 : 1);
      assert(offset >= cursor && offset <= old.length); out.push(...old.slice(cursor, offset)); cursor = offset;
      assert.equal(out.length, Number(h[3]) - (newN === 0 ? 0 : 1), rel + ': new offset');
      let oldCount = 0; let newCount = 0;
      while (oldCount < oldN || newCount < newN) {
        const l = lines[++i]; assert(l !== undefined);
        if (l.startsWith(' ')) { assert.equal(old[cursor++], l.slice(1)); out.push(l.slice(1)); oldCount++; newCount++; }
        else if (l.startsWith('-')) { assert.equal(old[cursor++], l.slice(1)); oldCount++; }
        else if (l.startsWith('+')) { out.push(l.slice(1)); newCount++; }
        else throw Error('Unexpected PHP hunk line: ' + rel);
      }
      assert.equal(oldCount, oldN); assert.equal(newCount, newN);
    }
    assert(hunks > 0); assert(!section.includes('\\ No newline at end of file'));
    out.push(...old.slice(cursor)); const result = Buffer.from(out.join('\n') + '\n');
    assert(blob(result).startsWith(index[2]), rel + ': patch result'); outputs.set(rel, result);
  }
  assert.equal(outputs.size, 12); assert.equal(ignored.length, 1);
  return { outputs, ignored };
}

function negatives(report, patch, rows, compressed) {
  const checks = [];
  const reject = (name, fn) => { assert.throws(fn, undefined, name); checks.push({ name, rejected: true }); };
  const replaceRow = fn => Buffer.from(report.toString('utf8').replace(/^SOURCE_FILE=(.+)$/m, (_, s) => 'SOURCE_FILE=' + JSON.stringify(fn(JSON.parse(s)))));
  reject('tampered-report-digest', () => parseReport(Buffer.concat([report, Buffer.from('x')])));
  reject('duplicate-row', () => parseReport(Buffer.from(report.toString('utf8').replace(/^SOURCE_FILE=.+$/m, 'SOURCE_FILE=' + JSON.stringify(rows[1]))), false));
  reject('missing-row', () => parseReport(Buffer.from(report.toString('utf8').replace(/^SOURCE_FILE=.+\r?\n/m, '')), false));
  for (const p of ['../app/x.php', '/app/x.php', 'app/../x.php', 'app\\x.php', 'bootstrap/sub/x.php', 'vendor/x.php']) {
    reject('bad-path-' + p, () => parseReport(replaceRow(r => ({ ...r, path: p })), false));
  }
  reject('incomplete-report', () => parseReport(Buffer.from(report.toString('utf8').replace('B1109_METADATA_COMPLETE=YES', '')), false));
  const b = git('cat-file', 'blob', rows[0].git_blob); verifyBytes(rows[0], b);
  reject('payload-digest-mismatch', () => verifyBytes({ ...rows[0], sha256: '0'.repeat(64) }, b));
  reject('payload-blob-mismatch', () => verifyBytes({ ...rows[0], git_blob: '0'.repeat(40) }, b));
  reject('patch-digest-mismatch', () => patchPHP(Buffer.concat([patch, Buffer.from('x')]), () => Buffer.alloc(0)));
  reject('patch-preimage-mismatch', () => patchPHP(patch, () => Buffer.from('wrong preimage\n')));
  const tampered = Buffer.from(compressed); tampered[0] ^= 1;
  reject('gzip-transport-digest-mismatch', () => readFixture(tampered));
  reject('oversized-gzip-transport', () => readFixture(Buffer.concat([compressed, Buffer.from('x')])));
  reject('bad-gzip-decode', () => decodeGzip(Buffer.from('not a gzip stream')));
  const wrongDecoded = Buffer.from(patch); wrongDecoded[0] ^= 1;
  reject('decoded-patch-digest-mismatch', () => decodeGzip(gzipSync(wrongDecoded)));
  reject('oversized-decoded-patch', () => decodeGzip(gzipSync(Buffer.alloc(PATCH_BYTES + 1))));
  return checks;
}

function inventory(root, scratch = false, prefix = '') {
  const result = {};
  for (const entry of fs.readdirSync(path.join(root, prefix), { withFileTypes: true })) {
    const rel = prefix + entry.name;
    if (scratch && (rel === 'apps/cms/current/vendor' || rel === 'apps/cms/current/storage' ||
      rel === 'apps/cms/current/.phpunit.cache' || rel === 'apps/cms/current/.phpunit.result.cache' ||
      rel.startsWith('apps/cms/current/bootstrap/cache/') && entry.name !== '.gitignore')) continue;
    assert(!entry.isSymbolicLink(), 'Symlink: ' + rel);
    if (entry.isDirectory()) Object.assign(result, inventory(root, scratch, rel + '/'));
    else { assert(entry.isFile()); result[rel] = hash(fs.readFileSync(path.join(root, rel))); }
  }
  return result;
}

function verifyObserved(root, rows) {
  const app = path.join(root, CMS); const actual = Object.keys(inventory(app)).filter(validPath).sort();
  assert.deepEqual(actual, rows.map(r => r.path).sort(), 'Exact observed PHP census');
  for (const row of rows) verifyBytes(row, fs.readFileSync(path.join(app, row.path)));
  return { observedPHP: actual.length, runtimePHP: 671, testsPHP: 94,
    entrypointTestFiles: rows.filter(r => /^(?:tests\/Unit|tests\/Feature)\/.+Test\.php$/.test(r.path)).length,
    retainedOutsideEntrypoint: rows.filter(r => r.path.startsWith('tests/') && !/^(?:tests\/Unit|tests\/Feature)\/.+Test\.php$/.test(r.path)).map(r => r.path),
    projectPHP: Object.keys(inventory(app)).filter(p => p.endsWith('.php') && !p.startsWith('vendor/')).length,
    testSetSHA256: hash(Buffer.from(rows.filter(r => r.path.startsWith('tests/')).map(r => `${r.path}\0${r.git_blob}\0${r.sha256}\0${r.bytes}`).sort().join('\n'))) };
}

function reconstruct(out) {
  assert(!fs.existsSync(out)); fs.mkdirSync(out);
  const evidence = path.join(out, 'evidence'); fs.mkdirSync(evidence);
  const report = git('show', MAIN + ':' + REPORT); const rows = parseReport(report);
  const compressed = fixtureBytes(); const patch = readFixture(compressed);
  json(path.join(evidence, 'negative-checks.json'), negatives(report, patch, rows, compressed));
  const derived = patchPHP(patch, p => git('show', PREIMAGE + ':' + p));
  const payloads = new Map();
  for (const r of rows) {
    const bytes = derived.outputs.get(r.path) ?? git('cat-file', 'blob', r.git_blob);
    verifyBytes(r, bytes); payloads.set(r.path, bytes);
  }
  assert.equal([...derived.outputs.keys()].filter(p => payloads.has(p)).length, 12);
  const entries = git('ls-tree', '-r', '-z', HOST).toString('utf8').split('\0').filter(Boolean);
  assert(entries.every(e => /^100(?:644|755) blob /.test(e)), 'Base tree includes a symlink/submodule');
  const basePHP = entries.map(e => e.split('\t')[1]).filter(p => p.startsWith(CMS) && validPath(p.slice(CMS.length)));
  assert.equal(basePHP.length, 727); assert(basePHP.every(p => payloads.has(p.slice(CMS.length))));
  const composer = git('show', HOST + ':' + CMS + 'composer.json');
  assert(composer.equals(git('show', MAIN + ':' + CMS + 'composer.json')));
  const oldLock = git('show', HOST + ':' + CMS + 'composer.lock');
  const newLock = git('show', MAIN + ':' + CMS + 'composer.lock');
  assert.equal(hash(oldLock), OLD_LOCK); assert.equal(hash(newLock), NEW_LOCK);
  // git archive preserves raw Git bytes, including non-PHP files; no live checkout is copied.
  const archive = git('archive', '--format=tar', HOST);
  const snapshots = {}; const census = {};
  for (const leg of ['baseline', 'candidate']) {
    const root = path.join(out, leg); fs.mkdirSync(root);
    execFileSync('tar', ['-xf', '-', '-C', root], { input: archive });
    assert(!fs.existsSync(path.join(root, CMS, '.env')));
    for (const [p, b] of payloads) { fs.mkdirSync(path.dirname(path.join(root, CMS, p)), { recursive: true }); fs.writeFileSync(path.join(root, CMS, p), b); }
    if (leg === 'candidate') fs.writeFileSync(path.join(root, CMS, 'composer.lock'), newLock);
    census[leg] = verifyObserved(root, rows); snapshots[leg] = inventory(root, true);
  }
  const changes = [...new Set([...Object.keys(snapshots.baseline), ...Object.keys(snapshots.candidate)])]
    .filter(p => snapshots.baseline[p] !== snapshots.candidate[p]);
  assert.deepEqual(changes, [CMS + 'composer.lock']);
  json(path.join(out, 'source-snapshots.json'), { rows, snapshots });
  json(path.join(evidence, 'reconstruction.json'), {
    controlHEAD: git('rev-parse', 'HEAD').toString().trim(), main: MAIN, hostHEAD: HOST, patchPreimage: PREIMAGE,
    report: REPORT, reportSHA256: REPORT_SHA, reportBytes: report.length, patchSHA256: PATCH_SHA,
    patchBytes: patch.length, fixtureTransport: 'standard gzip; decoded original patch byte-identical',
    fixtureGzipBytes: compressed.length, fixtureGzipSHA256: hash(compressed),
    originalArtifact: 13186312279, originalRun: 41540790119,
    originalArtifactZIP_SHA256: '9212fcbc227859aa876654ebcb4c821c4caff5f6e2a575bb48768f2344eb1f4c',
    originalRunConclusion: 'failure; source patch independently verified', ignoredCSS: derived.ignored,
    rawGitPayloads: 753, patchPHP: 12, oldLockSHA256: OLD_LOCK, newLockSHA256: NEW_LOCK,
    composerJSON_SHA256: hash(composer), changedPathsBeforeInstall: changes, census, sourceFiles: rows,
    nonObservedInputs: 'All non-observed files solely frozen hostHEAD Git bytes; NOT attested actual host bytes.',
    hostingComposerCoverage: 'Both legs use pinned Composer2.11.1, NOT actual hosting Composer2.8.12 install behavior.',
    releaseApproval: false,
  });
  console.log('B1111_RECONSTRUCTION=' + JSON.stringify({ census, changedPaths: changes }));
}

function verifyAfter(out) {
  const { rows, snapshots } = JSON.parse(fs.readFileSync(path.join(out, 'source-snapshots.json')));
  const result = {};
  for (const leg of ['baseline', 'candidate']) {
    const root = path.join(out, leg);
    // Vendor and generated caches are isolated scratch; every original observed file is still verified.
    for (const row of rows) verifyBytes(row, fs.readFileSync(path.join(root, CMS, row.path)));
    const now = inventory(root, true); assert.deepEqual(now, snapshots[leg], leg + ': unexpected source change after tests');
    const observed = Object.keys(now).filter(p => p.startsWith(CMS) && validPath(p.slice(CMS.length))).sort();
    assert.deepEqual(observed, rows.map(r => CMS + r.path).sort());
    result[leg] = { unchangedObservedPHP: rows.length, unchangedTestsPHP: 94, unexpectedSourceChanges: [] };
  }
  json(path.join(out, 'evidence', 'post-test-source.json'), result); console.log(JSON.stringify(result));
}

function manifest(out) {
  const dir = path.join(out, 'evidence'); fs.mkdirSync(dir, { recursive: true });
  const permitted = /^(?:reconstruction|negative-checks|post-test-source|comparison)\.json$|^(?:baseline|candidate)\.(?:log|xml|exit|install\.log|install\.exit|list\.xml|list\.log|list\.exit|static\.log|static\.exit)$|^candidate-audit\.(?:json|stderr|exit)$|^(?:reconstruct|verify|compare|download-composer)\.(?:log|exit)$|^composer\.sha256$/;
  const entries = [];
  for (const name of fs.readdirSync(dir)) {
    assert(permitted.test(name), 'Unexpected/private artifact: ' + name);
    const file = path.join(dir, name); assert(fs.lstatSync(file).isFile());
    const bytes = fs.readFileSync(file); entries.push({ path: name, bytes: bytes.length, sha256: hash(bytes) });
  }
  assert(entries.length > 0); json(path.join(dir, 'artifact-manifest.json'), entries);
}

const [mode, target] = process.argv.slice(2);
if (mode === 'reconstruct') { assert.equal(process.env.CI, 'true', 'Reconstruction runs on GitHub CI only'); reconstruct(path.resolve(target)); }
else if (mode === 'verify') verifyAfter(path.resolve(target));
else if (mode === 'manifest') manifest(path.resolve(target));
else if (mode === 'self-check') {
  const report = git('show', MAIN + ':' + REPORT); const rows = parseReport(report);
  const compressed = fixtureBytes(); const patch = readFixture(compressed);
  const checks = negatives(report, patch, rows, compressed); const result = patchPHP(patch, p => git('show', PREIMAGE + ':' + p));
  for (const [p, b] of result.outputs) verifyBytes(rows.find(r => r.path === p), b);
  for (const row of rows) verifyBytes(row, result.outputs.get(row.path) ?? git('cat-file', 'blob', row.git_blob));
  console.log(JSON.stringify({ checks, patchPHP: result.outputs.size, rawGitPayloads: 753, reportRows: rows.length, verifiedPayloads: rows.length,
    gzipSHA256: hash(compressed), decodedSHA256: hash(patch), gzipBytes: compressed.length, decodedBytes: patch.length }));
} else throw Error('Expected reconstruct|verify|manifest|self-check and external output path');
