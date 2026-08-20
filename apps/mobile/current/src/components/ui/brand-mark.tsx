import {
  StyleSheet,
  Text,
  View,
} from 'react-native';

import {
  radii,
  type AppColors,
} from '@/constants/theme';
import { useThemedStyles } from '@/theme/app-theme';

export function BrandMark({
  size = 46,
  inverse = false,
}: {
  size?: number;
  inverse?: boolean;
}) {
  const styles = useThemedStyles(createStyles);

  return (
    <View
      style={[
        styles.mark,
        {
          width: size,
          height: size,
          borderRadius: Math.round(size * 0.3),
        },
        inverse && styles.inverse,
      ]}
    >
      <Text
        style={[
          styles.letter,
          {
            fontSize: Math.round(size * 0.56),
          },
          inverse && styles.inverseLetter,
        ]}
      >
        A
      </Text>

      <View
        style={[
          styles.dot,
          {
            width: Math.max(6, size * 0.16),
            height: Math.max(6, size * 0.16),
          },
        ]}
      />
    </View>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    mark: {
      backgroundColor: theme.primary,
      alignItems: 'center',
      justifyContent: 'center',
      overflow: 'hidden',
    },

    inverse: {
      backgroundColor: theme.white,
    },

    letter: {
      color: theme.onPrimary,
      fontWeight: '900',
      lineHeight: 42,
      letterSpacing: -2,
    },

    inverseLetter: {
      color: theme.hero,
    },

    dot: {
      position: 'absolute',
      right: 5,
      top: 5,
      borderRadius: radii.pill,
      backgroundColor: theme.accent,
    },
  });
}
