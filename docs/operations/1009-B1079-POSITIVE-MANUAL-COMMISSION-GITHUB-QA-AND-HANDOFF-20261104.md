# Report1009 — B1079 positive manual commission, real GitHub QA and handoff

Date: 2026-11-04. Batch B1079 is distinct from report sequence1009. Sequence reserved after fresh origin/main and local docs/operations maximum1008. Source-only work; release HOLD.

## Owner decision and implemented behavior

Blank, omitted or null manual_commission_eur means automatic commission: 10% of product value converted to EUR. A manually supplied EUR amount must be positive (minimum0.01 at existing cent precision), and may be below or above10%. There is no manual10% floor or fixed50EUR ceiling. For a1000EUR product: blank =>100EUR; manual2 =>2EUR; manual150 =>150EUR; manual0 =>422. Existing technical maximum999999.99 and SuperAdministrator-only authority remain.

ProductRequest preserves raw boolean/array input for numeric validation rather than converting true to1 or throwing on arrays. Whitespace becomes null; decimal comma strings are normalized. Web form and both mobile create/edit validations agree. Mobile API type permits null for explicit clearing. All three OpenAPI copies are byte-identical and describe this exact contract. No new dependencies, database migration, historical commission/order snapshot rewrite, or change to CommissionCalculator/OrderService.

## Source authority and exact scope

[Draft PR131](https://github.com/AldinAga/ald1n-project/pull/131), branch fix/b1075-storefront-recovery-20261103, final tested SHA **7efffa17f068494b23d9713b7af4896c7045f349**. Stacked on unmerged PR93 acb8b9c42ec9254b1a27567abca29a26c1f733f8. B1079 delta base692e751e0dec407d998c0d1655c4bde5a4600d99.

Twelve paths: ProductRequest; CMS product form; two mobile catalog screens; mobile API types; three OpenAPI copies; new ManualCommissionPolicyB1079Test; existing CommissionCalculatorTest and CommissionPercentagePolicyContractTest; existing isolated QA workflow. Eight application/contract paths, three test paths, one workflow. All12 local files byte-matched exact GitHub head; git diff --check PASS. Complete and production-only UNAPPLIED patches retained outside the repository for review/SDK58 port. Calculator/OrderService unchanged because their existing positive override/uncapped calculation already matches the owner decision.

## Genuine RED then GREEN, including implementation mistakes

- Clean zero-input RED source6bfad090091d95be79fdb51e735cca91a39c02d6, [run41747146747](https://github.com/AldinAga/ald1n-project/actions/runs/41747146747):76tests/848assertions/5failures/0errors. Explicit zero/string-zero/subcent create accepted, zero update accepted, web zero accepted. Initial test-only update fixture lacked required SKU; corrected before treating the independently rerun RED as valid. Clearing to automatic already worked and is not attributed as a new fix.
- Type-boundary RED source30c636b2c07399b71099e7ba1e0e74456b2ce373, [run41750481838](https://github.com/AldinAga/ald1n-project/actions/runs/41750481838):86tests/942assertions/3failures. Original boolean normalization admitted true and array input returned500. Minimal raw-type preservation fixes the shared request boundary; false/true/array now422.
- Final [run41750685984](https://github.com/AldinAga/ald1n-project/actions/runs/41750685984): **86 focused tests/951assertions PASS** on PHP8.4.27 and disposable MariaDB10.11.19. Nineteen new feature cases cover invalid values, optional/default/manual values, authorized create/update/clear and web behavior; no real customer orders/payment.
- First implementation workflow used runner.temp in unsupported job-level env; no QA executed in that attempt. Fixed by step-level shell export. An initial OpenAPI paraphrase failed an existing static authority assertion; retained its canonical wording and appended the new owner clarification. These failed preflights are not hidden or called successful.

## Full, unfiltered results and unresolved identities

Original PR93:553cases/10errors/71failures/**81 failing IDs**,0skips.
Final candidate:640cases/7errors/68failures/**75 failing IDs**,0skips. **Zero newly failing IDs**. Overall backend run remains FAILURE/HOLD_HISTORICAL_FULL_SUITE_FAILURES.

Raw missingOriginalCases=3, not zero. One original provider-discovery error identity is explicitly replaced by its seven original passing datasets (B1078 metadata repair). Two obsolete commission test identities were renamed to the owner's new positive/manual-override contract; new identities execute and pass. Workflow explicitly requires this exact mapping and refuses any additional missing identity. Three obsolete financial assertions were reconciled from B1078's78 failing IDs to75, not three new calculator fixes. Unrelated OrderDetailPresenter stop-failing remains unattributed. Protected B805/B809/B815 fixtures and Composer locks byte-identical; no skip/filter/guest-policy relaxation.

Mobile: locked SDK57 npm10 source check, TypeScript, existing validator zeroFAIL and OpenAPI YAML schema/parity PASS. Full Release25 runs on BOTH unchanged B1078 baseline and final candidate:222tests/218PASS/**same4FAIL**,0skips/cancel/todo,0newfailedIDs. Test source/package/lock unchanged. Deliberate HOLD_HISTORICAL_RELEASE25_FAILURES, no waiver. The four inherited documentation-authority failures concern current WorkspaceV2 versus old Build25 pointers, archived Build23 instructions, canonical CLIv25/config convergence, and Report682 forensic pointer separation. Do not restore obsolete live release authority merely to green tests.

[Final browser run41750685912](https://github.com/AldinAga/ald1n-project/actions/runs/41750685912): B812 registered Kupac flow and replay, staff denial, real320x240 PNG HTTP/MIME/decode/private-no-store in **Chromium and Firefox PASS**. Synthetic disposable users only, no real SMTP or physical device acceptance. Containing historical B799 guest-success scenario still executes and fails; the workflow overall is FAILURE.
[Static run41750686150](https://github.com/AldinAga/ald1n-project/actions/runs/41750686150): CMS canonical983checks/0unsuccessful PASS. Other red historical role/security/catalog workflows remain red; focused evidence is not a complete CI PASS.

## Durable evidence and integrity

Evidence folder: D:\GPT\Ald1n-mobile-tools\storefront-qa\b1079.
- Clean zero RED ZIP SHA256538cf33fb70c5ea71d080ac12b56cef490206b4fbe63ed57b1715c36115d6b4d.
- Type RED ZIP SHA25629339f50a911588f3244003da2ae204df37ea8861332e14c46e772dd4a5e6e80.
- Final backend ZIP SHA2569d8c05fb91fc79f2dfb662e0697f05a4363ddcd81ec8f97f44b6997e56ea318a.
- Final mobile ZIP SHA2562264e8160796f1a8f1c9619721c4c6d1f7b290122484bd7349750cc028493d37.
Raw JUnit/full logs, identity migration/comparison JSON, both Release25 TAP streams and exits are retained. Aggregate Drive archive/runbook update receipt is appended below after upload. No production secrets, DB dumps, vendor or node_modules included.

## Continuation for Claude or another Codex account

1. Read root AGENTS.md including0M, docs/operations/000-LATEST.md, this report and Report1008, then canonical Drive runbook ALD1N-EXPO-CLOUDLINUX-UPDATE-RUNBOOK.md ID10Lxe_reZ1csYOMK9ESjZ8NUmEglDpOMy. Older pending-zero statements are superseded by this owner decision.
2. Preserve original D:\GPT\Ald1n-mobile, its full history/migration bundle, and recovery worktree C:\Users\ProDesk Mini\.codex\worktrees\storefront-recovery\Ald1n-mobile. Recovery local HEAD still PR93 with preserved uncommitted candidate files: do not reset/clean or assume HEAD includes the edits. Compare tracked and untracked files with exact remote candidate; use evidence patches for review.
3. Refresh main and relevant refs. PR131 is NOT main or deployed and does not include every other open fix. Draft PR129 Laravel mobile menu remains f814c3bfa567d8a6d191aae5cd9a6db8ec7d058c; Draft PR130 Windows validator remains0c4e2f9c23baedd5ab60336f154c42db2954f237. Draft PR111 M11 warranty head3acc37e90332b0d8fde659503c5cc1a8b5fcc6aa; Draft PR127 SDK58/Nitro/npm12/EAS27 head6c27cd9f5387a3a12fb8381a98e0fac32eb6c3d5. References129/130 rechecked open/draft in this batch; recheck all before integration.
4. PR127 does NOT contain B1079 input changes. Port/reconcile the eight application/contract paths into a reviewed final assembly, then rerun matching SDK58/npm12 tests and full combined suite before any build. Never claim SDK57 source tests attest SDK58. Resolve dependencies/conflicts with menu129, validator130, storefront93/131 and warranty111; no blind merge.
5. Next public-commerce work: investigate remaining75 exact PHP identities from raw JUnit without rewriting protected fixtures or reopening guest checkout; reconcile4 release-authority failures against current approved documentation; complete security368/SEC23, registered-user concurrency/idempotency/stock and payment/merchant/legal acceptance. Follow Report1008 remaining M10/M11/M13/M14/M15/M17 order and gate details.
6. Runtime checks remain GitHub-only by owner instruction. Do not start local PHP/MariaDB. Existing START-HERE-HANDOFF.md and migration start helpers provide Windows prerequisites/commands; production secrets and dumps remain excluded.
7. Before production: independently prove fresh host source/FPM/OPcache, actual SMTP, backup restore, merchant/legal and owner UAT. Dirty host84tracked/56untracked and B1065cleanup hold must be reconciled, never reset. Follow canonical incoming/scripts and sequential docs/operations reports with automatic integrity checks; do not run revoked B1069. No enabled flags, deployment, production migration, EAS/native/OTA/Play until all applicable gates pass.

## Mutation, limitations and progress

SOURCE_MUTATION=yes on isolated DraftPR131; COMMIT_CREATED=yes; PUSH_COMPLETED=yes; CANDIDATE_PUBLISHED=no; PRODUCTION_PROMOTED=no; FAILED_STAGE=75historicalPHP+4inheritedRelease25+unattestedreleasegates. Main changes for this batch are documentation only. No CloudLinux/production access, DB/schema/snapshot mutation, public flags, SMTP, build/OTA/Play, release bypass or historical test suppression.

Formal accepted ledger remains11/17=64.7%, CMS10/15=66.7%, originalUI6/6. Six milestones M10/M11/M13/M14/M15/M17 remain unaccepted; implementation work is not production acceptance. Weekly Usage measured15%, owner ceiling20%, no reset redeemed. AIVM topic capture blocked by403 plan/role from earlier attempt; no repeated unchanged write and no claim of brain persistence. GitHub+Drive are durable handoff records.

## Archive checkpoint

[Permanent complete B1079 evidence](https://drive.google.com/file/d/1NNWoEaqcsj0U93j0oFQzF8kNr3OYkgPh/view), SHA256 **ec5e98a18cfd7e8610f2f34e19ff0adc36a985d70ad8ccfa347484a5e96d3e7f**. Archive contains the preceding report revision before this self-referential receipt; individual original artifact hashes above remain authoritative.
