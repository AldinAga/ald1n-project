import { router } from 'expo-router';
import { Screen } from '@/components/layout/screen';
import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/ui/states';

export default function NotFound() {
  return <Screen contentStyle={{ justifyContent: 'center' }}><EmptyState title="Stranica ne postoji" message="Putanja nije deo trenutne mobilne aplikacije." /><Button onPress={() => router.replace('/home')}>Nazad na početnu</Button></Screen>;
}
