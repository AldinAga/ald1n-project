# B996 — Report971 B995 encrypted local operations archive PASS; private off-host upload gate OPEN

**Date:** 2026-10-30 (Europe/Belgrade). Actual source: [authentic owner Report971](971-B995-ALD1N-OFFHOST-OPS-ARCHIVE-20261030-140251.md), 2,734 bytes, file SHA256 `9aa8571b62a2025846830efb565ba3ae9f49156246ce75d791203b87cb3c56fc`. Report archived on GitHub as its original bytes, and [Google Drive exact-content verified copy](https://docs.google.com/document/d/1tLx_ZDNx92tjCbJVGyLe5QXKT4JrUwclUCvXJDRF9ZI/edit).

## Host-attested PASS (owner report)

- B995 script SHA256 `27076ff8fe4574aaca100206caa844f5b1ab5354b2e2fa439e592def39c845f7`, 39 project-specific roots, 2 ambiguous roots deliberately excluded, 21,185 distinct selected non-vendor/non-runtime operator scripts/reports.
- `TAR_CREATE_RC=0`, `GPG_ENCRYPT_RC=0`, `GPG_DECRYPT_RC=0`, `TAR_LIST_RC=0`, `IMMUTABLE_SOURCE_BEFORE_AFTER_HASH=PASS`.
- Private encrypted output (owner's CloudLinux account only):
  `/home/icaffeco/.ald1n-offhost-export-b995-20261030/ALD1N-ALL-OPS-SCRIPTS-REPORTS-B995-20261030T130651Z.tar.gz.gpg`
- Exact bytes **221,001,337**; complete-file SHA256 **`cba29ee1c6b708a3c01010aee25f738524b9fceb8a11876369b587063d273f1a`**; internal NUL-path manifest SHA256 **`aa0918988199a0d318afc2bb5d4e9b76d27d7aa0872df38bfa462b52a41d721d`**.
- Report971 was created after ciphertext snapshot and is **not** within that ciphertext; it was separately archived to GitHub and Drive in this continuation, meeting the report-specific safeguard.
- **Zero files deleted**, no production CMS, private SQL/media, EAS, Play or other domain changes.

## Independent Google Drive status

The assistant queried the private dedicated [Drive scripts/reports archive folder](https://drive.google.com/drive/folders/1sl0VNgX-cX8fywqg-4TmS8TeiidFwqJ2) on Oct 30; the returned list was **empty**. The 221 MB ciphertext exists only on CloudLinux according to authentic Report971. Agent has no CloudLinux SSH/SFTP access, and cannot transfer bytes it cannot read via connected tools. **OFFSITE_UPLOAD=HOLD**.

**Owner one-time transfer:**
1. Download only the ciphertext from the exact CloudLinux path above through authenticated WinSCP/SFTP or cPanel File Manager (enable hidden files).
2. On owner local workstation compare full byte size and SHA256 before upload (PowerShell `(Get-Item -LiteralPath '<downloaded .gpg>').Length`, `(Get-FileHash -Algorithm SHA256 -LiteralPath '<downloaded .gpg>').Hash`). Never paste or supply the GPG passphrase, plaintext TAR, private SQL/media or credentials.
3. Upload unchanged `.tar.gz.gpg` to the **private** Drive scripts/reports archive folder, no public/link-wide sharing. Return actual Drive file link or ID.
4. Agent reads authenticated Drive file metadata, compares exact bytes and available remote checksum to the host attested values. If Google does not expose SHA256, independently download encrypted Drive copy to owner system and verify `SHA256=cba29e...`; preserve GPG passphrase securely for eventual recovery. Do not mark offsite PASS based solely on matching filename.
5. No deletion, no retry of B992/B994/B995, and no new GPG archive. After offsite identity/restore PASS, prepare a distinct exact-path deletion manifest, protect unrelated domains/shared services and active `mobile-build.ald1n.com`, and separately maintain B959 current SQL/private recovery gate for local backup pruning.

**Not part of the 21,185 scripts/reports GPG:** current SQL database, Laravel private media, active source backups and arbitrary root `/home/icaffeco/backups` contents. Zero-local-backups target remains HOLD until independent current, restore-capable off-host SQL/private package is verified. The giant 221 MB ciphertext is **one inode**, so its eventual relocation isn't the main inode reclamation; high-inode disposable clones/temp/dependencies remain the main verified *candidates*, not yet deleted.

## Next state

- `PASS_B995_ENCRYPTED_LOCAL_OPS_ARCHIVE`.
- `OFFHOST_GOOGLE_DRIVE_UPLOAD=NOT_PERFORMED` until owner transfers ciphertext.
- `B996_GITHUB_DRIVE_REPORT_ARCHIVE=PASS`.
- `ALD1N_NONPRODUCTION_DELETION=HOLD`, zero deletions.
