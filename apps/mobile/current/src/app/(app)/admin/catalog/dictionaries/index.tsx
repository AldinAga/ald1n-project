import { router, type Href } from 'expo-router';
import { StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { UnavailableState } from '@/components/ui/states';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';
import { useThemedStyles } from '@/theme/app-theme';

// MOBILE_V1_0_ADMIN_CATALOG_DICTIONARIES_BATCH22
export default function AdminCatalogDictionariesHubScreen() {
  const styles = useThemedStyles(createStyles);
  const { bootstrap, can } = useAuth();
  const allowed = can('catalog.manage_taxonomy');

  if (!allowed) return <UnavailableState title="Šifarnici nisu dostupni" />;

  const open = (path: string) => router.push(path as Href);

  return (
    <Screen contentStyle={styles.content}>
      <Button variant="ghost" onPress={() => router.back()}>‹ Administracija</Button>
      <PageHeader title="Šifarnici" eyebrow="Admin · Katalog i lager" name={bootstrap?.user.name} />
      <Text style={styles.copy}>
        Centralno upravljanje kataloškim podacima. Brendovi koriste postojeći Global Brand Manager, a ostali šifarnici dele isti Laravel business sloj kao CMS.
      </Text>

      <View style={styles.grid}>
        <DictionaryCard title="Brendovi" copy="Globalni brendovi, povezani tipovi i do tri kurirane linije po tipu." onPress={() => open('/admin/catalog/brands')} styles={styles} />
        <DictionaryCard title="Kategorije" copy="Hijerarhija kategorija, status i redosled." onPress={() => open('/admin/catalog/dictionaries/categories')} styles={styles} />
        <DictionaryCard title="Linije proizvoda" copy="Linije vezane za brend, status i redosled." onPress={() => open('/admin/catalog/dictionaries/product-lines')} styles={styles} />
        <DictionaryCard title="Tipovi proizvoda" copy="Automatske kategorije, kompletnost, šablon naziva i dodeljene specifikacije." onPress={() => open('/admin/catalog/dictionaries/product-types')} styles={styles} />
        <DictionaryCard title="Specifikaciona polja" copy="Tipovi podataka, filteri, opcije, zavisnosti, redosled i bezbedan purge." onPress={() => open('/admin/catalog/dictionaries/specification-fields')} styles={styles} />
      </View>
    </Screen>
  );
}

function DictionaryCard({ title, copy, onPress, styles }: { title: string; copy: string; onPress: () => void; styles: ReturnType<typeof createStyles> }) {
  return (
    <Card style={styles.card}>
      <Text style={styles.title}>{title}</Text>
      <Text style={styles.copy}>{copy}</Text>
      <Button variant="secondary" onPress={onPress}>Otvori</Button>
    </Card>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: { gap: spacing.lg, paddingBottom: spacing.xxl },
    grid: { gap: spacing.md },
    card: { gap: spacing.md },
    title: { ...typography.h3, color: theme.ink },
    copy: { ...typography.body, color: theme.muted },
  });
}
