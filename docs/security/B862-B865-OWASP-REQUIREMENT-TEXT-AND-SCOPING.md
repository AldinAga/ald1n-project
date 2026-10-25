# B862–B865 — complete OWASP requirement wording and review-ready 368-item queue

Date: 2026-10-25. Scope: **security assurance prerequisites only**, not a penetration-test result, live vulnerability scan, storefront activation or production change. Source: Draft PR100 chained above PR99 / PR93; final exact commit must be taken from the CI event. Follow `AGENTS.md`, the canonical Drive runbook and `STOREFRONT-PENETRATION-TEST-PLAN-20261024.md`.

## Confirmed gap in B844

The original B844 census correctly enumerated **115 WSTG v4.2** items plus **70 ASVS v5.0.0 L1** and **183 additional L2** items (= **368**), and held every item `UNASSESSED/NOT_RUN`. However, for the 253 ASVS rows it emitted `title = chapter_name`, silently losing the actual `req_description`. A reviewer cannot meaningfully assess hundreds of individually distinct requirements from chapter headings alone. This is an **incomplete evidence artifact**, not a reason to treat those controls as N/A or PASS.

**B862** retains the **verbatim** `req_description` and original `chapter_id/chapter_name/section_id/section_name` for all L1 and L2 requirements, plus the pinned WSTG checklist titles. The original OWASP blobs remain exact: WSTG Git blob `7fe5f31651ccbdf4fd7f67fe72b1b4f866c98931`; ASVS Git blob `184cf3b04a8fe1809dbed8b6e73be7a6fbaf8689`. Any mismatch, missing requirement wording, section hierarchy error, unexpected standards version, duplicate ID, skipped case or changed count is a hard HOLD.

## B863 — review-ready 368-row working register

A strict offline generator converts the canonical B844 register into **JSON + CSV**, with each distinct requirement's actual wording and metadata and these extra *blank* manual review fields:

- candidate `SECxx` workstream hint, derived only from the official chapter/section and not a reviewed applicability decision;
- applicability `UNASSESSED`, status `NOT_RUN`, empty route/role scope, test method, expected result, evidence reference, assessor and assessment notes.

These automatic chapter-to-workstream hints are **not** assurance that the case is relevant to this application or that a specific endpoint has been tested. An assessor must independently discover and document the affected source/routes/roles, test expectation, synthetic test method, actual execution evidence, and justified inapplicability as appropriate. Potential workstreams are exactly the 28 sections SEC01–SEC28 in the canonical penetration plan; a chapter hint is never a substitute for full per-item coverage. Particular infrastructure-only cases remain **NOT_RUN or BLOCKED** until a separately approved isolated infrastructure scope exists. No blanket N/A.

The only previously tested *related* security evidence is scoped:
- [B854 real Laravel route census](https://github.com/AldinAga/ald1n-project/actions/runs/40194344742): 549 routes, 14 public-shop boundaries; does not prove actual authorization across roles, CSRF, live TLS or all INFO/APIT item PASS;
- [B858 Composer lock security audit](https://github.com/AldinAga/ald1n-project/actions/runs/40194344706): 19 old advisory records cleared to 0 on Draft PR100; production remains unpatched/unverified; does not prove all supply-chain and code security items PASS;
- [B859 full dependency delta](https://github.com/AldinAga/ald1n-project/actions/runs/40201308807): 574 vs 574 exact PHP tests, 0 new failing identities, still 98 failures; not a pen-test certificate;
- [B803 full baseline comparison](https://github.com/AldinAga/ald1n-project/actions/runs/40194344679): 56 *new* failures, including deliberately protected test fixtures, all release HOLD.

## B864 — safety and provenance gate in GitHub CI

The extended existing `b844-owasp-item-census-qa.yml` invokes 8 synthetic canonical-list tests + 15 adversarial review-queue tests before downloading anything. It downloads **only pinned official source content**, verifies the exact Git blob hashes, generates full 368-item B844 JSON and the new review-ready full-text JSON/CSV, checks exact source SHA, 115/253/L1-L2 counts, 0 reviewed, 0 PASS, no N/A, and separately stores the artifacts. It does not make HTTP requests to a CMS or staging target, log in to any account, execute dynamic probes, send email, or run EAS/Play/OTA.

If source enumeration is green, only the quality of the inventory improves. All controls still need individual applicability review, a selected test method, isolated execution, a recorded actual result, and independent final sign-off.

## B865 — actionable next security and release gates

The next permitted security step is **manual item-level scope/application review using this complete wording**, followed by safe, approved isolated test execution and separate evidence with no suppressed or skipped cases. Do not silently mark any of these 368 as PASS from source-only tests. Preserve the earlier provider safety denials on B805/B809/B815; no alternate-fixture workaround, test deletion, or weakening authorization. The full 56 B803 new failing identities remain a source/release HOLD.

Android PR82 exact-source 16 KiB compiler / offline emulator is already successful; avoid re-running unchanged expensive native tests. A new production-signed EAS AAB with independently verified remote `versionCode` (>27), source provenance, actual signer certificate, physical-device UAT and Play acceptance remains mandatory. No production build, migration, CloudLinux deployment, staging attack or user SSH required in this batch.

Formal owner milestone ledger: **7/17=41.2%** accepted (CMS **7/15=46.7%**, original UI **6/6=100%**). Producing a review-ready coverage register must not increment any acceptance milestone or be misrepresented as the penetration gate closing.

## Licensing

This is a derivative reference/index of selected pinned official OWASP material. The source catalog documents OWASP **CC BY-SA 4.0** licensing; include source links/blob identifiers and license notice in copies of the resulting register.
