# Report354 - Mobile Catalog Chaos Audit

- Timestamp: 20260908-203421
- Scope: read-only audit for mobile catalog regression, phone-image handling, direct-sale stock visibility mismatch, and add-product submit behavior
- Changes applied: NO
- Database writes: NO
- EAS build: NO
- OTA publish: NO

============================================================
0. ROOT + FILE GUARDS
============================================================
ROOT=/home/icaffeco/ald1n-project
CMS=/home/icaffeco/ald1n-project/apps/cms/current
MOBILE=/home/icaffeco/ald1n-project/apps/mobile/current
FILE_GUARDS=PASS

============================================================
1. GIT STATE
============================================================
CMD=git-branch
main
CMD_RESULT=git-branch :: PASS
CMD=git-head
6f38e8ce2ac29807c40d6385074a01488abc9589
CMD_RESULT=git-head :: PASS
CMD=git-status-short
 M apps/cms/current/public/.htaccess
?? .build16-batch123-backup-20260907-082312/
?? .build16-batch123-v2-backup-20260907-083012/
?? .build16-batch123-v3-backup-20260907-084320/
?? .build16-batch123-v4-backup-20260907-091704/
?? .build16-batch123-v6-backup-20260907-092919/
?? .build16-batch124-backup-20260907-100747/
?? .build16-batch124-backup-20260907-110746/
?? .build16-batch124-backup-20260907-111307/
?? .build16-batch125-v2-backup-20260907-113233/
?? .build16-batch125-v3-backup-20260907-113801/
?? .build16-batch126-backup-20260907-120723/
?? .build16-batch126-v2-backup-20260907-121327/
?? .build16-batch126-v3-backup-20260907-122835/
?? .build16-batch126-v4-backup-20260907-123749/
?? .build16-batch126-v5-backup-20260907-124438/
?? .build16-batch127-backup-20260907-125611/
?? .build16-batch127-v2-backup-20260907-132843/
?? .build16-batch127-v3-backup-20260907-134035/
?? .build16-batch128-backup-20260907-141015/
?? .build16-batch129-backup-20260907-142050/
?? .build16-batch130-backup-20260907-143353/
?? .build16-batch131-backup-20260907-144511/
?? .build16-batch132-backup-20260907-145653/
?? .build16-batch133-backup-20260907-151247/
?? .build16-batch134-backup-20260908-104443/
?? .build16-batch134-v2-backup-20260908-115957/
?? .build16-batch134-v3-backup-20260908-121818/
?? .build16-batch134-v4-backup-20260908-123328/
?? .build16-batch134-v6-backup-20260908-124724/
?? .build16-batch136-backup-20260908-110200/
?? .build16-batch136-v2-backup-20260908-110558/
?? docs/operations/259-CMS-V2.2.0-PDF-SERBIAN-LATIN-FONT-HOTFIX-BATCH55-20260827-233206.md
?? docs/operations/260-CMS-V2.2.0-PDF-SERBIAN-LATIN-FONT-HOTFIX-BATCH55-V2-20260827-233810.md
?? docs/operations/261-CMS-V2.2.0-PDF-SERBIAN-LATIN-FONT-HOTFIX-BATCH55-V3-20260827-234600.md
?? docs/operations/262-CMS-V2.2.0-PDF-SERBIAN-LATIN-FONT-HOTFIX-BATCH55-V4-1-20260827-235739.md
?? docs/operations/263-CMS-V2.2.0-PDF-SERBIAN-LATIN-FONT-HOTFIX-BATCH55-V4-2-20260828-000125.md
?? docs/operations/264-CMS-V2.2.0-PDF-SERBIAN-LATIN-FONT-HOTFIX-BATCH55-V4-3-20260828-000713.md
?? docs/operations/265-CMS-V2.2.0-INVOICE-PROFORMA-ISSUANCE-READ-ONLY-AUDIT-BATCH55B-20260828-001536.md
?? docs/operations/266-CMS-V2.2.0-PAID-BANK-TRANSFER-FINANCIAL-DOCUMENTS-HOTFIX-BATCH55C-20260828-082046.md
?? docs/operations/267-CMS-V2.2.0-PAID-BANK-TRANSFER-FINANCIAL-DOCUMENTS-HOTFIX-BATCH55C-V2-20260828-083132.md
?? docs/operations/268-MOBILE-V1.0-CMS-V2.2.0-FULL-STABLE-BACKUP-BATCH56-20260828-083827.md
?? docs/operations/269-MOBILE-V1.0-CMS-V2.2.0-STABLE-BACKUP-RECOVERY-AUDIT-BATCH56R-20260828-085055.md
?? docs/operations/270-MOBILE-V1.0-CMS-V2.2.0-STABLE-CERTIFICATION-CLEANUP-READ-ONLY-AUDIT-BATCH56C-57-20260828-085532.md
?? docs/operations/271-MOBILE-V1.0-CMS-V2.2.0-FULL-SAFE-CLEANUP-BATCH58-20260828-091511.md
?? docs/operations/272-MOBILE-V1.0-CMS-V2.2.0-FULL-SAFE-CLEANUP-BATCH58-V2-20260828-092227.md
?? docs/operations/273-MOBILE-V1.0-CMS-V2.2.0-CLEANUP-RECONCILIATION-DEEP-AUDIT-BATCH58R-59-20260828-093231.md
?? docs/operations/274-MOBILE-V1.0-CMS-V2.2.0-CLEANUP-RECONCILIATION-DEEP-AUDIT-BATCH58R-V2-59-20260828-093728.md
?? docs/operations/275-MOBILE-V1.0-CMS-V2.2.0-FINAL-DEEP-CLEANUP-BATCH60-20260828-095836.md
?? docs/operations/276-MOBILE-V1.0-CMS-V2.2.0-FINAL-DEEP-CLEANUP-BATCH60-V2-20260828-100305.md
?? docs/operations/277-MOBILE-V1.0-CMS-V2.2.0-CLEAN-STABLE-CONSOLIDATION-BATCH61-20260828-102254.md
?? docs/operations/278-MOBILE-V1.0-CMS-V2.2.0-CLEAN-STABLE-CONSOLIDATION-BATCH61-V2-20260828-112307.md
?? docs/operations/279-MOBILE-V1.0-PERFORMANCE-OPTIMIZATION-READ-ONLY-BASELINE-AUDIT-BATCH62-20260828-114426.md
?? docs/operations/280-MOBILE-V1.0-CATALOG-RENDER-PERFORMANCE-OPTIMIZATION-BATCH63-20260828-115911.md
?? docs/operations/281-MOBILE-V1.0-CORE-TAB-LIST-RENDER-PERFORMANCE-OPTIMIZATION-BATCH64-20260828-121114.md
?? docs/operations/282-MOBILE-V1.0-HOME-QUERY-CACHE-PERFORMANCE-READ-ONLY-AUDIT-BATCH65-20260828-121946.md
?? docs/operations/283-MOBILE-V1.0-AUTH-UNREAD-CONTEXT-HOME-RENDER-CONTAINMENT-OPTIMIZATION-BATCH66-20260828-123100.md
?? docs/operations/284-MOBILE-V1.0-AUTH-UNREAD-CONTEXT-HOME-RENDER-CONTAINMENT-OPTIMIZATION-BATCH66-V2-20260828-143720.md
?? docs/operations/285-MOBILE-V1.0-AUTH-UNREAD-CONTEXT-HOME-RENDER-CONTAINMENT-OPTIMIZATION-BATCH66-V3-20260828-145148.md
?? docs/operations/286-MOBILE-V1.0-AUTH-UNREAD-CONTEXT-HOME-RENDER-CONTAINMENT-OPTIMIZATION-BATCH66-V4-20260828-145904.md
?? docs/operations/287-MOBILE-V1.0-AUTH-UNREAD-CONTEXT-HOME-RENDER-CONTAINMENT-OPTIMIZATION-BATCH66-V5-20260828-151656.md
?? docs/operations/288-MOBILE-V1.0-AUTH-UNREAD-CONTEXT-HOME-RENDER-CONTAINMENT-OPTIMIZATION-BATCH66-V6-20260828-153432.md
?? docs/operations/289-MOBILE-V1.0-PUSH-FOREGROUND-STARTUP-PERFORMANCE-READ-ONLY-AUDIT-BATCH67-20260828-154513.md
?? docs/operations/290-MOBILE-V1.0-STARTUP-DEVICE-PUSH-DEDUP-OPTIMIZATION-BATCH68-20260828-173645.md
?? docs/operations/291-MOBILE-V1.0-STARTUP-DEVICE-PUSH-DEDUP-OPTIMIZATION-BATCH68-V2-20260828-195343.md
?? docs/operations/292-MOBILE-V1.0-EXISTING-SESSION-BOOTSTRAP-READINESS-SPLASH-PRESENTATION-READ-ONLY-AUDIT-BATCH69-20260828-200213.md
?? docs/operations/293-MOBILE-V1.0-SESSION-RESTORE-READY-SPLASH-GATE-OPTIMIZATION-BATCH70-20260828-200829.md
?? docs/operations/294-MOBILE-V1.0-CATALOG-MULTI-IMAGE-GALLERY-INDIVIDUAL-DOWNLOAD-BATCH71-20260828-202038.md
?? docs/operations/295-MOBILE-V1.0-CATALOG-MULTI-IMAGE-GALLERY-INDIVIDUAL-DOWNLOAD-BATCH71-V2-20260828-210650.md
?? docs/operations/296-MOBILE-V1.0-CATALOG-MULTI-IMAGE-GALLERY-INDIVIDUAL-DOWNLOAD-BATCH71-V3-20260828-212910.md
?? docs/operations/297-MOBILE-V1.0-POST-OPTIMIZATION-RESIDUAL-PERFORMANCE-READ-ONLY-AUDIT-BATCH72-20260828-220234.md
?? docs/operations/298-MOBILE-V1.0-POST-OPTIMIZATION-RESIDUAL-PERFORMANCE-READ-ONLY-AUDIT-BATCH72-V2-20260828-220634.md
?? docs/operations/299-MOBILE-V1.0-POST-OPTIMIZATION-RESIDUAL-PERFORMANCE-READ-ONLY-AUDIT-BATCH72-V3-20260828-221103.md
?? docs/operations/300-MOBILE-V1.0-POST-OPTIMIZATION-RESIDUAL-PERFORMANCE-READ-ONLY-AUDIT-BATCH72-V4-20260828-221534.md
?? docs/operations/301-MOBILE-V1.0-POST-OPTIMIZATION-RESIDUAL-PERFORMANCE-READ-ONLY-AUDIT-BATCH72-V5-20260828-221844.md
?? docs/operations/302-MOBILE-V1.0-NBS-API-PRIMARY-FRANKFURTER-FALLBACK-EXCHANGE-RATE-BATCH73-20260828-223549.md
?? docs/operations/303-MOBILE-V1.0-NBS-API-PRIMARY-FRANKFURTER-FALLBACK-EXCHANGE-RATE-BATCH73-V2-20260828-224154.md
?? docs/operations/304-MOBILE-V1.0-NBS-API-PRODUCTION-CREDENTIALS-PRIMARY-PREFLIGHT-BATCH74-20260828-224908.md
?? docs/operations/304-MOBILE-V1.0-NBS-PUBLIC-LIST-PRIMARY-FRANKFURTER-FALLBACK-BATCH74-V2-20260828-230547.md
?? docs/operations/305-MOBILE-V1.0-NBS-PUBLIC-PRIMARY-CONTROLLED-SYNC-BATCH75-20260828-231111.md
?? docs/operations/306-MOBILE-V1.0-NBS-PUBLIC-PRIMARY-CONTROLLED-SYNC-BATCH75-V2-20260829-081524.md
?? docs/operations/307-MOBILE-V1.0-EXCHANGE-RATE-WEB-MOBILE-FINAL-ACCEPTANCE-BATCH76-20260829-082131.md
?? docs/operations/308-MOBILE-V1.0-PUSH-PRODUCTION-NEW-PRODUCT-ANNOUNCEMENT-READ-ONLY-AUDIT-BATCH77-20260829-083525.md
?? docs/operations/309-MOBILE-V1.0-FULL-FUNCTIONAL-UX-READ-ONLY-AUDIT-BATCH78-20260829-090330.md
?? docs/operations/310-MOBILE-V1.0-ADMIN-REPORTS-2-UX-REORGANIZATION-BATCH79-20260829-100036.md
?? docs/operations/311-MOBILE-V1.0-CATALOG-CARD-HIDE-SKU-BATCH80-20260829-103427.md
?? docs/operations/312-MOBILE-V1.0-ACCOUNT-HUB-UX-REORGANIZATION-BATCH81-20260829-104845.md
?? docs/operations/313-MOBILE-V1.0-ACCOUNT-HUB-UX-REORGANIZATION-BATCH81-V2-20260829-110545.md
?? docs/operations/314-MOBILE-V1.0-ACCOUNT-HUB-UX-REORGANIZATION-BATCH81-V3-20260829-111644.md
?? docs/operations/315-MOBILE-V1.0-ADMIN-INVENTORY-UX-REORGANIZATION-BATCH82-V3-RECOVERED-20260829-121812.md
?? docs/operations/316-MOBILE-V1.0-ADMIN-AFTER-SALES-DETAIL-UX-REORGANIZATION-BATCH83-20260829-121812.md
?? docs/operations/317-MOBILE-V1.0-ADMIN-FIELD-OPERATIONS-DETAIL-UX-REORGANIZATION-BATCH84-20260829-122347.md
?? docs/operations/318-MOBILE-V1.0-ADMIN-WARRANTY-DETAIL-UX-REORGANIZATION-BATCH85-20260829-124632.md
?? docs/operations/319-MOBILE-V1.0-ADMIN-ORDER-DETAIL-UX-REORGANIZATION-BATCH86-20260829-131854.md
?? docs/operations/320-MOBILE-V1.0-ADMIN-SERVICE-PARTS-UX-REORGANIZATION-BATCH87-20260829-202142.md
?? docs/operations/321-MOBILE-V1.0-ADMIN-RECEIVABLES-DETAIL-UX-REORGANIZATION-BATCH88-20260829-221714.md
?? docs/operations/322-MOBILE-V1.0-ADMIN-RECEIVABLES-LIST-UX-REORGANIZATION-BATCH89-20260829-225352.md
?? docs/operations/323-MOBILE-V1.0-ADMIN-WARRANTY-RULES-UX-REORGANIZATION-BATCH90-20260829-231500.md
?? docs/operations/324-MOBILE-V1.0-ADMIN-COMMISSIONS-LIST-UX-REORGANIZATION-BATCH91-20260829-233453.md
?? docs/operations/325-MOBILE-V1.0-ADMIN-COMMISSIONS-DETAIL-UX-REORGANIZATION-BATCH92-20260829-234226.md
?? docs/operations/326-MOBILE-V1.0-ADMIN-ORDERS-LIST-UX-REORGANIZATION-BATCH93-20260830-000545.md
?? docs/operations/327-MOBILE-V1.0-ADMIN-WARRANTIES-LIST-UX-REORGANIZATION-BATCH94-20260830-001330.md
?? docs/operations/328-MOBILE-V1.0-ADMIN-AFTER-SALES-LIST-UX-REORGANIZATION-BATCH95-20260830-003157.md
?? docs/operations/329-MOBILE-V1.0-ADMIN-FIELD-OPERATIONS-LIST-UX-REORGANIZATION-BATCH96-20260830-003942.md
?? docs/operations/330-MOBILE-V1.0-ADMIN-AUDIT-LIST-UX-REORGANIZATION-BATCH97-20260830-004443.md
?? docs/operations/331-MOBILE-V1.0-ADMIN-AUDIT-LIST-UX-REORGANIZATION-BATCH97-V2-20260830-005046.md
?? docs/operations/332-MOBILE-V1.0-FINAL-UX-CLOSURE-RELEASE-CERTIFICATION-AUDIT-BATCH98-20260830-005654.md
?? docs/operations/333-MOBILE-V1.0-FINAL-CONSOLIDATED-ANDROID-PRODUCTION-AAB-BUILD14-BATCH99-20260830-010354.md
?? docs/operations/334-MOBILE-V1.0-FINAL-CONSOLIDATED-ANDROID-PRODUCTION-AAB-BUILD14-BATCH99-V2-20260830-010938.md
?? docs/operations/336-MOBILE-V1.0-FINAL-CONSOLIDATED-ANDROID-PRODUCTION-AAB-BUILD14-BATCH99-V4-20260830-012013.md
?? docs/operations/337-MOBILE-V1.0-FINAL-CONSOLIDATED-ANDROID-PRODUCTION-AAB-BUILD14-BATCH99-V5-20260830-012752.md
?? docs/operations/338-MOBILE-V1.0-FINAL-CONSOLIDATED-ANDROID-PRODUCTION-AAB-BUILD14-BATCH99-V6-20260830-013506.md
?? docs/operations/339-MOBILE-V1.0.0-PRODUCT-IMAGE-PERFORMANCE-OPTIMIZATION-BATCH116-V2-20260831-094900.md
?? docs/operations/340-MOBILE-V1.0.0-PRODUCT-IMAGE-PERFORMANCE-OPTIMIZATION-BATCH116-V3-20260831-103102.md
?? docs/operations/341-MOBILE-V1.0.0-PRODUCT-IMAGE-PERFORMANCE-OPTIMIZATION-BATCH116-V4-20260831-111936.md
?? docs/operations/342-MOBILE-V1.0.0-PRODUCT-IMAGE-PERFORMANCE-OPTIMIZATION-BATCH116-V5-20260831-121520.md
?? docs/operations/343-MOBILE-V1.0.0-PRODUCT-IMAGE-PERFORMANCE-OPTIMIZATION-BATCH116-V6-20260831-124703.md
?? docs/operations/344-MOBILE-V1.0.0-IMAGE-PERFORMANCE-PRODUCTION-AAB-BUILD15-BATCH117-20260831-132309.md
?? docs/operations/345-MOBILE-V1.0.0-FINAL-RELEASE-CERTIFICATION-BATCH118-20260831-155318.md
?? docs/operations/346-MOBILE-V1.0.0-FINAL-RELEASE-CERTIFICATION-BATCH118-V2-20260901-090150.md
?? docs/operations/346-MOBILE-V1.0.0-GPU-DEPENDENT-DROPDOWN-BATCH119-20260901-140421.md
?? docs/operations/347-MOBILE-V1.0.0-UPDATED-FINAL-RELEASE-CERTIFICATION-BATCH120-20260901-150720.md
?? docs/operations/347-MOBILE-V1.0.0-UPDATED-FINAL-RELEASE-CERTIFICATION-BATCH120-V2-RECOVERY-20260903-085145.md
?? docs/operations/348-MOBILE-V1.0.0-GOOGLE-PLAY-PRODUCTION-ROLLOUT-BATCH121-20260903-084807.md
?? docs/operations/348-MOBILE-V1.0.0-GOOGLE-PLAY-PRODUCTION-ROLLOUT-BATCH121-V2-20260903-090641.md
?? docs/operations/348-MOBILE-V1.0.0-GOOGLE-PLAY-PRODUCTION-ROLLOUT-BATCH121-V3-RECOVERY-20260903-093301.md
?? docs/operations/349-MOBILE-BUILD16-DESIGN-FOUNDATION-BATCH123-20260907-082312.md
?? docs/operations/349-MOBILE-BUILD16-DESIGN-FOUNDATION-BATCH123-V2-20260907-083012.md
?? docs/operations/349-MOBILE-BUILD16-DESIGN-FOUNDATION-BATCH123-V3-20260907-084320.md
?? docs/operations/349-MOBILE-BUILD16-DESIGN-FOUNDATION-BATCH123-V4-20260907-091704.md
?? docs/operations/349-MOBILE-BUILD16-DESIGN-FOUNDATION-BATCH123-V5-20260907-092359.md
?? docs/operations/349-MOBILE-BUILD16-DESIGN-FOUNDATION-BATCH123-V6-20260907-092919.md
?? docs/operations/350-MOBILE-BUILD16-GLOBAL-PRIMITIVES-ICON-FOUNDATION-HOME-PREP-BATCH124-20260907-100747.md
?? docs/operations/350-MOBILE-BUILD16-GLOBAL-PRIMITIVES-ICON-FOUNDATION-HOME-PREP-BATCH124-V2-20260907-110746.md
?? docs/operations/350-MOBILE-BUILD16-GLOBAL-PRIMITIVES-ICON-FOUNDATION-HOME-PREP-BATCH124-V3-20260907-111307.md
?? docs/operations/351-MOBILE-BUILD16-HOME-REDESIGN-CMS-ANDROID-BATCH125-20260907-112433.md
?? docs/operations/351-MOBILE-BUILD16-HOME-REDESIGN-CMS-ANDROID-BATCH125-V2-20260907-113233.md
?? docs/operations/351-MOBILE-BUILD16-HOME-REDESIGN-CMS-ANDROID-BATCH125-V3-20260907-113801.md
?? docs/operations/352-MOBILE-BUILD16-LARAVEL-PHOSPHOR-SHELL-BATCH126-20260907-120723.md
?? docs/operations/352-MOBILE-BUILD16-LARAVEL-PHOSPHOR-SHELL-BATCH126-V2-20260907-121327.md
?? docs/operations/352-MOBILE-BUILD16-LARAVEL-PHOSPHOR-SHELL-BATCH126-V3-20260907-122835.md
?? docs/operations/352-MOBILE-BUILD16-LARAVEL-PHOSPHOR-SHELL-BATCH126-V4-20260907-123749.md
?? docs/operations/352-MOBILE-BUILD16-LARAVEL-PHOSPHOR-SHELL-BATCH126-V5-20260907-124438.md
?? docs/operations/353-MOBILE-BUILD16-CATALOG-REDESIGN-BATCH127-20260907-125611.md
?? docs/operations/353-MOBILE-BUILD16-CATALOG-REDESIGN-BATCH127-V2-20260907-132843.md
?? docs/operations/353-MOBILE-BUILD16-CATALOG-REDESIGN-BATCH127-V3-20260907-134035.md
?? docs/operations/354-MOBILE-BUILD16-PRODUCT-DETAIL-REDESIGN-BATCH128-20260907-141015.md
?? docs/operations/354-MOBILE-CATALOG-CHAOS-AUDIT-20260908-203421.md
?? docs/operations/355-MOBILE-BUILD16-ORDERS-REDESIGN-BATCH129-20260907-142050.md
?? docs/operations/356-MOBILE-BUILD16-NOTIFICATIONS-ACCOUNT-REDESIGN-BATCH130-20260907-143353.md
?? docs/operations/357-MOBILE-BUILD16-CART-CHECKOUT-ORDER-CREATE-REDESIGN-BATCH131-20260907-144511.md
?? docs/operations/358-MOBILE-BUILD16-ADMIN-SHARED-STATES-FINAL-POLISH-BATCH132-20260907-145653.md
?? docs/operations/359-MOBILE-BUILD16-FINAL-SOURCE-CERT-EAS-BUILD16-BATCH133-20260907-151247.md
?? docs/operations/360-MOBILE-BUILD16-CATALOG-IMAGE-BOUNDS-HOTFIX-BATCH134-20260908-104443.md
?? docs/operations/360-MOBILE-BUILD16-CATALOG-RUNTIME-STABILIZATION-BATCH134-20260908-115023.md
?? docs/operations/360-MOBILE-BUILD16-CATALOG-RUNTIME-STABILIZATION-BATCH134-V2-20260908-115957.md
?? docs/operations/360-MOBILE-BUILD16-CATALOG-RUNTIME-STABILIZATION-BATCH134-V3-20260908-121818.md
?? docs/operations/360-MOBILE-BUILD16-CATALOG-RUNTIME-STABILIZATION-BATCH134-V4-20260908-123328.md
?? docs/operations/360-MOBILE-BUILD16-CATALOG-RUNTIME-STABILIZATION-BATCH134-V5-20260908-124214.md
?? docs/operations/360-MOBILE-BUILD16-CATALOG-RUNTIME-STABILIZATION-BATCH134-V6-20260908-124724.md
?? docs/operations/360-MOBILE-BUILD16-DEVICE-ACCEPTANCE-RELEASE-CERT-BATCH134-20260907-155144.md
?? docs/operations/360-MOBILE-BUILD16-DEVICE-ACCEPTANCE-RELEASE-CERT-BATCH134-V2-20260907-155415.md
?? docs/operations/360-MOBILE-BUILD16-DEVICE-ACCEPTANCE-RELEASE-CERT-BATCH134-V3-20260907-170740.md
?? docs/operations/361-DELL5591-DUPLICATE-RECONCILIATION-BATCH135-20260908-125752.md
?? docs/operations/361-MOBILE-BUILD16-CATALOG-IMAGE-ORIENTATION-AUDIT-BATCH135-20260908-104900.md
?? docs/operations/362-MOBILE-BUILD16-IMAGE-EXIF-ORIENTATION-REPAIR-BATCH136-20260908-110200.md
?? docs/operations/362-MOBILE-BUILD16-IMAGE-EXIF-ORIENTATION-REPAIR-BATCH136-V2-20260908-110558.md
?? docs/operations/MOBILE-V1.0.0-PRODUCT-IMAGE-PERFORMANCE-OPTIMIZATION-BATCH116-20260831-092819.md
CMD_RESULT=git-status-short :: PASS
CMD=git-log-12
6f38e8c (HEAD -> main, origin/main) fix(build16): stabilize catalog runtime consistency
8c869b5 fix(media): normalize EXIF orientation in product derivatives
3360430 fix(build16): bound catalog product image layout
7a35dd1 chore(build16): align native palette for Build16 release
9abaea4 feat(build16): finalize admin hub and shared visual states
a58ac1a feat(build16): redesign cart checkout and order create surfaces
efffa59 feat(build16): redesign notifications and account surfaces
8622e2e feat(build16): redesign customer order surfaces
a6df613 feat(build16): redesign product detail surfaces
96f813c feat(build16): redesign catalog list and filters
b4a9d86 feat(build16): migrate Laravel icons to local Phosphor runtime
99d86e9 feat(build16): redesign CMS and Android home surfaces
CMD_RESULT=git-log-12 :: PASS
CMD=git-diff-stat
 apps/cms/current/public/.htaccess | 5 +++++
 1 file changed, 5 insertions(+)
CMD_RESULT=git-diff-stat :: PASS

============================================================
2. MOBILE SURFACE AUDIT
============================================================
CMD=find-catalog-files
bash: line 1: rg: command not found
CMD_RESULT=find-catalog-files :: PASS
CMD=catalog-screen-snippet
import { useQuery } from '@tanstack/react-query';
import { router, useFocusEffect } from 'expo-router';
import { memo, useCallback, useEffect, useMemo, useState } from 'react';
import {
  FlatList,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ProductCard } from '@/components/catalog/product-card';
import { PageHeader } from '@/components/layout/page-header';
import { Glyph } from '@/components/ui/glyph';
import { SelectSheet } from '@/components/ui/select-sheet';
import { EmptyState, ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { radii, spacing, typography, type AppColors } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';
import { useCart } from '@/features/cart/cart-provider';
import { api } from '@/lib/api/endpoints';
import { useAppTheme, useThemedStyles } from '@/theme/app-theme';
import type { CatalogFilters, Product, Taxonomy } from '@/types/api';

// MOBILE_BUILD16_CATALOG_REDESIGN_BATCH127
export default function CatalogScreen() {
  const { colors: themeColors } = useAppTheme();
  const styles = useThemedStyles(createStyles);
  const { bootstrap, hasFeature } = useAuth();
  const { itemCount } = useCart();
  const allowed = hasFeature('catalog');

  const [search, setSearch] = useState('');
  const [stock, setStock] = useState(undefined as string | undefined);
  const [brandId, setBrandId] = useState('');
  const [typeId, setTypeId] = useState('');
  const [lineId, setLineId] = useState('');
  const [categoryId, setCategoryId] = useState('');
  const [sort, setSort] = useState('updated');
  const [filtersOpen, setFiltersOpen] = useState(false);

  const filters = useQuery({
    queryKey: ['catalog-filters'],
    queryFn: api.catalog.filters,
    enabled: allowed,
    staleTime: 10 * 60_000,
  });

  const productParams = useMemo(
    () => ({
      q: search || undefined,
      stock,
      sort,
      brand_id: brandId ? Number(brandId) : undefined,
      product_type_id: typeId ? Number(typeId) : undefined,
      product_line_id: lineId ? Number(lineId) : undefined,
      category_id: categoryId ? Number(categoryId) : undefined,
      per_page: 30,
    }),
    [brandId, categoryId, lineId, search, sort, stock, typeId],
  );

  const query = useQuery({
    queryKey: ['products', productParams],
    queryFn: () => api.catalog.products(productParams),
    enabled: allowed,
  });

  // MOBILE_BUILD16_CATALOG_FOCUS_FRESHNESS_BATCH134
  const refetchProducts = query.refetch;
  const refetchFilters = filters.refetch;
  useFocusEffect(useCallback(() => {
    if (!allowed) return;
    void refetchProducts();
    void refetchFilters();
  }, [allowed, refetchFilters, refetchProducts]));

  const stockOptions = useMemo(
    () => [{ value: undefined, label: 'Svi' }, ...(filters.data?.stock_filters ?? [])],
    [filters.data?.stock_filters],
  );

  const activeFilterCount = [
    stock,
    brandId,
    typeId,
    lineId,
    categoryId,
    sort !== 'updated' ? sort : '',
  ].filter(Boolean).length;

  const submitSearch = useCallback((value: string) => setSearch(value), []);
  const renderProduct = useCallback(
    ({ item }: { item: Product }) => <CatalogProductRow product={item} />,
    [],
  );

  const resetFilters = useCallback(() => {
    setStock(undefined);
    setBrandId('');
    setTypeId('');
    setLineId('');
    setCategoryId('');
    setSort('updated');
  }, []);

  if (!allowed) return <UnavailableState title="Katalog nije dostupan" />;
  if (query.isLoading) return <LoadingState label="Učitavanje kataloga…" />;
  if (query.isError) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;

  const total = query.data?.meta?.total ?? query.data?.data.length ?? 0;

  return (
    <SafeAreaView style={styles.safe} edges={['top']}>
      <FlatList
        data={query.data?.data ?? []}
        keyExtractor={catalogKeyExtractor}
        renderItem={renderProduct}
        ItemSeparatorComponent={CatalogSeparator}
        initialNumToRender={7}
        maxToRenderPerBatch={7}
        windowSize={5}
        updateCellsBatchingPeriod={50}
        refreshControl={(
          <RefreshControl
            refreshing={query.isRefetching}
            onRefresh={() => void query.refetch()}
            tintColor={themeColors.primary}
          />
        )}
        showsVerticalScrollIndicator={false}
        contentContainerStyle={styles.content}
        ListHeaderComponent={(
          <View style={styles.headerWrap}>
            <View style={styles.headerLine}>
              <View style={styles.headerCopy}>
                <PageHeader title="Katalog" eyebrow="Artikli" name={bootstrap?.user.name} />
              </View>
              {hasFeature('order_create') ? (
                <Pressable
                  accessibilityRole="button"
                  accessibilityLabel={`Otvori korpu${itemCount ? `, ${itemCount} artikala` : ''}`}
                  onPress={() => router.push('/cart')}
                  style={({ pressed }) => [styles.cartButton, pressed && styles.controlPressed]}
                >
                  <Glyph name="cart" size={20} color={themeColors.primary} />
                  <Text style={styles.cartButtonText}>Korpa</Text>
                  {itemCount ? <Text style={styles.cartCount}>{itemCount}</Text> : null}
                </Pressable>
              ) : null}
            </View>

            <CatalogSearchInput value={search} onSubmit={submitSearch} />

            <View style={styles.commandRow}>
              <Pressable
                accessibilityRole="button"
                accessibilityState={{ expanded: filtersOpen }}
                onPress={() => setFiltersOpen((current) => !current)}
                style={({ pressed }) => [
                  styles.filterButton,
                  filtersOpen && styles.filterButtonActive,
                  pressed && styles.controlPressed,
                ]}
              >
                <Text style={filtersOpen ? styles.filterButtonTextActive : styles.filterButtonText}>
                  Filteri
                </Text>
                {activeFilterCount > 0 ? (
                  <Text style={styles.filterCount}>{activeFilterCount}</Text>
                ) : null}
              </Pressable>

              <View style={styles.resultSummary}>
                <Text style={styles.resultCount}>{total}</Text>
                <Text style={styles.resultLabel}>artikala</Text>
              </View>
            </View>

            <ScrollView
              horizontal
              showsHorizontalScrollIndicator={false}
              contentContainerStyle={styles.stockFilters}
            >
              {stockOptions.map((item) => {
                const active = stock === item.value;
                return (
                  <Pressable
                    key={item.label}
                    accessibilityRole="button"
                    accessibilityState={{ selected: active }}
                    onPress={() => setStock(item.value)}
                    style={({ pressed }) => [
                      styles.stockFilter,
                      active && styles.stockFilterActive,
                      pressed && styles.controlPressed,
                    ]}
                  >
                    <Text style={active ? styles.stockFilterTextActive : styles.stockFilterText}>
                      {item.label}
                    </Text>
                  </Pressable>
                );
              })}
            </ScrollView>

            {filtersOpen ? (
              <CatalogFilterPanel
                filters={filters.data}
                brandId={brandId}
                typeId={typeId}
                lineId={lineId}
                categoryId={categoryId}
                sort={sort}
                onBrandChange={(value) => {
                  setBrandId(value);
                  if (!value) return;
                  const selectedLine = filters.data?.lines.find((line) => String(line.id) === lineId);
                  if (selectedLine && String(selectedLine.brand_id) !== value) setLineId('');
                }}
                onTypeChange={setTypeId}
                onLineChange={setLineId}
                onCategoryChange={setCategoryId}
                onSortChange={setSort}
                onReset={resetFilters}
              />
            ) : null}

            {search ? (
              <View style={styles.searchContext}>
                <Text style={styles.searchContextLabel}>Pretraga</Text>
                <Text style={styles.searchContextValue} numberOfLines={1}>„{search}”</Text>
              </View>
            ) : null}
          </View>
        )}
        ListEmptyComponent={(
          <EmptyState
            title="Nema proizvoda"
            message="Promeni pretragu ili filtere kataloga."
          />
        )}
      />
    </SafeAreaView>
  );
}

const CatalogProductRow = memo(function CatalogProductRow({ product }: { product: Product }) {
  const handlePress = useCallback(
    () => router.push({ pathname: '/product/[slug]', params: { slug: product.slug } }),
    [product.slug],
  );

  return <ProductCard product={product} onPress={handlePress} showSku={false} />;
});

type CatalogFilterPanelProps = {
CMD_RESULT=catalog-screen-snippet :: PASS
CMD=product-card-snippet
import { Image } from 'expo-image';
import { useMemo, useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { ProductImageGallery } from '@/components/catalog/product-image-gallery';
import { Glyph } from '@/components/ui/glyph';
import { Pill } from '@/components/ui/pill';
import { radii, spacing, typography, type AppColors } from '@/constants/theme';
import { useMoneyPresentation } from '@/features/preferences/money-presentation';
import { useAppTheme } from '@/theme/app-theme';
import type { Product } from '@/types/api';

// MOBILE_BUILD16_CATALOG_PRODUCT_ROW_BATCH127
// MOBILE_BUILD16_CATALOG_IMAGE_BOUNDS_HOTFIX_BATCH134
export function ProductCard({
  product,
  onPress,
  showSku = true,
}: {
  product: Product;
  onPress: () => void;
  showSku?: boolean;
}) {
  const { colors: themeColors } = useAppTheme();
  const { formatPrimaryMoney } = useMoneyPresentation();
  const styles = useMemo(() => createStyles(themeColors), [themeColors]);
  const [imagesOpen, setImagesOpen] = useState(false);

  const catalogImageUrl = product.primary_image_thumbnail_url
    ?? product.primary_image_display_url
    ?? product.primary_image_url;

  const stockTone = product.stock_quantity > 5
    ? 'success'
    : product.stock_quantity > 0
      ? 'warning'
      : 'danger';

  const stockLabel = product.stock_quantity > 0
    ? `${product.stock_quantity} na stanju`
    : 'Nema na stanju';

  const metaLabel = [product.brand?.name, product.type?.name]
    .filter(Boolean)
    .join(' · ') || (showSku ? product.sku : null);

  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={`Otvori artikal ${product.name}`}
      onPress={onPress}
      style={({ pressed }) => [styles.shell, pressed && styles.pressed]}
    >
      <View style={styles.row}>
        <View style={styles.imageWrap}>
          {catalogImageUrl ? (
            <Image
              source={{ uri: catalogImageUrl }}
              style={styles.image}
              contentFit="contain"
              cachePolicy="memory-disk"
              transition={100}
            />
          ) : (
            <Glyph name="box" size={32} color={themeColors.primary} />
          )}
        </View>

        <View style={styles.content}>
          <View style={styles.topRow}>
            <Pill tone={stockTone}>{stockLabel}</Pill>
            <Glyph name="arrow" size={21} color={themeColors.muted} />
          </View>

          <Text style={styles.name} numberOfLines={2}>{product.name}</Text>
          {metaLabel ? <Text style={styles.meta} numberOfLines={1}>{metaLabel}</Text> : null}
          {showSku ? <Text style={styles.sku} numberOfLines={1}>SKU: {product.sku}</Text> : null}

          <View style={styles.valueRow}>
            <Text style={styles.price} numberOfLines={1}>
              {product.price
                ? formatPrimaryMoney(product.price.amount, product.price.currency)
                : 'Cena po dozvoli'}
            </Text>
            {/* MOBILE_V0_9_CATALOG_COMMISSION_BATCH5A */}
            <Text style={styles.commission} numberOfLines={1}>
              Provizija: {formatPrimaryMoney(product.commission_eur, 'EUR')}
            </Text>
          </View>

          <Pressable
            accessibilityRole="button"
            accessibilityLabel={imagesOpen ? 'Sakrij fotografije artikla' : 'Prikaži sve fotografije artikla'}
            onPress={(event) => {
              event.stopPropagation();
              setImagesOpen((current) => !current);
            }}
            style={({ pressed }) => [styles.galleryToggle, pressed && styles.galleryTogglePressed]}
          >
            <Text style={styles.galleryToggleText}>{imagesOpen ? 'Sakrij slike' : 'Sve slike'}</Text>
          </Pressable>
        </View>
      </View>

      {imagesOpen ? (
        <View style={styles.gallerySection}>
          <ProductImageGallery
            mode="catalog"
            productName={product.name}
            productSku={product.sku}
            productSlug={product.slug}
            primaryImageUrl={product.primary_image_url}
            primaryImageOriginalUrl={product.primary_image_original_url ?? product.primary_image_url}
            primaryImageDisplayUrl={product.primary_image_display_url ?? product.primary_image_url}
            primaryImageThumbnailUrl={product.primary_image_thumbnail_url ?? product.primary_image_display_url ?? product.primary_image_url}
            stopParentPress
          />
        </View>
      ) : null}
    </Pressable>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    shell: {
      overflow: 'hidden',
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.lg,
      backgroundColor: theme.surface,
      shadowColor: theme.black,
      shadowOpacity: 0.08,
      shadowRadius: 5,
      shadowOffset: { width: 0, height: 2 },
      elevation: 1,
    },
    pressed: { opacity: 0.94, transform: [{ scale: 0.988 }] },
    row: { minHeight: 142, flexDirection: 'row', alignItems: 'flex-start' },
    imageWrap: {
      width: 118,
      height: 142,
      flexShrink: 0,
      alignItems: 'center',
      justifyContent: 'center',
      backgroundColor: theme.surfaceMuted,
      borderRightWidth: 1,
      borderRightColor: theme.line,
    },
    image: { width: 118, height: 142 },
    content: { flex: 1, padding: spacing.md, gap: 6 },
    topRow: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: spacing.sm,
    },
    name: { ...typography.h3, color: theme.ink, lineHeight: 21 },
    meta: { ...typography.small, color: theme.muted },
    sku: { ...typography.small, color: theme.muted },
    valueRow: {
      marginTop: 2,
      paddingTop: spacing.sm,
      borderTopWidth: 1,
      borderTopColor: theme.line,
      gap: 2,
    },
    price: { ...typography.label, color: theme.primaryDark },
    commission: { ...typography.small, color: theme.muted },
    galleryToggle: {
      alignSelf: 'flex-start',
      minHeight: 34,
      justifyContent: 'center',
      marginTop: 2,
      paddingHorizontal: spacing.md,
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.md,
      backgroundColor: theme.surfaceContainer,
    },
    galleryTogglePressed: { opacity: 0.76 },
    galleryToggleText: { ...typography.small, color: theme.primary, fontWeight: '800' },
    gallerySection: {
      padding: spacing.md,
      borderTopWidth: 1,
      borderTopColor: theme.line,
      backgroundColor: theme.surface,
    },
  });
}
CMD_RESULT=product-card-snippet :: PASS
CMD=product-image-gallery-snippet
import { useQuery } from '@tanstack/react-query';
import { Image } from 'expo-image';
import { useMemo, useState } from 'react';
import {
  FlatList,
  Pressable,
  StyleSheet,
  Text,
  View,
  type GestureResponderEvent,
} from 'react-native';

import { useAppFeedback } from '@/components/ui/app-feedback';
import { Glyph } from '@/components/ui/glyph';
import {
  radii,
  spacing,
  typography,
  type AppColors,
} from '@/constants/theme';
import { downloadAndShareProductImage } from '@/features/catalog/product-image-download';
import { api } from '@/lib/api/endpoints';
import { useAppTheme, useThemedStyles } from '@/theme/app-theme';
import type { ProductImage } from '@/types/api';

type GalleryMode = 'catalog' | 'detail';

type GalleryItem = {
  key: string;
  originalUrl: string;
  displayUrl: string;
  primary: boolean;
};

type ProductImageGalleryProps = {
  productName: string;
  productSku: string;
  productSlug?: string;
  primaryImageUrl?: string | null;
  primaryImageOriginalUrl?: string | null;
  primaryImageDisplayUrl?: string | null;
  primaryImageThumbnailUrl?: string | null;
  images?: ProductImage[];
  mode: GalleryMode;
  stopParentPress?: boolean;
};

function cleanUrl(value: string | null | undefined): string | null {
  const cleaned = value?.trim();
  return cleaned ? cleaned : null;
}

function normalizeImages(
  images: ProductImage[] | undefined,
  primaryImageUrl: string | null | undefined,
  primaryImageOriginalUrl: string | null | undefined,
  primaryImageDisplayUrl: string | null | undefined,
  primaryImageThumbnailUrl: string | null | undefined,
  mode: GalleryMode,
): GalleryItem[] {
  const items: GalleryItem[] = [];
  const seen = new Set<string> ();

  for (const image of images ?? []) {
    const originalUrl = cleanUrl(image.original_url) ?? cleanUrl(image.url);
    const displayUrl = mode === 'catalog'
      ? cleanUrl(image.thumbnail_url) ?? cleanUrl(image.display_url) ?? originalUrl
      : cleanUrl(image.display_url) ?? cleanUrl(image.thumbnail_url) ?? originalUrl;
    if (!originalUrl || !displayUrl) continue;
    const identity = `${image.id}:${originalUrl}`;
    if (seen.has(identity)) continue;
    seen.add(identity);
    items.push({
      key: identity,
      originalUrl,
      displayUrl,
      primary: Boolean(image.primary),
    });
  }

  const primaryOriginal = cleanUrl(primaryImageOriginalUrl) ?? cleanUrl(primaryImageUrl);
  const primaryDisplay = mode === 'catalog'
    ? cleanUrl(primaryImageThumbnailUrl) ?? cleanUrl(primaryImageDisplayUrl) ?? primaryOriginal
    : cleanUrl(primaryImageDisplayUrl) ?? cleanUrl(primaryImageThumbnailUrl) ?? primaryOriginal;

  if (primaryOriginal && primaryDisplay) {
    const existing = items.find((item) => item.originalUrl === primaryOriginal);
    if (existing) {
      existing.primary = true;
      existing.displayUrl = primaryDisplay;
    } else {
      items.unshift({
        key: `primary:${primaryOriginal}`,
        originalUrl: primaryOriginal,
        displayUrl: primaryDisplay,
        primary: true,
      });
    }
  }

  return items;
}

export function ProductImageGallery({
  productName,
  productSku,
  productSlug,
  primaryImageUrl,
  primaryImageOriginalUrl,
  primaryImageDisplayUrl,
  primaryImageThumbnailUrl,
  images,
  mode,
  stopParentPress = false,
}: ProductImageGalleryProps) {
  const { colors: themeColors } = useAppTheme();
  const styles = useThemedStyles(createStyles);
  const feedback = useAppFeedback();
  const [downloadingKey, setDownloadingKey] = useState<string | null> (null);

  const needsDetail = mode === 'catalog' && images === undefined && Boolean(productSlug);
  const detailQuery = useQuery({
    queryKey: ['product', productSlug],
    queryFn: () => api.catalog.product(String(productSlug)),
    enabled: needsDetail,
    staleTime: 45_000,
  });

  const resolvedImages = useMemo(
    () => normalizeImages(
      images ?? detailQuery.data?.images,
      detailQuery.data?.primary_image_url ?? primaryImageUrl,
      detailQuery.data?.primary_image_original_url ?? primaryImageOriginalUrl,
      detailQuery.data?.primary_image_display_url ?? primaryImageDisplayUrl,
      detailQuery.data?.primary_image_thumbnail_url ?? primaryImageThumbnailUrl,
      mode,
    ),
    [
      detailQuery.data?.images,
      detailQuery.data?.primary_image_url,
      detailQuery.data?.primary_image_original_url,
      detailQuery.data?.primary_image_display_url,
      detailQuery.data?.primary_image_thumbnail_url,
      images,
      mode,
      primaryImageDisplayUrl,
      primaryImageOriginalUrl,
      primaryImageThumbnailUrl,
      primaryImageUrl,
    ],
  );

  const stopEvent = (event: GestureResponderEvent) => {
    if (stopParentPress) event.stopPropagation();
  };

  const handleDownload = async (
    image: GalleryItem,
    index: number,
    event: GestureResponderEvent,
  ) => {
    stopEvent(event);
    if (downloadingKey) return;
    setDownloadingKey(image.key);
    try {
      await downloadAndShareProductImage({
        originalUrl: image.originalUrl,
        productName,
        productSku,
        imageNumber: index + 1,
      });
    } catch (error) {
      feedback.notify({
        tone: 'danger',
        title: 'Preuzimanje slike nije uspelo',
        message: error instanceof Error ? error.message : 'Pokušaj ponovo.',
      });
    } finally {
      setDownloadingKey(null);
    }
  };

  if (needsDetail && detailQuery.isLoading) {
    return (
      <View style={styles.loading} onTouchStart={stopEvent}>
        <Text style={styles.loadingText}>Učitavanje svih slika...</Text>
      </View>
    );
  }

  if (needsDetail && detailQuery.isError) {
    return (
      <View style={styles.loading} onTouchStart={stopEvent}>
        <Text style={styles.loadingText}>Slike trenutno nisu dostupne.</Text>
        <Pressable
          accessibilityRole="button"
          onPress={(event) => {
            stopEvent(event);
            void detailQuery.refetch();
          }}
          style={({ pressed }) => [styles.retryButton, pressed && styles.pressed]}
        >
          <Text style={styles.retryText}>Pokušaj ponovo</Text>
        </Pressable>
      </View>
    );
  }

  if (resolvedImages.length === 0) {
    return (
      <View style={styles.empty} onTouchStart={stopEvent}>
        <Glyph name="box" size={30} color={themeColors.primary} />
        <Text style={styles.loadingText}>Artikal nema sačuvane fotografije.</Text>
      </View>
    );
  }

  return (
    <View style={styles.root} onTouchStart={stopEvent}>
      <View style={styles.header}>
        <Text style={styles.title}>Fotografije</Text>
        <Text style={styles.count}>{resolvedImages.length} ukupno</Text>
      </View>

      <FlatList
        data={resolvedImages}
        horizontal
        nestedScrollEnabled
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={styles.scrollContent}
        keyExtractor={(item) => item.key}
        initialNumToRender={2}
        maxToRenderPerBatch={3}
        windowSize={3}
        removeClippedSubviews
        renderItem={({ item: image, index }) => {
          const downloading = downloadingKey === image.key;
          return (
            <View
              style={[
                styles.tile,
                mode === 'catalog' ? styles.catalogTile : styles.detailTile,
              ]}
            >
              <Image
                source={{ uri: image.displayUrl }}
                style={[
                  styles.image,
                  mode === 'catalog' ? styles.catalogImage : styles.detailImage,
                ]}
                contentFit="contain"
                cachePolicy="memory-disk"
                transition={120}
                recyclingKey={image.key}
              />

              <View style={styles.metaRow}>
                <View style={styles.labelWrap}>
                  <Text style={styles.imageLabel}>Slika {index + 1}</Text>
                  {image.primary ? <Text style={styles.primaryLabel}>Glavna</Text> : null}
                </View>
                <Pressable
                  accessibilityRole="button"
                  accessibilityLabel={`Preuzmi originalnu sliku ${index + 1}`}
                  disabled={Boolean(downloadingKey)}
                  onPress={(event) => void handleDownload(image, index, event)}
                  style={({ pressed }) => [
                    styles.downloadButton,
                    pressed && styles.pressed,
                    downloadingKey && !downloading ? styles.disabled : null,
                  ]}
                >
                  <Text style={styles.downloadText}>
                    {downloading ? 'Priprema originala...' : 'Preuzmi original'}
                  </Text>
                </Pressable>
              </View>
            </View>
          );
        }}
      />
    </View>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    root: { gap: spacing.sm },
    header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', gap: spacing.sm },
    title: { ...typography.h3, color: theme.ink },
    count: { ...typography.small, color: theme.muted },
    scrollContent: { gap: spacing.md, paddingRight: spacing.sm },
    tile: { borderWidth: 1, borderColor: theme.line, borderRadius: radii.lg, overflow: 'hidden', backgroundColor: theme.surface },
    catalogTile: { width: 172 },
    detailTile: { width: 286 },
    image: { width: '100%', backgroundColor: theme.surfaceMuted },
    catalogImage: { height: 126 },
    detailImage: { height: 236 },
    metaRow: { minHeight: 52, paddingHorizontal: spacing.sm, paddingVertical: spacing.sm, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: spacing.sm, borderTopWidth: 1, borderTopColor: theme.line },
    labelWrap: { flex: 1, minWidth: 0 },
    imageLabel: { ...typography.small, color: theme.ink },
    primaryLabel: { ...typography.small, color: theme.primary, marginTop: 2 },
    downloadButton: { minHeight: 36, paddingHorizontal: spacing.md, borderRadius: radii.pill, backgroundColor: theme.primarySoft, alignItems: 'center', justifyContent: 'center' },
    downloadText: { ...typography.label, color: theme.primaryDark },
    loading: { minHeight: 72, borderWidth: 1, borderColor: theme.line, borderRadius: radii.md, backgroundColor: theme.surfaceMuted, padding: spacing.md, alignItems: 'center', justifyContent: 'center', gap: spacing.sm },
    empty: { minHeight: 92, borderWidth: 1, borderColor: theme.line, borderRadius: radii.md, backgroundColor: theme.surfaceMuted, padding: spacing.md, alignItems: 'center', justifyContent: 'center', gap: spacing.sm },
    loadingText: { ...typography.small, color: theme.muted, textAlign: 'center' },
    retryButton: { minHeight: 34, paddingHorizontal: spacing.md, borderRadius: radii.pill, borderWidth: 1, borderColor: theme.line, backgroundColor: theme.surface, alignItems: 'center', justifyContent: 'center' },
    retryText: { ...typography.label, color: theme.primary },
    pressed: { opacity: 0.72 },
    disabled: { opacity: 0.45 },
  });
}
CMD_RESULT=product-image-gallery-snippet :: PASS
CREATE_SCREEN_FILE=NOT_FOUND_BY_GREP

============================================================
3. LARAVEL SERVICE AUDIT
============================================================
CMD=catalog-product-controller
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductLine;
use App\Models\ProductType;
use App\Models\User;
use App\Services\CatalogAccessService;
use App\Services\ProductAdminService;
use App\Services\ProductAnnouncementService;
use App\Services\ProductImageService;
use App\Services\StorageSpecificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// MOBILE_ADMIN_PRODUCT_CREATE_BATCH2B_STORAGE_METADATA_V06
final class CatalogProductController extends Controller
{
    public function __construct(
        private readonly CatalogAccessService $catalogAccess,
        private readonly StorageSpecificationService $storageSpecifications,
    ) {
    }

    // MOBILE_V0_8_PRODUCT_EDIT_ARCHIVE_RESTORE_BATCH8
    public function index(Request $request): JsonResponse
    {
        return $this->productList($request, false);
    }

    public function archived(Request $request): JsonResponse
    {
        return $this->productList($request, true);
    }

    public function show(Request $request, Product $product): JsonResponse
    {
        $actor = $this->actor($request);
        abort_unless($this->catalogAccess->canManage($product, $actor), 404);

        return response()->json(['data' => $this->detailPayload($product, $actor)]);
    }

    public function options(Request $request): JsonResponse
    {
        $storageRepeaterCount = 0;

        $types = ProductType::query()
            ->with([
                'category',
                'fields' => fn ($query) => $query
                    ->where('specification_fields.status', 'active')
                    ->orderBy('product_type_fields.sort_order')
                    ->orderBy('specification_fields.name'),
                'fields.options' => fn ($query) => $query
                    ->where('status', 'active')
                    ->orderBy('sort_order')
                    ->orderBy('label'),
                'fields.options.parentOptions' => fn ($query) => $query
                    ->select('specification_options.id')
                    ->where('specification_options.status', 'active'),
            ])
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(function (ProductType $type) use (&$storageRepeaterCount): array {
                $storageMap = $this->storageRepeaterMap($type);
                $storageRepeaterCount += count($storageMap);
                $derivedIds = array_values(array_unique(array_map(
                    static fn (array $metadata): int => (int) $metadata['total_field_id'],
                    $storageMap,
                )));

                return [
                    'id' => (int) $type->id,
                    'name' => (string) $type->name,
                    'category_id' => $type->category_id !== null ? (int) $type->category_id : null,
                    'category_name' => $type->category?->name,
                    'auto_name_enabled' => (bool) $type->auto_name_enabled,
                    'fields' => $type->fields->map(static function ($field) use ($storageMap, $derivedIds): array {
                        $fieldId = (int) $field->id;
                        return [
                            'id' => $fieldId,
                            'name' => (string) $field->name,
                            'slug' => (string) $field->slug,
                            'data_type' => (string) $field->data_type,
                            'filter_type' => $field->filter_type !== null ? (string) $field->filter_type : null,
                            'unit' => $field->unit !== null ? (string) $field->unit : null,
                            'min_value' => $field->min_value !== null ? (float) $field->min_value : null,
                            'max_value' => $field->max_value !== null ? (float) $field->max_value : null,
                            'parent_field_id' => $field->parent_field_id !== null ? (int) $field->parent_field_id : null,
                            'detail_input_enabled' => (bool) $field->detail_input_enabled,
                            'detail_label' => $field->detail_label !== null ? (string) $field->detail_label : null,
                            'required' => (bool) ($field->pivot?->getAttribute('is_required') ?? false),
                            'default_value' => $field->pivot?->getAttribute('default_value'),
                            'read_only_derived' => in_array($fieldId, $derivedIds, true),
                            'storage_repeater' => $storageMap[$fieldId] ?? null,
                            'options' => $field->options->map(static fn ($option): array => [
                                'id' => (int) $option->id,
                                'label' => (string) $option->label,
                                'value' => (string) $option->value,
                                'parent_option_ids' => $option->parentOptions
                                    ->pluck('id')
                                    ->map(static fn ($id): int => (int) $id)
                                    ->values()
                                    ->all(),
                            ])->values()->all(),
                        ];
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();

        // MOBILE_ADMIN_PRODUCT_CREATE_TYPE_SCOPED_TAXONOMY_V07
        $brandTypeMap = \Illuminate\Support\Facades\DB::table('brand_product_type')
            ->orderBy('brand_id')
            ->orderBy('product_type_id')
            ->get(['brand_id', 'product_type_id'])
            ->groupBy('brand_id')
            ->map(static fn ($rows): array => $rows->pluck('product_type_id')->map(static fn ($id): int => (int) $id)->values()->all())
            ->all();
        $lineTypeMap = \Illuminate\Support\Facades\DB::table('product_line_product_type')
            ->orderBy('product_line_id')
            ->orderBy('product_type_id')
            ->get(['product_line_id', 'product_type_id'])
            ->groupBy('product_line_id')
            ->map(static fn ($rows): array => $rows->pluck('product_type_id')->map(static fn ($id): int => (int) $id)->values()->all())
            ->all();

        $brands = Brand::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(static fn (Brand $brand): array => [
                'id' => (int) $brand->id,
                'name' => (string) $brand->name,
                'product_type_ids' => $brandTypeMap[(int) $brand->id] ?? [],
            ])
            ->values()
            ->all();

        $lines = ProductLine::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'brand_id'])
            ->map(static fn (ProductLine $line): array => [
                'id' => (int) $line->id,
                'name' => (string) $line->name,
                'brand_id' => $line->brand_id !== null ? (int) $line->brand_id : null,
                'product_type_ids' => $lineTypeMap[(int) $line->id] ?? [],
            ])
            ->values()
            ->all();

        $user = $request->user();
        $canManageImages = $user !== null
            && method_exists($user, 'hasPermission')
            && $user->hasPermission('catalog.manage_images');

        return response()->json(['data' => [
            'types' => $types,
            'brands' => $brands,
            'lines' => $lines,
            'defaults' => [
                'status' => 'draft',
                'price_currency' => 'EUR',
                'stock_quantity' => 0,
                'low_stock_threshold' => 1,
            ],
            'currencies' => [
                ['value' => 'EUR', 'label' => 'EUR'],
                ['value' => 'RSD', 'label' => 'RSD'],
            ],
            'statuses' => [
                ['value' => 'draft', 'label' => 'Nacrt'],
                ['value' => 'active', 'label' => 'Aktivan'],
                ['value' => 'inactive', 'label' => 'Neaktivan'],
            ],
            'capabilities' => [
                'advanced_specifications' => true,
                'image_upload' => $canManageImages,
                'specialized_storage_repeater' => $storageRepeaterCount > 0,
            ],
            'image_limits' => [
                'max_files' => 20,
                'max_bytes' => 10 * 1024 * 1024,
                'mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
                'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
            ],
        ]]);
    }

    // MOBILE_BUILD16_PRODUCT_CREATE_IDEMPOTENCY_BATCH134
    public function store(
        ProductRequest $request,
        ProductAdminService $service,
        ProductAnnouncementService $announcements,
        \App\Services\IdempotencyService $idempotency,
    ): JsonResponse {
        $validated = $request->validated();
        $actor = $request->user();
        $idempotencyKey = trim((string) $request->header('Idempotency-Key', ''));

        /** @var Product $product */
        $product = $idempotencyKey !== ''
            ? $idempotency->run(
                $actor,
                'catalog.product.create',
                $idempotencyKey,
                $validated,
                Product::class,
                fn (): Product => $service->create($validated, $actor),
                static fn (int $id): Product => Product::query()->findOrFail($id),
            )
            : $service->create($validated, $actor);

        // Announcement queues already deduplicate by product/recipient, so an idempotent replay is safe.
        $announcementCount = $announcements->queueForNewlyPublished($product, $actor);

        return response()->json([
            'message' => 'Artikal je uspešno kreiran.',
            'data' => [
                'id' => (int) $product->id,
                'slug' => (string) $product->slug,
                'sku' => (string) $product->sku,
                'name' => (string) $product->name,
                'status' => (string) $product->status,
            ],
            'announcement_count' => (int) $announcementCount,
        ], 201);
    }

    public function update(
        ProductRequest $request,
        Product $product,
        ProductAdminService $service,
        ProductAnnouncementService $announcements,
    ): JsonResponse {
        $actor = $this->actor($request);
        abort_unless($this->catalogAccess->canManage($product, $actor), 404);
        abort_if($product->deleted_at !== null, 409, 'Arhivirani artikal prvo vrati iz arhive.');

        $wasActive = (string) $product->status === 'active';
        $updated = $service->update($product, $request->validated(), $actor);
        $announcementCount = !$wasActive && (string) $updated->status === 'active'
            ? $announcements->queueForNewlyPublished($updated, $actor)
            : 0;

        return response()->json([
            'message' => 'Izmene su sačuvane.',
            'data' => $this->detailPayload($updated, $actor),
            'announcement_count' => (int) $announcementCount,
        ]);
    }

    public function archive(Request $request, Product $product, ProductAdminService $service): JsonResponse
    {
        $actor = $this->actor($request);
        abort_unless($this->catalogAccess->canManage($product, $actor), 404);

        if ($product->deleted_at === null) {
            $service->archive($product, $actor);
            $product->refresh();
        }

        return response()->json([
            'message' => 'Artikal je arhiviran.',
            'data' => $this->detailPayload($product, $actor),
        ]);
    }

    public function restore(Request $request, Product $product, ProductAdminService $service): JsonResponse
    {
        $actor = $this->actor($request);
        abort_unless($this->catalogAccess->canManage($product, $actor), 404);

        if ($product->deleted_at !== null) {
            $service->restore($product, $actor);
            $product->refresh();
        }

        return response()->json([
            'message' => 'Artikal je vraćen iz arhive.',
            'data' => $this->detailPayload($product, $actor),
        ]);
    }

    // MOBILE_V0_8_SUPERADMIN_DIRECT_SALE_BATCH10
    public function directSaleOptions(
        Request $request,
        Product $product,
        \App\Services\SettingsService $settings,
    ): JsonResponse {
        $actor = $this->actor($request);
        abort_unless($actor->hasRole('superadmin'), 403);
        abort_unless($this->catalogAccess->canManage($product, $actor), 404);

        $product->refresh();
        $rate = $settings->eurRsdRate();
        $catalogUnitPriceRsd = null;
        if ((string) $product->price_currency === 'RSD') {
            $catalogUnitPriceRsd = round((float) $product->price_amount, 2);
        } elseif ($rate !== null && $rate > 0) {
            $catalogUnitPriceRsd = round((float) $product->price_amount * $rate, 2);
        }

        $blockingReason = null;
        if ($product->deleted_at !== null) {
            $blockingReason = 'Arhiviran artikal nije dostupan za direktnu prodaju.';
        } elseif (!in_array((string) $product->status, ['active', 'inactive'], true)) {
            $blockingReason = 'Direktna prodaja je dostupna samo za aktivan ili neaktivan artikal.';
        } elseif ((int) $product->stock_quantity < 1) {
CMD_RESULT=catalog-product-controller :: PASS
CMD=product-admin-service
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductLine;
use App\Models\ProductType;
use App\Models\SpecificationField;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\WarrantyRule;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ProductAdminService
{
    public function __construct(
        private readonly ProductSkuGenerator $skuGenerator,
        private readonly ProductShortSkuSequenceService $shortSkuSequence,
        private readonly ProductImageService $images,
        private readonly ProductTemplateService $templates,
        private readonly StorageSpecificationService $storageSpecifications,
        private readonly AuditLogger $audit,
    ) {}

    /** @param array<string,mixed> $data */
    public function create(array $data, User $user): Product
    {
        return DB::transaction(function () use ($data, $user): Product {
            [$data, $specs, $specDetails, $specStructured, $files] = $this->prepareTemplateData($data);
            $generated = $this->shouldGenerateName($data);
            if ($generated) $data['name'] = $this->generateProductName($data, $specs, $specDetails);
            if (trim((string) ($data['name'] ?? '')) === '') throw ValidationException::withMessages(['name' => 'Naziv artikla nije mogao biti formiran. Proveri šablon naziva i specifikacije.']);

            $type = $this->typeFromData($data);
            $completeness = $this->templates->completeness($type, $data, $specs, $specDetails);
            $data['completeness_percent'] = $completeness['percent'];
            $data['name_is_manual'] = !$generated;
            $data['sku'] = $this->generateSku($data + ['specs' => $specs, 'spec_details' => $specDetails]);
            $data['slug'] = $this->uniqueSlug((string) $data['name']);
            $data['created_by'] = $user->id;
            $data['updated_by'] = $user->id;
            $data['locally_modified_at'] = now();
            $categories = array_map('intval', $data['category_ids'] ?? []);
            unset($data['category_ids'], $data['specs'], $data['spec_details'], $data['spec_lists'], $data['spec_capacities'], $data['spec_structured'], $data['images'], $data['regenerate_sku'], $data['regenerate_name']);

            $product = Product::query()->create($data);
            if ((int) $product->stock_quantity > 0) {
                StockMovement::query()->create([
                    'event_key' => 'product:'.$product->id.':initial',
                    'product_id' => $product->id,
                    'user_id' => $user->id,
                    'movement_type' => 'initial',
                    'source' => 'product_creation',
                    'quantity_change' => (int) $product->stock_quantity,
                    'quantity_before' => 0,
                    'quantity_after' => (int) $product->stock_quantity,
                    'note' => 'Početno stanje pri kreiranju artikla.',
                    'metadata_json' => ['tracked' => true],
                ]);
            }
            $product->categories()->sync($categories);
            $this->syncSpecifications($product, $specs, $specDetails, $specStructured);
            $this->images->upload($product, $files);
            $this->audit->log('product.created', 'Kreiran artikal '.$product->sku, $product, after: $this->snapshot($product), metadata: ['completeness' => $completeness]);
            return $product;
        }, 3);
    }

    /** @param array<string,mixed> $data */
    public function update(Product $product, array $data, User $user): Product
    {
        return DB::transaction(function () use ($product, $data, $user): Product {
            /** @var Product $locked */
            $locked = Product::query()->lockForUpdate()->findOrFail($product->id);
            $before = $this->snapshot($locked);
            $stockBefore = (int) $locked->stock_quantity;
            [$data, $specs, $specDetails, $specStructured, $files] = $this->prepareTemplateData($data);

            $generated = $this->shouldGenerateName($data);
            if ($generated) $data['name'] = $this->generateProductName($data, $specs, $specDetails);
            if (trim((string) ($data['name'] ?? '')) === '') throw ValidationException::withMessages(['name' => 'Naziv artikla nije mogao biti formiran. Proveri šablon naziva i specifikacije.']);

            $type = $this->typeFromData($data);
            $completeness = $this->templates->completeness($type, $data, $specs, $specDetails);
            $data['completeness_percent'] = $completeness['percent'];
            $submittedName = trim((string) ($data['name'] ?? ''));
            $data['name_is_manual'] = $generated
                ? false
                : ($submittedName !== trim((string) $locked->name) ? true : (bool) $locked->name_is_manual);
            if (!empty($data['regenerate_sku'])) $data['sku'] = $this->generateSku($data + ['specs' => $specs, 'spec_details' => $specDetails], $locked->id);
            $data['slug'] = $this->uniqueSlug((string) $data['name'], $locked->id);
            $data['updated_by'] = $user->id;
            $data['locally_modified_at'] = now();
            $categories = array_map('intval', $data['category_ids'] ?? []);
            unset($data['category_ids'], $data['specs'], $data['spec_details'], $data['spec_lists'], $data['spec_capacities'], $data['spec_structured'], $data['images'], $data['regenerate_sku'], $data['regenerate_name']);

            $locked->update($data);
            $stockAfter = (int) $locked->stock_quantity;
            if ($stockAfter !== $stockBefore) {
                StockMovement::query()->create([
                    'event_key' => 'product-edit:'.(string) Str::uuid(),
                    'product_id' => $locked->id,
                    'user_id' => $user->id,
                    'movement_type' => 'manual_adjustment',
                    'source' => 'product_edit',
                    'quantity_change' => $stockAfter - $stockBefore,
                    'quantity_before' => $stockBefore,
                    'quantity_after' => $stockAfter,
                    'note' => 'Korekcija lagera kroz izmenu artikla.',
                    'metadata_json' => ['tracked' => true],
                ]);
            }
            $locked->categories()->sync($categories);
            $this->syncSpecifications($locked, $specs, $specDetails, $specStructured);
            $this->images->upload($locked, $files);
            $locked->refresh();
            $this->audit->log('product.updated', 'Izmenjen artikal '.$locked->sku, $locked, $before, $this->snapshot($locked), ['completeness' => $completeness]);
            return $locked;
        }, 3);
    }

    /** @param array<string,mixed> $options */
    public function clone(Product $source, array $options, User $user): Product
    {
        return DB::transaction(function () use ($source, $options, $user): Product {
            $source->loadMissing(['categories', 'specificationValues.field', 'images', 'warrantyRules']);
            $copyBasic = (bool) ($options['copy_basic'] ?? true);
            $copySpecs = (bool) ($options['copy_specifications'] ?? true);
            $copyPrice = (bool) ($options['copy_price'] ?? true);
            $copyDescription = (bool) ($options['copy_description'] ?? true);

            $specs = [];
            $details = [];
            $structured = [];
            if ($copySpecs) {
                foreach ($source->specificationValues as $value) {
                    $raw = $value->value_text ?? $value->value_number ?? ($value->value_boolean === null ? null : (int) $value->value_boolean);
                    if ($raw !== null) $specs[(int) $value->field_id] = $raw;
                    if ($value->value_detail !== null) $details[(int) $value->field_id] = $value->value_detail;
                    if (is_array($value->value_json) && $value->value_json !== []) $structured[(int) $value->field_id] = $value->value_json;
                }
            }

            $data = [
                'product_type_id' => $copyBasic ? $source->product_type_id : null,
                'brand_id' => $copyBasic ? $source->brand_id : null,
                'product_line_id' => $copyBasic ? $source->product_line_id : null,
                'model_name' => $copyBasic ? $source->model_name : null,
                'name' => trim((string) ($options['name'] ?? '')) ?: $source->name.' — kopija',
                'price_amount' => $copyPrice ? $source->price_amount : 0,
                'purchase_price_rsd' => $copyPrice ? $source->purchase_price_rsd : null,
                'price_currency' => $copyPrice ? $source->price_currency : 'EUR',
                'manual_commission_eur' => $copyPrice ? $source->manual_commission_eur : null,
                'description' => $copyDescription ? $source->description : 'Kloniran artikal — dopuniti opis.',
                'notes' => !empty($options['copy_notes']) ? $source->notes : null,
                'stock_quantity' => 0,
                'low_stock_threshold' => $source->low_stock_threshold,
                'status' => 'draft',
                'source_product_id' => $source->id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'locally_modified_at' => now(),
            ];
            if (!empty($options['regenerate_name']) && $data['product_type_id']) {
                $data['name'] = $this->generateProductName($data, $specs, $details);
                $data['name_is_manual'] = false;
            } else {
                $data['name_is_manual'] = true;
            }
            $data['name'] = $this->uniqueCloneName((string) $data['name']);
            $data['slug'] = $this->uniqueSlug((string) $data['name']);
            $data['sku'] = $this->generateSku($data + ['specs' => $specs, 'spec_details' => $details]);
            $typeCategoryId = !empty($data['product_type_id'])
                ? ProductType::query()->whereKey((int) $data['product_type_id'])->value('category_id')
                : null;
            $categoryIds = $typeCategoryId !== null ? [(int) $typeCategoryId] : [];
            $completeness = $this->templates->completeness($this->typeFromData($data), $data + ['category_ids' => $categoryIds], $specs, $details);
            $data['completeness_percent'] = $completeness['percent'];

            $clone = Product::query()->create($data);
            $clone->categories()->sync($categoryIds);
            if ($copySpecs) $this->syncSpecifications($clone, $specs, $details, $structured);
            if (!empty($options['copy_images'])) $this->images->cloneImages($source, $clone);
            if (!empty($options['copy_warranty_rules']) && Schema::hasTable('warranty_rules')) {
                $this->cloneWarrantyRules($source, $clone, $user);
            }

            $this->audit->log('product.cloned', 'Kloniran artikal '.$source->sku.' kao '.$clone->sku, $clone, after: $this->snapshot($clone), metadata: ['source_product_id' => $source->id, 'options' => $options]);
            return $clone;
        }, 3);
    }

    public function regenerateName(Product $product, User $user): Product
    {
        return DB::transaction(function () use ($product, $user): Product {
            $locked = Product::query()->with([
                'type.fields' => fn ($query) => $query->where('specification_fields.status', 'active')->orderBy('product_type_fields.sort_order'),
                'specificationValues',
            ])->lockForUpdate()->findOrFail($product->id);
            $specs = [];
            $details = [];
            $structured = [];
            foreach ($locked->specificationValues as $value) {
                $specs[(int) $value->field_id] = $value->value_text ?? $value->value_number ?? ($value->value_boolean === null ? null : (int) $value->value_boolean);
                $details[(int) $value->field_id] = $value->value_detail;
            }
            $data = $locked->toArray();
            $name = $this->generateProductName($data, $specs, $details);
            if ($name === '') throw ValidationException::withMessages(['name' => 'Naziv nije mogao biti formiran iz šablona. Proveri podatke artikla i šablon.']);
            $before = $this->snapshot($locked);
            $locked->update(['name' => $name, 'slug' => $this->uniqueSlug($name, $locked->id), 'name_is_manual' => false, 'updated_by' => $user->id, 'locally_modified_at' => now()]);
            $this->audit->log('product.name.regenerated', 'Regenerisan naziv artikla '.$locked->sku, $locked, $before, $this->snapshot($locked));
            return $locked;
        }, 3);
    }

    public function archive(Product $product, User $user): void
    {
        $before = $this->snapshot($product);
        $product->update(['status' => 'archived', 'deleted_at' => now(), 'updated_by' => $user->id, 'locally_modified_at' => now()]);
        $this->audit->log('product.archived', 'Arhiviran artikal '.$product->sku, $product, $before, $this->snapshot($product));
    }

    public function restore(Product $product, User $user): void
    {
        $before = $this->snapshot($product);
        $product->update(['status' => 'inactive', 'deleted_at' => null, 'updated_by' => $user->id, 'locally_modified_at' => now()]);
        $this->audit->log('product.restored', 'Vraćen artikal '.$product->sku, $product, $before, $this->snapshot($product));
    }

    /** @param array<string,mixed> $data @return array{0:array<string,mixed>,1:array<int|string,mixed>,2:array<int|string,mixed>,3:array<int|string,array<int,array<string,mixed>>>,4:array<int,mixed>} */
    private function prepareTemplateData(array $data): array
    {
        $type = $this->typeFromData($data);
        $specs = (array) ($data['specs'] ?? []);
        $details = (array) ($data['spec_details'] ?? []);
        $structured = (array) ($data['spec_structured'] ?? []);
        if ($type !== null) {
            $allowedKeys = $type->fields->pluck('id')->mapWithKeys(static fn ($id): array => [(int) $id => true])->all();
            $specs = array_intersect_key($specs, $allowedKeys);
            $details = array_intersect_key($details, $allowedKeys);
            $structured = array_intersect_key($structured, $allowedKeys);
        }
        $defaults = $this->templates->defaults($type);
        foreach ($defaults['specs'] as $fieldId => $value) if (!array_key_exists($fieldId, $specs) || $specs[$fieldId] === '') $specs[$fieldId] = $value;
        foreach ($defaults['details'] as $fieldId => $value) if (!array_key_exists($fieldId, $details) || $details[$fieldId] === '') $details[$fieldId] = $value;
        if ($type !== null) $this->storageSpecifications->applyComputedTotals($type->fields, $specs, $structured);
        if (empty($data['status']) && $type !== null) $data['status'] = $defaults['status'];
        return [$data, $specs, $details, $structured, (array) ($data['images'] ?? [])];
    }

    /** @param array<string,mixed> $data */
    private function shouldGenerateName(array $data): bool
    {
        if (!empty($data['regenerate_name'])) return true;
        if (trim((string) ($data['name'] ?? '')) !== '') return false;
        return (bool) ($this->typeFromData($data)?->auto_name_enabled ?? false);
    }

    /** @param array<string,mixed> $data @param array<int|string,mixed> $specs @param array<int|string,mixed> $details */
    private function generateProductName(array $data, array $specs, array $details): string
    {
        return $this->templates->generateName($this->typeFromData($data), $data, $specs, $details);
    }

    /** @param array<string,mixed> $data */
    private function typeFromData(array $data): ?ProductType
    {
        $id = (int) ($data['product_type_id'] ?? 0);
        return $id > 0 ? ProductType::query()->with([
            'fields' => fn ($query) => $query->where('specification_fields.status', 'active')->orderBy('product_type_fields.sort_order'),
        ])->find($id) : null;
    }

    /** @param array<string,mixed> $data */
    private function generateSku(array $data, ?int $ignoreId = null): string
    {
        $prefix = '';

        if (!empty($data['brand_id'])) {
            $prefix = trim((string) Brand::query()->whereKey((int) $data['brand_id'])->value('name'));
        }

        if ($prefix === '') {
            $categoryId = null;
            $categoryIds = array_values(array_filter(
                array_map('intval', (array) ($data['category_ids'] ?? [])),
                static fn (int $id): bool => $id > 0,
            ));

            if ($categoryIds !== []) {
                $categoryId = $categoryIds[0];
            } elseif (!empty($data['product_type_id'])) {
                $resolvedCategoryId = ProductType::query()
                    ->whereKey((int) $data['product_type_id'])
                    ->value('category_id');
                if ($resolvedCategoryId !== null) {
                    $categoryId = (int) $resolvedCategoryId;
                }
            }

            if ($categoryId !== null) {
                $prefix = trim((string) \App\Models\Category::query()->whereKey($categoryId)->value('name'));
            }
        }

        if ($prefix === '') {
            $prefix = 'ARTIKAL';
        }

        return $this->shortSkuSequence->generate(
            $prefix,
            static function (string $candidate) use ($ignoreId): bool {
                $productExists = Product::query()
                    ->whereRaw('LOWER(sku) = ?', [mb_strtolower($candidate)])
                    ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                    ->exists();

                if ($productExists) {
                    return true;
                }

                return false;
            },
        );
    }
    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'artikal';
        $slug = $base;
        $i = 2;
        while (Product::query()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) $slug = $base.'-'.$i++;
        return $slug;
    }

    private function uniqueCloneName(string $name): string
    {
        $base = trim($name) !== '' ? trim($name) : 'Klonirani artikal';
        $candidate = $base;
        $i = 2;
        while (Product::query()->where('name', $candidate)->exists()) $candidate = $base.' '.$i++;
        return mb_substr($candidate, 0, 190);
    }

    /** @param array<int|string,mixed> $specs @param array<int|string,mixed> $details @param array<int|string,array<int,array<string,mixed>>> $structured */
    private function syncSpecifications(Product $product, array $specs, array $details = [], array $structured = []): void
    {
        DB::table('product_spec_values')->where('product_id', $product->id)->delete();
        if ($product->product_type_id === null) return;
        $allowed = SpecificationField::query()
            ->where('status', 'active')
            ->whereHas('productTypes', fn ($q) => $q->where('product_types.id', $product->product_type_id))
            ->get()
CMD_RESULT=product-admin-service :: PASS
CMD=direct-sale-service
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\OrderDelivery;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

final class DirectSaleService
{
    public function __construct(
        private readonly IdempotencyService $idempotency,
        private readonly SettingsService $settings,
        private readonly DocumentNumberService $numbers,
        private readonly AuditLogger $audit,
        private readonly WarrantyService $warranties,
        private readonly ReceivablesService $receivables,
    ) {
    }

    /** @param array<string,mixed> $input */
    public function record(Product $product, User $actor, array $input, string $idempotencyKey): Order
    {
        abort_unless($actor->hasRole('superadmin'), 403);

        $paymentMethod = trim((string) ($input['payment_method'] ?? ''));
        if (!in_array($paymentMethod, ['cash', 'card', 'bank_transfer', 'other', 'deferred_payment'], true)) {
            throw ValidationException::withMessages([
                'payment_method' => 'Izabrani način plaćanja nije dozvoljen za direktnu prodaju.',
            ]);
        }

        $buyerName = trim((string) ($input['buyer_name'] ?? ''));
        if ($buyerName === '') {
            $buyerName = 'Krajnji kupac';
        }
        $buyerPhone = trim((string) ($input['buyer_phone'] ?? ''));

        // MOBILE_V0_9_DIRECT_SALE_DEFERRED_PAYMENT_RECEIVABLES_BATCH5B_V2
        $installmentCount = null;
        $paymentDueAt = null;
        if ($paymentMethod === 'deferred_payment') {
            if (!$this->receivables->ready()) {
                throw ValidationException::withMessages([
                    'payment_method' => 'Odloženo plaćanje trenutno nije dostupno jer modul potraživanja nije spreman.',
                ]);
            }

            $installmentCount = (int) ($input['installment_count'] ?? 0);
            if ($installmentCount < 1 || $installmentCount > 24) {
                throw ValidationException::withMessages(['installment_count' => 'Broj rata mora biti između 1 i 24.']);
            }

            $paymentDueAt = trim((string) ($input['payment_due_at'] ?? ''));
            $parsedDue = \DateTimeImmutable::createFromFormat('!Y-m-d', $paymentDueAt);
            $today = new \DateTimeImmutable(today()->toDateString());
            if (!$parsedDue || $parsedDue->format('Y-m-d') !== $paymentDueAt || $parsedDue < $today) {
                throw ValidationException::withMessages(['payment_due_at' => 'Konačni datum pune isplate mora biti današnji ili budući datum.']);
            }
        }

        $payload = [
            'product_id' => (int) $product->getKey(),
            'buyer_name' => $buyerName,
            'buyer_phone' => $buyerPhone !== '' ? $buyerPhone : null,
            'quantity' => (int) ($input['quantity'] ?? 0),
            'sale_price_rsd' => round((float) ($input['sale_price_rsd'] ?? 0), 2),
            'payment_method' => $paymentMethod,
        ];
        if ($paymentMethod === 'deferred_payment') {
            $payload['installment_count'] = $installmentCount;
            $payload['payment_due_at'] = $paymentDueAt;
        }

        $order = $this->idempotency->run(
            $actor,
            'direct_sale.record',
            $idempotencyKey,
            $payload,
            Order::class,
            fn (): Order => $this->recordInTransaction($product, $actor, $payload, $idempotencyKey),
            static fn (int $id): Order => Order::query()->findOrFail($id),
        );

        if ($paymentMethod === 'deferred_payment') {
            $this->ensureDeferredReceivablePlan($order, $actor, (int) $installmentCount, (string) $paymentDueAt);
        }

        try {
            $this->warranties->ensureForOrder($order->loadMissing('user'), $actor);
        } catch (Throwable $exception) {
            Log::warning('Direct sale warranty backfill failed', [
                'order_id' => (int) $order->id,
                'actor_id' => (int) $actor->id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }

        return $order->fresh([
            'items.product',
            'payments',
            'delivery',
            'user',
            'directSaleRecorder',
            'commission',
        ]) ?? $order;
    }

    /** @param array{product_id:int,buyer_name:string,buyer_phone:?string,quantity:int,sale_price_rsd:float,payment_method:string,installment_count?:int,payment_due_at?:string} $payload */
    private function recordInTransaction(Product $product, User $actor, array $payload, string $idempotencyKey): Order
    {
        $lockedProduct = Product::query()
            ->with(['brand', 'line', 'type'])
            ->whereKey((int) $product->getKey())
            ->whereIn('status', ['active', 'inactive'])
            ->whereNull('deleted_at')
            ->lockForUpdate()
            ->first();

        if (!$lockedProduct instanceof Product) {
            throw ValidationException::withMessages([
                'product' => 'Artikal više nije dostupan za direktnu prodaju.',
            ]);
        }

        $quantity = $payload['quantity'];
        if ($quantity < 1 || $quantity > 1000) {
            throw ValidationException::withMessages([
                'quantity' => 'Količina mora biti između 1 i 1000.',
            ]);
        }

        $salePrice = round($payload['sale_price_rsd'], 2);
        if ($salePrice <= 0) {
            throw ValidationException::withMessages([
                'sale_price_rsd' => 'Prodajna cena mora biti veća od nule.',
            ]);
        }

        // MOBILE_V1_0_DIRECT_SALE_UNBOUNDED_PRICE_BATCH21
        // The catalog price is a reference/default, not a ceiling. The entered sale price
        // remains SuperAdmin-only and must still be a positive RSD amount.
        $rate = $this->settings->eurRsdRate();
        if ((string) $lockedProduct->price_currency === 'EUR' && ($rate === null || $rate <= 0)) {
            throw ValidationException::withMessages([
                'sale_price_rsd' => 'EUR/RSD kurs mora biti podešen pre direktne prodaje EUR artikla.',
            ]);
        }
        $quantityBefore = (int) $lockedProduct->stock_quantity;

        if ($quantityBefore < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => 'Nema dovoljno artikala na lageru za ovu direktnu prodaju.',
            ]);
        }

        $soldAt = now();
        $lineTotal = round($salePrice * $quantity, 2);
        $deferred = $payload['payment_method'] === 'deferred_payment';
        $fingerprint = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $order = Order::query()->create([
            'source_system' => 'laravel',
            'sales_channel' => 'direct_sale',
            'direct_sale_recorded_by' => (int) $actor->id,
            'order_number' => 'TMP-'.$soldAt->format('YmdHis').'-'.bin2hex(random_bytes(3)),
            'idempotency_key_hash' => hash('sha256', trim($idempotencyKey)),
            'request_fingerprint' => $fingerprint,
            'user_id' => null,
            'supplier_user_id' => null,
            'supplier_name_snapshot' => null,
            'supplier_email_snapshot' => null,
            'supplier_phone_snapshot' => null,
            'supplier_role_snapshot' => null,
            'assigned_at' => null,
            'status' => 'shipped',
            'inventory_state' => 'reserved',
            'inventory_reserved_at' => $soldAt,
            'shipping_full_name' => $payload['buyer_name'],
            'shipping_address' => '',
            'shipping_city' => '',
            'shipping_postal_code' => '',
            'shipping_phone' => $payload['buyer_phone'] ?? '',
            'subtotal_rsd' => $lineTotal,
            'eur_rsd_rate' => $rate,
            'customer_note' => 'Direktna prodaja Super Administratora krajnjem kupcu.',
            'payment_method' => $payload['payment_method'],
            'payment_status' => $deferred ? 'pending' : 'paid',
            'payment_state' => $deferred ? 'unpaid' : 'paid',
            'paid_total_rsd' => $deferred ? 0 : $lineTotal,
            'payment_due_at' => $deferred ? (string) $payload['payment_due_at'] : null,
            'payment_verified_at' => $deferred ? null : $soldAt,
            'completed_at' => $soldAt,
            'completed_by' => (int) $actor->id,
            'completion_note' => 'Direktna prodaja evidentirana i lično dostavljena krajnjem kupcu.',
            'updated_by' => (int) $actor->id,
        ]);

        $orderNumber = sprintf('APC-%s-%08d', $soldAt->format('Ymd'), (int) $order->id);
        $order->forceFill(['order_number' => $orderNumber])->save();

        $purchaseUnit = null;
        $costSource = 'missing';
        if ((float) ($lockedProduct->purchase_price_rsd ?? 0) > 0) {
            $purchaseUnit = round((float) $lockedProduct->purchase_price_rsd, 2);
            $costSource = 'product';
        }

        $item = OrderItem::query()->create([
            'order_id' => (int) $order->id,
            'product_id' => (int) $lockedProduct->id,
            'product_sku' => (string) $lockedProduct->sku,
            'product_name' => (string) $lockedProduct->name,
            'quantity' => $quantity,
            'unit_price_original' => $salePrice,
            'original_currency' => 'RSD',
            'unit_price_rsd' => $salePrice,
            'line_total_rsd' => $lineTotal,
            'purchase_unit_rsd_snapshot' => $purchaseUnit,
            'purchase_total_rsd_snapshot' => $purchaseUnit !== null ? round($purchaseUnit * $quantity, 2) : null,
            'cost_source_snapshot' => $costSource,
            'brand_name_snapshot' => $lockedProduct->brand?->name,
            'product_line_name_snapshot' => $lockedProduct->line?->name,
            'product_type_name_snapshot' => $lockedProduct->type?->name,
            'commission_source_snapshot' => 'direct_sale',
            'commission_rate_percent_snapshot' => 0,
            'commission_unit_eur_snapshot' => 0,
            'commission_total_eur_snapshot' => 0,
        ]);

        $quantityAfter = $quantityBefore - $quantity;
        $lockedProduct->forceFill([
            'stock_quantity' => $quantityAfter,
            'updated_by' => (int) $actor->id,
        ])->save();

        StockMovement::query()->create([
            'event_key' => sprintf('direct-sale:%d:item:%d:sale', (int) $order->id, (int) $item->id),
            'product_id' => (int) $lockedProduct->id,
            'order_id' => (int) $order->id,
            'user_id' => (int) $actor->id,
            'movement_type' => 'sale',
            'source' => 'direct_sale',
            'quantity_change' => -$quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'note' => 'Direktna prodaja '.$orderNumber,
            'metadata_json' => [
                'order_item_id' => (int) $item->id,
                'source_system' => 'laravel',
                'sales_channel' => 'direct_sale',
            ],
        ]);

        if (!$deferred) {
            OrderPayment::query()->create([
                'order_id' => (int) $order->id,
                'payment_number' => $this->numbers->next('payment', (int) $soldAt->format('Y')),
                'entry_type' => 'payment',
                'status' => 'verified',
                'amount_rsd' => $lineTotal,
                'payment_method' => $payload['payment_method'],
                'paid_at' => $soldAt,
                'reference' => 'Direktna prodaja '.$orderNumber,
                'note' => 'Verifikovana uplata evidentirana uz direktnu prodaju.',
                'submitted_by' => (int) $actor->id,
                'verified_by' => (int) $actor->id,
                'verified_at' => $soldAt,
            ]);
        }

        OrderDelivery::query()->create([
            'order_id' => (int) $order->id,
            'delivery_method' => 'own_transport',
            'delivered_at' => $soldAt,
            'recipient_name' => $payload['buyer_name'],
            'recipient_phone' => $payload['buyer_phone'],
            'reference' => 'Direktna prodaja '.$orderNumber,
            'note' => 'Artikal je lično dostavljen krajnjem kupcu u okviru direktne prodaje Super Administratora.',
            'confirmed_by' => (int) $actor->id,
        ]);

        DB::table('order_status_history')->insert([
            'order_id' => (int) $order->id,
            'old_status' => null,
            'new_status' => 'shipped',
            'changed_by' => (int) $actor->id,
            'note' => $deferred
                ? 'Direktna prodaja evidentirana i lično dostavljena; naplata se prati kroz plan odloženog plaćanja.'
                : 'Direktna prodaja evidentirana, plaćena i lično dostavljena krajnjem kupcu.',
            'created_at' => $soldAt,
        ]);

        $this->audit->log(
            'direct_sale.recorded',
            'Evidentirana direktna prodaja '.$orderNumber,
            $order,
            after: [
                'status' => 'shipped',
                'payment_state' => $deferred ? 'unpaid' : 'paid',
                'subtotal_rsd' => $lineTotal,
                'completed_at' => $soldAt->toISOString(),
            ],
            metadata: [
                'product_id' => (int) $lockedProduct->id,
                'customer_mode' => 'walk_in',
                'quantity' => $quantity,
                'sale_price_rsd' => $salePrice,
                'payment_method' => $payload['payment_method'],
                'installment_count' => $deferred ? (int) $payload['installment_count'] : null,
                'payment_due_at' => $deferred ? (string) $payload['payment_due_at'] : null,
                'sales_channel' => 'direct_sale',
            ],
            user: $actor,
        );

        return $order;
    }

    /**
     * Reconcile the installment plan after idempotent order creation. A retry may repair a
     * missing plan, but it may never silently replace a different existing plan.
     */
    private function ensureDeferredReceivablePlan(Order $order, User $actor, int $installmentCount, string $paymentDueAt): void
    {
        $fresh = $order->fresh() ?? $order;
        $case = $this->receivables->ensureForOrder($fresh, $actor);
        if ($case === null) {
            throw ValidationException::withMessages(['payment_method' => 'Potraživanje za odloženo plaćanje nije moguće otvoriti.']);
        }

        $case->loadMissing('installments');
        $existing = $case->installments->sortBy('sequence_no')->values();
        if ($existing->isNotEmpty()) {
            $metadata = is_array($case->metadata_json)
                ? $case->metadata_json
                : (is_string($case->metadata_json) ? json_decode($case->metadata_json, true) : []);
            $baseline = is_array($metadata) ? round(max(0, (float) ($metadata['plan_paid_baseline_rsd'] ?? 0)), 2) : 0.0;
            $plannedTotal = round(max(0, (float) $fresh->subtotal_rsd - $baseline), 2);
            $expected = $this->buildDeferredInstallments($plannedTotal, $installmentCount, $paymentDueAt);
            if (!$this->deferredPlanMatches($existing->all(), $expected)) {
                throw ValidationException::withMessages([
                    'installment_count' => 'Za ovu direktnu prodaju već postoji drugačiji plan otplate. Plan nije automatski prepisan.',
                ]);
            }
            $this->receivables->syncForOrder($fresh);
            return;
        }

CMD_RESULT=direct-sale-service :: PASS
CMD=product-image-service
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

final class ProductImageService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly ProductImageDerivativeService $derivatives,
    ) {}

    /** @param list<UploadedFile> $files */
    public function upload(Product $product, array $files): int
    {
        return $this->uploadInternal($product, $files);
    }


    /** @param list<UploadedFile> $files */
    private function uploadInternal(Product $product, array $files): int
    {
        $created = 0;
        foreach ($files as $file) {
            if (!$file instanceof UploadedFile || !$file->isValid()) continue;
            $sourcePath = $file->getRealPath();
            $fileHash = is_string($sourcePath) && is_file($sourcePath) ? (hash_file('sha256', $sourcePath) ?: null) : null;
            $directory = 'products/'.$product->id;
            $path = $file->store($directory, 'public');
            if (!is_string($path)) throw new RuntimeException('Slika nije mogla biti sačuvana.');
            $scope = ProductImage::query()->where('product_id', $product->id);
            $isFirst = !$scope->exists();
            $createdImage = ProductImage::query()->create([
                'product_id' => $product->id,
                'file_path' => $path,
                'storage_disk' => 'public',
                'original_filename' => mb_substr((string) $file->getClientOriginalName(), 0, 255),
                'mime_type' => (string) ($file->getMimeType() ?: $file->getClientMimeType()),
                'file_size' => (int) $file->getSize(),
                'file_hash' => $fileHash,
                'rotation_degrees' => 0,
                'sort_order' => (int) (clone $scope)->max('sort_order') + 10,
                'is_primary' => $isFirst,
                'created_at' => now(),
            ]);
            $this->derivatives->ensureSafe($createdImage);
            $created++;
        }
        if ($created > 0) {
            $this->audit->log('product.images.uploaded', 'Dodate slike artikla '.$product->sku, $product, metadata: ['count' => $created, 'product_id' => $product->id]);
        }
        return $created;
    }

    public function cloneImages(Product $source, Product $target): int
    {
        $source->loadMissing('images');
        $created = 0;
        foreach ($source->images as $image) {
            $path = (string) $image->file_path;
            $disk = (string) $image->storage_disk;
            if ($disk === 'public') {
                if (!Storage::disk('public')->exists($path)) continue;
                $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg';
                $newPath = 'products/'.$target->id.'/'.Str::uuid().'.'.$extension;
                Storage::disk('public')->makeDirectory('products/'.$target->id);
                if (!Storage::disk('public')->copy($path, $newPath)) continue;
                $path = $newPath;
            } elseif ($disk !== 'legacy') {
                continue;
            }

            $createdImage = ProductImage::query()->create([
                'product_id' => $target->id,
                'file_path' => $path,
                'storage_disk' => $disk,
                'original_filename' => $image->original_filename,
                'mime_type' => $image->mime_type,
                'file_size' => $image->file_size,
                'file_hash' => $image->file_hash,
                'rotation_degrees' => $image->rotation_degrees,
                'sort_order' => $image->sort_order,
                'is_primary' => $image->is_primary,
                'created_at' => now(),
            ]);
            $this->derivatives->ensureSafe($createdImage);
            $created++;
        }
        if ($created > 0) $this->audit->log('product.images.cloned', 'Klonirane slike sa artikla '.$source->sku.' na '.$target->sku, $target, metadata: ['source_product_id' => $source->id, 'count' => $created]);
        return $created;
    }


    public function setPrimary(Product $product, ProductImage $image): void
    {
        $this->assertOwner($product, $image);
        DB::transaction(function () use ($product, $image): void {
            $orderedIds = ProductImage::query()
                ->where('product_id', $product->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all();
            $orderedIds = array_values(array_unique(array_merge([(int) $image->id], $orderedIds)));

            ProductImage::query()->where('product_id', $product->id)->update(['is_primary' => false]);
            $image->update(['is_primary' => true]);
            $this->applyOrder($product, $orderedIds);
        });
        $this->audit->log('product.image.primary', 'Promenjena glavna slika artikla '.$product->sku, $product, metadata: ['image_id' => $image->id]);
    }

    public function rotate(Product $product, ProductImage $image, int $degrees): void
    {
        $this->assertOwner($product, $image);
        $degrees = (($degrees % 360) + 360) % 360;
        if (!in_array($degrees, [90, 180, 270], true)) {
            throw new RuntimeException('Rotacija mora biti 90, 180 ili 270 stepeni.');
        }
        $source = $this->sourceAbsolutePath($image);
        $mime = $this->supportedMime($source, (string) $image->mime_type);
        $temporary = $this->temporaryCopy($source);
        $newPublicPath = null;

        try {
            $this->rotatePhysicalFile($temporary, $mime, $degrees);

            if ($image->storage_disk === 'public') {
                $destination = Storage::disk('public')->path((string) $image->file_path);
                if (!@rename($temporary, $destination)) {
                    if (!@copy($temporary, $destination)) {
                        throw new RuntimeException('Rotirana slika nije mogla da zameni postojeći fajl.');
                    }
                    @unlink($temporary);
                }
            } else {
                $extension = $this->extensionForMime($mime);
                $newPublicPath = 'products/'.$product->id.'/'.Str::uuid().'.'.$extension;
                Storage::disk('public')->makeDirectory('products/'.$product->id);
                $destination = Storage::disk('public')->path($newPublicPath);
                if (!@rename($temporary, $destination)) {
                    if (!@copy($temporary, $destination)) {
                        throw new RuntimeException('Lokalna kopija legacy slike nije mogla biti sačuvana.');
                    }
                    @unlink($temporary);
                }
            }

            @chmod($destination, 0644);
            clearstatcache(true, $destination);

            $finalPath = $image->storage_disk === 'public'
                ? Storage::disk('public')->path((string) $image->file_path)
                : Storage::disk('public')->path((string) $newPublicPath);

            $wasLegacy = $image->storage_disk === 'legacy';
            if ($wasLegacy) {
                $image->storage_disk = 'public';
                $image->file_path = (string) $newPublicPath;
                $image->original_filename = 'rotated-'.($image->original_filename ?: 'legacy-image.'.$this->extensionForMime($mime));
            }
            $image->mime_type = $mime;
            $image->file_size = is_file($finalPath) ? (int) filesize($finalPath) : 0;
            $image->file_hash = is_file($finalPath) ? (hash_file('sha256', $finalPath) ?: null) : null;
            $image->rotation_degrees = ((int) $image->rotation_degrees + $degrees) % 360;
            $image->save();
            $this->derivatives->refreshSafe($image);

            $this->audit->log(
                'product.image.rotated',
                'Rotirana slika artikla '.$product->sku,
                $product,
                metadata: [
                    'image_id' => $image->id,
                    'degrees' => $degrees,
                    'legacy_copy_on_write' => $wasLegacy,
                ],
            );
        } catch (\Throwable $exception) {
            if (is_file($temporary)) @unlink($temporary);
            if ($newPublicPath !== null) Storage::disk('public')->delete($newPublicPath);
            throw $exception;
        }
    }

    /** @param list<int> $orderedIds */
    public function reorder(Product $product, array $orderedIds): void
    {
        $existingIds = ProductImage::query()
            ->where('product_id', $product->id)

            ->orderByDesc('is_primary')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
        $existingLookup = array_fill_keys($existingIds, true);
        $orderedIds = array_values(array_unique(array_filter(
            array_map('intval', $orderedIds),
            static fn (int $id): bool => isset($existingLookup[$id]),
        )));

        // Neposlati ID-jevi se dodaju na kraj. Tako parcijalni/malformirani zahtev
        // ne može izgubiti sliku iz rasporeda niti napraviti duple sort vrednosti.
        foreach ($existingIds as $existingId) {
            if (!in_array($existingId, $orderedIds, true)) {
                $orderedIds[] = $existingId;
            }
        }

        $primaryId = ProductImage::query()
            ->where('product_id', $product->id)

            ->where('is_primary', true)
            ->value('id');
        if ($primaryId !== null) {
            $orderedIds = array_values(array_filter($orderedIds, static fn (int $id): bool => $id !== (int) $primaryId));
            array_unshift($orderedIds, (int) $primaryId);
        }

        DB::transaction(fn () => $this->applyOrder($product, $orderedIds));
        $this->audit->log('product.images.reordered', 'Promenjen redosled slika artikla '.$product->sku, $product, metadata: ['image_ids' => $orderedIds]);
    }

    /** @param list<int> $orderedIds */
    private function applyOrder(Product $product, array $orderedIds): void
    {
        $valid = ProductImage::query()
            ->where('product_id', $product->id)

            ->whereIn('id', $orderedIds)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
        foreach ($orderedIds as $index => $id) {
            if (in_array($id, $valid, true)) {
                ProductImage::query()->whereKey($id)->update(['sort_order' => ($index + 1) * 10]);
            }
        }
    }

    public function delete(Product $product, ProductImage $image): void
    {
        $this->assertOwner($product, $image);
        abort_if($image->storage_disk !== 'public', 422, 'Legacy fajl je read-only. Rotiraj ga prvo ako želiš lokalnu kopiju.');
        $wasPrimary = (bool) $image->is_primary;
        $this->derivatives->delete($image);
        Storage::disk('public')->delete($image->file_path);
        $imageId = $image->id;
        $image->delete();
        if ($wasPrimary) {
            $next = ProductImage::query()->where('product_id', $product->id)->orderBy('sort_order')->orderBy('id')->first();
            $next?->update(['is_primary' => true]);
        }
        $this->audit->log('product.image.deleted', 'Uklonjena slika artikla '.$product->sku, $product, metadata: ['image_id' => $imageId]);
    }

    private function assertOwner(Product $product, ProductImage $image): void
    {
        abort_unless((int) $image->product_id === (int) $product->id, 404);
    }

    private function sourceAbsolutePath(ProductImage $image): string
    {
        if ($image->storage_disk === 'public') {
            $path = Storage::disk('public')->path((string) $image->file_path);
            if (!is_file($path)) throw new RuntimeException('Lokalni fajl slike ne postoji.');
            return $path;
        }

        if ($image->storage_disk !== 'legacy') {
            throw new RuntimeException('Storage slike nije podržan za rotaciju.');
        }

        $relative = ltrim(str_replace('\\', '/', trim((string) $image->file_path)), '/');
        if ($relative === '' || str_contains($relative, '..') || !str_starts_with($relative, 'uploads/products/')) {
            throw new RuntimeException('Legacy putanja slike nije bezbedna.');
        }

        $configuredRoot = trim((string) config('services.legacy_media.root'));
        $root = $configuredRoot !== '' ? realpath($configuredRoot) : false;
        if (!is_string($root) || !is_dir($root)) {
            throw new RuntimeException('Legacy media root nije podešen.');
        }

        $allowedRoot = realpath($root.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'products');
        $absolute = realpath($root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative));
        if (!is_string($allowedRoot) || !is_string($absolute) || !is_file($absolute)
            || !str_starts_with($absolute, rtrim($allowedRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('Legacy slika nije pronađena ili nije dozvoljena.');
        }

        return $absolute;
    }

    private function temporaryCopy(string $source): string
    {
        $directory = storage_path('app/tmp/image-rotation');
        if (!is_dir($directory) && !@mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException('Privremeni direktorijum za rotaciju nije dostupan.');
        }
        $temporary = tempnam($directory, 'rotate-');
        if (!is_string($temporary) || !@copy($source, $temporary)) {
            throw new RuntimeException('Privremena kopija slike nije mogla biti napravljena.');
        }
        return $temporary;
    }

    private function supportedMime(string $path, string $declared): string
    {
        $detected = mime_content_type($path) ?: $declared;
        if (!in_array($detected, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new RuntimeException('Format slike nije podržan za rotaciju.');
        }
        return $detected;
    }

    private function extensionForMime(string $mime): string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => throw new RuntimeException('Format slike nije podržan.'),
        };
    }

    private function rotatePhysicalFile(string $path, string $mime, int $degrees): void
    {
        if (class_exists(\Imagick::class)) {
            $this->rotateWithImagick($path, $mime, $degrees);
            return;
        }

        if (!extension_loaded('gd') || !function_exists('imagerotate')) {
            throw new RuntimeException('Rotacija zahteva PHP Imagick ili GD ekstenziju.');
        }

        $createFunction = match ($mime) {
            'image/jpeg' => 'imagecreatefromjpeg',
            'image/png' => 'imagecreatefrompng',
            'image/webp' => 'imagecreatefromwebp',
            default => null,
        };
        $saveFunction = match ($mime) {
            'image/jpeg' => 'imagejpeg',
            'image/png' => 'imagepng',
            'image/webp' => 'imagewebp',
            default => null,
CMD_RESULT=product-image-service :: PASS

============================================================
4. STATIC / ROUTE CHECKS
============================================================
CMD=cms-static-check
PASS  postoji artisan
PASS  postoji composer.json
PASS  postoji composer.lock
PASS  postoji .env.example
PASS  postoji VERSION
PASS  postoji RELEASE-TAG
PASS  postoji UPGRADE-FROM
PASS  postoji docs/UPGRADE-V2.1-BETA1.md
PASS  postoji docs/UPGRADE-V2.1-BETA1.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA1.2.md
PASS  postoji docs/UPGRADE-V2.1-BETA1.3.md
PASS  postoji docs/UPGRADE-V2.1-BETA2.md
PASS  postoji docs/UPGRADE-V2.1-BETA3.md
PASS  postoji docs/UPGRADE-V2.1-BETA3.2.md
PASS  postoji docs/UPGRADE-V2.1-BETA3.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA4.md
PASS  postoji docs/UPGRADE-V2.1-BETA5.md
PASS  postoji docs/UPGRADE-V2.1-BETA6.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.2.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.3.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.4.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.5.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.6.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.7.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.8.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.9.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.10.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.11.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.12.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.13.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.14.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.14.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.15.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.16.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.17.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.17.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.17.2.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.18.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.18.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.19.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.20.md
PASS  postoji DATABASE-MIGRATION-REQUIRED.txt
PASS  postoji app/Models/Order.php
PASS  postoji app/Models/OrderItem.php
PASS  postoji app/Models/OrderDocument.php
PASS  postoji app/Models/OrderCommission.php
PASS  postoji app/Models/CommissionStatusHistory.php
PASS  postoji app/Models/CommissionPaymentBatch.php
PASS  postoji app/Models/OrderInternalNote.php
PASS  postoji app/Models/OrderAssignment.php
PASS  postoji app/Models/OrderStatusHistory.php
PASS  postoji app/Models/StockMovement.php
PASS  postoji app/Models/IdempotencyKey.php
PASS  postoji app/Models/OrderPayment.php
PASS  postoji app/Models/OrderDelivery.php
PASS  postoji app/Models/AfterSalesCase.php
PASS  postoji app/Models/AfterSalesCaseItem.php
PASS  postoji app/Models/AfterSalesMessage.php
PASS  postoji app/Models/AfterSalesAttachment.php
PASS  postoji app/Models/AfterSalesStatusHistory.php
PASS  postoji app/Models/AfterSalesAction.php
PASS  postoji app/Models/AfterSalesActionItem.php
PASS  postoji app/Models/FieldServiceTeam.php
PASS  postoji app/Models/FieldWorkOrder.php
PASS  postoji app/Models/FieldWorkOrderAttachment.php
PASS  postoji app/Models/ServicePartSupplier.php
PASS  postoji app/Models/ServicePart.php
PASS  postoji app/Models/FieldWorkOrderPart.php
PASS  postoji app/Models/ServicePartMovement.php
PASS  postoji app/Models/ServicePartPurchaseRequest.php
PASS  postoji app/Models/ServicePartPurchaseRequestItem.php
PASS  postoji app/Models/WarrantyRule.php
PASS  postoji app/Models/ProductWarranty.php
PASS  postoji app/Models/WarrantyMaintenanceRecord.php
PASS  postoji app/Models/OrderEmailOutbox.php
PASS  postoji app/Models/StockReceipt.php
PASS  postoji app/Models/StockReceiptItem.php
PASS  postoji app/Models/InventoryCount.php
PASS  postoji app/Models/InventoryCountItem.php
PASS  postoji app/Models/AutomationRun.php
PASS  postoji app/Models/OperationalAlert.php
PASS  postoji app/Models/NotificationPreference.php
PASS  postoji app/Models/BackupRun.php
PASS  postoji app/Models/SystemHealthSnapshot.php
PASS  postoji app/Models/SystemRuntimeState.php
PASS  postoji app/Models/SecurityEvent.php
PASS  postoji app/Services/OrderService.php
PASS  postoji app/Services/OrderWorkflowService.php
PASS  postoji app/Services/InventoryService.php
PASS  postoji app/Services/IdempotencyService.php
PASS  postoji app/Services/OrderPaymentService.php
PASS  postoji app/Services/IpsPaymentPayloadService.php
PASS  postoji app/Services/AdvancedInventoryService.php
PASS  postoji app/Services/LegacyReadOnlyGuard.php
PASS  postoji app/Services/OrderAccessService.php
PASS  postoji app/Services/OrderReportService.php
PASS  postoji app/Services/CommissionReportService.php
PASS  postoji app/Services/CommissionWorkflowService.php
PASS  postoji app/Services/OrderOperationalService.php
PASS  postoji app/Services/OrderTimelineService.php
PASS  postoji app/Services/OperationalNotificationService.php
PASS  postoji app/Notifications/OperationalNotification.php
PASS  postoji app/Services/OperationalAutomationService.php
PASS  postoji app/Services/AutomationReadinessService.php
PASS  postoji app/Services/BackupService.php
PASS  postoji app/Services/SystemHealthService.php
PASS  postoji app/Services/SecurityEventLogger.php
PASS  postoji app/Services/SensitiveDataSanitizer.php
PASS  postoji app/Services/OrderIndexService.php
PASS  postoji app/Services/OrderDetailService.php
PASS  postoji app/Services/OrderDetailPresenter.php
PASS  postoji app/Support/ViewValue.php
PASS  postoji app/Services/OrderDocumentService.php
PASS  postoji app/Services/AfterSalesAccessService.php
PASS  postoji app/Services/AfterSalesCaseService.php
PASS  postoji app/Services/AfterSalesActionService.php
PASS  postoji app/Services/FieldWorkOrderPlanner.php
PASS  postoji app/Services/FieldOperationsService.php
PASS  postoji app/Services/ServicePartsInventoryService.php
PASS  postoji app/Services/WarrantyService.php
PASS  postoji app/Services/OrderEmailOutboxService.php
PASS  postoji app/Services/OrderEmailDispatcher.php
PASS  postoji app/Services/NbsIpsQrService.php
PASS  postoji app/Services/DocumentNumberService.php
PASS  postoji app/Services/Pdf/SimplePdfWriter.php
PASS  postoji app/Services/Pdf/BusinessDocumentPdfService.php
PASS  postoji app/Services/Pdf/WarrantyCertificatePdfService.php
PASS  postoji app/Http/Requests/StoreOrderRequest.php
PASS  postoji app/Http/Requests/AdjustStockRequest.php
PASS  postoji app/Http/Requests/StoreAfterSalesCaseRequest.php
PASS  postoji app/Http/Requests/StoreAfterSalesMessageRequest.php
PASS  postoji app/Http/Requests/UpdateAfterSalesCaseRequest.php
PASS  postoji app/Http/Requests/StoreAfterSalesActionRequest.php
PASS  postoji app/Http/Requests/CompleteAfterSalesActionRequest.php
PASS  postoji app/Http/Requests/CancelAfterSalesActionRequest.php
PASS  postoji app/Http/Requests/StoreFieldServiceTeamRequest.php
PASS  postoji app/Http/Requests/UpdateFieldServiceTeamRequest.php
PASS  postoji app/Http/Requests/ScheduleFieldWorkOrderRequest.php
PASS  postoji app/Http/Requests/CompleteFieldWorkOrderRequest.php
PASS  postoji app/Http/Requests/CancelFieldWorkOrderRequest.php
PASS  postoji app/Http/Requests/StoreServicePartRequest.php
PASS  postoji app/Http/Requests/UpdateServicePartRequest.php
PASS  postoji app/Http/Requests/AdjustServicePartStockRequest.php
PASS  postoji app/Http/Requests/StoreServicePartSupplierRequest.php
PASS  postoji app/Http/Requests/UpdateServicePartSupplierRequest.php
PASS  postoji app/Http/Requests/StoreFieldWorkOrderPartRequest.php
PASS  postoji app/Http/Requests/StoreServicePartPurchaseRequest.php
PASS  postoji app/Http/Requests/CancelServicePartPurchaseRequest.php
PASS  postoji app/Http/Requests/StoreWarrantyRuleRequest.php
PASS  postoji app/Http/Requests/UpdateProductWarrantyRequest.php
PASS  postoji app/Http/Requests/ScheduleWarrantyMaintenanceRequest.php
PASS  postoji app/Http/Requests/CompleteWarrantyMaintenanceRequest.php
PASS  postoji app/Http/Controllers/OrderController.php
PASS  postoji app/Http/Controllers/WarrantyController.php
PASS  postoji app/Http/Controllers/AfterSalesController.php
PASS  postoji app/Http/Controllers/AfterSalesAttachmentController.php
PASS  postoji app/Http/Controllers/FieldWorkOrderAttachmentController.php
PASS  postoji app/Http/Controllers/CommissionController.php
PASS  postoji app/Http/Controllers/NotificationController.php
PASS  postoji app/Http/Controllers/Api/V1/OrderController.php
PASS  postoji app/Http/Controllers/Admin/OrderController.php
PASS  postoji app/Http/Controllers/Admin/AfterSalesController.php
PASS  postoji app/Http/Controllers/Admin/AfterSalesActionController.php
PASS  postoji app/Http/Controllers/Admin/FieldOperationsController.php
PASS  postoji app/Http/Controllers/Admin/FieldServiceTeamController.php
PASS  postoji app/Http/Controllers/Admin/ServicePartController.php
PASS  postoji app/Http/Controllers/Admin/ServicePartSupplierController.php
PASS  postoji app/Http/Controllers/Admin/FieldWorkOrderPartController.php
PASS  postoji app/Http/Controllers/Admin/ServicePartPurchaseRequestController.php
PASS  postoji app/Http/Controllers/Admin/WarrantyController.php
PASS  postoji app/Http/Controllers/Admin/CommissionController.php
PASS  postoji app/Http/Controllers/Admin/StockAdjustmentController.php
PASS  postoji app/Http/Controllers/OrderDocumentController.php
PASS  postoji app/Http/Controllers/Admin/OrderDocumentController.php
PASS  postoji app/Http/Controllers/Admin/ReportController.php
PASS  postoji app/Http/Controllers/Admin/DocumentSettingsController.php
PASS  postoji app/Http/Controllers/OrderPaymentController.php
PASS  postoji app/Http/Controllers/OrderDeliveryController.php
PASS  postoji app/Http/Controllers/Admin/PaymentController.php
PASS  postoji app/Http/Controllers/Admin/InventoryController.php
PASS  postoji app/Http/Controllers/Admin/AutomationController.php
PASS  postoji app/Http/Controllers/Admin/SystemHealthController.php
PASS  postoji app/Http/Controllers/Admin/TurnstileSettingsController.php
PASS  postoji app/Http/Controllers/Admin/OrderEmailSettingsController.php
PASS  postoji app/Http/Resources/OrderResource.php
PASS  postoji app/Console/Commands/OrdersDoctorCommand.php
PASS  postoji app/Console/Commands/OrderCreateDoctorCommand.php
PASS  postoji app/Console/Commands/CatalogOwnershipDoctorCommand.php
PASS  postoji app/Console/Commands/DetailPagesDoctorCommand.php
PASS  postoji app/Console/Commands/ReportsDoctorCommand.php
PASS  postoji app/Console/Commands/OperationsDoctorCommand.php
PASS  postoji app/Console/Commands/PaymentsInventoryDoctorCommand.php
PASS  postoji app/Console/Commands/RunOperationalAutomationCommand.php
PASS  postoji app/Console/Commands/AutomationDoctorCommand.php
PASS  postoji app/Console/Commands/CreateBackupCommand.php
PASS  postoji app/Console/Commands/BackupDoctorCommand.php
PASS  postoji app/Console/Commands/SystemHealthCommand.php
PASS  postoji app/Console/Commands/SchedulerHeartbeatCommand.php
PASS  postoji app/Console/Commands/TestDatabaseDoctorCommand.php
PASS  postoji app/Console/Commands/AfterSalesDoctorCommand.php
PASS  postoji app/Console/Commands/FieldOperationsDoctorCommand.php
PASS  postoji app/Console/Commands/ServicePartsDoctorCommand.php
PASS  postoji app/Console/Commands/WarrantiesDoctorCommand.php
PASS  postoji app/Console/Commands/WarrantiesBackfillCommand.php
PASS  postoji app/Console/Commands/OrderEmailDispatchCommand.php
PASS  postoji app/Console/Commands/OrderEmailsDoctorCommand.php
PASS  postoji database/migrations/2026_07_22_000006_enable_production_orders_inventory.php
PASS  postoji database/migrations/2026_07_23_000007_repair_production_schema_beta5.php
PASS  postoji database/migrations/2026_07_23_000008_repair_authenticated_runtime_beta6.php
PASS  postoji database/migrations/2026_07_23_000009_create_reports_documents_and_supplier_assignment.php
PASS  postoji database/migrations/2026_07_23_000010_repair_reports_schema_beta1_2.php
PASS  postoji database/migrations/2026_07_23_000011_create_operational_orders_commissions_beta2.php
PASS  postoji database/migrations/2026_07_23_000012_create_payments_advanced_inventory_beta3.php
PASS  postoji database/migrations/2026_07_23_000013_create_automation_alerts_beta4.php
PASS  postoji database/migrations/2026_07_23_000014_create_security_backup_health_beta6.php
PASS  postoji database/migrations/2026_07_29_000015_repair_order_documents_and_payments_beta7_5.php
PASS  postoji database/migrations/2026_07_30_000016_add_order_completion_beta7_7.php
PASS  postoji database/migrations/2026_07_30_000017_add_delivery_workflow_beta7_8.php
PASS  postoji database/migrations/2026_07_30_000018_fix_delivery_note_document_type_beta7_9.php
PASS  postoji database/migrations/2026_07_30_000019_create_after_sales_cases_beta7_10.php
PASS  postoji database/migrations/2026_07_30_000020_create_after_sales_actions_beta7_11.php
PASS  postoji database/migrations/2026_07_30_000021_create_field_operations_beta7_12.php
PASS  postoji database/migrations/2026_07_30_000022_create_service_parts_procurement_beta7_13.php
PASS  postoji database/migrations/2026_07_30_000023_enable_document_revisions_beta7_14.php
PASS  postoji database/migrations/2026_07_30_000024_create_warranties_preventive_maintenance_beta7_15.php
PASS  postoji database/migrations/2026_07_30_000025_create_order_email_outbox_beta7_16.php
PASS  postoji resources/views/orders/index.blade.php
PASS  postoji resources/views/admin/orders/show.blade.php
PASS  postoji resources/views/commissions/index.blade.php
PASS  postoji resources/views/notifications/index.blade.php
PASS  postoji resources/views/admin/commissions/index.blade.php
PASS  postoji resources/views/orders/create.blade.php
PASS  postoji resources/views/orders/show.blade.php
PASS  postoji resources/views/admin/reports/index.blade.php
PASS  postoji resources/views/admin/settings/documents.blade.php
PASS  postoji resources/views/admin/inventory/index.blade.php
PASS  postoji resources/views/admin/orders/partials/payments.blade.php
PASS  postoji resources/views/orders/partials/payments.blade.php
PASS  postoji resources/views/after-sales/index.blade.php
PASS  postoji resources/views/after-sales/create.blade.php
PASS  postoji resources/views/after-sales/show.blade.php
PASS  postoji resources/views/admin/after-sales/index.blade.php
PASS  postoji resources/views/admin/after-sales/show.blade.php
PASS  postoji resources/views/admin/field-operations/index.blade.php
PASS  postoji resources/views/admin/field-operations/show.blade.php
PASS  postoji resources/views/admin/field-operations/teams.blade.php
PASS  postoji resources/views/admin/service-parts/index.blade.php
PASS  postoji resources/views/admin/service-parts/suppliers.blade.php
PASS  postoji resources/views/admin/service-parts/purchase-requests.blade.php
PASS  postoji resources/views/admin/service-parts/purchase-show.blade.php
PASS  postoji resources/views/admin/settings/automation.blade.php
PASS  postoji resources/views/admin/settings/system-health.blade.php
PASS  postoji resources/views/admin/settings/turnstile.blade.php
PASS  postoji resources/views/admin/settings/order-emails.blade.php
PASS  postoji resources/views/emails/order-events.blade.php
PASS  postoji tests/Feature/AdminOrdersImageRotationTest.php
PASS  postoji tests/Feature/OperationalOrdersCommissionsTest.php
PASS  postoji tests/Feature/OperationalAutomationTest.php
PASS  postoji tests/Feature/PaymentsAdvancedInventoryTest.php
PASS  postoji tests/Feature/OrderDeliveryWorkflowTest.php
PASS  postoji tests/Feature/AfterSalesWorkflowTest.php
PASS  postoji tests/Feature/AfterSalesActionExecutionTest.php
PASS  postoji tests/Feature/FieldOperationsWorkflowTest.php
PASS  postoji tests/Feature/ServicePartsWorkflowTest.php
PASS  postoji tests/Feature/InventoryWorkspaceUiTest.php
PASS  postoji tests/Feature/SecurityHealthBackupTest.php
PASS  postoji tests/Feature/MySqlTestDatabaseSafetyTest.php
PASS  postoji tests/Unit/SensitiveDataSanitizerTest.php
PASS  postoji tests/Feature/ProductionOrderInventoryTest.php
PASS  postoji tests/Feature/InventoryAdjustmentTest.php
PASS  postoji tests/Feature/ProductionPermissionsTest.php
PASS  postoji tests/Feature/DashboardLegacyDesignTest.php
PASS  postoji tests/Feature/ReportsDocumentsSupplierTest.php
PASS  postoji tests/Fixtures/pdf-logo.jpg
PASS  postoji tests/Unit/BusinessDocumentPdfServiceTest.php
PASS  postoji tests/Unit/DeliveryNoteMigrationContractTest.php
PASS  postoji tests/Unit/DocumentRevisionMigrationContractTest.php
PASS  postoji tests/Unit/CommissionReportPdfServiceTest.php
PASS  postoji tests/Feature/OrderEmailsIpsWarrantyTest.php
PASS  postoji tests/Unit/OrderEmailIpsMigrationContractTest.php
PASS  postoji tests/Unit/ReceivablesPermissionMigrationContractTest.php
PASS  postoji tests/Unit/LegacyReadOnlyGuardTest.php
PASS  postoji tests/Unit/OrderDetailPresenterTest.php
PASS  postoji tests/Unit/ViewValueTest.php
PASS  postoji tests/Feature/CatalogDetailPageTest.php
PASS  postoji tests/Feature/LoginDashboardFallbackTest.php
PASS  postoji resources/views/components/icon.blade.php
PASS  postoji app/Http/Middleware/EnsureRuntimeDirectories.php
PASS  postoji app/Http/Middleware/AttachRequestId.php
PASS  postoji app/Http/Middleware/SecurityHeaders.php
PASS  postoji app/Console/Commands/AuthDoctorCommand.php
PASS  postoji .env.testing.mysql.example
PASS  postoji phpunit.mysql.xml
PASS  postoji bin/php-lint.php
PASS  postoji bin/autoload-check.php
PASS  postoji bin/pdf-smoke.php
PASS  postoji bin/delivery-note-smoke.php
PASS  postoji bin/warranty-pdf-smoke.php
PASS  postoji bin/ips-qr-pdf-smoke.php
PASS  postoji storage/framework/cache/data/.gitignore
PASS  postoji storage/framework/sessions/.gitignore
PASS  postoji storage/framework/views/.gitignore
PASS  postoji storage/logs/.gitignore
PASS  postoji storage/app/backups/.gitignore
PASS  postoji config/backup.php
PASS  postoji docs/UPGRADE-V2.1-BETA7.23.md
PASS  postoji docs/RELEASE-CHECK.md
PASS  postoji app/Console/Commands/ReleaseCheckCommand.php
PASS  postoji config/release.php
PASS  postoji bin/release-check-smoke.php
PASS  postoji tests/Unit/ReleaseCheckContractTest.php
PASS  postoji tests/Feature/ReleaseCheckCommandTest.php
PASS  postoji storage/app/release-check/.gitignore
PASS  postoji docs/UPGRADE-V2.1-BETA7.22.1.md
PASS  postoji bin/theme-css-smoke.php
PASS  postoji docs/UPGRADE-V2.1-BETA7.21.md
PASS  postoji database/migrations/2026_07_31_000030_create_management_reports_beta7_21.php
PASS  postoji app/Models/ReportSchedule.php
PASS  postoji app/Models/ReportDelivery.php
PASS  postoji app/Services/ManagementReportService.php
PASS  postoji app/Services/ReportScheduleService.php
PASS  postoji app/Services/Pdf/ManagementReportPdfService.php
PASS  postoji app/Http/Controllers/Admin/ManagementReportController.php
PASS  postoji app/Http/Controllers/Admin/ReportScheduleController.php
PASS  postoji app/Console/Commands/ManagementReportsDoctorCommand.php
PASS  postoji app/Console/Commands/OrderCostSnapshotsCommand.php
PASS  postoji app/Services/OrderItemCostSnapshotService.php
PASS  postoji app/Console/Commands/ManagementReportsDispatchCommand.php
PASS  postoji resources/views/admin/reports/management.blade.php
PASS  postoji resources/views/emails/management-report.blade.php
PASS  postoji tests/Feature/ManagementReportsProfitabilityTest.php
PASS  postoji tests/Unit/OrderCostSnapshotRepairContractTest.php
PASS  postoji bin/management-report-smoke.php
PASS  postoji bin/order-cost-snapshot-smoke.php
PASS  postoji docs/UPGRADE-V2.1-BETA7.19.md
PASS  postoji database/migrations/2026_07_31_000028_create_smart_product_management_beta7_19.php
PASS  postoji app/Services/ProductTemplateService.php
PASS  postoji app/Services/ProductCompletenessService.php
PASS  postoji app/Services/ProductBulkService.php
PASS  postoji app/Http/Controllers/Admin/ProductBulkController.php
PASS  postoji app/Console/Commands/SmartProductsDoctorCommand.php
PASS  postoji resources/views/admin/products/clone.blade.php
PASS  postoji resources/views/admin/products/bulk.blade.php
PASS  postoji tests/Unit/SmartProductManagementMigrationContractTest.php
PASS  postoji tests/Unit/SmartProductManagementUiContractTest.php
PASS  postoji tests/Feature/SmartProductManagementTest.php
PASS  postoji bin/smart-product-smoke.php
PASS  postoji docs/UPGRADE-V2.1-BETA7.18.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.19.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.23.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.23.2.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.24.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.24.1.md
PASS  postoji docs/UPGRADE-V2.1-RC1.md
PASS  postoji docs/RC-OPERATIONS.md
PASS  postoji docs/UPGRADE-V2.1-STABLE.md
PASS  postoji docs/UPGRADE-V2.1.1.md
PASS  postoji docs/UPGRADE-V2.1.2.md
PASS  postoji docs/UPGRADE-V2.1.3.md
PASS  postoji docs/UPGRADE-V2.1.3.1.md
PASS  postoji docs/UPGRADE-V2.1.3.2.md
PASS  postoji docs/UPGRADE-V2.1.3.3.md
PASS  postoji docs/STABLE-OPERATIONS.md
PASS  postoji docs/BACKUP-RESTORE-DRILL.md
PASS  postoji bin/rc-hardening-smoke.php
PASS  postoji bin/stable-hardening-smoke.php
PASS  postoji bin/stable-maintenance-smoke.php
PASS  postoji bin/product-media-ux-smoke.php
PASS  postoji bin/product-announcement-smoke.php
PASS  postoji bin/catalog-settings-product-data-smoke.php
PASS  postoji bin/catalog-settings-integrity-hotfix-smoke.php
PASS  postoji bin/product-save-regex-hotfix-smoke.php
PASS  postoji bin/storage-capacity-total-smoke.php
PASS  postoji tests/Unit/ProductSaveRegexHotfixContractTest.php
PASS  postoji tests/Unit/StorageCapacityTotalContractTest.php
PASS  postoji tests/Unit/CatalogSettingsProductDataContractTest.php
PASS  postoji tests/Unit/CatalogSettingsIntegrityHotfixContractTest.php
PASS  postoji app/Console/Commands/CatalogSettingsDoctorCommand.php
PASS  postoji app/Services/ProductTypeCategoryService.php
PASS  postoji app/Services/SpecificationFieldLifecycleService.php
PASS  postoji app/Services/StorageSpecificationService.php
PASS  postoji database/migrations/2026_08_04_000033_create_catalog_type_layout_v2_1_3.php
PASS  postoji database/migrations/2026_08_04_000034_repair_catalog_category_and_spec_integrity_v2_1_3_1.php
PASS  postoji database/migrations/2026_08_04_000035_link_storage_components_and_total_capacity_v2_1_3_3.php
PASS  postoji public/assets/js/dictionary-sort-manager.js
PASS  postoji resources/views/admin/dictionary/product-type.blade.php
PASS  postoji tests/Unit/ProductMediaUxContractTest.php
PASS  postoji tests/Unit/ProductAnnouncementContractTest.php
PASS  postoji app/Services/ProductAnnouncementService.php
PASS  postoji tests/Unit/ReleaseCandidateHardeningContractTest.php
PASS  postoji tests/Unit/StableReleaseContractTest.php
PASS  postoji tests/Unit/StableMaintenanceContractTest.php
PASS  postoji app/Console/Commands/SecurityHardeningDoctorCommand.php
PASS  postoji app/Console/Commands/MigrationsDoctorCommand.php
PASS  postoji app/Console/Commands/AccessControlDoctorCommand.php
PASS  postoji app/Console/Commands/ReleaseIntegrityCommand.php
PASS  postoji app/Console/Commands/BackupVerifyCommand.php
PASS  postoji app/Console/Commands/ProductMediaDoctorCommand.php
PASS  postoji app/Http/Controllers/ProductMediaDownloadController.php
PASS  postoji public/assets/js/product-media-manager.js
PASS  postoji resources/views/admin/products/partials/image-card.blade.php
PASS  postoji resources/views/admin/products/partials/image-upload.blade.php
PASS  postoji bin/catalog-detail-smoke.php
PASS  postoji bin/detail-pages-doctor-smoke.php
PASS  postoji tests/Unit/CatalogDetailBladeContractTest.php
PASS  postoji tests/Unit/SystemHealthRemediationContractTest.php
PASS  postoji tests/Unit/DetailPagesDoctorContractTest.php
PASS  postoji database/migrations/2026_07_31_000027_create_correlated_specifications_beta7_18.php
PASS  postoji app/Models/SpecificationOption.php
PASS  postoji app/Services/SpecificationDependencyService.php
PASS  postoji app/Services/CatalogSpecificationFilterService.php
PASS  postoji app/Console/Commands/CatalogCorrelationsDoctorCommand.php
PASS  postoji resources/views/partials/correlated-specification-filters.blade.php
PASS  postoji resources/views/partials/correlated-specification-filter-script.blade.php
PASS  postoji tests/Unit/CorrelatedSpecificationsMigrationContractTest.php
PASS  postoji tests/Unit/CorrelatedSpecificationUiContractTest.php
PASS  postoji docs/UPGRADE-V2.1.4.md
PASS  postoji bin/cms-v2.1.4-smoke.php
PASS  postoji tests/Unit/CmsV214ContractTest.php
PASS  postoji app/Console/Commands/CmsV214DoctorCommand.php
PASS  postoji app/Services/ProductDeletionService.php
PASS  postoji database/migrations/2026_08_04_000036_add_product_model_and_name_templates_v2_1_4.php
PASS  postoji docs/UPGRADE-V2.1.4.1.md
PASS  postoji bin/product-type-page-render-hotfix-smoke.php
PASS  postoji tests/Unit/ProductTypePageRenderHotfixContractTest.php
PASS  postoji docs/UPGRADE-V2.1.5.md
PASS  postoji bin/cms-v2.1.5-smoke.php
PASS  postoji tests/Unit/CmsV215ContractTest.php
PASS  postoji app/Console/Commands/CmsV215DoctorCommand.php
PASS  postoji public/assets/js/ux-runtime.js
PASS  postoji resources/views/errors/minimal.blade.php
PASS  postoji resources/views/errors/403.blade.php
PASS  postoji resources/views/errors/404.blade.php
PASS  postoji resources/views/errors/419.blade.php
PASS  postoji resources/views/errors/429.blade.php
PASS  postoji resources/views/errors/500.blade.php
PASS  postoji resources/views/errors/503.blade.php
PASS  postoji database/migrations/2026_08_05_000037_place_desktop_power_supply_field_v2_1_5.php
PASS  postoji docs/UPGRADE-V2.2.0.md
PASS  postoji docs/openapi.yaml
PASS  postoji bin/cms-v2.2.0-smoke.php
PASS  postoji tests/Feature/MobileApiFoundationTest.php
PASS  postoji tests/Unit/MobileApiFoundationContractTest.php
PASS  postoji app/Console/Commands/CmsV220DoctorCommand.php
PASS  postoji app/Http/Controllers/Api/V1/BootstrapController.php
PASS  postoji app/Http/Controllers/Api/V1/MobileDeviceController.php
PASS  postoji app/Models/MobileDevice.php
PASS  postoji database/migrations/2026_08_06_000039_create_mobile_devices_v2_2_0.php
PASS  postoji database/migrations/2026_08_06_000040_add_push_notification_preference_v2_2_0.php
PASS  postoji database/migrations/2026_08_06_000041_create_database_queue_tables_v2_2_0.php
PASS  verzija je 2.2.0 Mobile API Foundation
PASS  release tag je v2.2.0
PASS  upgrade osnova je v2.1.6
PASS  composer.json validan
PASS  PHP minimum 8.4
PASS  Laravel 13
PASS  Composer lint/autoload/test/release skripte postoje
PASS  runtime verzija je 2.2.0
PASS  migracija sadrži idempotency_keys
PASS  migracija sadrži source_system
PASS  migracija sadrži inventory_state
PASS  migracija sadrži inventory_returned_at
PASS  migracija sadrži event_key
PASS  migracija sadrži orders_user_idempotency_unique
PASS  porudžbina zaključava proizvode
PASS  porudžbina umanjuje lager u transakciji
PASS  povrat lagera ima jedinstveni event key
PASS  idempotency koristi unique zapis i row lock
PASS  legacy porudžbine su blokirane
PASS  legacy SQL guard je registrovan pre izvršavanja
PASS  legacy MySQL sesija je READ ONLY
PASS  Redis je uklonjen iz database konfiguracije
PASS  Redis je uklonjen iz cache konfiguracije
PASS  Redis je uklonjen iz queue konfiguracije
PASS  login rate limiter koristi file store
PASS  dozvola orders.create
PASS  dozvola orders.view_own
PASS  dozvola orders.cancel_own
PASS  dozvola orders.manage
PASS  dozvola stock.view
PASS  dozvola stock.adjust
PASS  dozvola reports.view
PASS  dozvola reports.export
PASS  dozvola invoices.manage
PASS  dozvola invoices.view_own
PASS  web ruta orders.store
PASS  web ruta orders.cancel
PASS  web ruta admin.orders.status
PASS  web ruta admin.orders.payment
PASS  web ruta admin.orders.tracking
PASS  web ruta admin.stock.adjust
PASS  API porudžbine postoje
PASS  porudžbina ima dodeljenog SuperAdmin/Admin dobavljača
PASS  admin scope vidi samo njemu dodeljene porudžbine
PASS  izveštaji podržavaju filtere i CSV/PDF
PASS  poslovni dokumenti koriste nepromenljivi snapshot
PASS  PDF renderer je lokalni, embedded TrueType i ToUnicode
PASS  brojevi dokumenata su transakcioni i jedinstveni
PASS  web rute imaju reports CSV/PDF i dokumente
PASS  reports stranica ima schema fallback umesto 500
PASS  reports render je unutar zaštićenog controller toka
PASS  reports view ima render marker i bezbedne URL-ove
PASS  reports export vraća kontrolisani 503
PASS  reports doctor izvršava repair i stvarne SQL upite
PASS  reports doctor renderuje controller Blade i layout
PASS  reports logging je best-effort
PASS  beta1.2 repair migracija je nedestruktivna
PASS  hamburger dugme postoji
PASS  mobilni meni ima kontrolni JavaScript
PASS  mobilni meni nema horizontalni scroll
PASS  direktne mobilne stavke koriste zajednički levi wrapper
PASS  Početna Provizije i Izveštaji su poravnati ulevo
PASS  CSS ima pouzdan cache busting
PASS  legacy desktop header ima dva reda
PASS  legacy mobilni header zadržava kurs temu nalog i hamburger
PASS  dashboard ima moderni hero KPI prioritete i module
PASS  dashboard CSS ima 4 desktop i 2 mobilne kolone
PASS  admin gridovi su poravnati na vrh
PASS  forme koriste sadržajnu visinu
PASS  deployment check ima bezbedan repair režim
PASS  deployment check razlikuje runtime zaštitu i grant warning
PASS  deployment check proverava i operativne kolone
PASS  dashboard koristi DB fallback umesto 500
PASS  login telemetry je best-effort
PASS  login hvata session i remember-token probleme
PASS  authenticated layout nema direktan SettingsService upit
PASS  authenticated layout koristi bezbedne user helper metode
PASS  dashboard logging ne može da obori fallback
PASS  runtime middleware prethodi session/cache middleware-u
PASS  deployment repair kreira runtime direktorijume i kompajlira Blade
PASS  auth doctor može da renderuje kompletan dashboard
PASS  Turnstile hvata sve transportne/JSON greške
PASS  Turnstile podešavanja imaju DB prioritet i env fallback
PASS  Turnstile secret se čuva šifrovano i ne izlaže kroz all
PASS  Turnstile admin ekran i ruta postoje
PASS  beta6 repair migracija popravlja core login šemu
PASS  static check razdvaja runtime i ZIP režim
PASS  operativna migracija sadrži order_internal_notes
PASS  operativna migracija sadrži order_assignments
PASS  operativna migracija sadrži commission_payment_batches
PASS  operativna migracija sadrži notifications
PASS  operativna migracija sadrži payment_batch_id
PASS  operativna migracija sadrži status_updated_at
PASS  operativna migracija sadrži last_internal_note_at
PASS  beta2 dozvola commissions.view_own
PASS  beta2 dozvola orders.reassign
PASS  beta2 dozvola orders.internal_notes
PASS  beta2 dozvola notifications.view
PASS  provizije imaju odobravanje isplatu storniranje i istoriju
PASS  masovna isplata koristi transakciju row lock i batch
PASS  korisnik vidi samo svoje provizije i podrazumevanih 10 procenata
PASS  interne napomene nisu u javnom timeline-u
PASS  ponovna dodela je ograničena na SuperAdministratora
PASS  preuzimanje i rokovi porudžbine imaju audit i obaveštenja
PASS  database notifikacije su neblokirajuće i mail je opcioni
PASS  operativni doctor proverava šemu SQL i render
PASS  admin provizije imaju filtere CSV PDF i masovnu isplatu
PASS  commission tabela nema unutrašnji vertikalni scroll pri obradi
PASS  obrada provizije koristi veliki viewport modal
PASS  commission modal ima naslov i eksplicitno zatvaranje
PASS  otvaranje commission modala zatvara prethodni
PASS  porudžbina ima timeline interne napomene preuzimanje rokove i reassignment UI
PASS  inbox obaveštenja podržava read i read-all
PASS  operativni feature testovi postoje
PASS  commission modal regresioni feature test postoji
PASS  beta3.1 migracija nema globalni use Throwable
PASS  beta3.1 migracija koristi potpuno kvalifikovani Throwable
PASS  PHP lint odbija warning deprecated i notice izlaz
PASS  beta3 migracija sadrži order_payments
PASS  beta3 migracija sadrži stock_receipts
PASS  beta3 migracija sadrži stock_receipt_items
PASS  beta3 migracija sadrži inventory_counts
PASS  beta3 migracija sadrži inventory_count_items
PASS  beta3 migracija sadrži payment_state
PASS  beta3 migracija sadrži paid_total_rsd
PASS  beta3 migracija sadrži payment_due_at
PASS  beta3 dozvola payments.manage
PASS  beta3 dozvola payments.upload_proof
PASS  beta3 dozvola payments.view_own
PASS  beta3 dozvola inventory.receive
PASS  beta3 dozvola inventory.count
PASS  beta3 dozvola inventory.export
PASS  uplate koriste transakciju row lock audit i saldo
PASS  potvrde uplate su privatne i autorizovane
PASS  IPS podaci koriste snapshot porudžbine
PASS  predračun i račun postavljaju dospeće porudžbine
PASS  ulaz robe i popis koriste idempotency transakciju i row lock
PASS  napredni lager ima readiness fallback umesto 500
PASS  reports beta3 sažeci i izvozi su zaštićeni
PASS  beta3 doctor proverava repair SQL i render
PASS  beta3 feature testovi pokrivaju uplate ulaz i popis
PASS  beta7.5 repair migracija obnavlja PDF i payment šemu
PASS  beta7.5 repair migracija je nedestruktivna
PASS  beta7.5 potvrda koristi site name fallback
PASS  beta7.5 ručno evidentiranje uplate ima regresioni test
PASS  beta7.5 doctor proverava dokument i payment tabele
PASS  beta7.6 PDF dozvoljava lokalno uvezene porudžbine
PASS  beta7.6 uplate dozvoljavaju lokalno uvezene porudžbine
PASS  beta7.6 legacy lager zaštita ostaje aktivna
PASS  beta7.7 migracija dodaje terminalno stanje porudžbine
PASS  beta7.7 PDF podešavanja imaju upload pregled i uklanjanje logotipa
PASS  beta7.7 PDF logo se ugrađuje kao lokalni JPEG
PASS  beta7.7 PDF ne prikazuje subagent email kupca
PASS  beta7.7 kompletiranje COD porudžbine evidentira preostali saldo
PASS  beta7.7 kompletirana porudžbina zaključava dalje izmene
PASS  beta7.7 kompletiranje je jasno dostupno u detalju i listi
PASS  beta7.8 migracija dodaje evidenciju isporuke i reopening stanje
PASS  beta7.8 kompletiranje čuva dokaz isporuke privatno
PASS  beta7.8 otpremnica koristi OTP broj i delivery snapshot
PASS  beta7.8 ponovno otvaranje je superadmin-only i auditovano
PASS  beta7.8 detalj prikazuje strukturiranu evidenciju isporuke
PASS  beta7.8 doctor proverava novu šemu i dozvole
PASS  beta7.8 feature testovi pokrivaju dokaz otpremnicu i reopening
PASS  beta7.8 UI ima delivery workflow responsive stilove
PASS  beta7.9 migracija uklanja legacy ENUM blokadu za delivery_note
PASS  beta7.9 servis radi schema preflight pre izdavanja otpremnice
PASS  beta7.9 pomoćni notification kvar ne obara izdat dokument, a IPS važi samo za finansijske dokumente
PASS  beta7.9 kontroleri vraćaju incident poruku umesto Error 500
PASS  beta7.9 doctor proverava stvarni MySQL tip dokumenta
PASS  beta7.9 ima migration contract i delivery note PDF smoke test
PASS  detail koristi eksplicitan slug upit
PASS  slug upit primenjuje objedinjeni visibility scope
PASS  API detail koristi isti slug upit
PASS  neispravna slika ne obara detail
PASS  detail filtrira slike bez validnog URL-a
PASS  katalog generiše eksplicitan slug link
PASS  detail ima interaktivnu thumbnail galeriju
PASS  detail ima fullscreen lightbox i zoom kontrole
PASS  gallery podržava tastaturu swipe i preload
PASS  gallery radi i sa jednom slikom
PASS  gallery CSS ima fullscreen viewport i responsive mobile
PASS  gallery feature testovi postoje
PASS  beta4 migracija sadrži automation_runs
PASS  beta4 migracija sadrži operational_alerts
PASS  beta4 migracija sadrži notification_preferences
PASS  beta4 nema Redis i koristi scheduler/file lock
PASS  beta4 detektuje nepreuzete porudžbine dospele obaveze i nizak lager
PASS  beta4 upozorenja su deduplikovana i razrešavaju se
PASS  notification preferences upravljaju kanalima i kategorijama
PASS  automation settings UI i ručno pokretanje postoje
PASS  automation doctor proverava repair scheduler i run
PASS  beta4 dozvola automation.manage postoji
PASS  beta4 feature testovi pokrivaju deduplikaciju i preference
PASS  beta5 inventory koristi jednu aktivnu operaciju
PASS  beta5 inventory čuva filter i limit nakon knjiženja
PASS  beta5 inventory nema unutrašnji vertikalni scrollbar
PASS  beta5 inventory responsive tabela koristi data-label kartice
PASS  beta5 feature test pokriva inventory workspace
PASS  beta6 migracija sadrži backup_runs
PASS  beta6 migracija sadrži system_health_snapshots
PASS  beta6 migracija sadrži system_runtime_states
PASS  beta6 migracija sadrži security_events
PASS  beta6 migracija sadrži system.health
PASS  beta6 migracija sadrži backups.manage
PASS  beta6 migracija sadrži audit.export
PASS  beta6 migracija sadrži security.view
PASS  beta6 backup koristi mysqldump bez lozinke u argumentima
PASS  beta6 backup odbija public putanju i pravi SHA-256 manifest
PASS  beta6 system health proverava scheduler backup migracije i legacy
PASS  beta6 security header-i i request ID postoje
PASS  beta6 audit koristi rekurzivnu sanitizaciju i request ID
PASS  beta6 rate limiter-i pokrivaju upload export admin i backup
PASS  beta6 test DB doctor ima višestruku zaštitu
PASS  beta6 system health UI i backup akcije postoje
PASS  beta6 scheduler ima heartbeat backup i health snapshot
PASS  beta6 feature i unit testovi postoje
PASS  beta7.1 orders ima readiness SQL i render zaštitu
PASS  beta7.1 orders doctor proverava isti browser render
PASS  beta7.1 orders recovery ne završava generičkim 500
PASS  beta7.1 edit artikla ima rotaciju ulevo i udesno
PASS  beta7.1 legacy rotacija koristi copy-on-write
PASS  beta7.1 rotacija koristi privremeni fajl i kontrolisani Imagick/GD fallback
PASS  beta7.1 feature testovi postoje
PASS  beta7.2 order detail koristi opcioni schema-aware loader
PASS  beta7.2 admin i user detail imaju protected render
PASS  beta7.2 admin i user detail imaju readiness markere
PASS  beta7.2 timeline i IPS ne mogu oboriti detalj
PASS  beta7.2 orders doctor renderuje oba detalja
PASS  beta7.2 detail-pages doctor proverava ključne detail stranice
PASS  beta7.2 feature testovi pokrivaju detail i opcione tabele
PASS  beta7.3 detail koristi scalar presenter umesto Eloquent objekata u Blade-u
PASS  beta7.3 presenter bezbedno obrađuje raw i zero datume
PASS  beta7.3 presenter bezbedno generiše named rute
PASS  beta7.3 detail view nema direktne auth, relation ili datetime pozive
PASS  beta7.3 admin i user detail imaju ne-503 read-only fallback
PASS  beta7.3 orders doctor prikazuje tačan exception uzrok za oba detaila
PASS  beta7.3 orders doctor nastavlja admin i user audit
PASS  beta7.3 payment i inventory Gates su definisani
PASS  beta7.3 presenter i ViewValue regresioni testovi postoje
PASS  beta7.10 migracija sadrži after_sales_cases
PASS  beta7.10 migracija sadrži after_sales_case_items
PASS  beta7.10 migracija sadrži after_sales_messages
PASS  beta7.10 migracija sadrži after_sales_attachments
PASS  beta7.10 migracija sadrži after_sales_status_history
PASS  beta7.10 ima tri postprodajne dozvole
PASS  beta7.10 pristup poštuje vlasnika dodeljenog admina i superadmin scope
PASS  beta7.10 slučaj zahteva isporučenu ili kompletiranu porudžbinu
PASS  beta7.10 čuva pogođene stavke snapshot i SLA rok
PASS  beta7.10 privatni prilozi proveravaju MIME veličinu i autorizaciju
PASS  beta7.10 javne i interne poruke su odvojene
PASS  beta7.10 statusni tok zahteva obrazloženje konačne odluke
PASS  beta7.10 automatizacija upozorava na probijene rokove slučaja
PASS  beta7.10 UI ima korisnički i administratorski postprodajni tok
PASS  beta7.10 doctor proverava šemu dozvole i SQL
PASS  beta7.10 feature test pokriva privatni prilog i obradu
PASS  beta7.10 privatni download zabranjuje browser cache
PASS  beta7.10 konkurentno zatvaranje ne propušta novu poruku
PASS  beta7.10 reopening zahteva razlog i čuva vreme prethodnog rešenja
PASS  beta7.10 nedodeljeni slučajevi obaveštavaju superadministratore
PASS  beta7.10 dashboard prikazuje aktivne probijene i waiting slučajeve
PASS  beta7.11 migracija sadrži after_sales_actions
PASS  beta7.11 migracija sadrži after_sales_action_items
PASS  beta7.11 migracija sadrži after_sales_action_id
PASS  beta7.11 migracija sadrži after_sales.execute
PASS  beta7.11 podržava četiri izvršne radnje
PASS  beta7.11 lager efekti su zaključani i idempotentni
PASS  beta7.11 refundacija je vezana za radnju i ograničena neto uplatom
PASS  beta7.11 slučaj čeka završetak aktivnih radnji
PASS  beta7.11 UI ima planiranje pokretanje izvršenje i otkazivanje
PASS  beta7.11 Gate i permission middleware štite izvršne kontrole
PASS  beta7.11 controller ima sve izvršne endpoint-e
PASS  beta7.11 automatizacija prati rok izvršne radnje
PASS  beta7.11 dashboard prikazuje radnje za izvršenje
PASS  beta7.11 testovi pokrivaju idempotentni lager povrat i refundaciju
PASS  beta7.12 migracija sadrži field_service_teams
PASS  beta7.12 migracija sadrži field_work_orders
PASS  beta7.12 migracija sadrži field_work_order_attachments
PASS  beta7.12 migracija sadrži field_operations.view
PASS  beta7.12 migracija sadrži field_operations.manage
PASS  beta7.12 fizičke radnje automatski dobijaju radni nalog
PASS  beta7.12 sprečava preklapanje termina iste ekipe
PASS  beta7.12 završetak zahteva dolazak na lokaciju
PASS  beta7.12 radni nalog čuva troškove kilometražu i privatne dokaze
PASS  beta7.12 UI ima kalendar ekipe i operativne statuse
PASS  beta7.12 rute i Gate štite terenske operacije
PASS  beta7.12 automatizacija prati neplanirane i probijene radne naloge
PASS  beta7.12 doctor proverava tabele dozvole rute i SQL
PASS  beta7.12 test pokriva auto nalog konflikt i on-site završetak
PASS  beta7.13 migracija sadrži service_part_suppliers
PASS  beta7.13 migracija sadrži service_parts
PASS  beta7.13 migracija sadrži field_work_order_parts
PASS  beta7.13 migracija sadrži service_part_movements
PASS  beta7.13 migracija sadrži service_part_purchase_requests
PASS  beta7.13 migracija sadrži service_part_purchase_request_items
PASS  beta7.13 migracija sadrži service_parts.view
PASS  beta7.13 migracija sadrži service_parts.manage
PASS  beta7.13 migracija sadrži service_parts.procurement
PASS  beta7.13 početno stanje ulazi u movement ledger
PASS  beta7.13 rezervacija ne umanjuje fizičko stanje
PASS  beta7.13 završetak skida stvarni utrošak i oslobađa ostatak
PASS  beta7.13 otkazivanje oslobađa sve rezervacije
PASS  beta7.13 kretanja servisnog lagera su idempotentna i ponovo proverena pod lockom
PASS  beta7.13 nacrt nabavke koristi konkurentno bezbedan privremeni broj
PASS  beta7.13 prijem nabavke računa ponderisanu prosečnu cenu
PASS  beta7.13 UI ima servisni lager dobavljače nabavku i utrošak
PASS  beta7.13 Gate i rute štite lager i nabavku
PASS  beta7.13 automatizacija prati nizak lager i kašnjenje nabavke
PASS  beta7.13 doctor proverava tabele dozvole rute i SQL
PASS  beta7.13 testovi pokrivaju ledger rezervaciju utrošak i ponderisanu cenu
PASS  beta7.13 UI ima responsive stilove servisnog lagera
PASS  beta7.14.1 migracija prvo obezbeđuje FK indeks
PASS  beta7.14 migracija uklanja unique order/type ograničenje
PASS  beta7.14 migracija uvodi revizije i vezu sa prethodnim dokumentom
PASS  beta7.14 servis vraća samo aktivan dokument ili izdaje novu reviziju
PASS  beta7.14 storniranje zahteva razlog i čuva audit podatak
PASS  beta7.14 model podržava supersedes relaciju
PASS  beta7.14 UI razlikuje aktivan dokument i novu reviziju
PASS  beta7.14 PDF prikazuje broj revizije
PASS  beta7.14 regresioni test pokriva ponovno izdavanje
PASS  beta7.15 migracija sadrži warranty_rules
PASS  beta7.15 migracija sadrži product_warranties
PASS  beta7.15 migracija sadrži warranty_maintenance_records
PASS  beta7.15 migracija sadrži warranties.view_own
PASS  beta7.15 migracija sadrži warranties.manage
PASS  beta7.15 pravila imaju product category global prioritet
PASS  beta7.15 kompletiranje automatski izdaje garanciju bez obaranja porudžbine
PASS  beta7.15 otkazivanje poništava aktivne garancije
PASS  beta7.15 GAR poslovni broj je registrovan
PASS  beta7.15 garancija čuva snapshot kupca artikla uslova i serijskih brojeva
PASS  beta7.15 preventivno održavanje generiše sledeći termin
PASS  beta7.15 zakazivanje ne menja vreme tokom provere datuma
PASS  beta7.15 backfill bira samo stavke bez garancije
PASS  beta7.15 PDF garantni list prikazuje ključne snapshot podatke
PASS  beta7.15 korisnički i administratorski prikazi postoje
PASS  beta7.15 rute Gates i administratorski scope štite garancije
PASS  beta7.15 automatizacija prati istek i održavanje
PASS  beta7.15 dashboard prikazuje garancije
PASS  beta7.15 doctor i backfill komande postoje
PASS  beta7.15 feature test pokriva automatsko izdavanje i prioritet pravila
PASS  beta7.15 warranty PDF smoke postoji
PASS  beta7.16 migracija uvodi outbox QR snapshot i dane garancije
PASS  beta7.16 migracija ima recovery putanju za delimičan MariaDB DDL
PASS  beta7.16 e-mail outbox ima dedupe intervale i pojedinačne primaoce
PASS  beta7.16 e-mail prima autor odgovorno lice i dodatne adrese
PASS  beta7.16 workflow šalje status tracking plaćanje i dokumente
PASS  beta7.16 dispatcher ima retry stuck recovery i zaštitu storniranog priloga
PASS  beta7.16 scheduler šalje outbox svake minute
PASS  beta7.16 admin podešava intervale događaje i dokumente
PASS  beta7.16 e-mail šablon ima događaje i bezbedan action link
PASS  beta7.16 NBS payload koristi zvanične oznake i RSD zarez
PASS  beta7.16 NBS servis koristi zvanični HTTPS endpoint i čuva privatni PNG snapshot
PASS  beta7.16 stornirani istorijski dokument ostaje pregledljiv bez ponovnog NBS poziva
PASS  beta7.16 finansijski dokument trazi NBS QR samo za pozitivan neplaceni saldo
PASS  beta7.16 PDF crta PNG bez GD i prikazuje NBS IPS QR oznaku
PASS  beta7.16 IPS QR smoke potvrđuje sliku oznaku i tačan RSD iznos
PASS  beta7.16 garancija podržava kombinaciju meseci i dana
PASS  beta7.16 admin može kreirati porudžbinu
PASS  beta7.16 doctor proverava outbox SMTP scheduler NBS i garancijske dane
PASS  beta7.16 feature test pokriva admin porudžbinu događaje NBS QR i dane garancije
PASS  beta7.17 migracija uvodi predmete rate i komunikaciju naplate
PASS  beta7.17 migracija je recovery-safe za delimičan DDL
PASS  beta7.17 servis automatski otvara zatvara i usklađuje predmete
PASS  beta7.17 rate se raspoređuju prema stvarno plaćenom iznosu
PASS  beta7.17 automatske opomene koriste faze dedupe i outbox
PASS  beta7.17 admin ima aging pregled plan i evidenciju komunikacije
PASS  beta7.17 podmeni se zatvara klikom van escape i izborom stavke
PASS  beta7.17 checkbox i radio imaju normalnu globalnu veličinu
PASS  beta7.17 doctor proverava šemu dozvolu i scheduler
PASS  beta7.17.1 permission seed je schema-aware
PASS  beta7.17.1 seeder ne zahteva permissions.updated_at
PASS  beta7.17.2 hover podmeni ima grace period i click pin
PASS  beta7.17.2 CSS premošćava razmak do podmenija
PASS  beta7.18 migracija uvodi strukturirane opcije i korelacije
PASS  beta7.18 migracija je recovery-safe za MariaDB
PASS  beta7.18 procesor ima porodicu i tačan model
PASS  beta7.18 brend filtrira samo sopstvene linije
PASS  beta7.18 generičke zavisnosti imaju server validaciju i zaštitu ciklusa
PASS  beta7.18 forma skriva nepovezane opcije i čuva detalj
PASS  beta7.18 kataloški filteri podržavaju select range boolean text i detalj
PASS  beta7.18 oba kataloga koriste korelisane filtere
PASS  beta7.18 doctor proverava procesor linije veze i tipove
PASS  beta7.18.1 forma artikla ne koristi nedostupni index filter servis
PASS  beta7.18.1 jedinstveni katalog dobija podatke za korelisane filtere
PASS  beta7.18.1 šifarnici dobijaju podatke za roditelje i mape zavisnosti
PASS  beta7.19 migracija uvodi šablone kompletnost i poreklo klona
PASS  beta7.19 migracija je recovery-safe i obračunava postojeći katalog
PASS  beta7.19 template servis podržava alias placeholdere
PASS  beta7.19 completeness servis vraća nepotpun aktivan artikal u nacrt
PASS  beta7.19 kloniranje čuva novi SKU i nulti lager
PASS  beta7.19 clone checkboxi eksplicitno šalju nulu
PASS  beta7.19 bulk zahteva pregled i blokira praznu operaciju
PASS  beta7.19 bulk promena brenda čisti neusklađenu liniju
PASS  beta7.19 preview naziva uklanja method spoof
PASS  beta7.19 doctor proverava šemu rute i kompletnost
PASS  beta7.19 feature test pokriva naziv klon i bulk
PASS  beta7.20 istorijska migracija ostaje sačuvana kao migration history
PASS  Product Variants forward decommission migracija postoji jednom
PASS  Product Variants decommission migracija ima recovery-safe rollback rekonstrukciju
PASS  Product Variants runtime klase su fizički uklonjene
PASS  Product Variants admin UI fajlovi su fizički uklonjeni
PASS  Porudžbine su product-only bez variant identiteta i snapshotova
PASS  Postprodaja garancija i stock movement su product-only
PASS  Inventory je product-only bez variants_enabled grane
PASS  Kataloški query filter i detalj su product-only
PASS  Product slike i model su product-only
PASS  Clone vise ne nudi niti obrađuje kopiranje varijanti
PASS  Product Variants Feature test sada proverava retired route i uklonjenu šemu
PASS  Product Variants UI contract sada zahteva potpuno uklonjen variant UI
PASS  Product Variants decommission smoke postoji kao završni regresioni guard
PASS  beta7.17 feature test pokriva dedupe rate zatvaranje i UI regresiju
PASS  beta7.21 migracija uvodi nabavne snapshotove i rasporede
PASS  beta7.21 migracija je recovery-safe i permission schema-aware
PASS  beta7.21 marža koristi snapshot i prikazuje pokrivenost troška
PASS  beta7.21 filteri važe za KPI trend i segmente
PASS  beta7.21 dashboard pokriva lager potraživanja postprodaju i tim
PASS  beta7.21 PDF upravljačkog izveštaja postoji
PASS  beta7.21 raspored ima retry dedupe i zasebne primaoce
PASS  beta7.21 ekran je bezbedan pre migracije
PASS  beta7.21 UI ima CSV PDF rasporede i cost coverage
PASS  beta7.22.1 management analytics koristi aktivnu temu bez belog fallback-a
PASS  beta7.22.1 CSS kompatibilni aliasi postoje
PASS  beta7.21 feature test pokriva ekran export i raspored
PASS  beta7.22 portal servis i fallback podaci postoje
PASS  beta7.22 portal objedinjuje porudžbine dokumente uplate garancije i servis
PASS  beta7.22 report grouping je kompatibilan sa ONLY_FULL_GROUP_BY
PASS  beta7.22 dashboard ima trend prioritete brze akcije i operativne module
PASS  beta7.22.1 portal doctor prosleđuje ViewErrorBag
PASS  beta7.22.1 layout bezbedno proverava errors bag
PASS  beta7.23 release-check komanda ima profile i kontrolisane režime
PASS  beta7.23 release registry ima quick standard i full profile
PASS  beta7.23 release plan ne dispatchuje poslovne akcije
PASS  beta7.23 release metadata i atomski JSON report postoje
PASS  beta7.23 release rezultat ima READY i NOT READY ugovor
PASS  beta7.23 smoke i dokumentacija postoje
PASS  beta7.23.1 catalog detail je product-only i nema retired variant Blade markere
PASS  beta7.23.1 catalog detail Blade direktive su izbalansirane
PASS  beta7.23.1 product-only detail Feature i smoke regresija postoje
PASS  beta7.23.1 health daje čitljive runtime remediation komande
PASS  beta7.23.2 detail doctor rešava controller zavisnosti kroz container
PASS  beta7.23.2 detail doctor nema direktan edit poziv sa jednim argumentom
PASS  beta7.23.2 detail doctor smoke i contract regresija postoje
PASS  beta7.24 migracija uvodi aktivacije sesije komunikaciju i order-link audit
PASS  beta7.24 aktivacioni token je hashiran jednokratan i vremenski ograničen
PASS  beta7.24 session registry koristi hash i podržava revoke
PASS  beta7.24 kupac vidi samo javne poruke a admin interne
PASS  beta7.24 portal rute aktivacija i admin centar postoje
PASS  beta7.24 komunikacija razdvaja public i internal
PASS  beta7.24 smoke i PHPUnit regresije postoje
PASS  beta7.24 maintenance čisti tokene i stare session evidencije
PASS  beta7.24 reinvite ne deaktivira aktivnog kupca i aktivacija nije cache-ovana
PASS  beta7.24.1 management repair obrađuje missing snapshotove
PASS  beta7.24.1 repair ne prepisuje kompletne snapshotove
PASS  beta7.24.1 repair je transakcioni i koristi row lock
PASS  beta7.24.1 kandidati imaju transparentan product-only izvor
PASS  beta7.24.1 ručna finansijska promena zahteva razlog i audit
PASS  beta7.24.1 audit/repair komanda i regresije postoje
PASS  rc1 profil sadrzi final hardening provere
PASS  rc1 security doctor proverava production debug HTTPS session i public fajlove
PASS  rc1 migration doctor proverava pending SQL mode i foreign keys
PASS  rc1 access doctor proverava route permission i superadmin
PASS  rc1 release integrity proverava SHA-256 i path traversal
PASS  rc1 backup verify je read-only i proverava SQL gzip i file hash
PASS  rc1 smoke i contract regresije postoje
PASS  rc1 nema novu migration datoteku
PASS  stable profil je identican potvrdenom rc profilu
PASS  stable smoke i contract regresije postoje
PASS  stable početna je univerzalni dashboard sa integrisanim korisničkim centrom
PASS  stable nema zasebnu Moj portal stranicu ni stavku menija
PASS  stable nema novu migration datoteku
PASS  v2.1.2 obaveštenja o novom artiklu su opt-in i koriste outbox
PASS  v2.1.2 novi artikal se šalje aktivnim registrovanim korisnicima bez duplikata
PASS  v2.1.2 mail podešavanja i šablon podržavaju nove artikle
PASS  v2.1.2 product announcement regresije postoje
PASS  v2.1.2 nema novu migration datoteku
PASS  APP_ENV production
PASS  Redis nije obavezan za database queue
PASS  file session/cache/limiter i database queue
PASS  secret vrednosti su prazne
PASS  import ne upisuje legacy konekciju
INFO  ZIP hygiene provere su preskočene na instaliranoj aplikaciji; za raspakovani sanitized ZIP koristi --package.
PASS  v2.1.3 tipovi proizvoda imaju posebne stranice i Drag & Drop
PASS  v2.1.3 specifikaciona polja mogu trajno da se obrišu
PASS  v2.1.3 tip automatski određuje kategoriju
PASS  v2.1.3 diskovi imaju pojedinačne celobrojne GB kapacitete
PASS  v2.1.3 catalog settings doctor postoji
PASS  v2.1.3 grana ima tri kontrolisane migration datoteke
PASS  v2.1.3.3 ProductRequest zadržava validan SKU regex delimiter
PASS  v2.1.3.3 ProductVariantRequest je retired a ProductRequest zadržava validan SKU regex
PASS  v2.1.3.3 migracija povezuje listu diskova i izvedeni ukupni kapacitet
PASS  v2.1.3.3 stari kapacitet se bezbedno prenosi na prvi disk
PASS  v2.1.3.3 backend ne veruje ručnom ukupnom zbiru
PASS  v2.1.3.3 ukupni kapacitet je ispod diskova i readonly
PASS  v2.1.3.3 frontend sabira diskove i čuva početni legacy zbir
PASS  v2.1.3.3 product-only storage model zadržava izvedeni zbir bez variant servisa
PASS  v2.1.3.3 storage smoke i contract test postoje
PASS  v2.1.4 migracija dodaje model proizvoda i usklađuje šablone
PASS  v2.1.4 model se validira čuva i koristi u nazivu
PASS  v2.1.4 forma ima model proizvoda posle linije
PASS  v2.1.4 trajno brisanje ima SKU potvrdu i izbor brisanja slika
PASS  v2.1.4 poslovna istorija blokira destruktivno brisanje
PASS  v2.1.4 semantički sistem tastera pokriva sve uloge
PASS  v2.1.4 route i stable doctor postoje
PASS  v2.1.4 smoke i contract test postoje
PASS  v2.1.4.1 controller priprema i prosledjuje orderedFields
PASS  v2.1.4.1 Blade bezbedno inicijalizuje orderedFields
PASS  v2.1.4.1 doctor renderuje formulare svih tipova
PASS  v2.1.4.1 smoke i contract test postoje
PASS  v2.1.5 globalni UX runtime štiti submit i nesačuvane izmene
PASS  v2.1.5 mobilni action dock koristi originalni submit
PASS  v2.1.5 validacija i accessibility markeri postoje
PASS  v2.1.5 dugi formulari su eksplicitno označeni
PASS  v2.1.5 sistemske error stranice postoje
PASS  v2.1.5 migracija koristi postojeće snaga-napajanja polje
PASS  v2.1.5 migracija postavlja napajanje u sredinu
PASS  v2.1.5 doctor proverava Blade, route akcije i napajanje
PASS  v2.1.5 stable release koristi render i repair
PASS  v2.1.5 smoke i contract test postoje
PASS  v2.1.6 migracija kreira snapshot istoriju i ciljane indekse
PASS  v2.1.6 Data Quality audit pokriva product-only katalog slike i specifikacije
PASS  v2.1.6 repair je nedestruktivan i preračunava izvedene vrednosti
PASS  v2.1.6 performance doctor proverava indekse cache i SQL pragove
PASS  v2.1.6 dashboard kešira schema metadata po requestu
PASS  v2.1.6 Data Quality Center rute i prikaz postoje
PASS  v2.1.6 katalog ima quality filtere
PASS  v2.1.6 doctor renderuje centar i pokreće performance audit
PASS  v2.1.6 Stable release uključuje render repair i strict
PASS  v2.1.6 smoke i contract test postoje
PASS  v2.2.0 bootstrap device catalog order i notification rute postoje
PASS  v2.2.0 bootstrap vraća permissions features i app policy
PASS  v2.2.0 uređaji deduplikuju push tokene i podržavaju opoziv
PASS  v2.2.0 API greške imaju stabilan envelope
PASS  v2.2.0 OpenAPI i Stable doctor su povezani

Ukupno: 983, neuspešno: 0
CMD_RESULT=cms-static-check :: PASS
CMD=php-route-search

[37;41m                                                [39;49m
[37;41m  The "--ansi" option does not accept a value.  [39;49m
[37;41m                                                [39;49m

CMD_RESULT=php-route-search :: FAIL rc=1

============================================================
5. DATABASE AUDIT
============================================================
CMD=php-database-audit

---- product_summary ----
{
    "products_total": 44,
    "products_operational": 43,
    "products_archived": 1,
    "products_active_operational": 37,
    "products_inactive_operational": 6
}

---- recent_products ----
[
    {
        "id": 54,
        "sku": "DELL-000052",
        "name": "Dell Latitude 5591 Intel Core i7 8850H 16GB 512GB",
        "status": "archived",
        "stock_quantity": 0,
        "deleted_at": "2026-09-07 21:47:35",
        "created_at": "2026-09-07 21:00:33",
        "updated_at": "2026-09-07 21:47:35",
        "locally_modified_at": "2026-09-07 21:47:35",
        "created_by": 1,
        "updated_by": 1
    },
    {
        "id": 52,
        "sku": "DELL-000050",
        "name": "Dell OptiPlex 7040 Mini Tower",
        "status": "active",
        "stock_quantity": 6,
        "deleted_at": null,
        "created_at": "2026-09-04 00:36:25",
        "updated_at": "2026-09-07 14:43:32",
        "locally_modified_at": "2026-09-07 14:43:32",
        "created_by": 1,
        "updated_by": 1
    },
    {
        "id": 51,
        "sku": "HP-000049",
        "name": "HP ProDesk 400 G3 i5-6500 RAM 16GB SSD 256GB",
        "status": "active",
        "stock_quantity": 4,
        "deleted_at": null,
        "created_at": "2026-09-04 00:25:06",
        "updated_at": "2026-09-04 00:28:44",
        "locally_modified_at": "2026-09-04 00:28:44",
        "created_by": 1,
        "updated_by": 1
    },
    {
        "id": 50,
        "sku": "SAMSUNG-000048",
        "name": "RAM Memorija SODIMM DDR3 - DDR3L",
        "status": "active",
        "stock_quantity": 28,
        "deleted_at": null,
        "created_at": "2026-09-03 19:44:30",
        "updated_at": "2026-09-05 20:38:36",
        "locally_modified_at": "2026-09-04 00:45:50",
        "created_by": 1,
        "updated_by": 2
    },
    {
        "id": 49,
        "sku": "RACUNARI-000047",
        "name": "Intel Gamer i7-8700K",
        "status": "inactive",
        "stock_quantity": 0,
        "deleted_at": null,
        "created_at": "2026-09-01 00:15:53",
        "updated_at": "2026-09-07 11:00:00",
        "locally_modified_at": "2026-09-07 11:00:00",
        "created_by": 1,
        "updated_by": 1
    },
    {
        "id": 48,
        "sku": "LENOVO-000046",
        "name": "Lenovo ThinkPad E14 Gen2 Intel Core i7 1165G7 16GB 256GB",
        "status": "active",
        "stock_quantity": 1,
        "deleted_at": null,
        "created_at": "2026-08-28 11:38:31",
        "updated_at": "2026-08-28 11:38:31",
        "locally_modified_at": "2026-08-28 11:38:31",
        "created_by": 1,
        "updated_by": 1
    },
    {
        "id": 47,
        "sku": "HP-000045",
        "name": "HP Pavilion 17-CN2XXX Intel Core i5 1235U 16GB 256GB",
        "status": "active",
        "stock_quantity": 1,
        "deleted_at": null,
        "created_at": "2026-08-27 23:01:44",
        "updated_at": "2026-09-03 09:41:10",
        "locally_modified_at": "2026-09-03 09:41:10",
        "created_by": 1,
        "updated_by": 1
    },
    {
        "id": 46,
        "sku": "RACUNARI-000044",
        "name": "Intel Gamer i5-10Gen + 16GB DDR4 + SSD 256GB + HDD 2TB",
        "status": "active",
        "stock_quantity": 1,
        "deleted_at": null,
        "created_at": "2026-08-26 12:52:39",
        "updated_at": "2026-08-26 22:58:15",
        "locally_modified_at": "2026-08-26 22:58:15",
        "created_by": 1,
        "updated_by": 1
    }
]

---- dell_5591_candidates ----
[
    {
        "id": 54,
        "sku": "DELL-000052",
        "name": "Dell Latitude 5591 Intel Core i7 8850H 16GB 512GB",
        "status": "archived",
        "stock_quantity": 0,
        "deleted_at": "2026-09-07 21:47:35",
        "created_at": "2026-09-07 21:00:33",
        "updated_at": "2026-09-07 21:47:35",
        "locally_modified_at": "2026-09-07 21:47:35",
        "created_by": 1,
        "updated_by": 1
    }
]

In Connection.php line 857:
                                                                                                                                                                             
  SQLSTATE[42S02]: Base table or view not found: 1146 Table 'icaffeco_lrvl.audit_events' doesn't exist (Connection: mysql, Host: localhost, Port: 3306, Database: icaffeco_  
  lrvl, SQL: select `id`, `event`, `message`, `auditable_type`, `auditable_id`, `created_at` from `audit_events` where (`auditable_type` = App\Models\Product and `auditabl  
  e_id` = 54) or (`metadata_json` like %"product_id":54%) order by `id` desc limit 12)                                                                                       
                                                                                                                                                                             

In Connection.php line 435:
                                                                                                        
  SQLSTATE[42S02]: Base table or view not found: 1146 Table 'icaffeco_lrvl.audit_events' doesn't exist  
                                                                                                        

CMD_RESULT=php-database-audit :: PASS

============================================================
6. IMAGE STORAGE SAMPLE
============================================================
CMD=storage-image-folders
storage/app/public/products
storage/app/public/products/48
storage/app/public/products/48/LGZ3EqZEiKwXc8ZBUWmrfUzsar0q8XNVvgvXyF08.jpg
storage/app/public/products/48/WJSol3a61Xvl0lySqJLTVyF9frA2xh5hiS3kwfBL.jpg
storage/app/public/products/48/yuGtqX7hRBZK6mqUNbq7fqd9U7v80ORieXWDxg2s.jpg
storage/app/public/products/48/sSWApPxhz526CO1l5xHOJXG4ebUX4HlKs1TlZ2jd.jpg
storage/app/public/products/48/YX9ehyiCSzqRNkwPGJUGEVwj7UBQ05jaK8va0JRZ.png
storage/app/public/products/48/7msGNO5ELq4jTkW9BOGHrQNcQYTJqa0hLhS58AH5.jpg
storage/app/public/products/48/vXUgcLOYWpbOna4xwhlbRvFLoT1aQL3O1cTEcRYM.jpg
storage/app/public/products/48/CVFBgMTKSMA1alfFfqAiMIbKXsg8dVRFiEvnhDbK.jpg
storage/app/public/products/48/9BjwYMA5rHTh9XUV71mJVc3bpTVHpOvZGfC63fhI.jpg
storage/app/public/products/48/OTIDujGVT438Dqb6T9NDkQOKPi7GW88ac3mODylb.jpg
storage/app/public/products/48/icZ6pGPSJ5wZ8HVfei2B0oIUF7V1P20LRoB0dw09.jpg
storage/app/public/products/48/yEeZSTitTXsJO75Imy8dhrfiWOK00PCCZxh1bcmK.jpg
storage/app/public/products/10
storage/app/public/products/10/tgVTPOJOUcNFJjdClGEu8dvyidznwQUO8ze6IpZu.jpg
storage/app/public/products/10/IcUmNU3NN91OxlxrpVOUQ3dR3jhw3wCNoxhDMxqZ.jpg
storage/app/public/products/10/pvlMr4bMeXhRmxxWOjkHbmXZMPa1a1Yr7geKiyPO.jpg
storage/app/public/products/10/nxa1jbYruZUziNaxvmPt8MUuch7sBOBQhofYcciq.jpg
storage/app/public/products/10/M90O3ooNo35KiXItpskiyNbyhP4P7E5QeY9UGHHi.jpg
storage/app/public/products/10/Dgbn42dWzi7a3zTXJYn2miuKTXxHszbsRf2wzj1Z.jpg
storage/app/public/products/10/6qJ5mgKhC3vv69176csURl9KZtgYhYAchK0CgldQ.jpg
storage/app/public/products/38
storage/app/public/products/38/sLzWRGguYrgTLtvhWHsvg1ijm45xx91kVlVQnbPc.jpg
storage/app/public/products/38/aFeswZS5v2ASvjRd0qJnZF80cRfWeiFMrmS3Hxs4.jpg
storage/app/public/products/38/VdvmgTK15lR1uFkNfRzRvMOw8W3KloRIWS6ur2kA.png
storage/app/public/products/38/m6T9GxEA8AyKf0P8mE2VcIWJbI8KjQFALqCQJbZq.jpg
storage/app/public/products/38/XgpB850lsLtYwbFkNPdqvFmZ3FwfDESWJ5n319m9.jpg
storage/app/public/products/31
storage/app/public/products/31/kW83jhxZYk09s0xZ3YtmGpw8mAvk1236DsxrwXEt.png
storage/app/public/products/31/WMyzmoWOzFb0tPZCZ34tafkocGUzp6moZCAkDeyA.jpg
storage/app/public/products/31/LeEGFP3Ge1Car3VVMJFo6e8pnSr9MQv311ipquFm.jpg
storage/app/public/products/44
storage/app/public/products/44/4YhZ9FqNJDtJaBpPDvDDhXWjZOKtRUiaDo1Hr1Zr.jpg
storage/app/public/products/44/z4mG2mw6UWWq2g6dcr46FGSwh2JsoGHscuDUELcB.jpg
storage/app/public/products/44/MbuCRYa6Mg5ycecFckeltSYlVdgtuuc0GhgSMYtp.jpg
storage/app/public/products/44/nhJYnMXwYue69iqM69Hyrg8LfXJb2oizRn4LUxou.jpg
storage/app/public/products/44/QY6TrnN0DikK4wKy9qASrG8drtsBvw21gkXsTf8n.jpg
storage/app/public/products/44/pCWhkjeViu3eXZZQgwQ4YWDF3Uh8gj0driFFs3au.jpg
storage/app/public/products/44/QB8OFkF33p4PutBLw8M5sXudlApUxomLy4ksGuyk.png
storage/app/public/products/44/87rxlVjtBuPFeBiEYcR4DFYAtyme5ZtqUcHvcgAQ.jpg
storage/app/public/products/44/DaWc8e6Aum7T25OnCLJQAzas1BU1JSv9qOhVUs06.jpg
storage/app/public/products/44/IEvmqoDi8ZKKVQDKmaRGYkIoAmzom30EaLBiVvfF.jpg
storage/app/public/products/44/U73pQfQhTVqQaOzep1uXQF2urD7Cm1sHb3M9yNUo.jpg
storage/app/public/products/44/GOKlj0LMMTQUvLlKQOds9zjq0IA5vP24B3fg1s5n.jpg
storage/app/public/products/44/2lte5SIZC1HJyWJBofsMvSnYcLerzdndmJLe8B9C.jpg
storage/app/public/products/26
storage/app/public/products/26/NlTnBhlonObmu6pGDdaJIOEucuIgurZKFPP1Ftot.jpg
storage/app/public/products/26/J3j786VTVNffnUeuuQw0gTunv3RoeO47wWPUjLZJ.jpg
storage/app/public/products/26/XI8sj04pTKVqoypl4NZRycjaeDrVIGUMBDsNqK5B.jpg
storage/app/public/products/26/JIjo1mGytnT8P6A1bLUWhRmVc7NJTrZ4OAjYbs3h.jpg
storage/app/public/products/26/DVZtmJ6Tkt9fntDttwFWtyNAztkwz6ajO7JRYwEQ.jpg
storage/app/public/products/1
storage/app/public/products/1/legacy-8-b6563a2ea4af0d97.jpg
storage/app/public/products/1/legacy-5-54dc31edadadd9d5.jpg
storage/app/public/products/1/legacy-3-e895124e339bb7a5.jpg
storage/app/public/products/1/legacy-2-8caacf03ce94b3e7.jpg
storage/app/public/products/1/legacy-4-adc4a1cc7d57a8fc.jpg
storage/app/public/products/1/legacy-6-11eff2622e8cf8a3.jpg
storage/app/public/products/1/legacy-7-ed64840eed147d31.jpg
storage/app/public/products/1/legacy-1-832df612bfb788d5.jpg
storage/app/public/products/6
storage/app/public/products/6/legacy-49-e0ff312bca477775.jpg
storage/app/public/products/6/legacy-58-8ada56656165ba02.jpg
storage/app/public/products/6/legacy-50-84f09b5f4570400c.jpg
storage/app/public/products/6/legacy-56-642e9224665d5300.jpg
storage/app/public/products/6/legacy-57-c744a0a59e477e0e.jpg
storage/app/public/products/6/legacy-48-1d7fd2c4e53e9ce2.jpg
storage/app/public/products/6/legacy-52-145bc2004940633a.jpg
storage/app/public/products/6/legacy-55-951be2cd324fb459.jpg
storage/app/public/products/6/legacy-46-44058fc5fa791f0c.jpg
storage/app/public/products/6/legacy-51-8c71483d918a7e98.jpg
storage/app/public/products/6/legacy-47-bccc0737f5dd3d55.jpg
storage/app/public/products/6/legacy-54-c87747abe7354420.jpg
storage/app/public/products/6/legacy-45-d4a1fa1315ddd79a.jpg
storage/app/public/products/6/legacy-53-ef179a4cec10577d.jpg
storage/app/public/products/25
storage/app/public/products/25/xKNq5TDml1JjXRI8buPb9X9z1MjaFgmrwui8MG5b.jpg
storage/app/public/products/25/gPHi8G6uDAOtdIjzog6fEUCZRhCFCqqZKeM4yHac.jpg
storage/app/public/products/25/nl2kmGuxL81nNgjuNc8oKHPKSMv7UnHrvIcSSM1d.jpg
storage/app/public/products/25/AWHHJ4Lpv1jiuDI3OJEii348RvoPaVSbo0GI2Gfn.jpg
storage/app/public/products/25/MEXY27mbdpjPGaon5ssfB0erPTDO8grpurzjMfQk.jpg
storage/app/public/products/25/X5JvFRNNNj6n2RT1cq2c3SOr5C5ybMjoPafPHDhv.jpg
storage/app/public/products/25/HZEPKCnvnl6MlqRAbafMUNFfhiETso6MDwFVLox5.jpg
storage/app/public/products/25/st1wpcQM0nQ7PgoA3ypKQKxOjAKoXKGVzXXZx2lA.jpg
storage/app/public/products/18
storage/app/public/products/18/BmMibE6BT49UqOJRqGBbIZwtDVvBlkjo4sqIM8fq.jpg
storage/app/public/products/18/JSjf1Zj3hZudNvQNpX2shaHvyD9XQ7Aqvp7Abofa.jpg
storage/app/public/products/18/zsIsKK5G3i38JdJbZP3SUEgdIfICkrRTjiJyH8gG.jpg
storage/app/public/products/18/yUZy7uNbIeKD5SlST6E2zrJN5cK97YNC4h0dpPLj.jpg
storage/app/public/products/18/iOnc6f6PHMdzbe1TeQ7ssAd3mwV9XOBoJVSnr9XW.jpg
storage/app/public/products/18/711RBDBZFBLx9QWwuMx46UDZTn4iLI7x1Nftjcgi.jpg
storage/app/public/products/24
storage/app/public/products/24/SLtKD55RwRvdfG4Ke3LIR7ZkEILtuGqdDVDsia0e.jpg
storage/app/public/products/24/mdi78CRLsIxrGhTt9TpWoiVpd2qr6zjorCsOSZwB.jpg
storage/app/public/products/24/Mvb5IQjF5seswEoZhmC56Iro1rhKFCDNsoHvji4A.jpg
storage/app/public/products/24/ZGoN4khgI05yub9VBka7FfCg3H1d69GqYVfXImad.jpg
storage/app/public/products/24/4rBLJMGlsUM3E74HeW6JfzgTTghqavAY1zv6tD1S.jpg
storage/app/public/products/24/WymI5LDeDKKHgB4VDYy7OezbZNcqYswp7vVbFeVN.jpg
storage/app/public/products/24/Ew1bJGB45rgFRyu7vmOIfN419wH10TuvLuQxSKce.png
storage/app/public/products/24/NcO1X0swx3kosfQWWnhzqAWzM8Zfe7TQ2du2YRJH.jpg
storage/app/public/products/24/FtOeDFUDxEpuXFMhyaXre8IYuefBOGEmXYMxvAN3.jpg
storage/app/public/products/24/4dyzliKBjNVdQ8QyCMHRACT7YvAY4SzSf1EYaj49.jpg
storage/app/public/products/8
storage/app/public/products/8/mobile-fix-69-3d4bf96b04737bb3.jpg
storage/app/public/products/8/mobile-fix-67-b2e8ccc6e5708493.jpg
storage/app/public/products/8/mobile-fix-70-b53fc235cb5f3970.jpg
storage/app/public/products/8/mobile-fix-68-4a11ff134b3fabac.jpg
storage/app/public/products/7
storage/app/public/products/7/legacy-64-ee548744ceb97e95.jpg
storage/app/public/products/7/legacy-65-7817863ce5245c60.jpg
storage/app/public/products/7/legacy-62-33f664edc47e5837.jpg
storage/app/public/products/7/legacy-61-c7ae262648bf3a41.jpg
storage/app/public/products/7/legacy-66-9425c2e1f68872f5.jpg
storage/app/public/products/7/legacy-63-b71d0cb7928d2c84.jpg
storage/app/public/products/7/661e6197-4b34-41c4-848d-0005d946b13e.png
storage/app/public/products/7/legacy-60-34db9253fb9ce05a.jpg
storage/app/public/products/47
storage/app/public/products/47/7ppnlX1WjSIOKQJN5V5OWbggjdRbPz1eLZ9XYpm7.jpg
storage/app/public/products/47/9q9zesmTGCbJ2Te5INmcASNgdwnEpCKuXYre6yqw.jpg
storage/app/public/products/47/LOa0yFFqej7pZIb6kmGof5CavxCRBpkjz4cGC7VR.jpg
storage/app/public/products/47/GnZcgM5FoAsnHKvvmNkpG8ds66EEc4wgPsPNdI4V.jpg
storage/app/public/products/47/Z83bhEZUeFG58lPe4oVoYzwbxETFwsacRlEB5IOF.jpg
storage/app/public/products/47/nyZWIryhy3yOuEDheDDlchrV6aP6uKv0zXpqz1n4.jpg
storage/app/public/products/47/7R5PzTfuVx5NmbUyR01dUuE2v6I0YuBLdR5P3PPm.jpg
storage/app/public/products/47/k3ip1VFUb0IJSK68vXhCKRP89IqrqGVD0taSbqur.jpg
storage/app/public/products/47/q4F85fvayO2IoqYAycv89LNPiKLPniH70U7zi4NQ.png
storage/app/public/products/47/r3snJoTHGwDi8rZ8h485mHIWUBMfWxkXx8WfQSfz.jpg
storage/app/public/products/5
storage/app/public/products/5/legacy-44-2573d99510456c52.jpg
storage/app/public/products/5/legacy-42-331fd8e8dff6cabb.jpg
storage/app/public/products/5/legacy-43-d7b94eb9d44b06f0.jpg
storage/app/public/products/5/legacy-41-239eb53bc39746a7.jpg
storage/app/public/products/5/legacy-38-bc8872dd4b501d97.jpg
storage/app/public/products/5/legacy-39-3d8e3c4f7e2e64c9.jpg
storage/app/public/products/5/legacy-40-7ec27aceca4e656d.jpg
storage/app/public/products/23
storage/app/public/products/23/99m5ZGe3WRw88jkclhQZobu5bdP1MMEo2HLP4bRo.jpg
storage/app/public/products/23/VOXxxtCDlaXyfRAcA3r5OV1F7wZI8oOUWgXcQqqT.jpg
storage/app/public/products/23/9cdrTfBZCkh5iVb0zkAJWq25bLtM3Mf1c0DOTrjl.jpg
storage/app/public/products/23/Fk2jOIUiMnXI7d0BShrG4DK0dXv2NYMyqsxg5uzn.jpg
storage/app/public/products/23/b5FDmadpoh0GhoaezVL5MtO6rgVr7guSFg9xDW9N.jpg
storage/app/public/products/23/VuoxibIvTWedqnRPZRldEktJ9gHbmE18NKiWe18w.jpg
storage/app/public/products/23/7dbJ6aJUzmiTsolwUBickt34rdmLofhnBz5TnAGq.jpg
storage/app/public/products/23/tiU00aXrMBVCs2HQMmfwoe2a9PbqgLO7FxAhBNIT.jpg
storage/app/public/products/23/UdtSYNyBX8qZkIkZXGyI5viojB8O5khPPxEoYijA.jpg
storage/app/public/products/23/ygpJKeIr7wsEVOLdsRExB1r8yKi8NYiEokm7i08i.jpg
storage/app/public/products/22
storage/app/public/products/22/GPHmXMfRIjA3SfLfqlZSsFP9HgMaQOecDQ7XBWWk.jpg
storage/app/public/products/22/iSnWn0o1nviodSi0VngErXc1fbMa2uximXlTz46Y.jpg
storage/app/public/products/22/oU3Vm0ZDsYB9I7EuEHbKW4ZEt4MEOdqHnOvfRjnb.jpg
storage/app/public/products/22/AvCMTBI7Y9sYjorAZ2M3gjkldp8Q0XFfCaGn2bi1.jpg
storage/app/public/products/22/dhqGMslOddVvoOtH8U4gAnYnxVAHmIFtO10weBTh.jpg
storage/app/public/products/22/lF6XJXVivGQTF3NOpFg0DlMzutADZYfVuidY9rj5.jpg
storage/app/public/products/22/u5LyGMR59NMFQlfAwOMBN7mrOl7EZYhe4WpYSTlm.jpg
storage/app/public/products/22/n2Yw6eMjaEcDTwfqb9iZXr0wwNvTj12OIipBPG93.jpg
storage/app/public/products/22/vSS6t2yBtncuVoulfCccFmKv9OsibIvBVplyZbMK.jpg
storage/app/public/products/22/dLaZmEkN1stl8dBJ4qDyJxhGIM1vpKMDtidH2Iab.png
storage/app/public/products/22/JFYHqKkJVHZstnjVaPOPu1Mu0zR1JslE24I8vvjE.jpg
storage/app/public/products/22/Suf3qr355SgeK4IhJuh6O1NRDVjlvHxEVfx3WTSC.jpg
storage/app/public/products/22/PmWQuICBaE6LmtqPvgKbyWuCw6GddTcJEjEWOeVH.jpg
storage/app/public/products/22/5zr5j72J6H15Lgzh7tAbyEdW3px5uO14dIomniGg.jpg
storage/app/public/products/3
storage/app/public/products/3/legacy-28-4392044366bff16d.jpg
storage/app/public/products/3/legacy-27-9b9577c6ab4847e6.jpg
storage/app/public/products/3/legacy-23-6d9de490b28335ac.jpg
storage/app/public/products/3/legacy-29-024517f8400109fa.jpg
storage/app/public/products/3/legacy-35-003d341a080bdfbc.jpg
storage/app/public/products/3/legacy-22-15543e2c575b4911.jpg
storage/app/public/products/3/legacy-26-2d870a602c7ac5ef.jpg
storage/app/public/products/3/legacy-21-7be82caba0e76feb.jpg
storage/app/public/products/3/legacy-31-ed81e0e59df9cc64.jpg
storage/app/public/products/3/legacy-25-40bff6f97fbb9cea.jpg
storage/app/public/products/3/legacy-34-8c4415eddde76f14.jpg
storage/app/public/products/3/legacy-33-bd29dbcfc3974864.jpg
storage/app/public/products/3/legacy-20-e47987e47a80051e.png
storage/app/public/products/3/legacy-30-5308c95d4b2199e2.jpg
storage/app/public/products/3/legacy-32-981c81ae85225a24.jpg
storage/app/public/products/3/legacy-24-ab3afb7a8ba375be.jpg
storage/app/public/products/16
storage/app/public/products/16/Iw8XYYJ5miQVUBPPNkmmM4icenbiBXsPSnrvAdxC.jpg
storage/app/public/products/16/HyhXAv8UvoUNqXNm6HEcfX5INvzqSvgXB1HKUHhX.jpg
storage/app/public/products/16/tGCvklncdlrCKsU8VzNq1jsrUGvOI4uOqPftehh4.jpg
storage/app/public/products/16/pYLtuMxXdfICZ4RqVdWE4TGQADopar0egrLkA4tB.jpg
storage/app/public/products/16/rOZ86CCqun3ZaM2KSGxOIPMOn9SErRd0lTzEDU39.jpg
storage/app/public/products/16/vvX4XL3mrBWM9SroZNEMbKysr6IkLpoba3KVrBho.jpg
storage/app/public/products/16/WEaRKZTM17cw2CEkxeHSyVI5S9YxDhzw3Rzixgzj.jpg
storage/app/public/products/16/rVkW9920xWtpaF1V5eNZ6dUWgqX7W5BAwLJg4ztG.png
storage/app/public/products/51
storage/app/public/products/51/3eyhN934xgwL3wS9tu8EnxGQQuuPdD7ZRB612Axq.jpg
storage/app/public/products/51/ndHlxD1JZ6TtwebhWm6hhUIlAsH8IQvwIYvHLmG5.jpg
storage/app/public/products/51/JxFoT1c75b4kd9wZ10ryNEY6v17dFjT1nds6C8FF.png
storage/app/public/products/51/XJvXL4hs3C4ayh2zPjANYROx3NA8FPXMZdoOHtKE.jpg
storage/app/public/products/54
storage/app/public/products/54/PIOpisgirgT1eyhuauqouNudYgp1rSf8Wz8Jh2kT.jpg
storage/app/public/products/54/uGDwKvqTjdRlCOmBPTg098XG3kGZuvtqWrsfIV60.jpg
storage/app/public/products/54/2eAF06MWlSqZegiq2Mxj7OK2emGCurJXC9poogoN.jpg
storage/app/public/products/54/sSdjJOp2bRh5Pf41jNrFGDWTw8A28zm2F5dTULe2.jpg
storage/app/public/products/54/ZIOLHtwNLCyvpSmOXMXpkEBNATJS701HvqpuqY8j.jpg
storage/app/public/products/54/EmyUCHYpZAdBsI8JMSViOyf8Q2dHY3f9mz8jbQp3.jpg
CMD_RESULT=storage-image-folders :: PASS

============================================================
7. AUDIT FINISH
============================================================
REPORT_PATH=/home/icaffeco/ald1n-project/docs/operations/354-MOBILE-CATALOG-CHAOS-AUDIT-20260908-203421.md
NEXT_ACTION=Send the full report back before any fix batch is generated.
RESULT=PASS_READ_ONLY_AUDIT_READY
