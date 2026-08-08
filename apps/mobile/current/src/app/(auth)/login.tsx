import { zodResolver } from '@hookform/resolvers/zod';
import { router } from 'expo-router';
import { GoogleSignInButton } from 'react-native-nitro-google-signin';
import { Controller, useForm } from 'react-hook-form';
import { useState } from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, View } from 'react-native';
import { z } from 'zod';
import { BrandMark } from '@/components/ui/brand-mark';
import { Button } from '@/components/ui/button';
import { Glyph } from '@/components/ui/glyph';
import { TextField } from '@/components/ui/text-field';
import { colors, radii, shadow, spacing, typography } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';
import { API_URL, ApiError } from '@/lib/api/client';

const schema = z.object({
  login: z.string().trim().min(1, 'Unesi korisničko ime ili e-mail.'),
  password: z.string().min(1, 'Unesi lozinku.')
});

type FormValues = z.infer<typeof schema>;

export default function LoginScreen() {
  const { signIn, signInWithGoogle } = useAuth();
  const [googleLoading, setGoogleLoading] = useState(false);
  const { control, handleSubmit, setError, clearErrors, formState: { errors, isSubmitting } } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { login: '', password: '' }
  });

  const submit = handleSubmit(async (values) => {
    try {
      await signIn(values.login, values.password);
      router.replace('/home');
    } catch (error) {
      const message = error instanceof ApiError ? error.firstFieldError() ?? error.message : 'Prijava nije uspela.';
      setError('root', { message });
    }
  });

  const google = async () => {
    if (googleLoading) return;
    setGoogleLoading(true);
    clearErrors('root');
    try {
      const outcome = await signInWithGoogle();
      if (outcome.status === 'pending') {
        setError('root', { message: outcome.message });
        return;
      }
      router.replace('/home');
    } catch (error) {
      const message = error instanceof ApiError ? error.firstFieldError() ?? error.message : error instanceof Error ? error.message : 'Google prijava nije uspela.';
      setError('root', { message });
    } finally {
      setGoogleLoading(false);
    }
  };

  return (
    <KeyboardAvoidingView style={styles.page} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
        <View style={styles.hero}>
          <View style={styles.orbOne} /><View style={styles.orbTwo} />
          <BrandMark size={58} inverse />
          <Text style={styles.kicker}>ALD1N MOBILE</Text>
          <Text style={styles.heroTitle}>Sve što ti treba, odmah pri ruci.</Text>
          <Text style={styles.heroCopy}>Katalog, porudžbine i obaveštenja u brzom mobilnom interfejsu.</Text>
        </View>

        <View style={styles.formCard}>
          <View style={styles.formHeading}>
            <View style={styles.headingCopy}><Text style={styles.title}>Dobro došli</Text><Text style={styles.subtitle}>Prijavi se CMS nalogom ili nastavi sa Google nalogom.</Text></View>
            <View style={styles.secure}><Glyph name="lock" size={18} color={colors.success} /></View>
          </View>

          <View style={styles.googleButtonWrap}>
            <GoogleSignInButton
              size="wide"
              colorScheme="light"
              signInBehavior="none"
              loading={googleLoading}
              disabled={googleLoading || isSubmitting}
              onPress={() => void google()}
              style={styles.googleButton}
              accessibilityLabel="Nastavi sa Google nalogom"
            />
          </View>

          <View style={styles.divider}><View style={styles.dividerLine} /><Text style={styles.dividerText}>ILI</Text><View style={styles.dividerLine} /></View>

          <Controller control={control} name="login" render={({ field: { value, onBlur, onChange } }) => (
            <TextField label="Korisničko ime ili e-mail" autoCapitalize="none" autoCorrect={false} value={value} onBlur={onBlur} onChangeText={onChange} error={errors.login?.message} returnKeyType="next" />
          )} />
          <Controller control={control} name="password" render={({ field: { value, onBlur, onChange } }) => (
            <TextField label="Lozinka" secureTextEntry value={value} onBlur={onBlur} onChangeText={onChange} error={errors.password?.message} returnKeyType="done" onSubmitEditing={() => void submit()} />
          )} />
          {errors.root?.message ? <Text style={styles.rootError}>{errors.root.message}</Text> : null}
          <Button onPress={submit} loading={isSubmitting}>Prijavi se</Button>
          <Text style={styles.registrationNote}>Novi Google nalog može automatski da kreira registraciju. Aktivacija i dalje prati pravila CMS-a.</Text>
          <View style={styles.apiLine}><View style={styles.onlineDot} /><Text style={styles.apiText} numberOfLines={1}>{API_URL}</Text></View>
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  page: { flex: 1, backgroundColor: colors.background },
  scroll: { flexGrow: 1, justifyContent: 'center', padding: spacing.lg, gap: spacing.lg },
  hero: { minHeight: 292, overflow: 'hidden', borderRadius: radii.xxl, backgroundColor: colors.hero, padding: spacing.xxl, justifyContent: 'flex-end', ...shadow },
  orbOne: { position: 'absolute', width: 230, height: 230, borderRadius: 115, right: -85, top: -75, backgroundColor: colors.primary, opacity: 0.62 },
  orbTwo: { position: 'absolute', width: 138, height: 138, borderRadius: 69, right: 52, top: 72, backgroundColor: colors.accent, opacity: 0.24 },
  kicker: { ...typography.small, color: colors.accent, letterSpacing: 1.8, fontWeight: '900', marginTop: spacing.xl },
  heroTitle: { ...typography.hero, color: colors.white, marginTop: spacing.sm, maxWidth: 340 },
  heroCopy: { ...typography.body, color: colors.heroMuted, marginTop: spacing.md, maxWidth: 350 },
  formCard: { backgroundColor: colors.surface, borderRadius: radii.xxl, padding: spacing.xl, gap: spacing.lg, ...shadow },
  formHeading: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', gap: spacing.md },
  headingCopy: { flex: 1 },
  title: { ...typography.h1, color: colors.ink },
  subtitle: { ...typography.body, color: colors.muted, marginTop: spacing.xs },
  secure: { width: 44, height: 44, borderRadius: radii.pill, backgroundColor: colors.successSoft, alignItems: 'center', justifyContent: 'center' },
  googleButtonWrap: { width: '100%', alignItems: 'center', justifyContent: 'center' },
  googleButton: { width: '100%', height: 48 },
  divider: { flexDirection: 'row', alignItems: 'center', gap: spacing.md },
  dividerLine: { flex: 1, height: 1, backgroundColor: colors.line },
  dividerText: { ...typography.small, color: colors.muted, letterSpacing: 1.2 },
  rootError: { ...typography.small, color: colors.danger, backgroundColor: colors.dangerSoft, padding: spacing.md, borderRadius: radii.lg },
  registrationNote: { ...typography.small, color: colors.muted, textAlign: 'center', paddingHorizontal: spacing.md },
  apiLine: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: spacing.sm },
  onlineDot: { width: 7, height: 7, borderRadius: 4, backgroundColor: colors.success },
  apiText: { ...typography.small, color: colors.muted, maxWidth: '85%' }
});
