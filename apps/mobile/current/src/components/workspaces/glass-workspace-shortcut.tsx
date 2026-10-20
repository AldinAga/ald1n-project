/**
 * LG-04 Wave4: navigation link with one bounded 48dp glass ICON.
 * The row, title and description remain opaque and are a single tap target.
 * Business state and permissions are owned exclusively by the screen caller.
 */
import { useCallback, useEffect, useRef, useState } from 'react';
import { LiquidGlassAndroidView } from 'react-native-android-liquid-glass';
import {
  AccessibilityInfo,
  Animated,
  AppState,
  Platform,
  Pressable,
  StyleSheet,
  Text,
  View,
} from 'react-native';

import { resolveHeaderGlassPolicy } from '@/components/layout/glass-header-policy';
import { Glyph, type GlyphName } from '@/components/ui/glyph';
import { radii, spacing, typography, type AppColors } from '@/constants/theme';
import { useAppTheme } from '@/theme/app-theme';

type Props = {
  title: string;
  copy?: string;
  glyph: GlyphName;
  onPress: () => void;
  variant?: 'primary' | 'soft';
};

export function GlassWorkspaceShortcut({ title, copy, glyph, onPress, variant = 'primary' }: Props) {
  const { colors: theme, isDark } = useAppTheme();
  // Unknown OS accessibility settings must never result in glass or motion.
  const [reduceTransparency, setReduceTransparency] = useState(true);
  const [highTextContrast, setHighTextContrast] = useState(true);
  const [reduceMotion, setReduceMotion] = useState(true);
  const [pipelineFailed, setPipelineFailed] = useState(false);
  const [foreground, setForeground] = useState(AppState.currentState === 'active');
  const scale = useRef(new Animated.Value(1)).current;
  const styles = createStyles(theme);

  useEffect(() => {
    let mounted = true;
    const checkPreferences = () => {
      if (typeof AccessibilityInfo.isReduceTransparencyEnabled === 'function') {
        void AccessibilityInfo.isReduceTransparencyEnabled()
          .then((enabled) => { if (mounted) setReduceTransparency(Boolean(enabled)); })
          .catch(() => { if (mounted) setReduceTransparency(true); });
      }
      if (Platform.OS === 'android' && typeof AccessibilityInfo.isHighTextContrastEnabled === 'function') {
        void AccessibilityInfo.isHighTextContrastEnabled()
          .then((enabled) => { if (mounted) setHighTextContrast(Boolean(enabled)); })
          .catch(() => { if (mounted) setHighTextContrast(true); });
      }
      if (typeof AccessibilityInfo.isReduceMotionEnabled === 'function') {
        void AccessibilityInfo.isReduceMotionEnabled()
          .then((enabled) => { if (mounted) setReduceMotion(Boolean(enabled)); })
          .catch(() => { if (mounted) setReduceMotion(true); });
      }
    };
    checkPreferences();
    const transparency = AccessibilityInfo.addEventListener('reduceTransparencyChanged', (enabled) => {
      if (mounted) setReduceTransparency(Boolean(enabled));
    });
    const motion = AccessibilityInfo.addEventListener('reduceMotionChanged', (enabled) => {
      if (mounted) setReduceMotion(Boolean(enabled));
    });
    const app = AppState.addEventListener('change', (state) => {
      if (!mounted) return;
      setForeground(state === 'active');
      if (state === 'active') checkPreferences();
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
    reduceMotion,
    pipelineFailed,
  });

  useEffect(() => {
    if (!policy.animatePress) {
      scale.stopAnimation();
      scale.setValue(1);
    }
  }, [policy.animatePress, scale]);

  const animatePress = useCallback((pressed: boolean) => {
    if (!policy.animatePress) {
      scale.setValue(1);
      return;
    }
    Animated.spring(scale, {
      toValue: pressed ? 0.975 : 1,
      stiffness: 400,
      damping: 28,
      mass: 0.8,
      useNativeDriver: true,
    }).start();
  }, [policy.animatePress, scale]);

  const onError = useCallback((event: { nativeEvent: { fatal: boolean; code: string } }) => {
    if (event.nativeEvent.fatal || event.nativeEvent.code === 'PIPELINE_DEGRADED') setPipelineFailed(true);
  }, []);
  const onPipelineReady = useCallback((event: { nativeEvent: { supportsLensPipeline: boolean } }) => {
    if (!event.nativeEvent.supportsLensPipeline) setPipelineFailed(true);
  }, []);

  // Labels are intentionally declared outside the native lens.
  const labels = (
    <View style={styles.copy}>
      <Text style={styles.title}>{title}</Text>
      {copy ? <Text style={styles.description}>{copy}</Text> : null}
    </View>
  );
  const icon = (
    <View collapsable={false} style={styles.iconHost}>
      {policy.material === 'solid' ? (
        <View style={styles.solidIcon}>
          <Glyph name={glyph} size={22} color={theme.primary} />
        </View>
      ) : (
        <LiquidGlassAndroidView
          style={styles.nativeIcon}
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
          <View style={styles.iconCenter}>
            <Glyph name={glyph} size={22} color={theme.primary} />
          </View>
        </LiquidGlassAndroidView>
      )}
    </View>
  );
  return (
    <Animated.View style={{ transform: [{ scale }] }}>
      <Pressable
        accessibilityRole="button"
        accessibilityLabel={copy ? title + '. ' + copy : title}
        onPress={onPress}
        onPressIn={() => animatePress(true)}
        onPressOut={() => animatePress(false)}
        style={({ pressed }) => [styles.row, variant === 'soft' ? styles.softRow : null, pressed && !policy.animatePress ? styles.pressed : null]}
      >
        {icon}
        {labels}
        <Glyph name="arrow" size={21} color={theme.muted} />
      </Pressable>
    </Animated.View>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    row: {
      minHeight: 72,
      backgroundColor: theme.surface,
      borderRadius: radii.lg,
      flexDirection: 'row',
      alignItems: 'center',
      paddingHorizontal: spacing.md,
      paddingVertical: spacing.sm,
      gap: spacing.md,
      borderWidth: StyleSheet.hairlineWidth,
      borderColor: theme.line,
    },
    softRow: {
      minHeight: 64,
      backgroundColor: theme.surface,
      borderRadius: radii.md,
      borderTopWidth: 0,
      borderLeftWidth: 0,
      borderRightWidth: 0,
      borderBottomWidth: StyleSheet.hairlineWidth,
      paddingHorizontal: spacing.sm,
    },
    pressed: { opacity: 0.88 },
    iconHost: { width: 48, height: 48 },
    nativeIcon: { width: 48, height: 48, borderRadius: radii.lg },
    solidIcon: {
      width: 48,
      height: 48,
      borderRadius: radii.lg,
      backgroundColor: theme.surfaceContainerHigh,
      borderWidth: StyleSheet.hairlineWidth,
      borderColor: theme.line,
      alignItems: 'center',
      justifyContent: 'center',
    },
    iconCenter: { width: 48, height: 48, alignItems: 'center', justifyContent: 'center' },
    copy: { flex: 1, minWidth: 0, gap: 2 },
    title: { ...typography.body, fontWeight: '700', color: theme.ink, flexShrink: 1 },
    description: { ...typography.small, color: theme.muted, flexShrink: 1 },
  });
}
