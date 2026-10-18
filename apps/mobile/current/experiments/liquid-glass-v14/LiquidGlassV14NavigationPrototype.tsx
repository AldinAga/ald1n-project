/**
 * Android v1.4 Liquid Glass proof of concept.
 * Intentionally NOT wired into Expo Router or production navigation.
 * Requires installing react-native-android-liquid-glass@0.2.1 in isolated worktree
 * and a fresh Android development/native build. NOT OTA compatible.
 */
import React from 'react';
import { Platform, StyleSheet, Text, View } from 'react-native';
import { LiquidGlassAndroidView } from 'react-native-android-liquid-glass';

export function LiquidGlassV14NavigationPrototype() {
  const links = ['Početna', 'Katalog', 'Porudžbine'];
  return (
    <View collapsable={false} style={styles.host}>
      <View style={styles.backdrop}>
        {links.map((name, i) => (
          <View key={name} style={[styles.backdropTile, { opacity: 0.3 + i * 0.18 }]}>
            <Text style={styles.backgroundText}>{name}</Text>
          </View>
        ))}
      </View>
      {Platform.OS === 'android' ? (
        <LiquidGlassAndroidView
          style={styles.glass}
          material="regular"
          cornerRadius={28}
          refractionHeight={66}
          enableDynamicBackground={false}
        >
          <View style={styles.links}>
            {links.map((name) => <Text key={name} style={styles.link}>{name}</Text>)}
          </View>
        </LiquidGlassAndroidView>
      ) : (
        <View style={[styles.glass, styles.fallback]}>
          <Text style={styles.link}>Liquid Glass preview: Android only</Text>
        </View>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  host: { minHeight: 230, justifyContent: 'flex-end', padding: 24, backgroundColor: '#17242f' },
  backdrop: { ...StyleSheet.absoluteFillObject, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-around' },
  backdropTile: { width: 110, height: 130, borderRadius: 18, backgroundColor: '#85b4d3', justifyContent: 'center', alignItems: 'center' },
  backgroundText: { color: '#0b2133', fontWeight: '700', textAlign: 'center' },
  glass: { height: 62, borderRadius: 28, justifyContent: 'center', overflow: 'hidden' },
  fallback: { backgroundColor: '#50616b' },
  links: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-around', paddingHorizontal: 12 },
  link: { color: '#ffffff', fontWeight: '700', fontSize: 12 },
});
