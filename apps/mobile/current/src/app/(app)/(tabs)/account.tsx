import * as Application from 'expo-application';
import { router, type Href } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Glyph, type GlyphName } from '@/components/ui/glyph';
import { Pill } from '@/components/ui/pill';
import { radii, spacing, typography, type AppColors } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';
import { initials } from '@/lib/formatters';
import { useAppTheme, useThemedStyles } from '@/theme/app-theme';

export default function AccountScreen() {
  const { colors: themeColors } = useAppTheme();
  const styles = useThemedStyles(createStyles);
  const feedback = useAppFeedback();
  const { bootstrap, signOut } = useAuth();
  const user = bootstrap?.user;

  const logout = () => {
    void (async () => {
      const confirmed = await feedback.confirm({
        tone: 'danger',
        title: 'Odjava',
        message: 'Da li želiš da opozoveš ovu mobilnu sesiju?',
        confirmLabel: 'Odjavi me',
        cancelLabel: 'Ostani prijavljen',
      });

      if (confirmed) await signOut();
    })();
  };

  return (
    <Screen>
      {/* MOBILE_V0_9_ACCOUNT_SECTION_ORDER_BATCH5C */}
      {/* MOBILE_V1_0_ACCOUNT_HUB_UX_BATCH81 */}
      <PageHeader title="Nalog" eyebrow="Podešavanja" name={user?.name} />

      <View style={styles.profile}>
        <View style={styles.avatar}>
          <Text style={styles.initials}>{initials(user?.name)}</Text>
        </View>
        <View style={styles.profileCopy}>
          <Text style={styles.name}>{user?.name ?? 'Korisnik'}</Text>
          <Text style={styles.email}>{user?.email ?? user?.username}</Text>
          <Pill tone="primary">{user?.role?.name ?? 'Korisnik'}</Pill>
        </View>
      </View>

      <Card style={styles.summaryCard}>
        <Detail label="Korisničko ime" value={user?.username} />
        <Detail label="Telefon" value={user?.phone} />
        <Detail label="Grad" value={user?.city} />
        <Detail label="Grupa" value={user?.group?.name} last />
      </Card>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Nalog i bezbednost</Text>
        <HubRow
          icon="account"
          title="Profil"
          copy="Lični, kontakt i adresni podaci."
          onPress={() => router.push('/account/profile' as Href)}
          theme={themeColors}
        />
        <HubRow
          icon="lock"
          title="Bezbednost"
          copy="Promena lozinke i zaštita naloga."
          onPress={() => router.push('/account/security' as Href)}
          theme={themeColors}
        />
        <HubRow
          icon="bell"
          title="Obaveštenja"
          copy="Push registracija, kanali i kategorije."
          onPress={() => router.push('/notification-settings')}
          theme={themeColors}
        />
        <HubRow
          icon="device"
          title="Uređaji"
          copy="Pregledaj i opozovi prijavljene uređaje."
          onPress={() => router.push('/devices')}
          theme={themeColors}
        />
        <HubRow
          icon="lock"
          title="Aktivne prijave"
          copy="API i web sesije, opoziv pojedinačnih prijava."
          onPress={() => router.push('/sessions' as Href)}
          theme={themeColors}
        />
      </View>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Prikaz</Text>
        <HubRow
          icon="info"
          title="Izgled i valuta"
          copy="Tema aplikacije, primarna valuta prikaza i NBS kurs."
          onPress={() => router.push('/account/preferences' as Href)}
          theme={themeColors}
        />
      </View>

      <Card muted style={styles.versionCard}>
        <Text style={styles.version}>
          Ald1n Mobile {Application.nativeApplicationVersion ?? '1.0.0'} · build{' '}
          {Application.nativeBuildVersion ?? 'dev'}
        </Text>
        <Text style={styles.versionSub}>
          API {bootstrap?.app.api_version ?? 'v1'} · Backend {bootstrap?.app.backend_version ?? '—'}
        </Text>
      </Card>

      <Button variant="danger" onPress={logout}>Odjava</Button>
    </Screen>
  );
}

function HubRow({
  icon,
  title,
  copy,
  onPress,
  theme,
}: {
  icon: GlyphName;
  title: string;
  copy: string;
  onPress: () => void;
  theme: AppColors;
}) {
  const styles = createStyles(theme);
  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={title}
      onPress={onPress}
      style={({ pressed }) => pressed && styles.pressed}
    >
      <Card style={styles.rowCard}>
        <View style={styles.rowIcon}>
          <Glyph name={icon} size={23} color={theme.primary} />
        </View>
        <View style={styles.rowCopyWrap}>
          <Text style={styles.rowTitle}>{title}</Text>
          <Text style={styles.rowCopy}>{copy}</Text>
        </View>
        <Glyph name="arrow" size={26} color={theme.muted} />
      </Card>
    </Pressable>
  );
}

function Detail({ label, value, last = false }: { label: string; value?: string | null; last?: boolean }) {
  const styles = useThemedStyles(createStyles);
  return (
    <View style={[styles.detail, !last && styles.detailBorder]}>
      <Text style={styles.detailLabel}>{label}</Text>
      <Text style={styles.detailValue}>{value || '—'}</Text>
    </View>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    profile: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: spacing.lg,
      paddingVertical: spacing.sm,
    },
    avatar: {
      width: 72,
      height: 72,
      borderRadius: 36,
      backgroundColor: theme.primarySoft,
      alignItems: 'center',
      justifyContent: 'center',
      borderWidth: 1,
      borderColor: theme.primary,
    },
    initials: { ...typography.h2, color: theme.primaryDark },
    profileCopy: { flex: 1, alignItems: 'flex-start', gap: spacing.xs },
    name: { ...typography.h2, color: theme.ink },
    email: { ...typography.small, color: theme.muted },
    summaryCard: { gap: 0, paddingVertical: 0 },
    detail: {
      minHeight: 48,
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: spacing.md,
    },
    detailBorder: { borderBottomWidth: StyleSheet.hairlineWidth, borderBottomColor: theme.line },
    detailLabel: { ...typography.small, color: theme.muted, flex: 1 },
    detailValue: { ...typography.label, color: theme.ink, flex: 1, textAlign: 'right' },
    section: { gap: spacing.sm },
    sectionTitle: { ...typography.h3, color: theme.ink, marginBottom: spacing.xs },
    rowCard: {
      minHeight: 82,
      flexDirection: 'row',
      alignItems: 'center',
      gap: spacing.md,
      paddingVertical: spacing.md,
    },
    rowIcon: {
      width: 46,
      height: 46,
      borderRadius: radii.lg,
      backgroundColor: theme.primarySoft,
      alignItems: 'center',
      justifyContent: 'center',
    },
    rowCopyWrap: { flex: 1, gap: 3 },
    rowTitle: { ...typography.label, color: theme.ink },
    rowCopy: { ...typography.small, color: theme.muted },
    versionCard: { gap: spacing.xs },
    version: { ...typography.small, color: theme.ink, textAlign: 'center' },
    versionSub: { ...typography.small, color: theme.muted, textAlign: 'center' },
    pressed: { opacity: 0.72 },
  });
}
