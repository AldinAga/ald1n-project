/**
 * Ald1n Android LG-03 / Wave 1: Build25-safe material policy.
 *
 * This is an honest translucent visual fallback, NOT native refraction or
 * real backdrop blur. Native AGSL glass needs a compatible future Android
 * binary, real-device QA and a separately gated runtimeVersion.
 *
 * Do not change bottom-tab routes, access control, or the canonical palette.
 */
export type AndroidGlassMaterialMode = 'translucent' | 'solid' | 'legacy';

export type AndroidGlassMaterialContext = {
  platform: string;
  androidApiLevel?: number;
  reduceTransparency?: boolean;
  highContrast?: boolean;
};

export function resolveAndroidGlassMaterial({
  platform,
  androidApiLevel,
  reduceTransparency = false,
  highContrast = false,
}: AndroidGlassMaterialContext): AndroidGlassMaterialMode {
  // Preserve iOS and web shells: native iOS glass has its own release gate.
  if (platform !== 'android') return 'legacy';
  // Accessibility overrides visual decoration, never business functionality.
  if (reduceTransparency || highContrast) return 'solid';
  // Conservative fallback: native Android refraction candidate needs API 33+.
  if (!Number.isInteger(androidApiLevel) || (androidApiLevel ?? 0) < 33) return 'solid';
  return 'translucent';
}

export type AndroidGlassChrome = {
  specular: string;
  topRim: string;
  bottomRim: string;
  activeRim: string;
};

export function androidGlassChrome(isDark: boolean): AndroidGlassChrome {
  return isDark
    ? {
        specular: 'rgba(245,249,252,0.055)',
        topRim: 'rgba(255,255,255,0.24)',
        bottomRim: 'rgba(11,15,19,0.26)',
        activeRim: 'rgba(243,247,250,0.18)',
      }
    : {
        specular: 'rgba(255,255,255,0.23)',
        topRim: 'rgba(255,255,255,0.70)',
        bottomRim: 'rgba(101,111,121,0.16)',
        activeRim: 'rgba(255,255,255,0.46)',
      };
}
