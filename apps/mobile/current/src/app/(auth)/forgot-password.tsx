import { zodResolver } from '@hookform/resolvers/zod';
import { router, type Href } from 'expo-router';
import { useState } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, View } from 'react-native';
import { z } from 'zod';
import { Button } from '@/components/ui/button';
import { TextField } from '@/components/ui/text-field';
import { radii, shadow, spacing, typography, type AppColors } from '@/constants/theme';
import { ApiError } from '@/lib/api/client';
import { api } from '@/lib/api/endpoints';
import { useThemedStyles } from '@/theme/app-theme';

const schema = z.object({ email: z.string().trim().email('Unesi ispravnu e-mail adresu.').max(190) });
type FormValues = z.infer<typeof schema>;

export default function ForgotPasswordScreen() {
  const styles = useThemedStyles(createStyles);
  const [status, setStatus] = useState<string | null> (null);
  const { control, handleSubmit, setError, formState: { errors, isSubmitting } } = useForm<FormValues> ({
    resolver: zodResolver(schema),
    defaultValues: { email: '' }
  });

  const submit = handleSubmit(async (values) => {
    setStatus(null);
    try {
      const response = await api.auth.requestPasswordReset({ email: values.email.trim() });
      setStatus(response.message);
    } catch (error) {
      const message = error instanceof ApiError ? error.firstFieldError() ?? error.message : 'Zahtev trenutno nije moguće poslati.';
      setError('root', { message });
    }
  });

  return <KeyboardAvoidingView style={styles.page} behavior={Platform.OS === 'ios' ? 'padding' : 'height'}>
    <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
      <View style={styles.card}>
        <Text style={styles.kicker}>BEZBEDNOST NALOGA</Text>
        <Text style={styles.title}>Zaboravljena lozinka</Text>
        <Text style={styles.copy}>Unesi e-mail naloga. Odgovor je uvek isti i ne otkriva da li nalog postoji.</Text>
        <Controller control={control} name="email" render={({ field }) => <TextField label="E-mail" autoCapitalize="none" autoCorrect={false} keyboardType="email-address" value={field.value} onBlur={field.onBlur} onChangeText={field.onChange} error={errors.email?.message} returnKeyType="done" onSubmitEditing={() => void submit()} />} />
        {errors.root?.message ? <Text style={styles.error}>{errors.root.message}</Text> : null}
        {status ? <Text style={styles.success}>{status}</Text> : null}
        <Button onPress={submit} loading={isSubmitting}>Pošalji link za reset</Button>
        <Button variant="ghost" onPress={() => router.replace('/login' as Href)}>Nazad na prijavu</Button>
      </View>
    </ScrollView>
  </KeyboardAvoidingView>;
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    page: { flex: 1, backgroundColor: theme.background },
    scroll: { flexGrow: 1, justifyContent: 'center', padding: spacing.lg },
    card: { backgroundColor: theme.surface, borderRadius: radii.xxl, padding: spacing.xl, gap: spacing.lg, ...shadow },
    kicker: { ...typography.small, color: theme.primary, fontWeight: '900', letterSpacing: 1.4 },
    title: { ...typography.h1, color: theme.ink },
    copy: { ...typography.body, color: theme.muted },
    error: { ...typography.small, color: theme.danger, backgroundColor: theme.dangerSoft, padding: spacing.md, borderRadius: radii.lg },
    success: { ...typography.small, color: theme.success, backgroundColor: theme.successSoft, padding: spacing.md, borderRadius: radii.lg }
  });
}