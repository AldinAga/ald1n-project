import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { router, type Href } from 'expo-router';
import { StyleSheet, Text, View , TextInput} from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Pill } from '@/components/ui/pill';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { hasAdminAccess } from '@/features/admin/admin-access';
import { apiAdmin } from '@/features/admin/admin-api';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import { useAuth } from '@/features/auth/auth-provider';
import { useThemedStyles } from '@/theme/app-theme';

function moneyRsd(value: number): string {
  return `${Number(value).toLocaleString('sr-RS', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} RSD`;
}

// MOBILE_V0_7_ADMIN_HUB_COMMISSION_PRIORITY
// MOBILE_V0_8_SUPERADMIN_INVENTORY_VALUATION_BATCH3
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

  const modules = foundation.modules.filter((module) => module.enabled);
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
        <Pill tone="primary">ADMIN RADNI PROSTOR</Pill>
        <Text style={styles.heroTitle}>
          Centralizovan pristup administratorskim modulima.
        </Text>
        <Text style={styles.heroCopy}>
          Dozvole, API namespace i query-key konvencije dele isti foundation.
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

            {/* MOBILE_V0_9_GROUPED_ADMIN_HUB_BATCH5C */}
      <Card>
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
        (can('commissions.manage') && adminMatch('Provizije')) ||
        (can('receivables.manage') && adminMatch('Potraživanja'))) ? (
        <Card style={styles.adminGroupCard}>
          <Text style={styles.sectionTitle}>Prodaja</Text>
          <View style={styles.quickActions}>
            {can('orders.manage') && adminMatch('Porudžbine') ? <Button variant="secondary" onPress={() => router.push('/admin/orders')}>Porudžbine</Button> : null}
            {isSuperAdmin && adminMatch('Direktna prodaja') ? <Button variant="secondary" onPress={() => router.push('/admin/catalog' as Href)}>Direktna prodaja</Button> : null}
            {can('commissions.manage') && adminMatch('Provizije') ? <Button variant="secondary" onPress={() => router.push('/admin/commissions')}>Provizije</Button> : null}
            {can('receivables.manage') && adminMatch('Potraživanja') ? <Button variant="secondary" onPress={() => router.push('/admin/receivables')}>Potraživanja</Button> : null}
            {can('orders.manage') && adminMatch('Isporuke') ? <Button variant="secondary" onPress={() => router.push('/admin/orders')}>Isporuke</Button> : null}
          </View>
        </Card>
      ) : null}

      {((can('catalog.manage_products') && (adminMatch('Artikli') || adminMatch('Dodaj artikal') || (isSuperAdmin && adminMatch('Nabavne cene')))) ||
        ((can('stock.view') || can('stock.adjust') || can('inventory.receive') || can('inventory.count') || can('inventory.export')) && adminMatch('Lager')) ||
        (can('catalog.manage_taxonomy') && (adminMatch('Šifarnici') || adminMatch('Brendovi')))) ? (
        <Card style={styles.adminGroupCard}>
          <Text style={styles.sectionTitle}>Katalog i lager</Text>
          <View style={styles.quickActions}>
            {can('catalog.manage_products') && adminMatch('Artikli') ? <Button variant="secondary" onPress={() => router.push('/admin/catalog' as Href)}>Artikli</Button> : null}
            {can('catalog.manage_products') && adminMatch('Dodaj artikal') ? <Button variant="secondary" onPress={() => router.push('/admin/catalog/create')}>Dodaj artikal</Button> : null}
            {can('catalog.manage_products') && isSuperAdmin && adminMatch('Nabavne cene') ? <Button variant="secondary" onPress={() => router.push('/admin/catalog/purchase-costs' as Href)}>Nabavne cene</Button> : null}
            {(can('stock.view') || can('stock.adjust') || can('inventory.receive') || can('inventory.count') || can('inventory.export')) && adminMatch('Lager') ? <Button variant="secondary" onPress={() => router.push('/admin/inventory')}>Lager</Button> : null}
            {can('catalog.manage_taxonomy') && (adminMatch('Šifarnici') || adminMatch('Brendovi')) ? <Button variant="secondary" onPress={() => router.push('/admin/catalog/brands' as Href)}>Šifarnici · Brendovi</Button> : null}
          </View>
        </Card>
      ) : null}

      {((can('after_sales.manage') && adminMatch('Reklamacije')) ||
        (can('warranties.manage') && adminMatch('Garancije')) ||
        (can('field_operations.view') && adminMatch('Terenske operacije')) ||
        ((can('service_parts.view') || can('service_parts.manage') || can('service_parts.procurement')) && adminMatch('Servisni delovi'))) ? (
        <Card style={styles.adminGroupCard}>
          <Text style={styles.sectionTitle}>Postprodaja</Text>
          <View style={styles.quickActions}>
            {can('after_sales.manage') && adminMatch('Reklamacije') ? <Button variant="secondary" onPress={() => router.push('/admin/after-sales')}>Reklamacije</Button> : null}
            {can('warranties.manage') && adminMatch('Garancije') ? <Button variant="secondary" onPress={() => router.push('/admin/warranties')}>Garancije</Button> : null}
            {can('field_operations.view') && adminMatch('Terenske operacije') ? <Button variant="secondary" onPress={() => router.push('/admin/field-operations')}>Terenske operacije</Button> : null}
            {(can('service_parts.view') || can('service_parts.manage') || can('service_parts.procurement')) && adminMatch('Servisni delovi') ? <Button variant="secondary" onPress={() => router.push('/admin/service-parts')}>Servisni delovi</Button> : null}
          </View>
        </Card>
      ) : null}

      {((can('reports.view') && adminMatch('Izveštaji')) ||
        (can('system.manage_settings') && adminMatch('EUR/RSD')) ||
        (isSuperAdmin && adminMatch('Kurirske službe'))) ? (
        <Card style={styles.adminGroupCard}>
          <Text style={styles.sectionTitle}>Poslovanje</Text>
          <View style={styles.quickActions}>
            {can('reports.view') && adminMatch('Izveštaji') ? <Button variant="secondary" onPress={() => router.push('/admin/reports')}>Izveštaji</Button> : null}
            {can('system.manage_settings') && adminMatch('EUR/RSD') ? <Button variant="secondary" onPress={() => router.push('/admin/exchange-rate' as Href)}>EUR/RSD</Button> : null}
            {isSuperAdmin && adminMatch('Kurirske službe') ? <Button variant="secondary" onPress={() => router.push('/admin/couriers' as Href)}>Kurirske službe</Button> : null}
          </View>
        </Card>
      ) : null}

      {(can('system.manage_users') && adminMatch('Upravljanje korisnicima')) ? (
        <Card style={styles.adminGroupCard}>
          <Text style={styles.sectionTitle}>Korisnici</Text>
          <View style={styles.quickActions}>
            <Button variant="secondary" onPress={() => router.push('/admin/users' as Href)}>Upravljanje korisnicima</Button>
          </View>
        </Card>
      ) : null}

      {((can('system.health') && adminMatch('System Health')) ||
        (can('security.view') && adminMatch('Audit i bezbednost'))) ? (
        <Card style={styles.adminGroupCard}>
          <Text style={styles.sectionTitle}>Sistem</Text>
          <View style={styles.quickActions}>
            {can('system.health') && adminMatch('System Health') ? <Button variant="secondary" onPress={() => router.push('/admin/system-health')}>System Health</Button> : null}
            {can('security.view') && adminMatch('Audit i bezbednost') ? <Button variant="secondary" onPress={() => router.push('/admin/audit')}>Audit i bezbednost</Button> : null}
          </View>
        </Card>
      ) : null}

      <View style={styles.sectionHead}>
        <Text style={styles.sectionTitle}>Admin moduli</Text>
        <Text style={styles.sectionMeta}>P3 se uključuje po domenima</Text>
      </View>

      <View style={styles.modules}>
        {modules.map((module) => (
          <Card key={module.key} style={styles.moduleCard}>
            <View style={styles.moduleHead}>
              <Text style={styles.moduleTitle}>{module.label}</Text>
              <Pill tone="success">DOZVOLJENO</Pill>
            </View>
            <Text style={styles.moduleCopy}>{module.description}</Text>
            <Text style={styles.moduleMeta}>
              Foundation spreman · domen funkcije se uključuju po P3 batch-evima.
            </Text>
          </Card>
        ))}
      </View>
    </Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    hero: {
      gap: spacing.md,
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
      gap: spacing.sm,
    },
    adminSearchInput: {
      ...typography.body,
      color: theme.ink,
      paddingVertical: spacing.xs,
    },
    adminGroupCard: {
      gap: spacing.sm,
    },
    sectionHead: {
      flexDirection: 'row',
      justifyContent: 'space-between',
      alignItems: 'center',
      gap: spacing.md,
    },
    sectionTitle: {
      ...typography.h2,
      color: theme.ink,
      flex: 1,
    },
    sectionMeta: {
      ...typography.small,
      color: theme.muted,
    },
    modules: {
      gap: spacing.md,
    },
    moduleCard: {
      gap: spacing.sm,
    },
    moduleHead: {
      flexDirection: 'row',
      justifyContent: 'space-between',
      alignItems: 'center',
      gap: spacing.sm,
    },
    moduleTitle: {
      ...typography.h3,
      color: theme.ink,
      flex: 1,
    },
    moduleCopy: {
      ...typography.body,
      color: theme.muted,
    },
    moduleMeta: {
      ...typography.small,
      color: theme.muted,
    },
  });
}
