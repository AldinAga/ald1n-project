// MOBILE_GLOBAL_PAGE_HEADER_V06
// MOBILE_V1_0_HEADER_NOTIFICATIONS_BATCH50_V2
import { router, usePathname } from 'expo-router';
import { Image, Pressable, StyleSheet, Text, View } from 'react-native';

import { Glyph } from '@/components/ui/glyph';
import { radii, spacing, typography, type AppColors } from '@/constants/theme';
import { useAuth, useNotificationUnread } from '@/features/auth/auth-provider';
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
  onRefresh,
  refreshing = false,
}: PageHeaderProps) {
  const pathname = usePathname();
  const { colors: theme } = useAppTheme();
  const { hasFeature } = useAuth();
  const styles = createStyles(theme);
  const notificationsVisible = hasFeature('notifications');

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

        {notificationsVisible ? <PageHeaderNotificationButton pathname={pathname} /> : null}
      </View>
    </View>
  );
}

function PageHeaderNotificationButton({ pathname }: { pathname: string }) {
  const unread = useNotificationUnread();
  const { colors: theme } = useAppTheme();
  const styles = createStyles(theme);

  return (
    <Pressable
      accessibilityLabel={unread > 0 ? 'Obaveštenja, ' + String(unread) + ' nepročitanih' : 'Obaveštenja'}
      accessibilityRole="button"
      accessibilityState={{ selected: pathname === '/notifications' }}
      onPress={() => {
        if (pathname !== '/notifications') router.push('/notifications');
      }}
      style={({ pressed }) => [
        styles.notificationButton,
        pathname === '/notifications' ? styles.notificationButtonActive : null,
        pressed ? styles.pressed : null,
      ]}
    >
      <Glyph name="bell" size={23} color={pathname === '/notifications' ? theme.onPrimaryContainer : theme.ink} />
      {unread > 0 ? (
        <View style={styles.badge}>
          <Text style={styles.badgeText}>{unread > 99 ? '99+' : String(unread)}</Text>
        </View>
      ) : null}
    </Pressable>
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
    logo: { width: 42, height: 42, borderRadius: 12, flexShrink: 0 },
    copy: { flex: 1, minWidth: 0 },
    eyebrow: {
      ...typography.small,
      color: theme.primary,
      textTransform: 'uppercase',
      letterSpacing: 1.1,
      fontWeight: '800',
    },
    title: { ...typography.h2, color: theme.ink, fontWeight: '800' },
    actions: { flexShrink: 0, flexDirection: 'row', alignItems: 'center', gap: spacing.xs },
    iconButton: {
      width: 42,
      height: 42,
      borderRadius: radii.lg,
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: theme.surfaceContainer,
    },
    notificationButton: {
      width: 46,
      height: 46,
      borderRadius: radii.pill,
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: theme.surfaceContainer,
      borderWidth: StyleSheet.hairlineWidth,
      borderColor: theme.line,
    },
    notificationButtonActive: {
      backgroundColor: theme.primaryContainer,
      borderColor: theme.primary,
    },
    badge: {
      position: 'absolute',
      right: -1,
      top: -2,
      minWidth: 18,
      height: 18,
      paddingHorizontal: 4,
      borderRadius: radii.pill,
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: theme.danger,
      borderWidth: 2,
      borderColor: theme.background,
    },
    badgeText: { color: theme.onDanger, fontSize: 9, lineHeight: 10, fontWeight: '900' },
    disabled: { opacity: 0.45 },
    pressed: { opacity: 0.72, transform: [{ scale: 0.98 }] },
  });
}
