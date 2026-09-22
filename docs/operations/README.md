# Operations report archive

This directory is the canonical append-only archive for Ald1n batch, recovery, checkpoint, and certification reports.

Rules:
- Every numbered operational report is stored in `docs/operations`.
- Old numbered reports are never deleted or rotated out when a new report is created.
- Failed and recovered attempts are retained as audit evidence.
- New reports use the next report number; recovery revisions may keep the same report number with a V2/V3 suffix.
- Application source documentation such as CMS/Mobile `TEST-REPORT.md` remains with the application and is not part of this operational batch archive.
