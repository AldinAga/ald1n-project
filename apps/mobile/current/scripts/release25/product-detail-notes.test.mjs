import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const read = (relative) => readFileSync(new URL('../../src/' + relative, import.meta.url), 'utf8');
const screen = read('app/(app)/product/[slug].tsx');
const types = read('types/api.ts');

test('Product type carries optional nullable notes', () => {
  assert.match(types, /notes\?: Nullable<string>;/);
});

test('detail screen renders the Napomena block only for non-empty notes', () => {
  assert.match(screen, /product\.notes && product\.notes\.trim\(\) !== ''/);
  assert.match(screen, /<Text style=\{styles\.sectionTitle\}>Napomena<\/Text>/);
});

test('Napomena block sits directly below the description and above specifications', () => {
  const description = screen.indexOf('Opis artikla');
  const notes = screen.indexOf('>Napomena<');
  const specs = screen.indexOf('>Specifikacije<');
  assert.ok(description > 0 && description < notes && notes < specs);
});

test('Napomena block is accessible and never clipped', () => {
  const start = screen.indexOf('product.notes && product.notes.trim()');
  const block = screen.slice(start, screen.indexOf('product.specifications?.length'));
  assert.match(block, /accessibilityRole="summary"/);
  assert.match(block, /accessibilityLabel=\{`Napomena: /);
  assert.doesNotMatch(block, /numberOfLines|ellipsizeMode/);
});
