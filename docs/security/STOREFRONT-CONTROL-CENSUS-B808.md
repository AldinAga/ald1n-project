# Ald1n storefront security control census — B808 (initial unassessed register)

Created: 2026-10-24. Owner release gate: **NOT COMPLETE**.

- **115** unique WSTG4.2 checklist scenarios from the official OWASP WSTG checklist file, pinned commit `d0f7470ec93d1d2635802f804f58875268bb009d`.
- **253** ASVS 5.0.0 requirements for **cumulative Level 2** (70 Level1 +183 Level2) from the official tagged English CSV `v5.0.0`.
- **368** total rows requiring per-control applicability, attack surface, verifier, evidence and remediation decisions. All initially have `applicability=REVIEW_REQUIRED` and `status=NOT_RUN` by design. **Zero controls are claimed to be fully tested or certified merely by populating this register.**
- The 92 ASVS Level3-only requirements are **not silently marked PASS or N/A**. They are outside the pinned cumulative L2 baseline; review any additionally applicable L3 or project-specific risk separately.
- Prior B806/B807 and B808 targeted evidence is referenced only as partial coverage notes; targeted test PASS does **not** close an entire OWASP control or grant release.

Sources:
1. Official [WSTG version 4.2-based checklist](https://github.com/OWASP/wstg/blob/d0f7470ec93d1d2635802f804f58875268bb009d/checklists/checklist.json). The companion README says the checklist is based on v4.2; pin the exact immutable checklist commit rather than a moving master.
2. Official [ASVS5.0.0 English requirements CSV](https://github.com/OWASP/ASVS/blob/v5.0.0/5.0/docs_en/OWASP_Application_Security_Verification_Standard_5.0.0_en.csv).
3. Local scope: [mandatory storefront penetration plan](STOREFRONT-PENETRATION-TEST-PLAN-20261024.md) and [Report817](../operations/817-BATCH807-REAL-HTTP-SECURITY-GROUPED-QA-20261024.md). B808 role/IDOR tests must have their own exact-source outcome separately.

Procedure: for every control, review **REVIEW_REQUIRED -> APPLICABLE or NOT_APPLICABLE**, write the rationale for the latter, specify affected route/role and evidence method, and execute/retest isolated attacks as applicable. Use `NOT_RUN`, `FAIL`, `PASS`, `BLOCKED`, `NOT_APPLICABLE` only with authoritative evidence. Failed or blocked gates cannot be silently counted as passed. No actual production attack without explicit authorization, and no claim of 'every possible attack' beyond this versioned inventory.

Evidence and business-specific workflows SEC01-SEC28 remain separate; these 368 rows do **not** replace item-specific manual assessment, actual authenticated DAST, dependency checks, legal merchant policy or controlled release acceptance.

[Open machine-editable control register](STOREFRONT-WSTG42-ASVS500-L2-CENSUS-B808.csv).
