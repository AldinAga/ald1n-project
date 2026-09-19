import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { router, type Href } from 'expo-router';
import { StyleSheet, Text, TextInput, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { Card } from '@/components/ui/card';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { OperatorRow } from '@/components/ui/operator-row';
import { radii, spacing, typography, type AppColors } from '@/constants/theme';
import { hasAdminAccess } from '@/features/admin/admin-access';
import { apiAdmin, type AdminModuleKey } from '@/features/admin/admin-api';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import { useAuth } from '@/features/auth/auth-provider';
import { useThemedStyles } from '@/theme/app-theme';

function moneyRsd(value: number): string {
  return `${Number(value).toLocaleString('sr-RS', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} RSD`;
}

// MOBILE_V0_7_ADMIN_HUB_COMMISSION_PRIORITY
// MOBILE_V0_8_SUPERADMIN_INVENTORY_VALUATION_BATCH3
// MOBILE_BUILD16_ADMIN_HUB_FINAL_BATCH132
export default function AdminIndexScreen() {  const styles = useThemedStyles(createStyles);
  const { bootstrap, can } = useAuth();
  const [adminSearch, setAdminSearch] = useState('');

  const allowed = hasAdminAccess({
    permissions: bootstrap?.permissions ?? [],
    roleSlug: bootstrap?.user.role?.slug,
  });

  const query = useQuery({
    queryKey: adminQueryKeys.foundation(),
    queryFn: apiAdmin.foundation,
    enabled: allowed,
    staleTime: 60_000,
  });

  if (!allowed) {
    return <UnavailableState title="Administracija nije dostupna" />;
  }

  if (query.isLoading) {
    return <LoadingState label="Učitavanje admin prostora…" />;
  }

  if (query.isError) {
    return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;
  }

  const foundation = query.data;

  if (!foundation) {
    return <UnavailableState title="Admin podaci nisu dostupni" />;
  }

  const moduleEnabled = (key: AdminModuleKey) => foundation.modules.some((module) => module.key === key && module.enabled);
  const isSuperAdmin = bootstrap?.user.role?.slug === 'superadmin';
  const inventoryValuation = isSuperAdmin ? foundation.inventory_valuation : null;
  const adminSearchNeedle = adminSearch.trim().toLocaleLowerCase('sr');
  const adminMatch = (label: string) => adminSearchNeedle === '' || label.toLocaleLowerCase('sr').includes(adminSearchNeedle);

  return (
    <Screen>
      <PageHeader
        title="Administracija"
        eyebrow="Ald1n CMS · Admin"
        name={bootstrap?.user.name}
      />

      <Card style={styles.hero}>
        <Text style={styles.heroEyebrow}>ADMIN RADNI PROSTOR</Text>
        <Text style={styles.heroTitle}>
          Poslovni alati na jednom mestu.
        </Text>
        <Text style={styles.heroCopy}>
          Prikazane su samo funkcije koje su dostupne vašem nalogu.
        </Text>
        <Text style={styles.metric}>
          {foundation.enabled_module_count} dostupnih domena
        </Text>
      </Card>

      {inventoryValuation ? (
        <View style={styles.valuationGrid}>
          <Card style={styles.valuationCard}>
            <Text style={styles.valuationLabel}>Vrednost po nabavnoj ceni</Text>
            <Text style={styles.valuationValue}>{moneyRsd(inventoryValuation.purchase_value_rsd)}</Text>
            <Text style={styles.valuationNote}>{inventoryValuation.missing_cost_total_items} artikala bez nabavne cene</Text>
          </Card>
          <Card style={styles.valuationCard}>
            <Text style={styles.valuationLabel}>Vrednost po prodajnoj ceni</Text>
            <Text style={styles.valuationValue}>{moneyRsd(inventoryValuation.sale_value_rsd)}</Text>
            <Text style={styles.valuationNote}>Trenutna prodajna vrednost lagera</Text>
          </Card>
          <Card style={styles.valuationCard}>
            <Text style={styles.valuationLabel}>Ukupna očekivana zarada</Text>
            <Text style={styles.valuationValue}>{moneyRsd(inventoryValuation.expected_profit_rsd)}</Text>
            <Text style={styles.valuationNote}>{inventoryValuation.valuation_complete ? 'Kompletna valuacija trenutnog lagera' : 'Privremena procena — dopunite nabavne cene ili kurs'}</Text>
          </Card>
        </View>
      ) : null}

      {/* MOBILE_V1_0_GLOBAL_SEARCH_PARITY_BATCH38 */}
      <Card style={styles.adminGroupCard}>
        <Text style={styles.sectionTitle}>Globalna pretraga</Text>
        <Text style={styles.heroCopy}>Artikli, porudžbine, korisnici, garancije, reklamacije i prečice kroz jedan permission-aware ekran.</Text>
        <OperatorRow title="Pretraži sve module" copy="Permission-aware pretraga kroz poslovne domene." glyph="search" onPress={() => router.push('/admin/search' as Href)} />
      </Card>

            {/* MOBILE_V0_9_GROUPED_ADMIN_HUB_BATCH5C */}
      <Card style={styles.searchSurface}>
        <TextInput
          value={adminSearch}
          onChangeText={setAdminSearch}
          placeholder="Pretraži administraciju"
          autoCapitalize="none"
          autoCorrect={false}
          accessibilityLabel="Pretraga administracije"
          style={styles.adminSearchInput}
        />
      </Card>

      {((can('orders.manage') && (adminMatch('Porudžbine') || adminMatch('Isporuke'))) ||
        (isSuperAdmin && adminMatch('Direktna prodaja')) ||
        (moduleEnabled('commissions') && can('commissions.manage') && adminMatch('Provizije')) ||
        (moduleEnabled('receivables') && can('receivables.manage') && adminMatch('Potraživanja'))) ? (
        <Card style={styles.adminGroupCard}>
          <Text style={styles.sectionTitle}>Prodaja</Text>
          <View style={styles.quickActions}>
            {can('orders.manage') && adminMatch('Porudžbine') ? <OperatorRow title="Porudžbine" glyph="orders" onPress={() => router.push('/admin/orders')} divider /> : null}
            {isSuperAdmin && adminMatch('Direktna prodaja') ? <OperatorRow title="Direktna prodaja" glyph="cart" onPress={() => router.push('/admin/catalog' as Href)} divider /> : null}
            {moduleEnabled('commissions') && can('commissions.manage') && adminMatch('Provizije') ? <OperatorRow title="Provizije" glyph="commission" onPress={() => router.push('/admin/commissions')} divider /> : null}
            {moduleEnabled('receivables') && can('receivables.manage') && adminMatch('Potraživanja') ? <OperatorRow title="Potraživanja" glyph="report" onPress={() => router.push('/admin/receivables')} divider /> : null}
            {can('orders.manage') && adminMatch('Isporuke') ? <OperatorRow title="Isporuke" glyph="orders" onPress={() => router.push('/admin/orders')} divider /> : null}
          </View>
        </Card>
      ) : null}

      {((can('catalog.manage_products') && (adminMatch('Artikli') || adminMatch('Dodaj artikal') || adminMatch('Masovne izmene') || (isSuperAdmin && adminMatch('Nabavne cene')))) ||
        (can('catalog.audit') && adminMatch('Kvalitet podataka')) ||
        (moduleEnabled('inventory') && (can('stock.view') || can('stock.adjust') || can('inventory.receive') || can('inventory.count') || can('inventory.export')) && adminMatch('Lager')) ||
        (can('catalog.manage_taxonomy') && (adminMatch('Šifarnici') || adminMatch('Brendovi') || adminMatch('Kategorije') || adminMatch('Linije proizvoda') || adminMatch('Tipovi proizvoda') || adminMatch('Specifikaciona polja')))) ? (
        <Card style={styles.adminGroupCard}>
          <Text style={styles.sectionTitle}>Katalog i lager</Text>
          <View style={styles.quickActions}>
            {can('catalog.manage_products') && adminMatch('Artikli') ? <OperatorRow title="Artikli" glyph="catalog" onPress={() => router.push('/admin/catalog' as Href)} divider /> : null}
            {can('catalog.manage_products') && adminMatch('Dodaj artikal') ? <OperatorRow title="Dodaj artikal" glyph="add" onPress={() => router.push('/admin/catalog/create')} divider /> : null}
            {can('catalog.manage_products') && isSuperAdmin && adminMatch('Nabavne cene') ? <OperatorRow title="Nabavne cene" glyph="report" onPress={() => router.push('/admin/catalog/purchase-costs' as Href)} divider /> : null}
            {can('catalog.manage_products') && adminMatch('Masovne izmene') ? <OperatorRow title="Masovne izmene" glyph="admin" onPress={() => router.push('/admin/catalog/bulk' as Href)} divider /> : null}
            {can('catalog.audit') && adminMatch('Kvalitet podataka') ? <OperatorRow title="Kvalitet podataka" glyph="info" onPress={() => router.push('/admin/catalog/data-quality' as Href)} divider /> : null}
            {moduleEnabled('inventory') && (can('stock.view') || can('stock.adjust') || can('inventory.receive') || can('inventory.count') || can('inventory.export')) && adminMatch('Lager') ? <OperatorRow title="Lager" glyph="box" onPress={() => router.push('/admin/inventory')} divider /> : null}
            {can('catalog.manage_taxonomy') && (adminMatch('Šifarnici') || adminMatch('Brendovi') || adminMatch('Kategorije') || adminMatch('Linije proizvoda') || adminMatch('Tipovi proizvoda') || adminMatch('Specifikaciona polja')) ? <OperatorRow title="Šifarnici" glyph="catalog" onPress={() => router.push('/admin/catalog/dictionaries' as Href)} divider /> : null}
          </View>
        </Card>
      ) : null}

      {((moduleEnabled('after_sales') && can('after_sales.manage') && adminMatch('Reklamacije')) ||
        (moduleEnabled('warranties') && can('warranties.manage') && adminMatch('Garancije')) ||
        (moduleEnabled('field_operations') && can('field_operations.view') && adminMatch('Terenske operacije')) ||
        (moduleEnabled('service_parts') && (can('service_parts.view') || can('service_parts.manage') || can('service_parts.procurement')) && adminMatch('Servisni delovi'))) ? (
        <Card style={styles.adminGroupCard}>
          <Text style={styles.sectionTitle}>Postprodaja</Text>
          <View style={styles.quickActions}>
            {moduleEnabled('after_sales') && can('after_sales.manage') && adminMatch('Reklamacije') ? <OperatorRow title="Reklamacije" glyph="service" onPress={() => router.push('/admin/after-sales')} divider /> : null}
            {moduleEnabled('warranties') && can('warranties.manage') && adminMatch('Garancije') ? <OperatorRow title="Garancije" glyph="warranty" onPress={() => router.push('/admin/warranties')} divider /> : null}
            {moduleEnabled('field_operations') && can('field_operations.view') && adminMatch('Terenske operacije') ? <OperatorRow title="Terenske operacije" glyph="service" onPress={() => router.push('/admin/field-operations')} divider /> : null}
            {moduleEnabled('service_parts') && (can('service_parts.view') || can('service_parts.manage') || can('service_parts.procurement')) && adminMatch('Servisni delovi') ? <OperatorRow title="Servisni delovi" glyph="box" onPress={() => router.push('/admin/service-parts')} divider /> : null}
          </View>
        </Card>
      ) : null}

      {((moduleEnabled('reports') && can('reports.view') && adminMatch('Izveštaji')) ||
        (can('system.manage_settings') && adminMatch('EUR/RSD')) ||
        (isSuperAdmin && adminMatch('Kurirske službe'))) ? (
        <Card style={styles.adminGroupCard}>
          <Text style={styles.sectionTitle}>Poslovanje</Text>
          <View style={styles.quickActions}>
            {moduleEnabled('reports') && can('reports.view') && adminMatch('Izveštaji') ? <OperatorRow title="Izveštaji" glyph="report" onPress={() => router.push('/admin/reports')} divider /> : null}
            {can('system.manage_settings') && adminMatch('EUR/RSD') ? <OperatorRow title="EUR/RSD" glyph="report" onPress={() => router.push('/admin/exchange-rate' as Href)} divider /> : null}
            {isSuperAdmin && adminMatch('Kurirske službe') ? <OperatorRow title="Kurirske službe" glyph="service" onPress={() => router.push('/admin/couriers' as Href)} divider /> : null}
          </View>
        </Card>
      ) : null}

      {(can('system.manage_users') && (
        adminMatch('Upravljanje korisnicima')
        || adminMatch('Grupe pristupa')
        || (moduleEnabled('customer_portal') && adminMatch('Customer Portal'))
      )) ? (
        <Card style={styles.adminGroupCard}>
          <Text style={styles.sectionTitle}>Korisnici</Text>
          <View style={styles.quickActions}>
            {adminMatch('Upravljanje korisnicima') ? <OperatorRow title="Upravljanje korisnicima" glyph="account" onPress={() => router.push('/admin/users' as Href)} divider /> : null}
            {adminMatch('Grupe pristupa') ? <OperatorRow title="Grupe pristupa" glyph="account" onPress={() => router.push('/admin/user-groups' as Href)} divider /> : null}
            {moduleEnabled('customer_portal') && adminMatch('Customer Portal') ? <OperatorRow title="Customer Portal" glyph="messages" onPress={() => router.push('/admin/customer-portal' as Href)} divider /> : null}
          </View>
        </Card>
      ) : null}

      {((moduleEnabled('system_health') && can('system.health') && adminMatch('System Health')) ||
        (moduleEnabled('audit') && can('security.view') && adminMatch('Audit i bezbednost')) ||
        (can('system.manage_settings') && adminMatch('Sistemska podešavanja')) ||
        (isSuperAdmin && can('system.manage_settings') && adminMatch('Moduli sistema'))) ? (
        <Card style={styles.adminGroupCard}>
          <Text style={styles.sectionTitle}>Sistem</Text>
          <View style={styles.quickActions}>
            {moduleEnabled('system_health') && can('system.health') && adminMatch('System Health') ? <OperatorRow title="System Health" glyph="info" onPress={() => router.push('/admin/system-health')} divider /> : null}
            {moduleEnabled('audit') && can('security.view') && adminMatch('Audit i bezbednost') ? <OperatorRow title="Audit i bezbednost" glyph="lock" onPress={() => router.push('/admin/audit')} divider /> : null}
            {can('system.manage_settings') && adminMatch('Sistemska podešavanja') ? <OperatorRow title="Sistemska podešavanja" glyph="admin" onPress={() => router.push('/admin/settings' as Href)} divider /> : null}
            {isSuperAdmin && can('system.manage_settings') && adminMatch('Moduli sistema') ? <OperatorRow title="Moduli sistema" glyph="admin" onPress={() => router.push('/admin/settings/modules' as Href)} divider /> : null}
          </View>
        </Card>
      ) : null}


    </Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    hero: {
      gap: spacing.sm,
      borderLeftWidth: 3,
      borderLeftColor: theme.primary,
    },
    heroEyebrow: {
      ...typography.small,
      color: theme.primary,
      fontWeight: '800',
      letterSpacing: 1.1,
    },
    heroTitle: {
      ...typography.h2,
      color: theme.ink,
    },
    heroCopy: {
      ...typography.body,
      color: theme.muted,
    },
    metric: {
      ...typography.label,
      color: theme.primary,
    },
    valuationGrid: {
      gap: spacing.sm,
    },
    valuationCard: {
      gap: spacing.xs,
    },
    valuationLabel: {
      ...typography.small,
      color: theme.muted,
      fontWeight: '700',
    },
    valuationValue: {
      ...typography.h2,
      color: theme.ink,
    },
    valuationNote: {
      ...typography.small,
      color: theme.muted,
    },
    quickActions: {
      gap: 0,
      marginHorizontal: -spacing.lg,
      marginBottom: -spacing.lg,
      overflow: 'hidden',
    },
    adminSearchInput: {
      ...typography.body,
      color: theme.ink,
      paddingHorizontal: spacing.md,
      paddingVertical: spacing.sm,
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.md,
      backgroundColor: theme.surfaceMuted,
    },
    searchSurface: {
      padding: spacing.sm,
    },
    adminGroupCard: {
      gap: spacing.md,
      overflow: 'hidden',
    },

    sectionTitle: {
      ...typography.h2,
      color: theme.ink,
      flex: 1,
    },

  });
}
