<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\User;

/**
 * Public registration From identity. SMTP credentials and deliverability
 * remain separate server-controlled configuration and release gates.
 */
final class PublicRegistrationMailSenderService
{
    public function __construct(private readonly SettingsService $settings) {}

    public static function normalizeEmail(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    public static function validAddress(string $address): bool
    {
        if ($address==='' || strlen($address)>254
            || preg_match('/[\x00-\x20\x7f]/', $address)
            || filter_var($address,FILTER_VALIDATE_EMAIL)===false) return false;
        $domain=substr(strrchr($address,'@') ?: '',1);
        if ($domain==='' || filter_var($domain,FILTER_VALIDATE_DOMAIN,FILTER_FLAG_HOSTNAME)===false) return false;
        if (app()->environment('production')
            && preg_match('/(?:^|\.)(?:localhost|local|test|invalid|example)$/iD',$domain)) return false;
        return true;
    }

    public static function validName(string $name): bool
    {
        return $name!=='' && mb_strlen($name)<=100
            && preg_match('/[\x00-\x1f\x7f]|\p{C}/u',$name)!==1;
    }

    public static function fingerprint(string $address,string $name): string
    {
        return hash('sha256',json_encode([self::normalizeEmail($address),trim($name)],
            JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array{address:string,name:string,configured:bool,approved:bool,transport_ready:bool,ready:bool}
     */
    public function status(): array
    {
        $address=self::normalizeEmail((string)$this->settings->get('public_registration_sender_address',''));
        $name=trim((string)$this->settings->get('public_registration_sender_name',''));
        $configured=self::validAddress($address) && self::validName($name);
        $digest=(string)$this->settings->get('public_registration_sender_digest','');
        $by=filter_var($this->settings->get('public_registration_sender_approved_by',''),FILTER_VALIDATE_INT);
        $approved=$configured && $digest!=='' && hash_equals($digest,self::fingerprint($address,$name))
            && trim((string)$this->settings->get('public_registration_sender_approved_at',''))!==''
            && $by && $by>0
            && User::query()->whereKey($by)->where('status','active')->whereNull('retired_at')
                ->whereHas('role',static fn ($q)=>$q->where('slug','superadmin'))->exists();
        // A production log/array transport silently drops real email.
        // CI may use array: it is not production evidence.
        $transportReady=!app()->environment('production')
            || in_array((string)config('mail.default'),['smtp','sendmail'],true);
        return [
            'address'=>$address,'name'=>$name,'configured'=>$configured,
            'approved'=>$approved,'transport_ready'=>$transportReady,
            'ready'=>$approved && $transportReady,
        ];
    }

    /** @return array{address:string,name:string}|null */
    public function sender(): ?array
    {
        $state=$this->status();
        return $state['ready'] ? ['address'=>$state['address'],'name'=>$state['name']] : null;
    }
}
