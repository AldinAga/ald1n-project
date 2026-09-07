// MOBILE_BUILD16_SHARED_STATES_FINAL_BATCH132
import { useEffect, useMemo } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import Animated, {
  Easing,
  cancelAnimation,
  useAnimatedStyle,
  useReducedMotion,
  useSharedValue,
  withRepeat,
  withTiming,
} from 'react-native-reanimated';

import { BrandMark } from '@/components/ui/brand-mark';
import { Button } from '@/components/ui/button';
import { Glyph } from '@/components/ui/glyph';
import { radii, spacing, typography, type AppColors } from '@/constants/theme';
import { ApiError } from '@/lib/api/client';
import { useAppTheme } from '@/theme/app-theme';

function useStateTheme() {
  const { colors: themeColors } = useAppTheme();
  const styles = useMemo(() => createStyles(themeColors), [themeColors]);
  return { themeColors, styles };
}

export function LoadingState({ label = 'Učitavanje sadržaja' }: { label?: string }) {
  const { styles } = useStateTheme();
  const reduceMotion = useReducedMotion();
  const progress = useSharedValue(0);

  useEffect(() => {
    if (reduceMotion) {
      progress.value = 0.6;
      return () => cancelAnimation(progress);
    }

    progress.value = withRepeat(
      withTiming(1, { duration: 880, easing: Easing.inOut(Easing.ease) }),
      -1,
      true,
    );

    return () => cancelAnimation(progress);
  }, [progress, reduceMotion]);

  const markStyle = useAnimatedStyle(() => ({
    opacity: reduceMotion ? 1 : 0.72 + progress.value * 0.28,
    transform: [{ scale: reduceMotion ? 1 : 0.92 + progress.value * 0.08 }],
  }), [reduceMotion]);

  const skeletonStyle = useAnimatedStyle(() => ({
    opacity: reduceMotion ? 0.56 : 0.38 + progress.value * 0.42,
  }), [reduceMotion]);

  return (
    <View
      style={styles.loadingCenter}
      accessibilityRole="progressbar"
      accessibilityLabel={label}
      accessibilityLiveRegion="polite"
    >
      <Animated.View style={markStyle}>
        <BrandMark size={50} />
      </Animated.View>
      <View style={styles.skeletonStack}>
        <Animated.View style={[styles.skeletonLine, styles.skeletonLong, skeletonStyle]} />
        <Animated.View style={[styles.skeletonLine, styles.skeletonMedium, skeletonStyle]} />
        <Animated.View style={[styles.skeletonLine, styles.skeletonShort, skeletonStyle]} />
      </View>
    </View>
  );
}

export function EmptyState({ title, message }: { title: string; message: string }) {
  const { themeColors, styles } = useStateTheme();
  return (
    <View style={styles.center}>
      <View style={styles.icon}>
        <Glyph name="box" size={27} color={themeColors.primary} />
      </View>
      <Text style={styles.title}>{title}</Text>
      <Text style={styles.muted}>{message}</Text>
    </View>
  );
}

export function UnavailableState({
  title = 'Funkcija nije dostupna',
  message = 'Tvoj nalog nema pristup ovoj mobilnoj funkciji.',
}: { title?: string; message?: string }) {
  const { themeColors, styles } = useStateTheme();
  return (
    <View style={styles.center}>
      <View style={styles.icon}>
        <Glyph name="lock" size={27} color={themeColors.primary} />
      </View>
      <Text style={styles.title}>{title}</Text>
      <Text style={styles.muted}>{message}</Text>
    </View>
  );
}

export function ErrorState({ error, onRetry }: { error: unknown; onRetry?: () => void }) {
  const { themeColors, styles } = useStateTheme();
  const apiError = error instanceof ApiError ? error : null;
  const message = apiError?.firstFieldError()
    ?? (error instanceof Error ? error.message : 'Došlo je do neočekivane greške.');

  return (
    <View style={styles.center}>
      <View style={[styles.icon, styles.errorIcon]}>
        <Glyph name="close" size={27} color={themeColors.danger} />
      </View>
      <Text style={styles.title}>Nešto nije u redu</Text>
      <Text style={styles.muted}>{message}</Text>
      {apiError?.requestId ? (
        <Text selectable style={styles.request}>ID zahteva: {apiError.requestId}</Text>
      ) : null}
      {onRetry ? (
        <Button variant="secondary" onPress={onRetry}>Pokušaj ponovo</Button>
      ) : null}
    </View>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    loadingCenter: {
      width: '100%',
      maxWidth: 520,
      minHeight: 220,
      alignSelf: 'center',
      paddingHorizontal: spacing.xl,
      paddingVertical: spacing.xxxl,
      alignItems: 'center',
      justifyContent: 'center',
      gap: spacing.xl,
    },
    skeletonStack: {
      width: '78%',
      maxWidth: 320,
      alignItems: 'center',
      gap: spacing.sm,
    },
    skeletonLine: {
      height: 10,
      borderRadius: radii.md,
      backgroundColor: theme.primarySoft,
    },
    skeletonLong: { width: '100%' },
    skeletonMedium: { width: '76%' },
    skeletonShort: { width: '48%' },
    center: {
      width: '100%',
      maxWidth: 520,
      minHeight: 210,
      alignSelf: 'center',
      paddingHorizontal: spacing.xl,
      paddingVertical: spacing.xxxl,
      alignItems: 'center',
      justifyContent: 'center',
      gap: spacing.md,
      borderWidth: StyleSheet.hairlineWidth,
      borderColor: theme.line,
      borderRadius: radii.xl,
      backgroundColor: theme.surface,
    },
    icon: {
      width: 56,
      height: 56,
      borderRadius: radii.xl,
      backgroundColor: theme.primarySoft,
      alignItems: 'center',
      justifyContent: 'center',
    },
    errorIcon: { backgroundColor: theme.dangerSoft },
    title: { ...typography.h3, color: theme.ink, textAlign: 'center' },
    muted: {
      ...typography.body,
      color: theme.muted,
      textAlign: 'center',
      maxWidth: 380,
    },
    request: {
      ...typography.small,
      color: theme.muted,
      textAlign: 'center',
      fontVariant: ['tabular-nums'],
    },
  });
}
