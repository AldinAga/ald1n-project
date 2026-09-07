import * as Application from 'expo-application';
import { router, type Href } from 'expo-router';
import { StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { OperatorRow } from '@/components/ui/operator-row';
import { Pill } from '@/components/ui/pill';
import { radii, spacing, typography, type AppColors } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';
import { initials } from '@/lib/formatters';
import { useThemedStyles } from '@/theme/app-theme';

export default function AccountScreen() {
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
      {/* MOBILE_BUILD16_ACCOUNT_REDESIGN_BATCH130 */}
      <PageHeader title="Nalog" eyebrow="Podešavanja" name={user?.name} />

      <View style={styles.identitySurface}>
        <View style={styles.rail} />
        <View style={styles.avatar}>
          <Text style={styles.initials}>{initials(user?.name)}</Text>
        </View>
        <View style={styles.profileCopy}>
          <Text style={styles.name}>{user?.name ?? 'Korisnik'}</Text>
          <Text style={styles.email}>{user?.email ?? user?.username}</Text>
          <Pill tone="primary">{user?.role?.name ?? 'Korisnik'}</Pill>
        </View>
      </View>

      <View style={styles.summarySurface}>
        <Detail label="Korisničko ime" value={user?.username} />
        <Detail label="Telefon" value={user?.phone} />
        <Detail label="Grad" value={user?.city} />
        <Detail label="Grupa" value={user?.group?.name} last />
      </View>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Nalog i bezbednost</Text>
        <View style={styles.operatorGroup}>
          <OperatorRow
            glyph="account"
            title="Profil"
            copy="Lični, kontakt i adresni podaci."
            onPress={() => router.push('/account/profile' as Href)}
            divider
          />
          <OperatorRow
            glyph="lock"
            title="Bezbednost"
            copy="Promena lozinke i zaštita naloga."
            onPress={() => router.push('/account/security' as Href)}
            divider
          />
          <OperatorRow
            glyph="bell"
            title="Obaveštenja"
            copy="Push registracija, kanali i kategorije."
            onPress={() => router.push('/notification-settings')}
            divider
          />
          <OperatorRow
            glyph="device"
            title="Uređaji"
            copy="Pregledaj i opozovi prijavljene uređaje."
            onPress={() => router.push('/devices')}
          />
        </View>
      </View>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Pristup i prikaz</Text>
        <View style={styles.operatorGroup}>
          <OperatorRow
            glyph="lock"
            title="Aktivne prijave"
            copy="API i web sesije, opoziv pojedinačnih prijava."
            onPress={() => router.push('/sessions' as Href)}
            divider
          />
          <OperatorRow
            glyph="info"
            title="Izgled i valuta"
            copy="Tema aplikacije, primarna valuta prikaza i NBS kurs."
            onPress={() => router.push('/account/preferences' as Href)}
          />
        </View>
      </View>

      <View style={styles.versionSurface}>
        <Text style={styles.version}>
          Ald1n Mobile {Application.nativeApplicationVersion ?? '1.0.0'} · build{' '}
          {Application.nativeBuildVersion ?? 'dev'}
        </Text>
        <Text style={styles.versionSub}>
          API {bootstrap?.app.api_version ?? 'v1'} · Backend {bootstrap?.app.backend_version ?? '—'}
        </Text>
      </View>

      <Button variant="danger" onPress={logout}>Odjava</Button>
    </Screen>
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
    identitySurface: {
      position: 'relative',
      overflow: 'hidden',
      minHeight: 98,
      flexDirection: 'row',
      alignItems: 'center',
      gap: spacing.lg,
      padding: spacing.lg,
      paddingLeft: spacing.xl,
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.lg,
      backgroundColor: theme.surface,
    },
    rail: { position: 'absolute', left: 0, top: 0, bottom: 0, width: 4, backgroundColor: theme.primary },
    avatar: {
      width: 64,
      height: 64,
      borderRadius: radii.xl,
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
    summarySurface: {
      overflow: 'hidden',
      paddingHorizontal: spacing.lg,
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.lg,
      backgroundColor: theme.surface,
    },
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
    operatorGroup: {
      overflow: 'hidden',
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.lg,
      backgroundColor: theme.surface,
    },
    versionSurface: {
      gap: spacing.xs,
      padding: spacing.md,
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.lg,
      backgroundColor: theme.surfaceContainer,
    },
    version: { ...typography.small, color: theme.ink, textAlign: 'center', fontVariant: ['tabular-nums'] },
    versionSub: { ...typography.small, color: theme.muted, textAlign: 'center', fontVariant: ['tabular-nums'] },
  });
}
