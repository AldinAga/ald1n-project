import { zodResolver } from '@hookform/resolvers/zod';
import { router } from 'expo-router';
import { useState } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { Keyboard, Pressable, StyleSheet, Text, View } from 'react-native';
import { z } from 'zod';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { TextField } from '@/components/ui/text-field';
import { radii, spacing, typography, type AppColors } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';
import { ApiError } from '@/lib/api/client';
import { api } from '@/lib/api/endpoints';
import { useThemedStyles } from '@/theme/app-theme';

const passwordSchema = z
  .object({
    current_password: z.string().min(1, 'Unesi trenutnu lozinku.'),
    password: z.string().min(12, 'Nova lozinka mora imati najmanje 12 znakova.'),
    password_confirmation: z.string().min(1, 'Ponovi novu lozinku.'),
  })
  .refine((values) => values.password === values.password_confirmation, {
    message: 'Nova lozinka i potvrda se ne poklapaju.',
    path: ['password_confirmation'],
  });

type PasswordForm = z.infer<typeof passwordSchema>;

function apiMessage(error: unknown, fallback: string): string {
  if (error instanceof ApiError) return error.firstFieldError() ?? error.message;
  if (error instanceof Error && error.message) return error.message;
  return fallback;
}

export default function AccountSecurityScreen() {
  const styles = useThemedStyles(createStyles);
  const feedback = useAppFeedback();
  const { bootstrap, requireReauthentication } = useAuth();
  const [passwordApiError, setPasswordApiError] = useState<string | null>(null);
  const {
    control,
    handleSubmit,
    reset,
    formState: { errors, isSubmitting },
  } = useForm<PasswordForm>({
    resolver: zodResolver(passwordSchema),
    defaultValues: {
      current_password: '',
      password: '',
      password_confirmation: '',
    },
  });

  const changePassword = handleSubmit(async (values) => {
    setPasswordApiError(null);
    try {
      const response = await api.account.changePassword(values);
      reset();
      Keyboard.dismiss();
      feedback.notify({
        tone: 'success',
        title: 'Lozinka promenjena',
        message: response.message || 'Prijavi se ponovo novom lozinkom.',
        durationMs: 4200,
      });
      await requireReauthentication();
    } catch (error) {
      setPasswordApiError(apiMessage(error, 'Lozinku nije moguće promeniti.'));
    }
  });

  return (
    <Screen keyboardDismissMode="on-drag">
      {/* MOBILE_V1_0_ACCOUNT_HUB_SECURITY_BATCH81 */}
      <Pressable accessibilityRole="button" onPress={() => router.back()}>
        <Text style={styles.back}>‹ Nalog</Text>
      </Pressable>
      <PageHeader title="Bezbednost" eyebrow="Nalog" name={bootstrap?.user.name} />

      <Card style={styles.formCard}>
        <View style={styles.securityNotice}>
          <Text style={styles.securityNoticeText}>
            Nova lozinka mora imati najmanje 12 znakova. Nakon uspešne promene svi API tokeni se opozivaju i potrebno je da se ponovo prijaviš.
          </Text>
        </View>

        <Controller
          control={control}
          name="current_password"
          render={({ field }) => (
            <TextField
              label="Trenutna lozinka"
              value={field.value}
              onBlur={field.onBlur}
              onChangeText={field.onChange}
              error={errors.current_password?.message}
              secureTextEntry
              autoCapitalize="none"
              autoCorrect={false}
            />
          )}
        />
        <Controller
          control={control}
          name="password"
          render={({ field }) => (
            <TextField
              label="Nova lozinka"
              value={field.value}
              onBlur={field.onBlur}
              onChangeText={field.onChange}
              error={errors.password?.message}
              secureTextEntry
              autoCapitalize="none"
              autoCorrect={false}
            />
          )}
        />
        <Controller
          control={control}
          name="password_confirmation"
          render={({ field }) => (
            <TextField
              label="Ponovi novu lozinku"
              value={field.value}
              onBlur={field.onBlur}
              onChangeText={field.onChange}
              error={errors.password_confirmation?.message}
              secureTextEntry
              autoCapitalize="none"
              autoCorrect={false}
              returnKeyType="done"
              onSubmitEditing={() => void changePassword()}
            />
          )}
        />

        {passwordApiError ? <Text style={styles.formError}>{passwordApiError}</Text> : null}
        <Button variant="danger" onPress={changePassword} loading={isSubmitting}>
          Promeni lozinku
        </Button>
      </Card>

      <Button variant="secondary" onPress={() => router.push('/sessions')}>
        Aktivne prijave i sesije
      </Button>
    </Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    formCard: { gap: spacing.md },
    securityNotice: {
      padding: spacing.md,
      borderRadius: radii.lg,
      backgroundColor: theme.dangerSoft,
    },
    securityNoticeText: { ...typography.small, color: theme.danger },
    formError: { ...typography.small, color: theme.danger },
  });
}
