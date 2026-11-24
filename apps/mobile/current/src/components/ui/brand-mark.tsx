// Author: Ald1n <https://ald1n.com>
import { Image } from 'expo-image';
import { useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';

import { type AppColors } from '@/constants/theme';
import { useThemedStyles } from '@/theme/app-theme';

// SVG logo (same artwork as assets/brand/ald1n-v2-logo.svg, without the CorelDRAW DOCTYPE/CSS so Android's SVG decoder renders it reliably).
const LOGO = require('../../../assets/brand/ald1n-mark.svg');

// Decorative: every caller sits next to a visible title, so it is hidden from screen readers.
// `inverse` adds a hairline ring so the dark tile stays visible on dark hero panels; if the SVG fails to load, an "A" tile in the logo colours stays.
export function BrandMark({ size = 46, inverse = false }: { size?: number; inverse?: boolean }) {
  const styles = useThemedStyles(createStyles);
  const [failed, setFailed] = useState(false);
  return (
    <View
      accessible={false}
      importantForAccessibility="no-hide-descendants"
      style={[styles.frame, { width: size, height: size, borderRadius: Math.round(size * 0.19) }, inverse && styles.ring]}
    >
      {failed ? (
        <View style={styles.fallback}>
          <Text style={[styles.letter, { fontSize: Math.round(size * 0.56) }]} numberOfLines={1}>A</Text>
        </View>
      ) : (
        <Image source={LOGO} contentFit="contain" style={{ width: size, height: size }} accessibilityIgnoresInvertColors onError={() => setFailed(true)} />
      )}
    </View>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    frame: { overflow: 'hidden', flexShrink: 0 },
    ring: { borderWidth: StyleSheet.hairlineWidth, borderColor: theme.heroMuted },
    fallback: { flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: '#02141E' },
    letter: { color: '#FEA500', fontWeight: '900' },
  });
}
