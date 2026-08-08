import { Platform } from 'react-native';

// Material 3 Expressive-inspired semantic palette. Existing brand purple remains the product identity.
export const colors = {
  background: '#F8F6FC',
  surface: '#FFFFFF',
  surfaceMuted: '#F0ECF7',
  surfaceContainer: '#F1ECF8',
  surfaceContainerHigh: '#EAE4F3',
  ink: '#1C1724',
  muted: '#746C7D',
  line: '#E0D9E9',
  outline: '#7B7284',
  primary: '#6D45E5',
  primaryDark: '#4B2BAA',
  primarySoft: '#E9E1FF',
  primaryContainer: '#E9E1FF',
  onPrimaryContainer: '#251052',
  secondaryContainer: '#EEE5F4',
  accent: '#FFAA00',
  accentSoft: '#FFF0D1',
  success: '#168C63',
  successSoft: '#E0F5EC',
  warning: '#B87312',
  warningSoft: '#FFF0D8',
  danger: '#C43D52',
  dangerSoft: '#FCE6EA',
  info: '#2872C8',
  infoSoft: '#E4F0FF',
  white: '#FFFFFF',
  black: '#000000',
  hero: '#02141E',
  heroMuted: '#C4CFD4'
} as const;

export const spacing = { xs: 4, sm: 8, md: 12, lg: 16, xl: 20, xxl: 24, xxxl: 32 } as const;

export const radii = {
  sm: 12,
  md: 16,
  lg: 20,
  xl: 28,
  xxl: 34,
  pill: 999
} as const;

export const shadow = Platform.select({
  ios: {
    shadowColor: colors.black,
    shadowOpacity: 0.07,
    shadowRadius: 22,
    shadowOffset: { width: 0, height: 10 }
  },
  android: { elevation: 2 },
  default: {}
});

export const typography = {
  hero: { fontSize: 36, lineHeight: 41, fontWeight: '900' as const, letterSpacing: -0.6 },
  h1: { fontSize: 30, lineHeight: 36, fontWeight: '900' as const, letterSpacing: -0.4 },
  h2: { fontSize: 22, lineHeight: 28, fontWeight: '800' as const, letterSpacing: -0.2 },
  h3: { fontSize: 18, lineHeight: 23, fontWeight: '800' as const },
  body: { fontSize: 15, lineHeight: 22, fontWeight: '400' as const },
  label: { fontSize: 13, lineHeight: 18, fontWeight: '800' as const },
  small: { fontSize: 12, lineHeight: 17, fontWeight: '600' as const }
};
