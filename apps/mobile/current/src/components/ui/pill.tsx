import { StyleSheet, Text, View } from 'react-native';
import { colors, radii, spacing, typography } from '@/constants/theme';

type Tone = 'neutral' | 'primary' | 'success' | 'warning' | 'danger' | 'info';

export function Pill({ children, tone = 'neutral' }: { children: React.ReactNode; tone?: Tone }) {
  const palette = palettes[tone];
  return <View style={[styles.base, palette.container]}><Text style={[styles.text, palette.text]}>{children}</Text></View>;
}

const styles = StyleSheet.create({
  base: {
    alignSelf: 'flex-start',
    paddingHorizontal: spacing.md,
    paddingVertical: 6,
    borderRadius: radii.pill
  },
  text: { ...typography.small, fontWeight: '700' }
});

const palettes = {
  neutral: StyleSheet.create({ container: { backgroundColor: colors.surfaceMuted }, text: { color: colors.muted } }),
  primary: StyleSheet.create({ container: { backgroundColor: colors.primarySoft }, text: { color: colors.primaryDark } }),
  success: StyleSheet.create({ container: { backgroundColor: colors.successSoft }, text: { color: colors.success } }),
  warning: StyleSheet.create({ container: { backgroundColor: colors.warningSoft }, text: { color: colors.warning } }),
  danger: StyleSheet.create({ container: { backgroundColor: colors.dangerSoft }, text: { color: colors.danger } }),
  info: StyleSheet.create({ container: { backgroundColor: colors.infoSoft }, text: { color: colors.info } })
};
