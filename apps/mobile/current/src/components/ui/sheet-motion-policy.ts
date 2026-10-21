/**
 * LG-04 Wave 3: accessible modal transition policy.
 *
 * Native Modal's built-in slide avoids JS-thread animations and preserves
 * business-action ordering. The first open is static until OS preference
 * resolves; Reduce Motion ALWAYS wins.
 */
import { useEffect, useState } from 'react';
import { AccessibilityInfo } from 'react-native';

export type SheetAnimationType = 'none' | 'slide';

export function resolveSheetAnimationType(reduceMotion: boolean | undefined): SheetAnimationType {
  return reduceMotion === false ? 'slide' : 'none';
}

export function useSheetAnimationType(): SheetAnimationType {
  const [reduceMotion, setReduceMotion] = useState(true);

  useEffect(() => {
    let mounted = true;
    if (typeof AccessibilityInfo.isReduceMotionEnabled !== 'function') return;

    void AccessibilityInfo.isReduceMotionEnabled()
      .then((value) => { if (mounted) setReduceMotion(Boolean(value)); })
      .catch(() => { /* Unknown preference: no motion. */ });

    const listener = AccessibilityInfo.addEventListener('reduceMotionChanged', (enabled) => {
      if (mounted) setReduceMotion(Boolean(enabled));
    });

    return () => {
      mounted = false;
      listener.remove();
    };
  }, []);

  return resolveSheetAnimationType(reduceMotion);
}
