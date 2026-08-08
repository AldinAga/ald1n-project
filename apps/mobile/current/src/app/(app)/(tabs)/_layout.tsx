import { Tabs } from 'expo-router';
import { View, type ColorValue } from 'react-native';
import { Glyph, type GlyphName } from '@/components/ui/glyph';
import { colors, radii } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';

function TabIcon({ name, color, focused }: { name: GlyphName; color: ColorValue; focused: boolean }) {
  return (
    <View style={{
      minWidth: 50,
      height: 32,
      borderRadius: radii.pill,
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: focused ? colors.primaryContainer : 'transparent',
    }}>
      <Glyph name={name} size={22} color={focused ? colors.primaryDark : color} />
    </View>
  );
}

export default function TabsLayout() {
  const { bootstrap, hasFeature } = useAuth();
  return (
    <Tabs screenOptions={{
      headerShown: false,
      tabBarActiveTintColor: colors.primaryDark,
      tabBarInactiveTintColor: colors.muted,
      tabBarStyle: {
        height: 82,
        paddingTop: 8,
        paddingBottom: 10,
        borderTopWidth: 0,
        backgroundColor: colors.surface,
        elevation: 10,
      },
      tabBarItemStyle: { borderRadius: radii.xl, paddingVertical: 2 },
      tabBarLabelStyle: { fontSize: 11, lineHeight: 15, fontWeight: '800' }
    }}>
      <Tabs.Screen name="home" options={{ title: 'Početna', tabBarIcon: ({ color, focused }) => <TabIcon name="home" color={color} focused={focused} /> }} />
      <Tabs.Screen name="catalog" options={{ title: 'Katalog', href: hasFeature('catalog') ? undefined : null, tabBarIcon: ({ color, focused }) => <TabIcon name="catalog" color={color} focused={focused} /> }} />
      <Tabs.Screen name="orders" options={{ title: 'Porudžbine', href: hasFeature('orders') ? undefined : null, tabBarIcon: ({ color, focused }) => <TabIcon name="orders" color={color} focused={focused} /> }} />
      <Tabs.Screen name="notifications" options={{ title: 'Obaveštenja', href: hasFeature('notifications') ? undefined : null, tabBarBadge: bootstrap?.notification_counts.unread || undefined, tabBarIcon: ({ color, focused }) => <TabIcon name="bell" color={color} focused={focused} /> }} />
      <Tabs.Screen name="account" options={{ title: 'Nalog', tabBarIcon: ({ color, focused }) => <TabIcon name="account" color={color} focused={focused} /> }} />
    </Tabs>
  );
}
