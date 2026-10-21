import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
const read = path => readFileSync(new URL('../../src/' + path, import.meta.url), 'utf8');
test('Home and Admin use native-safe bounded icon treatments without changing permission predicates', () => {
  const home = read('app/(app)/(tabs)/home.tsx');
  const admin = read('app/(app)/admin/index.tsx');
  const shortcut = read('components/ui/operator-row.tsx');
  assert.match(home, /<HomeActionList actions=\{quickActions\} variant="primary"/);
  assert.match(home, /<HomeActionList actions=\{myActivities\} variant="soft"/);
  assert.match(home, /<HomeActionList actions=\{adminActions\} variant="soft"/);
  assert.match(home, /can\('catalog\.manage_products'\)/);
  assert.match(admin, /can\('orders\.manage'\)/);
  assert.match(admin, /can\('catalog\.manage_products'\)/);
  assert.ok((admin.match(/<OperatorRow variant="soft"/g) ?? []).length >= 26);
  assert.match(shortcut, /width: 48, height: 48/);
  assert.match(shortcut, /useReducedMotion\(/);
  assert.equal((shortcut.match(/<Pressable\b/g) ?? []).length, 1);
  assert.doesNotMatch(shortcut, /LiquidGlassAndroidView|react-native-android-liquid-glass/);
  assert.doesNotMatch(home + admin, /LiquidGlassAndroidView/);
});
test('Native runtime and existing keyboard fix are preserved', () => {
  const manifest = JSON.parse(readFileSync(new URL('../../package.json', import.meta.url), 'utf8'));
  assert.equal(manifest.dependencies['react-native-android-liquid-glass'], undefined);
  const editor = read('app/(app)/admin/catalog/create.tsx');
  assert.match(editor, /keyboardAware/);
});
