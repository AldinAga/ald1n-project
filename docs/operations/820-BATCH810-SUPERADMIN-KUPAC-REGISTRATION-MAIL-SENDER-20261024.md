# Report820 — B810 SuperAdministrator-controlled Kupac registration mail sender

DATE=2026-10-24
KIND=SOURCE_ONLY_CONFIG_AND_SCOPED_MARIADB_QA
PR=https://github.com/AldinAga/ald1n-project/pull/93
SOURCE_HEAD=c8730eaf90ee0dcbdb86185d3269f5808fd1b0eb
PR_STATE=DRAFT_NOT_MERGED
B809_B810_TARGETED=PASS_12_TESTS_160_ASSERTIONS_NO_ERRORS_NO_FAILURES
EXACT_HEAD_OVERALL_CI=HOLD_B809_LEGACY_GUEST_FIXTURES
PRODUCTION_MUTATIONS=0
STOREFRONT=OFF_LAST_HOST_ATTESTED
REAL_SMTP_DELIVERY=NOT_TESTED
OWNER_SSH_REQUIRED=NO
ACCEPTED_PROGRAM=7/17_41.2_PERCENT
ACCEPTED_CMS=7/15_46.7_PERCENT

## Implemented owner request

At SuperAdministrator > Settings > Javna prodavnica, a completely separate form "Email za potvrdu registracije Kupca" specifies the outbound From email and display name for **self-registered customer activation**. No customer-selected From field is honored. Owner manually attests that they control the address and it is authorized by their existing SMTP provider; the selected identity and SuperAdmin attestation fingerprint are stored in SettingsService. The updated From header applies to CustomerPortalInvitationNotification only when invoked by PublicBuyerRegistrationController via the public-registration parameter to CustomerActivationService. Preexisting operator-issued SubAgent/portal invitations keep the system MAIL_FROM_* identity. Ordinary order confirmation email and SMTP account credentials are unchanged.

Unconfigured, invalid, tampered digest, invalid inactive/retired signer or a production log/array-only mail transport blocks new public signup **before account or activation-token creation**. The registration form shows an unavailable message until configured. Registration remains role=customer pending, confirmed by one-time email link, and public ordering remains registered-Kupac-only. The settings form is SuperAdmin-only, throttled, CSRF-protected, and audited; it neither saves merchant legal policy nor revokes existing merchant approval nor toggles the store ON. Sender address/name validation prevents header control-character injection; app.url authority remains fixed against poisoned Host.

The requested sender is a MIME From header, **not a new authenticated SMTP mailbox**. Actual delivery requires the hosting provider to allow the sender and SPF/DKIM/DMARC alignment; no SMTP passwords, mailer account, live mail or production host were edited or tested. This remains an explicit release gate.

## Exact-head evidence

Source diff versus B809 56e0876b9ad2f19d7083d5182b9f2fcd51c87b2c: six runtime app PHP files, two Blade views, Settings defaults and routes, one new B810 test, and one existing grouped CI workflow; ZERO migrations, ZERO prior legacy guest test rewrites. PHP syntax checks PASS. New isolated real PHP8.4 / MariaDB10.11.19 feature suite `tests/Feature/PublicRegistrationMailSenderB810Test.php`: **6 tests / 76 assertions, 0 failures and 0 errors** from real JUnit, covering SuperAdmin vs admin/Kupac/guest, no orphan pending registration without approved sender, actual Laravel notification From header, mail origin resistance to spoofed Host, administrative invitations unaffected, merchant/SMTP toggles unchanged, attestation requirements, header injection and signer/digest invalidation.

Workflow run 40099486477, job 120888500659: B810 targeted stage concluded SUCCESS. The later B806 security stage remains FAIL because older B799–B808 scenarios still expect the now-disallowed guest checkout. Exact-head 14-workflow status at report creation: 8 success, four old guest-assumption failures, two still running. **Do not claim overall CI green, complete registration acceptance, real SMTP delivery or full penetration certification.** B809 legacy fixture adaptation had a provider safety denial; that blocked action was not repeated, repackaged or bypassed.

Evidence zipped artifact GitHub 12562301401, SHA256 `66468232467bb6a5c761d3e0968a2ccb30b0be8dca99cc28a9a4ef0c5967e52a`, 59222 bytes. Archived on Drive and read back (same bytes):
https://drive.google.com/file/d/1eXdQlxgfEPwu1Z8Sl6desdtGaiVdqgLV/view

## Remaining gates

Production remains unchanged B798, storefront OFF; owner Report815 B804 host source/backups already accepted. PR93 stays DRAFT, no deployment/SSH. Required: safely reconcile historic anonymous-only fixtures without evading the explicit blocked action, all exact-head CI, actual external registration mail delivery from chosen real mailbox, SuperAdmin merchant legal/privacy/shipping/payment approvals, full OWASP WSTG4.2/ASVS5.0.0 cumulative-L2 368-control applicability/test remediation and retest, restore drill, FPM/OPcache proof and buyer UAT. Android16KB and SDK58 retain separate gates. Final >400k-inode cleanup issue95 remains last phase, never delete now.

Functional acceptance ledger unchanged: program7/17=41.2%, CMS7/15=46.7%, UI6/6=100%. B810 is real source implementation plus scoped PASS, not a production milestone.

## B810 integrated registration and sender QA addendum — exact source c8730eaf90ee0dcbdb86185d3269f5808fd1b0eb

A synthetic-only update to the NEW B809 registration test (not any safety-blocked B799–B808 historical fixture) configured an explicit SuperAdmin-approved synthetic sender as required by B810. The B806 grouped workflow then ran two isolated real PHP8.4/MariaDB10.11.19 suites: **12 tests / 160 assertions, zero errors, failures or skips**: six B809 customer signup/email activation and six B810 From identity, role/anti-spoof settings tests. Precise JUnit: B809 6/84 and B810 6/76.

Final-source scoped run: 40100121787, artifact12562785496 SHA256 `b598c5edaa4144b4ef7e82f607d2e908d421f1a5b911bd6ec4649bcef31487c9`, 63149 bytes; [Drive ZIP](https://drive.google.com/file/d/1-rzffPZO1MbVO8OOyw9KQgzwt8sTnTT3/view) independently read back and local SHA256 verified. A subsequent B806 stage still fails because prior *guest-success* assumptions conflict with the owner's registered-only policy. **Do not report exact-head CI green, a complete pentest or SMTP delivery.** No production/SMTP/host or legacy test batch writes.
