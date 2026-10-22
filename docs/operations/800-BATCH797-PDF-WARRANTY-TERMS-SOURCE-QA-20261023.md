# Report800 - B797 PDF layout and prospective warranty terms

Date: 2026-10-23 (Europe/Belgrade)
KIND=SOURCE_AND_DELIVERY_QA_NOT_PRODUCTION
BATCH797_SOURCE=MERGED_PR90
SOURCE_COMMIT=126dd53eed17796733bc2a8b4cd7009d3a90ae41
TESTED_PR_HEAD=97af7f10b44f379ae0014d272b4e81d039bf0c7e
TESTED_MERGE_REF=bb321e315cb0d0d4652441957048635d617cc23d
TESTED_AND_MERGED_TREE=3f5d51575187ec7f190882f69ec472757b8f424b
PRODUCTION_EXECUTION=PENDING
WARRANTY_FIRST_PAYMENT_TRIGGER=UNCHANGED_PENDING_R4_R5
PUBLIC_STOREFRONT=NOT_IMPLEMENTED_NOT_ACTIVATED
ANDROID_BUILD_OTA_PLAY_ACTION=NONE

## Confirmed predecessor - never repeat

Reports798/799 certify B796 source 6f81f2bcfca1559dae92c39be323a1c827c31239 on CloudLinux: first invocation applied ten files and passed; accidental second invocation CHANGED=0, PASS_ALREADY_APPLIED NO_MUTATION, exit0. Both raw owner reports remain locally required and privately archived. No rollback or further B796 invocation is needed. Owner visual acceptance of the new B796 slice and independent PHP-FPM source attestation remain unconfirmed.

## Root causes and corrected scope

- Warranty product title/SKU were drawn on single unbounded lines inside fixed-height cards. Terms were limited to ten lines; serials, contacts and long metadata could be lost or overlap.
- Shared wrapping estimated text but did not split overlong unbroken tokens or preserve paragraph boundaries consistently. Bold measurement could differ from actual rendering.
- Business, order and commission reports used fixed rows and truncated text. The management report discarded segments after eighteen and could place operational content below the printable page.
- One shared FlowPdfLayout now measures real font widths, preserves paragraphs and complete tokens, grows/splits panels and rows, repeats headers and paginates. The four renderer families cover warranty certificates; invoices, proformas, delivery notes and order confirmations; order reports; commission reports and management reports. Totals, prices, taxes, payment snapshots, revisions and cancelled-document data are unchanged.
- Logo fitting preserves original aspect ratio within a consistent tile. Printable A4 margins, repeated page identifiers, footer and QR placement are controlled. User-configured notes, terms and all report segments are retained rather than sliced.
- Warranty issuer contact uses actual configured values. A separate statutory-rights notice is displayed; missing issuer configuration is not fabricated. Rendering improvements do not themselves certify all merchant/legal obligations.

## Warranty conditions - prospective only

The new-rule terms field is prefilled. NEW certificates with an empty rule use the following default; nonempty custom terms are preserved exactly. Existing issued terms snapshots, including blank historic snapshots, and their dates are not modified. No SQL migration, rule backfill or mass certificate rewrite. New restrictions must also be consistent with the terms actually agreed with the buyer; a new technical snapshot is not permission to change an existing contract.

Garancija ne obuhvata kvarove i oštećenja za koja se utvrdi da su nastali nakon predaje uređaja usled pada, udara, pritiska, prodora tečnosti, nepravilnog priključenja, korišćenja suprotno nameni ili uputstvu, kao ni neovlašćenog zahvata koji je prouzrokovao kvar. Uzrok i povezanost oštećenja sa prijavljenim kvarom utvrđuju se pregledom i obrazloženim nalazom. Ova isključenja ne ograničavaju pravo potrošača da podnese reklamaciju niti njegova zakonska prava zbog nesaobraznosti, koja ostvaruje prema prodavcu bez naknade.

Legal review basis: Serbian Consumer Protection Act, Sl. glasnik RS 35/2026, articles59,61,62,63, retrieved2026-10-23. Source text: https://www.paragrafco.rs/propisi/zakon-o-zastiti-potrosaca-2026.html . Statutory nonconformity rights cannot be excluded by a ten-day commercial field; used consumer goods may have an agreed shorter statutory period, not below one year. Damage exclusions must address the actual cause, not automatically defeat unrelated claims. Complaint intake remains available. The commercial guarantee must actually add rights, and issuer/procedure/electronic-delivery requirements require correct real merchant configuration. Legal-professional review is recommended; no claim of immunity or complete legal-compliance certification. Configured duration and historical dates have deliberately not been silently changed.

## Verified evidence

PR90: https://github.com/AldinAga/ald1n-project/pull/90 . Tested and squash-merged trees are byte-identical. Seventeen changed source/test/workflow files independently matched tested blob identities.

- Final PHP8.4.27 / disposable MariaDB10.11.19 scoped suite: **40 tests /337 assertions /0 failures /0 errors**, static **983/983**. https://github.com/AldinAga/ald1n-project/actions/runs/39866776524
- Real regression RED on unchanged source: fourteen new tests, eight assertion failures and zero setup errors; then GREEN. Standalone wrapping contract25 assertions passed after reproducing long-token width and paragraph loss.
- **20 synthetic PDF cases /91 A4 pages /7215 text spans**, zero page-boundary, overlap or retained-content errors. Normal examples for all eight document types fit one page; stress cases preserve final rows, segments, long SKUs, serials, notes, terms and maintenance text. Every page was rendered and visually reviewed; standard warranty reviewed at full resolution. Synthetic fixtures only, no owner/customer records or standalone fonts in delivered evidence.
- All eight final applicable PR workflows PASS:39866776347,39866776344,39866776368,39866776360,39866776375,39866776423,39866776524,39866776395. Existing authenticated Chromium, order-role, warranty-expiry and dedicated MariaDB/MySQL retirement regressions passed.
- Full project suite actually run before/after: **382tests/43problems ->396tests/42problems; zero new failure identities**. This is NOT a wholly green suite. Named baseline debt and raw JUnit comparison are retained. A pre-existing presenter error absent on the later run is not claimed to be causally fixed by PDF work.
- Bootstrap run39865855787 passed all test stages but failed its final push because that CI token could not add the new workflow. The tested already-created commit was preserved; an authorized connector advanced only the isolated branch with an expected-SHA lease. Overall bootstrap remains FAILED in history. Final PR checks above are independently green; no token escalation or production action.
- Review was author/agent self-review; no independent reviewer was available. No remaining Important defect was identified in this bounded slice; inherited generic-suite debt and merchant legal-policy review remain explicit limitations.

## Final operator package

Canonical basename: `ald1n-b797-laravel-pdf-warranty-terms-v1.sh`.
Source target: `126dd53eed17796733bc2a8b4cd7009d3a90ae41`.
Script SHA256: `fb46729499bd1da0c95f3a42d1499430b903cd95dfb556fb09d00b1c728bd50f`.
Manifest SHA256: `77aad63d23a822a9e115b2965d12f2352e54e1d4445b2e293363c129f1526fe6`.
**686-path runtime closure /13 allowed runtime writes**. Final package rebuilt against the merged source. Nine local delivery safety groups PASS: exact advertised basename, wrong path/name, tamper before host/network, immutable full closure, unknown-file denial, partial rollback, maintenance origin proof, scheduler ownership/drain and report-writer failure recovery. Bash/PHP syntax PASS; no forbidden CloudLinux process substitution, /dev/fd or python3 dependency in operator script.

One invocation only:

```bash
bash /home/icaffeco/ald1n-project/incoming/ald1n-b797-laravel-pdf-warranty-terms-v1.sh
```

Keep original `798-BATCH796-LARAVEL-OPERATOR-WORKSPACE-20261022-202142.md` (SHA256 2b2536a7852000db202317b02f2878e75b985cf3e8a24d12ef1a0dbc46cf798d) and `799-BATCH796-LARAVEL-OPERATOR-WORKSPACE-20261022-203226.md` (SHA256 f84e9b0ff37d2eee25939e6798b6c283ecfb74ae94ed0e60e0ac472d628dc25b) in canonical docs/operations. Both are automatically bound. Preserve dirty host HEAD/index/.htaccess and unrelated runtime files. Temporarily owned maintenance and Scheduler pause; private source snapshot and bounded rollback; no process killing. Production smoke makes synthetic PDFs in private scratch, not real customer documents. Fresh max remote/local report sequence; expected next801 if no intervening report. CLI/HTTP checks do not independently attest authenticated PHP-FPM/OPcache or owner visual acceptance.

No schema migration, old migration rerun, historical certificate/price/commission/business mutation, DB backup create/prune/restore, production test-order/customer-mail, destructive Git synchronization, Mobile/build/OTA/Play action. Already-applied identical target remains a no-op. Do not hand-edit the canonical script, hashes or basename.

## Remaining plan and progress

First confirmed positive incoming payment remains the settled issuance trigger for the NEXT R4/R5 implementation, including deposits/first installment and direct sales. Full settlement is not required. Preserve delivery-based validity, idempotency/concurrency, existing certificates and default-OFF independent per-recipient expiry opt-ins for the true selling user and active SuperAdministrator only. These business changes are NOT activated by B797. Public catalog/cart/checkout/protected follow-up at /katalog remains inactive; Android16KB and actualSDK58 remain separate later gates.

Keep the existing17-milestone denominator: originalUI6/6=100%; CMS7/15=46.7%; program7/17=41.2%. M08/M09/M12 have B796 source+hostPASS but owneracceptancepending. The newly requested PDF corrective item is tracked separately, SOURCE_QA_PASS/PRODUCTION_PENDING, without inventing closure credit for first-payment, expiry or storefront milestones. Planning and source merge are not production acceptance.

## Rulings and costs

1. Deliver the owner-requested PDF/terms repair as a source-only slice before any unverified payment/schema transition. Cost: R4/R5 need their next separately tested package.
2. Preserve historical terms/durations and statutory rights rather than promising liability exclusion. Cost: merchant guarantee policy and required real issuer/procedure data still need legal review; technical repair is not immunity.
3. Retain named whole-suite baseline debt instead of changing unrelated business logic to force green. Cost: no whole-suite green certification.
4. Track this new corrective item outside ledgerv1 rather than silently changing its denominator. Cost: extra corrective effort is not represented by the existing acceptance percentage.
