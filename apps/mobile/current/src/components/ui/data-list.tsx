// MOBILE_THEMED_DATA_LIST_ROOT_V06
import type { ReactNode } from 'react';
import {
  FlatList,
  RefreshControl,
  StyleSheet,
  Text,
  View,
  type ListRenderItem,
} from 'react-native';

import { spacing, typography, type AppColors } from '@/constants/theme';
import { useAppTheme } from '@/theme/app-theme';

type Props<T> = {
  data: T[];
  keyExtractor: (item: T, index: number) => string;
  renderItem: (item: T, index: number) => ReactNode;
  emptyTitle?: string;
  emptyMessage?: string;
  header?: ReactNode;
  footer?: ReactNode;
  refreshing?: boolean;
  onRefresh?: () => void;
};

export function DataList<T> ({
  data,
  keyExtractor,
  renderItem,
  emptyTitle = 'Nema podataka',
  emptyMessage = 'Nema stavki za trenutni prikaz.',
  header,
  footer,
  refreshing = false,
  onRefresh,
}: Props<T>) {
  const { colors: theme } = useAppTheme();
  const styles = createStyles(theme);
  const row: ListRenderItem<T> = ({ item, index }) => <>{renderItem(item, index)}</>;

  return (
    <FlatList<T>
      style={styles.list}
      contentContainerStyle={[
        styles.content,
        data.length === 0 ? styles.emptyGrow : null,
      ]}
      data={data}
      ItemSeparatorComponent={() => <View style={styles.separator} />}
      keyExtractor={keyExtractor}
      ListEmptyComponent={(
        <View style={styles.empty}>
          <Text style={styles.emptyTitle}>{emptyTitle}</Text>
          <Text style={styles.emptyMessage}>{emptyMessage}</Text>
        </View>
      )}
      ListFooterComponent={footer ? <View style={styles.footer}>{footer}</View> : null}
      ListHeaderComponent={header ? <View style={styles.header}>{header}</View> : null}
      refreshControl={onRefresh ? (
        <RefreshControl
          refreshing={refreshing}
          onRefresh={onRefresh}
          tintColor={theme.primary}
        />
      ) : undefined}
      renderItem={row}
    />
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    list: {
      flex: 1,
      backgroundColor: theme.background,
    },
    content: {
      paddingHorizontal: spacing.lg,
      paddingBottom: 120,
      backgroundColor: theme.background,
    },
    emptyGrow: {
      flexGrow: 1,
    },
    separator: {
      height: spacing.md,
    },
    header: {
      marginBottom: spacing.lg,
    },
    footer: {
      marginTop: spacing.lg,
    },
    empty: {
      flex: 1,
      minHeight: 220,
      alignItems: 'center',
      justifyContent: 'center',
      gap: spacing.sm,
      paddingHorizontal: spacing.xl,
    },
    emptyTitle: {
      ...typography.h3,
      color: theme.ink,
      textAlign: 'center',
    },
    emptyMessage: {
      ...typography.body,
      color: theme.muted,
      textAlign: 'center',
    },
  });
}
