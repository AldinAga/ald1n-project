# LG-04 Wave5 + Laptop chargers combined source-only candidate

**Approval:** User approved all LG polish and charger catalog; explicitly prohibited **any EAS builds** until complete LG release approval.

**Base source:** PR #55 `63e0b6529c1660e2d52f9aae7294f2241ce00334`, stacked on earlier LG Waves PR #47–51.

**Safety approach:** Reuse the proven source of charger PR #54 `4f3ca8de9c9b9b4297b5b258a0903ede2fd12bb3`, which requires explicit dry-run/--apply instead of automatically executing a data migration. Add only the mobile charger label adaptation already present in isolated PR #58, *without* its potentially overlapping data migration. No new backend fields or variants. Separate integrated exact-head CI is source-only and tests PHP8.4/MariaDB 10.11.19, 226+ RN regressions, TypeScript, validators, and Android JS export. No EAS build or OTA.

**Review focus:** Avoid duplicate category migrations and double-created brands; only existing laptop brand relations; W slug unchanged; signed native source runtime unchanged; order item snapshots displayed solely to existing orders.manage scope; preserve inaccessible action filtering, Reduce Motion/Transparency fallback and the 200dp bottom scroll reserve.

**TDD:** RED combined test until charger files + mobile UI are present; GREEN exact PR HEAD, then Draft PR. No release approvals inferred.
