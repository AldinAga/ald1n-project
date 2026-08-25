import { zodResolver } from '@hookform/resolvers/zod';
import * as Application from 'expo-application';
import { router, type Href } from 'expo-router';
import { useEffect, useState } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { Keyboard, Pressable, StyleSheet, Text, View } from 'react-native';
import { z } from 'zod';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { Button } from '@/components/ui/button';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Card } from '@/components/ui/card';
import { Glyph } from '@/components/ui/glyph';
import { Pill } from '@/components/ui/pill';
import { TextField } from '@/components/ui/text-field';
import { radii, spacing, typography, type AppColors } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';
import { ApiError } from '@/lib/api/client';
import { api } from '@/lib/api/endpoints';
import { initials } from '@/lib/formatters';
import { useAppTheme, useThemedStyles } from '@/theme/app-theme';

import type { User } from '@/types/api';

const profileSchema = z.object({
  first_name: z
    .string()
    .trim()
    .max(100, 'Ime može imati najviše 100 znakova.'),

  last_name: z
    .string()
    .trim()
    .max(100, 'Prezime može imati najviše 100 znakova.'),

  phone: z
    .string()
    .trim()
    .max(40, 'Telefon može imati najviše 40 znakova.'),

  address: z
    .string()
    .trim()
    .max(255, 'Adresa može imati najviše 255 znakova.'),

  city: z
    .string()
    .trim()
    .max(120, 'Grad može imati najviše 120 znakova.'),

  postal_code: z
    .string()
    .trim()
    .max(20, 'Poštanski broj može imati najviše 20 znakova.')
});

const passwordSchema = z
  .object({
    current_password: z
      .string()
      .min(1, 'Unesi trenutnu lozinku.'),

    password: z
      .string()
      .min(
        12,
        'Nova lozinka mora imati najmanje 12 znakova.'
      ),

    password_confirmation: z
      .string()
      .min(1, 'Ponovi novu lozinku.')
  })
  .refine(
    (values) =>
      values.password
      === values.password_confirmation,
    {
      message:
        'Nova lozinka i potvrda se ne poklapaju.',
      path: ['password_confirmation']
    }
  );

type ProfileForm =
  z.infer<typeof profileSchema>;

type PasswordForm =
  z.infer<typeof passwordSchema>;

function profileDefaults(
  user?: User
): ProfileForm {
  return {
    first_name:
      user?.first_name ?? '',

    last_name:
      user?.last_name ?? '',

    phone:
      user?.phone ?? '',

    address:
      user?.address ?? '',

    city:
      user?.city ?? '',

    postal_code:
      user?.postal_code ?? ''
  };
}

function nullable(
  value: string
): string | null {
  const normalized =
    value.trim();

  return normalized === ''
    ? null
    : normalized;
}

function apiMessage(
  error: unknown,
  fallback: string
): string {
  if (error instanceof ApiError) {
    return (
      error.firstFieldError()
      ?? error.message
    );
  }

  if (
    error instanceof Error
    && error.message
  ) {
    return error.message;
  }

  return fallback;
}

export default function AccountScreen() {
  const {
    colors: themeColors
  } = useAppTheme();

  const styles =
    useThemedStyles(createStyles);

  const feedback = useAppFeedback();

  const {
    bootstrap,
    signOut,
    replaceBootstrapUser,
    requireReauthentication
  } = useAuth();

  const user =
    bootstrap?.user;

  const [
    profileApiError,
    setProfileApiError
  ] = useState<string | null>(null);

  const [
    passwordApiError,
    setPasswordApiError
  ] = useState<string | null>(null);

  const {
    control: profileControl,
    handleSubmit: handleProfileSubmit,
    reset: resetProfile,
    formState: {
      errors: profileErrors,
      isSubmitting: profileSubmitting
    }
  } = useForm<ProfileForm>({
    resolver:
      zodResolver(profileSchema),

    defaultValues:
      profileDefaults(user)
  });

  const {
    control: passwordControl,
    handleSubmit: handlePasswordSubmit,
    reset: resetPassword,
    formState: {
      errors: passwordErrors,
      isSubmitting: passwordSubmitting
    }
  } = useForm<PasswordForm>({
    resolver:
      zodResolver(passwordSchema),

    defaultValues: {
      current_password: '',
      password: '',
      password_confirmation: ''
    }
  });

  useEffect(() => {
    if (!user) return;

    resetProfile(
      profileDefaults(user)
    );
  }, [
    resetProfile,
    user?.id
  ]);

  const saveProfile =
    handleProfileSubmit(
      async (values) => {
        setProfileApiError(null);

        try {
          const updated =
            await api.account.updateProfile({
              first_name:
                values.first_name.trim(),

              last_name:
                nullable(
                  values.last_name
                ),

              phone:
                nullable(
                  values.phone
                ),

              address:
                nullable(
                  values.address
                ),

              city:
                nullable(
                  values.city
                ),

              postal_code:
                nullable(
                  values.postal_code
                )
            });

          replaceBootstrapUser(
            updated
          );
          Keyboard.dismiss();

          feedback.notify({
            tone: 'success',
            title: 'Profil sačuvan',
            message: 'Podaci naloga su uspešno ažurirani.'
          });
        } catch (error) {
          setProfileApiError(
            apiMessage(
              error,
              'Profil nije moguće sačuvati.'
            )
          );
        }
      }
    );

  const changePassword =
    handlePasswordSubmit(
      async (values) => {
        setPasswordApiError(null);

        try {
          const response =
            await api.account.changePassword(
              values
            );
          resetPassword();
          Keyboard.dismiss();

          feedback.notify({
            tone: 'success',
            title: 'Lozinka promenjena',
            message: response.message || 'Prijavi se ponovo novom lozinkom.',
            durationMs: 4200
          });

          await requireReauthentication();
        } catch (error) {
          setPasswordApiError(
            apiMessage(
              error,
              'Lozinku nije moguće promeniti.'
            )
          );
        }
      }
    );

  const logout = () => {
    void (async () => {
      const confirmed = await feedback.confirm({
        tone: 'danger',
        title: 'Odjava',
        message: 'Da li želiš da opozoveš ovu mobilnu sesiju?',
        confirmLabel: 'Odjavi me',
        cancelLabel: 'Ostani prijavljen'
      });

      if (confirmed) {
        await signOut();
      }
    })();
  };

  return (
    <Screen>
      {/* MOBILE_V0_9_ACCOUNT_SECTION_ORDER_BATCH5C */}
      <PageHeader
        title="Nalog"
        eyebrow="Podešavanja"
        name={user?.name}
      />

      <View style={styles.profile}>
        <View style={styles.avatar}>
          <Text style={styles.initials}>
            {initials(user?.name)}
          </Text>
        </View>

        <Text style={styles.name}>
          {user?.name ?? 'Korisnik'}
        </Text>

        <Text style={styles.email}>
          {user?.email ?? user?.username}
        </Text>

        <Pill tone="primary">
          {user?.role?.name ?? 'Korisnik'}
        </Pill>
      </View>

      <Card style={styles.details}>
        <Detail
          label="Korisničko ime"
          value={user?.username}
        />

        <Detail
          label="Telefon"
          value={user?.phone}
        />

        <Detail
          label="Grad"
          value={user?.city}
        />

        <Detail
          label="Grupa"
          value={user?.group?.name}
        />

        <Detail
          label="Backend"
          value={
            bootstrap?.app.backend_version
          }
          last
        />
      </Card>

      <Card style={styles.formCard}>
        <SectionHeading
          icon="account"
          title="Profil"
          copy="Podaci koji se koriste za nalog, isporuku i kontakt."
          iconColor={
            themeColors.primary
          }
          iconBackground={
            themeColors.primarySoft
          }
        />

        <Controller
          control={profileControl}
          name="first_name"
          render={({
            field
          }) => (
            <TextField
              label="Ime"
              value={field.value}
              onBlur={field.onBlur}
              onChangeText={
                field.onChange
              }
              error={
                profileErrors
                  .first_name
                  ?.message
              }
              autoCapitalize="words"
              maxLength={100}
            />
          )}
        />

        <Controller
          control={profileControl}
          name="last_name"
          render={({
            field
          }) => (
            <TextField
              label="Prezime"
              value={field.value}
              onBlur={field.onBlur}
              onChangeText={
                field.onChange
              }
              error={
                profileErrors
                  .last_name
                  ?.message
              }
              autoCapitalize="words"
              maxLength={100}
            />
          )}
        />

        <Controller
          control={profileControl}
          name="phone"
          render={({
            field
          }) => (
            <TextField
              label="Telefon"
              value={field.value}
              onBlur={field.onBlur}
              onChangeText={
                field.onChange
              }
              error={
                profileErrors
                  .phone
                  ?.message
              }
              keyboardType="phone-pad"
              maxLength={40}
            />
          )}
        />

        <Controller
          control={profileControl}
          name="address"
          render={({
            field
          }) => (
            <TextField
              label="Adresa"
              value={field.value}
              onBlur={field.onBlur}
              onChangeText={
                field.onChange
              }
              error={
                profileErrors
                  .address
                  ?.message
              }
              maxLength={255}
            />
          )}
        />

        <Controller
          control={profileControl}
          name="city"
          render={({
            field
          }) => (
            <TextField
              label="Grad"
              value={field.value}
              onBlur={field.onBlur}
              onChangeText={
                field.onChange
              }
              error={
                profileErrors
                  .city
                  ?.message
              }
              autoCapitalize="words"
              maxLength={120}
            />
          )}
        />

        <Controller
          control={profileControl}
          name="postal_code"
          render={({
            field
          }) => (
            <TextField
              label="Poštanski broj"
              value={field.value}
              onBlur={field.onBlur}
              onChangeText={
                field.onChange
              }
              error={
                profileErrors
                  .postal_code
                  ?.message
              }
              maxLength={20}
              returnKeyType="done"
              onSubmitEditing={() =>
                void saveProfile()
              }
            />
          )}
        />

        {profileApiError ? (
          <Text style={styles.formError}>
            {profileApiError}
          </Text>
        ) : null}

        <Button
          onPress={saveProfile}
          loading={profileSubmitting}
          style={styles.fullButton}
        >
          Sačuvaj profil
        </Button>
      </Card>

      <Card style={styles.formCard}>
        <SectionHeading
          icon="lock"
          title="Bezbednost"
          copy="Nova lozinka mora imati najmanje 12 znakova."
          iconColor={
            themeColors.danger
          }
          iconBackground={
            themeColors.dangerSoft
          }
        />

        <View
          style={
            styles.securityNotice
          }
        >
          <Text
            style={
              styles.securityNoticeText
            }
          >
            Nakon uspešne promene
            lozinke svi API tokeni se
            opozivaju i potrebno je da
            se ponovo prijaviš.
          </Text>
        </View>

        <Controller
          control={passwordControl}
          name="current_password"
          render={({
            field
          }) => (
            <TextField
              label="Trenutna lozinka"
              value={field.value}
              onBlur={field.onBlur}
              onChangeText={
                field.onChange
              }
              error={
                passwordErrors
                  .current_password
                  ?.message
              }
              secureTextEntry
              autoCapitalize="none"
              autoCorrect={false}
            />
          )}
        />

        <Controller
          control={passwordControl}
          name="password"
          render={({
            field
          }) => (
            <TextField
              label="Nova lozinka"
              value={field.value}
              onBlur={field.onBlur}
              onChangeText={
                field.onChange
              }
              error={
                passwordErrors
                  .password
                  ?.message
              }
              secureTextEntry
              autoCapitalize="none"
              autoCorrect={false}
            />
          )}
        />

        <Controller
          control={passwordControl}
          name="password_confirmation"
          render={({
            field
          }) => (
            <TextField
              label="Ponovi novu lozinku"
              value={field.value}
              onBlur={field.onBlur}
              onChangeText={
                field.onChange
              }
              error={
                passwordErrors
                  .password_confirmation
                  ?.message
              }
              secureTextEntry
              autoCapitalize="none"
              autoCorrect={false}
              returnKeyType="done"
              onSubmitEditing={() =>
                void changePassword()
              }
            />
          )}
        />

        {passwordApiError ? (
          <Text style={styles.formError}>
            {passwordApiError}
          </Text>
        ) : null}

        <Button
          variant="danger"
          onPress={changePassword}
          loading={passwordSubmitting}
          style={styles.fullButton}
        >
          Promeni lozinku
        </Button>

        <Button
          variant="secondary"
          onPress={() => router.push('/sessions' as Href)}
          style={styles.fullButton}
        >
          Aktivne prijave i sesije
        </Button>
      </Card>

      <Pressable
        accessibilityRole="button"
        onPress={() =>
          router.push(
            '/notification-settings'
          )
        }
      >
        <Card style={styles.rowCard}>
          <View style={styles.rowIcon}>
            <Glyph
              name="bell"
              size={24}
              color={
                themeColors.primary
              }
            />
          </View>

          <View style={styles.rowCopyWrap}>
            <Text style={styles.rowTitle}>
              Obaveštenja
            </Text>

            <Text style={styles.rowCopy}>
              Push registracija, kanali
              i kategorije obaveštenja.
            </Text>
          </View>

          <Glyph
            name="arrow"
            size={28}
            color={themeColors.muted}
          />
        </Card>
      </Pressable>

      <Pressable
        accessibilityRole="button"
        onPress={() =>
          router.push('/devices')
        }
      >
        <Card style={styles.rowCard}>
          <View style={styles.rowIcon}>
            <Glyph
              name="device"
              size={24}
              color={
                themeColors.primary
              }
            />
          </View>

          <View style={styles.rowCopyWrap}>
            <Text style={styles.rowTitle}>
              Prijavljeni uređaji
            </Text>

            <Text style={styles.rowCopy}>
              Pregledaj i opozovi
              mobilne sesije.
            </Text>
          </View>

          <Glyph
            name="arrow"
            size={28}
            color={themeColors.muted}
          />
        </Card>
      </Pressable>

      <Card muted>
        <Text style={styles.version}>
          Ald1n Mobile{' '}
          {Application
            .nativeApplicationVersion
            ?? '0.9.0'}
          {' · build '}
          {Application
            .nativeBuildVersion
            ?? 'dev'}
        </Text>

        <Text style={styles.versionSub}>
          API{' '}
          {bootstrap
            ?.app
            .api_version
            ?? 'v1'}
          {' · Bezbedna mobilna sesija'}
        </Text>
      </Card>

      <Button
        variant="danger"
        onPress={logout}
      >
        Odjava
      </Button>
    </Screen>
  );
}

function SectionHeading({
  icon,
  title,
  copy,
  iconColor,
  iconBackground
}: {
  icon: 'account' | 'lock';
  title: string;
  copy: string;
  iconColor: string;
  iconBackground: string;
}) {
  const styles =
    useThemedStyles(createStyles);

  return (
    <View style={styles.sectionHeading}>
      <View
        style={[
          styles.sectionIcon,
          {
            backgroundColor:
              iconBackground
          }
        ]}
      >
        <Glyph
          name={icon}
          size={22}
          color={iconColor}
        />
      </View>

      <View style={styles.sectionCopyWrap}>
        <Text style={styles.sectionTitle}>
          {title}
        </Text>

        <Text style={styles.sectionCopy}>
          {copy}
        </Text>
      </View>
    </View>
  );
}

function Detail({
  label,
  value,
  last = false
}: {
  label: string;
  value?: string | null;
  last?: boolean;
}) {
  const styles =
    useThemedStyles(createStyles);

  return (
    <View
      style={[
        styles.detail,
        !last
          && styles.detailBorder
      ]}
    >
      <Text style={styles.detailLabel}>
        {label}
      </Text>

      <Text style={styles.detailValue}>
        {value || '—'}
      </Text>
    </View>
  );
}

function createStyles(
  theme: AppColors
) {
  return StyleSheet.create({
    profile: {
      alignItems: 'center',
      gap: spacing.sm,
      paddingVertical: spacing.lg
    },

    avatar: {
      width: 86,
      height: 86,
      borderRadius: 30,
      backgroundColor: theme.primary,
      alignItems: 'center',
      justifyContent: 'center',
      marginBottom: spacing.sm
    },

    initials: {
      fontSize: 29,
      fontWeight: '900',
      color: theme.onPrimary
    },

    name: {
      ...typography.h1,
      color: theme.ink,
      textAlign: 'center'
    },

    email: {
      ...typography.body,
      color: theme.muted,
      textAlign: 'center'
    },

    details: {
      paddingVertical: spacing.sm
    },

    detail: {
      minHeight: 54,
      flexDirection: 'row',
      justifyContent:
        'space-between',
      alignItems: 'center',
      gap: spacing.lg
    },

    detailBorder: {
      borderBottomWidth: 1,
      borderBottomColor: theme.line
    },

    detailLabel: {
      ...typography.small,
      color: theme.muted
    },

    detailValue: {
      ...typography.label,
      color: theme.ink,
      textAlign: 'right',
      flex: 1
    },

    formCard: {
      gap: spacing.lg
    },

    sectionHeading: {
      flexDirection: 'row',
      alignItems: 'flex-start',
      gap: spacing.md
    },

    sectionIcon: {
      width: 46,
      height: 46,
      borderRadius: radii.lg,
      alignItems: 'center',
      justifyContent: 'center'
    },

    sectionCopyWrap: {
      flex: 1
    },

    sectionTitle: {
      ...typography.h3,
      color: theme.ink
    },

    sectionCopy: {
      ...typography.small,
      color: theme.muted,
      marginTop: spacing.xs
    },

    securityNotice: {
      padding: spacing.md,
      borderRadius: radii.lg,
      backgroundColor:
        theme.dangerSoft
    },

    securityNoticeText: {
      ...typography.small,
      color: theme.danger
    },

    formError: {
      ...typography.small,
      color: theme.danger,
      backgroundColor:
        theme.dangerSoft,
      padding: spacing.md,
      borderRadius: radii.lg
    },

    fullButton: {
      width: '100%'
    },

    rowCard: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: spacing.md
    },

    rowIcon: {
      width: 48,
      height: 48,
      borderRadius: radii.lg,
      backgroundColor:
        theme.primarySoft,
      alignItems: 'center',
      justifyContent: 'center'
    },

    rowCopyWrap: {
      flex: 1
    },

    rowTitle: {
      ...typography.h3,
      color: theme.ink
    },

    rowCopy: {
      ...typography.small,
      color: theme.muted,
      marginTop: 3
    },

    version: {
      ...typography.label,
      color: theme.ink
    },

    versionSub: {
      ...typography.small,
      color: theme.muted,
      marginTop: spacing.xs
    }
  });
}
