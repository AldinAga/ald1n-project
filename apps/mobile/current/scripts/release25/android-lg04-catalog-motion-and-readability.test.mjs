// LG-04 Wave 2 — catalog visuals MUST NOT obscure any business data.
import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const file = (rel) => readFileSync(new URL(rel, import.meta.url), 'utf8');
const catalog = file('../../src/app/(app)/(tabs)/catalog.tsx');
const header = file('../../src/components/layout/page-header.tsx');
const token = file('../../src/constants/theme.ts');
const pkg = JSON.parse(file('../../package.json'));
const eas = JSON.parse(file('../../eas.json'));

test('catalog uses readable solid command areas and opaque filter controls, not a per-row lens', () => {
  assert.match(catalog,/backgroundColor: theme\.background/);
  assert.match(catalog,/backgroundColor: theme\.surfaceContainer/);
  assert.match(catalog,/backgroundColor: theme\.surfaceContainerHigh/);
  assert.match(catalog,/filterPanel: \{[\s\S]*?backgroundColor: theme\.surface/);
  assert.match(catalog,/searchWrap: \{[\s\S]*?backgroundColor: theme\.surface/);
  assert.match(catalog,/color: theme\.ink/);
  assert.doesNotMatch(catalog,/LiquidGlassAndroidView/);
  assert.match(header,/<GlassHeaderAction/);
  assert.match(token,/primary: tokens\.primary/);
});

test('filter panel uses bounded 180ms transform-only native-driver entrance', () => {
  assert.match(catalog,/Animated\.timing\(filterReveal/);
  assert.match(catalog,/duration: 180/);
  assert.match(catalog,/useNativeDriver: true/);
  assert.match(catalog,/Animated\.View[\s\S]*?opacity: filterReveal/);
  assert.match(catalog,/inputRange: \[0, 1\], outputRange: \[8, 0\]/);
  assert.match(catalog,/Animated\.spring\(filterScale/);
  assert.match(catalog,/stiffness: 370/);
  assert.doesNotMatch(catalog,/LayoutAnimation|UIManager\.setLayoutAnimationEnabledExperimental/);
  assert.doesNotMatch(catalog,/Animated\.timing\([^)]*backgroundColor/);
});

test('Reduce Motion defaults to static, reacts to OS setting and cancels animation when needed', () => {
  assert.match(catalog,/const \[reduceMotion, setReduceMotion\] = useState\(true\)/);
  assert.match(catalog,/AccessibilityInfo\.isReduceMotionEnabled\(\)/);
  assert.match(catalog,/addEventListener\('reduceMotionChanged'/);
  assert.match(catalog,/filterReveal\.stopAnimation\(\)/);
  assert.match(catalog,/filterScale\.stopAnimation\(\)/);
  assert.match(catalog,/if \(reduceMotion\) \{\s*filterReveal\.setValue\(1\)/);
  assert.match(catalog,/if \(reduceMotion\) \{\s*filterScale\.setValue\(1\)/);
});

test('search, filter and cart touch targets are legible and keyboard accessible', () => {
  assert.match(catalog,/searchWrap: \{\s*minHeight: 56/);
  assert.match(catalog,/cartButton: \{\s*minHeight: 48/);
  assert.match(catalog,/filterButton: \{\s*minHeight: 48/);
  assert.match(catalog,/stockFilter: \{\s*minHeight: 44/);
  assert.match(catalog,/accessibilityLabel="Pretraga artikala po nazivu, SKU ili modelu"/);
  assert.match(catalog,/returnKeyType="search"/);
  assert.match(catalog,/accessibilityState=\{\{ expanded: filtersOpen \}\}/);
  assert.match(catalog,/accessibilityState=\{\{ selected: active \}\}/);
  assert.match(catalog,/activeFilterCount > 0 \? [^:]* : 'Filteri'/);
});

test('existing catalog filters, sort, permissions and pagination are unmodified', () => {
  assert.match(catalog,/const allowed = hasFeature\('catalog'\)/);
  assert.match(catalog,/if \(!allowed\) return <UnavailableState title="Katalog nije dostupan"/);
  assert.match(catalog,/onPress=\{\(\) => router\.push\('\/cart'\)\}/);
  assert.match(catalog,/disabled=\{!item\.enabled\}|onEndReached=\{loadNextPage\}/);
  assert.match(catalog,/catalogProductsFromPages/);
  assert.match(catalog,/nextCatalogPage/);
  assert.match(catalog,/if \(!query\.hasNextPage \|\| query\.isFetching \|\| query\.isRefetching\)/);
  assert.match(catalog,/onSortChange=\{setSort\}/);
  assert.match(catalog,/onReset=\{resetFilters\}/);
  assert.match(catalog,/per_page: 30/);
  assert.match(catalog,/maxToRenderPerBatch=\{7\}/);
  assert.match(catalog,/windowSize=\{5\}/);
});

test('LG-04 only uses existing Android native dependency and beta release preparation', () => {
  assert.equal(pkg.dependencies['react-native-android-liquid-glass'],'0.2.1');
  assert.equal(pkg.dependencies['react-native'],'0.86.3');
  assert.equal(pkg.version,'1.0.0');
  assert.equal(eas.build['lg03-play-testing'].channel,'lg03-play-testing');
  assert.equal(eas.submit['lg03-play-testing'].android.track,'beta');
  assert.equal(eas.submit['lg03-play-testing'].android.releaseStatus,'completed');
  assert.equal(eas.submit.production.android.track,'production');
});
