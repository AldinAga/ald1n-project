import { zodResolver } from '@hookform/resolvers/zod';
import { router, type Href, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { z } from 'zod';
import { AuthNotice, AuthShell } from '@/components/auth/auth-shell';
import { Button } from '@/components/ui/button';
import { TextField } from '@/components/ui/text-field';
import { ApiError } from '@/lib/api/client';
import { api } from '@/lib/api/endpoints';

const password = z.string().min(12, 'Lozinka mora imati najmanje 12 znakova.').regex(/[a-z]/, 'Dodaj malo slovo.').regex(/[A-Z]/, 'Dodaj veliko slovo.').regex(/[0-9]/, 'Dodaj broj.');
const schema = z.object({ token: z.string().min(1, 'Unesi aktivni reset link ili token.'), password, password_confirmation: z.string().min(1, 'Ponovi lozinku.') }).refine((v) => v.password === v.password_confirmation, { path: ['password_confirmation'], message: 'Lozinke se ne poklapaju.' });
type FormValues = z.infer<typeof schema>;

function extractToken(value: string | string[] | undefined): string {
  const text = Array.isArray(value) ? value[0] ?? '' : value ?? '';
  return text.match(/[A-Za-z0-9]{80}/)?.[0] ?? text;
}

export default function ResetPasswordScreen() {
  const params = useLocalSearchParams<{ token?: string | string[] }> ();
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

  return <AuthShell kicker="RESET LOZINKE" title="Postavi novu lozinku" copy="Aplikacija prihvata i ceo CMS reset link: token će biti izdvojen lokalno pre slanja.">
        <Controller control={control} name="token" render={({ field }) => <TextField label="Reset link ili token" autoCapitalize="none" autoCorrect={false} value={field.value} onBlur={field.onBlur} onChangeText={field.onChange} error={errors.token?.message} />} />
        <Controller control={control} name="password" render={({ field }) => <TextField label="Nova lozinka" secureTextEntry autoCapitalize="none" autoCorrect={false} value={field.value} onBlur={field.onBlur} onChangeText={field.onChange} error={errors.password?.message} />} />
        <Controller control={control} name="password_confirmation" render={({ field }) => <TextField label="Ponovi novu lozinku" secureTextEntry autoCapitalize="none" autoCorrect={false} value={field.value} onBlur={field.onBlur} onChangeText={field.onChange} error={errors.password_confirmation?.message} returnKeyType="done" onSubmitEditing={() => void submit()} />} />
        {errors.root?.message ? <AuthNotice tone="danger">{errors.root.message}</AuthNotice> : null}
        {status ? <AuthNotice tone="success">{status}</AuthNotice> : null}
        <Button onPress={() => void submit()} loading={isSubmitting}>Promeni lozinku</Button>
        <Button variant="ghost" onPress={() => router.replace('/login' as Href)}>Nazad na prijavu</Button>
  </AuthShell>;
}