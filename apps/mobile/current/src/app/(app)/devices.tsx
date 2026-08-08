import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router } from 'expo-router';
import { Alert, Pressable, StyleSheet, Text, View } from 'react-native';
import { Screen } from '@/components/layout/screen';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Glyph } from '@/components/ui/glyph';
import { Pill } from '@/components/ui/pill';
import { EmptyState, ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { colors, radii, spacing, typography } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';
import { api } from '@/lib/api/endpoints';
import { formatDate } from '@/lib/formatters';
import type { MobileDevice } from '@/types/api';

export default function DevicesScreen() {
  const client = useQueryClient();
  const { signOut, hasFeature } = useAuth();
  const allowed = hasFeature('mobile_devices');
  const query = useQuery({ queryKey: ['devices'], queryFn: api.devices.list, enabled: allowed });
  const revoke = useMutation({ mutationFn: api.devices.revoke, onSuccess: async (_, id) => { const current = query.data?.find((item) => item.id === id)?.is_current; if (current) await signOut(); else await client.invalidateQueries({ queryKey: ['devices'] }); } });
  if (!allowed) return <UnavailableState title="Upravljanje uređajima nije dostupno" />;
  if (query.isLoading) return <LoadingState label="Učitavanje uređaja…" />;
  if (query.isError) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;
  const confirm = (device: MobileDevice) => Alert.alert('Opoziv uređaja', device.is_current ? 'Opoziv trenutnog uređaja će te odmah odjaviti.' : `Opozvati sesiju „${device.device_name ?? device.platform}”?`, [{ text: 'Odustani', style: 'cancel' }, { text: 'Opozovi', style: 'destructive', onPress: () => revoke.mutate(device.id) }]);
  return (
    <Screen>
      <Pressable onPress={() => router.back()}><Text style={styles.back}>‹ Nazad na nalog</Text></Pressable>
      <Text style={styles.title}>Prijavljeni uređaji</Text>
      <Text style={styles.copy}>Svaki uređaj ima zasebnu bezbednu sesiju. Opoziv prekida samo izabranu mobilnu prijavu.</Text>
      {query.data?.length ? query.data.map((device) => <Card key={device.id} style={styles.card}><View style={styles.icon}><Glyph name="device" size={26} color={colors.primary} /></View><View style={styles.deviceCopy}><View style={styles.deviceHead}><Text style={styles.deviceName}>{device.device_name ?? humanPlatform(device.platform)}</Text>{device.is_current ? <Pill tone="success">Ovaj uređaj</Pill> : <Pill>{humanPlatform(device.platform)}</Pill>}</View><Text style={styles.meta}>Verzija {device.app_version ?? '—'} · build {device.build_number ?? '—'}</Text><Text style={styles.meta}>Push: {device.push_registered && device.notifications_enabled ? 'registrovan' : 'nije aktivan'}</Text><Text style={styles.meta}>Poslednja aktivnost: {formatDate(device.last_seen_at, true)}</Text><Button variant="danger" onPress={() => confirm(device)} loading={revoke.isPending}>Opozovi sesiju</Button></View></Card>) : <EmptyState title="Nema uređaja" message="Ponovo pokreni aplikaciju kako bi registracija pokušala ponovo." />}
    </Screen>
  );
}
function humanPlatform(platform: string) { return platform === 'ios' ? 'iPhone / iPad' : 'Android'; }
const styles = StyleSheet.create({
  back: { ...typography.label, color: colors.primary, paddingVertical: spacing.sm },
  title: { ...typography.h1, color: colors.ink },
  copy: { ...typography.body, color: colors.muted },
  card: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md },
  icon: { width: 50, height: 50, borderRadius: radii.lg, backgroundColor: colors.primarySoft, alignItems: 'center', justifyContent: 'center' },
  deviceCopy: { flex: 1, gap: spacing.sm },
  deviceHead: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', gap: spacing.sm },
  deviceName: { ...typography.h3, color: colors.ink, flex: 1 },
  meta: { ...typography.small, color: colors.muted }
});
