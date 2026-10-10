import { useMemo, useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { Card } from '@/components/ui/card';
import { Glyph, type GlyphName } from '@/components/ui/glyph';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { useAppTheme } from '@/theme/app-theme';

export type QuickAction = {
  id: string;
  label: string;
  glyph: GlyphName;
  disabled?: boolean;
  pending?: boolean;
  kind?: 'primary' | 'secondary' | 'danger';
  onPress: () => void;
};

type Props = {
  actions: readonly QuickAction[];
  title?: string;
  maxVisible?: number;
};

// Presentation only; the caller owns capabilities, confirmation and domain writes.
export function QuickActionHub({ actions, title = 'Brze akcije', maxVisible = 4 }: Props) {
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const [expanded, setExpanded] = useState(false);
  const limit = Math.max(1, maxVisible);
  const displayed = expanded ? actions : actions.slice(0, limit);
  const hiddenCount = Math.max(0, actions.length - limit);

  return (
    <Card style={styles.card}>
      <View style={styles.heading}>
        <Glyph name="admin" size={20} color={theme.primary} />
        <Text style={styles.title}>{title}</Text>
      </View>
      {actions.length === 0 ? (
        <Text style={styles.empty}>Nema trenutno dostupnih operativnih akcija.</Text>
      ) : (
        <View style={styles.grid}>
          {displayed.map((action) => {
            const danger = action.kind === 'danger';
            const primary = action.kind === 'primary';
            return (
              <Pressable
                key={action.id}
                accessibilityRole="button"
                accessibilityLabel={action.label}
                accessibilityState={{ disabled: Boolean(action.disabled), busy: Boolean(action.pending) }}
                disabled={action.disabled}
                onPress={action.onPress}
                style={[
                  styles.action,
                  primary && styles.primaryAction,
                  danger && styles.dangerAction,
                  action.disabled && styles.disabled,
                ]}
              >
                <Glyph name={action.glyph} size={17} color={danger ? theme.danger : theme.primary} />
                <Text style={[styles.actionLabel, danger && styles.dangerLabel]}>{action.label}</Text>
              </Pressable>
            );
          })}
        </View>
      )}
      {hiddenCount > 0 ? (
        <Pressable
          accessibilityRole="button"
          accessibilityLabel={expanded ? 'Prikaži manje akcija' : 'Prikaži sve operativne akcije'}
          accessibilityState={{ expanded }}
          style={styles.disclosure}
          onPress={() => setExpanded((value) => !value)}
        >
          <Text style={styles.disclosureLabel}>
            {expanded ? 'Manje akcija' : 'Sve akcije (' + hiddenCount + ' dodatnih)'}
          </Text>
          <Glyph name="arrow" size={17} color={theme.primary} />
        </Pressable>
      ) : null}
    </Card>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    card: { gap: spacing.md },
    heading: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
    title: { ...typography.h3, color: theme.ink },
    empty: { ...typography.small, color: theme.muted },
    grid: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
    action: {
      flexBasis: '47%', flexGrow: 1, minWidth: 0, minHeight: 58,
      alignItems: 'center', flexDirection: 'row', gap: spacing.sm,
      paddingVertical: 10, paddingHorizontal: 11, borderWidth: 1,
      borderRadius: 13, borderColor: theme.line, backgroundColor: theme.surfaceContainer,
    },
    primaryAction: { borderColor: theme.primary, backgroundColor: theme.primarySoft },
    dangerAction: { borderColor: theme.danger },
    disabled: { opacity: 0.45 },
    actionLabel: { ...typography.small, color: theme.ink, fontWeight: '750', flex: 1, flexShrink: 1 },
    dangerLabel: { color: theme.danger },
    disclosure: {
      alignSelf: 'flex-start', minHeight: 44, flexDirection: 'row',
      alignItems: 'center', paddingHorizontal: spacing.sm, gap: spacing.sm,
    },
    disclosureLabel: { ...typography.label, color: theme.primary },
  });
}
