// MOBILE_GLOBAL_PAGE_HEADER_V06
import { router, usePathname } from 'expo-router';
import { Image, Pressable, StyleSheet, Text, View } from 'react-native';

import { Glyph } from '@/components/ui/glyph';
import { radii, spacing, typography, type AppColors } from '@/constants/theme';
import { initials } from '@/lib/formatters';
import { useAppTheme } from '@/theme/app-theme';

type PageHeaderProps = {
  title: string;
  eyebrow?: string;
  name?: string | null;
  onRefresh?: () => void;
  refreshing?: boolean;
};

const CANONICAL_APP_ICON = require('../../../assets/icon.png');

export function PageHeader({
  title,
  eyebrow,
  name,
  onRefresh,
  refreshing = false,
}: PageHeaderProps) {
  const pathname = usePathname();
  const { colors: theme } = useAppTheme();
  const styles = createStyles(theme);

  return (
    <View style={styles.root}>
      <View style={styles.leading}>
        <Image
          accessibilityIgnoresInvertColors
          source={CANONICAL_APP_ICON}
          resizeMode="contain"
          style={styles.logo}
        />

        <View style={styles.copy}>
          {eyebrow ? <Text style={styles.eyebrow}>{eyebrow}</Text> : null}
          <Text numberOfLines={1} style={styles.title}>{title}</Text>
        </View>
      </View>

      <View style={styles.actions}>
        {onRefresh ? (
          <Pressable
            accessibilityLabel="Osveži"
            accessibilityRole="button"
            accessibilityState={{ disabled: refreshing }}
            disabled={refreshing}
            onPress={onRefresh}
            style={({ pressed }) => [
              styles.iconButton,
              refreshing ? styles.disabled : null,
              pressed ? styles.pressed : null,
            ]}
          >
            <Glyph name="refresh" size={21} color={theme.ink} />
          </Pressable>
        ) : null}

        <Pressable
          accessibilityLabel="Otvori nalog i podešavanja profila"
          accessibilityRole="button"
          accessibilityState={{ selected: pathname === '/account' }}
          onPress={() => {
            if (pathname !== '/account') router.push('/account');
          }}
          style={({ pressed }) => [styles.avatar, pressed ? styles.pressed : null]}
        >
          <Text style={styles.avatarText}>{initials(name)}</Text>
        </Pressable>
      </View>
    </View>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    root: {
      minHeight: 66,
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: spacing.md,
    },
    leading: {
      flex: 1,
      minWidth: 0,
      flexDirection: 'row',
      alignItems: 'center',
      gap: spacing.md,
    },
    logo: {
      width: 42,
      height: 42,
      borderRadius: 12,
      flexShrink: 0,
    },
    copy: {
      flex: 1,
      minWidth: 0,
    },
    eyebrow: {
      ...typography.small,
      color: theme.primary,
      textTransform: 'uppercase',
      letterSpacing: 1.1,
      fontWeight: '800',
    },
    title: {
      ...typography.h2,
      color: theme.ink,
      fontWeight: '800',
    },
    actions: {
      flexShrink: 0,
      flexDirection: 'row',
      alignItems: 'center',
      gap: spacing.xs,
    },
    iconButton: {
      width: 42,
      height: 42,
      borderRadius: radii.lg,
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: theme.surfaceContainer,
    },
    avatar: {
      width: 44,
      height: 44,
      borderRadius: radii.lg,
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: theme.primarySoft,
      borderWidth: StyleSheet.hairlineWidth,
      borderColor: theme.primaryContainer,
    },
    avatarText: {
      ...typography.label,
      color: theme.primaryDark,
      fontWeight: '900',
    },
    disabled: {
      opacity: 0.45,
    },
    pressed: {
      opacity: 0.72,
      transform: [{ scale: 0.98 }],
    },
  });
}
