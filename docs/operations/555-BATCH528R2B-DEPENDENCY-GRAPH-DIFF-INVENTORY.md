# Batch528R2B - dependency graph and diff inventory recovery

## Authority

- Main remains `b5b942e645d28d3ed5f248eaae4180ebe9a54ee6`.
- Audit parent remains `d9ea4c22c0b7294c8cf879b29fbcbdd9b73beb22` on `audit/batch528-native-release-gate`.
- Batch528R2 failed locally because the default shell could not find Node.
- Batch528R2A proved the canonical CloudLinux Node binary is `/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node`, version `v22.23.3`.
- Batch528R2A then achieved the intended TDD GREEN for dependency graph inventory, but stopped before commit because its changed-file comparison omitted the newly created untracked operations document.

## Technical correction

The release runtime dependency inventory now uses `configuration.incoming.resolutionResult` and walks resolved components instead of calling `resolvedArtifacts`. Unresolved dependency results remain fatal. This avoids selecting Android artifacts merely to inventory module versions.

The local recovery runner also inventories both tracked modifications and untracked files during diff review. The operations document is therefore part of the exact expected change set instead of being invisible to `git diff --name-only`.

## Verification and release boundary

- TDD RED must identify the dependency-graph regression test before the fix.
- Full Batch528 contract plus wiring tests must pass after the fix.
- Only the snapshot helper, wiring regression test and this operations record may change.
- The resulting commit may be pushed only as a fast-forward child of the current audit branch head.
- GitHub Actions must still perform the real composite probe, Kotlin RED/GREEN, release assemble/bundle, R8 mapping and lint gates.
- No main push, production checkout mutation, EAS build, EAS submit, OTA publication or database write is authorized by this recovery.
- Build25 remains unauthorized until the complete audit and all remaining release findings are closed.
