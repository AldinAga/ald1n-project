import type { PropsWithChildren } from 'react';
import { StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';
import { colors, radii, shadow, spacing } from '@/constants/theme';

export function Card({ children, style, muted = false }: PropsWithChildren<{ style?: StyleProp<ViewStyle>; muted?: boolean }>) {
  return <View style={[styles.card, muted && styles.muted, style]}>{children}</View>;
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: colors.surface,
    borderWidth: 0,
    borderRadius: radii.xl,
    padding: spacing.lg,
    ...shadow
  },
  muted: {
    backgroundColor: colors.surfaceContainer,
    shadowOpacity: 0,
    elevation: 0
  }
});
