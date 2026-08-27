// MOBILE_GLOBAL_BOTTOM_NAV_V06
// MOBILE_V1_0_BOTTOM_TAB_ACTIVE_STATE_POLISH_BATCH48
import { useEffect, useMemo, useState } from 'react';
import { router, usePathname } from 'expo-router';
import { Keyboard, Pressable, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { Glyph, type GlyphName } from '@/components/ui/glyph';
import { radii, spacing, typography, type AppColors } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';
import { useAppTheme } from '@/theme/app-theme';

type MainRoute = '/home' | '/catalog' | '/orders' | '/notifications' | '/account';
type NavKey = 'home' | 'catalog' | 'orders' | 'notifications' | 'account';

type NavItem = {
  key: NavKey;
  label: string;
  route: MainRoute;
  glyph: GlyphName;
  visible: boolean;
  badge?: number;
};

function activeKey(pathname: string): NavKey | null {
  if (pathname === '/home' || pathname === '/') return 'home';

  if (
    pathname === '/catalog'
    || pathname.startsWith('/product/')
    || pathname === '/cart'
    || pathname === '/checkout'
    || pathname.startsWith('/admin/catalog/')
  ) {
    return 'catalog';
  }

  if (
    pathname === '/orders'
    || pathname.startsWith('/order/')
    || pathname.startsWith('/assigned-orders')
    || pathname.startsWith('/after-sales')
    || pathname.startsWith('/warranties')
    || pathname.startsWith('/commissions')
  ) {
    return 'orders';
  }

  if (pathname === '/notifications') return 'notifications';

  if (
    pathname === '/account'
    || pathname === '/devices'
    || pathname === '/notification-settings'
  ) {
    return 'account';
  }

  return null;
}

export function AppBottomNav() {
  const pathname = usePathname();
  const insets = useSafeAreaInsets();
  const { colors: theme } = useAppTheme();
  const { bootstrap, hasFeature } = useAuth();
  const [keyboardVisible, setKeyboardVisible] = useState(false);
  const styles = useMemo(() => createStyles(theme), [theme]);
  const active = activeKey(pathname);

  useEffect(() => {
    const show = Keyboard.addListener('keyboardDidShow', () => setKeyboardVisible(true));
    const hide = Keyboard.addListener('keyboardDidHide', () => setKeyboardVisible(false));

    return () => {
      show.remove();
      hide.remove();
    };
  }, []);

  const items: NavItem[] = useMemo(() => [
    {
      key: 'home',
      label: 'Početna',
      route: '/home',
      glyph: 'home',
      visible: true,
    },
    {
      key: 'catalog',
      label: 'Katalog',
      route: '/catalog',
      glyph: 'catalog',
      visible: hasFeature('catalog'),
    },
    {
      key: 'orders',
      label: 'Porudžbine',
      route: '/orders',
      glyph: 'orders',
      visible: hasFeature('orders'),
    },
    {
      key: 'notifications',
      label: 'Obaveštenja',
      route: '/notifications',
      glyph: 'bell',
      visible: hasFeature('notifications'),
      badge: bootstrap?.notification_counts.unread || undefined,
    },
    {
      key: 'account',
      label: 'Nalog',
      route: '/account',
      glyph: 'account',
      visible: true,
    },
  ], [bootstrap?.notification_counts.unread, hasFeature]);

  if (keyboardVisible) return null;

  return (
    <View
      pointerEvents="box-none"
      style={[
        styles.safeFrame,
        { paddingBottom: Math.max(insets.bottom, spacing.sm) },
      ]}
    >
      <View style={styles.bar}>
        {items.filter((item) => item.visible).map((item) => {
          const focused = active === item.key;

          return (
            <Pressable
              accessibilityLabel={item.label}
              accessibilityRole="button"
              accessibilityState={{ selected: focused }}
              key={item.key}
              onPress={() => {
                if (!focused) router.replace(item.route);
              }}
              style={({ pressed }) => [
                styles.item,
                focused ? styles.itemActive : null,
                pressed ? styles.pressed : null,
              ]}
            >
              <View style={styles.iconWrap}>
                <Glyph
                  name={item.glyph}
                  size={22}
                  color={focused ? theme.onPrimaryContainer : theme.muted}
                />
                {item.badge ? (
                  <View style={[styles.badge, focused ? styles.badgeActive : null]}>
                    <Text style={styles.badgeText}>
                      {item.badge > 99 ? '99+' : String(item.badge)}
                    </Text>
                  </View>
                ) : null}
              </View>
              <Text
                numberOfLines={1}
                style={[styles.label, focused ? styles.labelActive : null]}
              >
                {item.label}
              </Text>
            </Pressable>
          );
        })}
      </View>
    </View>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    safeFrame: {
      flexShrink: 0,
      backgroundColor: theme.background,
      paddingHorizontal: spacing.md,
      paddingTop: spacing.sm,
    },
    bar: {
      minHeight: 72,
      flexDirection: 'row',
      alignItems: 'stretch',
      justifyContent: 'space-around',
      gap: 2,
      paddingHorizontal: spacing.xs,
      paddingVertical: spacing.sm,
      borderWidth: StyleSheet.hairlineWidth,
      borderColor: theme.line,
      borderRadius: 28,
      backgroundColor: theme.surface,
      elevation: 10,
      shadowColor: theme.black,
      shadowOpacity: 0.08,
      shadowRadius: 18,
      shadowOffset: { width: 0, height: -4 },
    },
    item: {
      flex: 1,
      minWidth: 0,
      alignItems: 'center',
      justifyContent: 'center',
      gap: 2,
      borderRadius: radii.xl,
      paddingHorizontal: 2,
      paddingVertical: 2,
    },
    itemActive: {
      backgroundColor: theme.primaryContainer,
      borderWidth: StyleSheet.hairlineWidth,
      borderColor: theme.primary,
    },
    pressed: {
      opacity: 0.72,
    },
    iconWrap: {
      minWidth: 48,
      height: 30,
      borderRadius: radii.pill,
      alignItems: 'center',
      justifyContent: 'center',
    },
    label: {
      ...typography.small,
      maxWidth: '100%',
      color: theme.muted,
      fontSize: 10,
      lineHeight: 13,
      fontWeight: '700',
      textAlign: 'center',
    },
    labelActive: {
      color: theme.onPrimaryContainer,
      fontWeight: '900',
    },
    badge: {
      position: 'absolute',
      right: -3,
      top: -6,
      minWidth: 18,
      height: 18,
      paddingHorizontal: 4,
      borderRadius: radii.pill,
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: theme.danger,
      borderWidth: 2,
      borderColor: theme.surface,
    },
    badgeActive: {
      borderColor: theme.primaryContainer,
    },
    badgeText: {
      color: theme.onDanger,
      fontSize: 9,
      lineHeight: 10,
      fontWeight: '900',
    },
  });
}
