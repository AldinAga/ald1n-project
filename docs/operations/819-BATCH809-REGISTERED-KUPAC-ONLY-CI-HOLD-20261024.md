# Report819 — B809 registered Kupac only, source implementation, CI HOLD

DATE=2026-10-24
CURRENT_PR=https://github.com/AldinAga/ald1n-project/pull/93
SOURCE_HEAD=01c07c6738fd8aa91d541ffcae238d000d3c3529
SOURCE_STATE=DRAFT_UNMERGED
RESULT=HOLD_CI
PRODUCTION_CHANGE=NONE
STOREFRONT_PRODUCTION=OFF_AS_LAST_HOST_ATTESTED
OWNER_SSH=NOT_NEEDED
PROGRAM_ACCEPTANCE=7/17_41.2_PERCENT
CMS_ACCEPTANCE=7/15_46.7_PERCENT

## Owner's definitive change

"No one can place an order without registration." This cancels the prior B799-B808 guest-checkout requirement. Browsing the catalog and composing an uncommitted cart can remain public. Checkout requires an authenticated active non-retired role=customer/Kupac; anonymous GET uses login redirect, and anonymous POST is rejected (JSON HTTP401), before any sale/stock/attempt/commission/payment writes. Internal operators continue to be denied public checkout. No source code deployment is permitted while tests conflict.

## In-development implementation in PR93

Commits up to 01c07c6738fd8aa91d541ffcae238d000d3c3529 replace the anonymous checkout path in PublicCheckoutController and PublicRetailCheckoutService, including a second domain-level buyer check under DB lock and replay ownership check, require registered buyer in newly issued order capability, bind transactional email/confirmation to real account, show registered-only UI, and add self-registration at /registracija. New accounts are Kupac/pending with no group/permissions, created with an unguessable password; the existing 72h one-time email activation sets password and marks the account active/verified. Registration route has CSRF/throttle/Turnstile/pending re-send cooldown and no public role selection. Customer login/Google login fallbacks lead to public cart, not internal employee dashboard. Activation mail links use the trusted app base origin, never the submitted Host.

Added synthetic StorefrontRegistrationOnlyB809Test.php to exercise guest denial, domain fallback, signup+activation, existing employee role isolation, email impersonation rejection, registered checkout and OFF. These are **tests added but NOT yet proven green**. The current older B799/B802/B806/B808 test fixtures and Chromium/HTTP scripts still assume anonymous success; the B809 final QA is therefore explicitly HOLD, not falsely reported green.

## Evidence and blocker

At initial observed exact-source GitHub checks: 8 of 14 succeeded, with guest-expecting B799 retail, B802 retail role, B806 security and B799 Chromium failing; B803 full-suite and Liquid Glass still pending. Legacy failures show HTTP302 from new registration gate where old tests expected HTTP200 checkout. This is an expected test-contract conflict, not proof the new registration/account flow has passed. Any further final CI state must be freshly fetched.

An attempted batched modification of historical test fixtures was blocked by the tool safety gate. This is an explicit action-level block. The test changes were NOT published. Do not repurpose another tool, shell, file package or SSH to evade it. Owner is not required for an SSH script merely to publish source/tests. Preserve existing red/green history and all old financial, inventory, concurrent lock and warranty assertions; new tests must not simply delete guest tests, but convert their valid business invariants to real registered Kupac fixtures.

Production remains B798 source c4a5f060cc00b5d1be0e4d8068309524a4b99c99, storefront OFF as last host attested. B804 host prerequisite Report815 already accepted; do not repeat. Merchant/legal/shipping/backup restore/FPM/complete 368-control WSTG+ASVS security audit gate remains. Mandatory final inode cleanup issue95 still deferred; no deletion.

NEXT: obtain an authorized, safe path for reviewing and adapting historical test fixtures without bypassing the blocked action; rerun targeted regression and full exact-head CI, then continue OWASP applicability/pentests on isolated data. No merge, deployment, toggles or Play builds until full gates.
