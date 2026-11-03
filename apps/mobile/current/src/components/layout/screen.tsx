import { useCallback, useEffect, useRef, useState, type PropsWithChildren, type ReactNode } from 'react';
import {
  Keyboard,
  Platform,
  ScrollView,
  StyleSheet,
  UIManager,
  View,
  type ScrollViewProps,
  type ScrollViewInstance,
  type StyleProp,
  type ViewStyle,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { KeyboardScrollContext } from '@/components/layout/keyboard-scroll-context';
import { neededKeyboardScroll } from '@/components/layout/keyboard-visibility';
import { spacing } from '@/constants/theme';
import { useAppTheme } from '@/theme/app-theme';

type ScreenProps = PropsWithChildren<{
  scroll?: boolean;
  contentStyle?: StyleProp<ViewStyle>;
  topAccessory?: ReactNode;
  bottomAccessory?: ReactNode;
  /** Reveal focused fields above the Android IME; opt in only for long forms. */
  keyboardAware?: boolean;
} & ScrollViewProps>;

export function Screen({
  children,
  scroll = true,
  contentStyle,
  topAccessory,
  bottomAccessory,
  keyboardAware = false,
  ...props
}: ScreenProps) {
  const { colors: themeColors } = useAppTheme();
  const scrollRef = useRef<ScrollViewInstance>(null);
  const focusedTargetRef = useRef<number | null>(null);
  const keyboardTopRef = useRef<number | null>(null);
  const scrollOffsetRef = useRef(0);
  const [keyboardHeight, setKeyboardHeight] = useState(0);
  const { onScroll: externalOnScroll, scrollEventThrottle: externalThrottle, ...scrollProps } = props;

  const revealFocusedInput = useCallback(() => {
    if (!keyboardAware || Platform.OS !== 'android') return;
    const target = focusedTargetRef.current;
    const keyboardTop = keyboardTopRef.current;
    if (target === null || keyboardTop === null) return;

    // Android edge-to-edge can leave the ScrollView full-height even when
    // softwareKeyboardLayoutMode is 'resize'. Measure against the IME instead.
    UIManager.measureInWindow(target, (_x, top, _width, height) => {
      if (keyboardTopRef.current === null || !scrollRef.current) return;
      if (!Number.isFinite(top) || !Number.isFinite(height) || height <= 0) {
        scrollRef.current.scrollResponderScrollNativeHandleToKeyboard(target, 40, true);
        return;
      }
      const delta = neededKeyboardScroll(top + height, keyboardTopRef.current, 40);
      if (delta > 0) {
        scrollRef.current.scrollTo({
          y: scrollOffsetRef.current + delta,
          animated: true,
        });
      }
    });
  }, [keyboardAware]);

  const onFieldFocus = useCallback((target: number) => {
    focusedTargetRef.current = target;
    if (keyboardTopRef.current !== null) requestAnimationFrame(revealFocusedInput);
  }, [revealFocusedInput]);

  useEffect(() => {
    if (!keyboardAware || Platform.OS !== 'android') return;
    const show = Keyboard.addListener('keyboardDidShow', (event) => {
      keyboardTopRef.current = event.endCoordinates.screenY;
      setKeyboardHeight(Math.max(0, event.endCoordinates.height));
      // Wait for IME + content-padding layout before attempting focus reveal.
      requestAnimationFrame(() => requestAnimationFrame(revealFocusedInput));
    });
    const hide = Keyboard.addListener('keyboardDidHide', () => {
      keyboardTopRef.current = null;
      focusedTargetRef.current = null;
      setKeyboardHeight(0);
    });
    return () => { show.remove(); hide.remove(); };
  }, [keyboardAware, revealFocusedInput]);

  const safeStyle = [
    styles.safe,
    {
      backgroundColor: themeColors.background,
    },
  ];

  if (!scroll) {
    return (
      <SafeAreaView
        style={safeStyle}
        edges={['top', 'left', 'right']}
      >
        {topAccessory}
        <View
          style={[
            styles.content,
            styles.nonScrollContent,
            contentStyle,
          ]}
        >
          {children}
        </View>
        {bottomAccessory}
      </SafeAreaView>
    );
  }

  return (
    <KeyboardScrollContext.Provider value={keyboardAware ? onFieldFocus : null}>
      <SafeAreaView
        style={safeStyle}
        edges={['top', 'left', 'right']}
      >
      {topAccessory}
      <ScrollView
        ref={scrollRef}
        contentContainerStyle={[
          styles.content,
          contentStyle,
          keyboardAware && keyboardHeight > 0 ? { paddingBottom: 140 + keyboardHeight } : null,
        ]}
        keyboardShouldPersistTaps="handled"
        showsVerticalScrollIndicator={false}
        scrollEventThrottle={externalThrottle ?? 16}
        onScroll={(event) => {
          scrollOffsetRef.current = Math.max(0, event.nativeEvent.contentOffset.y);
          externalOnScroll?.(event);
        }}
        {...scrollProps}
      >
        {children}
      </ScrollView>
      {bottomAccessory}
      </SafeAreaView>
    </KeyboardScrollContext.Provider>
  );
}

const styles = StyleSheet.create({
  safe: {
    flex: 1,
  },

  content: {
    width: '100%',
    maxWidth: 1200,
    alignSelf: 'center',
    paddingHorizontal: spacing.lg,
    paddingBottom: 120,
    gap: spacing.lg,
  },

  nonScrollContent: {
    flex: 1,
  },
});
