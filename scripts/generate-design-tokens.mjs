import fs from 'node:fs';
import path from 'node:path';

const project = path.resolve(import.meta.dirname, '..');

const sourceFile = path.join(
  project,
  'packages/design-tokens/ald1n-operator.json'
);

const mobileOut = path.join(
  project,
  'apps/mobile/current/src/design/ald1n-tokens.generated.ts'
);

const webOut = path.join(
  project,
  'packages/web-theme/ald1n-operator.css'
);

const checkOnly = process.argv.includes('--check');

const data = JSON.parse(
  fs.readFileSync(sourceFile, 'utf8')
);

if (
  !Array.isArray(data.palette?.light) ||
  data.palette.light.length !== 12 ||
  !Array.isArray(data.palette?.dark) ||
  data.palette.dark.length !== 12
) {
  throw new Error(
    'Light i Dark Tamagui palette moraju imati po 12 boja.'
  );
}

const bannedAiPurple = new Set([
  '#6d45e5',
  '#a98cff',
  '#7e5be6',
  '#4b2baa',
  '#c4b2ff',
  '#d9caff',
]);

function parseHexColor(value) {
  if (
    typeof value !== 'string' ||
    !/^#[0-9a-f]{6}$/i.test(value)
  ) {
    throw new Error(
      `Neispravna HEX boja: ${String(value)}`
    );
  }

  return [
    Number.parseInt(value.slice(1, 3), 16),
    Number.parseInt(value.slice(3, 5), 16),
    Number.parseInt(value.slice(5, 7), 16),
  ];
}

function linearChannel(value) {
  const channel = value / 255;

  return channel <= 0.04045
    ? channel / 12.92
    : ((channel + 0.055) / 1.055) ** 2.4;
}

function relativeLuminance(value) {
  const [red, green, blue] =
    parseHexColor(value).map(linearChannel);

  return (
    0.2126 * red +
    0.7152 * green +
    0.0722 * blue
  );
}

function contrastRatio(first, second) {
  const firstLuminance = relativeLuminance(first);
  const secondLuminance = relativeLuminance(second);

  const lighter = Math.max(
    firstLuminance,
    secondLuminance
  );

  const darker = Math.min(
    firstLuminance,
    secondLuminance
  );

  return (
    (lighter + 0.05) /
    (darker + 0.05)
  );
}

function assertContrast(label, first, second, minimum = 4.5) {
  const ratio = contrastRatio(first, second);

  if (ratio < minimum) {
    throw new Error(
      `${label} nema dovoljan kontrast: ` +
      `${ratio.toFixed(2)}:1, minimum ${minimum}:1`
    );
  }

  console.log(
    `PASS ${label} contrast ${ratio.toFixed(2)}:1`
  );
}

for (const scheme of ['light', 'dark']) {
  const theme = data[scheme];

  if (!theme || typeof theme !== 'object') {
    throw new Error(
      `${scheme} design theme nedostaje.`
    );
  }

  for (const [key, value] of Object.entries(theme)) {
    if (
      typeof value === 'string' &&
      value.startsWith('#') &&
      bannedAiPurple.has(value.toLowerCase())
    ) {
      throw new Error(
        `${scheme}.${key} koristi zabranjeni AI-purple ton ${value}.`
      );
    }
  }

  if (
    String(theme.accent).toLowerCase() !==
    String(theme.primary).toLowerCase()
  ) {
    throw new Error(
      `${scheme}.accent mora pratiti jedinstveni brand primary accent.`
    );
  }

  assertContrast(
    `${scheme} primary/onPrimary`,
    theme.primary,
    theme.onPrimary
  );

  assertContrast(
    `${scheme} text/background`,
    theme.text,
    theme.background
  );

  assertContrast(
    `${scheme} muted/background`,
    theme.muted,
    theme.background
  );

  assertContrast(
    `${scheme} primaryContainer/onPrimaryContainer`,
    theme.primaryContainer,
    theme.onPrimaryContainer
  );

  assertContrast(
    `${scheme} danger/onDanger`,
    theme.danger,
    theme.onDanger
  );
}

fs.mkdirSync(
  path.dirname(mobileOut),
  { recursive: true }
);

fs.mkdirSync(
  path.dirname(webOut),
  { recursive: true }
);

function emit(file, content) {
  const relative = path.relative(project, file);

  if (checkOnly) {
    if (!fs.existsSync(file)) {
      throw new Error(
        `Generated output ne postoji: ${relative}`
      );
    }

    const current = fs.readFileSync(file, 'utf8');

    if (current !== content) {
      throw new Error(
        `Generated output nije sinhronizovan: ${relative}. ` +
        `Pokreni scripts/generate-design-tokens.mjs.`
      );
    }

    console.log(
      `PASS up-to-date ${relative}`
    );

    return;
  }

  fs.writeFileSync(file, content);

  console.log(
    `PASS generated ${relative}`
  );
}

const generatedTs = `// AUTO-GENERATED.
// Source: packages/design-tokens/ald1n-operator.json
// Ne menjati rucno.

export const ald1nDesignTokens = ${JSON.stringify({
  light: data.light,
  dark: data.dark,
  spacing: data.spacing,
  radii: data.radii,
  motion: data.motion
}, null, 2)} as const;

export const ald1nLightPalette = ${JSON.stringify(
  data.palette.light,
  null,
  2
)} as const;

export const ald1nDarkPalette = ${JSON.stringify(
  data.palette.dark,
  null,
  2
)} as const;

export type Ald1nColorScheme = 'light' | 'dark';
`;

function foundationVars() {
  return [
    `  --ald-radius-sm: ${data.radii.sm}px;`,
    `  --ald-radius-md: ${data.radii.md}px;`,
    `  --ald-radius-lg: ${data.radii.lg}px;`,
    `  --ald-radius-xl: ${data.radii.xl}px;`,
    `  --ald-radius-xxl: ${data.radii.xxl}px;`,
    `  --ald-radius-pill: ${data.radii.pill}px;`,
    `  --ald-motion-instant: ${data.motion.instant};`,
    `  --ald-motion-press: ${data.motion.press};`,
    `  --ald-motion-fast: ${data.motion.fast};`,
    `  --ald-motion-standard: ${data.motion.standard};`,
    `  --ald-motion-emphasized: ${data.motion.emphasized};`,
    `  --ald-motion-slow: ${data.motion.slow};`,
    `  --ald-ease-out: ${data.motion.easeOut};`,
    `  --ald-ease-in-out: ${data.motion.easeInOut};`,
    `  --ald-ease-sheet: ${data.motion.easeSheet};`,
  ].join('\n');
}

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
    `  --ald-on-primary: ${theme.onPrimary};`,
    `  --ald-primary-strong: ${theme.primaryStrong};`,
    `  --ald-primary-container: ${theme.primaryContainer};`,
    `  --ald-on-primary-container: ${theme.onPrimaryContainer};`,
    `  --ald-secondary-container: ${theme.secondaryContainer};`,
    `  --ald-accent: ${theme.accent};`,
    `  --ald-accent-soft: ${theme.accentSoft};`,
    `  --ald-success: ${theme.success};`,
    `  --ald-success-soft: ${theme.successSoft};`,
    `  --ald-warning: ${theme.warning};`,
    `  --ald-warning-soft: ${theme.warningSoft};`,
    `  --ald-danger: ${theme.danger};`,
    `  --ald-on-danger: ${theme.onDanger};`,
    `  --ald-danger-soft: ${theme.dangerSoft};`,
    `  --ald-info: ${theme.info};`,
    `  --ald-info-soft: ${theme.infoSoft};`,
    ``,
    foundationVars(),
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
 * Source: packages/design-tokens/ald1n-operator.json
 *
 * Canonical Build16 Operator theme tokens.
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

emit(mobileOut, generatedTs);
emit(webOut, generatedCss);
