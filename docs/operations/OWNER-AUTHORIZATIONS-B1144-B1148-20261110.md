# Owner authorizations and production changes, 2026-11-08 to 2026-11-10 (B1144 to B1148)

Author: Ald1n <https://ald1n.com>

This record lists what the owner explicitly authorized in chat for the production host (CloudLinux shared hosting,
cms.ald1n.com) and what each owner-run script changed. Reports 1104 to 1113 sit next to this file in
`docs/operations`. Every production step was an owner-run SSH script; no agent had SSH access.

## Owner authorizations (given in chat, per action)

- **Working rules (2026-11-08):** all source code is written by Claude; Claude asks before any merge to main, before any
  hosting step and before turning the Storefront ON. Every host change is a script the owner runs himself.
- **FileZilla uploads (2026-11-08):** Claude may upload scripts it prepared into `/home/icaffeco/ald1n-project/incoming/`
  through the already connected FileZilla session, and download reports. No deleting or editing of other host files.
- **Targeted OPcache invalidation (2026-11-08):** per-file `opcache_invalidate` of CMS scripts through a token-gated
  temporary probe is allowed; `opcache_reset` and any global clear are never allowed.
- **Production migration (2026-11-08, "Da, pisi B1145"):** `migrate --force` of exactly three migrations
  (`2026_10_23_020000_create_public_order_accesses`, `2026_10_23_020100_create_public_checkout_attempts`,
  `2026_10_24_030000_add_retail_customer_role`) after a fresh verified backup.
- **Environment edit (2026-11-08):** `PUBLIC_STOREFRONT_ENABLED` and `PUBLIC_CHECKOUT_ENABLED` set to `true` in the
  retained `.env`, and restoration of the original `config/public_storefront.php` (clamp removal). The storefront stays
  closed until the SuperAdmin ON/OFF switch is used.
- **Storefront ON (2026-11-09):** the owner turned the storefront ON in the admin himself. Claude never wrote the switch.
- **Merge to main (2026-11-09 and 2026-11-10):** PR #152 (legal pages, editable texts, ON checklist) and PR #155
  (catalog redesign, Liquid Glass, theme switcher, shared logo; includes the closed PR #154) were squash-merged after
  green CI. Main is now `eca7755c6efe05143a6d3cd19f8c9e70b9ee3515`; its `apps/cms/current` tree equals the code that
  B1147 and B1148 installed.
- **Indexing policy (2026-11-09):** the shop must NOT be indexed. The server-wide `public/.htaccess` header
  `X-Robots-Tag: noindex, nofollow, noarchive` stays, and the installers assert it.

## What each production script changed

| Report | Script | Result |
|---|---|---|
| 1105 | host facts probe v3 | read-only; uapi unusable from SSH, same filesystem, host on Laravel 13.20.0 |
| 1104 | B1143 | held at the uapi docroot gate, no change |
| 1106 | B1144 | `PASS_INSTALLED_OFF`: release switched to main (Laravel 13.40.0), Storefront OFF clamp |
| 1107 | post-install verify v4 | read-only; install healthy |
| 1108 | B1145 | `PASS_MIGRATED_UNCLAMPED_STOREFRONT_CLOSED`: backup 188, 3 migrations, clamp removed, env flags true |
| 1109 | B1146 | `PASS_INSTALLED_LEGAL_OVERLAY_STOREFRONT_CLOSED`: 21 files, public legal pages, editable texts, ON checklist |
| 1110 | save diagnosis v5 | read-only; showed that a checked approval box rolled back the whole save |
| 1111 | B1147 (first run) | preflight HOLD, no change (server-wide noindex header was not modelled) |
| 1112 | B1147 | `PASS_INSTALLED_CATALOG_OVERLAY_SHOP_STATE_UNCHANGED`: 8 files, catalog redesign |
| 1113 | B1148 | `PASS_INSTALLED_GLASS_OVERLAY_SHOP_STATE_UNCHANGED`: 11 files, Liquid Glass, theme switcher (POST /tema), shared logo |

## Not authorized, not done

- No change to the storefront switch by any script (`public_storefront_open` was written only by the owner in the admin).
- No `opcache_reset`, no route cache, no migration after B1145, no `.env` edit after B1145.
- The merchant approval was never written by a script.

## Open items

- Add the `shop_theme` cookie to the "Kolačići" legal text (admin editor; the approval must then be confirmed again).
- Legal texts are a template and need a lawyer's review (Zakon o zaštiti potrošača "Sl. glasnik RS" 35/2026, ZZPL).
- A logo upload that the server marks invalid is skipped silently; an error message is still to be added.
- OWASP WSTG/ASVS control census (368 controls) is not executed; CSP issue #101 and the draft security PRs are open.
- SuperAdmin "E-mail (SMTP)" settings page (encrypted password, test mail) is planned after the shop is live.
- Do not re-run B1144 to B1147 on the host; their manifest gates see the later overlays.
