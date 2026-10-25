# Report861 — B905–B908 M11 live warranty expiry eligibility and historical regression lineage

Date: 2026-10-26 Europe/Belgrade. **SOURCE + CI ONLY**; no production deploy, host/SSH, email provider delivery, EAS/OTA/Play or user-data mutation.

## Provenance / branch contract

- Reconstructed fresh GitHub `main` **`0ca06d12719e5cf003b481b041b059a78a31bb45`** (Report860), `AGENTS.md`, `docs/operations/000-LATEST.md` (**known stale B832**; a prior direct change was safety-denied, not retried), [Report860](860-BATCH902-904-PAYMENT-TRANSITION-NOTIFICATIONS-20261025.md), Google Drive canonical `ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md`, [Report796 accepted milestone ledger](https://drive.google.com/file/d/1QA5x3d6BIu2R2wwRkex82TKgXozH8uF_/view?usp=drivesdk), [Issue104 global B803 release HOLD](https://github.com/AldinAga/ald1n-project/issues/104). No assumptions about latest production checkout.
- [**Draft PR111**](https://github.com/AldinAga/ald1n-project/pull/111), branch `fix/b905-warranty-expiry-live-eligibility-20261026`, stacked on immutable PR110 base **`1c8abcf02a9313de79497fc626b3403292e4d386`**. Final exact source HEAD **`3acc37e90332b0d8fde659503c5cc1a8b5fcc6aa`**, not merged.
- Exactly 3 scoped changed paths: `apps/cms/current/app/Services/WarrantyExpiryRecipientPolicy.php`, new `apps/cms/current/tests/Feature/WarrantyExpiryFreshEligibilityB905Test.php`, new `.github/workflows/b905-warranty-expiry-live-eligibility-qa.yml`. No original unit tests, protected B805/B809/B815 fixtures, queue/SMTP, payment, inventory or Android source edited.

## Actual confirmed M11 defects and TDD progression

- Warranty expiry recipient eligibility formerly used potentially stale in-memory `ProductWarranty` / `User` state and `loadMissing('orderItem.order.user')`. Synthetic real DB updates after a prior read could leave an old authorization: a demoted SuperAdmin, deactivated or retired original seller, cancelled order, voided certificate, mismatched warranty/product-item association or actual application-service notification to a demoted recipient. No claim that any real customer was affected.
- First disposable new-test run [#40244001507](https://github.com/AldinAga/ald1n-project/actions/runs/40244001507) identified two invalid *new test fixture* assumptions (PHPUnit unordered assertion method and status enum `inactive`). Corrected the new test only (`assertEqualsCanonicalizing` and real `pending` status), with no application-code change. A ninth negative test covers a previously read warranty later voided.
- Valid independent [**RED #40244143574**](https://github.com/AldinAga/ald1n-project/actions/runs/40244143574): **9 original PHP8.4/MariaDB10.11.19 tests, 7 distinct intentional security failures, 2 existing positive controls PASS, zero test errors**. [Original 7-failure source/JUnit ZIP on Google Drive](https://drive.google.com/file/d/1ACc1RcT4YA0nopXiQ1KKA5ONCl_qpM-o/view?usp=drivesdk), readback 7,862 bytes.
- Minimal fix: `WarrantyExpiryRecipientPolicy::allows` re-fetches current persisted warranty with `orderItem.order.user`, current recipient and role, then enforces active/non-retired recipient, live warranty status, original order-item/order/product FK lineage, non-cancelled order, **only originating seller or current SuperAdmin**, and that recipient's **fresh explicit `warranty_expiry_alerts === true` opt-in**. Normal `warranty_updates` never grants expiry consent; no auto-enabled preferences. Invalid/deleted lineage fails closed. `recipients()` filters results using the revised same `allows` policy. No new role, purchase or private document capability.
- First targeted [**GREEN #40244295513**](https://github.com/AldinAga/ald1n-project/actions/runs/40244295513): **9/9, 26 assertions PASS**; [source/JUnit Drive ZIP](https://drive.google.com/file/d/19OBK_FhKKOYm87XfeOxup5KXnepJjhQG/view?usp=drivesdk), 7,984 bytes.
- Mandatory complete B803 run on that *intermediate* head [#40244295481](https://github.com/AldinAga/ald1n-project/actions/runs/40244295481) reported **666 cases / 99 problems / 57 newly failing over 451 baseline**, including exactly one added source-contract test `WarrantyExpiryOptInSourceTest::test_expiry_is_default_off_and_requires_immutable_seller_or_independent_superadmin_opt_in`. This unit test asserts literal `->warranty_expiry_alerts === true` in policy source. Restored the original precise opt-in check **on the refreshed current recipient** rather than relaxing or editing the original unit test. This fixed the compatibility regression without weakening the security patch. [Complete intermediate original JUnit/lineage Drive ZIP](https://drive.google.com/file/d/1U4BhoK-tpcZoSEpiTMNcT6h9_ujMzCS4/view?usp=drivesdk), readback 371,553 bytes.
- Final pinned [**B905–B908 target CI #40244904606**](https://github.com/AldinAga/ald1n-project/actions/runs/40244904606) **SUCCESS: 12/12 original distinct tests, 51 assertions, no failures/errors/skips/warnings**. Includes nine new real MariaDB feature tests and **all three original, unchanged M11 source/OpenAPI/migration unit tests**; exact head and immutable parent, 3-path allowlist, original JUnit, SHA256. [Readback-verified 9,575-byte exact source/JUnit/evidence Drive ZIP](https://drive.google.com/file/d/1rdLfvufpz_YG6kTGtOqXMETBxJbxZcMn/view?usp=drivesdk).

## Full B803 release gate — independent identity-level comparison

- Final exact-head [**full CI #40244904587**](https://github.com/AldinAga/ald1n-project/actions/runs/40244904587) still **FAIL / RELEASE HOLD**, no suppressed test: immutable baseline **451 tests / 44 errors+failures** (38 failures, 6 errors, 4 warnings), candidate **666 tests / 98 errors+failures** (89 failures, 9 errors), `NEW_FAILURES=56 WHOLE_SUITE_GREEN=NO`.
- Compared each of 56 `NEW_FAILURE` identity strings against [Report860 B803 run #40225095973](https://github.com/AldinAga/ald1n-project/actions/runs/40225095973): **56/56 identical; 0 added, 0 removed**. The one intermediate static-contract failure disappeared with the original unit test unchanged. [Final full original 666-case JUnit, baseline/manifest/logs Drive ZIP](https://drive.google.com/file/d/1rJ72iO0jmtG1uC8MsURVdYco57KSzQ2G/view?usp=drivesdk), original bytes 369,697. Historical [Issue104](https://github.com/AldinAga/ald1n-project/issues/104) still OPEN; do not reinterpret 12 scoped GREEN as 368-item OWASP, whole-suite green or authorized release.

## Acceptance boundaries / next steps

- M11 remains **OPEN**: new source checks deny stale in-memory eligibility at `OperationalNotificationService::send`, but separate queued email/mobile-push **provider dispatch-time** consent, recipient opt-out/revocation race, dedupe and host/owner UAT still require independent evidence. Periodic `app:order-email-dispatch` and `app:mobile-push-dispatch` are present in `routes/console.php`; this batch does not certify them as safely enforcing late opt-outs.
- Other CMS open milestones [M08–M15](https://drive.google.com/file/d/1MvF9AJ_vt0hsD3JvExN3N89I5EQoxpz9/view?usp=drivesdk): M08 SuperAdmin operations, M09 animated Liquid Glass, M10 verified-payment warranty, M12 first buyer panel shipment need final accepted behavior/host evidence; M13 catalog, M14 activated-Kupac checkout (old guest-success tests conflict with later policy), M15 owned receipt/tracking/document follow-up are Storefront release gates. 368 OWASP WSTG4.2+ASVS5 L2 controls remain **0/368 independently accepted**; source-only Draft PR93–111, CloudLinux Storefront production **OFF** at B798, browser/merchant/legal/backup/real host gate not closed.
- Android M16: PR82 native/debug AAB+16KiB ELF/zipalign/emulator **technical PASS** previously [#40192813557](https://github.com/AldinAga/ald1n-project/actions/runs/40192813557), but signed EAS production AAB with remote versionCode **greater than rejected 27**, signer audit, physical 16KiB device QA and Play acceptance remain **HOLD**. SDK58/M17 deferred. No Android build/OTA/Play mutation in this batch. Final large inode cleanup [Issue95](https://github.com/AldinAga/ald1n-project/issues/95) deferred until safe release, not cancelled.
- Owner formal scope **unchanged**: **7/17 = 41.2% program, CMS 7/15 = 46.7%, initial UI 6/6 = 100%**. Source/CI improvements do not count as actual production/owner acceptance.

PR111_HEAD=3acc37e90332b0d8fde659503c5cc1a8b5fcc6aa
M11_VALID_RED=7_OF_9_FAILING_NO_TEST_ERRORS
M11_FINAL_GREEN=12_OF_12_REAL_PHP84_MARIADB101119_51_ASSERTIONS
M11_ORIGINAL_SOURCE_UNIT_TESTS=3_OF_3_UNCHANGED_PASS
B803_BASELINE=451_CASES_44_FAILURES_ERRORS
B803_CANDIDATE=666_CASES_98_FAILURES_ERRORS
B803_FAILURE_IDENTITIES=56_EXACT_UNCHANGED_VS_REPORT860
B803_ADDITIONAL_REGRESSIONS=0
B803_FULL_SUITE=FAIL_HOLD
M11_FORMAL_ACCEPTANCE=OPEN
STOREFRONT_PRODUCTION=OFF
ANDROID_M16_SIGNED_RELEASE=HOLD
CMS_ACCEPTED=7_OF_15_46_7_PERCENT
PROGRAM_ACCEPTED=7_OF_17_41_2_PERCENT
OWNER_SSH_REQUIRED=NO
PRODUCTION_MUTATION=NONE
