import { useEffect, useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { router, type Href } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { Card } from '@/components/ui/card';
import { Pill } from '@/components/ui/pill';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { hasAdminAccess } from '@/features/admin/admin-access';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import {
  apiAdminGlobalSearch,
  type AdminGlobalSearchItem,
} from '@/features/admin/global-search-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import { useAppTheme } from '@/theme/app-theme';

// MOBILE_V1_0_GLOBAL_SEARCH_PARITY_BATCH38
export default function AdminGlobalSearchScreen() {
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const { bootstrap } = useAuth();
  const allowed = hasAdminAccess({
    permissions: bootstrap?.permissions ?? [],
    roleSlug: bootstrap?.user.role?.slug,
  });
  const [draftQuery, setDraftQuery] = useState('');
  const [searchQuery, setSearchQuery] = useState('');

  useEffect(() => {
    const normalized = draftQuery.trim().slice(0, 80);
    if (normalized.length < 2) {
      setSearchQuery(normalized);
      return undefined;
    }

    const timer = setTimeout(() => setSearchQuery(normalized), 250);
    return () => clearTimeout(timer);
  }, [draftQuery]);

  const query = useQuery({
    queryKey: adminQueryKeys.globalSearch(searchQuery),
    queryFn: () => apiAdminGlobalSearch.search(searchQuery),
    enabled: allowed && searchQuery.length >= 2,
    staleTime: 10_000,
  });

  if (!allowed) {
    return <UnavailableState title="Globalna pretraga nije dostupna" />;
  }

  const data = query.data?.data;
  const grouped = (groupKey: string): AdminGlobalSearchItem[] =>
    data?.items.filter((item) => item.group_key === groupKey) ?? [];

  return (
    <Screen contentStyle={styles.content}>
      <PageHeader
        title="Globalna pretraga"
        eyebrow="Admin · CAT-02"
        name={bootstrap?.user.name}
      />

      <Card style={styles.searchCard}>
        <Text style={styles.title}>Jedna pretraga kroz ceo operativni prostor</Text>
        <Text style={styles.copy}>
          Rezultati poštuju tvoje postojeće dozvole i obuhvataju artikle, porudžbine,
          korisnike, garancije, reklamacije i brze prečice.
        </Text>
        <TextField
          label="Traži"
          value={draftQuery}
          onChangeText={(value) => setDraftQuery(value.slice(0, 80))}
          placeholder="Najmanje 2 znaka"
          autoCapitalize="none"
          autoCorrect={false}
        />
        <Text style={styles.meta}>Pretraga se pokreće posle 250 ms i koristi postojeći Laravel ranking/permission authority.</Text>
      </Card>

      {searchQuery.length < 2 ? (
        <Card style={styles.card}>
          <Text style={styles.copy}>Unesi najmanje 2 znaka za globalnu pretragu.</Text>
        </Card>
      ) : null}

      {searchQuery.length >= 2 && query.isLoading ? <LoadingState label="Globalna pretraga…" /> : null}
      {searchQuery.length >= 2 && query.isError ? (
        <ErrorState error={query.error} onRetry={() => void query.refetch()} />
      ) : null}

      {searchQuery.length >= 2 && !query.isLoading && !query.isError && data?.items.length === 0 ? (
        <Card style={styles.card}>
          <Text style={styles.title}>Nema rezultata</Text>
          <Text style={styles.copy}>Nijedan rezultat koji smeš da vidiš ne odgovara upitu „{searchQuery}“.</Text>
        </Card>
      ) : null}

      {data?.sections.map((section) => {
        const items = grouped(section.key);
        if (items.length === 0) return null;

        return (
          <Card key={section.key} style={styles.card}>
            <View style={styles.sectionHead}>
              <Text style={styles.title}>{section.label}</Text>
              <Pill tone="primary">{section.count}</Pill>
            </View>
            <View style={styles.results}>
              {items.map((item) => (
                <Pressable
                  key={item.id}
                  accessibilityRole="button"
                  accessibilityLabel={`Otvori ${item.badge}: ${item.title}`}
                  onPress={() => router.push(item.mobile_path as Href)}
                  style={({ pressed }) => [styles.result, pressed ? styles.resultPressed : null]}
                >
                  <View style={styles.resultHead}>
                    <Text style={styles.resultTitle}>{item.title}</Text>
                    <Pill>{item.badge}</Pill>
                  </View>
                  {item.identifier ? <Text style={styles.identifier}>{item.identifier}</Text> : null}
                  {item.subtitle ? <Text style={styles.copy}>{item.subtitle}</Text> : null}
                  {typeof item.stock_quantity === 'number' ? (
                    <Text style={styles.meta}>Lager: {item.stock_quantity}</Text>
                  ) : null}
                </Pressable>
              ))}
            </View>
          </Card>
        );
      })}
    </Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: { gap: spacing.lg, paddingBottom: 160 },
    searchCard: { gap: spacing.md },
    card: { gap: spacing.md },
    title: { ...typography.h2, color: theme.ink },
    copy: { ...typography.body, color: theme.muted },
    meta: { ...typography.small, color: theme.muted },
    sectionHead: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: spacing.md },
    results: { gap: spacing.sm },
    result: {
      gap: spacing.xs,
      paddingVertical: spacing.md,
      paddingHorizontal: spacing.md,
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: 14,
      backgroundColor: theme.surface,
    },
    resultPressed: { opacity: 0.72 },
    resultHead: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: spacing.md },
    resultTitle: { ...typography.h3, color: theme.ink, flex: 1 },
    identifier: { ...typography.label, color: theme.primary },
  });
}
