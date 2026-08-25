import { useMemo, useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { StyleSheet, Text } from 'react-native';

import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ConfirmAction } from '@/components/ui/confirm-action';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { apiAdminOrders } from '@/features/admin/orders-admin-api';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import { useAppTheme } from '@/theme/app-theme';

export function AdminOrderArchiveActions({
  orderId,
  orderNumber,
  canArchive,
  onArchived,
}: {
  orderId: number;
  orderNumber: string;
  canArchive: boolean;
  onArchived: () => void;
}) {
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const [reason, setReason] = useState('');
  const [confirmVisible, setConfirmVisible] = useState(false);

  const mutation = useMutation({
    mutationFn: () => apiAdminOrders.archive(orderId, reason.trim()),
    onSuccess: async () => {
      setConfirmVisible(false);
      await client.invalidateQueries({ queryKey: adminQueryKeys.adminOrdersRoot() });
      feedback.notify({
        tone: 'success',
        title: 'Porudžbina je arhivirana',
        message: `${orderNumber} je premeštena u arhivu.`,
      });
      onArchived();
    },
    onError: (error) => {
      setConfirmVisible(false);
      feedback.notify({
        tone: 'danger',
        title: 'Arhiviranje nije uspelo',
        message: error instanceof Error ? error.message : 'Pokušaj ponovo.',
      });
    },
  });

  if (!canArchive) return null;

  return (
    <Card style={styles.card}>
      <Text style={styles.title}>Arhiviranje porudžbine</Text>
      <Text style={styles.copy}>
        Arhivirati se može samo završena ili otkazana porudžbina bez aktivnog postprodajnog slučaja.
      </Text>
      <TextField
        label="Razlog arhiviranja"
        value={reason}
        onChangeText={setReason}
        placeholder="Najmanje 3 karaktera"
        multiline
      />
      <Button
        variant="secondary"
        disabled={mutation.isPending || reason.trim().length < 3}
        onPress={() => setConfirmVisible(true)}
      >
        Arhiviraj porudžbinu
      </Button>
      <ConfirmAction
        visible={confirmVisible}
        title="Arhiviraj porudžbinu?"
        message={`${orderNumber} će biti uklonjena iz aktivnog operativnog prikaza i premeštena u arhivu.`}
        confirmLabel="Arhiviraj"
        destructive
        busy={mutation.isPending}
        onConfirm={() => mutation.mutate()}
        onCancel={() => setConfirmVisible(false)}
      />
    </Card>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    card: { gap: spacing.md },
    title: { ...typography.h3, color: theme.ink },
    copy: { ...typography.body, color: theme.muted },
  });
}
