<?php

declare(strict_types=1);
namespace App\Services;

use App\Models\User;
use App\Notifications\OperationalNotification;
use Illuminate\Support\Facades\{DB, Log};
use Throwable;

final class WarrantyExpiryDeliveryService
{
    public function __construct(private readonly WarrantyExpiryPolicy $policy, private readonly MobilePushOutboxService $push) {}

    /** Return a claimed delivery attempt, not proof of email/device receipt. */
    public function send(User $recipient, array $data, int $reminderHours = 24): bool
    {
        $claim = null;
        try {
            $resolved = $this->policy->resolve((int) $recipient->id, $data);
            if (!$resolved) return false;
            $data = $this->policy->canonicalData($resolved, $data);
            $data['_email'] = $data['_email'] && (bool) config('services.operational_notifications.mail_enabled', false)
                && filled($resolved['user']->email);
            $data['_push'] = $data['_push'] && (bool) config('mobile.push.enabled', false)
                && (string) config('mobile.push.provider', 'expo') === 'expo'
                && $resolved['user']->mobileDevices()->whereNull('revoked_at')->where('notifications_enabled', true)
                    ->where('push_provider', 'expo')->whereNotNull('push_token_hash')->exists();
            if (!$data['_in_app'] && !$data['_email'] && !$data['_push']) return false;
            $hours = min(720, max(1, $reminderHours));
            $claim = DB::transaction(function () use ($recipient, $data, $hours): ?int {
                // Serializes simultaneous attempts for this recipient, including first insert.
                User::query()->whereKey($recipient->id)->lockForUpdate()->firstOrFail();
                if (!$this->policy->resolve((int) $recipient->id, $data)) return null;
                $keys = ['product_warranty_id' => $data['warranty_id'], 'user_id' => $recipient->id,
                    'event' => 'warranty.expiring', 'expires_on' => $data['warranty_expires_on']];
                $query = DB::table('warranty_expiry_deliveries')->where($keys);
                $previous = (clone $query)->lockForUpdate()->first();
                if ($previous && \Illuminate\Support\Carbon::parse($previous->claimed_at)->gt(now()->subHours($hours))) return null;
                $values = ['claimed_at' => now(), 'state' => 'claimed', 'updated_at' => now(),
                    'channels_json' => json_encode(array_intersect_key($data, array_flip(['_in_app','_email','_push'])), JSON_THROW_ON_ERROR)];
                if ($previous) { $query->update($values); return (int) $previous->id; }
                return (int) DB::table('warranty_expiry_deliveries')->insertGetId(array_merge($keys, $values, ['created_at' => now()]));
            });
            if ($claim === null) return false;
            $fresh = $this->policy->resolve((int) $recipient->id, $data);
            if (!$fresh) { DB::table('warranty_expiry_deliveries')->where('id', $claim)->update(['state'=>'consent_revoked']); return false; }
            $data = $this->policy->canonicalData($fresh, $data);
            if ($data['_in_app'] || $data['_email']) $fresh['user']->notify(new OperationalNotification($data));
            if ($data['_push']) $this->push->enqueue($fresh['user'], $data);
            DB::table('warranty_expiry_deliveries')->where('id', $claim)->update(['state'=>'attempted', 'updated_at'=>now()]);
            return true;
        } catch (Throwable $exception) {
            // Never release a claim on ambiguous transport failure: it may already have delivered.
            try {
                if ($claim !== null) DB::table('warranty_expiry_deliveries')->where('id', $claim)->update(['state'=>'attempt_failed', 'updated_at'=>now()]);
                Log::warning('Warranty expiry delivery attempt failed.', ['recipient_id'=>$recipient->id, 'exception'=>$exception::class]);
            } catch (Throwable) {}
            return false;
        }
    }
}
