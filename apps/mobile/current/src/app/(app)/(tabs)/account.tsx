import * as Application from 'expo-application';
import { router } from 'expo-router';
import { Alert, Pressable, StyleSheet, Text, View } from 'react-native';
import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Glyph } from '@/components/ui/glyph';
import { Pill } from '@/components/ui/pill';
import { colors, radii, spacing, typography } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';
import { initials } from '@/lib/formatters';

export default function AccountScreen() {
  const { bootstrap, signOut } = useAuth();
  const user = bootstrap?.user;
  const logout = () => Alert.alert('Odjava', 'Da li želiš da opozoveš ovu mobilnu sesiju?', [
    { text: 'Otkaži', style: 'cancel' },
    { text: 'Odjavi me', style: 'destructive', onPress: () => void signOut() }
  ]);

  return (
    <Screen>
      <PageHeader title="Nalog" eyebrow="Podešavanja" name={user?.name} />
      <View style={styles.profile}>
        <View style={styles.avatar}><Text style={styles.initials}>{initials(user?.name)}</Text></View>
        <Text style={styles.name}>{user?.name ?? 'Korisnik'}</Text>
        <Text style={styles.email}>{user?.email ?? user?.username}</Text>
        <Pill tone="primary">{user?.role?.name ?? 'Korisnik'}</Pill>
      </View>

      <Card style={styles.details}>
        <Detail label="Telefon" value={user?.phone} />
        <Detail label="Grad" value={user?.city} />
        <Detail label="Grupa" value={user?.group?.name} />
        <Detail label="Backend" value={bootstrap?.app.backend_version} last />
      </Card>

      <Pressable onPress={() => router.push('/notification-settings')}>
        <Card style={styles.rowCard}>
          <View style={styles.rowIcon}><Glyph name="bell" size={24} color={colors.primary} /></View>
          <View style={{ flex: 1 }}><Text style={styles.rowTitle}>Obaveštenja i push</Text><Text style={styles.rowCopy}>Push registracija, kanali i kategorije obaveštenja.</Text></View>
          <Glyph name="arrow" size={28} color={colors.muted} />
        </Card>
      </Pressable>

      <Pressable onPress={() => router.push('/devices')}>
        <Card style={styles.rowCard}>
          <View style={styles.rowIcon}><Glyph name="device" size={24} color={colors.primary} /></View>
          <View style={{ flex: 1 }}><Text style={styles.rowTitle}>Prijavljeni uređaji</Text><Text style={styles.rowCopy}>Pregledaj i opozovi mobilne sesije.</Text></View>
          <Glyph name="arrow" size={28} color={colors.muted} />
        </Card>
      </Pressable>

      <Card muted><Text style={styles.version}>Ald1n Mobile {Application.nativeApplicationVersion ?? '0.4.0'} · build {Application.nativeBuildVersion ?? 'dev'}</Text><Text style={styles.versionSub}>API {bootstrap?.app.api_version ?? 'v1'} · Bezbedna mobilna sesija</Text></Card>
      <Button variant="danger" onPress={logout}>Odjavi ovaj uređaj</Button>
    </Screen>
  );
}

function Detail({ label, value, last = false }: { label: string; value?: string | null; last?: boolean }) {
  return <View style={[styles.detail, !last && styles.detailBorder]}><Text style={styles.detailLabel}>{label}</Text><Text style={styles.detailValue}>{value || '—'}</Text></View>;
}

const styles = StyleSheet.create({
  profile: { alignItems: 'center', gap: spacing.sm, paddingVertical: spacing.lg },
  avatar: { width: 86, height: 86, borderRadius: 30, backgroundColor: colors.primary, alignItems: 'center', justifyContent: 'center', marginBottom: spacing.sm },
  initials: { fontSize: 29, fontWeight: '900', color: colors.white },
  name: { ...typography.h1, color: colors.ink, textAlign: 'center' },
  email: { ...typography.body, color: colors.muted },
  details: { paddingVertical: spacing.sm },
  detail: { minHeight: 54, flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', gap: spacing.lg },
  detailBorder: { borderBottomWidth: 1, borderBottomColor: colors.line },
  detailLabel: { ...typography.small, color: colors.muted },
  detailValue: { ...typography.label, color: colors.ink, textAlign: 'right', flex: 1 },
  rowCard: { flexDirection: 'row', alignItems: 'center', gap: spacing.md },
  rowIcon: { width: 48, height: 48, borderRadius: radii.lg, backgroundColor: colors.primarySoft, alignItems: 'center', justifyContent: 'center' },
  rowTitle: { ...typography.h3, color: colors.ink },
  rowCopy: { ...typography.small, color: colors.muted, marginTop: 3 },
  version: { ...typography.label, color: colors.ink },
  versionSub: { ...typography.small, color: colors.muted, marginTop: spacing.xs }
});
