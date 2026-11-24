<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

/**
 * Potvrdi/Odbij uplatu su ikonična dugmad (✓ / ✕) u jednom redu: bez vidljivog teksta koji bi se lomio,
 * sa srpskim aria-label i title, a forme/rute/data-confirm ostaju netaknuti. Author: Ald1n <https://ald1n.com>
 */
final class PaymentActionIconPillTest extends TestCase
{
    private function paymentsHtml(): string
    {
        return view('admin.orders.partials.payments', [
            'errors' => new ViewErrorBag(),
            'detail' => [
                'order' => ['payment_state' => 'unpaid', 'is_direct_sale' => false],
                'permissions' => ['manage_payments' => true],
                'urls' => [],
                'payments' => [[
                    'id' => 7,
                    'number' => 'UPL-7',
                    'status' => 'submitted',
                    'verify_url' => 'https://example.test/admin/payments/7/verify',
                    'reject_url' => 'https://example.test/admin/payments/7/reject',
                ]],
            ],
        ])->render();
    }

    public function test_verify_and_reject_are_icon_only_buttons_with_serbian_labels(): void
    {
        $html = $this->paymentsHtml();

        foreach (['Potvrdi uplatu UPL-7', 'Odbij uplatu UPL-7'] as $label) {
            self::assertStringContainsString('aria-label="'.$label.'"', $html);
            self::assertStringContainsString('title="'.$label.'"', $html);
        }

        // Svako ikonično dugme sadrži samo SVG: nema vidljivog teksta koji bi se lomio po slovima.
        preg_match_all('~<button\b[^>]*a1-iconpill[^>]*>(.*?)</button>~s', $html, $buttons);
        self::assertCount(2, $buttons[1]);
        foreach ($buttons[1] as $inner) {
            self::assertSame('', trim(strip_tags($inner)), 'Ikonično dugme ne sme imati vidljiv tekst.');
            self::assertStringContainsString('<svg', $inner);
            self::assertStringContainsString('aria-hidden="true"', $inner);
        }
        self::assertStringContainsString('a1-iconpill--ok', $html);
        self::assertStringContainsString('a1-iconpill--danger', $html);
        // Oblik ikone razlikuje akcije i bez boje: kvačica nasuprot iksu.
        self::assertStringContainsString('#ph-check', $html);
        self::assertStringContainsString('#ph-x', $html);
    }

    public function test_forms_and_reason_validation_are_unchanged(): void
    {
        $html = $this->paymentsHtml();

        // ✓ (ok, #ph-check) je isključivo u formi verify; ✕ (danger, #ph-x) isključivo u formi reject.
        self::assertMatchesRegularExpression('~<form[^>]*action="https://example\.test/admin/payments/7/verify"[^>]*>(?:(?!</form>).)*a1-iconpill--ok(?:(?!</form>).)*#ph-check(?:(?!</form>).)*</form>~s', $html);
        self::assertMatchesRegularExpression('~<form[^>]*action="https://example\.test/admin/payments/7/reject"[^>]*>(?:(?!</form>).)*a1-iconpill--danger(?:(?!</form>).)*#ph-x(?:(?!</form>).)*</form>~s', $html);
        self::assertDoesNotMatchRegularExpression('~/verify"[^>]*>(?:(?!</form>).)*a1-iconpill--danger~s', $html);
        self::assertDoesNotMatchRegularExpression('~/reject"[^>]*>(?:(?!</form>).)*a1-iconpill--ok~s', $html);
        self::assertMatchesRegularExpression('~<input[^>]*id="od-reject-7"[^>]*name="reason"[^>]*required[^>]*maxlength="1000"~', $html);
        self::assertStringContainsString('for="od-reject-7"', $html);
    }

    public function test_component_passes_extra_attributes_through(): void
    {
        $html = view('components.icon-action-pill', [
            'tone' => 'danger',
            'label' => 'Odbij predlog zaduženja',
            'attributes' => new \Illuminate\View\ComponentAttributeBag(['data-confirm' => 'Odbiti predlog zaduženja?']),
        ])->render();

        self::assertStringContainsString('data-confirm="Odbiti predlog zaduženja?"', $html);
        self::assertStringContainsString('aria-label="Odbij predlog zaduženja"', $html);
        self::assertStringContainsString('type="submit"', $html);
    }

    public function test_commission_debit_actions_use_the_same_component(): void
    {
        $src = (string) file_get_contents(resource_path('views/admin/commissions/index.blade.php'));

        // ✓ vodi na confirm rutu i nosi iznos u data-confirm; ✕ vodi na reject rutu.
        self::assertMatchesRegularExpression('~debits\.confirm[^>]*>[^<]*(?:<[^>]*>)?\s*@csrf<x-icon-action-pill tone="ok" label="Potvrdi zaduženje" data-confirm="Potvrditi zaduženje od \{\{ \$eur\(\$debit->amount_eur\) \}\} EUR\?" /></form>~', $src);
        self::assertMatchesRegularExpression('~debits\.reject.*?<x-icon-action-pill tone="danger" label="Odbij predlog zaduženja" data-confirm="Odbiti predlog zaduženja\?" /></form>~s', $src);
    }

    public function test_styles_guarantee_touch_target_and_focus(): void
    {
        $css = (string) file_get_contents(public_path('assets/css/ald1n-icon-actions.css'));

        // Svako pravilo za red (.a1-rowact) i formu (.a1-rowact__form) mora imati flex-wrap:nowrap.
        // Pravila iz ald1n-admin.css (.a1-od-rowactions form = 0,1,1) pobjeđuju jednu klasu, pa je potrebna specifičnost >= 0,2,0.
        preg_match_all('~([^{}]+)\{([^{}]*)\}~', $css, $rules, PREG_SET_ORDER);
        $checked = 0;
        foreach ($rules as [, $selectors, $body]) {
            if (!str_contains($body, 'flex-wrap:nowrap') || !str_contains($selectors, '.a1-rowact__form')) {
                continue;
            }
            foreach (explode(',', $selectors) as $selector) {
                $classes = substr_count($selector, '.');
                self::assertGreaterThanOrEqual(2, $classes, 'Slaba specifičnost: '.trim($selector));
                $checked++;
            }
        }
        self::assertGreaterThanOrEqual(3, $checked);
        self::assertMatchesRegularExpression('~\.a1-rowact__reason\{[^}]*min-width:8rem~', $css);
        self::assertMatchesRegularExpression('~\.a1-iconpill\{[^}]*min-width:40px;min-height:40px;width:40px;height:40px~', $css);
        self::assertMatchesRegularExpression('~\.a1-iconpill:focus-visible\{[^}]*outline:~', $css);
        self::assertMatchesRegularExpression('~\.a1-iconpill\.is-loading\{[^}]*opacity~', $css);

        // Kontejner u tabeli uplata ne smije nositi stare klase iz ald1n-admin.css koje lome red.
        $partial = (string) file_get_contents(resource_path('views/admin/orders/partials/payments.blade.php'));
        self::assertStringContainsString('<div class="a1-rowact">', $partial);
        self::assertStringNotContainsString('a1-od-rowactions a1-rowact', $partial);
        self::assertStringContainsString('assets/css/ald1n-icon-actions.css', (string) file_get_contents(resource_path('views/layouts/app.blade.php')));
    }
}
