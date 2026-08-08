import { Redirect } from 'expo-router';
import { LoadingState } from '@/components/ui/states';
import { useAuth } from '@/features/auth/auth-provider';

export default function Index() {
  const { status } = useAuth();
  if (status === 'hydrating') return <LoadingState label="Pokretanje Ald1n Mobile…" />;
  return <Redirect href={status === 'authenticated' ? '/home' : '/login'} />;
}
