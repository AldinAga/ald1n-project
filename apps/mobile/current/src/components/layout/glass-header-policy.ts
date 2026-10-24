/**
 * LG-04 Wave 1: accessibility-first header appearance and motion policy.
 *
 * A native glass lens is decoration, never the only means of navigating.
 * Older Android/iOS/web and high-contrast users retain opaque controls.
 */
export type HeaderGlassContext = {
  platform: string;
  androidApiLevel?: number;
  reduceTransparency?: boolean;
  highTextContrast?: boolean;
  pipelineFailed?: boolean;
  reduceMotion?: boolean;
};

export type HeaderGlassPolicy = {
  material: 'native' | 'solid';
  animatePress: boolean;
  minTouchSize: 48;
};

export function resolveHeaderGlassPolicy({
  platform,
  androidApiLevel,
  reduceTransparency = true, // safe until settings resolve
  highTextContrast = false,
  pipelineFailed = false,
  reduceMotion = true,
}: HeaderGlassContext): HeaderGlassPolicy {
  const native =
    platform === 'android'
    && Number.isInteger(androidApiLevel)
    && (androidApiLevel ?? 0) >= 33
    && !reduceTransparency
    && !highTextContrast
    && !pipelineFailed;

  return {
    material: native ? 'native' : 'solid',
    animatePress: !reduceMotion,
    minTouchSize: 48,
  };
}
