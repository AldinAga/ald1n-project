import { useMemo } from 'react';
import {
  ActivityIndicator,
  StyleSheet,
  Text,
  View,
} from 'react-native';

import { Button } from '@/components/ui/button';
import { Glyph } from '@/components/ui/glyph';
import {
  spacing,
  typography,
  type AppColors,
} from '@/constants/theme';
import { ApiError } from '@/lib/api/client';
import { useAppTheme } from '@/theme/app-theme';

function useStateTheme() {
  const { colors: themeColors } = useAppTheme();

  const styles = useMemo(
    () => createStyles(themeColors),
    [themeColors],
  );

  return {
    themeColors,
    styles,
  };
}

export function LoadingState({
  label = 'Učitavanje…',
}: {
  label?: string;
}) {
  const { themeColors, styles } = useStateTheme();

  return (
    <View style={styles.center}>
      <ActivityIndicator
        size="large"
        color={themeColors.primary}
      />

      <Text style={styles.muted}>
        {label}
      </Text>
    </View>
  );
}

export function EmptyState({
  title,
  message,
}: {
  title: string;
  message: string;
}) {
  const { themeColors, styles } = useStateTheme();

  return (
    <View style={styles.center}>
      <View style={styles.icon}>
        <Glyph
          name="box"
          size={28}
          color={themeColors.primary}
        />
      </View>

      <Text style={styles.title}>
        {title}
      </Text>

      <Text style={styles.muted}>
        {message}
      </Text>
    </View>
  );
}

export function UnavailableState({
  title = 'Funkcija nije dostupna',
  message =
    'Tvoj nalog nema pristup ovoj mobilnoj funkciji.',
}: {
  title?: string;
  message?: string;
}) {
  const { themeColors, styles } = useStateTheme();

  return (
    <View style={styles.center}>
      <View style={styles.icon}>
        <Glyph
          name="lock"
          size={28}
          color={themeColors.primary}
        />
      </View>

      <Text style={styles.title}>
        {title}
      </Text>

      <Text style={styles.muted}>
        {message}
      </Text>
    </View>
  );
}

export function ErrorState({
  error,
  onRetry,
}: {
  error: unknown;
  onRetry?: () => void;
}) {
  const { themeColors, styles } = useStateTheme();

  const apiError =
    error instanceof ApiError
      ? error
      : null;

  const message =
    apiError?.firstFieldError() ??
    (
      error instanceof Error
        ? error.message
        : 'Došlo je do neočekivane greške.'
    );

  return (
    <View style={styles.center}>
      <View
        style={[
          styles.icon,
          styles.errorIcon,
        ]}
      >
        <Glyph
          name="close"
          size={28}
          color={themeColors.danger}
        />
      </View>

      <Text style={styles.title}>
        Nešto nije u redu
      </Text>

      <Text style={styles.muted}>
        {message}
      </Text>

      {apiError?.requestId ? (
        <Text style={styles.request}>
          ID zahteva: {apiError.requestId}
        </Text>
      ) : null}

      {onRetry ? (
        <Button
          variant="secondary"
          onPress={onRetry}
        >
          Pokušaj ponovo
        </Button>
      ) : null}
    </View>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    center: {
      padding: spacing.xxxl,
      alignItems: 'center',
      justifyContent: 'center',
      gap: spacing.md,
    },

    icon: {
      width: 58,
      height: 58,
      borderRadius: 20,
      backgroundColor: theme.primarySoft,
      alignItems: 'center',
      justifyContent: 'center',
    },

    errorIcon: {
      backgroundColor: theme.dangerSoft,
    },

    title: {
      ...typography.h3,
      color: theme.ink,
      textAlign: 'center',
    },

    muted: {
      ...typography.body,
      color: theme.muted,
      textAlign: 'center',
    },

    request: {
      ...typography.small,
      color: theme.muted,
      textAlign: 'center',
    },
  });
}
