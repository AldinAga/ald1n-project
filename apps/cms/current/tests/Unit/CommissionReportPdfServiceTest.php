<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Pdf\CommissionReportPdfService;
use PHPUnit\Framework\TestCase;

final class CommissionReportPdfServiceTest extends TestCase
{
    public function test_commission_report_is_valid_multi_page_pdf(): void
    {
        $rows = [];
        for ($index = 1; $index <= 35; $index++) {
            $rows[] = [
                'order_number' => sprintf('ALD-20260723-%08d', $index),
                'user' => 'Test korisnik '.$index,
                'status' => ['pending', 'approved', 'paid', 'cancelled'][$index % 4],
                'amount_eur' => 20 + $index,
                'date' => '23.07.2026',
            ];
        }

        $pdf = (new CommissionReportPdfService())->render([
            'company_name' => 'AP Computers',
            'generated_at' => '23.07.2026 12:00',
            'filters_label' => 'bez dodatnih filtera',
            'summary' => ['count' => 35, 'pending_eur' => 100, 'approved_eur' => 200, 'paid_eur' => 300],
            'rows' => $rows,
        ]);

        self::assertStringStartsWith('%PDF-1.4', $pdf);
        self::assertGreaterThan(10_000, strlen($pdf));
        self::assertGreaterThanOrEqual(2, substr_count($pdf, '/Type /Page'));
    }
}
