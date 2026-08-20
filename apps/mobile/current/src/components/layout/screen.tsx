import type { PropsWithChildren } from 'react';
import {
  ScrollView,
  StyleSheet,
  View,
  type ScrollViewProps,
  type StyleProp,
  type ViewStyle,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { spacing } from '@/constants/theme';
import { useAppTheme } from '@/theme/app-theme';

type ScreenProps = PropsWithChildren<{
  scroll?: boolean;
  contentStyle?: StyleProp<ViewStyle>;
} & ScrollViewProps>;

export function Screen({
  children,
  scroll = true,
  contentStyle,
  ...props
}: ScreenProps) {
  const { colors: themeColors } = useAppTheme();

  const safeStyle = [
    styles.safe,
    {
      backgroundColor: themeColors.background,
    },
  ];

  if (!scroll) {
    return (
      <SafeAreaView
        style={safeStyle}
        edges={['top']}
      >
        <View
          style={[
            styles.content,
            styles.nonScrollContent,
            contentStyle,
          ]}
        >
          {children}
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView
      style={safeStyle}
      edges={['top']}
    >
      <ScrollView
        contentContainerStyle={[
          styles.content,
          contentStyle,
        ]}
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
  safe: {
    flex: 1,
  },

  content: {
    paddingHorizontal: spacing.lg,
    paddingBottom: 120,
    gap: spacing.lg,
  },

  nonScrollContent: {
    flex: 1,
  },
});
