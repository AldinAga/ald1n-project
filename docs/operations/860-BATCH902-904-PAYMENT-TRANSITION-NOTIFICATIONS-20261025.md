# Report860 — B902–B904 verified/voided payment notification idempotence; B798 warranty integration and full B803

Date: 2026-10-25, Europe/Belgrade. Type: **SOURCE_ONLY_SECURITY_AND_BUSINESS_INTEGRATION**, no production changes.

## Fresh authority and canonical acceptance ledger

- GitHub `main` read before implementation: `084de7b4709d883fcff2da12b367bb4e6cfd362d` ([Report859](859-BATCH899-901-STOREFRONT-RELATIVE-ORIGIN-20261025.md)); `AGENTS.md`, `000-LATEST.md` (known stale B832; direct edit earlier provider-denied — no retry), latest Report859, canonical Google Drive `ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md`, Report796 authoritative scope, connected Drive `ALD1N-PROGRESS-LEDGER-EXPLAINED-20261024.md`, and [B803 issue104](https://github.com/AldinAga/ald1n-project/issues/104). No production version was inferred from source.
- [Owner Report796 M01–M17 ledger](https://drive.google.com/file/d/1QA5x3d6BIu2R2wwRkex82TKgXozH8uF_/view?usp=drivesdk) preserves **M01–M07 closed**, **M08–M15 eight CMS outcomes not formally closed**, M16 signed Android 16 KiB and M17 SDK58 pending.
- **Source-only Draft PR110**: https://github.com/AldinAga/ald1n-project/pull/110 , stacked on PR109 immutable parent `ccc4ca043a0f164aa6ee993af50ab18ee64daf4a`; current exact source HEAD **`1c8abcf02a9313de79497fc626b3403292e4d386`**. Exactly three allowlisted changed paths: `apps/cms/current/app/Services/OrderPaymentService.php`, `apps/cms/current/tests/Feature/PaymentTransitionNotificationB902Test.php` (new), `.github/workflows/b902-payment-transition-notifications-qa.yml` (new). No existing B798 or protected B805/B809/B815 fixture changed.

## Real production-service bug reproduced and patched (M10 partial)

- Both `OrderPaymentService::verify()` and `::void()` correctly returned a previously transitioned payment **without creating another ledger state transition**, but *after* the DB transaction they unconditionally re-sent `notifyCustomer()`. A user clicking twice or retrying a POST therefore generated multiple `order.payment_verified` or `order.payment_voided` operational notifications. This is an application business side-effect defect, not evidence of real customer impact on the OFF store.
- TDD [original RED CI #40224887069](https://github.com/AldinAga/ald1n-project/actions/runs/40224887069): actual PHP8.4/MariaDB10.11.19, **three original test identities with three expected failures**. Logs proved repeated verification emitted three `order.payment_verified` events instead of one, repeated void emitted three `order.payment_voided` events instead of one, and distinct second installment combined with a retry emitted one extra duplicate. Captured [original 10,264-byte source and JUnit RED ZIP on Google Drive](https://drive.google.com/file/d/1eZlEeKahAmrTYvNiNE9ie1AqLx4GLFtf/view?usp=drivesdk).
- Minimal real service fix: boolean `$transitioned` is initialized/reset on each DB transaction callback (including DB retry), set **only after** successfully performing the legitimate state update, audit and verified issuance. `notifyCustomer()` occurs only when that transition was committed. Existing `OrderFinancialReconciliationService` call still runs for idempotent no-ops; all order status, payment amount, buyer roles, warranty source/date and merchant checks remain unchanged.
- [First GREEN CI #40224993396](https://github.com/AldinAga/ald1n-project/actions/runs/40224993396): **three new tests, 25 assertions PASS**.
- Expanded exact-head integrated [B902–B904 CI #40225096041](https://github.com/AldinAga/ald1n-project/actions/runs/40225096041) **SUCCESS: all 9/9 PHP8.4/MariaDB10.11.19 unique cases, 82 assertions**, zero fail/errors/skips. Six are the **original untouched `WarrantyFirstPaymentIssuance798Test`** covering first positive deposit/pending non-started warranty, delivered activation with same certificate, deferred direct sale first installment, COD completion, nonverified/refund negative controls, PDF entitlement. Three are new B902 retry and distinct-installment notification tests. [Readback-verified 14,295-byte full source/JUnit/SHA ZIP on Drive](https://drive.google.com/file/d/1_LXF5XS2Lz8-POA9XyY6ozxTY8dGjMlx/view?usp=drivesdk).
- No live email: `Mail::fake()` and `Notification::fake()`, disposable seeded users/orders/products/payments in isolated MariaDB. Existing original B798 PHP/DB tests were not edited or reduced.

## Mandatory immutable full Laravel B803 release gate

- [Exact-head B803 workflow #40225095973](https://github.com/AldinAga/ald1n-project/actions/runs/40225095973), actual PHP8.4/MariaDB10.11.19: original baseline **451 tests / 44 failures+errors** (38 failure/6 error/4 warnings). Candidate **657 tests / 98 failures+errors** (89 failure/9 error). Comparator **`NEW_FAILURES=56 WHOLE_SUITE_GREEN=NO`**.
- Independently compared all 56 failure identity strings with previous [Report859 B803 run #40223700268](https://github.com/AldinAga/ald1n-project/actions/runs/40223700268): **exact 56/56 unchanged, zero added and zero removed**. This is **NOT full-suite PASS**; old anonymous-success vs later verified Kupac rules and protected safety-denied B815 fixture remain [Issue104 HOLD](https://github.com/AldinAga/ald1n-project/issues/104). No skips/excludes/waivers, no weakening authentication, session or private receipts.
- [Readback-verified 368,986-byte complete original JUnit, logs, exact runtime hash and case lineage Drive ZIP](https://drive.google.com/file/d/1Xi2SxyfKnVTVcUtL6PKwptWarl-DO3S6/view?usp=drivesdk).

## 8 remaining CMS acceptance milestones and next gates

Source: [Original Report796](https://drive.google.com/file/d/1QA5x3d6BIu2R2wwRkex82TKgXozH8uF_/view?usp=drivesdk) and [current explanatory ledger](https://drive.google.com/file/d/1MvF9AJ_vt0hsD3JvExN3N89I5EQoxpz9/view?usp=drivesdk).

| Milestone | Exact acceptance outcome | Actual remaining gate |
| --- | --- | --- |
| M08 | SuperAdmin customer/order **operational** context, not personal purchases | B796 source/host PASS; independent owner behavior acceptance pending |
| M09 | Reusable readable animated Liquid Glass, with reduced-motion/focus/touch protections | B796 source/host evidence; final dynamic animation UAT acceptance pending |
| M10 | One-time warranty issuance on verified positive payment, including direct sales | B798 source/host proof + B902 notification retry fix and nine-case CI; payment/partial-date business semantics, independent production acceptance pending |
| M11 | Seller/SuperAdmin per-recipient **explicit** expiry opt-in | B798 source/host proof; separate recipient/channel/dispatch UAT acceptance pending |
| M12 | Canonical shipment number/carrier/safe link on first buyer panel | B796 source/host evidence; final owner acceptance pending |
| M13 | Public catalog/products at /katalog | Draft PR93–110 source; 368-item OWASP, test suite, host OFF rollout and user acceptance HOLD |
| M14 | Public cart/full retail checkout | New security policy requires registered activated Kupac only; historical anonymous B799 success tests are superseded but unreconciled; whole-suite security/merchant/release HOLD |
| M15 | Secure owned private order follow-up/documents/shipment/warranty | Draft source; bearer+tracked-session isolation, full penetration checks, source/host release acceptance HOLD |

- Browser SEC23 and OWASP WSTG v4.2 / ASVS5 L2 **0/368 independent accepted items**; staged production Storefront still **OFF** (B798). Previous backups/prerequisite Report815 are NOT to be rerun as unfinished.
- Android M16 already passed debug native/AAB/16 KiB emulator on [PR82 CI #40192813557](https://github.com/AldinAga/ald1n-project/actions/runs/40192813557) but **signed EAS production AAB** with remote `versionCode > rejected 27`, signer verification, physical 16 KiB UAT and Google Play acceptance remain HOLD. SDK58 M17 deferred.
- All production operations omitted: no SSH, incoming host script, CloudLinux deployment, Storefront ON, real order/SMTP, production database mutation, EAS native build, OTA, Play submission, PR merge or inode cleanup.

Formal acceptance ledger is **M01–M07 = 7/17 41.2% total**, **7/15 46.7% CMS**, **original six UI checks 6/6 100%**; this scoped M10 source improvement does not itself close M10. **One concrete M10 defect fixed + 9 real tests GREEN, but production milestone OPEN.**

PR110_HEAD=1c8abcf02a9313de79497fc626b3403292e4d386
B902_RED=3_OF_3_DUPLICATE_NOTIFICATION_FAILURES
B902_B904_GREEN=9_OF_9_REAL_MARIADB_HTTP_AND_DOMAIN_PASS_82_ASSERTIONS
B803_BASELINE=451_TESTS_44_FAILURES_ERRORS
B803_CANDIDATE=657_TESTS_98_FAILURES_ERRORS
B803_56_FAILURE_IDENTITIES=EXACT_UNCHANGED_VS_REPORT859
B803_NEW_REGRESSIONS_VS_REPORT859=0
FULL_SUITE=FAIL_HOLD
M10_FORMAL_ACCEPTANCE=OPEN
STOREFRONT_PRODUCTION=OFF
ANDROID16KB_SIGNED_EAS_PLAY=HOLD
CMS_ACCEPTED=7_OF_15_46_7_PERCENT
PROGRAM_ACCEPTED=7_OF_17_41_2_PERCENT
OWNER_SSH_REQUIRED=NO
PRODUCTION_MUTATION=NONE
