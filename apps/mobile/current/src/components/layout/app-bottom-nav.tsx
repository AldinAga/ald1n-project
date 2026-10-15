// MOBILE_GLOBAL_BOTTOM_NAV_V06
// MOBILE_V1_0_BOTTOM_TAB_ACTIVE_STATE_POLISH_BATCH48
// MOBILE_V1_0_CENTER_HOME_ROLE_AWARE_NAV_BATCH50_V2
// MOBILE_BATCH518B_STABLE_FIVE_TAB_NAV
// MOBILE_BATCH664_FLOATING_BOTTOM_NAV
import { useEffect, useMemo, useState } from 'react';
import { router, usePathname } from 'expo-router';
import { Keyboard, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { Glyph } from '@/components/ui/glyph';
import { activeBottomNavKey, buildBottomNavItems } from './app-bottom-nav-policy';
import { radii, spacing, typography, type AppColors } from '@/constants/theme';
import { useAuth, useNotificationUnread } from '@/features/auth/auth-provider';
import { useAppTheme } from '@/theme/app-theme';

const BAR_COLOR = '#292929';
const BAR_TEXT = '#F5F5F5';
const BAR_MUTED = '#D6D6D6';

export function AppBottomNav({ variant = 'bottom' }: { variant?: 'bottom' | 'rail' }) {
  const pathname = usePathname();
  const insets = useSafeAreaInsets();
  const { colors: theme } = useAppTheme();
  const { bootstrap, hasFeature, can } = useAuth();
  const unread = useNotificationUnread();
  const [keyboardVisible, setKeyboardVisible] = useState(false);
  const styles = useMemo(() => createStyles(theme), [theme]);
  const roleSlug = bootstrap?.user.role?.slug;
  const active = activeBottomNavKey(pathname, roleSlug);
  const rail = variant === 'rail';

  useEffect(() => {
    const show = Keyboard.addListener('keyboardDidShow', () => setKeyboardVisible(true));
    const hide = Keyboard.addListener('keyboardDidHide', () => setKeyboardVisible(false));
    return () => { show.remove(); hide.remove(); };
  }, []);

  const items = useMemo(() => buildBottomNavItems({
    roleSlug,
    catalogEnabled: hasFeature('catalog'),
    ordersEnabled: hasFeature('orders'),
    notificationsEnabled: hasFeature('notifications'),
    canManageOrders: can('orders.manage'),
    unread,
  }), [roleSlug, hasFeature, can, unread]);

  if (keyboardVisible && !rail) return null;

  const navItems = items.map((item) => {
    const focused = item.enabled && active === item.key;
    const center = item.center === true && !rail;
    return (
      <Pressable
        accessibilityLabel={item.label}
        accessibilityRole="button"
        accessibilityState={{ selected: focused, disabled: !item.enabled }}
        disabled={!item.enabled}
        key={item.key}
        onPress={() => { if (item.enabled && pathname !== item.route) router.replace(item.route); }}
        style={({ pressed }) => [
          styles.item,
          rail ? styles.railItem : null,
          center ? styles.homeItem : null,
          !center && focused ? (rail ? styles.railItemActive : styles.itemActive) : null,
          !item.enabled ? styles.disabled : null,
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
              ? theme.onPrimary
              : rail
                ? (focused ? theme.primary : theme.muted)
                : (focused ? BAR_TEXT : BAR_MUTED)}
          />
          {item.enabled && item.badge ? (
            <View style={[styles.badge, focused ? styles.badgeActive : null]}>
              <Text style={styles.badgeText}>{item.badge > 99 ? '99+' : String(item.badge)}</Text>
            </View>
          ) : null}
        </View>
        {center ? null : (
          <Text
            numberOfLines={1}
            adjustsFontSizeToFit
            minimumFontScale={0.72}
            style={[
              styles.label,
              rail ? styles.labelRail : null,
              focused ? (rail ? styles.labelRailActive : styles.labelActive) : null,
            ]}
          >
            {item.label}
          </Text>
        )}
      </Pressable>
    );
  });

  return (
    <View
      pointerEvents="box-none"
      style={rail
        ? [
            styles.railFrame,
            {
              paddingTop: Math.max(insets.top, spacing.lg),
              paddingBottom: Math.max(insets.bottom, spacing.lg),
              paddingLeft: Math.max(insets.left, spacing.sm),
            },
          ]
        : [
            styles.safeFrame,
            {
              paddingBottom: Math.max(insets.bottom, spacing.sm),
              paddingLeft: Math.max(insets.left, spacing.md),
              paddingRight: Math.max(insets.right, spacing.md),
            },
          ]}
    >
      {rail ? (
        <ScrollView
          style={styles.railScroller}
          contentContainerStyle={styles.rail}
          showsVerticalScrollIndicator={false}
          keyboardShouldPersistTaps="handled"
        >
          {navItems}
        </ScrollView>
      ) : (
        <View style={styles.bar}>{navItems}</View>
      )}
    </View>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    safeFrame: {
      flexShrink: 0,
      backgroundColor: theme.background,
      paddingTop: 24,
    },
    railFrame: {
      width: 104,
      minHeight: 0,
      flexShrink: 0,
      paddingRight: spacing.sm,
      backgroundColor: theme.background,
    },
    railScroller: {
      flex: 1,
      minHeight: 0,
      width: '100%',
    },
    rail: {
      flexGrow: 1,
      width: '100%',
      alignItems: 'stretch',
      gap: spacing.sm,
      paddingHorizontal: spacing.xs,
      paddingVertical: spacing.md,
      borderWidth: StyleSheet.hairlineWidth,
      borderColor: theme.line,
      borderRadius: 28,
      backgroundColor: theme.surface,
    },
    bar: {
      minHeight: 76,
      flexDirection: 'row',
      alignItems: 'stretch',
      justifyContent: 'space-around',
      gap: 1,
      paddingHorizontal: spacing.xs,
      paddingVertical: spacing.sm,
      borderRadius: 32,
      backgroundColor: BAR_COLOR,
      overflow: 'visible',
      elevation: 12,
      shadowColor: theme.black,
      shadowOpacity: 0.24,
      shadowRadius: 20,
      shadowOffset: { width: 0, height: -5 },
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
    railItem: {
      flex: 0,
      width: '100%',
      minHeight: 62,
      paddingHorizontal: spacing.xs,
      paddingVertical: spacing.sm,
    },
    itemActive: { backgroundColor: 'rgba(255,255,255,0.09)' },
    railItemActive: {
      backgroundColor: theme.primaryContainer,
      borderWidth: StyleSheet.hairlineWidth,
      borderColor: theme.primary,
    },
    homeItem: { transform: [{ translateY: -20 }], overflow: 'visible' },
    homeCircle: {
      width: 70,
      height: 70,
      minWidth: 70,
      borderRadius: 35,
      backgroundColor: theme.primary,
      borderWidth: 5,
      borderColor: theme.background,
      elevation: 13,
      shadowColor: theme.primary,
      shadowOpacity: 0.32,
      shadowRadius: 14,
      shadowOffset: { width: 0, height: 6 },
      alignItems: 'center',
      justifyContent: 'center',
    },
    homeCircleActive: { transform: [{ scale: 1.04 }] },
    disabled: { opacity: 0.36 },
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
      color: BAR_MUTED,
      fontSize: 10,
      lineHeight: 14,
      fontWeight: '600',
      textAlign: 'center',
    },
    labelActive: { color: BAR_TEXT, fontWeight: '800' },
    labelRail: { color: theme.muted },
    labelRailActive: { color: theme.primary, fontWeight: '800' },
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
      borderColor: BAR_COLOR,
    },
    badgeActive: { borderColor: BAR_COLOR },
    badgeText: { color: theme.onDanger, fontSize: 9, lineHeight: 10, fontWeight: '900' },
  });
}
