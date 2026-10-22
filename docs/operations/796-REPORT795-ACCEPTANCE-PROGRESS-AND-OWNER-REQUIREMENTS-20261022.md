# Report796 - Report795 accepted, progress ledger and new owner requirements

Date: 2026-10-22
KIND=DOCUMENTATION_AND_READ_ONLY_SOURCE_AUDIT
BASE_MAIN=7a14319300cfed42578664883f08b251ce69798d
DEPLOYED_CMS_SOURCE=71fb8a6a07375acefafa766a291643c9faa93585
PREVIOUS_BATCH=795
BATCH795_RESULT=PASS_OWNER_ACCEPTANCE
UI_BASELINE_ACCEPTANCE=6_OF_6
NEW_RUNTIME_IMPLEMENTATION=NOT_PERFORMED_IN_THIS_CHECKPOINT
PRODUCTION_MUTATION=NONE
DATABASE_MUTATION=NONE
ANDROID_BUILD_OTA_PLAY_ACTION=NONE
PUBLIC_STOREFRONT=PLANNED_NOT_IMPLEMENTED_NOT_ACTIVATED
PUBLIC_CATALOG_CANONICAL_TARGET=https://cms.ald1n.com/katalog
NEXT_OPERATOR_BATCH=ONLY_AFTER_IMPLEMENTATION_AND_QA_NO_REPEAT_ACCEPTANCE

## Evidence and interpretation

The unchanged owner-supplied `795-BATCH795-UI-ACCEPTANCE-READONLY-20261022-121657.md` is 2999 bytes, SHA-256 `e84ee86d200ce43e31eedfbb50f77b084fe6cc3ae990f438ae20b26c4e4a1cd0`.
It records exact nine runtime files and both public CSS byte checks PASS, all OWNER_CHECK_1 through OWNER_CHECK_6 PASS, UI_ACCEPTANCE=PASS_OWNER_ATTESTED and exit 0. No application/database/maintenance/scheduler mutation occurred. Do not rerun completed Batches793-795 or the completed warranty preference migration.
Owner acceptance is genuine evidence of the six tested UI requirements, not independent authenticated PHP-FPM/OPcache provenance; the latter remains NOT_INDEPENDENTLY_ATTESTED. The new owner requests below extend scope and do not revoke the earlier six acceptance results.

## Progress method - scope ledger v1

Percentages count CLOSED ACCEPTANCE MILESTONES, not lines of code, time, effort or all historical Ald1n modules. Each listed milestone has weight 1 and gets closure credit only when its applicable source, tests, production and acceptance gates are satisfied. Planning/partial implementations are described but do not earn closure credit. Preserve this denominator until the owner explicitly changes scope; record any denominator change separately.

| ID | Milestone | Closed | Evidence or remaining work |
| --- | --- | --- | --- |
| M01 | Laravel CloudLinux backend activation | 1 | Report791 / Batch793, preserved by Report793 |
| M02 | Whole-document horizontal overflow correction | 1 | Report795 check 1 |
| M03 | Desktop period and priorities row | 1 | Report795 check 2 |
| M04 | Product-first recent-order labels | 1 | Report795 check 3 |
| M05 | Readable Liquid Glass action/module styling | 1 | Report795 check 4 |
| M06 | Product specification insets and gallery/summary height | 1 | Report795 check 5 |
| M07 | Grouped user editor and separate retirement zone | 1 | Report795 check 6 |
| M08 | SuperAdministrator operational customer-order context, no personal purchase flow | 0 | New requirement; source issue identified |
| M09 | Reusable, readable Liquid Glass card animation | 0 | New enhancement; static glass acceptance is not animation completion |
| M10 | Automatic, idempotent warranty issuance on confirmed incoming payment, including direct sales | 0 | Current issuance still requires delivery/completion |
| M11 | Per-recipient opt-in expiry: originating seller and SuperAdministrator only | 0 | Default OFF/strict owner foundation exists; new administrator exception not implemented |
| M12 | Shipment number and safe tracking action in the first buyer/delivery panel | 0 | Existing shipment data, not yet placed in that panel |
| M13 | Public retail catalog and product pages | 0 | Planned; target /katalog, existing /catalog remains authenticated |
| M14 | Guest cart and checkout at full retail price | 0 | Planned; no commission deduction, stock/idempotency/security gates required |
| M15 | Secure guest order follow-up and tracking | 0 | Planned; per-order capability, documents/status/cancellation security required |
| M16 | New signed Android 16 KB-compatible artifact and release acceptance | 0 | Source/verifier work exists; final signed-binary gate remains open |
| M17 | Actual SDK58 upgrade and regression/release acceptance | 0 | Deferred until current work and Android compatibility close |

ORIGINAL_UI_CLOSED=6/6
ORIGINAL_UI_PERCENT=100.0
EXPANDED_CMS_CLOSED=7/15
EXPANDED_CMS_PERCENT=46.7
EXPANDED_CURRENT_PROGRAM_CLOSED=7/17
EXPANDED_CURRENT_PROGRAM_PERCENT=41.2

The 41.2% figure is NOT a claim that the entire historical application is only 41.2% developed, nor that 58.8% of engineering time remains. No verified complete historical work-breakdown/effort denominator is available. iOS owner-deferred work is excluded from this ledger, not declared completed.

## Source findings at the exact audited main

- `DashboardController::portalData()` enables the personal center from `orders_view_own`; `CustomerPortalService::build()` filters orders by the signed-in user's `user_id`. SuperAdministrator permissions therefore also expose the personal customer center. Changing its heading alone would preserve the wrong data scope.
- `routes/web.php` places `/catalog`, its details and the current cart behind authentication/active-session permissions. `/katalog` is the chosen NEW public target, not a verified active storefront. Do not remove authentication from internal routes to implement it.
- `WarrantyService::ensureForOrder()` returns early until completion or delivery; `OrderPaymentService::verify()` and `record()` have no warranty issuance hook. `DirectSaleService` already calls ensureForOrder after its normal workflow; preserve existing guarantees and do not claim direct-sale coverage is wholly absent.
- `OperationalNotificationService::send()` already defaults `warranty_expiry_alerts` OFF and requires strict order-item -> order -> user ownership. It also rejects an unrelated SuperAdministrator. The requested seller-plus-SuperAdministrator rule intentionally extends that previous owner-only recipient contract; it is not already implemented by the existing preference.
- `OrderDetailPresenter` already projects shipment tracking number, courier name and tracking URL. The first buyer/delivery panel in `admin/orders/show.blade.php` does not render them. Reuse canonical shipment data; do not create a competing tracking store.

## Authoritative owner requirements - 2026-10-22

These requirements describe the NEXT implementation, not current deployed behavior. They supersede older contrary assumptions only within the explicit scopes below.

### R1. SuperAdministrator order context

The SuperAdministrator is an operator/seller, not a personal shopper. Replace the personal-order center with customer/user operational orders and admin detail links. Remove the SuperAdministrator's "Moje porudzbine" labels, counters, links and self-purchase entrypoints consistently across relevant navigation, dashboard and order actions. Ordinary users retain their own correctly scoped order view.
Direct sales remain legitimate sales records with the actual buyer/shipping snapshot and recording administrator; they are NOT the administrator's personal purchases. Admin-created orders on behalf of a real user must preserve that user's ownership. Do not delete, reassign or rewrite existing orders, warranty ownership, commissions or financial history to remove a UI concept. Test both role-specific rendering and server-side create/read access; a label-only fix is insufficient.

### R2. Public URL and retail boundary

Chosen public catalog entry: `https://cms.ald1n.com/katalog`. Proposed public paths: `/katalog/{slug}`, `/korpa`, `/porucivanje`; use a separate public route/view-model namespace. These paths are not active by this checkpoint.
Preserve internal `/catalog` and `/admin/*` authentication. Guest prices are the full configured retail amount with no commission subtraction or internal-cost disclosures. Guest checkout creates no privileged or shared fake buyer account. Preserve the previously approved full storefront scope and its security gates; no partial production checkout.

### R3. Animated Liquid Glass cards

Use one reusable, scoped treatment for quick actions, modules and visually equivalent interactive cards. A restrained moving highlight/reflection on hover and keyboard focus must not move, blur or dim text, shift layout, intercept pointer events or obscure content. Preserve contrast and visible focus. Avoid perpetual shimmering across all cards, device-tilt permission requests and per-frame global DOM polling. Respect reduced-motion, reduced-transparency, forced-colors and touch fallbacks. Verify light/dark, keyboard/touch and narrow screens. Existing static glass acceptance is preserved.

### R4. Warranty issuance upon payment confirmation

Owner requirement: a genuinely confirmed incoming payment must trigger automatic warranty issuance for eligible items, including SuperAdministrator direct sales; merely uploading a payment proof must NOT issue it. Do not wait solely for the completed/delivered order status. Use the authoritative verified payment ledger, never a manually posted paid flag. Issuance must be one-per-order-item and safe on retries/concurrent confirmations; refunds, rejected/submitted proofs and zero payments are not new issuance triggers. Existing direct-sale issuance and historical certificates must not be duplicated, silently voided or re-dated. Preserve product/category/global duration rules; do not invent a warranty period.
Payment-triggered issuance and the contractual start of warranty are separate concerns. Do not silently shorten an undelivered buyer's warranty by treating a deposit date as delivery. Partial-payment/installment and pre-delivery start-date semantics require an explicit tested implementation contract, and, where existing rules cannot resolve them, a focused owner decision before production activation. This checkpoint deliberately does not redefine "confirmation" as either "any deposit" or "fully settled only" without evidence. No mass backfill or customer mail from this audit.

### R5. Expiry recipients and opt-in

`warranty_expiry_alerts` remains OFF by default for every new/existing account lacking explicit opt-in. Preserve already given valid opt-ins; do not bulk reset or auto-enable them. Keep this separate from ordinary warranty updates and apply enabled delivery-channel preferences.
Eligible recipients: (a) the originating selling user's immutable order-item -> order -> user chain, and (b) active SuperAdministrator accounts with their OWN explicit expiry opt-in. Do not substitute the final buyer's typed shipping name, mutable warranty `user_id`, current assignee, all administrators or all users. Require valid matching warranty/order-item/order lineage even for the administrative exception; use correct role-specific URLs. A direct sale without a registered selling user goes only to eligible opted-in SuperAdministrators, not a fabricated user.
Each recipient decides independently: seller ON/admin OFF -> seller only; seller OFF/admin ON -> admin only; both ON -> both; both OFF -> none. Deduplicate per recipient/event/window and enforce eligibility/opt-out again at dispatch for queued email/push. An administrator's switch never enables another user's switch. Use synthetic seller A/order A versus seller B/order B tests; do not publish the owner's real customer/phone/address screenshots in repository fixtures. The owner's named order example is a required scenario, not independently audited production ownership.
The new SuperAdministrator exception supersedes only the previous owner-only EXPIRY recipient restriction. It does not relax generic customer ownership, document permissions or unrelated notifications, and it does not authorize a global broadcast or immediate scheduler run.

### R6. Tracking in the first buyer panel

In "Krajnji kupac - kontakt i dostava", show the canonical recorded shipment number, courier and a "Prati posiljku" action for the SuperAdministrator. Reuse the shipment snapshot/presenter and existing safe tracking-link builder. Validate scheme/destination and escape output; use `noopener noreferrer` for a new tab. With no shipment number, show an explicit not-yet-recorded state; with no valid URL, show the number without a fake working button. Preserve current lower shipment block and all shipment/business state.

## Execution sequence and boundaries

1. Implement R1/R3/R6 with role, browser, access and link-safety regression in isolated source.
2. Implement R4/R5 with payment, direct-sale, recipient/channel, dedupe, rollback and MariaDB regressions. Settle the R4 business semantics before its production activation.
3. Complete the public catalog/cart/guest-checkout/follow-up flow; activate only a tested complete flow with real merchant configuration.
4. Close the separately gated signed Android 16 KB artifact; then perform the actual SDK58 upgrade. Do not repeat already green unchanged compiler/verifier work or spend a build solely for documentation/UI.

No new production batch is delivered for this documentation-only checkpoint. The owner has already completed the prior acceptance. The next operator script should apply tested changes, not ask to repeat the same six acceptance answers. All deployment artifacts require canonical incoming basename, automatic integrity, fresh remote/local report sequence, bounded source scope and safe recovery.
