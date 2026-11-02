# ALD1N B1045 — One-time real operator SMTP probe (M10)

**Date:** 2026-11-02 (Europe/Belgrade)  
**Current source baseline at preparation:** GitHub `main` `f407bf65923d37c3ddbe10b939dbcdbf3b69734a`; B1044 actual post-merge 80/80 PHPUnit + 586 assertions PASS, run `41506876578`.  
**Status:** Script **PREPARED and local static QA PASS 18/18**, **not yet run** on CloudLinux, **no real email has been sent**.  
**Android builds:** ZERO; no EAS, OTA or Play operations.

## Why this is next

- Genuine B962 CloudLinux owner report already proves Laravel PHP8.4.27 + LiteSpeed, exactly 12 M10/M11 production source files installed and the additive recovery ledger migration. Do not rerun the deployment, migration, B955 SQL restore, B959 private-file restore or B1044 tests.
- B1044 authentic GitHub 80-case CI proves the merged source passes **69 M10/M11 cases + 7 recovery cases + 4 disposable Mailpit SMTP/MariaDB cases**. Mailpit cannot prove actual external delivery.
- Current production-scope `WarrantyActivationRecoveryCommand.php` source Git blob `7b8568f6fc548cfff9a0046f9564bb10561eefd6` implements `--probe-alert`, which sends a single fixed synthetic email, without accessing any individual order/warranty or enabling real recovery. `config/warranty_recovery.php` source Git blob `7701ccdb958a59adcd78f41843f478d29fa8e2df` defaults execution and alerts OFF. These are the exact source hashes the script pins.

## Artifact integrity

- File: `B1045-ALD1N-M10-ONE-TIME-SMTP-OPERATOR-PROBE.sh`
- **Full SHA-256:** `2520b31dcdcc024e72ea20e70b82199bdad7cdcc199355554700115488a8d516`.
- Embedded self-normalized SHA-256: `bf6389250ebd8be9a1bb46e914de58523ccbe68369c81ef8da0f48436eeadc32`.
- Local checked: Bash syntax, embedded real PHP8.4 lint, 18/18 positive/negative fixture assertions, self-tamper refusal, no process-substitution `/dev/fd`, no Git checkout/reset/clean/pull, and no EAS/native build command. This is **not** production SMTP proof.

## One CloudLinux command — only when operator intends to send ONE TEST email

Transfer the **unmodified** script to exactly:

`/home/icaffeco/ald1n-project/incoming/B1045-ALD1N-M10-ONE-TIME-SMTP-OPERATOR-PROBE.sh`

Run in the owner's interactive CloudLinux terminal as the `icaffeco` account:

```bash
bash /home/icaffeco/ald1n-project/incoming/B1045-ALD1N-M10-ONE-TIME-SMTP-OPERATOR-PROBE.sh --probe-alert
```

It first validates its source digest, physical path, user identity, deployed command/config Git hashes, PHP8.4, Laravel production environment and real configured SMTP transport. It **refuses** disabled/unsupported mailer, existing enabled recovery or alert settings, absent command, known Mailpit target, modified host code and duplicate attempts. It then privately prompts for the intended operator email address (no email appears in the report), and requires the exact human confirmation:

`SEND ONE B1045 TEST MAIL`

No SMTP call happens without that confirmation. A new private, atomic once-only marker is written under `$HOME/.ald1n-b1045-operator-probe/` before SMTP dispatch; **do not rerun if a marker exists**, even if the resulting mail is delayed/unknown. The script does not alter `.env`, application config cache, scheduler, inbox recipients, business database or application source. Runtime alert opt-in exists only in the one PHP process. It does **not** run the `--execute` option.

**Optional no-mail inspection**, if desired instead of the send action:

```bash
bash /home/icaffeco/ald1n-project/incoming/B1045-ALD1N-M10-ONE-TIME-SMTP-OPERATOR-PROBE.sh --preflight
```

This read-only mode never sends an email and never creates the one-time marker.

## Expected output and evidence

Successful SMTP transport return is displayed as:

`B1045_RESULT=PASS_SMTP_TRANSPORT_ACCEPTED`

The script writes one sanitized report in the existing `docs/operations/` folder named `B1045-M10-ONE-TIME-SMTP-OPERATOR-PROBE-<UTC>-<PID>.md`. Send that report back to the project. **Operator must additionally confirm the actual message arrived in their inbox** (subject: `[TEST] Ald1n warranty recovery operator alert`, content contains no customer/order data). `WARRANTY_ALERT_PROBE_SENT` / transport success by itself is not inbox delivery. In all outcomes the report states `ACTUAL_INBOX_DELIVERY=OWNER_CONFIRMATION_REQUIRED` until the operator confirms.

If any `HOLD` occurs, do **not** retry the same send to work around the one-time guard; use the original sanitized report to investigate. Any follow-up requires a separately scoped gate/review, not duplicate unsolicited emails.

## Formal milestone boundaries

- M17 SDK58 Android AAB vCode29 16KiB binary proven; owner accepted as functional PASS; no new Android build.
- M10 source merge, real Laravel production deploy and 80-case changed-source integration QA already PASS; M10 live operator SMTP inbox acceptance is **PENDING** until this one-time on-host probe and confirmation.
- M11 recipient revocation and local mail/push tests are in the 69 cases, but **real Expo push-provider authorized receipt UAT remains OPEN**. This B1045 email does not substitute for Expo push.
- The public storefront M13–M15 stays OFF; B803 56 unresolved historical failures remain unwaived, B815 safety-denied test untouched.
- GitHub `docs/operations/000-LATEST.md` is historically stale due previous denied mutation; do not bypass that denial. The canonical Drive runbook has been reviewed; archive this B1045 handoff as a chronological addendum.
