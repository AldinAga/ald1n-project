# Report1011 — B1081/B1082/B1083: commission main, complete Draft assembly, manual menu and registration authority

Date2026-11-04 Europe/Belgrade. Sequential report1011 after fresh main/local maximum1010. Batches1081/1082/1083 are separate from report sequence. Active overall goal remains INCOMPLETE; runtime QA GitHub-only, owner weekly maximum20%.

## Completed source integrations

PR134 positive manual commission merged **1c185c6c5a97d3917069c8d73ad4d2df781c0e7c**, tested source2c20f2a83e267764314d759b91604f25c33e1784. Eight application/contract paths, three tests, existing workflow only, clean three-way port onto fresh main rather than replacing newer files. Blank/omitted/null =>automatic10%; explicit positive manualEUR (cent minimum0.01) may be below/above10%, no fixed50EUR cap; existing SA-only authority and technical maximum999999.99 retained. CommissionCalculator/OrderService, native identity, locks and already-issued snapshots untouched. No production backend deployment or DB migration.

[Actual41792554005](https://github.com/AldinAga/ald1n-project/actions/runs/41792554005), two NEW independent MariaDB10.11.19 databases and independent vendor installs: same24 owner-policy cases, frozen main RED24/100assertions/10failures/0errors =>candidate **24PASS/159assertions**. No skips. API/web invalid zero/subcent/boolean/array refusal and optional/default/manual persistence exercised. Canonical CMS983/0unsuccessful PASS. Same head full309mobile/TSC/validator PASS and all10 PR workflows SUCCESS, including real authenticated Chromium. Source merged only after all completed.

PR136 test-only product contracts/metadata merged **e89def93293ed2828f02bed5e412c93e00a7c0df**, tested db516c618a8a0392ad15db89e3e385f414a8d27b. Three obsolete literal-source expectations demanded permanently removed variant markup/maps/normalizer. They now require existing product-only gallery, product_id order payload and product storage path, preserving all other safety/render/owner assertions and original test identities. Added one direct production StorageSpecificationService regression proving500GB+1TB=1524GB and legacy missing-capacity fallback750GB. Existing actual CatalogDetailPage/ProductionOrderInventory HTTP, inventory and replay cases execute on both sides. Two already-proven metadata-only fixes ported: original7 forbidden SQL datasets now execute via PHPUnit12 DataProvider attribute; existing Tests\\TestCase resolves base_path. No application/resource/schema/money/mobile/dependency change; B805/B809/B815 untouched.

[Actual41794501015](https://github.com/AldinAga/ald1n-project/actions/runs/41794501015): baseline25cases/120assertions/2errors/3failures =>candidate **32cases/152assertions/0errors/failures/skips**. One old provider discovery-error placeholder explicitly replaced by its7original datasets; one new storage behavior case added, all other identities retained. All9 PR workflows SUCCESS before merge. This is contract/harness repair, not5 production application defects fixed.

## Complete current-main storefront Draft, not a safety bypass

[DraftPR135](https://github.com/AldinAga/ald1n-project/pull/135) is an explicit review assembly, NOT approved for main/public activation. It combines newer main with prior PR131 d5d7a62cf8e2a6b374bc93bbdc131b23a9b5ae18. Original main/PR93 differences are historical absence of commerce in main, not new deletions by this batch.

Initial source46f8ebac60e1cb71783afebce638063edc1f9b1d: resolved the sole conflict by keeping current-main WarrantyFirstPaymentIssuance798Test byte-identical, including B943 all-or-nothing COD/warranty rollback. All prior test methods retained. Kept current-main two mobile contract files; no stale forensic bindings transplanted. Current-main payment, order, commission, warranty recovery/recipient, push services, dependencies/native identity/menu CSS byte-parity proved. Protected3 fixture files remain exact original PR93 bytes. All640 actually executed earlier PR131 cases pinned by artifact-bound census and required in full CI.

[Initial full41793687507](https://github.com/AldinAga/ald1n-project/actions/runs/41793687507): main480/6errors/16failures/**22failedIDs**; candidate697/7errors/68failures/**75failedIDs**,0skips. Exact same75 as earlier PR131, all640 retained; rawmissing1 is explicitly mapped provider discovery replacement. **56 failed IDs are new versus main**, because main had no commerce suites, and are the known unchanged historical commerce conflicts. Never claim0new versus fresh main. The workflow FAILS on them; no whitelist or acceptance waiver.

After verified PR136, Draft refreshed to **75130a030f090384d44ec65d635fd064b56adf53**, baseline frozen actual maine89def93293ed2828f02bed5e412c93e00a7c0df. State-aware metadata guard reuses fixed providers rather than applying imports twice. [Actual full41795397919](https://github.com/AldinAga/ald1n-project/actions/runs/41795397919): **main487/4errors/13failures/17failedIDs; candidate698/7errors/65failures/72failedIDs**,0skips,0missing main IDs, all640oldPR131 cases retained. 56new versus main remain explicit FAIL. Three variant-contract identities removed from the old75 by verified PR136; no protected fixture changes. Same head full309mobile/TSC/validator PASS. Real registered Chromium+Firefox journey/PNG/replay evidence also retained; containing old guest-positive browser test remains red.

Remaining72 =40HTTP302+9HTTP403+3HTTP404 mismatches,13other assertions,7execution errors. Status failures often stop at an old guest preparation GET before reaching the intended attack/payment case. They are not52 proven broken production requests. Execution errors:3 obsolete DirectSale private-price-cap methods; protected B815 nonexistent SettingsService::put;3 capability fixtures invalid under registered-buyer rules. Never restore retired price caps, variants or guest purchases just to green tests. Protected B805/B809/B815, prior rejected integration and all historical gates remain in force; no alternate SSH/manual-packaging bypass.

Unattributed OrderDetailPresenter stop-failing remains unattributed. Main17 problems and Draft72 are different full-suite observations; main's scoped9/10 workflows do not certify full PHP suite GREEN.

## Manual menu upload: evidence and limits

Owner explicitly forbids agent FileZilla control, prefers files/exact paths or one tested SSH command when manual action is needed. Honor this persistently. No FileZilla mutation was performed. Previous UI attempt stopped when browser URL could not be safely determined; no credentials obtained or account changes.

Pack delivered **D:\GPT\Ald1n-menu-fix-20261104.zip** and flat directory D:\GPT\Ald1n-menu-fix-20261104. Exactly two already-tested PR129 app files:
- public/assets/css/ald1n-ui-refinement.css -> /home/icaffeco/ald1n-project/apps/cms/current/public/assets/css/ald1n-ui-refinement.css.
- resources/views/layouts/app.blade.php -> /home/icaffeco/ald1n-project/apps/cms/current/resources/views/layouts/app.blade.php.

Owner confirmed manual upload. Before upload public CSS HTTP200,16158bytes,SHA2565b98add1b208bc08a0672677044644c41cd49fcac2237bef1a225f70d025221a exactly matched old baseline. After upload cache-busted public CSS HTTP200,24702bytes,SHA256 **3a6176d2a049588797506b4d3b1e97e5aaef9e49625f3c9be7d9245127b51954**, exact approved file. Cloudflare returned stale unversioned content first; fresh query returned MISS, max-age604800, Last-Modified2026-11-04 07:29:48GMT. This proves actual CSS delivery, not physical-phone visual UAT. Owner reports both uploads; server Blade bytes were not independently downloaded/read. Approved Blade SHA256583b60e3676acb51b7361f59d5bb7a2a3c2902f789a84271936a1d25cd30c039. Preserve owner backup, no broad cache/source/DB action prescribed.

## Latest explicit owner decisions — supersede old guest assumptions

Owner reconfirmed original guest-buying rule was later withdrawn. Every NEW public purchase requires a registered and authenticated Kupac, with existing active/non-retired/verified-email/activation and tracked-session guards preserved. Mandatory registration details: **first name, surname, address, city, phone and email**. Existing privacy acceptance, Turnstile, approved activation-mail sender and one-time email activation remain; no postal-code/extra field silently made mandatory.

Owner separately confirmed **there are NO old orders without accounts**. Do not invent or implement a legacy anonymous-order/capability compatibility path. New private receipt issuance/access stays tied to its registered owner; public browsing/cart may remain anonymous.

At this checkpoint the Draft still has phone optional and no address/city collection/persistence. B1085 fourteen new actual HTTP/DOM regressions were pushed TEST-FIRST, head7db8f18a83202e73c2107f432d696704e4e8ad9e on DraftPR135. They require each6field omitted/blank to422 without user/token/order/mail writes; full profile persists on pending Kupac without role escalation; form has all6 required inputs. Genuine RED is being collected BEFORE controller/form change. No implementation PASS claimed here. New policy may expose older positive signup fixtures lacking these now-required fields; keep complete full results and protected fixtures visible. No production signup deployment.

## User-readable exact failure list and continuation

D:\GPT\Ald1n-75-dijagnostika-20261104 contains Markdown/ExcelCSV/JSON for all75 original cases in27files, exact failure lines and pinned GitHub links; the3verified product-contract resolutions are marked. This is a retained historical snapshot, not a claim72still75. Latest full verified count72 above. Source-only prospective changes must keep all original identities and report newly revealed owner-policy conflicts explicitly.

Immediate next: complete B1085 RED/GREEN and real registered browser fixture updates for the6required fields without weakening customer verification; continue individually audited unprotected stale contracts/real defects. Do not retry previously denied protected-fixture adaptation. Full security368/SEC23, merchant/legal/mail, concurrency, backups/restore/FPM/dirty host, M10/M11/M13/M14/M15/M17 and physical SDK58/OAuth/signature/16KiB gates remain OPEN_NOT_WAIVED. PR111/127 and production release are separate authorities; no build/native/OTA/Play authorized merely by this progress.

SOURCE_MUTATION=yes isolated source/tests; MAIN_MERGED=PR134+PR136; STOREFRONT_MAIN_MERGED=no; AGENT_PRODUCTION_MUTATION=no; OWNER_MENU_UPLOAD=reported/CSSverified; PRODUCTION_BACKEND_DEPLOYMENT=no; DB/flags/SMTP/EAS/build/OTA/Play=no. Original dirty worktree/acb8b9c HEAD and original D:\GPT\Ald1n-mobile/history retained; local runtime not started.

Formal accepted ledger unchanged **11/17=64.7%,CMS10/15=66.7%,UI6/6**. Weekly Usage latest17%,ceiling20%,reserve retained; no reset redeemed. Goal remains active/incomplete. Prior AIVM capture403 persists; no repeated or alternate brain write. GitHub/Drive checkpoints are durable records.

## Permanent archive checkpoint

[Complete B1081-B1083 evidence](https://drive.google.com/file/d/1hz7lr4Qo5udG-3oLBD5QoElotMOg4uL4/view) SHA256 **f8a09375e4b375d2534029c5e78ec790c30ead951d7898372d847a621dc77b3a**. Includes commission RED/GREEN, full309mobile, initial697/75 and refreshed698/72 full JUnit,32-case contract proof, both registered browsers, original75case spreadsheet, exact assembly proof and approved/live menuCSS. No attached user screenshot or real customer data copied. Archive report predates this self-referential receipt.
