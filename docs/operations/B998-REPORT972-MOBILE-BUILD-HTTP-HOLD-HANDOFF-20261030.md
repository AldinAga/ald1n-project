# B998 - Report972 HTTP baseline diagnostic / HOLD

Date: 2026-10-30 (Europe/Belgrade)
Repository: AldinAga/ald1n-project
Source authority (GitHub main verified before preparation): `84d8ebf37f1ab664480f07b8629ebd314639d6e5`

## 1. Authentic host report

Original user-uploaded file: `972-B997-VERIFIED-GENERATED-INODE-CLEANUP-20261030-160918.md`
Original file SHA-256: `a9860d38b4021fdc7e3bb928e65145ac23d1156a70348cb92e9f7b6ce3e0d651`
Report UTC: `2026-10-30T16:09:18Z`

Report972 says:

- B997 source hash and B995 local ciphertext SHA-256 verification PASS.
- `HOLD_B997_MOBILE_BUILD_VHOST_HEALTH_BASELINE; DEL=0; SKIP=0`.
- No candidate was deleted or skipped, and the gate occurred before the dynamic inode census and per-target safety review.
- This is NOT a successful inode cleanup and provides NO measured inode reduction.
- The previous B997 `findmnt` blocker was bypassed through the separate read-only `/proc/self/mountinfo` compatibility wrapper. The ORIGINAL B997 was not modified.

B997 `http_code()` executes HTTPS GET with curl and accepts HTTP status 2xx/3xx/4xx only if the transport command succeeds. A 5xx HTTP status, DNS lookup failure, TLS validation failure, connection refusal or timeout can all lead to the same B997 HOLD marker, because B997 did not record curl RC or the response code before stopping. **The cause cannot be distinguished from Report972 alone.**

The original script's CMS HTTP GET had returned an accepted status just before the mobile-build test; that does not prove the CMS's authenticated functional health.

## 2. Scope of the read-only next step

B998 script: `B998-MOBILE-BUILD-VHOST-READONLY-DIAG.sh`
Full script SHA-256: `1f63eb51e6c45956079a1d2c572bf4f6441af52b3896d87c080f594e8e08b2bc`
Script's normalized built-in self-hash: `b86f3c3e5282359ad3595dbb3a84e6acea413081086cc08df2bd7e3bca3dc3f4`

The single bounded B998 CloudLinux operation:

1. Verifies the original `Report972` exact SHA and its source script's own checksum.
2. Uses only read-only GET requests against the exact `https://cms.ald1n.com/` and `https://mobile-build.ald1n.com/` hostnames.
3. Reproduces original curl flags and records transport RC and 3-digit HTTP code, with three comparative diagnostics: `-q` ignore curlrc, IPv4 and HTTP/1.1. Comparative probes never override original baseline result.
4. Records DNS lookup pass/fail and proxy configuration presence but never prints IP addresses, cookies, headers, environment values or response bodies.
5. Generates only the next local numbered Markdown operations report (following Report972); never runs B997, deletes caches, changes DB/app/service configuration, executes a build or modifies other domains.
6. Returns HOLD when the exact B997 mobile-build baseline remains unacceptable. If a current baseline passes, it reports `PASS_...DIAGNOSTIC_ONLY` - this is not a deletion authorization.

The application service is not to be restarted or given a health-check bypass as a recovery shortcut. Full B997 original exact-target inode/process/cron/mount/HTTP gates and human typed confirmation remain mandatory if cleanup is reconsidered after diagnosis.

## 3. Local QA and limits

- `bash -n`: PASS.
- Built-in `--selftest` checks SHA and Bash syntax: PASS.
- Isolated synthetic test scenarios: 19/19 PASS (HTTP 200/302/403, HTTP 500/503, DNS error, timeout, report numbering, Report972 preservation, tamper rejection, invalid mode).
- Actual CloudLinux HTTPS/DNS/proxy behavior: NOT VERIFIED - awaiting the B998 host report.
- Separate sandbox internet probe could not resolve either custom hostname; this is NOT evidence of host-side DNS failure, because it was run outside the user's hosting environment.

## 4. Operator action

Download and place the exact original script inside:
`/home/icaffeco/ald1n-project/incoming/B998-MOBILE-BUILD-VHOST-READONLY-DIAG.sh`

One command:

```bash
bash /home/icaffeco/ald1n-project/incoming/B998-MOBILE-BUILD-VHOST-READONLY-DIAG.sh --run
```

Return the next numbered `docs/operations` report. No deletion command should be run on the basis of Report972.

## 5. Protected state

Keep all business SQL, private files, the two current restore-ready local backups and encrypted B959 history until current full offsite SQL/private recovery with independent restore is proven. B995 full scripts/reports offsite archive is verified but is not a current SQL recovery.

B990 AAB release one-shot state, active `mobile-build.ald1n.com`, Laravel vendor/storage, `.git`, other hosted sites and global npm remain excluded from B997. No Android build, Play submit, OTA, SDK upgrade, CMS deployment or production database change from this B998 preparation.

Accepted milestone ledger stays **program 7/17=41.2%**, **CMS 7/15=46.7%**, **original UI 6/6=100%**. Maintenance is not an additional accepted feature milestone.
