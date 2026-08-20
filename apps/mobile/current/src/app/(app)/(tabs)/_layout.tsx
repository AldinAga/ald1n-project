// MOBILE_GLOBAL_BOTTOM_NAV_LEGACY_TABS_HIDDEN_V06
import { Tabs } from 'expo-router';
import type { ColorValue } from 'react-native';
import { XStack } from 'tamagui';
import { Glyph, type GlyphName } from '@/components/ui/glyph';
import { radii } from '@/constants/theme';
import { useAppTheme } from '@/theme/app-theme';
import { useAuth } from '@/features/auth/auth-provider';

function TabIcon({ name, color, focused }: { name: GlyphName; color: ColorValue; focused: boolean }) {
  const { colors: themeColors } = useAppTheme();

  return (
    <XStack
      minWidth={52}
      height={32}
      borderRadius={radii.pill}
      alignItems="center"
      justifyContent="center"
      backgroundColor={focused ? themeColors.primaryContainer : 'transparent'}
      scale={focused ? 1 : 0.96}
      y={focused ? -1 : 0}
      transition="quickLessBouncy"
    >
      <Glyph
        name={name}
        size={22}
        color={focused ? themeColors.primaryDark : color}
      />
    </XStack>
  );
}

export default function TabsLayout() {
  const { colors: themeColors } = useAppTheme();
  const { bootstrap, hasFeature } = useAuth();

  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: themeColors.primaryDark,
        tabBarInactiveTintColor: themeColors.muted,
        tabBarHideOnKeyboard: true,
        tabBarStyle: {
          display: 'none',
          height: 86,
          paddingTop: 8,
          paddingBottom: 12,
          borderTopWidth: 1,
          borderTopColor: themeColors.line,
          borderTopLeftRadius: 28,
          borderTopRightRadius: 28,
          backgroundColor: themeColors.surface,
          elevation: 12,
          shadowColor: themeColors.black,
          shadowOpacity: 0.08,
          shadowRadius: 18,
          shadowOffset: { width: 0, height: -6 },
        },
        tabBarItemStyle: {
          borderRadius: radii.xl,
          paddingVertical: 2,
        },
        tabBarLabelStyle: {
          fontSize: 11,
          lineHeight: 15,
          fontWeight: '800',
        },
        tabBarBadgeStyle: {
          backgroundColor: themeColors.danger,
          color: themeColors.onDanger,
          fontSize: 10,
          fontWeight: '800',
        },
      }}
    >
      <Tabs.Screen
        name="home"
        options={{
          title: 'Početna',
          tabBarIcon: ({ color, focused }) => <TabIcon name="home" color={color} focused={focused} />,
        }}
      />
      <Tabs.Screen
        name="catalog"
        options={{
          title: 'Katalog',
          href: hasFeature('catalog') ? undefined : null,
          tabBarIcon: ({ color, focused }) => <TabIcon name="catalog" color={color} focused={focused} />,
        }}
      />
      <Tabs.Screen
        name="orders"
        options={{
          title: 'Porudžbine',
          href: hasFeature('orders') ? undefined : null,
          tabBarIcon: ({ color, focused }) => <TabIcon name="orders" color={color} focused={focused} />,
        }}
      />
      <Tabs.Screen
        name="notifications"
        options={{
          title: 'Obaveštenja',
          href: hasFeature('notifications') ? undefined : null,
          tabBarBadge: bootstrap?.notification_counts.unread || undefined,
          tabBarIcon: ({ color, focused }) => <TabIcon name="bell" color={color} focused={focused} />,
        }}
      />
      <Tabs.Screen
        name="account"
        options={{
          title: 'Nalog',
          tabBarIcon: ({ color, focused }) => <TabIcon name="account" color={color} focused={focused} />,
        }}
      />
    </Tabs>
  );
}
