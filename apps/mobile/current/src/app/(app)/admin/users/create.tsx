import { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router, type Href } from 'expo-router';
import { Pressable, StyleSheet, Text } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import {
  apiAdminUsers,
  type AdminUserCreateInput,
  type AdminUserInput,
} from '@/features/admin/users-admin-api';
import { AdminUserForm } from '@/features/admin/users-admin-form';
import { useAuth } from '@/features/auth/auth-provider';
import { ApiError } from '@/lib/api/client';
import { useAppTheme } from '@/theme/app-theme';

function fieldErrors(error: unknown): Record<string, string> {
  if (!(error instanceof ApiError)) return {};
  const result: Record<string, string> = {};
  for (const [field, messages] of Object.entries(error.errors)) {
    const first = messages[0];
    if (first) result[field] = first;
  }
  return result;
}

// MOBILE_V0_8_COMPLETE_USER_MANAGEMENT_BATCH12
export default function AdminUsersCreateScreen() {
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const { can, bootstrap } = useAuth();
  const allowed = can('system.manage_users');
  const [errors, setErrors] = useState<Record<string, string>> ({});

  const optionsQuery = useQuery({
    queryKey: adminQueryKeys.userOptions(),
    queryFn: apiAdminUsers.options,
    enabled: allowed,
    staleTime: 60_000,
  });

  const mutation = useMutation({
    mutationFn: (input: AdminUserCreateInput) => apiAdminUsers.create(input),
    onSuccess: async (response) => {
      await client.invalidateQueries({ queryKey: adminQueryKeys.users() });
      feedback.notify({
        tone: 'success',
        title: 'Korisnik je dodat',
        message: `${response.data.name} · ${response.data.username}`,
      });
      router.replace({
        pathname: '/admin/users/[id]',
        params: { id: String(response.data.id) },
      } as Href);
    },
    onError: (error) => {
      setErrors(fieldErrors(error));
      feedback.notify({
        tone: 'danger',
        title: 'Korisnik nije dodat',
        message: error instanceof Error ? error.message : 'Server je odbio unos. Svi podaci ostaju u formi.',
      });
    },
  });

  if (!allowed) return <UnavailableState title="Dodavanje korisnika nije dostupno" />;
  if (optionsQuery.isLoading) return <LoadingState label="Učitavanje uloga i grupa…" />;
  if (optionsQuery.isError || !optionsQuery.data) {
    return <ErrorState error={optionsQuery.error} onRetry={() => void optionsQuery.refetch()} />;
  }

  const submit = (input: AdminUserInput) => {
    if (!input.password) {
      setErrors({ password: 'Početna lozinka je obavezna.' });
      return;
    }
    setErrors({});
    mutation.mutate({ ...input, password: input.password });
  };

  return (
    <Screen contentStyle={styles.content} keyboardShouldPersistTaps="handled">
      <Pressable accessibilityRole="button" onPress={() => router.back()}>
        <Text style={styles.back}>‹ Korisnici</Text>
      </Pressable>
      <PageHeader
        title="Novi korisnik"
        eyebrow="Admin · Pristup i ovlašćenja"
        name={bootstrap?.user.name}
      />
      <Text style={styles.copy}>
        Novi nalog koristi iste uloge, grupe, statuse i validaciju kao Laravel portal.
      </Text>
      <AdminUserForm
        mode="create"
        options={optionsQuery.data.data}
        serverErrors={errors}
        busy={mutation.isPending}
        onSubmit={submit}
      />
    </Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: { paddingBottom: 140, gap: spacing.lg },
    back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    copy: { ...typography.body, color: theme.muted },
  });
}
