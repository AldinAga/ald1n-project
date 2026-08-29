import { zodResolver } from '@hookform/resolvers/zod';
import { router } from 'expo-router';
import { useEffect, useState } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { Keyboard, Pressable, StyleSheet, Text } from 'react-native';
import { z } from 'zod';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';
import { ApiError } from '@/lib/api/client';
import { api } from '@/lib/api/endpoints';
import { useThemedStyles } from '@/theme/app-theme';
import type { User } from '@/types/api';

const profileSchema = z.object({
  first_name: z.string().trim().max(100, 'Ime može imati najviše 100 znakova.'),
  last_name: z.string().trim().max(100, 'Prezime može imati najviše 100 znakova.'),
  phone: z.string().trim().max(40, 'Telefon može imati najviše 40 znakova.'),
  address: z.string().trim().max(255, 'Adresa može imati najviše 255 znakova.'),
  city: z.string().trim().max(120, 'Grad može imati najviše 120 znakova.'),
  postal_code: z.string().trim().max(20, 'Poštanski broj može imati najviše 20 znakova.'),
});

type ProfileForm = z.infer<typeof profileSchema>;

function profileDefaults(user?: User): ProfileForm {
  return {
    first_name: user?.first_name ?? '',
    last_name: user?.last_name ?? '',
    phone: user?.phone ?? '',
    address: user?.address ?? '',
    city: user?.city ?? '',
    postal_code: user?.postal_code ?? '',
  };
}

function nullable(value: string): string | null {
  const normalized = value.trim();
  return normalized === '' ? null : normalized;
}

function apiMessage(error: unknown, fallback: string): string {
  if (error instanceof ApiError) return error.firstFieldError() ?? error.message;
  if (error instanceof Error && error.message) return error.message;
  return fallback;
}

export default function AccountProfileScreen() {
  const styles = useThemedStyles(createStyles);
  const feedback = useAppFeedback();
  const { bootstrap, replaceBootstrapUser } = useAuth();
  const user = bootstrap?.user;
  const [profileApiError, setProfileApiError] = useState<string | null>(null);
  const {
    control,
    handleSubmit,
    reset,
    formState: { errors, isSubmitting },
  } = useForm<ProfileForm>({
    resolver: zodResolver(profileSchema),
    defaultValues: profileDefaults(user),
  });

  useEffect(() => {
    if (user) reset(profileDefaults(user));
  }, [reset, user?.id]);

  const saveProfile = handleSubmit(async (values) => {
    setProfileApiError(null);
    try {
      const updated = await api.account.updateProfile({
        first_name: values.first_name.trim(),
        last_name: nullable(values.last_name),
        phone: nullable(values.phone),
        address: nullable(values.address),
        city: nullable(values.city),
        postal_code: nullable(values.postal_code),
      });
      replaceBootstrapUser(updated);
      Keyboard.dismiss();
      feedback.notify({
        tone: 'success',
        title: 'Profil sačuvan',
        message: 'Podaci naloga su uspešno ažurirani.',
      });
    } catch (error) {
      setProfileApiError(apiMessage(error, 'Profil nije moguće sačuvati.'));
    }
  });

  return (
    <Screen keyboardDismissMode="on-drag">
      {/* MOBILE_V1_0_ACCOUNT_HUB_PROFILE_BATCH81 */}
      <Pressable accessibilityRole="button" onPress={() => router.back()}>
        <Text style={styles.back}>‹ Nalog</Text>
      </Pressable>
      <PageHeader title="Profil" eyebrow="Nalog" name={user?.name} />
      <Text style={styles.copy}>Podaci koji se koriste za nalog, kontakt i isporuku.</Text>

      <Card style={styles.formCard}>
        <Controller
          control={control}
          name="first_name"
          render={({ field }) => (
            <TextField
              label="Ime"
              value={field.value}
              onBlur={field.onBlur}
              onChangeText={field.onChange}
              error={errors.first_name?.message}
              autoCapitalize="words"
              maxLength={100}
            />
          )}
        />
        <Controller
          control={control}
          name="last_name"
          render={({ field }) => (
            <TextField
              label="Prezime"
              value={field.value}
              onBlur={field.onBlur}
              onChangeText={field.onChange}
              error={errors.last_name?.message}
              autoCapitalize="words"
              maxLength={100}
            />
          )}
        />
        <Controller
          control={control}
          name="phone"
          render={({ field }) => (
            <TextField
              label="Telefon"
              value={field.value}
              onBlur={field.onBlur}
              onChangeText={field.onChange}
              error={errors.phone?.message}
              keyboardType="phone-pad"
              maxLength={40}
            />
          )}
        />
        <Controller
          control={control}
          name="address"
          render={({ field }) => (
            <TextField
              label="Adresa"
              value={field.value}
              onBlur={field.onBlur}
              onChangeText={field.onChange}
              error={errors.address?.message}
              maxLength={255}
            />
          )}
        />
        <Controller
          control={control}
          name="city"
          render={({ field }) => (
            <TextField
              label="Grad"
              value={field.value}
              onBlur={field.onBlur}
              onChangeText={field.onChange}
              error={errors.city?.message}
              autoCapitalize="words"
              maxLength={120}
            />
          )}
        />
        <Controller
          control={control}
          name="postal_code"
          render={({ field }) => (
            <TextField
              label="Poštanski broj"
              value={field.value}
              onBlur={field.onBlur}
              onChangeText={field.onChange}
              error={errors.postal_code?.message}
              maxLength={20}
              returnKeyType="done"
              onSubmitEditing={() => void saveProfile()}
            />
          )}
        />

        {profileApiError ? <Text style={styles.formError}>{profileApiError}</Text> : null}
        <Button onPress={saveProfile} loading={isSubmitting}>Sačuvaj profil</Button>
      </Card>
    </Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    copy: { ...typography.body, color: theme.muted },
    formCard: { gap: spacing.md },
    formError: { ...typography.small, color: theme.danger },
  });
}
