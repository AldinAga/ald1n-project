/**
 * LG-04 Wave 1: fast, legible, 48dp header affordances.
 *
 * Native lens is intentionally bounded to a 48dp action, with NO continuous
 * background capture, NO animated blur parameters and solid fail-closed mode.
 * Must be a DIRECT child of a native non-collapsible header actions View.
 */
import { useCallback, useEffect, useRef, useState, type ReactNode } from 'react';
import { LiquidGlassAndroidView } from 'react-native-android-liquid-glass';
import {
  AccessibilityInfo,
  Animated,
  AppState,
  Platform,
  Pressable,
  StyleSheet,
  View,
} from 'react-native';

import { radii, type AppColors } from '@/constants/theme';
import { useAppTheme } from '@/theme/app-theme';
import { resolveHeaderGlassPolicy } from './glass-header-policy';

type GlassHeaderActionProps = {
  accessibilityLabel: string;
  onPress: () => void;
  selected?: boolean;
  disabled?: boolean;
  children: ReactNode;
};

export function GlassHeaderAction({
  accessibilityLabel,
  onPress,
  selected = false,
  disabled = false,
  children,
}: GlassHeaderActionProps) {
  const { colors: theme, isDark } = useAppTheme();
  const [reduceTransparency, setReduceTransparency] = useState(true);
  const [highTextContrast, setHighTextContrast] = useState(true);
  const [reduceMotion, setReduceMotion] = useState(true);
  const [pipelineFailed, setPipelineFailed] = useState(false);
  const [foreground, setForeground] = useState(AppState.currentState === 'active');
  const scale = useRef(new Animated.Value(1)).current;
  const styles = createStyles(theme);

  useEffect(() => {
    let mounted = true;
    if (typeof AccessibilityInfo.isReduceTransparencyEnabled === 'function') {
      void AccessibilityInfo.isReduceTransparencyEnabled()
        .then((enabled) => { if (mounted) setReduceTransparency(Boolean(enabled)); })
        .catch(() => { /* Unknown: keep opaque. */ });
    }
    const transparency = AccessibilityInfo.addEventListener('reduceTransparencyChanged', (enabled) => {
      if (mounted) setReduceTransparency(Boolean(enabled));
    });
    if (Platform.OS === 'android' && typeof AccessibilityInfo.isHighTextContrastEnabled === 'function') {
      void AccessibilityInfo.isHighTextContrastEnabled()
        .then((enabled) => { if (mounted) setHighTextContrast(Boolean(enabled)); })
        .catch(() => { /* Unknown: keep opaque. */ });
    }
    if (typeof AccessibilityInfo.isReduceMotionEnabled === 'function') {
      void AccessibilityInfo.isReduceMotionEnabled()
        .then((enabled) => { if (mounted) setReduceMotion(Boolean(enabled)); })
        .catch(() => { /* Unknown: no animation. */ });
    }
    const motion = AccessibilityInfo.addEventListener('reduceMotionChanged', (enabled) => {
      if (mounted) setReduceMotion(Boolean(enabled));
    });
    const app = AppState.addEventListener('change', (state) => {
      if (!mounted) return;
      setForeground(state === 'active');
      // Contrast may change while Settings is foregrounded. Re-evaluate on
      // return instead of leaving a now-unreadable transparent glass control.
      if (state === 'active' && Platform.OS === 'android'
        && typeof AccessibilityInfo.isHighTextContrastEnabled === 'function') {
        void AccessibilityInfo.isHighTextContrastEnabled()
          .then((enabled) => { if (mounted) setHighTextContrast(Boolean(enabled)); })
          .catch(() => { if (mounted) setHighTextContrast(true); });
      }
    });
    return () => {
      mounted = false;
      transparency.remove();
      motion.remove();
      app.remove();
    };
  }, []);

  const policy = resolveHeaderGlassPolicy({
    platform: Platform.OS,
    androidApiLevel: Platform.OS === 'android' ? Number(Platform.Version) : undefined,
    reduceTransparency,
    highTextContrast,
    pipelineFailed,
    reduceMotion,
  });

  useEffect(() => {
    if (!policy.animatePress || disabled) {
      scale.stopAnimation();
      scale.setValue(1);
    }
  }, [policy.animatePress, disabled, scale]);

  const animatePress = useCallback((pressed: boolean) => {
    if (!policy.animatePress || disabled) {
      scale.setValue(1);
      return;
    }
    Animated.spring(scale, {
      toValue: pressed ? 0.94 : 1,
      stiffness: 400,
      damping: 26,
      mass: 0.7,
      useNativeDriver: true,
    }).start();
  }, [disabled, policy.animatePress, scale]);

  const onError = useCallback((event: { nativeEvent: { fatal: boolean; code: string } }) => {
    if (event.nativeEvent.fatal || event.nativeEvent.code === 'PIPELINE_DEGRADED') {
      setPipelineFailed(true);
    }
  }, []);
  const onPipelineReady = useCallback((event: { nativeEvent: { supportsLensPipeline: boolean } }) => {
    if (!event.nativeEvent.supportsLensPipeline) setPipelineFailed(true);
  }, []);

  const control = (
    <Animated.View style={[styles.pressBody, { transform: [{ scale }] }]}>
      <Pressable
        accessibilityRole="button"
        accessibilityLabel={accessibilityLabel}
        accessibilityState={{ selected, disabled }}
        disabled={disabled}
        onPress={onPress}
        onPressIn={() => animatePress(true)}
        onPressOut={() => animatePress(false)}
        style={({ pressed }) => [
          styles.action,
          selected ? styles.selected : null,
          disabled ? styles.disabled : null,
          pressed && !policy.animatePress ? styles.pressed : null,
        ]}
      >
        {children}
      </Pressable>
    </Animated.View>
  );

  if (policy.material === 'solid') {
    return (
      <View style={[styles.solidSurface, selected ? styles.selectedSurface : null]}>
        {control}
      </View>
    );
  }

  return (
    <LiquidGlassAndroidView
      style={styles.nativeSurface}
      cornerRadius={radii.lg}
      material="regular"
      overLight={!isDark}
      refractionHeight={23}
      bevelWidth={8}
      dispersionStrength={0.035}
      enableBackdropBlur
      enableDynamicBackground={false}
      globalDownsampleFactor={0.5}
      highQualityBlur={false}
      enableChromaticAberration={false}
      enableChromaticDispersion={false}
      enableSensorHighlight={false}
      enableAdaptiveTint={false}
      enableEdgeHighlight
      enableShadow={false}
      paused={!foreground}
      accessibilityMode="auto"
      collectFrameStats={false}
      frameStatsInterval={0}
      onError={onError}
      onPipelineReady={onPipelineReady}
    >
      {control}
    </LiquidGlassAndroidView>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    nativeSurface: {
      width: 48,
      height: 48,
      borderRadius: radii.lg,
      overflow: 'visible',
    },
    solidSurface: {
      width: 48,
      height: 48,
      borderRadius: radii.lg,
      backgroundColor: theme.surfaceContainerHigh,
      borderWidth: StyleSheet.hairlineWidth,
      borderColor: theme.outline,
    },
    selectedSurface: {
      backgroundColor: theme.primaryContainer,
      borderColor: theme.primary,
    },
    pressBody: {
      width: 48,
      height: 48,
      borderRadius: radii.lg,
    },
    action: {
      width: 48,
      height: 48,
      minHeight: 48,
      alignItems: 'center',
      justifyContent: 'center',
      borderRadius: radii.lg,
    },
    selected: { backgroundColor: theme.primaryContainer },
    disabled: { opacity: 0.45 },
    pressed: { opacity: 0.86 },
  });
}
