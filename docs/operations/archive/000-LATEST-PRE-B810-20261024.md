# CURRENT B809 — registered Kupac-only checkout coded; QA HOLD, no production deploy

Updated: 2026-10-24. Source: [Draft PR93](https://github.com/AldinAga/ald1n-project/pull/93), exact HEAD 01c07c6738fd8aa91d541ffcae238d000d3c3529. [Report819](819-BATCH809-REGISTERED-KUPAC-ONLY-CI-HOLD-20261024.md) supersedes previous B808 acceptance assumptions; read root AGENTS.md and operations supplement.

**Owner override:** nobody may submit an order without registration and sign-in as active non-retired customer/Kupac. Public catalog/cart browsing is still open. New public orders must have an authenticated customer user_id and receipt email belonging to that profile. Anonymous GET checkout redirects to login; POST must not create orders/inventory/attempts; staff are denied 403; store OFF remains 404. Self-service /registracija creates pending Kupac and emails existing single-use verified activation, not automatic purchase permission. Internal SubAgent/direct sale unchanged.

**Current status HOLD:** code and B809 synthetic test added in source PR93; historical anonymous-positive fixtures and Chromium still fail. Attempted legacy fixture adaptation was explicitly safety-blocked, was not committed, and must not be bypassed by SSH or an alternative write route. Initial observed CI 8 success/14, four failed due stale guest assumptions, two pending. Recheck fresh checks; do not claim B809 PASS. Previous B808 exact HEAD fa05307efafc067bf13437fe0851543f6e594af8 had 14/14 green for OLD (now obsolete) guest policy, not the new buyer-only policy.

**Release:** PR remains DRAFT not merged; production B798 unchanged, storefront OFF. Full OWASP 368-control assessment, merchant/legal/payment/delivery approvals, exact-source rollback, restore drill and FPM/UAT still OPEN. B804 Report815 host proof already accepted, no rerun. No owner SSH needed. Inode cleanup issue95 later.

**Progress:** accepted program 7/17=41.2%; accepted CMS7/15=46.7%; B809 source work does not add a finished production milestone. Keep status compact, keep CI failure identities/evidence. Previous index archived at [archive/000-LATEST-PRE-B809-20261024.md](archive/000-LATEST-PRE-B809-20261024.md).
