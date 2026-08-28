import { memo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { router } from 'expo-router';
import { Pressable, RefreshControl, StyleSheet, Text, View } from 'react-native';
import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { Card } from '@/components/ui/card';
import { Glyph } from '@/components/ui/glyph';
import { Pill } from '@/components/ui/pill';
import { LoadingState } from '@/components/ui/states';
import { radii, spacing, typography, type AppColors } from '@/constants/theme';
import { hasAdminAccess } from '@/features/admin/admin-access';
import { apiAdmin } from '@/features/admin/admin-api';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import {
  apiAdminReports,
  type AdminReportTrendPoint,
} from '@/features/admin/reports-admin-api';
import { useAuth, useNotificationUnread } from '@/features/auth/auth-provider';
import { useMoneyPresentation } from '@/features/preferences/money-presentation';
import { useCart } from '@/features/cart/cart-provider';
import { useAppTheme, useThemedStyles } from '@/theme/app-theme';

// MOBILE_V0_6_HOME_DASHBOARD_PARITY_V1

// MOBILE_V0_7_COMMISSION_RELEASE_CRITICAL_VISIBILITY
const HOME_REPORT_PARAMS = {
  report_type: 'management_summary' as const,
};

export default function HomeScreen() {
  const { colors: themeColors } = useAppTheme();
  const styles = useThemedStyles(createStyles);
  const { formatPrimaryMoney } = useMoneyPresentation();

  const { bootstrap, refreshBootstrap, hasFeature, can } = useAuth();
  const { itemCount } = useCart();
  const user = bootstrap?.user;
  const adminAllowed = hasAdminAccess({
    permissions: bootstrap?.permissions ?? [],
    roleSlug: bootstrap?.user.role?.slug,
  });
  const reportsAllowed = can('reports.view');
  const isSuperAdmin = bootstrap?.user.role?.slug === 'superadmin';
  const foundationQuery = useQuery({
    queryKey: adminQueryKeys.foundation(),
    queryFn: apiAdmin.foundation,
    enabled: isSuperAdmin,
    staleTime: 60_000,
  });
  const reportQuery = useQuery({
    queryKey: adminQueryKeys.managementReport(HOME_REPORT_PARAMS),
    queryFn: () => apiAdminReports.management(HOME_REPORT_PARAMS),
    enabled: reportsAllowed,
    staleTime: 60_000,
  });
  const report = reportQuery.data?.data;
  const inventoryValuation = isSuperAdmin ? foundationQuery.data?.inventory_valuation ?? null : null;

  type HomeAction = {
    title: string;
    copy: string;
    glyph: 'catalog' | 'cart' | 'orders' | 'check' | 'bell';
    route: '/catalog' | '/admin/catalog/create' | '/cart' | '/orders' | '/commissions' | '/warranties' | '/after-sales' | '/portal/messages' | '/admin';
  };

  // MOBILE_V0_9_HOME_INFORMATION_ARCHITECTURE_BATCH5C
  const quickActions = [
    hasFeature('catalog') ? { title: 'Otvori katalog', copy: 'Pretraži aktivne proizvode', glyph: 'catalog' as const, route: '/catalog' as const } : null,
    can('catalog.manage_products') ? { title: 'Dodaj artikal', copy: 'Kreiraj novi artikal', glyph: 'catalog' as const, route: '/admin/catalog/create' as const } : null,
    hasFeature('order_create') ? { title: 'Korpa', copy: itemCount ? `${itemCount} komada spremno` : 'Pripremi novu porudžbinu', glyph: 'cart' as const, route: '/cart' as const } : null,
  ].filter(Boolean) as HomeAction[];

  const myActivities = [
    hasFeature('orders') ? { title: 'Moje porudžbine', copy: 'Status, plaćanja i detalji porudžbina', glyph: 'orders' as const, route: '/orders' as const } : null,
    can('commissions.view_own') ? { title: 'Moje provizije', copy: 'Obračun i status isplate', glyph: 'orders' as const, route: '/commissions' as const } : null,
    can('warranties.view_own') ? { title: 'Moje garancije', copy: 'Garantni listovi i održavanje', glyph: 'check' as const, route: '/warranties' as const } : null,
    can('after_sales.view_own') ? { title: 'Reklamacije i servis', copy: 'Postprodajni slučajevi i komunikacija', glyph: 'check' as const, route: '/after-sales' as const } : null,
    hasFeature('customer_portal') ? { title: 'Poruke podršci', copy: 'Razgovori sa podrškom i pitanja uz porudžbine', glyph: 'bell' as const, route: '/portal/messages' as const } : null,
  ].filter(Boolean) as HomeAction[];

  const adminActions = [
    adminAllowed ? { title: 'Administracija', copy: 'Otvori grupisani administratorski radni prostor', glyph: 'check' as const, route: '/admin' as const } : null,
  ].filter(Boolean) as HomeAction[];

  const refreshHome = () => {
    void refreshBootstrap();
    if (reportsAllowed) void reportQuery.refetch();
    if (isSuperAdmin) void foundationQuery.refetch();
  };

  return (
    <Screen
      refreshControl={(
        <RefreshControl
          refreshing={(reportsAllowed && reportQuery.isFetching) || (isSuperAdmin && foundationQuery.isFetching)}
          onRefresh={refreshHome}
          tintColor={themeColors.primary}
        />
      )}
    >
      <PageHeader title="Pregled" eyebrow="Ald1n Mobile" name={user?.name} />

      <View style={styles.hero}>
        <View style={styles.heroOrb} />
        <Pill tone="warning">AKTIVAN RADNI PROSTOR</Pill>
        <Text style={styles.greeting}>
          Zdravo, {user?.first_name ?? user?.name?.split(' ')[0] ?? 'korisniče'}.
        </Text>
        <Text style={styles.heroCopy}>
          Najvažniji poslovni podaci su spremni za mobilni rad.
        </Text>
        <View style={styles.heroMeta}>
          <Text style={styles.heroMetaLabel}>Uloga</Text>
          <Text style={styles.heroMetaValue}>{user?.role?.name ?? 'Korisnik'}</Text>
        </View>
      </View>

      {/* MOBILE_V0_9_HOME_FOCUS_SECTION_BATCH5C */}
      <HomeFocusCard itemCount={itemCount} />

      {reportsAllowed ? (
        <View style={styles.dashboardSection}>
          <View style={styles.sectionHead}>
            <View style={styles.sectionCopy}>
              <Text style={styles.sectionEyebrow}>POSLOVNI PREGLED</Text>
              <Text style={styles.sectionTitle}>Dashboard</Text>
              <Text style={styles.sectionSubtitle}>
                {report?.period_label ?? 'Tekući mesec'}
              </Text>
            </View>
            <Pressable
              accessibilityRole="button"
              onPress={() => router.push('/admin/reports')}
              style={({ pressed }) => pressed && styles.pressed}
            >
              <Text style={styles.sectionLink}>Detalji ›</Text>
            </Pressable>
          </View>

          {reportQuery.isLoading ? (
            <LoadingState label="Učitavanje poslovnih podataka" />
          ) : reportQuery.isError ? (
            <Card muted style={styles.stateCard}>
              <Text style={styles.stateTitle}>Dashboard trenutno nije dostupan</Text>
              <Text style={styles.stateCopy}>Ostatak aplikacije radi normalno. Povuci nadole za novi pokušaj.</Text>
            </Card>
          ) : report ? (
            <>
              <View style={styles.kpiGrid}>
                <DashboardMetric
                  label="Prihod ovog meseca"
                  value={formatPrimaryMoney(report.summary.revenue_rsd, 'RSD')}
                  meta={`${report.summary.orders_count} završenih porudžbina`}
                  tone="primary"
                />
                <DashboardMetric
                  label="Bruto dobit"
                  value={formatPrimaryMoney(report.summary.gross_profit_rsd, 'RSD')}
                  meta={`Marža ${report.summary.gross_margin_percent.toFixed(1)}%`}
                  tone="success"
                />
                <DashboardMetric
                  label="Neto doprinos"
                  value={formatPrimaryMoney(report.summary.net_contribution_rsd, 'RSD')}
                  meta={`Pokrivenost troška ${report.summary.cost_coverage_percent.toFixed(1)}%`}
                  tone="accent"
                />
                <DashboardMetric
                  label="Otvoreno potraživanje"
                  value={formatPrimaryMoney(report.summary.outstanding_rsd, 'RSD')}
                  meta={`${report.receivables.open_orders} otvorenih porudžbina`}
                  tone="danger"
                />
              {/* MOBILE_V0_9_SUPERADMIN_HOME_INVENTORY_VALUE_KPIS */}
              {inventoryValuation ? (
                <>
                  <DashboardMetric
                    label="Vrednost lagera po nabavnoj ceni"
                    value={formatPrimaryMoney(inventoryValuation.purchase_value_rsd, 'RSD')}
                    meta={inventoryValuation.missing_cost_items === 0 ? 'Sve stavke sa lagerom imaju nabavnu cenu' : String(inventoryValuation.missing_cost_items) + ' artikala sa lagerom bez nabavne cene'}
                    tone="accent"
                    route="/admin/inventory"
                  />
                  <DashboardMetric
                    label="Vrednost robe po prodajnoj ceni"
                    value={formatPrimaryMoney(inventoryValuation.sale_value_rsd, 'RSD')}
                    meta={inventoryValuation.missing_sale_value_items === 0 ? 'Prodajna vrednost kompletnog pozitivnog lagera' : String(inventoryValuation.missing_sale_value_items) + ' artikala bez obračunate prodajne vrednosti'}
                    tone="primary"
                    route="/admin/inventory"
                  />
                </>
              ) : null}
              </View>

              <SalesPulse points={report.trend} periodLabel={report.period_label} />

              <Card style={styles.focusCard}>
                <View style={styles.focusHeading}>
                  <View>
                    <Text style={styles.sectionEyebrow}>OPERATIVNI SIGNAL</Text>
                    <Text style={styles.focusTitle}>Operativni signal</Text>
                  </View>
                  <Glyph name="info" size={22} color={themeColors.primary} />
                </View>
                <FocusRow
                  label="Porudžbine"
                  value={`${report.summary.orders_count} završeno · ${report.summary.units_count} komada`}
                  last={false}
                />
                <FocusRow
                  label="Postprodaja"
                  value={`${report.after_sales.open} otvoreno · ${report.after_sales.overdue} preko roka`}
                  last={false}
                />
                <FocusRow
                  label="Lager"
                  value={`${report.inventory.items_count} stavki · ${report.inventory.slow_items} sporo obrtnih`}
                  last
                />
              </Card>
            </>
          ) : null}
        </View>
      ) : (
        <View style={styles.metrics}>
          <HomeUnreadMetric />
          <Metric value={String(bootstrap?.permissions.length ?? 0)} label="Dozvole" tone="accent" />
          <Metric value={bootstrap?.app.api_version ?? 'v1'} label="API" tone="success" />
        </View>
      )}

      <View style={styles.sectionHead}>
        <Text style={styles.sectionTitle}>Brze akcije</Text>
        <Text style={styles.sectionMeta}>{quickActions.length} dostupno</Text>
      </View>
      <View style={styles.actions}>
        {quickActions.map((action) => (
          <Pressable
            key={action.title}
            onPress={() => router.push(action.route)}
            style={({ pressed }) => pressed && styles.pressed}
          >
            <Card style={styles.actionCard}>
              <View style={styles.actionIcon}>
                <Glyph name={action.glyph} size={24} color={themeColors.primary} />
              </View>
              <View style={styles.actionCopy}>
                <Text style={styles.actionTitle}>{action.title}</Text>
                <Text style={styles.actionText}>{action.copy}</Text>
              </View>
              <Glyph name="arrow" size={27} color={themeColors.muted} />
            </Card>
          </Pressable>
        ))}
      </View>

      {/* MOBILE_V0_9_HOME_MY_ACTIVITIES_BATCH5C */}
      <View style={styles.sectionHead}>
        <Text style={styles.sectionTitle}>Moje aktivnosti</Text>
        <Text style={styles.sectionMeta}>{myActivities.length} dostupno</Text>
      </View>
      {myActivities.length > 0 ? (
        <View style={styles.actions}>
          {myActivities.map((action) => (
            <Pressable
              key={action.title}
              onPress={() => router.push(action.route)}
              style={({ pressed }) => pressed && styles.pressed}
            >
              <Card style={styles.actionCard}>
                <View style={styles.actionIcon}>
                  <Glyph name={action.glyph} size={24} color={themeColors.primary} />
                </View>
                <View style={styles.actionCopy}>
                  <Text style={styles.actionTitle}>{action.title}</Text>
                  <Text style={styles.actionText}>{action.copy}</Text>
                </View>
                <Glyph name="arrow" size={27} color={themeColors.muted} />
              </Card>
            </Pressable>
          ))}
        </View>
      ) : (
        <Card muted style={styles.stateCard}>
          <Text style={styles.stateCopy}>Nema dodatnih korisničkih aktivnosti za ovaj nalog.</Text>
        </Card>
      )}

      {/* MOBILE_V0_9_HOME_ADMINISTRATION_BATCH5C */}
      {adminActions.length > 0 ? (
        <>
          <View style={styles.sectionHead}>
            <Text style={styles.sectionTitle}>Administracija</Text>
            <Text style={styles.sectionMeta}>Radni prostor</Text>
          </View>
          <View style={styles.actions}>
            {adminActions.map((action) => (
              <Pressable
                key={action.title}
                onPress={() => router.push(action.route)}
                style={({ pressed }) => pressed && styles.pressed}
              >
                <Card style={styles.actionCard}>
                  <View style={styles.actionIcon}>
                    <Glyph name={action.glyph} size={24} color={themeColors.primary} />
                  </View>
                  <View style={styles.actionCopy}>
                    <Text style={styles.actionTitle}>{action.title}</Text>
                    <Text style={styles.actionText}>{action.copy}</Text>
                  </View>
                  <Glyph name="arrow" size={27} color={themeColors.muted} />
                </Card>
              </Pressable>
            ))}
          </View>
        </>
      ) : null}

      <Card muted style={styles.foundation}>
        <View style={styles.foundationIcon}>
          <Glyph name="check" color={themeColors.success} size={22} />
        </View>
        <View style={{ flex: 1 }}>
          <Text style={styles.foundationTitle}>Sve je sinhronizovano</Text>
          <Text style={styles.foundationCopy}>
            Katalog, porudžbine i poslovna obaveštenja koriste isti bezbedan Ald1n nalog.
          </Text>
        </View>
      </Card>
    </Screen>
  );
}

const HomeFocusCard = memo(function HomeFocusCard({ itemCount }: { itemCount: number }) {
  const unread = useNotificationUnread();
  const styles = useThemedStyles(createStyles);

  return (
    <>
      <View style={styles.sectionHead}>
        <Text style={styles.sectionTitle}>Fokus danas</Text>
        <Text style={styles.sectionMeta}>
          {unread > 0 ? `${unread} novih` : 'Sve pod kontrolom'}
        </Text>
      </View>
      <Card muted style={styles.stateCard}>
        <Text style={styles.stateTitle}>
          {unread > 0 ? 'Proveri nova obaveštenja' : 'Nema novih obaveštenja'}
        </Text>
        <Text style={styles.stateCopy}>
          {itemCount > 0
            ? `${itemCount} stavki čeka u korpi.`
            : 'Katalog, porudžbine i aktivnosti su spremni za rad.'}
        </Text>
      </Card>
    </>
  );
});

const HomeUnreadMetric = memo(function HomeUnreadMetric() {
  const unread = useNotificationUnread();

  return <Metric value={String(unread)} label="Nepročitano" tone="primary" />;
});

function DashboardMetric({
  value,
  label,
  meta,
  tone,
  route = '/admin/reports',
}: {
  value: string;
  label: string;
  meta: string;
  tone: 'primary' | 'accent' | 'success' | 'danger';
  route?: '/admin/reports' | '/admin/inventory';
}) {
  const toneStyles = useThemedStyles(createToneStyles);
  const styles = useThemedStyles(createStyles);

  return (
    <Pressable
      accessibilityRole="button"
      onPress={() => router.push(route)}
      style={({ pressed }) => [styles.kpiPressable, pressed && styles.pressed]}
    >
      <Card style={styles.dashboardMetricCard}>
        <View style={[styles.metricDot, toneStyles[tone]]} />
        <Text style={styles.dashboardMetricLabel}>{label}</Text>
        <Text style={styles.dashboardMetricValue} numberOfLines={2}>{value}</Text>
        <Text style={styles.dashboardMetricMeta}>{meta}</Text>
      </Card>
    </Pressable>
  );
}

function SalesPulse({
  points,
  periodLabel,
}: {
  points: AdminReportTrendPoint[];
  periodLabel: string;
}) {
  const styles = useThemedStyles(createStyles);
  const visible = points.slice(-7);
  const maximum = Math.max(1, ...visible.map((point) => point.revenue_rsd));

  return (
    <Card style={styles.pulseCard}>
      <View style={styles.pulseHeading}>
        <View>
          <Text style={styles.sectionEyebrow}>FINANSIJSKI PULS</Text>
          <Text style={styles.focusTitle}>Trend prodaje</Text>
        </View>
        <Text style={styles.sectionMeta}>{periodLabel}</Text>
      </View>

      {visible.length > 0 ? (
        <View style={styles.chart}>
          {visible.map((point) => {
            const height = Math.max(6, Math.round((point.revenue_rsd / maximum) * 72));
            const label = point.period.length >= 5 ? point.period.slice(-5) : point.period;

            return (
              <View key={point.period} style={styles.chartColumn}>
                <View style={[styles.chartBar, { height }]} />
                <Text style={styles.chartLabel}>{label}</Text>
              </View>
            );
          })}
        </View>
      ) : (
        <Text style={styles.stateCopy}>Trend će se pojaviti kada postoje završene porudžbine.</Text>
      )}
    </Card>
  );
}

function FocusRow({
  label,
  value,
  last,
}: {
  label: string;
  value: string;
  last: boolean;
}) {
  const styles = useThemedStyles(createStyles);

  return (
    <View style={[styles.focusRow, !last && styles.focusRowBorder]}>
      <Text style={styles.focusLabel}>{label}</Text>
      <Text style={styles.focusValue}>{value}</Text>
    </View>
  );
}

function Metric({
  value,
  label,
  tone,
}: {
  value: string;
  label: string;
  tone: 'primary' | 'accent' | 'success';
}) {
  const toneStyles = useThemedStyles(createToneStyles);
  const styles = useThemedStyles(createStyles);

  return (
    <Card style={styles.metricCard}>
      <View style={[styles.metricDot, toneStyles[tone]]} />
      <Text style={styles.metricValue}>{value}</Text>
      <Text style={styles.metricLabel}>{label}</Text>
    </Card>
  );
}

function createToneStyles(theme: AppColors) {
  return StyleSheet.create({
    primary: { backgroundColor: theme.primary },
    accent: { backgroundColor: theme.accent },
    success: { backgroundColor: theme.success },
    danger: { backgroundColor: theme.danger },
  });
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    hero: {
      overflow: 'hidden',
      minHeight: 205,
      borderRadius: 28,
      padding: spacing.xl,
      backgroundColor: theme.hero,
      justifyContent: 'flex-end',
      gap: spacing.md,
    },
    heroOrb: {
      position: 'absolute',
      width: 230,
      height: 230,
      borderRadius: 115,
      backgroundColor: theme.primary,
      right: -88,
      top: -82,
      opacity: 0.5,
    },
    greeting: { ...typography.hero, color: theme.white, maxWidth: 310 },
    heroCopy: { ...typography.body, color: theme.heroMuted, maxWidth: 330 },
    heroMeta: {
      alignSelf: 'flex-start',
      flexDirection: 'row',
      gap: spacing.sm,
      alignItems: 'center',
      marginTop: spacing.xs,
    },
    heroMetaLabel: { ...typography.small, color: theme.heroMuted },
    heroMetaValue: { ...typography.label, color: theme.white },
    dashboardSection: { gap: spacing.md },
    sectionHead: {
      flexDirection: 'row',
      justifyContent: 'space-between',
      alignItems: 'center',
      gap: spacing.md,
    },
    sectionCopy: { flex: 1 },
    sectionEyebrow: {
      ...typography.small,
      color: theme.primary,
      fontWeight: '900',
      letterSpacing: 0.7,
    },
    sectionTitle: { ...typography.h2, color: theme.ink },
    sectionSubtitle: { ...typography.small, color: theme.muted, marginTop: 2 },
    sectionLink: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    sectionMeta: { ...typography.small, color: theme.muted },
    stateCard: { gap: spacing.xs },
    stateTitle: { ...typography.label, color: theme.ink },
    stateCopy: { ...typography.small, color: theme.muted },
    kpiGrid: {
      flexDirection: 'row',
      flexWrap: 'wrap',
      gap: spacing.sm,
    },
    kpiPressable: { width: '48%', flexGrow: 1 },
    dashboardMetricCard: {
      minHeight: 142,
      gap: spacing.xs,
      borderRadius: radii.xl,
    },
    dashboardMetricLabel: { ...typography.small, color: theme.muted },
    dashboardMetricValue: { ...typography.h3, color: theme.ink, marginTop: spacing.xs },
    dashboardMetricMeta: { ...typography.small, color: theme.muted },
    pulseCard: { gap: spacing.md },
    pulseHeading: {
      flexDirection: 'row',
      justifyContent: 'space-between',
      alignItems: 'flex-start',
      gap: spacing.md,
    },
    chart: {
      minHeight: 98,
      flexDirection: 'row',
      alignItems: 'flex-end',
      gap: spacing.sm,
      paddingTop: spacing.sm,
    },
    chartColumn: {
      flex: 1,
      height: 90,
      minWidth: 0,
      alignItems: 'center',
      justifyContent: 'flex-end',
      gap: 5,
    },
    chartBar: {
      width: '72%',
      minHeight: 6,
      borderRadius: radii.pill,
      backgroundColor: theme.primary,
    },
    chartLabel: { fontSize: 9, lineHeight: 12, color: theme.muted },
    focusCard: { gap: 0 },
    focusHeading: {
      flexDirection: 'row',
      justifyContent: 'space-between',
      alignItems: 'center',
      paddingBottom: spacing.sm,
    },
    focusTitle: { ...typography.h3, color: theme.ink, marginTop: 2 },
    focusRow: {
      minHeight: 54,
      flexDirection: 'row',
      justifyContent: 'space-between',
      alignItems: 'center',
      gap: spacing.md,
    },
    focusRowBorder: { borderBottomWidth: 1, borderBottomColor: theme.line },
    focusLabel: { ...typography.small, color: theme.muted },
    focusValue: { ...typography.label, color: theme.ink, flex: 1, textAlign: 'right' },
    metrics: { flexDirection: 'row', gap: spacing.sm },
    metricCard: { flex: 1, padding: spacing.md, gap: 3, borderRadius: radii.lg },
    metricDot: { width: 8, height: 8, borderRadius: 4, marginBottom: spacing.sm },
    metricValue: { ...typography.h2, color: theme.ink },
    metricLabel: { ...typography.small, color: theme.muted },
    actions: { gap: spacing.md },
    pressed: { transform: [{ scale: 0.99 }], opacity: 0.92 },
    actionCard: { flexDirection: 'row', alignItems: 'center', gap: spacing.md },
    actionIcon: {
      width: 48,
      height: 48,
      borderRadius: radii.lg,
      backgroundColor: theme.primarySoft,
      alignItems: 'center',
      justifyContent: 'center',
    },
    actionCopy: { flex: 1 },
    actionTitle: { ...typography.h3, color: theme.ink },
    actionText: { ...typography.small, color: theme.muted, marginTop: 3 },
    foundation: { flexDirection: 'row', gap: spacing.md, alignItems: 'flex-start' },
    foundationIcon: {
      width: 38,
      height: 38,
      borderRadius: radii.md,
      backgroundColor: theme.successSoft,
      alignItems: 'center',
      justifyContent: 'center',
    },
    foundationTitle: { ...typography.label, color: theme.ink },
    foundationCopy: { ...typography.small, color: theme.muted, marginTop: 4 },
  });
}
