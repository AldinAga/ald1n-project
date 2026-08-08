import type { PropsWithChildren } from 'react';
import { ScrollView, StyleSheet, View, type ScrollViewProps, type StyleProp, type ViewStyle } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { colors, spacing } from '@/constants/theme';

export function Screen({ children, scroll = true, contentStyle, ...props }:
  PropsWithChildren<{ scroll?: boolean; contentStyle?: StyleProp<ViewStyle> } & ScrollViewProps>) {
  if (!scroll) {
    return <SafeAreaView style={styles.safe} edges={['top']}><View style={[styles.content, styles.flex, contentStyle]}>{children}</View></SafeAreaView>;
  }
  return (
    <SafeAreaView style={styles.safe} edges={['top']}>
      <ScrollView
        contentContainerStyle={[styles.content, contentStyle]}
        keyboardShouldPersistTaps="handled"
        showsVerticalScrollIndicator={false}
        {...props}
      >
        {children}
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.background },
  content: { paddingHorizontal: spacing.lg, paddingBottom: 120, gap: spacing.lg },
  flex: { flex: 1 }
});
