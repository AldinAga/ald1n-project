import { router } from 'expo-router';
import { Image, Pressable, StyleSheet, Text, View } from 'react-native';
import { Screen } from '@/components/layout/screen';
import { Button } from '@/components/ui/button';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Card } from '@/components/ui/card';
import { Glyph } from '@/components/ui/glyph';
import { UnavailableState } from '@/components/ui/states';
import { radii, spacing, typography, type AppColors } from '@/constants/theme';
import { useAppTheme, useThemedStyles } from '@/theme/app-theme';
import { useAuth } from '@/features/auth/auth-provider';
import { useCart } from '@/features/cart/cart-provider';
import { formatMoney } from '@/lib/formatters';

export default function CartScreen() {
  const { colors: themeColors } = useAppTheme();
  const styles = useThemedStyles(createStyles);
  const feedback = useAppFeedback();

  const { hasFeature } = useAuth();
  const { items, itemCount, setQuantity, removeItem, clearCart } = useCart();
  const allowed = hasFeature('order_create');

  if (!allowed) return <UnavailableState title="Kreiranje porudžbine nije dostupno" />;

  const totals = items.reduce<Record<string, number>>((result, item) => {
    if (!item.price) return result;
    result[item.price.currency] = (result[item.price.currency] ?? 0) + item.price.amount * item.quantity;
    return result;
  }, {});
  const confirmClear = () => {
    void (async () => {
      const confirmed = await feedback.confirm({
        tone: 'danger',
        title: 'Isprazni korpu',
        message: 'Ukloniti sve stavke iz korpe?',
        confirmLabel: 'Isprazni',
        cancelLabel: 'Ne'
      });

      if (confirmed) {
        clearCart();
      }
    })();
  };

  return (
    <Screen>
      <View style={styles.topRow}>
        <Pressable onPress={() => router.back()}><Text style={styles.back}>‹ Nazad</Text></Pressable>
        {items.length ? <Pressable onPress={confirmClear}><Text style={styles.clear}>Isprazni</Text></Pressable> : null}
      </View>

      <View>
        <Text style={styles.eyebrow}>KUPOVINA</Text>
        <Text style={styles.title}>Korpa</Text>
        <Text style={styles.subtitle}>{itemCount} {itemCount === 1 ? 'komad' : 'komada'} u korpi</Text>
      </View>

      {!items.length ? (
        <Card muted style={styles.empty}>
          <View style={styles.emptyIcon}><Glyph name="cart" size={30} color={themeColors.primary} /></View>
          <Text style={styles.emptyTitle}>Korpa je prazna</Text>
          <Text style={styles.emptyCopy}>Otvori katalog, izaberi proizvod i dodaj željenu količinu.</Text>
          <Button variant="secondary" onPress={() => router.replace('/catalog')}>Otvori katalog</Button>
        </Card>
      ) : (
        <>
          <View style={styles.items}>
            {items.map((item) => (
              <Card key={item.key} style={styles.itemCard}>
                <View style={styles.itemTop}>
                  <View style={styles.imageWrap}>
                    {item.imageUrl ? <Image source={{ uri: item.imageUrl }} style={styles.image} resizeMode="contain" /> : <Glyph name="box" size={28} color={themeColors.primary} />}
                  </View>
                  <View style={styles.itemCopy}>
                    <Text style={styles.itemName}>{item.productName}</Text>
                    <Text style={styles.itemMeta}>{item.sku}</Text>
                    <Text style={styles.itemPrice}>{item.price ? formatMoney(item.price.amount, item.price.currency) : 'Cena nije prikazana'}</Text>
                  </View>
                </View>

                <View style={styles.quantityRow}>
                  <Text style={styles.stock}>Dostupno: {item.maxQuantity}</Text>
                  <View style={styles.stepper}>
                    <Pressable
                      accessibilityRole="button"
                      accessibilityLabel="Smanji količinu"
                      onPress={() => item.quantity > 1 ? setQuantity(item.key, item.quantity - 1) : removeItem(item.key)}
                      style={styles.stepButton}
                    ><Text style={styles.stepText}>−</Text></Pressable>
                    <Text style={styles.quantity}>{item.quantity}</Text>
                    <Pressable
                      accessibilityRole="button"
                      accessibilityLabel="Povećaj količinu"
                      disabled={item.quantity >= item.maxQuantity}
                      onPress={() => setQuantity(item.key, item.quantity + 1)}
                      style={[styles.stepButton, item.quantity >= item.maxQuantity && styles.disabled]}
                    ><Text style={styles.stepText}>+</Text></Pressable>
                  </View>
                  <Pressable onPress={() => removeItem(item.key)}><Text style={styles.remove}>Ukloni</Text></Pressable>
                </View>
              </Card>
            ))}
          </View>

          <Card muted>
            <Text style={styles.summaryTitle}>Pregled korpe</Text>
            {Object.entries(totals).map(([currency, amount]) => (
              <View key={currency} style={styles.summaryRow}>
                <Text style={styles.summaryLabel}>Procena · {currency}</Text>
                <Text style={styles.summaryValue}>{formatMoney(amount, currency)}</Text>
              </View>
            ))}
            {!Object.keys(totals).length ? <Text style={styles.note}>Cene za ovaj nalog nisu prikazane. Server će obračunati porudžbinu pri potvrdi.</Text> : null}
            <Text style={styles.note}>Konačni RSD iznos, lager i kurs proveravaju se transakcijski na serveru pri kreiranju porudžbine.</Text>
          </Card>

          <Button onPress={() => router.push('/checkout')}>Nastavi na porudžbinu</Button>
          <Button variant="ghost" onPress={() => router.push('/catalog')}>Dodaj još proizvoda</Button>
        </>
      )}
    </Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
  topRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
  clear: { ...typography.label, color: theme.danger, paddingVertical: spacing.sm },
  eyebrow: { ...typography.small, color: theme.primary, letterSpacing: 1.2, fontWeight: '800' },
  title: { ...typography.h1, color: theme.ink, marginTop: spacing.xs },
  subtitle: { ...typography.body, color: theme.muted, marginTop: spacing.xs },
  empty: { alignItems: 'center', gap: spacing.md, paddingVertical: spacing.xxxl },
  emptyIcon: { width: 62, height: 62, borderRadius: 22, backgroundColor: theme.primarySoft, alignItems: 'center', justifyContent: 'center' },
  emptyTitle: { ...typography.h2, color: theme.ink, textAlign: 'center' },
  emptyCopy: { ...typography.body, color: theme.muted, textAlign: 'center', maxWidth: 320 },
  items: { gap: spacing.md },
  itemCard: { gap: spacing.md },
  itemTop: { flexDirection: 'row', gap: spacing.md },
  imageWrap: { width: 82, height: 82, borderRadius: radii.lg, backgroundColor: theme.surfaceMuted, alignItems: 'center', justifyContent: 'center', overflow: 'hidden' },
  image: { width: '100%', height: '100%' },
  itemCopy: { flex: 1, justifyContent: 'center' },
  itemName: { ...typography.h3, color: theme.ink },
  itemMeta: { ...typography.small, color: theme.muted, marginTop: 3 },
  itemPrice: { ...typography.label, color: theme.primaryDark, marginTop: spacing.sm },
  quantityRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.md, borderTopWidth: 1, borderTopColor: theme.line, paddingTop: spacing.md },
  stock: { ...typography.small, color: theme.muted, flex: 1 },
  stepper: { flexDirection: 'row', alignItems: 'center', borderWidth: 1, borderColor: theme.line, borderRadius: radii.md, overflow: 'hidden' },
  stepButton: { width: 38, height: 38, alignItems: 'center', justifyContent: 'center', backgroundColor: theme.surfaceMuted },
  stepText: { fontSize: 20, lineHeight: 23, color: theme.primaryDark, fontWeight: '800' },
  quantity: { minWidth: 36, textAlign: 'center', ...typography.label, color: theme.ink },
  disabled: { opacity: 0.35 },
  remove: { ...typography.small, color: theme.danger, fontWeight: '700' },
  summaryTitle: { ...typography.h3, color: theme.ink, marginBottom: spacing.sm },
  summaryRow: { flexDirection: 'row', justifyContent: 'space-between', gap: spacing.lg, paddingVertical: spacing.sm },
  summaryLabel: { ...typography.small, color: theme.muted },
  summaryValue: { ...typography.label, color: theme.ink },
  note: { ...typography.small, color: theme.muted, marginTop: spacing.sm }
});
}
