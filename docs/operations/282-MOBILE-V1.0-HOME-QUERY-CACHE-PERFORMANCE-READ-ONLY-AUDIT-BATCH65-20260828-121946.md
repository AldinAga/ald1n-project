============================================================
282 - MOBILE v1.0.0 HOME + QUERY CACHE PERFORMANCE READ-ONLY AUDIT - BATCH65
============================================================
DATE=Fri Aug 28 12:19:47 CEST 2026
PROJECT=/home/icaffeco/ald1n-project
PURPOSE=MEASURE_HOME_AUTH_CONTEXT_AND_REACT_QUERY_RENDER_NETWORK_BLAST_RADIUS_BEFORE_NEXT_OPTIMIZATION_PATCH
SOURCE_MUTATION=NO
DATABASE_WRITES=0
DATABASE_SCHEMA_CHANGES=0
MIGRATIONS_RUN=NO
EAS_BUILD_COMMANDS_RUN=0
BUILD14_CREATED=NO

============================================================
0. AUTHORITY PREFLIGHT
============================================================
REPORT281_AUTHORITY=PASS_SHA256_596782a36c16dd9a212db856733de40af9dd341619f2ba5a5fedc5eb123cdc9c
SOURCE_AUTHORITY_PRE=PASS_HEAD_REMOTE_TREES_HTACCESS_AND_TARGET_BLOBS
CANONICAL_BUILD13_PRE=PASS_SHA256

============================================================
1. AUTH CONTEXT RENDER BLAST-RADIUS AUDIT
============================================================
USE_AUTH_CONSUMER_FILE_COUNT=90
USE_AUTH_CALL_COUNT=92
BOOTSTRAP_REFERENCE_FILE_COUNT=72
BOOTSTRAP_REFERENCE_COUNT=176
HAS_FEATURE_CALL_COUNT=31
CAN_PERMISSION_CALL_COUNT=120
NOTIFICATION_UNREAD_BOOTSTRAP_FILE_COUNT=5
SET_NOTIFICATION_UNREAD_COUNT_FILE_COUNT=2
REFRESH_BOOTSTRAP_FILE_COUNT=6
USE_AUTH_CONSUMER_FILES_BEGIN
src/app/(app)/(tabs)/_layout.tsx
src/app/(app)/(tabs)/account.tsx
src/app/(app)/(tabs)/catalog.tsx
src/app/(app)/(tabs)/home.tsx
src/app/(app)/(tabs)/notifications.tsx
src/app/(app)/(tabs)/orders.tsx
src/app/(app)/_layout.tsx
src/app/(app)/admin/after-sales/[id].tsx
src/app/(app)/admin/after-sales/index.tsx
src/app/(app)/admin/audit/[id].tsx
src/app/(app)/admin/audit/index.tsx
src/app/(app)/admin/catalog/[id].tsx
src/app/(app)/admin/catalog/[id]/clone.tsx
src/app/(app)/admin/catalog/[id]/direct-sale.tsx
src/app/(app)/admin/catalog/brands/index.tsx
src/app/(app)/admin/catalog/bulk/index.tsx
src/app/(app)/admin/catalog/create.tsx
src/app/(app)/admin/catalog/data-quality/index.tsx
src/app/(app)/admin/catalog/dictionaries/[resource].tsx
src/app/(app)/admin/catalog/dictionaries/index.tsx
src/app/(app)/admin/catalog/dictionaries/product-types/[id].tsx
src/app/(app)/admin/catalog/index.tsx
src/app/(app)/admin/catalog/purchase-costs.tsx
src/app/(app)/admin/commissions/[id].tsx
src/app/(app)/admin/commissions/index.tsx
src/app/(app)/admin/couriers/index.tsx
src/app/(app)/admin/customer-portal/[userId].tsx
src/app/(app)/admin/customer-portal/conversations/[id].tsx
src/app/(app)/admin/customer-portal/index.tsx
src/app/(app)/admin/exchange-rate/index.tsx
src/app/(app)/admin/field-operations/[id].tsx
src/app/(app)/admin/field-operations/index.tsx
src/app/(app)/admin/index.tsx
src/app/(app)/admin/inventory/index.tsx
src/app/(app)/admin/orders/[id].tsx
src/app/(app)/admin/orders/archived.tsx
src/app/(app)/admin/orders/index.tsx
src/app/(app)/admin/receivables/[id].tsx
src/app/(app)/admin/receivables/index.tsx
src/app/(app)/admin/reports/index.tsx
src/app/(app)/admin/search.tsx
src/app/(app)/admin/service-parts/index.tsx
src/app/(app)/admin/service-parts/purchases/[id].tsx
src/app/(app)/admin/service-parts/purchases/index.tsx
src/app/(app)/admin/service-parts/suppliers.tsx
src/app/(app)/admin/settings/appearance.tsx
src/app/(app)/admin/settings/automation.tsx
src/app/(app)/admin/settings/bank-accounts.tsx
src/app/(app)/admin/settings/documents.tsx
src/app/(app)/admin/settings/index.tsx
src/app/(app)/admin/settings/modules/index.tsx
src/app/(app)/admin/settings/order-emails.tsx
src/app/(app)/admin/settings/turnstile.tsx
src/app/(app)/admin/system-health/index.tsx
src/app/(app)/admin/user-groups/index.tsx
src/app/(app)/admin/users/[id].tsx
src/app/(app)/admin/users/create.tsx
src/app/(app)/admin/users/index.tsx
src/app/(app)/admin/warranties/[id].tsx
src/app/(app)/admin/warranties/index.tsx
src/app/(app)/admin/warranties/rules.tsx
src/app/(app)/after-sales/[id].tsx
src/app/(app)/after-sales/create/[orderId].tsx
src/app/(app)/after-sales/index.tsx
src/app/(app)/assigned-orders/[id].tsx
src/app/(app)/assigned-orders/index.tsx
src/app/(app)/cart.tsx
src/app/(app)/checkout.tsx
src/app/(app)/commissions/[id].tsx
src/app/(app)/commissions/index.tsx
src/app/(app)/devices.tsx
src/app/(app)/notification-settings.tsx
src/app/(app)/order/[id].tsx
src/app/(app)/portal/messages/[id].tsx
src/app/(app)/portal/messages/index.tsx
src/app/(app)/product/[slug].tsx
src/app/(app)/sessions.tsx
src/app/(app)/warranties/[id].tsx
src/app/(app)/warranties/index.tsx
src/app/(auth)/_layout.tsx
src/app/(auth)/login.tsx
src/app/_layout.tsx
src/app/index.tsx
src/components/layout/app-bottom-nav.tsx
src/components/layout/page-header.tsx
src/features/auth/auth-provider.tsx
src/features/cart/cart-provider.tsx
src/features/device/device-registrar.tsx
src/features/notifications/push-notification-bridge.tsx
src/features/preferences/money-presentation.ts
USE_AUTH_CONSUMER_FILES_END
AUTH_CONTEXT_VALUE_CONTAINS_BOOTSTRAP=YES
SET_UNREAD_REPLACES_BOOTSTRAP_OBJECT=YES
CAN_CALLBACK_DEPENDS_ON_WHOLE_BOOTSTRAP=YES
HAS_FEATURE_CALLBACK_DEPENDS_ON_WHOLE_BOOTSTRAP=YES
AUTH_UNREAD_GLOBAL_RERENDER_RISK=CONFIRMED_STATIC_HIGH
AUTH_UNREAD_ESTIMATED_CONTEXT_BLAST_RADIUS_FILES=90

============================================================
2. NOTIFICATION / PUSH NETWORK FAN-OUT AUDIT
============================================================
PUSH_RECEIVE_INVALIDATES_NOTIFICATIONS=YES
PUSH_RECEIVE_REFRESHES_BOOTSTRAP=YES
NOTIFICATION_READ_USES_LOCAL_SETQUERYDATA=YES
NOTIFICATION_READ_INVALIDATES_NOTIFICATIONS_QUERY=NO
PUSH_FOREGROUND_NETWORK_FANOUT=BOOTSTRAP_REQUEST_PLUS_ACTIVE_NOTIFICATIONS_REFETCH
PUSH_FOREGROUND_POTENTIAL_REQUEST_COUNT=2_WHEN_NOTIFICATIONS_QUERY_IS_ACTIVE_OTHERWISE_BOOTSTRAP_PLUS_STALE_MARK

============================================================
3. HOME QUERY + RENDER AUDIT
============================================================
HOME_USE_QUERY_COUNT=2
HOME_EXPLICIT_STALE_TIME_60S_COUNT=2
HOME_MANUAL_QUERY_REFETCH_CALL_COUNT=2
HOME_REFRESH_BOOTSTRAP_CALL_COUNT=1
HOME_ACTION_ARRAY_RECREATION_COUNT=3
HOME_DASHBOARD_METRIC_COMPONENT_CALLS=6
HOME_SALES_PULSE_COMPONENT_CALLS=1
HOME_FOCUS_ROW_COMPONENT_CALLS=3
HOME_SCROLL_CONTAINER=SCROLLVIEW_VIA_SHARED_SCREEN
HOME_QUERY_CACHE_POLICY=EXPLICIT_60S_MANUAL_PULL_TO_REFRESH_NO_INTERVAL

HOME_QUERY_LINES_BEGIN
44:  const foundationQuery = useQuery({
45:    queryKey: adminQueryKeys.foundation(),
48:    staleTime: 60_000,
50:  const reportQuery = useQuery({
51:    queryKey: adminQueryKeys.managementReport(HOME_REPORT_PARAMS),
54:    staleTime: 60_000,
86:    void refreshBootstrap();
87:    if (reportsAllowed) void reportQuery.refetch();
88:    if (isSuperAdmin) void foundationQuery.refetch();
HOME_QUERY_LINES_END

============================================================
4. GLOBAL REACT QUERY CACHE / INVALIDATION AUDIT
============================================================
USE_QUERY_CALL_COUNT=85
USE_INFINITE_QUERY_CALL_COUNT=1
USE_MUTATION_CALL_COUNT=92
INVALIDATE_QUERY_CALL_COUNT=77
SET_QUERY_DATA_CALL_COUNT=21
EXPLICIT_STALE_TIME_DECLARATION_COUNT=14
EXPLICIT_GC_TIME_DECLARATION_COUNT=0
REFETCH_INTERVAL_DECLARATION_COUNT=0
REFETCH_ON_MOUNT_DECLARATION_COUNT=0
REFETCH_ON_RECONNECT_DECLARATION_COUNT=0
REFETCH_ON_WINDOW_FOCUS_DECLARATION_COUNT=1
QUERY_CLIENT_DEFAULT_STALE_TIME_45S=YES
QUERY_CLIENT_REFETCH_ON_WINDOW_FOCUS_FALSE=YES
QUERY_CLIENT_QUERY_RETRY_ONE=YES
GLOBAL_QUERY_CACHE_POLICY=HEALTHY_NO_BLIND_GLOBAL_STALE_TIME_CHANGE_RECOMMENDED
INVALIDATION_CALL_SITES_BEGIN
src/features/admin/order-archive-admin.tsx:37:      await client.invalidateQueries({ queryKey: adminQueryKeys.adminOrdersRoot() });
src/features/admin/catalog-advanced-product-actions.tsx:34:      await client.invalidateQueries({ queryKey: adminQueryKeys.catalogAdvancedProduct(product.id) });
src/features/admin/orders-admin-actions.tsx:227:      await client.invalidateQueries({ queryKey: ['admin', 'orders'] });
src/features/catalog/product-image-manager.tsx:233:    await client.invalidateQueries({ queryKey: ['admin', 'catalog', 'product-images', productId] });
src/features/notifications/push-notification-bridge.tsx:64:      void queryClient.invalidateQueries({ queryKey: ['notifications'] });
src/features/notifications/push-notification-bridge.tsx:89:        await queryClient.invalidateQueries({ queryKey: ['devices'] });
src/app/(app)/order/[id].tsx:97:      await client.invalidateQueries({ queryKey: ['orders'] });
src/app/(app)/order/[id].tsx:98:      await client.invalidateQueries({ queryKey: ['order', orderId] });
src/app/(app)/order/[id].tsx:99:      await client.invalidateQueries({ queryKey: ['order-post-create', orderId] });
src/app/(app)/order/[id].tsx:113:      await client.invalidateQueries({ queryKey: ['orders'] });
src/app/(app)/order/[id].tsx:114:      await client.invalidateQueries({ queryKey: ['order', orderId] });
src/app/(app)/order/[id].tsx:115:      await client.invalidateQueries({ queryKey: ['order-post-create', orderId] });
src/app/(app)/notification-settings.tsx:81:        queryClient.invalidateQueries({ queryKey: ['devices'] }),
src/app/(app)/notification-settings.tsx:111:        await queryClient.invalidateQueries({ queryKey: ['devices'] });
src/app/(app)/checkout.tsx:81:      await client.invalidateQueries({ queryKey: ['orders'] });
src/app/(app)/checkout.tsx:82:      await client.invalidateQueries({ queryKey: ['order-options'] });
src/app/(app)/admin/commissions/[id].tsx:71:      await client.invalidateQueries({ queryKey: adminQueryKeys.commissions() });
src/app/(app)/admin/commissions/index.tsx:99:      await client.invalidateQueries({ queryKey: adminQueryKeys.commissions() });
src/app/(app)/admin/receivables/[id].tsx:127:    await client.invalidateQueries({ queryKey: adminQueryKeys.receivables() });
src/app/(app)/admin/receivables/index.tsx:184:      await client.invalidateQueries({ queryKey: adminQueryKeys.receivables() });
src/app/(app)/admin/receivables/index.tsx:195:      await client.invalidateQueries({ queryKey: adminQueryKeys.receivables() });
src/app/(app)/admin/users/[id].tsx:72:      await client.invalidateQueries({ queryKey: adminQueryKeys.users() });
src/app/(app)/admin/users/create.tsx:52:      await client.invalidateQueries({ queryKey: adminQueryKeys.users() });
src/app/(app)/admin/settings/modules/index.tsx:56:        client.invalidateQueries({ queryKey: adminQueryKeys.foundation() }),
src/app/(app)/admin/settings/modules/index.tsx:66:      await client.invalidateQueries({ queryKey: adminQueryKeys.moduleSettings() });
src/app/(app)/admin/exchange-rate/index.tsx:82:      await client.invalidateQueries({ queryKey: adminQueryKeys.exchangeRate() });
src/app/(app)/admin/customer-portal/conversations/[id].tsx:48:      await client.invalidateQueries({ queryKey: adminQueryKeys.customerPortalRoot() });
src/app/(app)/admin/customer-portal/conversations/[id].tsx:64:      await client.invalidateQueries({ queryKey: adminQueryKeys.customerPortalRoot() });
src/app/(app)/admin/customer-portal/[userId].tsx:41:    await client.invalidateQueries({ queryKey: adminQueryKeys.customerPortalUserRoot(userId) });
src/app/(app)/admin/customer-portal/[userId].tsx:42:    await client.invalidateQueries({ queryKey: adminQueryKeys.customerPortalRoot() });
src/app/(app)/admin/customer-portal/index.tsx:51:      await client.invalidateQueries({ queryKey: adminQueryKeys.customerPortalRoot() });
src/app/(app)/admin/orders/archived.tsx:59:    await client.invalidateQueries({ queryKey: adminQueryKeys.adminOrdersRoot() });
src/app/(app)/admin/user-groups/index.tsx:128:      await queryClient.invalidateQueries({ queryKey: adminQueryKeys.userGroupsRoot() });
src/app/(app)/admin/user-groups/index.tsx:147:      await queryClient.invalidateQueries({ queryKey: adminQueryKeys.userGroupsRoot() });
src/app/(app)/admin/reports/index.tsx:489:    await client.invalidateQueries({ queryKey: adminQueryKeys.reportSchedules() });
src/app/(app)/admin/service-parts/index.tsx:131:    await client.invalidateQueries({ queryKey: adminQueryKeys.serviceParts() });
src/app/(app)/admin/service-parts/purchases/[id].tsx:55:      await client.invalidateQueries({ queryKey: adminQueryKeys.servicePartPurchasesRoot() });
src/app/(app)/admin/service-parts/purchases/[id].tsx:56:      await client.invalidateQueries({ queryKey: adminQueryKeys.servicePartPurchase(purchaseId) });
src/app/(app)/admin/service-parts/purchases/index.tsx:84:      await client.invalidateQueries({ queryKey: adminQueryKeys.servicePartPurchasesRoot() });
src/app/(app)/admin/service-parts/suppliers.tsx:64:  const refresh = async () => client.invalidateQueries({ queryKey: adminQueryKeys.servicePartSuppliersRoot() });
src/app/(app)/admin/inventory/index.tsx:159:    await client.invalidateQueries({ queryKey: adminQueryKeys.inventory() });
src/app/(app)/admin/warranties/[id].tsx:163:    await client.invalidateQueries({
src/app/(app)/admin/warranties/rules.tsx:229:        client.invalidateQueries({ queryKey: adminQueryKeys.warrantyRules() }),
src/app/(app)/admin/warranties/rules.tsx:230:        client.invalidateQueries({ queryKey: adminQueryKeys.warranties() }),
src/app/(app)/admin/warranties/rules.tsx:252:      await client.invalidateQueries({ queryKey: adminQueryKeys.warranties() });
src/app/(app)/admin/field-operations/[id].tsx:157:    await client.invalidateQueries({ queryKey: adminQueryKeys.fieldOperations() });
src/app/(app)/admin/after-sales/[id].tsx:124:    await client.invalidateQueries({ queryKey: adminQueryKeys.afterSalesAdmin() });
src/app/(app)/admin/catalog/[id]/clone.tsx:89:      await client.invalidateQueries({ queryKey: adminQueryKeys.catalogAdvancedRoot() });
src/app/(app)/admin/catalog/[id]/direct-sale.tsx:117:        client.invalidateQueries({ queryKey: ['admin'] }),
src/app/(app)/admin/catalog/[id]/direct-sale.tsx:118:        client.invalidateQueries({ queryKey: ['products'] }),
src/app/(app)/admin/catalog/dictionaries/product-types/[id].tsx:99:      client.invalidateQueries({ queryKey: adminQueryKeys.dictionaryProductType(productTypeId) }),
src/app/(app)/admin/catalog/dictionaries/product-types/[id].tsx:100:      client.invalidateQueries({ queryKey: adminQueryKeys.dictionary('product-types') }),
src/app/(app)/admin/catalog/dictionaries/[resource].tsx:93:    await client.invalidateQueries({ queryKey: adminQueryKeys.dictionary(resource) });
src/app/(app)/admin/catalog/[id].tsx:198:      client.invalidateQueries({ queryKey: ['admin', 'catalog'] }),
src/app/(app)/admin/catalog/[id].tsx:199:      client.invalidateQueries({ queryKey: ['products'] }),
src/app/(app)/admin/catalog/[id].tsx:200:      client.invalidateQueries({ queryKey: ['catalog-filters'] }),
src/app/(app)/admin/catalog/create.tsx:178:        client.invalidateQueries({ queryKey: ['products'] }),
src/app/(app)/admin/catalog/create.tsx:179:        client.invalidateQueries({ queryKey: ['catalog-filters'] }),
src/app/(app)/admin/catalog/create.tsx:180:        client.invalidateQueries({ queryKey: ['admin-catalog-create-options'] }),
src/app/(app)/admin/catalog/data-quality/index.tsx:60:      await client.invalidateQueries({ queryKey: adminQueryKeys.dataQuality() });
src/app/(app)/admin/catalog/bulk/index.tsx:111:      await client.invalidateQueries({ queryKey: adminQueryKeys.catalogAdvancedRoot() });
src/app/(app)/admin/catalog/purchase-costs.tsx:44:        client.invalidateQueries({ queryKey: ['admin', 'catalog', 'purchase-costs'] }),
src/app/(app)/admin/catalog/purchase-costs.tsx:45:        client.invalidateQueries({ queryKey: adminQueryKeys.foundation() }),
src/app/(app)/admin/catalog/purchase-costs.tsx:46:        client.invalidateQueries({ queryKey: ['admin', 'catalog'] }),
src/app/(app)/admin/catalog/brands/index.tsx:70:        client.invalidateQueries({ queryKey: ['admin', 'brands'] }),
src/app/(app)/admin/catalog/brands/index.tsx:71:        client.invalidateQueries({ queryKey: ['admin', 'catalog'] }),
src/app/(app)/admin/catalog/brands/index.tsx:85:      await client.invalidateQueries({ queryKey: ['admin', 'brands'] });
src/app/(app)/admin/couriers/index.tsx:41:        client.invalidateQueries({ queryKey: ['admin', 'couriers'] }),
src/app/(app)/admin/couriers/index.tsx:42:        client.invalidateQueries({ queryKey: ['admin', 'orders'] }),
src/app/(app)/portal/messages/[id].tsx:32:      await client.invalidateQueries({ queryKey: ['portal', 'messages'] });
src/app/(app)/portal/messages/index.tsx:38:      await client.invalidateQueries({ queryKey: portalKey });
src/app/(app)/devices.tsx:30:  const revoke = useMutation({ mutationFn: api.devices.revoke, onSuccess: async (_, id) => { const current = query.data?.find((item) => item.id === id)?.is_current; if (current) await signOut(); else await client.invalidateQueries({ queryKey: ['devices'] }); } });
src/app/(app)/after-sales/[id].tsx:84:      void client.invalidateQueries({ queryKey: ['after-sales'] });
src/app/(app)/after-sales/create/[orderId].tsx:113:      await client.invalidateQueries({ queryKey: ['after-sales'] });
src/app/(app)/after-sales/create/[orderId].tsx:114:      await client.invalidateQueries({ queryKey: ['after-sales-options', orderId] });
src/app/(app)/sessions.tsx:35:      await queryClient.invalidateQueries({ queryKey: ['account', 'sessions'] });
src/app/(app)/sessions.tsx:42:      await queryClient.invalidateQueries({ queryKey: ['account', 'sessions'] });
INVALIDATION_CALL_SITES_END

============================================================
5. TARGETED OPTIMIZATION DECISION
============================================================
PRIMARY_OPTIMIZATION_CANDIDATE=DECOUPLE_NOTIFICATION_UNREAD_STATE_FROM_MAIN_AUTH_BOOTSTRAP_CONTEXT
PRIMARY_EXPECTED_EFFECT=READ_BADGE_CHANGES_STOP_INVALIDATING_ALL_USEAUTH_CONSUMERS
SECONDARY_OPTIMIZATION_CANDIDATE=MEMOIZE_HOME_DASHBOARD_AND_ACTION_SECTIONS_AROUND_UNREAD_OR_REFRESH_STATE_CHANGES
QUERY_CACHE_CHANGE_RECOMMENDATION=KEEP_GLOBAL_45S_AND_HOME_60S_FRESHNESS_UNCHANGED
PUSH_CHANGE_RECOMMENDATION=DO_NOT_REMOVE_BOOTSTRAP_REFRESH_UNTIL_UNREAD_COUNT_CORRECTNESS_PATH_IS_PROVEN
NEXT_PATCH_SCOPE_RECOMMENDATION=AUTH_UNREAD_CONTEXT_SPLIT_PLUS_HOME_RENDER_CONTAINMENT_WITHOUT_API_OR_DB_CHANGES

============================================================
6. FINAL READ-ONLY CERTIFICATION
============================================================
MOBILE_VALIDATE=PASS
MOBILE_TYPECHECK=PASS_TSC_NO_EMIT
CMS_STATIC_CHECK=PASS_983_OF_983
CLEAN_STABLE_VERIFY=PASS_RUN88
CANONICAL_BUILD13=PASS_PRESERVED_SHA256
SOURCE_AUTHORITY_POST=PASS_ONLY_CANONICAL_HTACCESS_DRIFT
STRICT_PARITY=PRESERVED_62_OF_62_100_PERCENT
PRODUCT_VARIANTS_REINTRODUCED=NO
EAS_BUILD_REQUIRED_NOW=NO
BUILD14_CREATED=NO
OPTIMIZATION_SOURCE_MUTATION_THIS_BATCH=NO
BATCH65_RESULT=PASS_HOME_QUERY_CACHE_READ_ONLY_AUDIT_COMPLETE
NEXT_ACTION=RUN_BATCH66_AUTH_UNREAD_CONTEXT_AND_HOME_RENDER_CONTAINMENT_OPTIMIZATION_PATCH_WITHOUT_NEW_EAS_BUILD
REPORT282_BODY_SHA256=4816d8ec7111e72e73dcc699c19e93c6f415374b42f2f49180f93e15ee3ccb70
