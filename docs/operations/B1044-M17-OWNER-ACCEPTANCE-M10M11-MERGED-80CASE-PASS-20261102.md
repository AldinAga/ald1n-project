# B1044 — M17 owner acceptance and exact merged-main M10/M11 80-case CI PASS (2026-11-02)

## Authenticated GitHub and owner decision

- Before B1044 GitHub main was `92dbba6b11ca4002ada72c98c8d8ad405be0476c`; CI run source `995549259da67664ed316c194c055f8a19778fa8`.
- The owner said "Prošlo sve" following M17 Play-device functional checks and explicitly ordered conserving native builds.
- [M17 Issue124](https://github.com/AldinAga/ald1n-project/issues/124) was closed **as owner-accepted functional PASS**; see [owner acceptance record](https://github.com/AldinAga/ald1n-project/issues/124#issuecomment-6585783961). This is not an independently captured physical `getconf PAGE_SIZE` output or Play rollout.
- Previously authenticated [B1041 one-time EAS AAB](https://github.com/AldinAga/ald1n-project/actions/runs/41461875485) + [B1042 actual signed 16KiB AAB binary audit](https://github.com/AldinAga/ald1n-project/actions/runs/41478237879) PASS. EAS ID `6ce5565f-6cd9-4107-8d8c-ce5a3ffd468d`, app `com.ald1n.mobile`, version `1.0.0`, **versionCode 29**, source `6adb3b440b8cfdc068b142a98c418d9d91487f64`, declared production channel and runtime `1.0.0-m17-sdk58-play2`. Signed AAB SHA256 `aba6ab1d1c66bd29b8167e7e166bbc03d8ff51e031b203bdb48a65176584753d`; ELF/ZIP/bundletool/default split/standalone APKs all passed. [Original receipt](https://drive.google.com/file/d/1JMLc4UIwLPQXfmx2BuulyGGhDcLWd7vW/view).
- M17 functional owner acceptance updates the ledger to **12/17** total, **10/15** CMS, **6/6** initial UI; **not** 13/17 because M10/M11 remain open.

## No repeat of completed M10/M11 deployment

- PR113 and PR115 were legitimately **merged by owner** on 2026-10-28 (merge commits `12367dd0ac41025de9846c6533285a02a1eea4fa` and `807f4bc855d1efe3df6d89b9d034e3b113077f2d`).
- [Original B962 real CloudLinux release](https://drive.google.com/file/d/1bjp-Sr2Y9U7riqtH4B1ORfbgTyqA27Sm/view) **PASS**: exact 12 PHP/config runtime files deployed, one additive warranty recovery migration, LiteSpeed PHP8.4.27/OPcache/release HTTP probes PASS, no additional storefront, alert sending or live recovery execution. Separate B955 SQL restore and B959 private-file restore previously done. Do **not** rerun release, migrate again, or disturb protected dirty host Git checkout.

## New B1044 engineering and actual independent result

- [B1044 main workflow](https://github.com/AldinAga/ald1n-project/actions/workflows/b1044-m10-m11-merged-main-80case-qa.yml) is push-on-own-workflow-file and optionally manual; it never dispatches EAS/Play/OTA, touches CMS production DB, or contacts actual external SMTP/Expo.
- Initial two workflow definition attempts `41506336690` and `41506510800` failed YAML validation **before jobs** (misnested Mailpit service and double-quoted GitHub `if`). Corrected in follow-up commits. Third run `41506571369` passed YAML and source checkout but failed pretest on a mistaken existence assertion for a historical B815 fixture filename that **does not exist on current main**. Fixed only the workflow guard using a no-change-to-CMS-tests diff condition; **no denied B815 fixture was edited, skipped or bypassed**.
- Final actual [GitHub run41506876578](https://github.com/AldinAga/ald1n-project/actions/runs/41506876578), head commit **`995549259da67664ed316c194c055f8a19778fa8`**: **COMPLETED SUCCESS**. Real PHP8.4/MariaDB10.11.19 / disposable local Mailpit:
  - 69/69 original payment/warranty/push/MailChannel tests, **428 assertions**; original pinned 69 identity manifest SHA256 `01fdcebe4e53839cecf64c6275c5a0c0715e13770fc45cb958cb30217ea187dc` matched.
  - 7/7 warranty recovery tests, **104 assertions**.
  - 4/4 real MariaDB two-worker and isolated Mailpit SMTP integration, **54 assertions**.
  - **Total 80/80 distinct authentic tests, 586 assertions, zero fails/errors/skips; source and JUnit identity guards PASS**.
- The original [GitHub artifact13171476390](https://github.com/AldinAga/ald1n-project/actions/runs/41506876578) was downloaded and independently ZIP-inspected: 69+7+4 test identities, zero errors/skips. ZIP SHA256 `9597b82128370922e6f3f78cd09392dc34c60c8307123e8e3d9cc68eeceb92dc`. Original archived on [Google Drive](https://drive.google.com/file/d/17e0bsZwcbhKOlzMvpDN1CsPmGbK5Ciee/view).
- [Detailed B1044 owner Drive QA](https://docs.google.com/document/d/11PPRJgbgUz6rShcWnwlw5b58PuF2LsCDlRjjU_ngXRc/edit) and [GitHub workflow YAML source copy on Drive](https://docs.google.com/document/d/18S8zS6suN5jlHzXmE1iXrmfarRf2rqZmVfrsmvvy8Bc/edit) are archived. GitHub canonical executable YAML is linked above.
- [Issue114](https://github.com/AldinAga/ald1n-project/issues/114#issuecomment-6585998987) and [Issue112](https://github.com/AldinAga/ald1n-project/issues/112#issuecomment-6586000222) updated with verified B1044 results.

## Boundaries and queue

**M10/M11 merged-source and actual CloudLinux deployment gates are closed**, but formal M10/M11 business acceptance remains open until independently authorized external SMTP/Expo and safe operator delivery UAT (recovery execution and alerts remain default OFF). Do not invent a real provider receipt from Mailpit tests. Public storefront **OFF**; Issue104 original B803 **56** historic failures unwaived, safety-denied B815 fixture unchanged. M13–M15 remain open pending independent security/release gates. No new EAS build, APK/AAB, OTA, Play, host deployment, production migration or customer mutation was made in B1044. Future native builds should be consolidated and explicitly owner-approved, not repeated solely for source/CI documentation.

GitHub `docs/operations/000-LATEST.md` is stale from B1032 due previous write safety denial; this new report **does not represent an update of that file**. Canonical Google Drive runbook was reviewed; the independent B1044 Drive record above is a supplement.
