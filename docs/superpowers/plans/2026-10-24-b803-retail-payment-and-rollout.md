# B803 retail payments and production preparation implementation plan

Goal: Exactly three buyer payment methods: COD, bank transfer, and payment at personal pickup/own delivery. Remove public deferred credit; preserve internal SubAgent/direct-sale credit. Prepare a concrete, bounded production rollout path with store OFF, not a public launch.

Authority: owner request 2026-10-24; live main c1d58ec97f6bc955e9eb4efaaaf30db30d408db7; PR93 baseline32def73e4145b00f51e672b7b1810b34d8fe44e4; host B798 Report804/805 and read-only Report810. Existing blocked index/runbook writes stay untouched.

Architecture: Reuse existing orders/payment ledger cash enum as unpaid in-person choice. Restrict cash to customer_pickup/own_transport. Operator must record verified collection; choosing payment does not create a payment, warranty or delivery. Preserve independent technical gates, signed merchant policy and actual SuperAdmin ON/OFF.

Tasks:
- [x] Read current authority and preserve the fixed17-milestone acceptance denominator.
- [x] Add failing real MariaDB tests for exact choices, old deferred rejection, unpaid cash lifecycle, courier incompatibility and retained COD/bank behavior. RED commitddf6b8ff0a89e3e15283b8b8efa9160d8297b93c, workflow40082427153: seven expected new-feature failures, zero errors.
- [ ] Update public-only policy, checkout, admin form and regression tests. No populated-table alteration and no internal deferred-payment changes.
- [ ] Run exact-head scoped/full regression checks, review immutable preference/financial boundaries and record limitations by name.
- [ ] Prepare one canonical integrity-checked operator step for affected source/backup gates before a controlled OFF rollout; do not rerun a generic B800 audit or infer PHP-FPM/backup readiness from old receipts.
- [ ] Archive code, scripts, evidence and new operations report on Google Drive; report precise progress and production next action.

Review focus: forged paid state, stale merchant signature, cash+courier mismatch, duplicate collection/delivery, internal credit regressions, dirty-host preservation. Author self-review only unless a real independent reviewer is available.
