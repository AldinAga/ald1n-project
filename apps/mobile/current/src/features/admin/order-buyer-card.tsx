import { useMemo } from 'react';
import * as Clipboard from 'expo-clipboard';
import { Linking, Pressable, StyleSheet, Text, View } from 'react-native';

import { Card } from '@/components/ui/card';
import { Glyph } from '@/components/ui/glyph';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { useAppTheme } from '@/theme/app-theme';

type Props = {
  name: string;
  phone: string;
  address: string;
  postalCode: string;
  city: string;
  note: string;
};

function display(value: string): string {
  const trimmed = value.trim();
  return trimmed !== '' && trimmed !== '-' ? trimmed : '—';
}

function callablePhone(phone: string): string | null {
  const normalized = phone.replace(/[^0-9+]/g, '');
  return /^\+?[0-9]{6,15}$/.test(normalized) ? normalized : null;
}

// Display the order's saved SHIPPING RECIPIENT, never creator/supplier data.
export function OrderBuyerCard({ name, phone, address, postalCode, city, note }: Props) {
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const feedback = useAppFeedback();
  const dial = callablePhone(phone);
  const hasPart = (value: string) => value.trim() !== '' && value.trim() !== '-' && value.trim() !== '—';
  const postalCity = [postalCode, city].filter(hasPart).join(' ');
  const addressDisplay = [address, postalCity].filter(hasPart).join(', ');

  async function copyPhone() {
    try {
      await Clipboard.setStringAsync(phone.trim());
      feedback.notify({ title: 'Broj telefona je kopiran', tone: 'success' });
    } catch {
      feedback.notify({ title: 'Kopiranje broja nije uspelo', tone: 'warning' });
    }
  }

  async function callPhone() {
    if (!dial) return;
    try {
      await Linking.openURL('tel:' + dial);
    } catch {
      feedback.notify({ title: 'Pozivanje trenutno nije dostupno', tone: 'warning' });
    }
  }

  return (
    <Card style={styles.card}>
      <View style={styles.heading}>
        <Glyph name="account" size={22} color={theme.primary} />
        <View style={styles.headingText}>
          <Text style={styles.eyebrow}>Prva informacija za obradu</Text>
          <Text style={styles.title}>Krajnji kupac — kontakt i dostava</Text>
        </View>
      </View>

      <View style={styles.field}>
        <Text style={styles.label}>Ime i prezime primaoca</Text>
        <Text selectable style={styles.name}>{display(name)}</Text>
      </View>
      <View style={styles.field}>
        <Text style={styles.label}>Telefon</Text>
        <Text selectable style={styles.value}>{display(phone)}</Text>
        {dial ? (
          <View style={styles.actions}>
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Pozovi krajnjeg kupca"
              style={styles.action}
              onPress={() => void callPhone()}
            >
              <Glyph name="phone" size={16} color={theme.primary} />
              <Text style={styles.actionText}>Pozovi</Text>
            </Pressable>
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Kopiraj broj telefona"
              style={styles.action}
              onPress={() => void copyPhone()}
            >
              <Glyph name="copy" size={16} color={theme.primary} />
              <Text style={styles.actionText}>Kopiraj broj</Text>
            </Pressable>
          </View>
        ) : null}
      </View>
      <View style={styles.field}>
        <Text style={styles.label}>Adresa za isporuku</Text>
        <Text selectable style={styles.value}>{display(addressDisplay)}</Text>
      </View>
      <View style={[styles.field, styles.lastField]}>
        <Text style={styles.label}>Napomena kupca</Text>
        <Text selectable style={styles.value}>{display(note)}</Text>
      </View>
    </Card>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    card: {
      gap: spacing.md,
      borderColor: theme.primary,
      borderLeftWidth: 3,
      borderLeftColor: theme.primary,
    },
    heading: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
    headingText: { flex: 1, minWidth: 0, gap: 3 },
    eyebrow: { ...typography.small, color: theme.primary, fontWeight: '800' },
    title: { ...typography.h3, color: theme.ink, flexShrink: 1 },
    field: { gap: 5, paddingBottom: spacing.sm, borderBottomWidth: 1, borderBottomColor: theme.line },
    lastField: { borderBottomWidth: 0, paddingBottom: 0 },
    label: { ...typography.small, color: theme.muted },
    name: { ...typography.h3, color: theme.ink },
    value: { ...typography.body, color: theme.ink },
    actions: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
    action: {
      flexDirection: 'row', alignItems: 'center', justifyContent: 'center',
      minHeight: 44, minWidth: 98, gap: 6, paddingHorizontal: spacing.md,
      borderWidth: 1, borderRadius: 12, borderColor: theme.line,
      backgroundColor: theme.surfaceContainer,
    },
    actionText: { ...typography.small, color: theme.primary, fontWeight: '800' },
  });
}
