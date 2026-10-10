import { useMemo } from 'react';
import { Pressable, StyleSheet, Text, View, useWindowDimensions } from 'react-native';

import { Glyph, type GlyphName } from '@/components/ui/glyph';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { useAppTheme } from '@/theme/app-theme';

export type WorkspaceOption<T extends string> = {
  id: T;
  label: string;
  glyph: GlyphName;
  disabled?: boolean;
  a11yLabel?: string;
};

type Props<T extends string> = {
  options: readonly WorkspaceOption<T>[];
  selected: T;
  onSelect: (id: T) => void;
};

// Screen-owned state and callbacks. Selecting a tile never mutates the server.
export function WorkspaceGrid<T extends string>({ options, selected, onSelect }: Props<T>) {
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const { width, fontScale } = useWindowDimensions();
  const columns = width < 375 || fontScale > 1.25 ? 2 : 3;

  return (
    <View style={styles.grid}>
      {options.map((option) => {
        const active = option.id === selected;
        return (
          <Pressable
            key={option.id}
            accessibilityRole="button"
            accessibilityLabel={option.a11yLabel ?? option.label}
            accessibilityState={{ selected: active, disabled: Boolean(option.disabled) }}
            disabled={option.disabled}
            onPress={() => onSelect(option.id)}
            style={[
              styles.tile,
              { width: columns === 3 ? '31%' : '48%' },
              active && styles.active,
              option.disabled && styles.disabled,
            ]}
          >
            <Glyph name={option.glyph} size={21} color={active ? theme.primary : theme.muted} />
            <Text style={[styles.label, active && styles.activeLabel]}>{option.label}</Text>
          </Pressable>
        );
      })}
    </View>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    grid: {
      flexDirection: 'row',
      flexWrap: 'wrap',
      justifyContent: 'space-between',
      gap: spacing.sm,
    },
    tile: {
      minHeight: 76,
      minWidth: 0,
      alignItems: 'center',
      justifyContent: 'center',
      paddingVertical: spacing.md,
      paddingHorizontal: spacing.sm,
      gap: 7,
      borderRadius: 14,
      borderWidth: 1,
      borderColor: theme.line,
      backgroundColor: theme.surfaceContainer,
    },
    active: {
      borderWidth: 2,
      borderColor: theme.primary,
      backgroundColor: theme.primarySoft,
    },
    disabled: { opacity: 0.45 },
    label: {
      ...typography.small,
      color: theme.ink,
      fontWeight: '700',
      textAlign: 'center',
      flexShrink: 1,
    },
    activeLabel: { color: theme.primary, fontWeight: '900' },
  });
}
