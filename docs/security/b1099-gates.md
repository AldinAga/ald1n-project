# B1099 security gates: gitleaks allowlist and npm audit policy

Workflow: `.github/workflows/b1099-source-security-qa.yml`. Config: `.gitleaks.toml` (gitleaks v8.30.1, default rules plus the entries below).

## gitleaks

The scan runs on the tracked tree and on the full HEAD history with `--ignore-gitleaks-allow` (inline `gitleaks:allow` comments are ignored) and `--redact=100`. Both scans must report 0 findings. No baseline file is used: allowlists apply to history scans too, and a baseline would need commit metadata that we do not want to publish.

Every entry is rule-scoped (`targetRules`). The remaining entries also require the path and a matching source line (`condition = "AND"`, `regexTarget = "line"`), so a new secret in the same file still fails unless it sits on an allowed line.

| Class | Scope | Why it is safe |
| --- | --- | --- |
| Handover reports | `docs/operations/*.md`, rule `generic-api-key` | Batch reports quote commit hashes, state ids and digests. Other rules (cloud keys, tokens, private keys) still fire in these files. |
| Firebase client key | `apps/mobile/current/google-services.json`, rule `gcp-api-key` | The Android client key is public by design and ships in every APK. It must be restricted (package name, SHA-1, API list) in Google Cloud console. |
| CMS test fixtures | 5 files in `apps/cms/current/tests/Feature/`, lines with `'password'`, `'password_confirmation'`, `'password_hash'`, `'checkout_key'` array keys or `activate($plainToken,` | Fake values in test arrays. |
| Example Idempotency-Key | two CMS docs and `apps/mobile/current/docs/BACKEND-PREFLIGHT-20260807.txt`, `Idempotency-Key:` lines | Random example request ids. |
| File hash | `apps/cms/current/config/release.php`, the `public/.htaccess` line | Integrity pin, not a credential. |
| Disposable CI database | `.github/workflows/b1049-m13-independent-catalog-isolation.yml`, `MARIADB_ROOT_PASSWORD:` line | Password of a throwaway container that lives only inside that job. |
| Identifier names | `apps/mobile/current/scripts/batch528-native-contract.mjs`, `CreateRestoreCredentialRequest` line | Android API class name, not a secret. |

The workflow proves the allowlist is not a blanket bypass: a synthetic secret in a non-allowlisted path (`apps/cms/current/app/`), a look-alike test-fixture line in a non-allowlisted path, and a synthetic token of a different rule inside each allowlisted file class (`google-services.json`, `docs/operations/`, a test fixture) must all still make gitleaks exit 10.

### Adding an entry

1. Confirm the value is not a credential. If it could be one, rotate it and fix the source instead.
2. Add a `[[allowlists]]` block with `targetRules`, an anchored path regex (keep the `(?:.*/b1099-tracked-tree/)?` prefix, the CI scan reports absolute paths) and, for single lines, `condition = "AND"` with a `regexTarget = "line"` regex.
3. Put a one-line comment above the block saying why it is safe, and add a row to the table above.
4. Never paste a secret value into the config, this doc, a commit message or a PR.

## npm audit

`npm audit` runs on `apps/mobile/current/package-lock.json` with all severities in the report. The full counts per severity and the package names are printed in the job log and in the job summary. The job fails when high or critical findings exist (`--audit-level=high`), and also when the report cannot be parsed or has no severity counts (network error, missing lockfile), so a broken audit never passes. Moderate and lower findings are reported but do not block; they are reviewed when the Expo SDK is upgraded. `composer audit` stays strict: any advisory or abandoned package fails the job.
