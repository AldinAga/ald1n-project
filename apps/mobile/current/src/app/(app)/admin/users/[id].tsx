import { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router, useLocalSearchParams } from 'expo-router';
import { Pressable, StyleSheet, Text } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import {
  apiAdminUsers,
  type AdminUserInput,
  type AdminUserMutationResponse,
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
export default function AdminUsersDetailScreen() {
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const {
    can,
    bootstrap,
    refreshBootstrap,
    requireReauthentication,
  } = useAuth();
  const allowed = can('system.manage_users');
  const params = useLocalSearchParams<{ id?: string | string[] }> ();
  const rawId = Array.isArray(params.id) ? params.id[0] : params.id;
  const userId = Number(rawId);
  const validId = Number.isInteger(userId) && userId > 0;
  const [errors, setErrors] = useState<Record<string, string>> ({});
  const [formRevision, setFormRevision] = useState(0);

  const userQuery = useQuery({
    queryKey: adminQueryKeys.user(userId),
    queryFn: () => apiAdminUsers.detail(userId),
    enabled: allowed && validId,
  });

  const optionsQuery = useQuery({
    queryKey: adminQueryKeys.userOptions(),
    queryFn: apiAdminUsers.options,
    enabled: allowed,
    staleTime: 60_000,
  });

  const mutation = useMutation({
    mutationFn: (input: AdminUserInput) => apiAdminUsers.update(userId, input),
    onSuccess: async (response: AdminUserMutationResponse) => {
      client.setQueryData(adminQueryKeys.user(userId), {
        data: response.data,
        capabilities: userQuery.data?.capabilities ?? optionsQuery.data?.capabilities,
      });
      await client.invalidateQueries({ queryKey: adminQueryKeys.users() });
      setErrors({});
      setFormRevision((current) => current + 1);

      if (response.reauthenticate) {
        feedback.notify({
          tone: 'success',
          title: 'Lozinka je promenjena',
          message: 'Svi tokeni su opozvani. Prijavi se ponovo novom lozinkom.',
          durationMs: 5200,
        });
        await requireReauthentication();
        return;
      }

      if (bootstrap?.user.id === response.data.id) {
        try {
          await refreshBootstrap();
        } catch {
          await requireReauthentication();
          return;
        }
      }

      feedback.notify({
        tone: 'success',
        title: 'Korisnik je sačuvan',
        message: `${response.data.name} · ${response.data.status}`,
      });
    },
    onError: (error) => {
      setErrors(fieldErrors(error));
      feedback.notify({
        tone: 'danger',
        title: 'Korisnik nije sačuvan',
        message: error instanceof Error ? error.message : 'Server je odbio izmenu. Svi podaci ostaju u formi.',
      });
    },
  });

  if (!allowed) return <UnavailableState title="Upravljanje korisnicima nije dostupno" />;
  if (!validId) return <UnavailableState title="Korisnik nije validan" />;
  if (userQuery.isLoading || optionsQuery.isLoading) {
    return <LoadingState label="Učitavanje korisnika…" />;
  }
  if (userQuery.isError || !userQuery.data) {
    return <ErrorState error={userQuery.error} onRetry={() => void userQuery.refetch()} />;
  }
  if (optionsQuery.isError || !optionsQuery.data) {
    return <ErrorState error={optionsQuery.error} onRetry={() => void optionsQuery.refetch()} />;
  }

  const user = userQuery.data.data;

  return (
    <Screen contentStyle={styles.content} keyboardShouldPersistTaps="handled">
      <Pressable accessibilityRole="button" onPress={() => router.back()}>
        <Text style={styles.back}>‹ Korisnici</Text>
      </Pressable>
      <PageHeader
        title={user.name}
        eyebrow="Admin · Uredi korisnika"
        name={bootstrap?.user.name}
      />
      <Text style={styles.copy}>
        Izmena koristi centralni Laravel User Manager. Nova lozinka opoziva sve postojeće API tokene korisnika.
      </Text>
      <AdminUserForm
        key={`${user.id}:${user.status}:${user.role?.id ?? 0}:${user.group?.id ?? 0}:${formRevision}`}
        mode="edit"
        options={optionsQuery.data.data}
        initial={user}
        serverErrors={errors}
        busy={mutation.isPending}
        onSubmit={(input) => { setErrors({}); mutation.mutate(input); }}
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
