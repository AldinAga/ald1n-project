# Batch 511 V2 - Payment / IPS terminal-state repair

- RESULT: PASS_SOURCE_REPAIR
- TIMESTAMP: 20261003-093551
- MODE: SOURCE_REPAIR_WITH_READ_ONLY_DB_GATES
- REPORT_PATH: /home/icaffeco/ald1n-project/docs/operations/511-V2-PAYMENT-IPS-TERMINAL-STATE-REPAIR-20261003-093551.md
- CURRENT_BRANCH: main
- HEAD: 4ba582c0fdb03f5dd8357ee4ff083a45edcda9f5
- ORIGIN_MAIN: 4ba582c0fdb03f5dd8357ee4ff083a45edcda9f5
- EXPECTED_BASE: 4ba582c0fdb03f5dd8357ee4ff083a45edcda9f5
- SOURCE_APPLIED_AT_REPORT_TIME: YES
- ROLLBACK: NOT_NEEDED
- FAIL_REASON: NONE
- DATABASE_WRITE_AUTHORIZED: NO
- PAYMENT48_MUTATION_AUTHORIZED: NO
- MIGRATION_RUN: NO
- COMPOSER_INSTALL_RUN: NO
- EAS_BUILD_STARTED: NO
- OTA_PUBLISHED: NO
- GOOGLE_PLAY_ACTION: NO

## Gates

- BASELINE_IPS_SMOKE: PASS
- BASELINE_STATIC_CHECK: PASS_983_983
- PREFLIGHT_ORDER132_PAYMENT48: PASS
- PATCH_CONTRACT: PASS
- POST_PHP_LINT: PASS
- POST_GIT_DIFF_CHECK: PASS
- POST_IPS_SMOKE: PASS
- POST_STATIC_CHECK: PASS_983_983
- POSTFLIGHT_ORDER132_PAYMENT48: PASS

## Source hashes

- IPS_SERVICE_SHA256: 5dd9f6ceca496fb6cac9c13fe14d5045880f24b20549dd9f23c514d57f275b8d
- STATIC_CHECK_SHA256: a1b52c358b7f0769e0d3437c7c0035e8be8e6a4f403f5ae6026b2395d87e6ac8

## Preflight DB snapshot

```text
SNAPSHOT_RESULT=PASS
ORDER132_STATUS=shipped
ORDER132_PAYMENT_METHOD=bank_transfer
ORDER132_PAYMENT_STATUS=pending
ORDER132_PAYMENT_STATE=unpaid
ORDER132_SUBTOTAL=76603.67
ORDER132_PAID_TOTAL=0.00
ORDER132_VERIFIED_NET=0.00
ORDER132_REMAINING=76603.67
ORDER132_PAYMENT_VERIFIED_AT=NULL
PAYMENT48_STATUS=submitted
PAYMENT48_AMOUNT=76603.67
PAYMENT48_ENTRY_TYPE=payment
PAYMENT48_VERIFIED_AT=NULL
PAYMENT48_PROOF_EXISTS=YES
IPS_CACHE_ROWS_ORDER132=1
IPS_CACHE_STATUS_ORDER132=ready
```

## Postflight DB snapshot

```text
SNAPSHOT_RESULT=PASS
ORDER132_STATUS=shipped
ORDER132_PAYMENT_METHOD=bank_transfer
ORDER132_PAYMENT_STATUS=pending
ORDER132_PAYMENT_STATE=unpaid
ORDER132_SUBTOTAL=76603.67
ORDER132_PAID_TOTAL=0.00
ORDER132_VERIFIED_NET=0.00
ORDER132_REMAINING=76603.67
ORDER132_PAYMENT_VERIFIED_AT=NULL
PAYMENT48_STATUS=submitted
PAYMENT48_AMOUNT=76603.67
PAYMENT48_ENTRY_TYPE=payment
PAYMENT48_VERIFIED_AT=NULL
PAYMENT48_PROOF_EXISTS=YES
IPS_CACHE_ROWS_ORDER132=1
IPS_CACHE_STATUS_ORDER132=ready
```

## IPS smoke

```text
PASS IPS payment-state financial document policy
PAID_BANK_TRANSFER_QR=SKIP
PARTIAL_BANK_TRANSFER_QR=REQUIRED
PARTIAL_QR_AMOUNT=7500.00_RSD_OUTSTANDING_ONLY
```

## Static check summary

```text
PASS  v2.1.6 smoke i contract test postoje
PASS  v2.2.0 bootstrap device catalog order i notification rute postoje
PASS  v2.2.0 bootstrap vraća permissions features i app policy
PASS  v2.2.0 uređaji deduplikuju push tokene i podržavaju opoziv
PASS  v2.2.0 API greške imaju stabilan envelope
PASS  v2.2.0 OpenAPI i Stable doctor su povezani

Ukupno: 983, neuspešno: 0
```

## Target diff

```text
diff --git a/apps/cms/current/app/Services/IpsPaymentPayloadService.php b/apps/cms/current/app/Services/IpsPaymentPayloadService.php
index 84be689..cf587df 100644
--- a/apps/cms/current/app/Services/IpsPaymentPayloadService.php
+++ b/apps/cms/current/app/Services/IpsPaymentPayloadService.php
@@ -71,10 +71,19 @@ final class IpsPaymentPayloadService
 
     public function persist(Order $order, ?float $amountRsd = null): ?string
     {
-        $payload = $this->payload($order, $amountRsd);
-        if ($payload === null) return null;
-
+        // BATCH511_V2_PAYMENT_IPS_CACHE_GUARD
+        // Derived IPS cache must never roll back canonical payment ledger state.
         try {
+            if ($this->shouldClearCachedPayload($order, $amountRsd)) {
+                $this->clearCachedPayload($order);
+                return null;
+            }
+
+            $payload = $this->payload($order, $amountRsd);
+            if ($payload === null) {
+                $this->clearCachedPayload($order);
+                return null;
+            }
             if (!Schema::hasTable('order_ips_qr')) return $payload;
             $columns = Schema::getColumnListing('order_ips_qr');
             if (array_diff(['order_id', 'status', 'payload_text'], $columns) !== []) return $payload;
@@ -86,6 +95,12 @@ final class IpsPaymentPayloadService
             if (in_array('created_at', $columns, true)) $values['created_at'] = now();
             DB::table('order_ips_qr')->updateOrInsert(['order_id' => $order->id], $values);
         } catch (Throwable $exception) {
+            // Never leave a previously ready cache row authoritative after a failed refresh.
+            try {
+                $this->clearCachedPayload($order);
+            } catch (Throwable) {
+            }
+
             try {
                 Log::warning('IPS payload je generisan, ali nije sačuvan.', [
                     'order_id' => $order->id,
@@ -99,6 +114,32 @@ final class IpsPaymentPayloadService
         return $payload;
     }
 
+    private function shouldClearCachedPayload(Order $order, ?float $amountRsd = null): bool
+    {
+        if ((string) $order->payment_method !== 'bank_transfer') return true;
+        if ((string) $order->status === 'cancelled') return true;
+        if (in_array((string) $order->payment_state, ['paid', 'overpaid', 'cancelled', 'refunded'], true)) return true;
+
+        return $this->outstandingAmount($order, $amountRsd) <= 0.004;
+    }
+
+    private function clearCachedPayload(Order $order): void
+    {
+        if (!$order->exists || (int) $order->getKey() <= 0) return;
+        if (!Schema::hasTable('order_ips_qr')) return;
+
+        $columns = Schema::getColumnListing('order_ips_qr');
+        if (!in_array('order_id', $columns, true)) return;
+
+        DB::table('order_ips_qr')->where('order_id', (int) $order->getKey())->delete();
+    }
+
+    private function outstandingAmount(Order $order, ?float $amountRsd = null): float
+    {
+        $amount = $amountRsd ?? max(0.0, (float) $order->subtotal_rsd - (float) ($order->paid_total_rsd ?? 0));
+
+        return round(max(0.0, $amount), 2);
+    }
     private function cleanLine(string $value, int $maxLength): string
     {
         $value = trim(preg_replace('/[|\r\n]+/u', ' ', $value) ?? $value);
diff --git a/apps/cms/current/bin/static-check.php b/apps/cms/current/bin/static-check.php
index f4288d6..564fcdd 100644
--- a/apps/cms/current/bin/static-check.php
+++ b/apps/cms/current/bin/static-check.php
@@ -655,7 +655,7 @@ $check('beta7.16 dispatcher ima retry stuck recovery i zaštitu storniranog pril
 $check('beta7.16 scheduler šalje outbox svake minute', str_contains((string) file_get_contents($root.'/routes/console.php'), "Schedule::command('app:order-email-dispatch')") && str_contains((string) file_get_contents($root.'/routes/console.php'), '->everyMinute()'));
 $check('beta7.16 admin podešava intervale događaje i dokumente', str_contains($orderEmailSettingsController, 'order_email_creation_interval_minutes') && str_contains($orderEmailSettingsView, 'Dokumenti koji se šalju') && str_contains($orderEmailSettingsView, 'Dodatne adrese'));
 $check('beta7.16 e-mail šablon ima događaje i bezbedan action link', str_contains($orderEmailView, '@foreach($events as $event)') && str_contains($orderEmailView, '$event->action_url'));
-$check('beta7.16 NBS payload koristi zvanične oznake i RSD zarez', str_contains($ipsPayloadService, "'K:PR'") && str_contains($ipsPayloadService, "'V:01'") && str_contains($ipsPayloadService, "'I:RSD'.number_format") && str_contains($ipsPayloadService, "'SF:'"));
+$check('beta7.16 NBS payload + batch511 v2 terminal cache guard', str_contains($ipsPayloadService, "'K:PR'") && str_contains($ipsPayloadService, "'V:01'") && str_contains($ipsPayloadService, "'I:RSD'.number_format") && str_contains($ipsPayloadService, "'SF:'") && str_contains($ipsPayloadService, 'BATCH511_V2_PAYMENT_IPS_CACHE_GUARD') && str_contains($ipsPayloadService, 'shouldClearCachedPayload($order, $amountRsd)') && str_contains($ipsPayloadService, 'clearCachedPayload') && str_contains($ipsPayloadService, "['paid', 'overpaid', 'cancelled', 'refunded']") && str_contains($ipsPayloadService, 'outstandingAmount($order, $amountRsd) <= 0.004'));
 $check('beta7.16 NBS servis koristi zvanični HTTPS endpoint i čuva privatni PNG snapshot', str_contains($nbsService, 'https://nbs.rs/QRcode/api/qr/v1/generate/320') && str_contains($nbsService, "Storage::disk('local')->put") && str_contains($nbsService, 'ips_qr_generated_at'));
 $check('beta7.16 stornirani istorijski dokument ostaje pregledljiv bez ponovnog NBS poziva', str_contains($nbsService, "(string) \$document->status !== 'issued'"));
 $check('beta7.16 finansijski dokument trazi NBS QR samo za pozitivan neplaceni saldo', str_contains($nbsService, 'Dokument nije izdat') && str_contains($nbsService, "['paid', 'overpaid', 'cancelled', 'refunded']") && str_contains($nbsService, 'outstandingAmount') && str_contains($documentService, '$this->nbsIpsQr->generate($order)') && str_contains($nbsService, '$this->generate($order)'));
```

## Decision gate

- PASS requires SOURCE_APPLIED_AT_REPORT_TIME=YES, POST_STATIC_CHECK=PASS_983_983 and POSTFLIGHT_ORDER132_PAYMENT48=PASS.
- This batch does not verify payment 48 and does not mutate payment/order business data.
- After PASS, the next step is controlled verification of payment 48 followed immediately by a read-only acceptance audit.
