# B819 — storefront security closure register (initial, evidence-bound)

Date: 2026-10-24. This is an initial **workstream** register, NOT the complete OWASP WSTG 4.2 / ASVS 5.0 L2 item-level 368-control census. It does not certify security or authorize public activation.

## Fixed authority
- Source candidate: PR #93, `fab3f4991fe5792e28e5a402dbf402617fb5c303` at latest completed B818 exact-source CI. Verify again before any subsequent source work.
- Production: B798, public storefront OFF. PR #93 remains Draft and unmerged.
- Full CI: 9/14 PASS; 5 FAIL. B803 baseline 451 cases/44 problems; candidate 533 cases/91 problems; 49 new failure identities versus baseline = 48 preserved historical guest-positive cases + one independently safety-DENIED B815 checkout OFF fixture. No tests may be skipped, edited around, suppressed or waived.
- B818 targeted RED: https://github.com/AldinAga/ald1n-project/actions/runs/40115578447 (5 tests, 20 assertions, 2 failures). GREEN: https://github.com/AldinAga/ald1n-project/actions/runs/40115830200 (5 tests, 27 assertions PASS). Original complete comparator: https://github.com/AldinAga/ald1n-project/actions/runs/40115830268.
- Evidence: docs/operations/828-BATCH818-PRIVATE-RECEIPT-STOREFRONT-OFF-20261024.md and https://drive.google.com/file/d/1IpbOL4Pm0wnzZ9qrysM73mBMPS-1eaIW/view.

## Scoped workstream status (SEC01–SEC28)

Status is deliberately conservative: **PARTIAL_EVIDENCE** means a specific verified case exists but the workstream is not complete. **BLOCKED** means a known release gate prevents closure. **NOT_RUN_OR_NOT_EVIDENCED** never means N/A or PASS. The actual WSTG/ASVS catalog item IDs, applicability and individual execution results remain to be enumerated.

| ID | Workstream | Status | Grounded evidence or next gate |
|---|---|---|---|
| SEC01 | Routes/debug/source exposure | NOT_RUN_OR_NOT_EVIDENCED | Exact-source route inventory + isolated HTTP needed |
| SEC02 | Host/proxy poisoning | NOT_RUN_OR_NOT_EVIDENCED | Isolated forwarded header and email-link tests |
| SEC03 | TLS/proxy origin isolation | BLOCKED | Approved bounded infrastructure examination, no production scanning |
| SEC04 | Role boundary | BLOCKED | Historic B802 role CI fails; no global PASS |
| SEC05 | Horizontal order/document IDOR | PARTIAL_EVIDENCE | B818 shared private receipt guard; cross-buyer full matrix pending |
| SEC06 | Bearer capabilities/revocation | PARTIAL_EVIDENCE | B818 GREEN 5/27 verifies emergency OFF and revocation; PDF-level test pending |
| SEC07 | Authentication/reset | PARTIAL_EVIDENCE | B813 retired login, B814 tracked session invalidation, B817 POST 6/40; full catalog pending |
| SEC08 | Session fixation/revocation | PARTIAL_EVIDENCE | B815 scoped credential-bound sessions 5/61; full real HTTP/cookie matrix pending |
| SEC09 | CSRF | NOT_RUN_OR_NOT_EVIDENCED | Real middleware requests and method tampering needed |
| SEC10 | XSS | NOT_RUN_OR_NOT_EVIDENCED | Rendered browser and sink review needed |
| SEC11 | SQL injection | NOT_RUN_OR_NOT_EVIDENCED | Input corpus and DB invariant checks needed |
| SEC12 | Expression/deserialization/XML | NOT_RUN_OR_NOT_EVIDENCED | Reachability analysis required before N/A |
| SEC13 | Media traversal | NOT_RUN_OR_NOT_EVIDENCED | Synthetic path/encoding/symlink corpus required |
| SEC14 | Upload controls | NOT_RUN_OR_NOT_EVIDENCED | Isolated uploads and ownership tests required |
| SEC15 | SSRF | NOT_RUN_OR_NOT_EVIDENCED | Controlled outbound sinks only |
| SEC16 | Redirects/CRLF/parameter pollution | NOT_RUN_OR_NOT_EVIDENCED | Isolated HTTP corpus required |
| SEC17 | Price/quantity/mass assignment | NOT_RUN_OR_NOT_EVIDENCED | Synthetic financial invariants required |
| SEC18 | Payment/delivery fraud | NOT_RUN_OR_NOT_EVIDENCED | Synthetic COD/bank/pickup state machines required |
| SEC19 | Checkout concurrency/idempotency | PARTIAL_EVIDENCE | B812 registered buyer Chromium scoped PASS; independent concurrent matrix pending |
| SEC20 | ON/OFF concurrency | PARTIAL_EVIDENCE | B818 sequential OFF receipt behavior proven; concurrent commit boundary pending |
| SEC21 | Rate limits/abuse | BLOCKED | Safe isolated load ceilings and test target required |
| SEC22 | Cache and bearer privacy | PARTIAL_EVIDENCE | B817 authentication POST privacy 6/40; receipt response headers in source; actual edge/browser pending |
| SEC23 | CORS/CSP/framing | NOT_RUN_OR_NOT_EVIDENCED | Actual browser/response header audit required |
| SEC24 | Audit logs/secret redaction | NOT_RUN_OR_NOT_EVIDENCED | Synthetic failure-path review required |
| SEC25 | Dependency/secret/code scanners | NOT_RUN_OR_NOT_EVIDENCED | Pinned source, lockfile and scanner receipts required |
| SEC26 | Cancellation/refund/warranty integrity | NOT_RUN_OR_NOT_EVIDENCED | Synthetic business state-machine cases required |
| SEC27 | Smuggling/proxy exhaustion | BLOCKED | Explicitly approved isolated proxy scope required |
| SEC28 | OFF rollout/migrations/backups/FPM | BLOCKED | B804 prerequisites done, restore/FPM/OPcache and exact-source rollout remain open |

## Non-negotiable next release gates
1. Independently review the 48 historical guest-positive assumptions and the one safety-denied B815 fixture via a permitted mechanism, without rerouting the denied operation. Preserve full names and original JUnit evidence.
2. Generate the complete version-pinned WSTG4.2 / ASVS5.0 L2 **item-level** register with applicability, role/route/method, expected outcome and original evidence. No synthetic PASS and no invented 368 completed controls.
3. Execute isolated synthetic security/browser testing with exact source, then complete full CI with all original tests enabled. A zero-new-failure comparison is not whole-suite green.
4. Merchant/legal/payment/shipping approval, real SMTP with SPF/DKIM/DMARC, backup restore, actual FPM/OPcache provenance, owner buyer UAT and separately approved OFF rollout are required before public ON.

## Change and execution boundary
This B819 register is read-only analysis/documentation. No PHP/TS changes, test suppression, DB/backup modification, deploy, SSH, EAS, OTA, Play or storefront ON. Accepted functional ledger remains program 7/17 (41.2%), CMS 7/15 (46.7%), initial UI 6/6 (100%).
