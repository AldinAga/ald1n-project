import { SymbolView } from 'expo-symbols';
import { Text, View, type ColorValue, type StyleProp, type ViewStyle } from 'react-native';

const symbolNames = {
  home: { ios: 'house.fill', android: 'home', web: 'home' },
  catalog: { ios: 'square.grid.2x2.fill', android: 'inventory_2', web: 'inventory_2' },
  cart: { ios: 'cart.fill', android: 'shopping_cart', web: 'shopping_cart' },
  orders: { ios: 'shippingbox.fill', android: 'receipt_long', web: 'receipt_long' },
  bell: { ios: 'bell.fill', android: 'notifications', web: 'notifications' },
  account: { ios: 'person.crop.circle.fill', android: 'account_circle', web: 'account_circle' },
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

const fallback = {
  home: '⌂', catalog: '▦', cart: '▤', orders: '≡', bell: '●', account: '◎',
  arrow: '›', search: '⌕', box: '□', refresh: '↻', check: '✓', close: '×',
  device: '▯', lock: '◆', info: 'i', google: 'G',
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
        fallback={<Text style={{ fontSize: size, lineHeight: size + 4, color, fontWeight: '800' }}>{fallback[name]}</Text>}
      />
    </View>
  );
}
