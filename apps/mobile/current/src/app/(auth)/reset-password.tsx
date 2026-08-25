import { zodResolver } from '@hookform/resolvers/zod';
import { router, type Href, useLocalSearchParams } from 'expo-router';
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

const password = z.string().min(12, 'Lozinka mora imati najmanje 12 znakova.').regex(/[a-z]/, 'Dodaj malo slovo.').regex(/[A-Z]/, 'Dodaj veliko slovo.').regex(/[0-9]/, 'Dodaj broj.');
const schema = z.object({ token: z.string().min(1, 'Unesi aktivni reset link ili token.'), password, password_confirmation: z.string().min(1, 'Ponovi lozinku.') }).refine((v) => v.password === v.password_confirmation, { path: ['password_confirmation'], message: 'Lozinke se ne poklapaju.' });
type FormValues = z.infer<typeof schema>;

function extractToken(value: string | string[] | undefined): string {
  const text = Array.isArray(value) ? value[0] ?? '' : value ?? '';
  return text.match(/[A-Za-z0-9]{80}/)?.[0] ?? text;
}

export default function ResetPasswordScreen() {
  const params = useLocalSearchParams<{ token?: string | string[] }> ();
  const styles = useThemedStyles(createStyles);
  const [status, setStatus] = useState<string | null> (null);
  const { control, handleSubmit, setError, formState: { errors, isSubmitting } } = useForm<FormValues> ({
    resolver: zodResolver(schema),
    defaultValues: { token: extractToken(params.token), password: '', password_confirmation: '' }
  });

  const submit = handleSubmit(async (values) => {
    const token = extractToken(values.token.trim());
    if (!/^[A-Za-z0-9]{80}$/.test(token)) {
      setError('token', { message: 'Reset link ili token nije validan.' });
      return;
    }
    try {
      const response = await api.auth.resetPassword({ token, password: values.password, password_confirmation: values.password_confirmation });
      setStatus(response.message);
    } catch (error) {
      const message = error instanceof ApiError ? error.firstFieldError() ?? error.message : 'Lozinku trenutno nije moguće promeniti.';
      setError('root', { message });
    }
  });

  return <KeyboardAvoidingView style={styles.page} behavior={Platform.OS === 'ios' ? 'padding' : 'height'}>
    <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
      <View style={styles.card}>
        <Text style={styles.kicker}>RESET LOZINKE</Text>
        <Text style={styles.title}>Postavi novu lozinku</Text>
        <Text style={styles.copy}>Aplikacija prihvata i ceo CMS reset link: token će biti izdvojen lokalno pre slanja.</Text>
        <Controller control={control} name="token" render={({ field }) => <TextField label="Reset link ili token" autoCapitalize="none" autoCorrect={false} value={field.value} onBlur={field.onBlur} onChangeText={field.onChange} error={errors.token?.message} />} />
        <Controller control={control} name="password" render={({ field }) => <TextField label="Nova lozinka" secureTextEntry autoCapitalize="none" autoCorrect={false} value={field.value} onBlur={field.onBlur} onChangeText={field.onChange} error={errors.password?.message} />} />
        <Controller control={control} name="password_confirmation" render={({ field }) => <TextField label="Ponovi novu lozinku" secureTextEntry autoCapitalize="none" autoCorrect={false} value={field.value} onBlur={field.onBlur} onChangeText={field.onChange} error={errors.password_confirmation?.message} returnKeyType="done" onSubmitEditing={() => void submit()} />} />
        {errors.root?.message ? <Text style={styles.error}>{errors.root.message}</Text> : null}
        {status ? <Text style={styles.success}>{status}</Text> : null}
        <Button onPress={submit} loading={isSubmitting}>Promeni lozinku</Button>
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