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

        self::assertStringContainsString('action="https://example.test/admin/payments/7/verify"', $html);
        self::assertStringContainsString('action="https://example.test/admin/payments/7/reject"', $html);
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

        self::assertStringContainsString('<x-icon-action-pill tone="ok" label="Potvrdi zaduženje"', $src);
        self::assertStringContainsString('<x-icon-action-pill tone="danger" label="Odbij predlog zaduženja"', $src);
        self::assertStringContainsString('data-confirm="Odbiti predlog zaduženja?"', $src);
    }

    public function test_styles_guarantee_touch_target_and_focus(): void
    {
        $css = (string) file_get_contents(public_path('assets/css/ald1n-icon-actions.css'));

        self::assertStringContainsString('min-width:40px', $css);
        self::assertStringContainsString('min-height:40px', $css);
        self::assertStringContainsString(':focus-visible', $css);
        self::assertStringContainsString('flex-wrap:nowrap', $css);
        self::assertStringContainsString('min-width:8rem', $css);
        self::assertStringContainsString('assets/css/ald1n-icon-actions.css', (string) file_get_contents(resource_path('views/layouts/app.blade.php')));
    }
}
