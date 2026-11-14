// AUTO-GENERATED. Do not edit.
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

export const kobaltDark = {
  accent: {
    link: "#a9c3ff",
    soft: "rgba(128, 160, 238, 0.17)",
  },
  backToTop: {
    bg: "#3557c7",
    border: "#b5c8ff",
    hover: "#4466d6",
    ring: "#ffffff",
  },
  border: {
    normal: "rgba(206, 222, 234, 0.13)",
    strong: "rgba(206, 222, 234, 0.26)",
  },
  button: {
    ghost: {
      bg: "rgba(206, 222, 234, 0.07)",
      hover: "rgba(206, 222, 234, 0.13)",
    },
    primary: {
      bg: "#3557c7",
      hover: "#3e62d6",
      on: "#ffffff",
    },
  },
  edge: {
    light: "rgba(255, 255, 255, 0.13)",
    rim: "rgba(255, 255, 255, 0.1)",
    sheen: "rgba(255, 255, 255, 0.06)",
  },
  focus: {
    ring: "#9fd0ff",
  },
  glass: {
    dense: "rgba(24, 32, 38, 0.84)",
    hover: "rgba(34, 45, 53, 0.9)",
    soft: "rgba(30, 40, 47, 0.72)",
    standard: "rgba(24, 32, 38, 0.76)",
    strong: "rgba(15, 21, 26, 0.96)",
  },
  glow: {
    1: "rgba(88, 122, 196, 0.36)",
    2: "rgba(126, 104, 216, 0.32)",
    3: "rgba(64, 156, 184, 0.28)",
    4: "rgba(196, 140, 108, 0.2)",
  },
  heading: {
    from: "#ffffff",
    to: "#c6d6ff",
  },
  image: {
    lightboxBg: "#07090c",
    photoBed: "rgba(255, 255, 255, 0.035)",
    photoRing: "rgba(255, 255, 255, 0.1)",
    stage: "#e8ecf1",
    stageEnd: "#dbe1e8",
    stageText: "#44505c",
    thumbRing: "#a9c3ff",
  },
  status: {
    danger: {
      bg: "#3b1d28",
      dot: "#d7788f",
      text: "#ffe2e9",
    },
    info: {
      bg: "#12332f",
      dot: "#4fb7a3",
      text: "#d9fbf3",
    },
    off: {
      bg: "rgba(142, 154, 166, 0.16)",
      dot: "#8e9aa6",
      text: "#c0c9d2",
    },
    ok: {
      bg: "rgba(95, 226, 154, 0.13)",
      dot: "#5fe29a",
      text: "#9bf0c0",
    },
    warning: {
      bg: "#3a2d12",
      dot: "#e0b45c",
      strong: "#fff4d8",
      text: "#ffe8bb",
    },
  },
  surface: {
    field: "#111820",
    page: "#0d1216",
    solid: "#182026",
  },
  text: {
    primary: "#eef2f5",
    secondary: "#c8d2d9",
    tertiary: "#a7b4bd",
  },
  shadow: {
    button: [
      {
        offsetX: 0,
        offsetY: 1,
        blurRadius: 0,
        spreadDistance: 0,
        color: "rgba(255, 255, 255, 0.18)",
        inset: true,
      },
      {
        offsetX: 0,
        offsetY: 10,
        blurRadius: 24,
        spreadDistance: -12,
        color: "rgba(40, 72, 190, 0.85)",
        inset: false,
      },
    ],
    card: [
      {
        offsetX: 0,
        offsetY: 1,
        blurRadius: 0,
        spreadDistance: 0,
        color: "rgba(255, 255, 255, 0.13)",
        inset: true,
      },
      {
        offsetX: 0,
        offsetY: 18,
        blurRadius: 40,
        spreadDistance: -26,
        color: "rgba(0, 0, 0, 0.75)",
        inset: false,
      },
    ],
    cardHover: [
      {
        offsetX: 0,
        offsetY: 1,
        blurRadius: 0,
        spreadDistance: 0,
        color: "rgba(255, 255, 255, 0.13)",
        inset: true,
      },
      {
        offsetX: 0,
        offsetY: 28,
        blurRadius: 54,
        spreadDistance: -26,
        color: "rgba(0, 0, 0, 0.85)",
        inset: false,
      },
    ],
    header: [
      {
        offsetX: 0,
        offsetY: 1,
        blurRadius: 0,
        spreadDistance: 0,
        color: "rgba(255, 255, 255, 0.13)",
        inset: true,
      },
      {
        offsetX: 0,
        offsetY: 10,
        blurRadius: 30,
        spreadDistance: -18,
        color: "rgba(0, 0, 0, 0.7)",
        inset: false,
      },
    ],
    photo: [
      {
        offsetX: 0,
        offsetY: 1,
        blurRadius: 2,
        spreadDistance: 0,
        color: "rgba(0, 0, 0, 0.35)",
        inset: false,
      },
      {
        offsetX: 0,
        offsetY: 14,
        blurRadius: 30,
        spreadDistance: -18,
        color: "rgba(0, 0, 0, 0.85)",
        inset: false,
      },
    ],
  },
} as const;

export const kobaltLight = {
  accent: {
    link: "#2546ad",
    soft: "rgba(42, 77, 187, 0.09)",
  },
  backToTop: {
    bg: "#1f3f99",
    border: "#ffffff",
    hover: "#2a4fb8",
    ring: "#121b24",
  },
  border: {
    normal: "rgba(24, 40, 58, 0.11)",
    strong: "rgba(24, 40, 58, 0.22)",
  },
  button: {
    ghost: {
      bg: "rgba(24, 40, 58, 0.05)",
      hover: "rgba(24, 40, 58, 0.09)",
    },
    primary: {
      bg: "#2d50bf",
      hover: "#2746aa",
      on: "#ffffff",
    },
  },
  edge: {
    light: "rgba(255, 255, 255, 0.95)",
    rim: "rgba(255, 255, 255, 0.85)",
    sheen: "rgba(255, 255, 255, 0.7)",
  },
  focus: {
    ring: "#1d4fd6",
  },
  glass: {
    dense: "rgba(255, 255, 255, 0.86)",
    hover: "rgba(255, 255, 255, 0.94)",
    soft: "rgba(255, 255, 255, 0.7)",
    standard: "rgba(255, 255, 255, 0.74)",
    strong: "rgba(250, 251, 253, 0.96)",
  },
  glow: {
    1: "rgba(104, 140, 214, 0.4)",
    2: "rgba(150, 124, 232, 0.36)",
    3: "rgba(84, 178, 204, 0.26)",
    4: "rgba(232, 168, 124, 0.32)",
  },
  heading: {
    from: "#121b24",
    to: "#2b4fbd",
  },
  image: {
    lightboxBg: "#07090c",
    photoBed: "#fbf9f6",
    photoRing: "rgba(24, 40, 58, 0.1)",
    stage: "#f4f6f9",
    stageEnd: "#e8ecf1",
    stageText: "#4c5864",
    thumbRing: "#2546ad",
  },
  status: {
    danger: {
      bg: "#fdecef",
      dot: "#c4415f",
      text: "#5c1426",
    },
    info: {
      bg: "#e5f6f2",
      dot: "#2f9e88",
      text: "#0d3b33",
    },
    off: {
      bg: "#eceff4",
      dot: "#7c879a",
      text: "#475263",
    },
    ok: {
      bg: "#e4f5ea",
      dot: "#16a34a",
      text: "#11653a",
    },
    warning: {
      bg: "#fff4dc",
      dot: "#c58a1a",
      strong: "#3d2800",
      text: "#5c3d00",
    },
  },
  surface: {
    field: "#ffffff",
    page: "#edf0f4",
    solid: "#ffffff",
  },
  text: {
    primary: "#121b24",
    secondary: "#33404c",
    tertiary: "#3f4a56",
  },
  shadow: {
    button: [
      {
        offsetX: 0,
        offsetY: 1,
        blurRadius: 0,
        spreadDistance: 0,
        color: "rgba(255, 255, 255, 0.22)",
        inset: true,
      },
      {
        offsetX: 0,
        offsetY: 10,
        blurRadius: 22,
        spreadDistance: -12,
        color: "rgba(36, 64, 170, 0.7)",
        inset: false,
      },
    ],
    card: [
      {
        offsetX: 0,
        offsetY: 1,
        blurRadius: 0,
        spreadDistance: 0,
        color: "rgba(255, 255, 255, 0.95)",
        inset: true,
      },
      {
        offsetX: 0,
        offsetY: 1,
        blurRadius: 2,
        spreadDistance: 0,
        color: "rgba(18, 27, 36, 0.05)",
        inset: false,
      },
      {
        offsetX: 0,
        offsetY: 16,
        blurRadius: 34,
        spreadDistance: -24,
        color: "rgba(18, 27, 36, 0.32)",
        inset: false,
      },
    ],
    cardHover: [
      {
        offsetX: 0,
        offsetY: 1,
        blurRadius: 0,
        spreadDistance: 0,
        color: "rgba(255, 255, 255, 0.95)",
        inset: true,
      },
      {
        offsetX: 0,
        offsetY: 2,
        blurRadius: 4,
        spreadDistance: 0,
        color: "rgba(18, 27, 36, 0.06)",
        inset: false,
      },
      {
        offsetX: 0,
        offsetY: 26,
        blurRadius: 46,
        spreadDistance: -24,
        color: "rgba(18, 27, 36, 0.4)",
        inset: false,
      },
    ],
    header: [
      {
        offsetX: 0,
        offsetY: 1,
        blurRadius: 0,
        spreadDistance: 0,
        color: "rgba(255, 255, 255, 0.95)",
        inset: true,
      },
      {
        offsetX: 0,
        offsetY: 8,
        blurRadius: 26,
        spreadDistance: -18,
        color: "rgba(18, 27, 36, 0.35)",
        inset: false,
      },
    ],
    photo: [
      {
        offsetX: 0,
        offsetY: 1,
        blurRadius: 2,
        spreadDistance: 0,
        color: "rgba(18, 27, 36, 0.08)",
        inset: false,
      },
      {
        offsetX: 0,
        offsetY: 14,
        blurRadius: 28,
        spreadDistance: -20,
        color: "rgba(18, 27, 36, 0.45)",
        inset: false,
      },
    ],
  },
} as const;

export const kobaltScale = {
  blur: {
    standard: {
      radius: 24,
      saturatePercent: 185,
    },
    strong: {
      radius: 30,
      saturatePercent: 210,
    },
  },
  fallback: {
    contrast: {
      imageStageText: "#1e2731",
    },
  },
  focus: {
    offset: 3,
    offsetInset: 1,
    width: 3,
  },
  gradient: {
    buttonGloss: {
      angleCssDeg: 180,
      colors: ["rgba(255, 255, 255, 0.16)", "rgba(255, 255, 255, 0)"],
      locations: [0, 0.52],
    },
  },
  motion: {
    duration: {
      base: 220,
      fast: 180,
      lift: 600,
      rise: 550,
      slow: 320,
      stagger: 40,
    },
    easing: {
      basic: [0.25, 0.1, 0.25, 1],
      standard: [0.2, 0.8, 0.2, 1],
    },
  },
  radius: {
    lg: 22,
    md: 16,
    photo: 14,
    pill: 999,
    sm: 12,
  },
  size: {
    control: 48,
    touchTarget: 44,
  },
  space: {
    4: 4,
    8: 8,
    12: 12,
    16: 16,
    20: 20,
    24: 24,
    28: 28,
    40: 40,
    48: 48,
    72: 72,
    80: 80,
  },
  typography: {
    fontFamilyCssStack: {
      display: "\"Segoe UI Variable Display\",\"SF Pro Display\",\"Segoe UI\",system-ui,-apple-system,BlinkMacSystemFont,Roboto,Arial,sans-serif",
      sans: "\"Segoe UI Variable Text\",\"Segoe UI\",system-ui,-apple-system,BlinkMacSystemFont,Roboto,\"Helvetica Neue\",Arial,sans-serif",
    },
    fontSize: {
      base: 16,
      lg: 19,
      md: 15,
      mdPlus: 17,
      sm: 13,
      smPlus: 14,
      xl: 20,
      xl2: 21,
      xl3: 23,
      xl4: 24,
      xs: 12,
      xsPlus: 12.5,
    },
    fontWeight: {
      black: 800,
      bold: 700,
      button: 650,
      display: 780,
      label: 620,
      medium: 500,
      nav: 560,
      numeric: 750,
      regular: 400,
      semibold: 600,
      stock: 640,
    },
    letterSpacingEm: {
      button: 0.005,
      display: -0.03,
      eyebrow: 0.12,
      heading: -0.015,
      label: 0.02,
      normal: 0,
      price: -0.02,
      sub: 0.04,
      title: -0.01,
    },
    lineHeightRatio: {
      body: 1.6,
      button: 1.2,
      caption: 1.3,
      detail: 1.25,
      display: 1.04,
      heading: 1.12,
      lead: 1.55,
      results: 1.4,
      snug: 1.15,
      title: 1.35,
    },
  },
} as const;

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
