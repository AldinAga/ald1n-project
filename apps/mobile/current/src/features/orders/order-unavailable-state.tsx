import { router } from 'expo-router';

import { Screen } from '@/components/layout/screen';
import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/ui/states';
import { ApiError } from '@/lib/api/client';

export function isOrderUnavailable(error: unknown): boolean {
  return error instanceof ApiError && error.status === 404;
}

export function OrderUnavailableState({
  audience,
}: {
  audience: 'admin' | 'user';
}) {
  const listRoute = audience === 'admin' ? '/admin/orders' : '/orders';

  return (
    <Screen contentStyle={{ justifyContent: 'center' }}>
      <EmptyState
        title="Porudžbina nije dostupna"
        message="Detalji ove porudžbine ne mogu da se otvore. Možda je arhivirana, uklonjena ili više nemate pristup."
      />
      <Button
        variant="secondary"
        onPress={() => router.replace(listRoute)}
      >
        Nazad na listu porudžbina
      </Button>
    </Screen>
  );
}
