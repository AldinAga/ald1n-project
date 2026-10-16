# CURRENT AUTHORITY — Product Editor UX2 Android OTA and EAS CLI25 (2026-10-16)

- Latest verified Android production OTA source **904c1db0f4213ff2e3eea3757869f86556d55378**, merged PR #10. CLI-only config/docs commits after this source SHA do **not** imply a new OTA.
- Production Android OTA group **37ae8af0-b950-4549-8476-c960e1350866**, update **01a1436c-40d0-7f00-a22d-460d74dcf12c**, independently exact-group and GraphQL latest verified (Report682, 2026-10-16). Isolated candidate group **1156d58f-af30-4a7c-a4c2-91ccfb9385ab**, Android update **01a1436b-b502-7efc-8803-fc208a18219c** (Report681).
- Build25 appVersion **1.0.0**, versionCode **25**, runtimeVersion **1.0.0-build17**, Android `com.ald1n.mobile`, EAS profile/channel `production`. No new native build, OTA or Google Play action for CLI pin migration.
- Last source QA for production Product Editor: full Release25 **140/140 PASS** under real JDK17, TypeScript PASS, Mobile validator 0 FAIL; [CI 38746486532](https://github.com/AldinAga/ald1n-project/actions/runs/38746486532).
- Canonical EAS CLI is `25.0.0` for the updated `eas.json` and `AGENTS.md`; [read-only Node22.16 CLI25 compatibility CI 38862601089](https://github.com/AldinAga/ald1n-project/actions/runs/38862601089) PASS. **Authenticated CLI25 host exact-group smoke PASS** (owner-supplied Report683, 2026-10-16; exact production group and Android update verified, no source/build/OTA/Play mutation). CLI25 activation did **not** recheck production branch latest GraphQL mapping; do that fresh before any new OTA promotion. Do not globally install EAS CLI.
- Android physical Google Play UAT of Product Create/Edit (two cold starts, editor sections, retention, validation jump, idempotent saves) and compact navigation appearance remains **HOLD_PENDING_PHYSICAL_PLAY_DEVICE**. CLI or OTA test evidence is not physical UAT.
- Host checkout last reported `b5b942e645d28d3ed5f248eaae4180ebe9a54ee6` is stale and dirty: do not run destructive reset, checkout, pull or clean. Reports681/682 were uploaded by the owner from canonical `docs/operations`; preserve their evidence and archive through normal SHA/sequence gates.
- Batch684 iOS readiness (in-progress source): tracked `GoogleService-Info.plist` has preview Bundle ID `com.ald1n.mobile.preview`, matching iOS **preview** only. iOS **production** expects `com.ald1n.mobile`, so first iOS production build is **HOLD_GOOGLE_PLIST_BUNDLE_MISMATCH** until proper Firebase iOS credentials are supplied and separately gated. No signing, TestFlight, iOS build or device UAT has been attested. The read-only preflight and first-iOS QA belong to PR #13 / Batch684; do not infer merge/CI success before actual results.
- Liquid Glass is the LAST planned project phase, after functional QA and first iOS build.

## Historical Workspace V2 production OTA / Batch632 — NOT CURRENT AUTHORITY

# CURRENT AUTHORITY — Workspace V2 production OTA / Batch632 (2026-10-12)

- GitHub main source deployed as OTA: `52850d185d2c26d0a72a26d64520528714fe2f01`. For current main HEAD reverify live GitHub refs; later documentation commits do not imply a new OTA.
- Build25 appVersion `1.0.0`, versionCode `25`, runtimeVersion `1.0.0-build17`; production profile/channel, Android `com.ald1n.mobile`.
- Workspace V2 source quality PASS (TypeScript, Mobile 0 FAIL, 52/52 tests, Expo compatibility and Android export).
- Candidate OTA group `8f27e122-317b-4db1-93a6-9bb95653de26`; production exact-group-verified `27dd4b5d-63b3-4902-9b0b-5f289076f0e6` (2026-10-12 00:55:37Z), Android runtime `1.0.0-build17`.
- Previous production group `3488c7cc-ac63-472d-bd7c-27e5abc677bd` for controlled recovery assessment only. Physical Android UAT: owner-attested PASS; full multi-device telemetry not supplied.
- Canonical EAS CLI is `24.8.0` in eas.json and current AGENTS.md; CLI `24.12.1` was advertised as newer but not verified compatible, so not approved for upgrade.
- Production CMS host last observed checkout `b5b942e6...` with 97 dirty/untracked entries. Not verified equal to GitHub; do not run destructive sync.
- Order #134, archived notification deep links, hosting parity, 320px/a11y and broad device QA: follow-on scoped gates, NOT implicitly PASS.
- Batch632 Order #134 source candidate is **DRAFT PR #4** (branch `audit/batch632-order-notification-route-recovery-20261012`, HEAD `25d29162c01938b7a58abe6914fee403beb72879`): real-source PHP routing tests RED→GREEN, combined 14/14 PASS; five-file CMS-only scope. **NOT merged, NOT deployed, no production resolution attested.** Full Laravel/static/Android/HTTP gates remain.
- Reports633/634 (owner-executed, 2026-10-12): PR4 read-only QA **PASS** (five-file PHP lint, actual-source 14/14, CMS static 983/983, no production mutations); DB-backed Laravel, live Order134 and Android UAT still HOLD. CLI lab **FAIL** (Release25 116/125 PASS, two stale 24.7 vs 24.8 pin assertions, seven signature tests blocked by Java `jar` fixture setup). Latest EAS 24.12.1 launches/helps on Node22, but **not approved** for production.
- **PR #5 MERGED** https://github.com/AldinAga/ald1n-project/pull/5: owner-authorized SHA-guarded squash merge `e461d6d2d6657faf764c5910bb2a0f5efb23e4db`; reviewed candidate `0efb11e91a557dd478d8501158076dd82871c2b8`, six changed Mobile files all main blobs verified byte-identical. Exact-source CI [38236158464](https://github.com/AldinAga/ald1n-project/actions/runs/38236158464): full Release25 **125/125 PASS**, real JDK17 **7/7 PASS**, TypeScript PASS, Mobile validator **Ukupno FAIL: 0**. No production build, CMS deployment, OTA or Play action; EAS stays `24.8.0`.
- Canonical closure report: `docs/operations/632-WORKSPACE-V2-BUILD25-PRODUCTION-OTA-CLOSURE-20261012.md`.
- **Report646 Draft PR7 404 UX**: [source/CI audit](https://github.com/AldinAga/ald1n-project/blob/main/docs/operations/646-BATCH646-MOBILE-ORDER-UNAVAILABLE-DRAFT-PR7-CI-20261013.md), four scoped Mobile files, [Draft PR #7](https://github.com/AldinAga/ald1n-project/pull/7) head `a08322a62d7a62a95464ba0345325a73ffc78af2`. Neutral non-disclosing 404 message and return to admin/user order list; preserve non404 error behavior and stop futile 404 retries. Exact-source GitHub Actions [run 38323541320](https://github.com/AldinAga/ald1n-project/actions/runs/38323541320): **4/4 behavior PASS, TypeScript PASS, Mobile validator 0 FAIL, full Release25 125/125 PASS with JDK17**, immutable source, no artifacts. **PR4 and PR7 remain DRAFT, NOT MERGED, NOT DEPLOYED**. No new build/OTA/Play/CMS production mutations. Future authenticated HTTP matrix requires separately approved nonpurged active order/test roles; physical device cold/warm UAT remains HOLD.
- **Report645 LIVE Order134 PASS**: [raw byte-exact archive](https://github.com/AldinAga/ald1n-project/blob/main/docs/operations/645-BATCH645-LIVE-ORDER134-READONLY-DIAG-20261013-013543.md), SHA256 `7f13558d9061d10b8eb45c46d0df1191cc9a5fa6d2d982a365b6d2cb41a657ac`, Git blob `7f6659ab9539e872a88dbb707ef8ee868ea892c6`. Live Order #134 exists but **CANCELLED, ARCHIVED and PURGED**, so normal Laravel order detail 404 is expected regardless of correct admin/user route, NOT actual HTTP proof. 12 inbox records (2 historical admin legacy, 10 user), 2 push; host NotificationResource/OperationalNotificationService matched MAIN not PR4. Source/index/DB unchanged, real auth HTTP and physical Android UAT still HOLD.
- **Report644 PR4 MySQL QA**: [source audit and baseline report](https://github.com/AldinAga/ald1n-project/blob/main/docs/operations/644-BATCH632-PR4-ISOLATED-LARAVEL-MYSQL-BASELINE-20261012.md). Candidate `25d29162c01938b7a58abe6914fee403beb72879` run [38257422989](https://github.com/AldinAga/ald1n-project/actions/runs/38257422989): all **3 new Laravel MySQL notification tests PASS, 12 assertions**; existing source notification smoke **14/14 PASS**. Expanded 11-test run [38256868139](https://github.com/AldinAga/ald1n-project/actions/runs/38256868139) still **8/11 PASS** from three preexisting mobile-device/token/read-flag failures, independently reproduced **on unpatched main** (5/8 original tests PASS, same 3 FAIL) by baseline audit [38257664993](https://github.com/AldinAga/ald1n-project/actions/runs/38257664993). Baseline CI success means *expected failures were reproduced*, not 8/8 PASS. PR4 remains **DRAFT / NOT MERGED / NOT DEPLOYED**. Live Order134 permission/archival/HTTP matrix and Android cold-start tests remain HOLD. No prod CMS/DB/build/OTA/Play mutation.
- **Restore drill deferred by owner (2026-10-12):** Report644 isolated restore preflight will NOT be run in this sequence. Backup IDs 151/150 remain confirmed by first-party file/manifest/SQL and backup_runs checks (Reports642/643), but neither was restored to an isolated database. **RESTORE_READY remains HOLD; deletion of any backup or archived evidence remains blocked.** Progress instead to independent **Draft PR4 Order134 Laravel MySQL QA** on exact candidate `25d29162c01938b7a58abe6914fee403beb72879`, using disposable CI test DB, no production database access/merge/deployment/build/OTA/Play. Existing PR4 14/14 source routing and 983/983 static gates are already PASS; do not repeat hosting backup or restore work.
- Batch632 **Report643 PASS**, complete read-only CMS `backup_runs` authority: backup IDs **151** and **150** are distinct completed rows linked to both previously integrity-verified backup directories. First-party `php artisan app:backup-verify --run` **2/2 RC0 PASS** including record-size agreement, SQL/attachments/version and zero warnings; no production or backup mutations. Original [byte-exact Report643 GitHub archive](https://github.com/AldinAga/ald1n-project/blob/main/docs/operations/643-BATCH632-BACKUP-RUNS-READONLY-AUTHORITY-20261012-151330.md), SHA256 `b87df4a76ec46eaed47604bf1c950e2c97a9be09ef0481d81c9f44448bac5235`, Git blob `4ca8270e55471b5c5d469549fd31d596f1755c81`. **Neither candidate has yet passed an actual restore**; two restore-ready keepers remain HOLD and the old third backup is protected. Next non-mutating operator `incoming/ald1n-b632-isolated-restore-preflight-v1.sh` SHA256 `30a73ef3580313807828a5835c0ea1b7832a3e35fa603708b5ff010087f08149` produces Report644, requires a separately prepared empty MySQL restore test DB with dedicated scoped user and privately stored credential file. It verifies isolation/readiness only, **does not import any SQL or write to production**. CloudLinux-safe Bash/PHP local QA PASS. 82 unarchived operations reports and staged Git evidence remain protected.
- Batch632 **Report641 FAIL** before either real backup verification (CloudLinux `/dev/fd/63`, prohibited Bash process substitution); original [Report641](https://github.com/AldinAga/ald1n-project/blob/main/docs/operations/641-BATCH632-RESTORE-CANDIDATE-VERIFY-ARCHIVE-GATE-20261012-133542.md) byte-exact SHA-256 `9a31fb0fca57d2ca3760b59c96c628b2360893090cc2e3c05bd3169ad6ff932d`, blob `07eecdeb63950c79d1b56f177844c1ecdf785f88`; audited RC1, **not evidence of bad backups**. Previously used CloudLinux-safe operator `incoming/ald1n-b632-backup-verify-archive-gate-v2.sh` SHA-256 `55dcd88a10421a065e09b4dcac2006036685c468b21d6607962f99fb961264f8`, used Report641 SHA+Git blob and created original **Report642** exclusively under `docs/operations`. Portable NUL-file loop (no process substitution); local shell/static gate PASS and **3/3 isolated control-flow fixtures PASS**. On real hosting execution and first-party file verification: **PASS as recorded in Report642**. Preserves all 82 unarchived reports, staged index/Report549, two candidate backups and old third directory. No backup delete/restore/creation, deployment, OTA, native build or Play action.
- Batch632 host **Report640 PASS**: immutable archived report SHA256 `3e0134b4d8174782ecd30751a1b202fdcb43d0a3883e851fb89bf7f5813d4ef9`, [byte-exact Report640](https://github.com/AldinAga/ald1n-project/blob/main/docs/operations/640-BATCH632-ARCHIVE-PARITY-BACKUP-CONFIG-20261012-132203.md), seven CMS blobs equal main, `.htaccess` approved file and diff checks PASS. 82 historical operations reports not archived; cached backup directory contains 2 manifest candidates and 1 old non-manifest entry; real backup verification NOT reached in failed Report641. Historic v1 script must not be re-run; new action is Report642 v2 as above.
- Batch632 host **Report639 PASS**: source read-only inventory, script `incoming/ald1n-b632-host-inventory-v1.sh`, 10 unstaged tracked/2 staged/89 untracked paths across the entire repo (not comparable directly with Report638's app-only counts). GitHub byte-exact [Report639 archive](https://github.com/AldinAga/ald1n-project/blob/main/docs/operations/639-BATCH632-HOST-DRIFT-BACKUP-INVENTORY-20261012-130006.md) SHA256 `609240b229574ae0b78c607a892e3753bc8740c0633558afdd3b5dea74818879`, blob `c4f99800d5e288523b666276a40570cfd0f27bce`. Seven CMS presentation/CSS paths match main exactly; .htaccess known file-SHA diff gate pending; Report548 differs, staged 000-LATEST and Report549 require no-reset protection. Of untracked, 82 operations reports are missing at matching GitHub main paths, one CMS smoke source exists on main, three error_log gz protected, three are current report/protected. **Do not delete unarchived reports or staged files.** Default backup folder empty but actual runtime BACKUP_PATH and backup_runs unverified; no verified two-backup retention gate. **Next READ ONLY:** `incoming/ald1n-b632-host-archive-backup-map-v1.sh` SHA256 `6891312b8f1138c8e21fa642c1548e977bd612d56a4a3925bb2243af87b06f85`, creates Report640 in canonical `docs/operations` after Report639 archive/sequence preflight. No CMS/DB/build/OTA/Play changes.
- Batch632 host **Report638 PASS**, operator SHA-256 `1c8aeee139e28ee02a3575d7932f042b7d6ce0a05a2cfc4d25f6b4e91bdb0932`, [GitHub byte-exact archive](https://github.com/AldinAga/ald1n-project/blob/main/docs/operations/638-BATCH632-SAFE-HOST-HOUSEKEEPING-20261012-125100.md): removed four GitHub-archived local untracked reports and six SHA-matched obsolete scripts; Report637 preserved; no app/code/DB/build/OTA/Play mutations. Remaining host app inventory: **8 unstaged tracked / 0 staged / 1 untracked**; not yet classified for deletion. Backup count/restore-readiness HOLD. Next read-only script `incoming/ald1n-b632-host-inventory-v1.sh`, SHA256 `6b06979341fbfea231b9771e325c666d6cfda278bd011c4f56ccc3c35c3be9d8`, creates next numbered Report639 in `docs/operations` only after Report638 hash+GitHub/archive+sequence gates. Do NOT run legacy `scripts/maintenance/ald1n-project-residual-cleanup.sh --apply` (outdated retention/output paths).
- **PR #6 cleanup MERGED** https://github.com/AldinAga/ald1n-project/pull/6: SHA-guarded squash `fde5aa9ec88c9824d603476ef130c9d6943e3d6a` removed only empty legacy v0.9 lock and misleading v0.3/v0.4 `PROJECT-STATUS.md`. Isolated QA [38237326270](https://github.com/AldinAga/ald1n-project/actions/runs/38237326270): no runtime references, TypeScript PASS, validator 0 FAIL, source immutable. Both recoverable via Git history; no CMS/host edits.
- **Hosting cleanup next, not yet executed**: canonical `incoming/ald1n-b632-safe-host-housekeeping-v1.sh` SHA256 `021121aab64681c1ed205dcec21e7bb6e7992c5cebd5b6a3a9b3826f7cd3173d`, outputs numbered Report638 under `docs/operations` if current max remains Report637. Deletes ONLY exact hash-attested obsolete recovery scripts and locally untracked Reports633–636 whose GitHub archives match; keeps Report637, PR4 v2 script, tracked and unknown files. No `git clean`, backups, uploads, secrets, DB, production apps or Android build. Wider hosting cleanup requires fresh manifest and two verified restore-ready backups.
- **Reports633–637 are byte-exact archived to GitHub `docs/operations/`**, with Git blob SHA attested against uploaded host evidence; Report637 SHA-256 `010458e9515ef42401ac33e6053e67770983e87656986dc9890456e876014572`. Original FAIL/HOLD evidence retained unchanged; do not rewrite historical reports or interpret old failure status as active release state. Local report retention cleanup is a separate gated operation.
- **Permanent operator artifact policy (2026-10-12, AGENTS.md §1A):** all delivered scripts must be uploaded/executed from `/home/icaffeco/ald1n-project/incoming`; all newly generated numbered batch execution reports/terminal logs must be created in `/home/icaffeco/ald1n-project/docs/operations`. Preserve sequential report numbering and GitHub archival gate; temporary scratch stays outside the worktree. Reports633 and 634 verify execution of the two `-v2.sh` scripts from canonical incoming; subsequent recovery v3/v4 execution is attested by Reports635/636. v5 also executed on host (Report637); Reports633–637 are byte-exact archived on GitHub. Continue from verified gates instead of repeating recovery scripts.
- Batch632 layout source parity: GitHub commit `8064ff9a1bba1cb74856f2e3732094dcfe524d84`, host/source SHA-256 `324dbae89961d3b48d8cacf062a15060d3af380a681027c7b25feb0d717b5737` (cmp PASS). Laravel anonymous x-icon, isolated Blade compile and compiled-PHP syntax PASS; Workspace V2 tests 52/52 PASS. Full authenticated HTTP render and global host/source parity remain unverified.
- No new Android build, Google Play submit, production CMS mutation or OTA republish is authorized by Batch632 documentation.

---

## Historical Build25 pre-OTA snapshot — NOT CURRENT AUTHORITY

# Ald1n Operations - CURRENT Build25 Authority (2026-10-09)

This header is the up-to-date **source and gate index at audit preparation time**.
Reverify live refs before any new batch. This file is NOT EAS, Play, billing or production source authority.

- GitHub `main` at Report575: `b5b942e645d28d3ed5f248eaae4180ebe9a54ee6` (does not contain pending Build25 audit work).
- Last verified audit parent: `7c4e4ef435b46a65bc0e0db57dd74509a62a5e89` (Report574 / Report575).
- Batch576 remediation candidate branch: `audit/build25-report575-targeted-fixes-20261009`; obtain its exact HEAD from GitHub and Report576 after push, never infer it here.
- Report575: `575-BATCH541-BUILD25-DEEP-FORENSIC-AUDIT-20261009-123747-575124.md` (read-only PASS; 2 code/documentation findings; 7 external blockers).
- App: `Ald1n CMS`; version `1.0.0`; runtimeVersion `1.0.0-build17`; Android applicationId `com.ald1n.mobile`.
- EAS production profile/channel: `production` / `production`; no production build/submit authorized.
- EAS_CLI=eas-cli@24.7.0 (from AGENTS.md and eas.json).
- REMOTE_ANDROID_VERSIONCODE=UNATTESTED. Read exact EAS remote authority at a separately authorized read-only gate; do NOT assume 23 or 24.
- BUILD25_AUTHORIZED=NO.
- PROGRESS=75_PERCENT_ESTIMATE; native CI audit run 37932584905 PASSED; no production release gate has passed.
- Native CI on candidate `dc775bd39af46764896ee5e204f22122e3018c58`: PASS, GitHub Actions run 37932584905 (one run, no artifact retained). Trigger branch is at the same SHA. New release-controller-only changes require new source approval before any additional CI.
- Direct production CLI and legacy submit are fail-closed. Automatic AAB/APK upload remains disabled.
- Open: EAS remote version command exit 1 (Report577), exact remote project identity (CLI version JSON does not carry it), public Git history/GitHub secret scan attestation, upload signing certificate, retained signed AAB/16 KB/ELF, devices, A03-A14 acceptance, source merge and owner build/Play authorization.
- Reports: `docs/operations/573-BATCH539-BUILD25-DEEP-OFFLINE-CONVERGENCE-SCOPE.md`, `docs/operations/574-BATCH540-BUILD25-CI-COST-SAFETY-SCOPE.md`, and `docs/operations/576-BATCH542-BUILD25-REPORT575-TARGETED-FIX-SCOPE.md`.

- Report577: read-only PASS, EAS version:get exited 1 and suppressed its raw cause, so remote versionCode is still not attested.
- Batch579: pinned eas-cli v24.7.0 source confirms version:get returns a decimal string; parser remediation is isolated, EAS auth/identity remain independent gates.

---

## Archived Build23/Build24 historical snapshot - NOT CURRENT

Obsolete historical entries below are not operational instructions.
They preserve earlier reports and commands strictly for forensic reference.
Ignore any historical next-versionCode, old EAS pin or Build23/24 authorization language below.

### Ald1n Operations - Historical source authority from Build23/24

- Canonical directory: `docs/operations`
- Archive policy: **append-only** for numbered reports.
- Current application version: **1.0.0**
- Current runtimeVersion: **1.0.0-build17**
- Current source authority after Batch525R2: `7066a7aee04a7bdb2c37e862905f74306737e78b`
- Last successful Android production build: **Build22 / versionCode 22**
- Latest attempted Android production build: **Build23 / versionCode 23 — ERRORED during Gradle configuration; no AAB and no Google Play submission**
- EAS remote Android versionCode: **23**; next possible versionCode is **24**.
- Build profile / channel: **production / production**
- Build23: **ERRORED** — versionCode 23 was consumed and the build failed before AAB creation because the local Restore Credentials Expo module lacked `android.defaultConfig.versionName`.
- Build24: **DEFERRED / NOT AUTHORIZED**. AutoSubmit is allowed again, but exactly one Build24 production AutoSubmit requires separate explicit owner authorization.
- Google Play release notes: **required as copy/paste text at Build23 completion**.
- Batch520: **PASS** — subagent shipment tracking visibility + Push/E-mail/Both preference.
- Batch521: **PASS_ACTIVATED via Batch522** — Android Zero-Tap Restore Credentials implementation is committed, migrated and production-enabled after exact Play App Signing SHA-256 + live Digital Asset Links verification.
- Next gate: **explicit owner authorization for exactly one Build24 production build with AutoSubmit; Build24 AAB must pass bundletool/ELF 16 KB verification before final acceptance.**

## Batch524 / Batch525 / Batch525R / Batch525R2 - Build23 Failure + Gradle Source Fix

- Build23 versionCode **23** is consumed with status **ERRORED**; no AAB was produced and no Google Play submission occurred.
- Exact EAS Gradle root cause: local project `:ald1n-restore-credentials` lacked `android.defaultConfig.versionName`.
- Batch525 stopped before mutation on a stale diagnostic-file dependency. Batch525R re-proved the exact root cause from the live EAS log but stopped before mutation because its worktree gate did not classify known historical operations residue.
- Batch525R2 binds Batch525R by exact report SHA, preserves the known historical residue read-only, adds only `defaultConfig { versionCode 1; versionName "1.0.0" }` to the Restore Credentials Expo module, and passes Mobile/Expo/CMS/OpenAPI plus isolated Android prebuild gates.
- Source fix commit: `7066a7aee04a7bdb2c37e862905f74306737e78b`.
- EAS remote Android versionCode remains **23**; the next possible versionCode is **24**.
- AutoSubmit is allowed again, but Build24 remains not authorized until a separate explicit owner approval.
- Build24 AAB still requires mandatory bundletool/ELF 16 KB acceptance after a successful build.
- Report: `docs/operations/548-BATCH525R2-BUILD23-GRADLE-VERSIONNAME-FIX-RECOVERY-20261007-100059.md`.

## Batch523 / Batch523R / Batch523R2 - Fresh Pre-Build23 Release Readiness

- Status: **PASS_READY_FOR_OWNER_AUTHORIZATION via Batch523R2 recovery**
- Source commit: `8a776f8b8820f51791d990ae436c782944525eac`
- Original Batch523 generated a 937-entry CMS release manifest sorted by relative path and passed release-integrity, CMS 983/983, stable strict+render, Mobile, Expo, OpenAPI and live DAL gates.
- Original Batch523 stopped before source commit because its local Restore-options checker looked for rpId at the JSON root instead of the controller's canonical data.options envelope.
- Batch523R corrected the Restore-options checker but stopped before mutation because its replacement manifest helper accidentally sorted complete hash-prefixed lines instead of sorting by relative path, producing a different manifest SHA from the same source authority.
- Batch523R2 binds both failed reports by exact SHA, reuses the original Batch523 path-sorted generator byte-for-byte, requires the exact original 937-entry manifest SHA, validates the canonical data.options Restore contract, and commits only that manifest source change.
- Live Restore options are verified as rpId=cms.ald1n.com and userVerification=discouraged; live DAL remains bound to the exact Play App Signing SHA-256.
- EAS remote Android version remains 22; the next single production AutoSubmit build is versionCode 23.
- Build23 remains deferred until explicit owner authorization.
- Final bundletool/ELF 16 KB verification remains mandatory on the Build23 AAB before final acceptance.
- Failed original report: `docs/operations/541-BATCH523-FRESH-PRE-BUILD23-RELEASE-READINESS-20261006-222816.md`.
- Failed first recovery report: `docs/operations/542-BATCH523R-PRE-BUILD23-RELEASE-READINESS-RECOVERY-20261006-223757.md`.
- Successful recovery report: `docs/operations/543-BATCH523R2-PRE-BUILD23-RELEASE-READINESS-RECOVERY-20261006-224326.md`.

## Batch522 - Play App Signing + Digital Asset Links + Native Compliance Gate

- Status: **PASS_ACTIVATED_GATED**
- Source commit: `33316a89165c49f78215dd33e7ab03513f13f8a3`
- Exact Google Play App Signing SHA-256 was supplied from Play App Signing authority and explicitly rejected if equal to the historical EAS upload certificate.
- Production Digital Asset Links is live at `https://cms.ald1n.com/.well-known/assetlinks.json` for `com.ald1n.mobile`, including handle_all_urls and get_login_creds relations.
- Restore Credentials production config is enabled only after live HTTP 200 + application/json + exact fingerprint verification.
- Restore server origin is derived as android:apk-key-hash from the Play app-signing SHA-256.
- Native source readiness covers API 36, R8/resource shrinking, optimized ProGuard, edge-to-edge/adaptive posture, DAL metadata and SecureStore backup exclusions.
- 16 KB source/toolchain readiness is accepted, but final bundletool/ELF alignment remains an artifact gate on Build23.
- No database mutation, EAS build, EAS submit, OTA publish or Google Play mutation was performed.
- Play fingerprint recorded in public DAL authority: `1D:08:DB:67:06:C6:53:4B:18:AE:E6:D5:AA:C2:3C:B6:0B:CE:EA:28:9B:9D:AD:07:3B:E6:A1:63:5C:2E:DB:21`.
- Report: `docs/operations/540-BATCH522-PLAY-APP-SIGNING-DAL-NATIVE-COMPLIANCE-20261006-221245.md`.

## Batch521 / Batch521R / Batch521R2 / Batch521R3 / Batch521R4 / Batch521R5 - Android Zero-Tap Restore Credentials

- Status: **PASS_IMPLEMENTED_GATED via Batch521R5 recovery**
- Source commit: `42f4207ef106d8567a267bd4e99972a43a25e48f`
- Original Batch521 stopped before mutation on the Report533 post-checkpoint tracked residue.
- Batch521R normalized that exact append-only residue and then rolled back after the server rejected an unsupported Composer require flag.
- Batch521R2 proved the supported Composer path and WebAuthn 5.3.9 resolution, then rolled back after a cwd-relative source-scope inventory mismatch.
- Batch521R3 fixed source inventory and proved TDD green, CMS 983/983, Mobile typecheck/validator and zero new Composer advisories; it rolled back when Expo's live SDK57 compatibility authority advanced to a new same-day patch set.
- Batch521R4 aligned expo 57.0.27, expo-constants 57.0.21, expo-linking 57.0.12, expo-notifications 57.0.22, expo-router 57.0.25 and expo-updates 57.0.25 through canonical CloudLinux npm, proved exact source routes and all canonical quality gates, then rolled back because its native audit incorrectly required allowBackup=false.
- Batch521R5 preserves the existing Android backup posture, verifies expo-secure-store exclusions for encrypted SecureStore data when backup is enabled, and keeps Restore Credentials independent of allowBackup as required by Android guidance.
- Android Credential Manager Restore Credentials client + Laravel WebAuthn server foundation are implemented.
- Restore verification uses userVerification=discouraged to match Android passive GetRestoreCredentialOption semantics.
- Resolved web-auth/webauthn-lib is guarded at 5.3.3+ within the package's 5.3.x contract for Android apk-key-hash origin support.
- Restore keys are stored separately from future user-managed passkeys.
- Normal password and Google sign-in remain canonical and unchanged in user behavior.
- Logout and HTTP 401 clear the Restore Credential state; create flow uses cloud backup with E2EE-unavailable fallback.
- Production activation remains fail-closed until exact Google Play App Signing SHA-256, android:apk-key-hash origin and HTTPS Digital Asset Links are verified.
- Recovery report: `docs/operations/539-BATCH521R5-ZERO-TAP-RESTORE-CREDENTIALS-RECOVERY-20261006-182159.md`
- Failed Batch521R4 report: `docs/operations/538-BATCH521R4-ZERO-TAP-RESTORE-CREDENTIALS-RECOVERY-20261006-175355.md`
- Failed Batch521R3 report: `docs/operations/537-BATCH521R3-ZERO-TAP-RESTORE-CREDENTIALS-RECOVERY-20261006-155312.md`
- Failed Batch521R2 report: `docs/operations/536-BATCH521R2-ZERO-TAP-RESTORE-CREDENTIALS-RECOVERY-20261006-153616.md`
- Failed Batch521R report: `docs/operations/535-BATCH521R-ZERO-TAP-RESTORE-CREDENTIALS-RECOVERY-20261006-152558.md`
- Failed original Batch521 report: `docs/operations/534-BATCH521-ZERO-TAP-RESTORE-CREDENTIALS-20261006-143308.md`
- No EAS build, submit, OTA or Google Play action.

## Batch520 - Subagent Shipment Tracking Notifications

- Status: **PASS**
- Source commit: `dff990823507719d5e2f476523cdeeb9e15cdd5b`
- Creator/subagent sees shipment tracking prominently near the top of the customer order detail with courier, shipped time, copy and safe HTTPS tracking actions.
- Notification settings expose exactly Push, E-mail or Push + E-mail for shipment tracking alerts; no disabled/none option is introduced.
- Canonical OrderShipmentService sends the creator/subagent shipment alert using the chosen channel while keeping a persistent in-app event record.
- Push selection reuses existing device registration; email reuses existing operational notification mail channel.
- Existing supplier/operational order email flow remains separate and creator duplication is suppressed for the shipment event.
- CMS static 983/983, Mobile typecheck/validator, Expo checks and OpenAPI parity passed.
- Production migration applied only after verified backup.
- No EAS build, submit, OTA or Google Play action.

---

---

## Batch518A - APK Upload & Order Continuation

- Status: **PASS**
- Source commit: `e55cbc9d9062c96f405d83e57a592ae5b545776e`
- Android payment-proof upload now uses the existing Expo File + Expo multipart transport instead of legacy React Native uri/name/type multipart.
- Successful customer proof upload refreshes order/payment context and leaves explicit success feedback on the same order.
- Successful admin shipment now continues directly to Isporuka, where tracking is prominent with copy and safe HTTPS open actions.
- CMS static remains 983/983; Mobile typecheck/validator, Expo and OpenAPI parity gates passed.
- No schema/database mutation, EAS build, submit, OTA publish or Google Play action. Build23 remains deferred.
- Next grouped batch: Batch518B Navigation UX Consolidation.

---

## Batch517 - Catalog Image Availability & Legacy Media Publication

- Status: **PASS**
- Source commit: `6ddb27ba26bf893ca1659f814eaa02398b607556`
- Legacy product images for the targeted production SKU set are published to canonical public storage with validated originals and derivatives.
- Catalog API primary-image presentation now falls back to the first ordered image when historical primary flags are missing.
- The authenticated legacy Web media route remains private; Mobile continues using public thumbnail/display/original URLs.
- Production repair was dry-run gated and, when needed, executed only after a verified database backup.
- CMS static remains 983/983; Mobile/Expo/OpenAPI gates passed.
- No EAS build, submit, OTA publish or Google Play action. Build23 remains deferred.
- Next grouped chain: Batch518A APK payment-proof upload + order continuation, then Batch518B navigation UX consolidation.

---


## Batch516 - Financial State Canonicalization

- Status: **PASS**
- Source commit: `318c0cb9f7225f2f2139f72debbb5723872e6316`
- `order_payments` is the canonical received/refunded money ledger; order financial status fields are derived through one projector.
- Manual Web/API/Mobile payment-status mutation is removed; real ledger operations remain authoritative.
- GET order detail no longer persists IPS cache; downstream IPS/receivables reconciliation runs after canonical mutations.
- Controlled production derived-state repair completed only after dry-run census and verified database backup.
- CMS static remains 983/983; Mobile/Expo/OpenAPI parity gates passed.
- No EAS build, submit, OTA publish or Google Play action. Build23 remains deferred.

---


## Batch515R3 - Customer Order Amendment Recovery V3

- Status: **PASS**
- Source commit: `5ceb1cbc43aaeaa132fe9abc2e4a1ea8e2555d27`
- Customer can amend products, quantities, delivery data and note until shipment/completion/cancellation lock.
- Inventory deltas, idempotency and optimistic concurrency protect retries and stale user/admin screens.
- Order documents now persist immutable item snapshots; customer amendment invalidates active confirmation/proforma/invoice for later revision.
- Payment ledger is preserved; payment state, receivables and commission workflow are reconciled with the amended subtotal/items.
- Laravel product save labels use canonical UTF-8 `Sačuvaj` copy.
- Controlled migration `2026_10_03_000200_create_order_document_items_batch515.php` applied after DB backup and source gates.
- No EAS build, EAS submit, OTA publish or Google Play action. Build23 remains deferred.

---


## Historical planned Build23 command - OBSOLETE, DO NOT EXECUTE

```bash
/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node /opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js exec --yes --package="eas-cli@24.8.0" -- eas build --platform android --profile production --auto-submit-with-profile production --non-interactive --wait --json
```

## Google Play - Napomene o verziji

Poboljšana kompatibilnost i stabilnost aplikacije na Android uređajima.
Ažurirane sistemske komponente u okviru Expo SDK 57.
Dodatna interna poboljšanja pouzdanosti i performansi.

---

## Batch513R2 - Shipment / Delivery Dual Action UX Recovery V2

- Status: **PASS**
- Source commit: `3cbd0d0a67023352292020722441b29185649ca4`
- Recovery: Batch513 stopped in preflight on pre-existing audit/runtime residue; Batch513R then reached CMS static and exposed a stale beta7.7 literal sentinel. Batch513R2 preserves the same domain logic, keeps residue read-only, and aligns that static regression sentinel with the approved dual-action UX.
- Laravel order detail now exposes one logistics action hub with separate confirmation for shipment sent and shipment delivered.
- Mobile exposes the two primary shipment/delivery actions vertically, with COD-aware delivery wording.
- Existing shipment, completion and payment domain semantics are preserved.
- No migration, database mutation, EAS build, EAS submit, OTA publish or Google Play action.
- Build23 remains deferred.
