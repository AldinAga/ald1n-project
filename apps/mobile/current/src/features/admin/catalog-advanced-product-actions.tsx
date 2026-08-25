import { useMutation, useQueryClient } from '@tanstack/react-query';
import { router, type Href } from 'expo-router';
import { Text } from 'react-native';

import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { spacing, typography } from '@/constants/theme';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import { apiCatalogAdvanced } from '@/features/admin/catalog-advanced-admin-api';
import { ApiError } from '@/lib/api/client';

function errorMessage(error: unknown): string {
  if (error instanceof ApiError) return error.firstFieldError() ?? error.message;
  return error instanceof Error ? error.message : 'Operacija nije uspela.';
}

// MOBILE_V1_0_CATALOG_ADVANCED_PARITY_BATCH35
export function CatalogAdvancedProductActions({
  product,
  onChanged,
}: {
  product: { id: number; sku: string; is_archived: boolean };
  onChanged: () => Promise<void>;
}) {
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const regenerateMutation = useMutation({
    mutationFn: () => apiCatalogAdvanced.regenerateName(product.id),
    onSuccess: async (response) => {
      await onChanged();
      await client.invalidateQueries({ queryKey: adminQueryKeys.catalogAdvancedProduct(product.id) });
      feedback.notify({ tone: 'success', title: 'Naziv je regenerisan', message: response.data.name });
    },
    onError: (error) => feedback.notify({ tone: 'danger', title: 'Regenerisanje naziva nije uspelo', message: errorMessage(error) }),
  });

  if (product.is_archived) return null;

  return (
    <Card style={{ gap: spacing.md }}>
      <Text style={{ ...typography.h3 }}>Napredne akcije artikla</Text>
      <Text style={{ ...typography.body }}>
        Kloniranje koristi novi SKU, status Nacrt i lager 0. Regenerisanje naziva koristi canonical šablon tipa proizvoda.
      </Text>
      <Button variant="secondary" onPress={() => router.push({ pathname: '/admin/catalog/[id]/clone', params: { id: String(product.id) } } as Href)}>
        Kloniraj artikal
      </Button>
      <Button variant="ghost" loading={regenerateMutation.isPending} onPress={() => regenerateMutation.mutate()}>
        Regeneriši naziv iz šablona
      </Button>
    </Card>
  );
}
