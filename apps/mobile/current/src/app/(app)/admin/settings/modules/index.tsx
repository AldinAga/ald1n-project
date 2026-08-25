import { useEffect, useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { StyleSheet, Switch, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Pill } from '@/components/ui/pill';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import { apiAdminModuleSettings } from '@/features/admin/module-settings-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import { useAppTheme } from '@/theme/app-theme';

export default function AdminModuleSettingsScreen() {
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const { bootstrap, can, refreshBootstrap } = useAuth();
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const isSuperAdmin = bootstrap?.user.role?.slug === 'superadmin';
  const allowed = isSuperAdmin && can('system.manage_settings');
  const [draft, setDraft] = useState({} as Record<string, boolean>);

  const query = useQuery({
    queryKey: adminQueryKeys.moduleSettings(),
    queryFn: apiAdminModuleSettings.state,
    enabled: allowed,
    staleTime: 30_000,
  });

  const data = query.data?.data ?? null;

  useEffect(() => {
    if (!data) return;
    setDraft(Object.fromEntries(data.modules.map((module) => [module.key, module.enabled])));
  }, [data]);

  const changed = data?.modules.some((module) => draft[module.key] !== module.enabled) ?? false;

  const mutation = useMutation({
    mutationFn: async () => {
      if (!data) throw new Error('Podešavanja modula nisu učitana.');
      const payload = Object.fromEntries(
        data.modules.map((module) => [module.key, Boolean(draft[module.key])]),
      );
      return apiAdminModuleSettings.update(payload);
    },
    onSuccess: async (response) => {
      client.setQueryData(adminQueryKeys.moduleSettings(), response);
      setDraft(Object.fromEntries(response.data.modules.map((module) => [module.key, module.enabled])));
      await Promise.all([
        client.invalidateQueries({ queryKey: adminQueryKeys.foundation() }),
        refreshBootstrap(),
      ]);
      feedback.notify({
        tone: 'success',
        title: 'Moduli su sačuvani',
        message: response.message ?? 'Mobile i CMS sada koriste novo stanje opcionih modula.',
      });
    },
    onError: async (error) => {
      await client.invalidateQueries({ queryKey: adminQueryKeys.moduleSettings() });
      feedback.notify({
        tone: 'danger',
        title: 'Moduli nisu sačuvani',
        message: error instanceof Error ? error.message : 'Server trenutno ne može da sačuva podešavanje.',
      });
    },
  });

  if (!allowed) {
    return <UnavailableState title="Moduli sistema su dostupni samo Super Administratoru" />;
  }
  if (query.isLoading) return <LoadingState label="Učitavanje modula sistema…" />;
  if (query.isError || !data) {
    return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;
  }

  const restoreDraft = () => {
    setDraft(Object.fromEntries(data.modules.map((module) => [module.key, module.enabled])));
  };

  return (
    <Screen contentStyle={styles.content}>
      <PageHeader
        title="Moduli sistema"
        eyebrow="Admin · Podešavanja"
        name={bootstrap?.user.name}
      />

      <Card style={styles.hero}>
        <Pill tone="primary">SUPERADMIN</Pill>
        <Text style={styles.heroTitle}>Uključi samo opcione domene koji su trenutno potrebni.</Text>
        <Text style={styles.copy}>
          Isključivanje ne briše podatke, istoriju, rute ili domain servise. Menja vidljivost interfejsa i postojeće Mobile feature flagove tamo gde već postoje.
        </Text>
      </Card>

      <View style={styles.list}>
        {data.modules.map((module) => {
          const enabled = Boolean(draft[module.key]);
          return (
            <Card key={module.key} style={styles.moduleCard}>
              <View style={styles.rowBetween}>
                <View style={styles.flex}>
                  <Text style={styles.moduleTitle}>{module.label}</Text>
                  <Text style={styles.copy}>{module.description}</Text>
                </View>
                <Switch
                  accessibilityLabel={`${module.label}: ${enabled ? 'uključen' : 'isključen'}`}
                  value={enabled}
                  disabled={mutation.isPending || !data.capabilities.update}
                  onValueChange={(value) => setDraft((current) => ({ ...current, [module.key]: value }))}
                />
              </View>
              <Pill tone={enabled ? 'success' : 'warning'}>
                {enabled ? 'UKLJUČEN' : 'ISKLJUČEN'}
              </Pill>
            </Card>
          );
        })}
      </View>

      <Card style={styles.card}>
        <Text style={styles.sectionTitle}>Osnovni moduli</Text>
        <Text style={styles.copy}>{data.core_modules.join(' · ')}</Text>
        <Text style={styles.copy}>Osnovni moduli ostaju uključeni i ne mogu se deaktivirati iz ovog ekrana.</Text>
      </Card>

      <Card style={styles.card}>
        <Text style={styles.sectionTitle}>Sačuvaj promene</Text>
        <Text style={styles.copy}>
          Promena je reverzibilna. Posle čuvanja Mobile odmah osvežava bootstrap feature flagove i Admin Foundation.
        </Text>
        <View style={styles.actions}>
          <Button
            loading={mutation.isPending}
            onPress={() => {
              if (changed && data.capabilities.update) mutation.mutate();
            }}
          >
            Sačuvaj module
          </Button>
          <Button variant="secondary" onPress={restoreDraft}>
            Vrati trenutno stanje
          </Button>
        </View>
        {!changed ? <Text style={styles.meta}>Nema nesačuvanih promena.</Text> : null}
      </Card>
    </Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: { paddingBottom: 140, gap: spacing.lg },
    hero: { gap: spacing.md },
    heroTitle: { ...typography.h2, color: theme.ink },
    copy: { ...typography.body, color: theme.muted },
    list: { gap: spacing.md },
    moduleCard: { gap: spacing.sm },
    rowBetween: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: spacing.md },
    flex: { flex: 1, gap: spacing.xs },
    moduleTitle: { ...typography.h3, color: theme.ink },
    card: { gap: spacing.md },
    sectionTitle: { ...typography.h2, color: theme.ink },
    actions: { gap: spacing.sm },
    meta: { ...typography.small, color: theme.muted },
  });
}