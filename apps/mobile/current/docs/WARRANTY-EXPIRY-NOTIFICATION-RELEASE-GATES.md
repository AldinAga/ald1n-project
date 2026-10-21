# Warranty expiry alerts — Android release gate

**Scope:** warranty EXPIRY reminders only, not ordinary warranty issue/update/maintenance events.
**Default:** OFF for new and existing users, independent of global Push, Email or Inbox settings.
**Explicit opt-in:** `warranty_expiry_alerts = true` through authenticated Notification Settings only.
**Owner rule:** recipient MUST equal the customer attached to the immutable `order_item -> order -> user` chain for the specific warranty. Warranty `user_id`, editor, supplier, assignee or global CMS access does NOT grant eligibility.

## Implementation contract

- Backend `OperationalNotificationService` blocks any warranty event containing `expiry`/`expir` unless preference is explicitly true, `warranty_id` exists, and `ProductWarranty::strictCustomerOwner()` returns precisely the intended recipient.
- Additive `notification_preferences.warranty_expiry_alerts` BOOLEAN DEFAULT FALSE. Existing rows remain false until explicit selection.
- All notification channels (Inbox, Email, Push) pass through the same server send guard.
- Category is independent of general `warranty_updates`. Turning on general updates must NOT turn on expiry alerts.
- No global warranty-expiration broadcast, no all-CMS-users fallback, no automatic backfill of delivery events.
- Only explicit, verified source-side changes are in this PR; expiry cron/scheduler is intentionally NOT activated.

## Release gates (all HOLD until attested)

1. Exact-head PHP8.4, MariaDB10.11, Node22 TypeScript, validator and Release25 regression gates.
2. Separate production DB backup with checksum and migration dry-run, then separately authorized migration. Do not run migrations automatically.
3. Production audit for preference defaults and ownership sample of user A vs B, missing warranty/order item, old existing users, revoked/archived items.
4. Only after source + CMS readiness, integrate into the final LG04/Build release candidate.
5. Physical Android UAT: OFF first-run, OFF after update, explicit ON/OFF persistence, wrong-customer denied, no global alerts, no accidental enabling from general warranty updates.
6. Exact native/OTA compatibility checks, signed EAS/Play release approval. No build, OTA or Play until separately authorized.
