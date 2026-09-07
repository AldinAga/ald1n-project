import { Pressable, StyleSheet, Text, View } from 'react-native';
import Animated, {
  Easing,
  useAnimatedStyle,
  useReducedMotion,
  useSharedValue,
  withTiming,
} from 'react-native-reanimated';

import { Glyph, type GlyphName } from '@/components/ui/glyph';
import { motion, radii, spacing, typography, type AppColors } from '@/constants/theme';
import { useAppTheme, useThemedStyles } from '@/theme/app-theme';

const PRESS_DURATION = Number.parseInt(motion.press, 10);
const RELEASE_DURATION = Number.parseInt(motion.fast, 10);
const OPERATOR_EASE = Easing.bezier(0.23, 1, 0.32, 1);

export function OperatorRow({
  title,
  copy,
  glyph,
  onPress,
  divider = false,
}: {
  title: string;
  copy?: string;
  glyph: GlyphName;
  onPress: () => void;
  divider?: boolean;
}) {
  const { colors: themeColors } = useAppTheme();
  const styles = useThemedStyles(createStyles);
  const reduceMotion = useReducedMotion();
  const progress = useSharedValue(0);

  const animatedStyle = useAnimatedStyle(() => ({
    opacity: reduceMotion ? 1 : 1 - (progress.value * 0.06),
    transform: [{ scale: reduceMotion ? 1 : 1 - (progress.value * 0.012) }],
  }), [reduceMotion]);

  const setPressed = (pressed: boolean) => {
    if (reduceMotion) {
      progress.value = pressed ? 1 : 0;
      return;
    }
    progress.value = withTiming(pressed ? 1 : 0, {
      duration: pressed ? PRESS_DURATION : RELEASE_DURATION,
      easing: OPERATOR_EASE,
    });
  };

  return (
    <Animated.View style={animatedStyle}>
      <Pressable
        accessibilityRole="button"
        accessibilityLabel={copy ? title + '. ' + copy : title}
        onPress={onPress}
        onPressIn={() => setPressed(true)}
        onPressOut={() => setPressed(false)}
      >
        <View style={[styles.row, divider && styles.divider]}>
          <View style={styles.icon}>
            <Glyph name={glyph} size={22} color={themeColors.primary} />
          </View>
          <View style={styles.copy}>
            <Text style={styles.title}>{title}</Text>
            {copy ? <Text style={styles.subtitle}>{copy}</Text> : null}
          </View>
          <Glyph name="arrow" size={22} color={themeColors.muted} />
        </View>
      </Pressable>
    </Animated.View>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    row: {
      minHeight: 72,
      flexDirection: 'row',
      alignItems: 'center',
      gap: spacing.md,
      paddingHorizontal: spacing.lg,
      paddingVertical: spacing.md,
      backgroundColor: theme.surface,
    },
    divider: {
      borderBottomWidth: StyleSheet.hairlineWidth,
      borderBottomColor: theme.line,
    },
    icon: {
      width: 42,
      height: 42,
      borderRadius: radii.md,
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: theme.primarySoft,
    },
    copy: { flex: 1, minWidth: 0 },
    title: {
      ...typography.body,
      fontWeight: '700',
      color: theme.ink,
    },
    subtitle: {
      ...typography.small,
      color: theme.muted,
      marginTop: 2,
    },
  });
}
