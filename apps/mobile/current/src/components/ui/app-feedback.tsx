import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useRef,
  useState,
  type PropsWithChildren,
} from 'react';
import {
  Modal,
  Pressable,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { Button } from '@/components/ui/button';
import {
  radii,
  spacing,
  typography,
  type AppColors,
} from '@/constants/theme';
import {
  useAppTheme,
  useThemedStyles,
} from '@/theme/app-theme';

type FeedbackTone =
  | 'success'
  | 'info'
  | 'warning'
  | 'danger';

type NoticeOptions = {
  title: string;
  message?: string;
  tone?: FeedbackTone;
  durationMs?: number;
};

type ConfirmOptions = {
  title: string;
  message: string;
  tone?: FeedbackTone;
  confirmLabel?: string;
  cancelLabel?: string;
};

type FeedbackValue = {
  notify: (options: NoticeOptions) => void;
  confirm: (options: ConfirmOptions) => Promise<boolean>;
};

type NoticeState =
  NoticeOptions & {
    id: number;
  };

const FeedbackContext =
  createContext<FeedbackValue | null>(null);

function toneColor(
  theme: AppColors,
  tone: FeedbackTone,
): string {
  if (tone === 'danger') {
    return theme.danger;
  }

  if (tone === 'warning') {
    return theme.warning;
  }

  if (tone === 'info') {
    return theme.info;
  }

  return theme.success;
}

export function AppFeedbackProvider({
  children,
}: PropsWithChildren) {
  const {
    colors: themeColors,
  } = useAppTheme();

  const styles =
    useThemedStyles(createStyles);

  const insets =
    useSafeAreaInsets();

  const [notice, setNotice] =
    useState<NoticeState | null>(null);

  const [dialog, setDialog] =
    useState<ConfirmOptions | null>(null);

  const noticeTimer =
    useRef<ReturnType<typeof setTimeout> | null>(null);

  const noticeId =
    useRef(0);

  const confirmResolver =
    useRef<((accepted: boolean) => void) | null>(null);

  const dismissNotice =
    useCallback(() => {
      if (noticeTimer.current) {
        clearTimeout(
          noticeTimer.current,
        );

        noticeTimer.current = null;
      }

      setNotice(null);
    }, []);

  const notify =
    useCallback(
      (options: NoticeOptions) => {
        if (noticeTimer.current) {
          clearTimeout(
            noticeTimer.current,
          );
        }

        noticeId.current += 1;

        setNotice({
          ...options,
          id: noticeId.current,
          tone:
            options.tone
            ?? 'info',
        });

        noticeTimer.current =
          setTimeout(
            () => {
              noticeTimer.current = null;
              setNotice(null);
            },
            options.durationMs
            ?? 3200,
          );
      },
      [],
    );

  const confirm =
    useCallback(
      (options: ConfirmOptions) =>
        new Promise<boolean>(
          (resolve) => {
            if (
              confirmResolver.current
            ) {
              confirmResolver.current(
                false,
              );
            }

            confirmResolver.current =
              resolve;

            setDialog({
              ...options,
              tone:
                options.tone
                ?? 'info',
            });
          },
        ),
      [],
    );

  const finishConfirm =
    useCallback(
      (accepted: boolean) => {
        const resolve =
          confirmResolver.current;

        confirmResolver.current = null;
        setDialog(null);

        resolve?.(accepted);
      },
      [],
    );

  useEffect(() => {
    return () => {
      if (noticeTimer.current) {
        clearTimeout(
          noticeTimer.current,
        );
      }

      confirmResolver.current?.(
        false,
      );

      confirmResolver.current = null;
    };
  }, []);

  const value =
    useMemo<FeedbackValue>(
      () => ({
        notify,
        confirm,
      }),
      [
        confirm,
        notify,
      ],
    );

  const noticeTone =
    notice?.tone
    ?? 'info';

  const dialogTone =
    dialog?.tone
    ?? 'info';

  return (
    <FeedbackContext.Provider
      value={value}
    >
      <View style={styles.host}>
        {children}

        {notice ? (
          <View
            pointerEvents="box-none"
            style={[
              styles.noticeLayer,
              {
                paddingTop:
                  insets.top
                  + spacing.md,
              },
            ]}
          >
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Zatvori obaveštenje"
              onPress={dismissNotice}
              style={styles.notice}
            >
              <View
                style={[
                  styles.noticeAccent,
                  {
                    backgroundColor:
                      toneColor(
                        themeColors,
                        noticeTone,
                      ),
                  },
                ]}
              />

              <View
                style={styles.noticeCopy}
              >
                <Text
                  style={styles.noticeTitle}
                >
                  {notice.title}
                </Text>

                {notice.message ? (
                  <Text
                    style={styles.noticeMessage}
                  >
                    {notice.message}
                  </Text>
                ) : null}
              </View>
            </Pressable>
          </View>
        ) : null}
      </View>

      <Modal
        transparent
        visible={dialog !== null}
        animationType="fade"
        statusBarTranslucent
        onRequestClose={() =>
          finishConfirm(false)
        }
      >
        <View
          accessibilityViewIsModal
          style={styles.modalRoot}
        >
          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Zatvori dijalog"
            onPress={() =>
              finishConfirm(false)
            }
            style={styles.scrim}
          />

          {dialog ? (
            <View style={styles.dialog}>
              <View
                style={[
                  styles.dialogAccent,
                  {
                    backgroundColor:
                      toneColor(
                        themeColors,
                        dialogTone,
                      ),
                  },
                ]}
              />

              <Text
                style={styles.dialogTitle}
              >
                {dialog.title}
              </Text>

              <Text
                style={styles.dialogMessage}
              >
                {dialog.message}
              </Text>

              <View style={styles.actions}>
                <Button
                  variant={
                    dialogTone === 'danger'
                      ? 'danger'
                      : 'primary'
                  }
                  onPress={() =>
                    finishConfirm(true)
                  }
                >
                  {
                    dialog.confirmLabel
                    ?? 'Potvrdi'
                  }
                </Button>

                <Button
                  variant="ghost"
                  onPress={() =>
                    finishConfirm(false)
                  }
                >
                  {
                    dialog.cancelLabel
                    ?? 'Odustani'
                  }
                </Button>
              </View>
            </View>
          ) : null}
        </View>
      </Modal>
    </FeedbackContext.Provider>
  );
}

export function useAppFeedback():
  FeedbackValue {
  const value =
    useContext(
      FeedbackContext,
    );

  if (!value) {
    throw new Error(
      'useAppFeedback mora biti korišćen unutar AppFeedbackProvider-a.',
    );
  }

  return value;
}

function createStyles(
  theme: AppColors,
) {
  return StyleSheet.create({
    host: {
      flex: 1,
    },

    noticeLayer: {
      position: 'absolute',
      top: 0,
      left: 0,
      right: 0,
      zIndex: 1000,
      elevation: 30,
      paddingHorizontal:
        spacing.lg,
    },

    notice: {
      minHeight: 76,
      flexDirection: 'row',
      alignItems: 'stretch',
      gap: spacing.md,
      padding: spacing.md,
      borderWidth: 1,
      borderColor:
        theme.line,
      borderRadius:
        radii.xl,
      backgroundColor:
        theme.surface,
      shadowColor:
        theme.black,
      shadowOpacity: 0.18,
      shadowRadius: 18,
      shadowOffset: {
        width: 0,
        height: 10,
      },
      elevation: 14,
    },

    noticeAccent: {
      width: 5,
      borderRadius:
        radii.pill,
    },

    noticeCopy: {
      flex: 1,
      justifyContent:
        'center',
      gap: spacing.xs,
    },

    noticeTitle: {
      ...typography.h3,
      color: theme.ink,
    },

    noticeMessage: {
      ...typography.small,
      color: theme.muted,
    },

    modalRoot: {
      flex: 1,
      alignItems: 'center',
      justifyContent:
        'center',
      padding: spacing.xl,
    },

    scrim: {
      ...StyleSheet.absoluteFill,
      backgroundColor:
        theme.black,
      opacity: 0.58,
    },

    dialog: {
      width: '100%',
      maxWidth: 460,
      overflow: 'hidden',
      padding: spacing.xl,
      borderWidth: 1,
      borderColor:
        theme.line,
      borderRadius:
        radii.xl,
      backgroundColor:
        theme.surface,
      shadowColor:
        theme.black,
      shadowOpacity: 0.24,
      shadowRadius: 28,
      shadowOffset: {
        width: 0,
        height: 16,
      },
      elevation: 24,
      gap: spacing.md,
    },

    dialogAccent: {
      width: 56,
      height: 6,
      borderRadius:
        radii.pill,
      marginBottom:
        spacing.xs,
    },

    dialogTitle: {
      ...typography.h2,
      color: theme.ink,
    },

    dialogMessage: {
      ...typography.body,
      color: theme.muted,
    },

    actions: {
      gap: spacing.sm,
      marginTop:
        spacing.sm,
    },
  });
}
