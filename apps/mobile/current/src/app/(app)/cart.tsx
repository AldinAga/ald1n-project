import { Image } from 'expo-image';
import { router } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { Screen } from '@/components/layout/screen';
import { Button } from '@/components/ui/button';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Glyph } from '@/components/ui/glyph';
import { UnavailableState } from '@/components/ui/states';
import { radii, spacing, typography, type AppColors } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';
import { useCart } from '@/features/cart/cart-provider';
import { useMoneyPresentation } from '@/features/preferences/money-presentation';
import { useAppTheme, useThemedStyles } from '@/theme/app-theme';

export default function CartScreen() {
  const { colors: themeColors } = useAppTheme();
  const styles = useThemedStyles(createStyles);
  const feedback = useAppFeedback();
  const { formatPrimaryMoney, primaryCurrency } = useMoneyPresentation();
  const { hasFeature } = useAuth();
  const { items, itemCount, setQuantity, removeItem, clearCart } = useCart();
  const allowed = hasFeature('order_create');

  if (!allowed) return <UnavailableState title="Kreiranje porudžbine nije dostupno" />;

  const totals: Record<string, number> = {};
  for (const item of items) {
    if (!item.price) continue;
    totals[item.price.currency] = (totals[item.price.currency] ?? 0) + item.price.amount * item.quantity;
  }

  const confirmClear = () => {
    void (async () => {
      const confirmed = await feedback.confirm({
        tone: 'danger',
        title: 'Isprazni korpu',
        message: 'Ukloniti sve stavke iz korpe?',
        confirmLabel: 'Isprazni',
        cancelLabel: 'Ne',
      });

      if (confirmed) clearCart();
    })();
  };

  return (
    <Screen>
      {/* MOBILE_BUILD16_CART_REDESIGN_BATCH131 */}
      <View style={styles.topRow}>
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Nazad"
          onPress={() => router.back()}
          style={({ pressed }) => [styles.backButton, pressed && styles.pressed]}
        >
          <Glyph name="arrow" size={20} color={themeColors.primary} style={styles.backGlyph} />
          <Text style={styles.backLabel}>Nazad</Text>
        </Pressable>

        {items.length ? (
          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Isprazni korpu"
            onPress={confirmClear}
            style={({ pressed }) => [styles.clearButton, pressed && styles.pressed]}
          >
            <Text style={styles.clear}>Isprazni</Text>
          </Pressable>
        ) : null}
      </View>

      <View style={styles.identity}>
        <Text style={styles.eyebrow}>KUPOVINA</Text>
        <Text style={styles.title}>Korpa</Text>
        <Text style={styles.subtitle}>
          {itemCount} {itemCount === 1 ? 'komad' : 'komada'} spremno za proveru.
        </Text>
      </View>

      {!items.length ? (
        <View style={styles.emptySurface}>
          <View style={styles.emptyIcon}>
            <Glyph name="cart" size={30} color={themeColors.primary} />
          </View>
          <Text style={styles.emptyTitle}>Korpa je prazna</Text>
          <Text style={styles.emptyCopy}>
            Otvori katalog, izaberi proizvod i dodaj željenu količinu.
          </Text>
          <Button variant="secondary" onPress={() => router.replace('/catalog')}>
            Otvori katalog
          </Button>
        </View>
      ) : (
        <>
          <View style={styles.itemsSurface}>
            {items.map((item, index) => (
              <View
                key={item.key}
                style={[
                  styles.itemRow,
                  index < items.length - 1 && styles.itemDivider,
                ]}
              >
                <View style={styles.itemTop}>
                  <View style={styles.imageWrap}>
                    {item.imageUrl ? (
                      <Image
                        source={{ uri: item.imageUrl }}
                        style={styles.image}
                        contentFit="contain"
                        cachePolicy="memory-disk"
                        transition={80}
                      />
                    ) : (
                      <Glyph name="box" size={28} color={themeColors.primary} />
                    )}
                  </View>

                  <View style={styles.itemCopy}>
                    <Text style={styles.itemName}>{item.productName}</Text>
                    <Text style={styles.itemMeta}>{item.sku}</Text>
                    <Text style={styles.itemPrice}>
                      {item.price
                        ? formatPrimaryMoney(item.price.amount, item.price.currency)
                        : 'Cena nije prikazana'}
                    </Text>
                  </View>
                </View>

                <View style={styles.quantityRow}>
                  <View style={styles.stockWrap}>
                    <Text style={styles.stockLabel}>Dostupno</Text>
                    <Text style={styles.stockValue}>{item.maxQuantity}</Text>
                  </View>

                  <View style={styles.stepper}>
                    <Pressable
                      accessibilityRole="button"
                      accessibilityLabel="Smanji količinu"
                      onPress={() => item.quantity > 1
                        ? setQuantity(item.key, item.quantity - 1)
                        : removeItem(item.key)}
                      style={({ pressed }) => [styles.stepButton, pressed && styles.stepPressed]}
                    >
                      <Glyph name="remove" size={18} color={themeColors.primaryDark} />
                    </Pressable>

                    <Text style={styles.quantity}>{item.quantity}</Text>

                    <Pressable
                      accessibilityRole="button"
                      accessibilityLabel="Povećaj količinu"
                      disabled={item.quantity >= item.maxQuantity}
                      onPress={() => setQuantity(item.key, item.quantity + 1)}
                      style={({ pressed }) => [
                        styles.stepButton,
                        item.quantity >= item.maxQuantity && styles.disabled,
                        pressed && item.quantity < item.maxQuantity && styles.stepPressed,
                      ]}
                    >
                      <Glyph name="add" size={18} color={themeColors.primaryDark} />
                    </Pressable>
                  </View>

                  <Pressable
                    accessibilityRole="button"
                    accessibilityLabel={`Ukloni ${item.productName}`}
                    onPress={() => removeItem(item.key)}
                    style={({ pressed }) => pressed && styles.pressed}
                  >
                    <Text style={styles.removeLink}>Ukloni</Text>
                  </Pressable>
                </View>
              </View>
            ))}
          </View>

          <View style={styles.summarySurface}>
            <View style={styles.summaryHeading}>
              <View>
                <Text style={styles.summaryEyebrow}>PREGLED</Text>
                <Text style={styles.summaryTitle}>Pregled korpe</Text>
              </View>
              <View style={styles.countBadge}>
                <Text style={styles.countBadgeText}>{itemCount}</Text>
              </View>
            </View>

            {Object.entries(totals).map(([currency, amount]) => (
              <View key={currency} style={styles.summaryRow}>
                <Text style={styles.summaryLabel}>Procena · primarno {primaryCurrency}</Text>
                <Text style={styles.summaryValue}>{formatPrimaryMoney(amount, currency)}</Text>
              </View>
            ))}

            {!Object.keys(totals).length ? (
              <Text style={styles.note}>
                Cene za ovaj nalog nisu prikazane. Server će obračunati porudžbinu pri potvrdi.
              </Text>
            ) : null}

            <View style={styles.authorityNote}>
              <Glyph name="info" size={18} color={themeColors.primary} />
              <Text style={styles.noteCopy}>
                Konačni RSD iznos, lager i kurs proveravaju se transakcijski na serveru pri kreiranju porudžbine.
              </Text>
            </View>
          </View>

          <Button onPress={() => router.push('/checkout')}>
            Nastavi na porudžbinu
          </Button>
          <Button variant="ghost" onPress={() => router.push('/catalog')}>
            Dodaj još proizvoda
          </Button>
        </>
      )}
    </Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    topRow: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      minHeight: 44,
    },
    backButton: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: spacing.xs,
      minHeight: 40,
    },
    backGlyph: { transform: [{ rotate: '180deg' }] },
    backLabel: { ...typography.label, color: theme.primary },
    clearButton: { minHeight: 40, justifyContent: 'center' },
    clear: { ...typography.label, color: theme.danger },
    pressed: { opacity: 0.68 },
    identity: { gap: spacing.xs },
    eyebrow: {
      ...typography.small,
      color: theme.primary,
      letterSpacing: 1.2,
      fontWeight: '800',
    },
    title: { ...typography.h1, color: theme.ink },
    subtitle: { ...typography.body, color: theme.muted },
    emptySurface: {
      alignItems: 'center',
      gap: spacing.md,
      paddingHorizontal: spacing.xl,
      paddingVertical: spacing.xxxl,
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.xl,
      backgroundColor: theme.surface,
    },
    emptyIcon: {
      width: 62,
      height: 62,
      borderRadius: radii.xl,
      backgroundColor: theme.primarySoft,
      alignItems: 'center',
      justifyContent: 'center',
    },
    emptyTitle: { ...typography.h2, color: theme.ink, textAlign: 'center' },
    emptyCopy: {
      ...typography.body,
      color: theme.muted,
      textAlign: 'center',
      maxWidth: 320,
    },
    itemsSurface: {
      overflow: 'hidden',
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.xl,
      backgroundColor: theme.surface,
    },
    itemRow: {
      gap: spacing.md,
      padding: spacing.lg,
    },
    itemDivider: {
      borderBottomWidth: StyleSheet.hairlineWidth,
      borderBottomColor: theme.line,
    },
    itemTop: { flexDirection: 'row', gap: spacing.md },
    imageWrap: {
      width: 78,
      height: 78,
      borderRadius: radii.lg,
      backgroundColor: theme.surfaceMuted,
      alignItems: 'center',
      justifyContent: 'center',
      overflow: 'hidden',
    },
    image: { width: '100%', height: '100%' },
    itemCopy: { flex: 1, justifyContent: 'center', minWidth: 0 },
    itemName: { ...typography.h3, color: theme.ink },
    itemMeta: { ...typography.small, color: theme.muted, marginTop: 2 },
    itemPrice: {
      ...typography.label,
      color: theme.primaryDark,
      marginTop: spacing.sm,
      fontVariant: ['tabular-nums'],
    },
    quantityRow: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: spacing.md,
    },
    stockWrap: { flex: 1 },
    stockLabel: { ...typography.small, color: theme.muted },
    stockValue: {
      ...typography.label,
      color: theme.ink,
      marginTop: 1,
      fontVariant: ['tabular-nums'],
    },
    stepper: {
      flexDirection: 'row',
      alignItems: 'center',
      overflow: 'hidden',
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.md,
      backgroundColor: theme.surfaceMuted,
    },
    stepButton: {
      width: 38,
      height: 38,
      alignItems: 'center',
      justifyContent: 'center',
    },
    stepPressed: { backgroundColor: theme.primarySoft },
    quantity: {
      minWidth: 38,
      textAlign: 'center',
      ...typography.label,
      color: theme.ink,
      fontVariant: ['tabular-nums'],
    },
    disabled: { opacity: 0.32 },
    removeLink: { ...typography.small, color: theme.danger, fontWeight: '700' },
    summarySurface: {
      gap: spacing.md,
      padding: spacing.lg,
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.xl,
      backgroundColor: theme.surfaceContainer,
    },
    summaryHeading: {
      flexDirection: 'row',
      justifyContent: 'space-between',
      alignItems: 'center',
      gap: spacing.md,
    },
    summaryEyebrow: {
      ...typography.small,
      color: theme.primary,
      fontWeight: '800',
      letterSpacing: 1,
    },
    summaryTitle: { ...typography.h3, color: theme.ink, marginTop: 2 },
    countBadge: {
      minWidth: 34,
      height: 34,
      borderRadius: radii.md,
      backgroundColor: theme.primarySoft,
      alignItems: 'center',
      justifyContent: 'center',
      paddingHorizontal: spacing.sm,
    },
    countBadgeText: {
      ...typography.label,
      color: theme.primaryDark,
      fontVariant: ['tabular-nums'],
    },
    summaryRow: {
      flexDirection: 'row',
      justifyContent: 'space-between',
      gap: spacing.lg,
      paddingTop: spacing.sm,
      borderTopWidth: StyleSheet.hairlineWidth,
      borderTopColor: theme.line,
    },
    summaryLabel: { ...typography.small, color: theme.muted, flex: 1 },
    summaryValue: {
      ...typography.label,
      color: theme.ink,
      fontVariant: ['tabular-nums'],
      textAlign: 'right',
    },
    note: { ...typography.small, color: theme.muted },
    authorityNote: {
      flexDirection: 'row',
      alignItems: 'flex-start',
      gap: spacing.sm,
      paddingTop: spacing.sm,
      borderTopWidth: StyleSheet.hairlineWidth,
      borderTopColor: theme.line,
    },
    noteCopy: { ...typography.small, color: theme.muted, flex: 1 },
  });
}
