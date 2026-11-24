// Author: Ald1n <https://ald1n.com>
import type { ReactNode } from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, View } from 'react-native';

import { BrandMark } from '@/components/ui/brand-mark';
import { radii, spacing, typography, type AppColors } from '@/constants/theme';
import { useThemedStyles } from '@/theme/app-theme';

// Shared Kobalt card for forgot / reset / activation: logo lockup, kicker, title, copy, then the form as children.
export function AuthShell({ kicker, title, copy, children }: { kicker: string; title: string; copy: string; children: ReactNode }) {
  const styles = useThemedStyles(createStyles);
  return (
    <KeyboardAvoidingView style={styles.page} behavior={Platform.OS === 'ios' ? 'padding' : 'height'}>
      <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
        <View style={styles.card}>
          <View style={styles.lockup}>
            <BrandMark size={40} />
            <Text style={styles.kicker} numberOfLines={1}>{kicker}</Text>
          </View>
          <Text style={styles.title}>{title}</Text>
          <Text style={styles.copy}>{copy}</Text>
          {children}
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

// Inline feedback: soft status surface plus coloured text; role=alert so TalkBack announces it.
export function AuthNotice({ tone, children }: { tone: 'info' | 'success' | 'danger'; children: ReactNode }) {
  const styles = useThemedStyles(createStyles);
  return (
    <View accessibilityRole="alert" accessibilityLiveRegion="polite" style={[styles.notice, styles[tone]]}>
      <Text style={[styles.noticeText, styles[`${tone}Text` as const]]}>{children}</Text>
    </View>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    page: { flex: 1, backgroundColor: theme.background },
    scroll: { flexGrow: 1, justifyContent: 'center', padding: spacing.lg },
    card: { backgroundColor: theme.surface, borderRadius: radii.xl, borderWidth: 1, borderColor: theme.line, padding: spacing.xl, gap: spacing.lg },
    lockup: { flexDirection: 'row', alignItems: 'center', gap: spacing.md },
    kicker: { ...typography.small, flex: 1, flexShrink: 1, color: theme.primary, fontWeight: '900', letterSpacing: 1.4 },
    title: { ...typography.h1, color: theme.ink },
    copy: { ...typography.body, color: theme.muted },
    notice: { padding: spacing.md, borderRadius: radii.lg, borderWidth: 1 },
    noticeText: { ...typography.small, fontWeight: '700' },
    info: { backgroundColor: theme.primarySoft, borderColor: theme.primary },
    infoText: { color: theme.primary },
    success: { backgroundColor: theme.successSoft, borderColor: theme.success },
    successText: { color: theme.success },
    danger: { backgroundColor: theme.dangerSoft, borderColor: theme.danger },
    dangerText: { color: theme.danger },
  });
}
