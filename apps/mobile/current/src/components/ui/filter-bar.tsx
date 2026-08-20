import type { PropsWithChildren } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { useAppTheme } from '@/theme/app-theme';

type FilterBarProps = PropsWithChildren<{ activeCount?: number; clearLabel?: string; onClear?: () => void }>;
type FilterChipProps = { label: string; active?: boolean; disabled?: boolean; onPress: () => void };

export function FilterBar({ activeCount = 0, children, clearLabel = 'Očisti', onClear }: FilterBarProps) {
  const { colors: theme } = useAppTheme(); const styles = createStyles(theme);
  return <View style={styles.wrap}><View style={styles.chips}>{children}</View>{onClear && activeCount > 0 ? <Pressable accessibilityRole="button" onPress={onClear} style={({ pressed }: { pressed: boolean }) => [styles.clear, pressed ? styles.pressed : null]}><Text style={styles.clearText}>{clearLabel} ({activeCount})</Text></Pressable> : null}</View>;
}
export function FilterChip({ label, active = false, disabled = false, onPress }: FilterChipProps) {
  const { colors: theme } = useAppTheme(); const styles = createStyles(theme);
  return <Pressable accessibilityRole="button" accessibilityState={{ selected: active, disabled }} disabled={disabled} onPress={onPress} style={({ pressed }: { pressed: boolean }) => [styles.chip, active ? styles.active : null, disabled ? styles.disabled : null, pressed ? styles.pressed : null]}><Text style={[styles.chipText, active ? styles.activeText : null]}>{label}</Text></Pressable>;
}
function createStyles(theme: AppColors) { return StyleSheet.create({ wrap:{gap:spacing.sm}, chips:{flexDirection:'row',flexWrap:'wrap',gap:spacing.sm}, chip:{minHeight:38,justifyContent:'center',paddingHorizontal:spacing.md,paddingVertical:spacing.sm,borderRadius:999,borderWidth:1,borderColor:theme.line,backgroundColor:theme.surface}, active:{borderColor:theme.primary,backgroundColor:theme.primarySoft}, chipText:{...typography.small,color:theme.ink,fontWeight:'700'}, activeText:{color:theme.primary}, clear:{alignSelf:'flex-start',minHeight:36,justifyContent:'center'}, clearText:{...typography.small,color:theme.primary,fontWeight:'700'}, disabled:{opacity:.45}, pressed:{opacity:.72} }); }
