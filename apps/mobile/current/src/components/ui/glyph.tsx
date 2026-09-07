import { SymbolView } from 'expo-symbols';
import medium from 'expo-symbols/androidWeights/medium';
import { View, type ColorValue, type StyleProp, type ViewStyle } from 'react-native';

const symbolNames = {
  home: { ios: 'house.fill', android: 'home', web: 'home' },
  catalog: { ios: 'square.grid.2x2.fill', android: 'inventory_2', web: 'inventory_2' },
  add: { ios: 'plus.circle.fill', android: 'add_circle', web: 'add_circle' },
  remove: { ios: 'minus', android: 'remove', web: 'remove' },
  cart: { ios: 'cart.fill', android: 'shopping_cart', web: 'shopping_cart' },
  orders: { ios: 'shippingbox.fill', android: 'receipt_long', web: 'receipt_long' },
  commission: { ios: 'banknote.fill', android: 'payments', web: 'payments' },
  warranty: { ios: 'checkmark.shield.fill', android: 'verified_user', web: 'verified_user' },
  service: { ios: 'wrench.and.screwdriver.fill', android: 'build', web: 'build' },
  messages: { ios: 'bubble.left.and.bubble.right.fill', android: 'forum', web: 'forum' },
  report: { ios: 'chart.bar.fill', android: 'bar_chart', web: 'bar_chart' },
  bell: { ios: 'bell.fill', android: 'notifications', web: 'notifications' },
  account: { ios: 'person.crop.circle.fill', android: 'account_circle', web: 'account_circle' },
  admin: { ios: 'shield.lefthalf.filled', android: 'admin_panel_settings', web: 'admin_panel_settings' },
  arrow: { ios: 'chevron.right', android: 'chevron_right', web: 'chevron_right' },
  search: { ios: 'magnifyingglass', android: 'search', web: 'search' },
  box: { ios: 'shippingbox', android: 'inventory_2', web: 'inventory_2' },
  refresh: { ios: 'arrow.clockwise', android: 'refresh', web: 'refresh' },
  check: { ios: 'checkmark.circle.fill', android: 'check_circle', web: 'check_circle' },
  close: { ios: 'xmark.circle.fill', android: 'cancel', web: 'cancel' },
  device: { ios: 'iphone', android: 'smartphone', web: 'smartphone' },
  lock: { ios: 'lock.fill', android: 'lock', web: 'lock' },
  info: { ios: 'info.circle.fill', android: 'info', web: 'info' },
  google: { ios: 'person.badge.key.fill', android: 'passkey', web: 'passkey' },
} as const;

export type GlyphName = keyof typeof symbolNames;

export function Glyph({ name, size = 20, color, style }: {
  name: GlyphName;
  size?: number;
  color?: ColorValue;
  style?: StyleProp<ViewStyle>;
}) {
  return (
    <View style={[{ width: size + 4, height: size + 4, alignItems: 'center', justifyContent: 'center' }, style]}>
      <SymbolView
        name={symbolNames[name]}
        tintColor={color}
        size={size}
        weight={{ ios: 'medium', android: medium }}
      />
    </View>
  );
}
