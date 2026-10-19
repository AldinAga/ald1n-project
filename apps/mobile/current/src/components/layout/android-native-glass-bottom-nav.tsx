/**
 * Android LG-03 / Wave 2 -- isolated native candidate, NOT Build25 OTA safe.
 *
 * Critical renderer contract: this component returns LiquidGlassAndroidView
 * directly. It must be placed as a sibling of the Stack under the non-
 * collapsable app-shell View. Never insert a surrounding native View: the
 * upstream library snapshots only its own immediate parent's subtree.
 */
import { useCallback, useEffect, useMemo, useState } from 'react';
import { LiquidGlassAndroidView } from 'react-native-android-liquid-glass';
import {
  AccessibilityInfo,
  AppState,
  Keyboard,
  Platform,
  StyleSheet,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { resolveAndroidGlassMaterial } from './android-glass-material-policy';
import { AppBottomNav } from './app-bottom-nav';
import { useAppTheme } from '@/theme/app-theme';

export function AndroidNativeGlassBottomNav() {
  const insets = useSafeAreaInsets();
  const { isDark } = useAppTheme();
  const [keyboardVisible, setKeyboardVisible] = useState(false);
  // Fail CLOSED while determining OS accessibility support.
  const [reduceTransparency, setReduceTransparency] = useState(true);
  const [fatalNativeError, setFatalNativeError] = useState(false);
  const [foreground, setForeground] = useState(AppState.currentState === 'active');

  useEffect(() => {
    let mounted = true;
    if (typeof AccessibilityInfo.isReduceTransparencyEnabled !== 'function') {
      return;
    }
    void AccessibilityInfo.isReduceTransparencyEnabled()
      .then((enabled) => { if (mounted) setReduceTransparency(Boolean(enabled)); })
      .catch(() => { /* Accessibility unknown: remain solid. */ });
    const listener = AccessibilityInfo.addEventListener('reduceTransparencyChanged', (enabled) => {
      if (mounted) setReduceTransparency(Boolean(enabled));
    });
    return () => { mounted = false; listener.remove(); };
  }, []);

  useEffect(() => {
    const show = Keyboard.addListener('keyboardDidShow', () => setKeyboardVisible(true));
    const hide = Keyboard.addListener('keyboardDidHide', () => setKeyboardVisible(false));
    return () => { show.remove(); hide.remove(); };
  }, []);

  useEffect(() => {
    const subscription = AppState.addEventListener('change', (nextState) => {
      setForeground(nextState === 'active');
    });
    return () => subscription.remove();
  }, []);

  const onError = useCallback((event: { nativeEvent: { fatal: boolean; code: string } }) => {
    if (event.nativeEvent.fatal || event.nativeEvent.code === 'PIPELINE_DEGRADED') {
      setFatalNativeError(true);
    }
  }, []);
  const onPipelineReady = useCallback((event: { nativeEvent: { supportsLensPipeline: boolean } }) => {
    if (!event.nativeEvent.supportsLensPipeline) setFatalNativeError(true);
  }, []);

  const material = resolveAndroidGlassMaterial({
    platform: Platform.OS,
    androidApiLevel: Platform.OS === 'android' ? Number(Platform.Version) : undefined,
    reduceTransparency,
  });
  const nativeSupported = material === 'translucent' && !fatalNativeError;
  const frame = useMemo(
    () => ({
      bottom: Math.max(insets.bottom, 4),
      left: Math.max(insets.left, 16),
      right: Math.max(insets.right, 16),
    }),
    [insets.bottom, insets.left, insets.right],
  );

  if (keyboardVisible) return null;
  if (!nativeSupported) return <AppBottomNav variant="bottom" />;

  return (
    <LiquidGlassAndroidView
      style={[styles.nativeGlass, frame]}
      cornerRadius={24}
      material="regular"
      overLight={!isDark}
      refractionHeight={42}
      bevelWidth={12}
      dispersionStrength={0.06}
      enableBackdropBlur
      enableDynamicBackground
      paused={!foreground}
      globalDownsampleFactor={0.5}
      highQualityBlur={false}
      enableChromaticAberration={false}
      enableChromaticDispersion={false}
      enableSensorHighlight={false}
      enableAdaptiveTint={false}
      enableEdgeHighlight
      enableShadow={false}
      accessibilityMode="auto"
      collectFrameStats={false}
      frameStatsInterval={0}
      onError={onError}
      onPipelineReady={onPipelineReady}
    >
      <AppBottomNav variant="native-content" />
    </LiquidGlassAndroidView>
  );
}

const styles = StyleSheet.create({
  // Native root MUST be a direct child of the collapsable={false} app shell.
  // On API33+ this captures the live Stack sibling backdrop behind the bar.
  nativeGlass: {
    position: 'absolute',
    height: 70,
    borderRadius: 24,
    zIndex: 10,
    overflow: 'visible',
  },
});
