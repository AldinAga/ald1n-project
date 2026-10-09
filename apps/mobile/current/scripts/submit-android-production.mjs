#!/usr/bin/env node

// PRODUCTION_SUBMIT_HARDENING_BATCH505R
import fs from 'node:fs';
import path from 'node:path';
import { spawnSync } from 'node:child_process';

const root = path.resolve(import.meta.dirname, '..');
const EXPECTED_PACKAGE = 'com.ald1n.mobile';
const EXPECTED_APP_VERSION = '1.0.0';
const EXPECTED_RUNTIME = '1.0.0-build17';
const EXPECTED_API = 'https://cms.ald1n.com/api/v1';
const EAS_CLI_VERSION = '24.7.0';
const NODE_BIN = '/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node';
const NPM_CLI = '/opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js';
const UUID_RE = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;

function die(message, code = 64) {
  console.error(`ERROR ${message}`);
  process.exit(code);
}

function requireFile(file) {
  if (!fs.existsSync(file)) die(`required file missing: ${file}`, 66);
  return file;
}

const args = process.argv.slice(2);
if (args.length !== 2 || !['--check', '--execute'].includes(args[0])) {
  die('usage: npm run submit:android:production -- --check|--execute <EAS_BUILD_ID>');
}
const mode = args[0];
// DIRECT_SUBMIT_DISABLED -- requires verified AAB, cannot use raw --id path.
if (mode === '--execute') die('DIRECT_SUBMIT_DISABLED: use verified Build25 controller', 78);
const buildId = args[1];
if (!UUID_RE.test(buildId)) die('EAS_BUILD_ID must be an explicit UUID');

const packageJson = JSON.parse(fs.readFileSync(requireFile(path.join(root, 'package.json')), 'utf8'));
const easJson = JSON.parse(fs.readFileSync(requireFile(path.join(root, 'eas.json')), 'utf8'));

if (packageJson.version !== EXPECTED_APP_VERSION) die(`app version mismatch: ${packageJson.version}`, 65);
if (easJson.cli?.version !== EAS_CLI_VERSION) die(`eas cli pin mismatch: ${easJson.cli?.version}`, 65);
if (easJson.build?.production?.channel !== 'production') die('production build channel mismatch', 65);
if (easJson.build?.production?.env?.EXPO_PUBLIC_APP_ENV !== 'production') die('production app env mismatch', 65);
if (easJson.build?.production?.env?.EXPO_PUBLIC_API_URL !== EXPECTED_API) die('production API URL mismatch', 65);
if (easJson.submit?.production?.android?.track !== 'production') die('Google Play track mismatch', 65);
if (easJson.submit?.production?.android?.releaseStatus !== 'completed') die('Google Play release status mismatch', 65);

const productionEnv = {
  ...process.env,
  EXPO_PUBLIC_APP_ENV: 'production',
  EXPO_PUBLIC_API_URL: EXPECTED_API,
};
const expoCli = requireFile(path.join(root, 'node_modules', 'expo', 'bin', 'cli'));
const configResult = spawnSync(process.execPath, [expoCli, 'config', '--type', 'public', '--json'], {
  cwd: root,
  env: productionEnv,
  encoding: 'utf8',
  maxBuffer: 16 * 1024 * 1024,
});
if (configResult.status !== 0) {
  if (configResult.stderr) process.stderr.write(configResult.stderr);
  die(`expo config failed rc=${configResult.status}`, 65);
}
let expoConfig;
try {
  expoConfig = JSON.parse(configResult.stdout);
} catch {
  die('expo config did not return valid JSON', 65);
}
if (expoConfig.android?.package !== EXPECTED_PACKAGE) die(`android package mismatch: ${expoConfig.android?.package}`, 65);
if (expoConfig.version !== EXPECTED_APP_VERSION) die(`expo app version mismatch: ${expoConfig.version}`, 65);
if (expoConfig.runtimeVersion !== EXPECTED_RUNTIME) die(`runtimeVersion mismatch: ${expoConfig.runtimeVersion}`, 65);
if (expoConfig.extra?.appEnv !== 'production') die(`extra.appEnv mismatch: ${expoConfig.extra?.appEnv}`, 65);
if (expoConfig.extra?.apiUrl !== EXPECTED_API) die(`extra.apiUrl mismatch: ${expoConfig.extra?.apiUrl}`, 65);

const submitArgs = [
  NPM_CLI,
  'exec',
  '--yes',
  `--package=eas-cli@${EAS_CLI_VERSION}`,
  '--',
  'eas',
  'submit',
  '--platform', 'android',
  '--profile', 'production',
  '--id', buildId,
  '--non-interactive',
  '--wait',
];

console.log(`PRODUCTION_SUBMIT_HELPER=PASS`);
console.log(`APP_VERSION=${EXPECTED_APP_VERSION}`);
console.log(`RUNTIME_VERSION=${EXPECTED_RUNTIME}`);
console.log(`ANDROID_PACKAGE=${EXPECTED_PACKAGE}`);
console.log(`EAS_BUILD_ID=${buildId}`);
console.log(`EAS_CLI_VERSION=${EAS_CLI_VERSION}`);
console.log(`BUILD_PROFILE=production`);
console.log(`PLAY_TRACK=production`);
console.log(`RELEASE_STATUS=completed`);
console.log(`SUBMIT_COMMAND=${NODE_BIN} ${submitArgs.join(' ')}`);

if (mode === '--check') {
  console.log('SUBMIT_WOULD_RUN=NO');
  process.exit(0);
}

console.log('SUBMIT_WOULD_RUN=YES');
const submit = spawnSync(NODE_BIN, submitArgs, {
  cwd: root,
  env: productionEnv,
  stdio: 'inherit',
});
if (submit.error) die(`failed to start EAS submit: ${submit.error.message}`, 69);
process.exit(submit.status ?? 1);
