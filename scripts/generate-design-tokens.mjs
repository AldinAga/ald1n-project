import fs from 'node:fs';
import path from 'node:path';

const project = path.resolve(import.meta.dirname, '..');

const sourceFile = path.join(
  project,
  'packages/design-tokens/ald1n-violet.json'
);

const mobileOut = path.join(
  project,
  'apps/mobile/current/src/design/ald1n-tokens.generated.ts'
);

const webOut = path.join(
  project,
  'packages/web-theme/ald1n-violet.css'
);

const data = JSON.parse(fs.readFileSync(sourceFile, 'utf8'));

if (
  !Array.isArray(data.palette?.light) ||
  data.palette.light.length !== 12 ||
  !Array.isArray(data.palette?.dark) ||
  data.palette.dark.length !== 12
) {
  throw new Error('Light i Dark Tamagui palette moraju imati po 12 boja.');
}

fs.mkdirSync(path.dirname(mobileOut), { recursive: true });
fs.mkdirSync(path.dirname(webOut), { recursive: true });

const generatedTs = `// AUTO-GENERATED.
// Source: packages/design-tokens/ald1n-violet.json
// Ne menjati rucno.

export const ald1nDesignTokens = ${JSON.stringify({
  light: data.light,
  dark: data.dark,
  spacing: data.spacing,
  radii: data.radii,
  motion: data.motion
}, null, 2)} as const;

export const ald1nLightPalette = ${JSON.stringify(data.palette.light, null, 2)} as const;

export const ald1nDarkPalette = ${JSON.stringify(data.palette.dark, null, 2)} as const;

export type Ald1nColorScheme = 'light' | 'dark';
`;

fs.writeFileSync(mobileOut, generatedTs);

function cssVars(theme) {
  return [
    `  --ald-background: ${theme.background};`,
    `  --ald-surface: ${theme.surface};`,
    `  --ald-surface-muted: ${theme.surfaceMuted};`,
    `  --ald-surface-container: ${theme.surfaceContainer};`,
    `  --ald-surface-container-high: ${theme.surfaceContainerHigh};`,
    `  --ald-text: ${theme.text};`,
    `  --ald-muted: ${theme.muted};`,
    `  --ald-border: ${theme.border};`,
    `  --ald-outline: ${theme.outline};`,
    `  --ald-primary: ${theme.primary};`,
    `  --ald-primary-strong: ${theme.primaryStrong};`,
    `  --ald-primary-container: ${theme.primaryContainer};`,
    `  --ald-on-primary-container: ${theme.onPrimaryContainer};`,
    `  --ald-secondary-container: ${theme.secondaryContainer};`,
    `  --ald-accent: ${theme.accent};`,
    `  --ald-success: ${theme.success};`,
    `  --ald-warning: ${theme.warning};`,
    `  --ald-danger: ${theme.danger};`,
    `  --ald-info: ${theme.info};`,
    ``,
    `  /* Laravel compatibility aliases */`,
    `  --pozadina: var(--ald-background);`,
    `  --kartica: var(--ald-surface);`,
    `  --tekst: var(--ald-text);`,
    `  --muted: var(--ald-muted);`,
    `  --border: var(--ald-border);`,
    `  --input-bg: var(--ald-surface-muted);`,
    `  --input-brd: var(--ald-outline);`,
    `  --btn-bg: var(--ald-surface-container);`,
    `  --btn-brd: var(--ald-border);`,
    `  --btn-txt: var(--ald-text);`,
    `  --tbl-hover: var(--ald-surface-container);`,
    `  --menu-wrapper-bg: var(--ald-surface);`,
    `  --menu-border: var(--ald-border);`,
    `  --menu-btn-text: var(--ald-muted);`,
    `  --menu-btn-hover-bg: var(--ald-primary-container);`,
    `  --menu-btn-hover-text: var(--ald-primary-strong);`,
    `  --menu-btn-active-bg: var(--ald-primary-container);`,
    `  --menu-btn-active-text: var(--ald-primary);`,
    `  --success: var(--ald-success);`,
    `  --danger: var(--ald-danger);`
  ].join('\n');
}

const generatedCss = `/*
 * AUTO-GENERATED.
 * Source: packages/design-tokens/ald1n-violet.json
 *
 * Jos se NE ucitava na Laravel produkciji.
 */

html[data-theme="light"],
body[data-theme="light"] {
${cssVars(data.light)}
}

html[data-theme="dark"],
body[data-theme="dark"] {
${cssVars(data.dark)}
}
`;

fs.writeFileSync(webOut, generatedCss);

console.log(`PASS generated ${path.relative(project, mobileOut)}`);
console.log(`PASS generated ${path.relative(project, webOut)}`);
