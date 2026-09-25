
============================================================
481 - BATCH179 V5 SOURCE RECOVERY PRE-MUTATION CERTIFICATION
============================================================
TIMESTAMP=20260925-091129
TASK=RECOVER_BATCH179_SOURCE_WITHOUT_GLASS_THEN_CERTIFY_BEFORE_ORDER81_MUTATION
EXPECTED_HEAD=c6a4660c02d07573dc4c648d4dea00d9642b4846
ORDER_ID=81
EXPECTED_OLD_SALE_PRICE_RSD=2140.00
TARGET_NEW_SALE_PRICE_RSD=21240.00
DATABASE_MUTATION=FORBIDDEN_IN_V5
SOURCE_MUTATION=GUARDED
GLASS_SCRIPT_EXECUTED=NO
REPORT_PATH=/home/icaffeco/ald1n-project/docs/operations/481-BATCH179-V5-SOURCE-RECOVERY-PRE-MUTATION-20260925-091129.md
CONCURRENCY_LOCK=ACQUIRED
ROUTE_CACHE_WAS_PRESENT_BEFORE=1

============================================================
0. GIT AUTHORITY / PRESTATE
============================================================

============================================================
RUN - git_fetch
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=git fetch origin main
From github.com:AldinAga/ald1n-project
 * branch            main       -> FETCH_HEAD
RC_git_fetch=0
BRANCH=main
LOCAL_HEAD=c6a4660c02d07573dc4c648d4dea00d9642b4846
REMOTE_HEAD=c6a4660c02d07573dc4c648d4dea00d9642b4846
HEAD_SUBJECT=docs: recover and certify Batch178 final EAS production build
 M apps/cms/current/public/.htaccess
?? docs/operations/481-BATCH179-DIRECT-SALE-PRICE-CORRECTION-TWO-DECIMAL-DISPLAY-FAILED-20260924-123719.md
?? docs/operations/481-BATCH179-DIRECT-SALE-PRICE-CORRECTION-TWO-DECIMAL-DISPLAY-V2-ROUTE-CACHE-RECOVERY-FAILED-20260924-153441.md
?? docs/operations/481-BATCH179-DIRECT-SALE-PRICE-CORRECTION-TWO-DECIMAL-DISPLAY-V3-FAILED-20260924-155301.md
?? docs/operations/481-BATCH179-V4-RECOVERY-STATE-AUDIT-20260925-082822.md
?? docs/operations/481-BATCH179-V4-RECOVERY-STATE-AUDIT-20260925-084458.md
?? docs/operations/481-BATCH179-V5-SOURCE-RECOVERY-PRE-MUTATION-20260925-091129.md
HTACCESS_SHA256_PRE=d10ecf0d63f9a8e1bd978a440afcbadb34faf1d24eb899bf2582d1e5c0033cef

============================================================
1. V3 EVIDENCE / HELPER AUTHORITY
============================================================
V3_HELPER_SHA256 patch.php f87c6674a5c904c040ffd16e4f51088c0d6d6c8a289fbc73e1ee0543db9e290e
V3_HELPER_SHA256 fix-presentation-precision.php 805b55087067b7bcc54045a8dd8a114213b2bbb5cc6dc7d5debdcb22d0229945
V3_HELPER_SHA256 precision-files.txt 7894d50c3a05f61928de19e9b10a850c6610a71aeb1744a3eb69690b5456c3e0
V3_HELPER_SHA256 order81-snapshot.php 17b25ceaf9ed5caca02ce5a449cb8defd696565a57dfc0f865f2eef56426b810
V3_HELPER_SHA256 guard-order81.cjs 3da915bc0bc98dfe7a18884ddc32c7a55f0ea553f5a38e4dd78b5141214b0098
V3_HELPER_SHA256 runtime-openapi-audit.cjs 39810be9cf3767eb3a994fdeede89081fab6ab1118b1ea57e958d128f7ae7fc3
V3_HELPER_SHA256 patch-login-glass.php 2d29d70cddfd07df16bb06897081d30ad44e59da1d63dd42ecd8c52aac1a0e71
V3_TEMP_EVIDENCE_AUTHORITY=PASS
PATCH_LOGIN_GLASS_POLICY=DO_NOT_EXECUTE_IN_V5

============================================================
2. EXACT BASELINE BLOB GUARDS
============================================================
BLOB apps/cms/current/app/Http/Controllers/Admin/OrderController.php actual=867469793d3091dc39d5913753575312ee059fe4 expected=867469793d3091dc39d5913753575312ee059fe4
BLOB apps/cms/current/app/Http/Controllers/Api/V1/Admin/OrderController.php actual=51f1d60213e678e3e660ec93d6dd5414bc563f18 expected=51f1d60213e678e3e660ec93d6dd5414bc563f18
BLOB apps/cms/current/app/Http/Controllers/Api/V1/Admin/OrderMutationController.php actual=585cce26772aaf73c0d58d189234c42963e3c857 expected=585cce26772aaf73c0d58d189234c42963e3c857
BLOB apps/cms/current/app/Services/OrderDetailPresenter.php actual=25f24870c8bfe8b184badc7c42678d8253636899 expected=25f24870c8bfe8b184badc7c42678d8253636899
BLOB apps/cms/current/app/Support/ViewValue.php actual=f6c968175797717ad14aacb5c6337f3a28f1dd47 expected=f6c968175797717ad14aacb5c6337f3a28f1dd47
BLOB apps/cms/current/resources/views/admin/orders/show.blade.php actual=52ba2836153a7e875a159f175cbf306215eec28e expected=52ba2836153a7e875a159f175cbf306215eec28e
BLOB apps/cms/current/resources/views/layouts/app.blade.php actual=af50ec71eb590dbb8c6c487d4f8f9e5a7bbc168c expected=af50ec71eb590dbb8c6c487d4f8f9e5a7bbc168c
BLOB apps/cms/current/routes/api.php actual=d0194bccd08c0d9681da0c756ef31c1b94fae3a9 expected=d0194bccd08c0d9681da0c756ef31c1b94fae3a9
BLOB apps/cms/current/routes/web.php actual=c8cc56c14381e2311a66a4592e1f4c319f7b831a expected=c8cc56c14381e2311a66a4592e1f4c319f7b831a
BLOB apps/mobile/current/src/features/admin/orders-admin-api.ts actual=413481f2b5570cc1e93b52a85b6ec0da508385df expected=413481f2b5570cc1e93b52a85b6ec0da508385df
BLOB apps/mobile/current/src/features/admin/orders-admin-actions.tsx actual=f48b11e52eb7c3008c918a382df708f965b59852 expected=f48b11e52eb7c3008c918a382df708f965b59852
BLOB apps/mobile/current/src/app/(app)/admin/orders/[id].tsx actual=e49be6e4765e43f89afd377ba16df33e2002f432 expected=e49be6e4765e43f89afd377ba16df33e2002f432
BLOB apps/mobile/current/src/lib/formatters.ts actual=23ba4072e79be14736aefa684b82215305905a56 expected=23ba4072e79be14736aefa684b82215305905a56
BLOB apps/mobile/current/scripts/validate-project.mjs actual=46a54a4f956fbaddceffa41e5a54a28418293a7f expected=46a54a4f956fbaddceffa41e5a54a28418293a7f
BLOB packages/api-contract/openapi.yaml actual=9b8f445ff2bc623cf45c78ea13a9ccdd21f57bb2 expected=9b8f445ff2bc623cf45c78ea13a9ccdd21f57bb2
BLOB apps/cms/current/docs/openapi.yaml actual=9b8f445ff2bc623cf45c78ea13a9ccdd21f57bb2 expected=9b8f445ff2bc623cf45c78ea13a9ccdd21f57bb2
BLOB apps/mobile/current/docs/openapi.yaml actual=9b8f445ff2bc623cf45c78ea13a9ccdd21f57bb2 expected=9b8f445ff2bc623cf45c78ea13a9ccdd21f57bb2
BLOB apps/cms/current/resources/views/auth/login.blade.php actual=11f2d8c636c4e0a790c0dee6e96b97ce55342dbd expected=11f2d8c636c4e0a790c0dee6e96b97ce55342dbd
BLOB apps/cms/current/public/assets/css/ald1n-ui-v2.css actual=260ef7c5723e9d2af61178d4b56f31f6976ba020 expected=260ef7c5723e9d2af61178d4b56f31f6976ba020

============================================================
3. ORDER 81 EXACT READ-ONLY PRECONDITION
============================================================

============================================================
RUN - order81_snapshot_before
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=php /home/icaffeco/.ald1n-batch179-20260924-155301/order81-snapshot.php
{
    "id": 81,
    "order_number": "APC-20260920-00000081",
    "source_system": "laravel",
    "sales_channel": "direct_sale",
    "status": "shipped",
    "completed_at": "2026-09-20 13:32:39",
    "archived_at": null,
    "payment_method": "cash",
    "payment_status": "paid",
    "payment_state": "paid",
    "subtotal_rsd": 2140,
    "paid_total_rsd": 2140,
    "direct_sale_recorded_by": 1,
    "items": [
        {
            "id": 43,
            "quantity": 1,
            "unit_price_original": 2140,
            "unit_price_rsd": 2140,
            "line_total_rsd": 2140
        }
    ],
    "payments": [
        {
            "id": 30,
            "entry_type": "payment",
            "status": "verified",
            "amount_rsd": 2140
        }
    ],
    "active_issued_documents": 0,
    "receivable_cases": 0
}
RC_order81_snapshot_before=0

============================================================
RUN - order81_exact_guard
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /home/icaffeco/.ald1n-batch179-20260924-155301/guard-order81.cjs /home/icaffeco/.ald1n-batch179-v5-20260925-091129/order81-before.json
ORDER81_EXACT_PRECONDITION=PASS
RC_order81_exact_guard=0
ORDER81_PRECONDITION=PASS
DATABASE_MUTATION_SO_FAR=NO

============================================================
4. APPLY BATCH179 CORE SOURCE PATCH - GLASS EXCLUDED
============================================================

============================================================
RUN - patch_source
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=php /home/icaffeco/.ald1n-batch179-20260924-155301/patch.php /home/icaffeco/ald1n-project
RC_patch_source=0

============================================================
RUN - precision_fix
============================================================
CWD=/home/icaffeco/ald1n-project
COMMAND=php /home/icaffeco/.ald1n-batch179-20260924-155301/fix-presentation-precision.php /home/icaffeco/ald1n-project /home/icaffeco/.ald1n-batch179-20260924-155301/precision-files.txt
PRECISION_CHANGED=apps/cms/current/resources/views/admin/field-operations/show.blade.php
PRECISION_CHANGED=apps/cms/current/resources/views/admin/service-parts/index.blade.php
PRECISION_CHANGED=apps/cms/current/resources/views/admin/service-parts/purchase-show.blade.php
PRECISION_CHANGED=apps/cms/current/resources/views/admin/settings/exchange-rate.blade.php
PRECISION_CHANGED=apps/cms/current/resources/views/dashboard/index.blade.php
PRECISION_CHANGED=apps/cms/current/resources/views/layouts/app.blade.php
PRECISION_CHANGED=apps/mobile/current/src/app/(app)/admin/exchange-rate/index.tsx
PRECISION_CHANGED=apps/mobile/current/src/app/(app)/admin/service-parts/index.tsx
PRECISION_CHANGED=apps/mobile/current/src/app/(app)/admin/service-parts/purchases/[id].tsx
RC_precision_fix=0
LOGIN_GLASS_ISOLATION=PASS

============================================================
5. EXACT SOURCE SCOPE
============================================================
ACTUAL_SOURCE_CHANGED_BEGIN
apps/cms/current/app/Http/Controllers/Admin/OrderController.php
apps/cms/current/app/Http/Controllers/Api/V1/Admin/OrderController.php
apps/cms/current/app/Http/Controllers/Api/V1/Admin/OrderMutationController.php
apps/cms/current/app/Services/OrderDetailPresenter.php
apps/cms/current/app/Support/ViewValue.php
apps/cms/current/docs/openapi.yaml
apps/cms/current/resources/views/admin/field-operations/show.blade.php
apps/cms/current/resources/views/admin/orders/show.blade.php
apps/cms/current/resources/views/admin/service-parts/index.blade.php
apps/cms/current/resources/views/admin/service-parts/purchase-show.blade.php
apps/cms/current/resources/views/admin/settings/exchange-rate.blade.php
apps/cms/current/resources/views/dashboard/index.blade.php
apps/cms/current/resources/views/layouts/app.blade.php
apps/cms/current/routes/api.php
apps/cms/current/routes/web.php
apps/mobile/current/docs/openapi.yaml
apps/mobile/current/scripts/validate-project.mjs
apps/mobile/current/src/app/(app)/admin/exchange-rate/index.tsx
apps/mobile/current/src/app/(app)/admin/orders/[id].tsx
apps/mobile/current/src/app/(app)/admin/service-parts/index.tsx
apps/mobile/current/src/app/(app)/admin/service-parts/purchases/[id].tsx
apps/mobile/current/src/features/admin/orders-admin-actions.tsx
apps/mobile/current/src/features/admin/orders-admin-api.ts
apps/mobile/current/src/lib/formatters.ts
packages/api-contract/openapi.yaml
ACTUAL_SOURCE_CHANGED_END
SOURCE_SCOPE_DRIFT_BEGIN
apps/cms/current/app/Services/DirectSalePriceCorrectionService.php
SOURCE_SCOPE_DRIFT_END

============================================================
ROLLBACK SOURCE TO PRE-V5 BASELINE
============================================================
SOURCE_ROLLBACK=ATTEMPTED

   INFO  Routes cached successfully.  

ROUTE_CACHE_RESTORE=ROUTE_CACHE_REBUILT

============================================================
FAIL
============================================================
BATCH179_V5_SOURCE_RECOVERY=FAIL
FAILED_REASON=SOURCE_SCOPE_DRIFT
ORDER_ID=81
ORDER_PRICE_CORRECTION_TARGET=2140.00_TO_21240.00
DATABASE_MUTATION=NO
BUSINESS_DATA_MUTATION=NO
GLASS_SCRIPT_EXECUTED=NO
SOURCE_COMMIT=NONE
REPORT_PATH=/home/icaffeco/ald1n-project/docs/operations/481-BATCH179-V5-SOURCE-RECOVERY-PRE-MUTATION-20260925-091129.md
