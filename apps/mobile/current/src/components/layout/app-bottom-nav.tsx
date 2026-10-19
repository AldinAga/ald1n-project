// MOBILE_GLOBAL_BOTTOM_NAV_V06
// MOBILE_V1_0_BOTTOM_TAB_ACTIVE_STATE_POLISH_BATCH48
// MOBILE_V1_0_CENTER_HOME_ROLE_AWARE_NAV_BATCH50_V2
// MOBILE_BATCH518B_STABLE_FIVE_TAB_NAV
// MOBILE_BATCH664_FLOATING_BOTTOM_NAV
// MOBILE_BATCH678_COMPACT_TRANSLUCENT_BOTTOM_NAV
import { useEffect, useMemo, useState } from 'react';
import { router, usePathname } from 'expo-router';
import { AccessibilityInfo, Keyboard, Platform, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { Glyph } from '@/components/ui/glyph';
import { activeBottomNavKey, buildBottomNavItems } from './app-bottom-nav-policy';
import { androidGlassChrome, resolveAndroidGlassMaterial } from './android-glass-material-policy';
import { radii, spacing, typography, type AppColors } from '@/constants/theme';
import { useAuth, useNotificationUnread } from '@/features/auth/auth-provider';
import { useAppTheme } from '@/theme/app-theme';

const LIGHT_GLASS = 'rgba(225,229,232,0.78)';
const DARK_GLASS = 'rgba(48,54,60,0.76)';

export function AppBottomNav({ variant = 'bottom' }: { variant?: 'bottom' | 'rail' }) {
  const pathname = usePathname();
  const insets = useSafeAreaInsets();
  const { colors: theme, isDark } = useAppTheme();
  const { bootstrap, hasFeature, can } = useAuth();
  const unread = useNotificationUnread();
  const [keyboardVisible, setKeyboardVisible] = useState(false);
  const [reduceTransparency, setReduceTransparency] = useState(false);
  const glassMode = resolveAndroidGlassMaterial({
    platform: Platform.OS,
    androidApiLevel: Platform.OS === 'android' ? Number(Platform.Version) : undefined,
    reduceTransparency,
  });
  const styles = useMemo(() => createStyles(theme, isDark), [theme, isDark]);
  const roleSlug = bootstrap?.user.role?.slug;
  const active = activeBottomNavKey(pathname, roleSlug);
  const rail = variant === 'rail';

  useEffect(() => {
    let mounted = true;
    // RN accessibility preference is optional across Android vendors.
    if (typeof AccessibilityInfo.isReduceTransparencyEnabled !== 'function') return;
    void AccessibilityInfo.isReduceTransparencyEnabled()
      .then((enabled) => { if (mounted) setReduceTransparency(Boolean(enabled)); })
      .catch(() => { /* Opaque fallback is still available by API level. */ });
    const listener = AccessibilityInfo.addEventListener('reduceTransparencyChanged', (enabled) => {
      if (mounted) setReduceTransparency(Boolean(enabled));
    });
    return () => { mounted = false; listener.remove(); };
  }, []);

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
            size={center ? 24 : 20}
            color={center
              ? theme.onPrimary
              : rail
                ? (focused ? theme.primary : theme.muted)
                : (focused ? theme.ink : theme.muted)}
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
              paddingBottom: Math.max(insets.bottom, spacing.xs),
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
        <View style={[styles.bar, glassMode === 'solid' ? styles.barSolid : null]}>
          {Platform.OS === 'android' && glassMode === 'translucent' ? (
            <View
              pointerEvents="none"
              accessible={false}
              importantForAccessibility="no-hide-descendants"
              style={styles.glassReflection}
            >
              <View style={styles.glassSpecular} />
              <View style={styles.glassTopRim} />
              <View style={styles.glassBottomRim} />
            </View>
          ) : null}
          {navItems}
        </View>
      )}
    </View>
  );
}

function createStyles(theme: AppColors, isDark: boolean) {
  const chrome = androidGlassChrome(isDark);
  return StyleSheet.create({
    safeFrame: {
      position: 'absolute',
      bottom: 0,
      left: 0,
      right: 0,
      zIndex: 10,
      backgroundColor: 'transparent',
      paddingTop: 12,
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
      minHeight: 54,
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-around',
      gap: 1,
      paddingHorizontal: spacing.xs,
      paddingVertical: 2,
      borderRadius: 24,
      backgroundColor: isDark ? DARK_GLASS : LIGHT_GLASS,
      borderWidth: StyleSheet.hairlineWidth,
      borderColor: isDark ? 'rgba(232,239,244,0.17)' : 'rgba(78,88,98,0.22)',
      overflow: 'visible',
      elevation: 5,
      shadowColor: theme.black,
      shadowOpacity: 0.10,
      shadowRadius: 10,
      shadowOffset: { width: 0, height: 2 },
    },
    barSolid: {
      backgroundColor: theme.surface,
      borderColor: theme.line,
      elevation: 2,
      shadowOpacity: 0.04,
    },
    glassReflection: {
      ...StyleSheet.absoluteFillObject,
      borderRadius: 24,
      overflow: 'hidden',
    },
    glassSpecular: {
      position: 'absolute',
      top: 1,
      left: 12,
      right: 12,
      height: 19,
      borderTopLeftRadius: 22,
      borderTopRightRadius: 22,
      backgroundColor: chrome.specular,
    },
    glassTopRim: {
      position: 'absolute',
      top: 1,
      left: 13,
      right: 13,
      height: StyleSheet.hairlineWidth,
      backgroundColor: chrome.topRim,
    },
    glassBottomRim: {
      position: 'absolute',
      bottom: 1,
      left: 16,
      right: 16,
      height: StyleSheet.hairlineWidth,
      backgroundColor: chrome.bottomRim,
    },
    item: {
      flex: 1,
      minWidth: 0,
      minHeight: 48,
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
    itemActive: {
      backgroundColor: isDark ? 'rgba(255,255,255,0.12)' : 'rgba(255,255,255,0.34)',
      borderWidth: Platform.OS === 'android' ? StyleSheet.hairlineWidth : 0,
      borderColor: chrome.activeRim,
    },
    railItemActive: {
      backgroundColor: theme.primaryContainer,
      borderWidth: StyleSheet.hairlineWidth,
      borderColor: theme.primary,
    },
    homeItem: { transform: [{ translateY: -9 }], overflow: 'visible' },
    homeCircle: {
      width: 52,
      height: 52,
      minWidth: 52,
      borderRadius: 26,
      backgroundColor: theme.primary,
      borderWidth: 3,
      borderColor: theme.background,
      elevation: 7,
      shadowColor: theme.primary,
      shadowOpacity: 0.20,
      shadowRadius: 9,
      shadowOffset: { width: 0, height: 3 },
      alignItems: 'center',
      justifyContent: 'center',
    },
    homeCircleActive: { transform: [{ scale: 1.04 }] },
    disabled: { opacity: 0.36 },
    pressed: { opacity: 0.72 },
    iconWrap: {
      minWidth: 48,
      height: 24,
      borderRadius: radii.pill,
      alignItems: 'center',
      justifyContent: 'center',
    },
    label: {
      ...typography.small,
      maxWidth: '100%',
      color: theme.muted,
      fontSize: 10,
      lineHeight: 12,
      fontWeight: '600',
      textAlign: 'center',
    },
    labelActive: { color: theme.ink, fontWeight: '800' },
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
      borderColor: theme.surface,
    },
    badgeActive: { borderColor: theme.surface },
    badgeText: { color: theme.onDanger, fontSize: 9, lineHeight: 10, fontWeight: '900' },
  });
}
