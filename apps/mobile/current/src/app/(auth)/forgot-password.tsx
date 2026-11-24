import { zodResolver } from '@hookform/resolvers/zod';
import { router, type Href } from 'expo-router';
import { useState } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { z } from 'zod';
import { AuthNotice, AuthShell } from '@/components/auth/auth-shell';
import { Button } from '@/components/ui/button';
import { TextField } from '@/components/ui/text-field';
import { ApiError } from '@/lib/api/client';
import { api } from '@/lib/api/endpoints';

const schema = z.object({ email: z.string().trim().email('Unesi ispravnu e-mail adresu.').max(190) });
type FormValues = z.infer<typeof schema>;

export default function ForgotPasswordScreen() {
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

  return <AuthShell kicker="BEZBEDNOST NALOGA" title="Zaboravljena lozinka" copy="Unesi e-mail naloga. Odgovor je uvek isti i ne otkriva da li nalog postoji.">
        <Controller control={control} name="email" render={({ field }) => <TextField label="E-mail" autoCapitalize="none" autoCorrect={false} keyboardType="email-address" value={field.value} onBlur={field.onBlur} onChangeText={field.onChange} error={errors.email?.message} returnKeyType="done" onSubmitEditing={() => void submit()} />} />
        {errors.root?.message ? <AuthNotice tone="danger">{errors.root.message}</AuthNotice> : null}
        {status ? <AuthNotice tone="success">{status}</AuthNotice> : null}
        <Button onPress={() => void submit()} loading={isSubmitting}>Pošalji link za reset</Button>
        <Button variant="ghost" onPress={() => router.replace('/login' as Href)}>Nazad na prijavu</Button>
  </AuthShell>;
}