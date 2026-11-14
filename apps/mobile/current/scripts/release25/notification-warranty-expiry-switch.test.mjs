import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const read = (path) => readFileSync(new URL('../../' + path, import.meta.url), 'utf8');

test('warranty-expiry switch is wired to the backend field in notification settings', () => {
  const screen = read('src/app/(app)/notification-settings.tsx');
  assert.match(screen, /key: 'warranty_expiry_alerts', title: 'Istek garancije'/);
  assert.match(read('src/types/api.ts'), /warranty_expiry_alerts: boolean;/);
  // Rendered by the same PreferenceRow/setPreference path as every other category switch.
  assert.match(screen, /preferenceRows\.map\(\(row\) => \(/);
  assert.match(screen, /preferenceMutation\.mutate\(\{ \[key\]: value \}\)/);
});
