import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import test from 'node:test';
import ts from 'typescript';

const read = (relative) => readFileSync(new URL('../../src/' + relative, import.meta.url), 'utf8');

test('Keyboard reveal calculates the minimum scroll displacement without jumping visible fields', () => {
  const source = read('components/layout/keyboard-visibility.ts');
  const compiled = ts.transpileModule(source, { compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022 } });
  const exports = {};
  runInNewContext(compiled.outputText, { exports });
  const { neededKeyboardScroll } = exports;
  assert.equal(neededKeyboardScroll(630, 800), 0);
  assert.equal(neededKeyboardScroll(780, 800), 20);
  assert.equal(neededKeyboardScroll(860, 800), 100);
  assert.equal(neededKeyboardScroll(860.2, 800, 40), 101);
  assert.equal(neededKeyboardScroll(Number.NaN, 800), 0);
  assert.equal(neededKeyboardScroll(800, Number.NaN), 0);
  assert.equal(neededKeyboardScroll(800, 790, -200), 10);
});

test('Product Create/Edit opt into Android keyboard reveal without global navigation mutation', () => {
  for (const relative of ['app/(app)/admin/catalog/create.tsx', 'app/(app)/admin/catalog/[id].tsx']) {
    const source = read(relative);
    assert.match(source, /<Screen\s+keyboardAware\s+contentStyle=\{styles\.content\}/);
    assert.match(source, /<ProductEditorWorkspaceNav/);
    assert.match(source, /<ProductEditorSaveDock/);
    assert.match(source, /activeWorkspace/);
  }
  const screen = read('components/layout/screen.tsx');
  assert.match(screen, /keyboardAware\?: boolean/);
  assert.match(screen, /Platform\.OS !== 'android'/);
  assert.match(screen, /Keyboard\.addListener\('keyboardDidShow'/);
  assert.match(screen, /Keyboard\.addListener\('keyboardDidHide'/);
  assert.match(screen, /UIManager\.measureInWindow\(target/);
  assert.match(screen, /neededKeyboardScroll\(top \+ height/);
  assert.match(screen, /scrollRef\.current\.scrollTo\(/);
  assert.match(screen, /paddingBottom: 140 \+ keyboardHeight/);
  assert.match(screen, /keyboardTopRef\.current = null/);
  assert.match(screen, /<KeyboardScrollContext\.Provider value=\{keyboardAware \? onFieldFocus : null\}>/);
});

test('Shared Tamagui TextField preserves caller onFocus and only forwards native targets', () => {
  const source = read('components/ui/text-field.tsx');
  assert.match(source, /const revealFocusedInput = useContext\(KeyboardScrollContext\)/);
  assert.match(source, /onFocus\?\.\(event\)/);
  assert.match(source, /if \(typeof target === 'number'\) revealFocusedInput\?\.\(target\)/);
  assert.match(source, /\.\.\.inputProps/);
});

test('Existing Android resize baseline and mounted editor workspaces remain preserved', () => {
  const config = readFileSync(new URL('../../app.config.js', import.meta.url), 'utf8');
  assert.match(config, /softwareKeyboardLayoutMode: 'resize'/);
  const nav = read('features/admin/product-editor-workspace.tsx');
  assert.match(nav, /Hidden sections remain mounted/);
  assert.match(nav, /if \(keyboardVisible\) return null/);
});
