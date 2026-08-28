// MOBILE_GLOBAL_BOTTOM_NAV_V06
// MOBILE_V1_0_BOTTOM_TAB_ACTIVE_STATE_POLISH_BATCH48
// MOBILE_V1_0_CENTER_HOME_ROLE_AWARE_NAV_BATCH50_V2
import { useEffect, useMemo, useState } from 'react';
import { router, usePathname } from 'expo-router';
import { Keyboard, Pressable, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { Glyph, type GlyphName } from '@/components/ui/glyph';
import { radii, spacing, typography, type AppColors } from '@/constants/theme';
import { useAuth, useNotificationUnread } from '@/features/auth/auth-provider';
import { useAppTheme } from '@/theme/app-theme';

type MainRoute = '/home' | '/catalog' | '/orders' | '/notifications' | '/account' | '/admin';
type NavKey = 'home' | 'catalog' | 'orders' | 'notifications' | 'account' | 'admin';

type NavItem = {
  key: NavKey;
  label: string;
  route: MainRoute;
  glyph: GlyphName;
  visible: boolean;
  badge?: number;
  center?: boolean;
};

function activeKey(pathname: string): NavKey | null {
  if (pathname === '/home' || pathname === '/') return 'home';
  if (
    pathname === '/catalog'
    || pathname.startsWith('/product/')
    || pathname === '/cart'
    || pathname === '/checkout'
    || pathname.startsWith('/admin/catalog/')
  ) return 'catalog';
  if (
    pathname === '/orders'
    || pathname.startsWith('/order/')
    || pathname.startsWith('/assigned-orders')
    || pathname.startsWith('/after-sales')
    || pathname.startsWith('/warranties')
    || pathname.startsWith('/commissions')
  ) return 'orders';
  if (pathname === '/notifications') return 'notifications';
  if (
    pathname === '/account'
    || pathname === '/devices'
    || pathname === '/notification-settings'
    || pathname === '/sessions'
  ) return 'account';
  if (pathname === '/admin' || pathname.startsWith('/admin/')) return 'admin';
  return null;
}

export function AppBottomNav() {
  const pathname = usePathname();
  const insets = useSafeAreaInsets();
  const { colors: theme } = useAppTheme();
  const { bootstrap, hasFeature } = useAuth();
  const unread = useNotificationUnread();
  const [keyboardVisible, setKeyboardVisible] = useState(false);
  const styles = useMemo(() => createStyles(theme), [theme]);
  const active = activeKey(pathname);
  const isSuperAdmin = bootstrap?.user.role?.slug === 'superadmin';

  useEffect(() => {
    const show = Keyboard.addListener('keyboardDidShow', () => setKeyboardVisible(true));
    const hide = Keyboard.addListener('keyboardDidHide', () => setKeyboardVisible(false));
    return () => { show.remove(); hide.remove(); };
  }, []);

  const items: NavItem[] = useMemo(() => [
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
      key: 'home',
      label: 'Početna',
      route: '/home',
      glyph: 'home',
      visible: true,
      center: true,
    },
    isSuperAdmin
      ? {
          key: 'admin',
          label: 'Admin',
          route: '/admin',
          glyph: 'admin',
          visible: true,
        }
      : {
          key: 'notifications',
          label: 'Obaveštenja',
          route: '/notifications',
          glyph: 'bell',
          visible: hasFeature('notifications'),
          badge: unread || undefined,
        },
    {
      key: 'account',
      label: 'Nalog',
      route: '/account',
      glyph: 'account',
      visible: true,
    },
  ], [hasFeature, isSuperAdmin, unread]);

  if (keyboardVisible) return null;

  return (
    <View
      pointerEvents="box-none"
      style={[styles.safeFrame, { paddingBottom: Math.max(insets.bottom, spacing.sm) }]}
    >
      <View style={styles.bar}>
        {items.map((item) => {
          if (!item.visible) return <View key={item.key} style={styles.item} pointerEvents="none" />;
          const focused = active === item.key;
          const center = item.center === true;
          return (
            <Pressable
              accessibilityLabel={item.label}
              accessibilityRole="button"
              accessibilityState={{ selected: focused }}
              key={item.key}
              onPress={() => { if (!focused) router.replace(item.route); }}
              style={({ pressed }) => [
                styles.item,
                center ? styles.homeItem : null,
                !center && focused ? styles.itemActive : null,
                pressed ? styles.pressed : null,
              ]}
            >
              <View
                style={[
                  styles.iconWrap,
                  center ? styles.homeCircle : null,
                  center && focused ? styles.homeCircleActive : null,
                ]}
              >
                <Glyph
                  name={item.glyph}
                  size={center ? 28 : 22}
                  color={center
                    ? (focused ? theme.onPrimary : theme.onPrimaryContainer)
                    : (focused ? theme.onPrimaryContainer : theme.muted)}
                />
                {item.badge ? (
                  <View style={[styles.badge, focused ? styles.badgeActive : null]}>
                    <Text style={styles.badgeText}>{item.badge > 99 ? '99+' : String(item.badge)}</Text>
                  </View>
                ) : null}
              </View>
              <Text
                numberOfLines={1}
                style={[
                  styles.label,
                  focused ? styles.labelActive : null,
                  center ? styles.homeLabel : null,
                ]}
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
      paddingTop: 18,
    },
    bar: {
      minHeight: 76,
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
    homeItem: {
      transform: [{ translateY: -17 }],
      overflow: 'visible',
    },
    homeCircle: {
      width: 62,
      height: 62,
      minWidth: 62,
      borderRadius: 31,
      backgroundColor: theme.primaryContainer,
      borderWidth: 4,
      borderColor: theme.background,
      elevation: 13,
      shadowColor: theme.primary,
      shadowOpacity: 0.26,
      shadowRadius: 14,
      shadowOffset: { width: 0, height: 6 },
      alignItems: 'center',
      justifyContent: 'center',
    },
    homeCircleActive: {
      backgroundColor: theme.primary,
      borderColor: theme.surface,
    },
    homeLabel: {
      marginTop: 1,
      color: theme.primary,
      fontWeight: '900',
    },
    pressed: { opacity: 0.72 },
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
    labelActive: { color: theme.onPrimaryContainer, fontWeight: '900' },
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
    badgeActive: { borderColor: theme.primaryContainer },
    badgeText: { color: theme.onDanger, fontSize: 9, lineHeight: 10, fontWeight: '900' },
  });
}
