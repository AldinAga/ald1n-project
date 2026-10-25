# B876 — stable retail customer role ID under MariaDB rollback seeding

Date: 2026-10-25. Scoped, non-destructive source correction added to child Draft PR102. No production DB migration, user foreign-key reassignment, checkout policy change or CloudLinux execution.

## Reproduced real defect in complete 598-case CI

The current unmodified B803 full baseline/candidate [CI #40208344978](https://github.com/AldinAga/ald1n-project/actions/runs/40208344978) on exact PR102 head `de24093161829614ac06877fdd067b8d920309f1` recorded **baseline 451 / 44 failing identities** and **candidate 598 / 102 failing identities**, hence **60 new failures** versus B803's 56 previous new failures (parent PR100 585 / 98). Strict old/current identity comparison isolates exactly **four additional failing identities**, all under `WarrantyTerms797Test`.

The preserved JUnit `candidate-junit.xml` contains the same concrete **MariaDB SQLSTATE[22003], numeric out-of-range for `roles.id`** in each of those four tests while `CoreAccessSeeder.php:22` attempts `insert into roles (slug,name) values (customer,Kupac)`. This is NOT evidence of warranty implementation regression or CSP behavioral change. In the original schema migration `2026_07_21_000001_create_access_tables.php`, `roles.id` is `tinyIncrements` (**unsigned TINYINT, max 255**) and `users.role_id` is `unsignedTinyInteger`. In `CoreAccessSeeder`, three original role IDs are explicit 1/2/3, but `updateOrInsert(['slug'=>'customer'])` without an explicit ID causes MySQL to consume an AUTO_INCREMENT sequence value when the transaction rolls back between `RefreshDatabase` tests. Merely adding 13 normal new tests crossed the finite ID ceiling in the complete suite. No role/test was skipped.

These four new failures must remain explicit HOLD until fixed and retested across all 598 tests; the 65/65 targeted PHP and Chromium PASS does not waive them.

## Small, real application correction

Replace only the seeder's ambiguous dynamic customer-role insertion with:
1. Look up existing `roles.slug='customer'`; if it exists, update only its display name, **never its PK**. Existing production customer IDs and `users.role_id` foreign keys are untouched.
2. If absent, read all occupied role IDs and select the **first truly available explicit ID in 4..255** (legacy 1–3 reserved); insert the new role with that ID. This does not increment the MySQL auto-increment counter on recurring rollbacks and never overwrites an unrelated role occupying ID4.
3. Fail closed with `B876_ROLE_ID_SPACE_EXHAUSTED` if all possible remaining IDs are in use. Never silently reassign users, widen a production FK, delete a role or change an existing applied migration.

Three independent real MariaDB negative/invariant tests confirm: repeated seeds keep ID and next auto-increment stable; an existing customer role ID12 with a related user retains its primary/foreign key; a custom role occupying ID4 is preserved and the new buyer role receives ID5. The expected bounded CI total grows from **65 to 68** new-plus-unchanged cases; the original B836/B843/B847/B866 fixtures and protected B805/B809/B815 stay unmodified.

## Required complete source proof and constraints

Re-run exact-source B872–B876 isolated 68-case HTTP feature suite and real Chromium, plus original B803 full baseline/candidate comparator: candidate should have **601** total test identities (598+3). Only a completed new CI may determine whether the **4 newly observed WarrantyTerms797Test failures are gone** and whether old 56 remain or shift. Never infer PASS from a scoped feature suite. Keep all JUnit XML and source SHA/provenance. Any remaining new regression identity blocks merging/release.

No production migration or role reassignment is authorized by this isolated fix. Public storefront OFF and all 368 OWASP item-level controls still UNASSESSED/NOT_RUN. Android M16 still HOLD for signed EAS AAB/device/Play, SDK58 deferred. Formal owner milestone ledger remains **7/17=41.2%** overall, **7/15=46.7%** CMS, **6/6=100%** initial UI.
