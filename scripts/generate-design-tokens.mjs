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

for (const scheme of ['light', 'dark']) {
  const theme = data[scheme];

  if (!theme || typeof theme !== 'object') {
    throw new Error(
      `${scheme} design theme nedostaje.`
    );
  }

  if (!theme.primary || !theme.onPrimary) {
    throw new Error(
      `${scheme} mora imati primary i onPrimary.`
    );
  }

  const ratio = contrastRatio(
    theme.primary,
    theme.onPrimary
  );

  if (ratio < 4.5) {
    throw new Error(
      `${scheme}.onPrimary nema dovoljan kontrast sa primary: ` +
      `${ratio.toFixed(2)}:1`
    );
  }

  console.log(
    `PASS ${scheme} primary/onPrimary contrast ` +
    `${ratio.toFixed(2)}:1`
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
// Source: packages/design-tokens/ald1n-violet.json
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
    `  --ald-success: ${theme.success};`,
    `  --ald-warning: ${theme.warning};`,
    `  --ald-danger: ${theme.danger};`,
    `  --ald-on-danger: ${theme.onDanger};`,
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

emit(mobileOut, generatedTs);
emit(webOut, generatedCss);
