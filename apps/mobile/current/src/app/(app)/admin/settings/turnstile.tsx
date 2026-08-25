import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useEffect, useMemo, useState } from 'react';
import { StyleSheet, Switch, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import { apiAdminSystemSettings } from '@/features/admin/system-settings-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import { useAppTheme } from '@/theme/app-theme';

// MOBILE_V1_0_SYSTEM_SETTINGS_PARITY_BATCH37_SET04
export default function AdminTurnstileSettingsScreen() {
  const { can, bootstrap } = useAuth();
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const allowed = can('system.manage_settings');
  const query = useQuery({ queryKey: adminQueryKeys.systemSettingsTurnstile(), queryFn: apiAdminSystemSettings.turnstile.state, enabled: allowed });
  const [enabled, setEnabled] = useState(false);
  const [siteKey, setSiteKey] = useState('');
  const [secretKey, setSecretKey] = useState('');
  const [hostname, setHostname] = useState('');
  useEffect(() => { const data = query.data?.data; if (!data) return; setEnabled(data.settings.turnstile_enabled === '1'); setSiteKey(data.settings.turnstile_site_key ?? ''); setHostname(data.settings.turnstile_expected_hostname ?? ''); }, [query.data]);
  const mutation = useMutation({ mutationFn: () => apiAdminSystemSettings.turnstile.update({ turnstile_enabled: enabled, turnstile_site_key: siteKey, turnstile_secret_key: secretKey || undefined, turnstile_expected_hostname: hostname }), onSuccess: (response) => { client.setQueryData(adminQueryKeys.systemSettingsTurnstile(), response); setSecretKey(''); feedback.notify({ tone: 'success', title: 'Turnstile je sačuvan' }); }, onError: (error) => feedback.notify({ tone: 'danger', title: 'Turnstile nije sačuvan', message: error instanceof Error ? error.message : 'Greška.' }) });
  if (!allowed) return <UnavailableState title="Turnstile nije dostupan" />;
  if (query.isLoading) return <LoadingState label="Učitavanje Turnstile podešavanja…" />;
  if (query.isError || !query.data) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;
  const data = query.data.data;
  return <Screen contentStyle={styles.content}><PageHeader title="Cloudflare Turnstile" eyebrow="Sistem · SET-04" name={bootstrap?.user.name} /><Card style={styles.card}><View style={styles.row}><Text style={styles.body}>Zaštita prijave uključena</Text><Switch value={enabled} onValueChange={setEnabled} /></View><TextField label="Site Key" value={siteKey} onChangeText={setSiteKey} autoCapitalize="none" /><TextField label="Expected hostname" value={hostname} onChangeText={setHostname} autoCapitalize="none" /><TextField label={data.secret_configured ? 'Novi Secret Key (ostavi prazno da zadržiš postojeći)' : 'Secret Key'} value={secretKey} onChangeText={setSecretKey} secureTextEntry autoCapitalize="none" /><Text style={styles.meta}>Secret: {data.secret_configured ? `podešen · ${data.secret_source}` : 'nije podešen'} · Site Key: {data.site_key_source}</Text><Button loading={mutation.isPending} onPress={() => mutation.mutate()}>Sačuvaj Turnstile</Button></Card></Screen>;
}
function createStyles(theme: AppColors) { return StyleSheet.create({ content: { gap: spacing.lg, paddingBottom: 120 }, card: { gap: spacing.md }, body: { ...typography.body, color: theme.ink, flex: 1 }, meta: { ...typography.small, color: theme.muted }, row: { flexDirection: 'row', alignItems: 'center', gap: spacing.md } }); }
