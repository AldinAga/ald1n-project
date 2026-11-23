import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { TextField } from '@/components/ui/text-field';
import { radii, spacing, typography, type AppColors } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';
import { getGoogleIdToken } from '@/features/auth/google-auth';
import { ApiError } from '@/lib/api/client';
import { api } from '@/lib/api/endpoints';
import { useThemedStyles } from '@/theme/app-theme';

function apiMessage(error: unknown, fallback: string): string {
  if (error instanceof ApiError) return error.firstFieldError() ?? error.message;
  if (error instanceof Error && error.message) return error.message;
  return fallback;
}

export default function AccountDeleteScreen() {
  const styles = useThemedStyles(createStyles);
  const feedback = useAppFeedback();
  const { bootstrap, requireReauthentication } = useAuth();
  const [password, setPassword] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const submit = async (input: { password?: string; id_token?: string }) => {
    if (busy) return;
    setBusy(true);
    setError(null);
    try {
      const response = await api.account.requestDeletion(input);
      feedback.notify({
        tone: 'success',
        title: 'Brisanje zakazano',
        message: response.message,
        durationMs: 6000,
      });
      await requireReauthentication();
    } catch (failure) {
      setError(apiMessage(failure, 'Brisanje naloga nije moguće zakazati.'));
    } finally {
      setBusy(false);
    }
  };

  const withPassword = () => {
    if (!password) {
      setError('Unesi lozinku da potvrdiš brisanje.');
      return;
    }
    void submit({ password });
  };

  const withGoogle = async () => {
    if (busy) return;
    setError(null);
    try {
      const idToken = await getGoogleIdToken();
      await submit({ id_token: idToken });
    } catch (failure) {
      setError(apiMessage(failure, 'Google potvrda nije uspela.'));
    }
  };

  return (
    <Screen keyboardDismissMode="on-drag">
      <Pressable accessibilityRole="button" onPress={() => router.back()}>
        <Text style={styles.back}>‹ Bezbednost</Text>
      </Pressable>
      <PageHeader title="Brisanje naloga" eyebrow="Nalog" name={bootstrap?.user.name} />

      <Card style={styles.card}>
        <View style={styles.notice}>
          <Text style={styles.noticeText}>
            Nalog se odmah blokira i odjavljuje sa svih uređaja. Konačno brisanje sledi posle 14 dana. U tom roku zahtev poništavaš linkom iz e-pošte koju ćemo ti poslati.
          </Text>
        </View>
        <Text style={styles.body}>
          Brišemo ime, e-poštu, telefon, adresu, vezu sa Google nalogom, push token i oznake uređaja. Porudžbine, provizije, garancije i poslovna istorija ostaju bez veze sa tvojim imenom.
        </Text>
        <Text style={styles.body}>
          Ako nalog ima otvorene provizije ili zaduženja, brisanje čeka dok se ne zatvore. Administratorski nalog može da se obriše samo kada postoji drugi aktivni SuperAdministrator.
        </Text>

        <TextField
          label="Lozinka"
          value={password}
          onChangeText={setPassword}
          secureTextEntry
          autoCapitalize="none"
          autoCorrect={false}
          returnKeyType="done"
          onSubmitEditing={withPassword}
        />
        {error ? <Text style={styles.error}>{error}</Text> : null}
        <Button variant="danger" onPress={withPassword} loading={busy}>
          Obriši nalog
        </Button>
        <Button variant="secondary" onPress={() => void withGoogle()} disabled={busy}>
          Potvrdi Google nalogom
        </Button>
      </Card>
    </Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    card: { gap: spacing.md },
    notice: { padding: spacing.md, borderRadius: radii.lg, backgroundColor: theme.dangerSoft },
    noticeText: { ...typography.small, color: theme.danger },
    body: { ...typography.small, color: theme.ink },
    error: { ...typography.small, color: theme.danger },
  });
}
