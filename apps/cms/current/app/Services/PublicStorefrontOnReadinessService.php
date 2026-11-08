<?php
declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Schema;

/**
 * The single evaluator of the three requirements for turning the public
 * storefront ON. Used by PublicStorefrontStateService::change() (server-side
 * enforcement) and by the settings checklist, so both always agree.
 *
 * Read-only: it never changes settings, flags, schema or approval.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class PublicStorefrontOnReadinessService
{
    public const SCHEMA_TABLE = 'public_order_accesses';
    public const SCHEMA_ATTEMPT_TABLE = 'public_checkout_attempts';
    public const SCHEMA_ATTEMPT_COLUMNS = [
        'merchant_policy_digest', 'delivery_method', 'customer_user_id', 'payment_due_at_snapshot',
    ];

    public function __construct(private readonly PublicMerchantPolicyService $merchant) {}

    /**
     * @return array{
     *   ready:bool,
     *   failed:list<string>,
     *   review:array<string,mixed>,
     *   requirements:array<string,array{label:string,ok:bool,items:list<array{code:string,label:string,ok:bool,help:string}>}>
     * }
     */
    public function evaluate(?array $review = null): array
    {
        $catalog = config('public_storefront.enabled') === true;
        $checkout = config('public_storefront.checkout_enabled') === true;
        $code = [
            ['code' => 'flag_catalog', 'label' => 'Javni katalog dozvoljen u konfiguraciji servera', 'ok' => $catalog,
                'help' => 'Postavlja se pri instalaciji: PUBLIC_STOREFRONT_ENABLED=true u .env na serveru. Ovaj ekran to ne menja.'],
            ['code' => 'flag_checkout', 'label' => 'Javno poručivanje dozvoljeno u konfiguraciji servera', 'ok' => $checkout,
                'help' => 'Postavlja se pri instalaciji: PUBLIC_CHECKOUT_ENABLED=true u .env na serveru. Ovaj ekran to ne menja.'],
        ];

        $schema = [];
        $tableOk = Schema::hasTable(self::SCHEMA_TABLE);
        $schema[] = ['code' => 'schema_'.self::SCHEMA_TABLE, 'label' => 'Tabela '.self::SCHEMA_TABLE, 'ok' => $tableOk,
            'help' => 'Nastaje odobrenom migracijom pri instalaciji koda prodavnice.'];
        $attemptTable = Schema::hasTable(self::SCHEMA_ATTEMPT_TABLE);
        foreach (self::SCHEMA_ATTEMPT_COLUMNS as $column) {
            $schema[] = ['code' => 'schema_'.$column, 'label' => 'Kolona '.self::SCHEMA_ATTEMPT_TABLE.'.'.$column,
                'ok' => $attemptTable && Schema::hasColumn(self::SCHEMA_ATTEMPT_TABLE, $column),
                'help' => 'Nastaje odobrenom migracijom pri instalaciji koda prodavnice.'];
        }
        // Same predicate as before the refactor: the table plus all columns.
        $schemaOk = $tableOk && Schema::hasColumns(self::SCHEMA_ATTEMPT_TABLE, self::SCHEMA_ATTEMPT_COLUMNS);

        $review ??= $this->merchant->review();
        $merchant = [];
        $help = self::issueHelp();
        foreach ($review['issue_codes'] as $index => $issueCode) {
            $merchant[] = ['code' => $issueCode, 'label' => $review['issues'][$index], 'ok' => false,
                'help' => $help[$issueCode] ?? ''];
        }
        if ($review['issues'] === []) {
            $merchant[] = ['code' => 'policy_complete', 'label' => 'Svi podaci trgovca su uneti', 'ok' => true,
                'help' => 'Identitet, pravne adrese, dostava, plaćanje i e-pošta podrške prolaze proveru.'];
        }
        $merchant[] = ['code' => 'approval', 'label' => 'Potpis SuperAdministratora na tačne uslove', 'ok' => $review['approved'],
            'help' => $review['approved']
                ? 'Uslovi su potvrđeni. Svaka kasnija izmena uslova ili pravnih tekstova poništava potvrdu.'
                : ($review['complete']
                    ? 'Označi potvrdu uslova i sačuvaj podešavanja.'
                    : 'Potvrda je moguća kada sve stavke iznad budu ispunjene.')];

        $requirements = [
            'code' => ['label' => 'Kod prodavnice je isporučen i uključen na serveru', 'ok' => $catalog && $checkout, 'items' => $code],
            'schema' => ['label' => 'Baza je spremna za javne porudžbine', 'ok' => $schemaOk, 'items' => $schema],
            'merchant' => ['label' => 'Uslovi trgovca su potpuni i potvrđeni', 'ok' => $review['approved'], 'items' => $merchant],
        ];
        $failed = [];
        foreach ($requirements as $requirement) {
            if (!$requirement['ok']) $failed[] = $requirement['label'];
        }

        return ['ready' => $failed === [], 'failed' => $failed, 'review' => $review, 'requirements' => $requirements];
    }

    /** @return array<string,string> */
    public static function issueHelp(): array
    {
        return [
            'identity' => 'U „PDF i fakturisanje” unesi stvarno poslovno ime, adresu, mesto i PIB.',
            'terms_url' => 'Klikni „Koristi ove adrese” kod pravnih stranica ili upiši HTTPS adresu uslova prodaje, pa sačuvaj.',
            'privacy_url' => 'Klikni „Koristi ove adrese” kod pravnih stranica ili upiši HTTPS adresu politike privatnosti, pa sačuvaj.',
            'shipping_description' => 'U delu „Dostava i plaćanje” opiši rokove i područja dostave.',
            'shipping_fee' => 'U polje „Cena dostave” upiši 0. Naplata dostave još nije podržana.',
            'support_email' => 'Upiši e-poštu na koju kupci pišu podršci.',
            'payment_methods' => 'Označi bar jedan način plaćanja.',
            'delivery_methods' => 'Označi bar jedan način dostave.',
            'cash_delivery' => 'Uz plaćanje pri preuzimanju označi lično preuzimanje ili sopstvenu dostavu.',
            'bank_account' => 'U „Žiro računi” aktiviraj račun, pa ga izaberi za bankovni transfer.',
        ];
    }
}
