// Author: Ald1n <https://ald1n.com>
import { Image } from 'expo-image';
import { StyleSheet, View } from 'react-native';

import { type AppColors } from '@/constants/theme';
import { useThemedStyles } from '@/theme/app-theme';

// SVG logo (same artwork as assets/brand/ald1n-v2-logo.svg, without the CorelDRAW DOCTYPE/CSS so Android's SVG decoder renders it reliably).
const LOGO = require('../../../assets/brand/ald1n-mark.svg');

// The logo carries its own dark tile; `inverse` adds a hairline ring so the tile stays visible on dark hero panels.
export function BrandMark({ size = 46, inverse = false }: { size?: number; inverse?: boolean }) {
  const styles = useThemedStyles(createStyles);
  return (
    <View
      accessible
      accessibilityRole="image"
      accessibilityLabel="ALD1N logo"
      style={[styles.frame, { width: size, height: size, borderRadius: Math.round(size * 0.19) }, inverse && styles.ring]}
    >
      <Image source={LOGO} contentFit="contain" style={{ width: size, height: size }} accessibilityIgnoresInvertColors />
    </View>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    frame: { overflow: 'hidden', flexShrink: 0 },
    ring: { borderWidth: StyleSheet.hairlineWidth, borderColor: theme.heroMuted },
  });
}
