#!/usr/bin/env node
/*
 * Kobalt token generator. Zero dependencies, Node >= 20.
 *
 *   node scripts/generate-ald1n-tokens.mjs          write both outputs
 *   node scripts/generate-ald1n-tokens.mjs --check  fail if an output is stale,
 *                                                   a contrast gate fails or the
 *                                                   storefront parity breaks
 *
 * Source:  packages/tokens/ald1n.tokens.json
 * Outputs: apps/cms/current/public/assets/css/ald1n-tokens.css
 *          apps/mobile/current/src/design/ald1n-tokens.kobalt.generated.ts
 * Output is deterministic: sorted keys, LF, no timestamps, fixed number format.
 *
 * Does not touch packages/design-tokens, generate-design-tokens.mjs or any
 * file they write; that pipeline stays until its last consumer is migrated.
 *
 * @author Ald1n <https://ald1n.com>
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

export const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
export const PATHS = {
  source: 'packages/tokens/ald1n.tokens.json',
  css: 'apps/cms/current/public/assets/css/ald1n-tokens.css',
  ts: 'apps/mobile/current/src/design/ald1n-tokens.kobalt.generated.ts',
  storefront: 'apps/cms/current/public/assets/css/ald1n-public-storefront.css',
  gallery: 'apps/cms/current/public/assets/css/ald1n-public-gallery.css',
};
export const MODES = ['dark', 'light'];
const MODE_GROUPS = ['color', 'shadow'];
const UNIT_KEY = 'com.ald1n.unit';
const PREFIX = '--a1-';

/* ------------------------------------------------------------------ */
/* Tree helpers                                                        */
/* ------------------------------------------------------------------ */

const isToken = (n) => n !== null && typeof n === 'object' && !Array.isArray(n) && '$value' in n;

/** Integer-like keys first and numeric (the order JS itself enumerates), then code-point order. */
export function compareKeys(a, b) {
  const ai = /^(0|[1-9]\d*)$/.test(a);
  const bi = /^(0|[1-9]\d*)$/.test(b);
  if (ai && bi) return Number(a) - Number(b);
  if (ai) return -1;
  if (bi) return 1;
  return a < b ? -1 : a > b ? 1 : 0;
}

export function sortedKeys(obj) {
  return Object.keys(obj).sort(compareKeys);
}

/** Every key of every object in the source must already be sorted. */
export function unsortedPaths(node, trail = []) {
  const bad = [];
  if (node === null || typeof node !== 'object' || Array.isArray(node) || isToken(node)) return bad;
  const keys = Object.keys(node);
  const want = sortedKeys(node);
  if (keys.join('\u0000') !== want.join('\u0000')) bad.push(trail.join('.') || '(root)');
  for (const k of keys) bad.push(...unsortedPaths(node[k], [...trail, k]));
  return bad;
}

/** Yields {full, mode, type, unit, value} for every token, groups in sorted order. */
export function* leaves(node, full = [], meta = {}) {
  const m = {
    type: node.$type ?? meta.type,
    unit: node.$extensions?.[UNIT_KEY] ?? meta.unit,
    source: node.$extensions?.['com.ald1n.source'] ?? meta.source,
  };
  if (isToken(node)) {
    const mode = MODE_GROUPS.includes(full[0]) ? full[1] : null;
    yield { full, mode, type: m.type, unit: m.unit, source: m.source, value: node.$value };
    return;
  }
  for (const k of sortedKeys(node)) {
    if (k.startsWith('$')) continue;
    yield* leaves(node[k], [...full, k], m);
  }
}

export function getAt(tokens, full) {
  let n = tokens;
  for (const k of full) n = n?.[k];
  return n;
}

const ALIAS = /^\{([^{}]+)\}$/;
const aliasPath = (v) => (typeof v === 'string' && ALIAS.test(v) ? ALIAS.exec(v)[1].split('.') : null);

export function resolveColor(tokens, value, depth = 0) {
  const target = aliasPath(value);
  if (!target) return value;
  if (depth > 8) throw new Error(`Alias loop at ${value}`);
  const node = getAt(tokens, target);
  if (!isToken(node)) throw new Error(`Unknown alias ${value}`);
  return resolveColor(tokens, node.$value, depth + 1);
}

const kebab = (s) => s.replace(/([a-z])([A-Z0-9])/g, '$1-$2').toLowerCase();

/** Path inside the mode group, e.g. ['color','dark','accent','link'] -> ['accent','link']. */
function nameParts(full) {
  const [g, ...rest] = full;
  if (MODE_GROUPS.includes(g)) {
    rest.shift();
    return g === 'color' ? rest : [g, ...rest];
  }
  return [g, ...rest];
}
export const cssName = (full) => PREFIX + nameParts(full).map(kebab).join('-');

/** Plain number text, no leading zero before the dot (".2", "-.03"). */
export function fmt(n) {
  return String(n).replace(/^(-?)0\./, '$1.');
}

/* ------------------------------------------------------------------ */
/* Colour maths (WCAG 2.x, gamma-space blending as browsers do)        */
/* ------------------------------------------------------------------ */

export function parseColor(input) {
  const s = String(input).trim().toLowerCase();
  let m = /^#([0-9a-f]{3})$/.exec(s);
  if (m) return [...m[1]].map((c) => parseInt(c + c, 16)).concat(1);
  m = /^#([0-9a-f]{6})([0-9a-f]{2})?$/.exec(s);
  if (m) {
    const h = m[1];
    return [0, 2, 4].map((i) => parseInt(h.slice(i, i + 2), 16)).concat(m[2] ? parseInt(m[2], 16) / 255 : 1);
  }
  m = /^rgba?\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)\s*(?:,\s*([\d.]+)\s*)?\)$/.exec(s);
  if (m) return [Number(m[1]), Number(m[2]), Number(m[3]), m[4] === undefined ? 1 : Number(m[4])];
  throw new Error(`Unsupported colour: ${input}`);
}

/** Composite top over an opaque bottom; channels rounded to 8 bit. */
export function over(top, bottom) {
  const a = top[3];
  return [0, 1, 2].map((i) => Math.round(top[i] * a + bottom[i] * (1 - a))).concat(1);
}

function luminance([r, g, b]) {
  const lin = (v) => {
    const c = v / 255;
    return c <= 0.04045 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4;
  };
  return 0.2126 * lin(r) + 0.7152 * lin(g) + 0.0722 * lin(b);
}

/** WCAG contrast ratio of two opaque colours (CSS strings or channel arrays). */
export function contrastRatio(fg, bg) {
  const f = typeof fg === 'string' ? parseColor(fg) : fg;
  const b = typeof bg === 'string' ? parseColor(bg) : bg;
  if (f[3] !== 1 || b[3] !== 1) throw new Error('contrastRatio needs opaque colours; composite first');
  const l1 = luminance(f);
  const l2 = luminance(b);
  return (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05);
}

/**
 * Documented known gaps: a real storefront value that fails a gate. The value is
 * NOT changed; the pair is listed here with the measured ratio (2 decimals).
 * A stale entry (pair now passes, or the ratio moved) fails the check.
 * id format: "<mode>: <label>".
 */
export const KNOWN_GAPS = {};

const GLASS_SURFACES = ['standard', 'dense', 'soft', 'hover', 'strong'];

export function evaluateGates(tokens, knownGaps = KNOWN_GAPS) {
  const results = [];
  for (const mode of MODES) {
    const c = tokens.color[mode];
    const val = (group, ...p) => {
      let n = c[group];
      for (const k of p) n = n[k];
      return resolveColor(tokens, n.$value);
    };
    const page = parseColor(val('surface', 'page'));
    // Backdrops under the glass: the plain page and the page with one glow at full strength.
    const backdrops = [{ name: 'page', rgb: page }];
    for (const n of [1, 2, 3, 4]) {
      backdrops.push({ name: `page+glow.${n}`, rgb: over(parseColor(val('glow', String(n))), page) });
    }
    // Every glass surface text can sit on.
    const surfaces = GLASS_SURFACES.map((s) => ({ name: `glass.${s}`, fill: parseColor(val('glass', s)) }));
    /** Minimum ratio over all surface x backdrop pairs; names the worst pair. */
    const addOnGlass = (label, fg, min, under) => {
      let worst = null;
      for (const b of backdrops) {
        for (const s of surfaces) {
          const base = over(s.fill, b.rgb);
          const bg = under ? over(under, base) : base;
          const ratio = contrastRatio(fg[3] === 1 ? fg : over(fg, bg), bg);
          if (!worst || ratio < worst.ratio) worst = { ratio, where: `${s.name} over ${b.name}` };
        }
      }
      results.push({ id: `${mode}: ${label}`, mode, label, ratio: worst.ratio, min, worst: worst.where });
    };
    const add = (label, fg, bg, min, worst) => {
      results.push({ id: `${mode}: ${label}`, mode, label, ratio: contrastRatio(fg, bg), min, worst });
    };
    const text = (k) => parseColor(val('text', k));
    addOnGlass('text.primary on every glass surface', text('primary'), 7);
    addOnGlass('text.secondary on every glass surface', text('secondary'), 4.5);
    addOnGlass('text.tertiary on every glass surface', text('tertiary'), 4.5);
    addOnGlass('accent.link on every glass surface', parseColor(val('accent', 'link')), 4.5);
    const on = parseColor(val('button', 'primary', 'on'));
    add('button.primary.on on button.primary.bg', on, parseColor(val('button', 'primary', 'bg')), 4.5, 'opaque');
    add('button.primary.on on button.primary.hover', on, parseColor(val('button', 'primary', 'hover')), 4.5, 'opaque');
    for (const s of sortedKeys(c.status)) {
      const bg = parseColor(val('status', s, 'bg'));
      addOnGlass(`status.${s}.text on status.${s}.bg over every glass surface`, parseColor(val('status', s, 'text')), 4.5, bg);
      if (c.status[s].strong) {
        addOnGlass(`status.${s}.strong on status.${s}.bg over every glass surface`, parseColor(val('status', s, 'strong')), 4.5, bg);
      }
    }
    // The ring is drawn at the edge of an element, over the page and its glows.
    let ring = null;
    for (const b of backdrops) {
      const r = contrastRatio(parseColor(val('focus', 'ring')), b.rgb);
      if (!ring || r < ring.ratio) ring = { ratio: r, where: b.name };
    }
    results.push({ id: `${mode}: focus.ring on page background`, mode, label: 'focus.ring on page background', ratio: ring.ratio, min: 3, worst: ring.where });
  }
  const errors = [];
  for (const r of results) {
    r.shown = r.ratio.toFixed(2);
    const gap = knownGaps[r.id];
    r.pass = r.ratio >= r.min;
    r.known = Boolean(gap);
    if (!r.pass && !gap) errors.push(`Contrast gate failed: ${r.id} = ${r.shown}:1, needs ${r.min}:1`);
    if (!r.pass && gap && gap.ratio !== r.shown) errors.push(`Known gap moved: ${r.id} is ${r.shown}:1, list says ${gap.ratio}:1`);
    if (r.pass && gap) errors.push(`Stale known gap: ${r.id} now passes (${r.shown}:1); remove it from KNOWN_GAPS`);
  }
  for (const id of Object.keys(knownGaps)) {
    if (!results.some((r) => r.id === id)) errors.push(`Stale known gap: no gate named "${id}"`);
  }
  return { results, errors };
}

/* ------------------------------------------------------------------ */
/* Value rendering                                                     */
/* ------------------------------------------------------------------ */

/** layers -> CSS box-shadow text in storefront order ("0 1px 0 <c> inset,..."). */
function shadowCss(tokens, layers, ref) {
  const px = (n) => (n === 0 ? '0' : `${fmt(n)}px`);
  return layers
    .map((l) => {
      const t = aliasPath(l.color);
      const color = t ? `var(${ref(t)})` : l.color;
      const parts = [px(l.offsetX), px(l.offsetY), px(l.blur)];
      if (l.spread !== 0) parts.push(px(l.spread));
      parts.push(color);
      if (l.inset) parts.push('inset');
      return parts.join(' ');
    })
    .join(',');
}

function gradientCss(g) {
  const stops = g.stops.map((s) => {
    const pos = Math.round(s.position * 10000) / 100;
    return pos === 0 ? s.color : `${s.color} ${fmt(pos)}%`;
  });
  return `linear-gradient(${g.angle}deg,${stops.join(',')})`;
}

/** CSS text of one token. ref(fullPath) names the custom property an alias points at. */
export function cssValue(tokens, leaf, ref = cssName) {
  const { type, unit, value } = leaf;
  switch (type) {
    case 'color': {
      const t = aliasPath(value);
      return t ? `var(${ref(t)})` : value;
    }
    case 'shadow':
      return shadowCss(tokens, value, ref);
    case 'gradient':
      return gradientCss(value);
    case 'dimension':
      return `${fmt(value)}${unit ?? 'px'}`;
    case 'duration':
      return `${fmt(value)}${unit ?? 'ms'}`;
    case 'cubicBezier':
      return `cubic-bezier(${value.map(fmt).join(',')})`;
    case 'number':
      return `${fmt(value)}${unit ?? ''}`;
    case 'fontWeight':
      return String(value);
    case 'fontFamily':
      return value;
    default:
      throw new Error(`Unknown $type "${type}" at ${leaf.full.join('.')}`);
  }
}

function decl(name, value) {
  return `  ${name}: ${value};`;
}

/* ------------------------------------------------------------------ */
/* CSS output                                                          */
/* ------------------------------------------------------------------ */

const HEADER = `/*
 * AUTO-GENERATED. Do not edit.
 * Source:    packages/tokens/ald1n.tokens.json
 * Generator: node scripts/generate-ald1n-tokens.mjs
 * Palette:   Kobalt (the storefront palette, shared by storefront, admin CMS and Android app).
 * Prefix:    --a1-  (no collision with --bg, --ald-*, --lg*)
 * Author:    Ald1n <https://ald1n.com>
 *
 * Theme selectors. Both conventions in use today work without changing either:
 *
 *   Storefront  <html data-theme="system|light|dark">, cookie shop_theme.
 *               "system" follows prefers-color-scheme.
 *   Admin CMS   <html data-theme="light|dark" data-theme-mode="auto|light|dark">,
 *               localStorage ald1n-theme-mode. The bootstrap script resolves
 *               "auto" itself and always writes the resolved light or dark into
 *               data-theme, so the admin never sets data-theme="system".
 *
 *   :root                          dark, the default (also with no attribute)
 *   :root[data-theme="light"]      light
 *   :root[data-theme="system"]     light when prefers-color-scheme is light
 *
 * data-theme-mode is not needed here: it only records the user's choice, the
 * resolved value is always in data-theme. Unlike the storefront file, which
 * also turns light for a page with no attribute, a page with no data-theme is
 * dark here; both real consumers always set the attribute.
 *
 * Fallbacks are token overrides placed after the themes with specificity at
 * least equal to the light block, so they win in every mode. They replace the
 * fill and effect aliases (--a1-glass-fill*, --a1-fx*), not the glass tokens
 * themselves; the storefront does the same. prefers-reduced-transparency and
 * prefers-contrast: more mirror the storefront rules. The forced-colors layer
 * is derived: the storefront handles forced colors per component with system
 * colours, here the same system colours are applied at token level.
 */
`;

function fallbackLayers() {
  const solid = 'var(--a1-surface-solid)';
  const fills = [
    ['glass-fill', solid],
    ['glass-fill-strong', solid],
    ['glass-fill-soft', solid],
  ];
  const flat = [
    ['glass-hover', solid],
    ['fx', 'none'],
    ['fx-strong', 'none'],
    ['glow-1', 'transparent'],
    ['glow-2', 'transparent'],
    ['glow-3', 'transparent'],
    ['glow-4', 'transparent'],
    ['edge-sheen', 'transparent'],
    ['edge-rim', 'transparent'],
    ['gradient-button-gloss', 'none'],
  ];
  const contrast = [
    ['text-secondary', 'var(--a1-text-primary)'],
    ['text-tertiary', 'var(--a1-text-primary)'],
    ['border-normal', 'var(--a1-border-strong)'],
    ['image-stage-text', 'var(--a1-fallback-contrast-image-stage-text)'],
  ];
  const forcedFill = 'Canvas';
  const forced = [
    ['surface-page', forcedFill],
    ['surface-solid', forcedFill],
    ['surface-field', forcedFill],
    ['glass-fill', forcedFill],
    ['glass-fill-strong', forcedFill],
    ['glass-fill-soft', forcedFill],
    ['glass-hover', forcedFill],
    ['text-primary', 'CanvasText'],
    ['text-secondary', 'CanvasText'],
    ['text-tertiary', 'CanvasText'],
    ['border-normal', 'CanvasText'],
    ['border-strong', 'CanvasText'],
    ['accent-link', 'LinkText'],
    ['button-primary-bg', 'ButtonFace'],
    ['button-primary-hover', 'ButtonFace'],
    ['button-primary-on', 'ButtonText'],
    ['back-to-top-bg', 'ButtonFace'],
    ['back-to-top-hover', 'ButtonFace'],
    ['back-to-top-border', 'ButtonText'],
    ['focus-ring', 'Highlight'],
    ['fx', 'none'],
    ['fx-strong', 'none'],
    ['glow-1', 'transparent'],
    ['glow-2', 'transparent'],
    ['glow-3', 'transparent'],
    ['glow-4', 'transparent'],
    ['edge-sheen', 'transparent'],
    ['edge-rim', 'transparent'],
    ['gradient-button-gloss', 'none'],
  ];
  const block = (list) => list.map(([n, v]) => decl(PREFIX + n, v)).join('\n');
  // ":root" alone covers pages without data-theme; ":root[data-theme]" has the
  // same specificity (0,2,0) as the light block and comes later, so it wins.
  const sel = ':root,\n:root[data-theme]';
  return [
    '/* Fallback: no backdrop-filter (same condition as the storefront, for Safari). */',
    '@supports not ((-webkit-backdrop-filter:blur(1px)) or (backdrop-filter:blur(1px))) {',
    `${sel} {`,
    block([...fills, ['edge-sheen', 'transparent']]),
    '}',
    '}',
    '',
    '/* Fallback: reduced transparency. */',
    '@media (prefers-reduced-transparency: reduce) {',
    `${sel} {`,
    block([...fills, ...flat]),
    '}',
    '}',
    '',
    '/* Fallback: more contrast. */',
    '@media (prefers-contrast: more) {',
    `${sel} {`,
    block([...fills, ...flat, ...contrast]),
    '}',
    '}',
    '',
    '/* Fallback: forced colors (derived, see header). */',
    '@media (forced-colors: active) {',
    `${sel} {`,
    block(forced),
    '}',
    '}',
  ].join('\n');
}

export function buildCss(tokens) {
  const all = [...leaves(tokens)];
  const names = new Map();
  for (const l of all) {
    const n = cssName(l.full);
    const key = `${l.mode ?? ''}|${n}`;
    if (names.has(key)) throw new Error(`Duplicate CSS name ${n}`);
    names.set(key, l);
  }
  const modeDecls = (mode) =>
    all.filter((l) => l.mode === mode).map((l) => decl(cssName(l.full), cssValue(tokens, l))).join('\n');
  const shared = all.filter((l) => l.mode === null).map((l) => decl(cssName(l.full), cssValue(tokens, l))).join('\n');
  // Mode-dependent names must be identical in both modes.
  const nameSet = (mode) => all.filter((l) => l.mode === mode).map((l) => cssName(l.full)).join('|');
  if (nameSet('dark') !== nameSet('light')) throw new Error('Light and dark token sets differ');

  const aliases = [
    decl('--a1-glass-fill', 'var(--a1-glass-standard)'),
    decl('--a1-glass-fill-strong', 'var(--a1-glass-strong)'),
    decl('--a1-glass-fill-soft', 'var(--a1-glass-soft)'),
    decl('--a1-fx', 'blur(var(--a1-blur-standard-radius)) saturate(var(--a1-blur-standard-saturate))'),
    decl('--a1-fx-strong', 'blur(var(--a1-blur-strong-radius)) saturate(var(--a1-blur-strong-saturate))'),
  ].join('\n');

  return [
    HEADER,
    ':root {',
    '  color-scheme: dark;',
    modeDecls('dark'),
    '',
    shared,
    '',
    aliases,
    '}',
    '',
    ':root[data-theme="light"] {',
    '  color-scheme: light;',
    modeDecls('light'),
    '}',
    '',
    '@media (prefers-color-scheme: light) {',
    ':root[data-theme="system"] {',
    '  color-scheme: light;',
    modeDecls('light'),
    '}',
    '}',
    '',
    '/* Phones and touch screens: cards use the dense fill (storefront rule). */',
    '@media (max-width: 899px), (hover: none) {',
    ':root {',
    '  --a1-glass-fill: var(--a1-glass-dense);',
    '}',
    '}',
    '',
    fallbackLayers(),
    '',
  ].join('\n');
}

/* ------------------------------------------------------------------ */
/* TypeScript (React Native) output                                    */
/* ------------------------------------------------------------------ */

/** RN-facing names where the CSS unit would be misleading; the value is unchanged. */
const TS_RENAME = {
  'typography.letterSpacing': 'letterSpacingEm',
  'typography.lineHeight': 'lineHeightRatio',
  'typography.fontFamily': 'fontFamilyCssStack',
  'blur.standard.saturate': 'saturatePercent',
  'blur.strong.saturate': 'saturatePercent',
};

function tsColor(css) {
  const s = css.trim().toLowerCase();
  if (s.startsWith('#')) return s;
  const [r, g, b, a] = parseColor(s);
  return `rgba(${r}, ${g}, ${b}, ${Number(a.toFixed(4))})`;
}

function tsValue(tokens, leaf) {
  const { type, value } = leaf;
  switch (type) {
    case 'color':
      return tsColor(resolveColor(tokens, value));
    case 'shadow':
      // React Native boxShadow layer objects (New Architecture only).
      return value.map((l) => ({
        offsetX: l.offsetX,
        offsetY: l.offsetY,
        blurRadius: l.blur,
        spreadDistance: l.spread,
        color: tsColor(resolveColor(tokens, l.color)),
        inset: l.inset,
      }));
    case 'gradient':
      return {
        angleCssDeg: value.angle,
        colors: value.stops.map((s) => tsColor(s.color)),
        locations: value.stops.map((s) => s.position),
      };
    default:
      return value;
  }
}

function setDeep(obj, parts, value) {
  let n = obj;
  parts.slice(0, -1).forEach((k) => {
    n[k] ??= {};
    n = n[k];
  });
  n[parts[parts.length - 1]] = value;
}

function tsKey(k) {
  return /^[A-Za-z_$][\w$]*$/.test(k) || /^(0|[1-9]\d*)$/.test(k) ? k : JSON.stringify(k);
}

function tsLit(v, depth = 0) {
  const pad = '  '.repeat(depth + 1);
  const end = '  '.repeat(depth);
  if (Array.isArray(v)) {
    if (v.every((x) => x === null || typeof x !== 'object')) return `[${v.map((x) => tsLit(x)).join(', ')}]`;
    return `[\n${v.map((x) => pad + tsLit(x, depth + 1)).join(',\n')},\n${end}]`;
  }
  if (v !== null && typeof v === 'object') {
    return `{\n${Object.keys(v).map((k) => `${pad}${tsKey(k)}: ${tsLit(v[k], depth + 1)}`).join(',\n')},\n${end}}`;
  }
  return JSON.stringify(v);
}

export function buildTs(tokens) {
  const all = [...leaves(tokens)];
  const palette = (mode) => {
    const o = {};
    for (const l of all.filter((x) => x.mode === mode)) {
      // color -> palette.<group>.<name>; shadow -> palette.shadow.<name>
      const parts = l.full[0] === 'color' ? l.full.slice(2) : [l.full[0], ...l.full.slice(2)];
      setDeep(o, parts, tsValue(tokens, l));
    }
    return o;
  };
  let scale = {};
  for (const l of all.filter((x) => x.mode === null)) {
    // Rename a path segment where the CSS unit would mislead in React Native.
    const parts = l.full.map((seg, i) => TS_RENAME[l.full.slice(0, i + 1).join('.')] ?? seg);
    setDeep(scale, parts, tsValue(tokens, l));
  }
  // Rebuild every object with sorted keys so the renamed groups sit in order.
  const resort = (v) =>
    v !== null && typeof v === 'object' && !Array.isArray(v)
      ? Object.fromEntries(sortedKeys(v).map((k) => [k, resort(v[k])]))
      : v;
  scale = resort(scale);

  return `// AUTO-GENERATED. Do not edit.
// Source: packages/tokens/ald1n.tokens.json
// Generator: node scripts/generate-ald1n-tokens.mjs
// Palette: Kobalt (same values as the storefront). Not wired into the app yet.
// Author: Ald1n <https://ald1n.com>
//
// React Native notes:
// - Colours are hex or rgba() strings React Native accepts.
// - Dimensions are numbers without units (px in CSS, dp in React Native).
// - duration values are milliseconds; easing values are cubic-bezier [x1, y1, x2, y2].
// - letterSpacingEm is in em; React Native letterSpacing is in px, multiply by the font size.
// - lineHeightRatio is a multiplier; React Native lineHeight is absolute, multiply by the font size.
// - fontWeight values include variable-font steps (560, 620, 640, 650, 750, 780);
//   React Native accepts only 100-step weights, so snap them before use.
// - fontFamilyCssStack is the CSS stack, not a React Native fontFamily name.
// - shadow layers use the React Native boxShadow shape; boxShadow needs the New Architecture.
// - angleCssDeg is a CSS angle (0 = up); React Native has no CSS gradient angle.

export const kobaltDark = ${tsLit(palette('dark'))} as const;

export const kobaltLight = ${tsLit(palette('light'))} as const;

export const kobaltScale = ${tsLit(scale)} as const;

export const kobaltModes = ['dark', 'light'] as const;

export type KobaltMode = (typeof kobaltModes)[number];

// Same shape as the dark palette with the literal values widened, so light and dark can be compared.
type Widen<T> = T extends string
  ? string
  : T extends number
    ? number
    : T extends boolean
      ? boolean
      : T extends readonly (infer U)[]
        ? readonly Widen<U>[]
        : { readonly [K in keyof T]: Widen<T[K]> };

export type KobaltPalette = Widen<typeof kobaltDark>;

export const kobalt = {
  dark: kobaltDark satisfies KobaltPalette,
  light: kobaltLight satisfies KobaltPalette,
  scale: kobaltScale,
} as const;
`;
}

/* ------------------------------------------------------------------ */
/* Storefront parity                                                   */
/* ------------------------------------------------------------------ */

/** New colour token (path inside the mode group) -> storefront/gallery custom property. */
export const PARITY_COLORS = {
  'accent.link': 'accent',
  'accent.soft': 'accent-soft',
  'backToTop.bg': 'top-bg',
  'backToTop.border': 'top-border',
  'backToTop.hover': 'top-bg-hover',
  'backToTop.ring': 'top-ring',
  'border.normal': 'border',
  'border.strong': 'border-strong',
  'button.ghost.bg': 'ghost-bg',
  'button.ghost.hover': 'ghost-bg-hover',
  'button.primary.bg': 'btn-bg',
  'button.primary.hover': 'btn-bg-hover',
  'button.primary.on': 'btn-text',
  'edge.light': 'edge-light',
  'edge.rim': 'rim',
  'edge.sheen': 'sheen',
  'focus.ring': 'focus',
  'glass.dense': 'glass-dense',
  'glass.hover': 'glass-hover',
  'glass.soft': 'glass-soft',
  'glass.standard': 'glass',
  'glass.strong': 'glass-strong',
  'glow.1': 'glow-1',
  'glow.2': 'glow-2',
  'glow.3': 'glow-3',
  'glow.4': 'glow-4',
  'heading.from': 'heading-from',
  'heading.to': 'heading-to',
  'image.lightboxBg': 'lightbox-bg',
  'image.photoBed': 'photo-bed',
  'image.photoRing': 'photo-ring',
  'image.stage': 'stage',
  'image.stageEnd': 'stage-2',
  'image.stageText': 'stage-text',
  'image.thumbRing': 'thumb-ring',
  'status.danger.bg': 'error-bg',
  'status.danger.dot': 'error-border',
  'status.danger.text': 'error-text',
  'status.info.bg': 'alert-bg',
  'status.info.dot': 'alert-border',
  'status.info.text': 'alert-text',
  'status.off.bg': 'off-bg',
  'status.off.dot': 'off',
  'status.off.text': 'off-text',
  'status.ok.bg': 'ok-bg',
  'status.ok.dot': 'ok',
  'status.ok.text': 'ok-text',
  'status.warning.bg': 'draft-bg',
  'status.warning.dot': 'draft-border',
  'status.warning.strong': 'draft-strong',
  'status.warning.text': 'draft-text',
  'surface.field': 'field',
  'surface.page': 'bg',
  'surface.solid': 'surface-solid',
  'text.primary': 'text',
  'text.secondary': 'text-2',
  'text.tertiary': 'text-3',
};

export const PARITY_SHADOWS = {
  button: 'btn-shadow',
  card: 'shadow-card',
  cardHover: 'shadow-hover',
  header: 'shadow-header',
  photo: 'photo-shadow',
};

export const PARITY_GRADIENTS = { buttonGloss: 'btn-gloss' };

/** Property names in the theme blocks that are deliberately not colour tokens. */
export const NOT_TOKENISED = {
  chevron: 'inline SVG data URI whose stroke is a literal colour; stays in the component CSS',
  font: 'typography.fontFamily.sans (checked against the same property)',
  'font-display': 'typography.fontFamily.display (checked against the same property)',
  radius: 'radius.md (checked against the same property)',
  'radius-lg': 'radius.lg (checked against the same property)',
  'radius-sm': 'radius.sm (checked against the same property)',
  ease: 'motion.easing.standard (checked against the same property)',
  'photo-radius': 'radius.photo (checked against the same property)',
};

/** Groups the JSON may mark as derived (a design decision with no single storefront source). */
export const DERIVED_GROUPS = ['space'];

/** The CSS keyword `ease` is defined by the CSS Easing spec, not by the storefront. */
const CSS_EASE = [0.25, 0.1, 0.25, 1];

/* ------------------------------------------------------------------ */
/* Minimal CSS parser: rules with their @media/@supports context       */
/* ------------------------------------------------------------------ */

/** Normalise a selector or an at-rule prelude so formatting does not matter. */
export function normPrelude(s) {
  const t = s.trim();
  if (t.startsWith('@')) return t.replace(/\s+/g, '');
  return t.replace(/["']/g, '').replace(/\s+/g, ' ').replace(/\s*([,>+~])\s*/g, '$1');
}

function parseDecls(body) {
  const out = {};
  const push = (s) => {
    const k = s.indexOf(':');
    if (k > 0) out[s.slice(0, k).trim().toLowerCase()] = s.slice(k + 1).trim();
  };
  let start = 0;
  let q = null;
  let paren = 0;
  for (let j = 0; j < body.length; j++) {
    const ch = body[j];
    if (q) {
      if (ch === '\\') j++;
      else if (ch === q) q = null;
    } else if (ch === '"' || ch === "'") q = ch;
    else if (ch === '(') paren++;
    else if (ch === ')') paren--;
    else if (ch === ';' && paren <= 0) {
      push(body.slice(start, j));
      start = j + 1;
    }
  }
  push(body.slice(start));
  return out;
}

/** Parses a stylesheet into [{ctx, selector, decls}]. @keyframes and other at-rules are skipped. */
export function parseCss(source) {
  const text = source.replace(/\/\*[\s\S]*?\*\//g, '');
  const rules = [];
  let i = 0;

  function scanTo(stops) {
    let q = null;
    let paren = 0;
    for (; i < text.length; i++) {
      const ch = text[i];
      if (q) {
        if (ch === '\\') i++;
        else if (ch === q) q = null;
      } else if (ch === '"' || ch === "'") q = ch;
      else if (ch === '(') paren++;
      else if (ch === ')') paren--;
      else if (paren <= 0 && stops.includes(ch)) return;
    }
  }

  function blockBody() {
    const start = i;
    let q = null;
    let depth = 1;
    for (; i < text.length; i++) {
      const ch = text[i];
      if (q) {
        if (ch === '\\') i++;
        else if (ch === q) q = null;
      } else if (ch === '"' || ch === "'") q = ch;
      else if (ch === '{') depth++;
      else if (ch === '}' && --depth === 0) {
        const body = text.slice(start, i);
        i++;
        return body;
      }
    }
    throw new Error('Unbalanced braces in CSS');
  }

  function parseRules(ctx, nested) {
    while (i < text.length) {
      const start = i;
      scanTo('{;}');
      const prelude = text.slice(start, i).trim();
      const ch = text[i];
      if (ch === undefined) return;
      i++;
      if (ch === '}') {
        if (nested) return;
        continue;
      }
      if (ch === ';') continue;
      if (/^@(media|supports)\b/i.test(prelude)) parseRules([...ctx, normPrelude(prelude)], true);
      else if (prelude.startsWith('@')) blockBody();
      else rules.push({ ctx, selector: normPrelude(prelude), decls: parseDecls(blockBody()) });
    }
  }

  parseRules([], false);
  return rules;
}

/** Exactly one rule with this selector in this context (and, optionally, this property). */
export function findRule(rules, selector, ctx = [], has = null) {
  const sel = normPrelude(selector);
  const c = ctx.map(normPrelude);
  const hit = rules.filter(
    (r) => r.selector === sel && r.ctx.length === c.length && r.ctx.every((x, k) => x === c[k]) && (!has || has in r.decls),
  );
  if (hit.length !== 1) {
    throw new Error(`${hit.length} rules match "${selector}"${c.length ? ` in ${c.join(' > ')}` : ''}${has ? ` with ${has}` : ''}, need exactly 1`);
  }
  return hit[0];
}

/** The three theme blocks of a storefront-style stylesheet, found by selector, context and a property they define. */
export function themeBlocks(css, anchor) {
  const rules = parseCss(css);
  const strip = (d) => Object.fromEntries(Object.entries(d).filter(([k]) => k.startsWith('--')).map(([k, v]) => [k.slice(2), v]));
  const dark = findRule(rules, ':root', [], anchor);
  const light = findRule(rules, ':root[data-theme=light]', [], anchor);
  const system = findRule(rules, ':root:not([data-theme=dark])', ['@media (prefers-color-scheme:light)'], anchor);
  return { dark: strip(dark.decls), light: strip(light.decls), system: strip(system.decls) };
}

const sameDecls = (a, b) => JSON.stringify(sortedKeys(a).map((k) => [k, a[k]])) === JSON.stringify(sortedKeys(b).map((k) => [k, b[k]]));

/* ------------------------------------------------------------------ */
/* Scale parity: every value is read from the rule it belongs to       */
/* ------------------------------------------------------------------ */

const num = (s) => Number(String(s).replace(/(px|em|s)$/, ''));

function fontShorthand(v) {
  const m = /^(?:(\d{3})\s+)?([\d.]+)px\/([\d.]+)(?:\s|$)/.exec(String(v ?? ''));
  return m ? { weight: m[1] ? Number(m[1]) : undefined, size: Number(m[2]), lh: Number(m[3]) } : null;
}

/** What a typography row reads from the rule: the longhand property, else the font shorthand. */
const READ = {
  size: (d) => (d['font-size'] !== undefined ? num(d['font-size']) : fontShorthand(d.font)?.size),
  weight: (d) => (d['font-weight'] !== undefined ? num(d['font-weight']) : fontShorthand(d.font)?.weight),
  lh: (d) => (d['line-height'] !== undefined ? num(d['line-height']) : fontShorthand(d.font)?.lh),
  ls: (d) => (d['letter-spacing'] !== undefined ? num(d['letter-spacing']) : undefined),
};

const SEL_BRAND_SUB = '.shop-brand-text span,.shop-brand-sub';
const SEL_MENU = '.shop-menu a,.shop-nav-user';
const SEL_CHECKOUT_INPUT = '.shop-checkout input:not([type=checkbox]):not([type=radio]),.shop-checkout textarea,.shop-checkout select';

/** token path -> [storefront selector (top level), what to read]. */
export const TYPE_PARITY = [
  ['typography.fontSize.base', 'body.public-retail', 'size'],
  ['typography.fontSize.lg', '.shop-brand-text', 'size'],
  ['typography.fontSize.md', SEL_MENU, 'size'],
  ['typography.fontSize.mdPlus', '.shop-product-title', 'size'],
  ['typography.fontSize.sm', '.shop-field label', 'size'],
  ['typography.fontSize.smPlus', '.shop-chip', 'size'],
  ['typography.fontSize.xl', '.shop-detail-content h2', 'size'],
  ['typography.fontSize.xl2', '.legal-article h2', 'size'],
  ['typography.fontSize.xl3', '.shop-price', 'size'],
  ['typography.fontSize.xl4', '.shop-price-amount', 'size'],
  ['typography.fontSize.xs', SEL_BRAND_SUB, 'size'],
  ['typography.fontSize.xsPlus', '.eyebrow', 'size'],
  ['typography.fontWeight.black', '.shop-brand-mark', 'weight'],
  ['typography.fontWeight.bold', '.eyebrow', 'weight'],
  ['typography.fontWeight.button', 'button', 'weight'],
  ['typography.fontWeight.display', '.shop-brand-text', 'weight'],
  ['typography.fontWeight.label', '.shop-field label', 'weight'],
  ['typography.fontWeight.medium', SEL_BRAND_SUB, 'weight'],
  ['typography.fontWeight.nav', SEL_MENU, 'weight'],
  ['typography.fontWeight.numeric', '.shop-trust strong', 'weight'],
  ['typography.fontWeight.regular', SEL_CHECKOUT_INPUT, 'weight'],
  ['typography.fontWeight.semibold', '.shop-details-link', 'weight'],
  ['typography.fontWeight.stock', '.shop-stock', 'weight'],
  ['typography.lineHeight.body', 'body.public-retail', 'lh'],
  ['typography.lineHeight.button', '.shop-button', 'lh'],
  ['typography.lineHeight.caption', SEL_BRAND_SUB, 'lh'],
  ['typography.lineHeight.detail', '.shop-detail-content h1', 'lh'],
  ['typography.lineHeight.display', '.shop-hero-catalog h1', 'lh'],
  ['typography.lineHeight.heading', '.shop-hero h1', 'lh'],
  ['typography.lineHeight.lead', '.shop-hero-catalog .shop-hero-lead', 'lh'],
  ['typography.lineHeight.results', '.shop-results', 'lh'],
  ['typography.lineHeight.snug', '.shop-brand-text', 'lh'],
  ['typography.lineHeight.title', '.shop-product-title', 'lh'],
  ['typography.letterSpacing.button', 'button', 'ls'],
  ['typography.letterSpacing.display', '.shop-hero-catalog h1', 'ls'],
  ['typography.letterSpacing.eyebrow', '.eyebrow', 'ls'],
  ['typography.letterSpacing.heading', 'h1,h2,h3', 'ls'],
  ['typography.letterSpacing.label', '.shop-field label', 'ls'],
  ['typography.letterSpacing.normal', '.shop-results', 'ls'],
  ['typography.letterSpacing.price', '.shop-price-amount', 'ls'],
  ['typography.letterSpacing.sub', SEL_BRAND_SUB, 'ls'],
  ['typography.letterSpacing.title', '.shop-product-title', 'ls'],
];

const RM = '@media (prefers-reduced-motion:no-preference)';

function scaleParity(tokens, sfRules, gaRules, errors, covered) {
  const tok = (p) => getAt(tokens, p.split('.'))?.$value;
  const eq = (p, want) => {
    covered.add(p);
    const got = tok(p);
    if (JSON.stringify(got) !== JSON.stringify(want)) errors.push(`${p}: token ${JSON.stringify(got)} != storefront ${JSON.stringify(want)}`);
  };
  /** Run one lookup; a missing or ambiguous rule becomes an error, not a crash. */
  const attempt = (label, fn) => {
    try {
      fn();
    } catch (e) {
      errors.push(`${label}: ${e.message}`);
    }
  };
  const sf = (selector, ctx, has) => findRule(sfRules, selector, ctx, has).decls;
  const match = (decl, re, what) => {
    const m = re.exec(decl ?? '');
    if (!m) throw new Error(`${what}: ${re} not found in "${decl}"`);
    return m;
  };
  const ms = (s) => Math.round(Number(s) * 1000);

  attempt('theme root (radius, easing, fonts)', () => {
    const d = sf(':root', [], '--radius-sm');
    eq('radius.sm', num(d['--radius-sm']));
    eq('radius.md', num(d['--radius']));
    eq('radius.lg', num(d['--radius-lg']));
    eq('motion.easing.standard', match(d['--ease'], /^cubic-bezier\(([^)]+)\)$/, '--ease')[1].split(',').map(Number));
    eq('typography.fontFamily.sans', d['--font']);
    eq('typography.fontFamily.display', d['--font-display']);
  });
  attempt('radius.photo', () => eq('radius.photo', num(findRule(gaRules, ':root', [], '--photo-radius').decls['--photo-radius'])));
  attempt('radius.pill', () => eq('radius.pill', num(sf(SEL_MENU, [], 'border-radius')['border-radius'])));
  attempt('blur', () => {
    const d = sf(':root', [], '--glass-fx');
    for (const [k, prop] of [['standard', '--glass-fx'], ['strong', '--glass-fx-strong']]) {
      const m = match(d[prop], /^blur\(([\d.]+)px\) saturate\(([\d.]+)%\)$/, prop);
      eq(`blur.${k}.radius`, Number(m[1]));
      eq(`blur.${k}.saturate`, Number(m[2]));
    }
  });
  attempt('focus', () => {
    const base = sf(':focus-visible', [], 'outline');
    const m = match(base.outline, /^([\d.]+)px solid var\(--focus\)$/, ':focus-visible outline');
    eq('focus.width', Number(m[1]));
    eq('focus.offset', num(base['outline-offset']));
    const inset = sf('.shop-filters input[type=search]:focus-visible,.shop-filters select:focus-visible', [], 'outline-offset');
    eq('focus.offsetInset', num(inset['outline-offset']));
  });
  attempt('size', () => {
    eq('size.touchTarget', num(sf('button', [], 'min-height')['min-height']));
    eq('size.control', num(sf('.shop-button', [], 'min-height')['min-height']));
  });
  attempt('motion', () => {
    const fast = sf('.shop-button,button,.shop-details-link,.shop-chip,.shop-page,.shop-menu a,.shop-filters input,.shop-filters select,.shop-theme-option,.legal-card,.legal-nav a', [RM], 'transition').transition;
    eq('motion.duration.fast', ms(match(fast, /background-color ([\d.]+)s ease(?:,|$)/, 'fast transition')[1]));
    eq('motion.easing.basic', CSS_EASE); // the storefront uses the keyword `ease` there
    eq('motion.duration.base', ms(match(sf('.shop-toggle-track,.shop-toggle-track::after', [RM], 'transition').transition, /transform ([\d.]+)s var\(--ease\)/, 'toggle transition')[1]));
    eq('motion.duration.slow', ms(match(sf('.shop-product', [RM], 'transition').transition, /transform ([\d.]+)s var\(--ease\)/, 'card transition')[1]));
    eq('motion.duration.rise', ms(match(sf('.shop-product-cell:nth-child(n+5)', [RM], 'animation').animation, /shop-rise ([\d.]+)s var\(--ease\)/, 'rise animation')[1]));
    eq('motion.duration.lift', ms(match(sf('.shop-hero-catalog>*,.shop-filter-panel,.shop-product-cell:nth-child(-n+4)', [RM], 'animation').animation, /shop-lift ([\d.]+)s var\(--ease\)/, 'lift animation')[1]));
    eq('motion.duration.stagger', ms(match(sf('.shop-hero-catalog>:nth-child(2),.shop-product-cell:nth-child(2)', [RM], 'animation-delay')['animation-delay'], /^([\d.]+)s$/, 'stagger')[1]));
  });
  attempt('fallback.contrast.imageStageText', () => {
    eq('fallback.contrast.imageStageText', sf(':root[data-theme]', ['@media (prefers-contrast:more)'], '--stage-text')['--stage-text']);
  });
  for (const [path, selector, read] of TYPE_PARITY) {
    attempt(path, () => {
      covered.add(path);
      const want = READ[read](sf(selector));
      if (want === undefined) throw new Error(`rule "${selector}" has no ${read}`);
      eq(path, want);
    });
  }
}

/* ------------------------------------------------------------------ */
/* Parity entry point                                                  */
/* ------------------------------------------------------------------ */

export function parity(tokens, storefrontCss, galleryCss) {
  const errors = [];
  const covered = new Set();
  const sf = themeBlocks(storefrontCss, '--bg');
  const ga = themeBlocks(galleryCss, '--photo-bed');
  for (const [name, b] of [['storefront', sf], ['gallery', ga]]) {
    if (!sameDecls(b.system, b.light)) errors.push(`${name}: the system block differs from the light block, so deriving system from light is not allowed`);
  }
  // Effective light value = dark block overlaid with the light block (--btn-text, --btn-gloss exist in dark only).
  const effective = {
    dark: { ...sf.dark, ...ga.dark },
    light: { ...sf.dark, ...ga.dark, ...sf.light, ...ga.light },
  };
  const mapped = new Set([...Object.values(PARITY_COLORS), ...Object.values(PARITY_SHADOWS), ...Object.values(PARITY_GRADIENTS)]);

  // Forward: every colour, shadow and gradient token has a mapping and equals the storefront value.
  const refFor = () => (full) => {
    const key = nameParts(full).join('.');
    const prop = PARITY_COLORS[key];
    if (!prop) throw new Error(`No parity mapping for alias target ${key}`);
    return `--${prop}`;
  };
  for (const l of leaves(tokens)) {
    let table;
    let key;
    if (l.full[0] === 'color') {
      table = PARITY_COLORS;
      key = l.full.slice(2).join('.');
    } else if (l.full[0] === 'shadow') {
      table = PARITY_SHADOWS;
      key = l.full.slice(2).join('.');
    } else if (l.full[0] === 'gradient') {
      table = PARITY_GRADIENTS;
      key = l.full.slice(1).join('.');
    } else continue;
    covered.add(l.full.join('.'));
    const prop = table[key];
    if (!prop) {
      errors.push(`No parity mapping for ${l.full.join('.')}`);
      continue;
    }
    const want = effective[l.mode ?? 'dark'][prop];
    if (want === undefined) {
      errors.push(`Storefront has no --${prop} (${l.full.join('.')})`);
      continue;
    }
    const got = cssValue(tokens, l, refFor());
    if (got !== want) errors.push(`${l.full.join('.')}: token "${got}" != storefront --${prop} "${want}"`);
  }

  // Reverse: every custom property of the theme blocks is mapped or explicitly not tokenised.
  const seen = new Set([...Object.keys(sf.dark), ...Object.keys(sf.light), ...Object.keys(ga.dark), ...Object.keys(ga.light)]);
  for (const prop of seen) {
    if (!mapped.has(prop) && !(prop in NOT_TOKENISED)) errors.push(`Storefront --${prop} is neither mapped nor listed in NOT_TOKENISED`);
  }
  for (const prop of mapped) {
    if (!seen.has(prop)) errors.push(`Mapped property --${prop} no longer exists in the storefront theme blocks`);
  }
  for (const prop of Object.keys(NOT_TOKENISED)) {
    if (!seen.has(prop)) errors.push(`NOT_TOKENISED lists --${prop}, which is not in the theme blocks`);
  }

  // Scale, typography, motion, focus, size.
  scaleParity(tokens, parseCss(storefrontCss), parseCss(galleryCss), errors, covered);

  // Coverage: a token is either checked above or explicitly derived. Nothing is silently unchecked.
  for (const l of leaves(tokens)) {
    const id = l.full.join('.');
    if (l.source === 'derived') {
      if (!DERIVED_GROUPS.includes(l.full[0])) errors.push(`${id} is marked derived but ${l.full[0]} is not in DERIVED_GROUPS`);
    } else if (!covered.has(id)) errors.push(`${id} has no parity check and is not marked derived`);
  }
  return errors;
}

/* ------------------------------------------------------------------ */
/* Load, validate, run                                                 */
/* ------------------------------------------------------------------ */

const TYPES = new Set(['color', 'shadow', 'gradient', 'dimension', 'duration', 'cubicBezier', 'number', 'fontWeight', 'fontFamily']);

export function loadTokens(root = ROOT) {
  return JSON.parse(fs.readFileSync(path.join(root, PATHS.source), 'utf8'));
}

export function validateSource(tokens) {
  const errors = unsortedPaths(tokens).map((p) => `Keys not sorted at ${p}`);
  for (const l of leaves(tokens)) {
    if (!TYPES.has(l.type)) errors.push(`${l.full.join('.')}: unknown or missing $type "${l.type}"`);
    else if (l.type === 'color' && !aliasPath(l.value)) {
      try {
        parseColor(l.value);
      } catch {
        errors.push(`${l.full.join('.')}: invalid colour "${l.value}"`);
      }
    }
  }
  return errors;
}

export function build(tokens) {
  return { css: buildCss(tokens), ts: buildTs(tokens) };
}

const read = (root, rel) => fs.readFileSync(path.join(root, rel), 'utf8').replace(/\r\n/g, '\n');

export function run(argv = process.argv.slice(2), root = ROOT, log = console.log) {
  const check = argv.includes('--check');
  const tokens = loadTokens(root);
  const errors = validateSource(tokens);
  if (errors.length) {
    errors.forEach((e) => log(`FAIL ${e}`));
    return 1;
  }
  const out = build(tokens);

  const gates = evaluateGates(tokens);
  for (const r of gates.results) {
    log(`${r.pass ? 'PASS' : r.known ? 'GAP ' : 'FAIL'} ${r.id}: ${r.shown}:1 (min ${r.min}:1, worst: ${r.worst})`);
  }
  errors.push(...gates.errors);

  errors.push(...parity(tokens, read(root, PATHS.storefront), read(root, PATHS.gallery)));

  for (const [key, text] of [['css', out.css], ['ts', out.ts]]) {
    const file = path.join(root, PATHS[key]);
    if (check) {
      if (!fs.existsSync(file)) errors.push(`Missing output ${PATHS[key]}`);
      else if (read(root, PATHS[key]) !== text) errors.push(`Output out of date: ${PATHS[key]} (run node scripts/generate-ald1n-tokens.mjs)`);
      else log(`PASS up to date ${PATHS[key]}`);
    } else {
      fs.mkdirSync(path.dirname(file), { recursive: true });
      fs.writeFileSync(file, text);
      log(`WROTE ${PATHS[key]}`);
    }
  }
  errors.forEach((e) => log(`FAIL ${e}`));
  return errors.length ? 1 : 0;
}

if (process.argv[1] && import.meta.url === pathToFileURL(path.resolve(process.argv[1])).href) {
  process.exitCode = run();
}
