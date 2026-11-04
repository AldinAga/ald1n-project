<?php
declare(strict_types=1);
namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class PublicStorefrontStateService
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly PublicMerchantPolicyService $merchant,
        private readonly AuditLogger $audit,
    ) {}

    public function requestedOpen(bool $lock = false): bool
    {
        if (!Schema::hasTable('settings')) return false;
        // Do not use the settings cache for the emergency OFF control.
        $row=Setting::query()->where('setting_key','public_storefront_open');
        if ($lock) $row->sharedLock();
        return (string)$row->value('setting_value') === '1';
    }

    public function isOpen(bool $lock = false): bool
    {
        return config('public_storefront.enabled') === true && $this->requestedOpen($lock);
    }

    public function change(bool $open, User $actor): void
    {
        abort_unless($actor->status === 'active' && $actor->retired_at === null
            && $actor->hasRole('superadmin'),403);
        try {
            DB::transaction(function () use ($open,$actor): void {
                Setting::query()->firstOrCreate(['setting_key'=>'public_storefront_open'],[
                    'setting_value'=>'0','updated_by'=>$actor->id,
                ]);
                $state=Setting::query()->where('setting_key','public_storefront_open')->lockForUpdate()->firstOrFail();
                if ($open) {
                    $review=$this->merchant->review();
                    $schema=Schema::hasTable('public_order_accesses')
                        && Schema::hasColumns('public_checkout_attempts',[
                            'merchant_policy_digest','delivery_method','customer_user_id','payment_due_at_snapshot',
                        ]);
                    if (config('public_storefront.enabled')!==true
                        || config('public_storefront.checkout_enabled')!==true
                        || !$schema || !$review['approved']) {
                        throw ValidationException::withMessages(['enabled'=>
                            'ON zahteva isporucen kod, spremnu bazu i potvrdjene uslove trgovca.']);
                    }
                }
                $before=(string)$state->setting_value === '1';
                $state->update(['setting_value'=>$open?'1':'0','updated_by'=>$actor->id]);
                $this->audit->log('settings.public_storefront_toggled','Javna prodavnica ON / OFF',
                    null,['enabled'=>$before],['enabled'=>$open],user:$actor);
            },5);
        } finally {
            $this->settings->forgetCache();
        }
    }
}
