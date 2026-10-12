# Report 641 - Batch632 two backup verification and unarchived operations guard (READ ONLY)

Script: `/home/icaffeco/ald1n-project/incoming/ald1n-b632-backup-verify-archive-gate-v1.sh`

Script SHA-256: `c1b3dba55b1f214f98999d3939f30b13d2057df8cd66b864a1b1676635fd81f4`

GitHub main: `3b91fad8aaffb98cd3157a4e299c9dfd86969134`

Report640 SHA-256: `3e0134b4d8174782ecd30751a1b202fdcb43d0a3883e851fb89bf7f5813d4ef9`

Report640 Git blob: `85136f32acedd24df0684dda4f01858a6373945a`

Execution: 2026-10-12T13:35:42+02:00

## Safe terminal evidence (no raw backup paths, secrets or file contents)

```text
=== REPORT640 IMMUTABLE ARCHIVE GATE ===
GITHUB_MAIN=3b91fad8aaffb98cd3157a4e299c9dfd86969134
REPORT640_SHA256=PASS
REPORT640_GIT_BLOB=PASS
=== HISTORICAL OPS EVIDENCE PROTECTION ===
/home/icaffeco/ald1n-project/incoming/ald1n-b632-backup-verify-archive-gate-v1.sh: line 211: /dev/fd/63: No such file or directory

```

## Final status

BATCH632_BACKUP_FILE_VERIFY_AUDIT_RC=1

AUDITED_COMMAND_RC=1

TEE_RC=0

PRODUCTION_SOURCE_MUTATIONS=NONE
REPORT641_BACKUP_DELETE=NONE
