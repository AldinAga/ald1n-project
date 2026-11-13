<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Pdf\BusinessDocumentPdfService;
use App\Services\Pdf\CommissionReportPdfService;
use App\Services\Pdf\ManagementReportPdfService;
use App\Services\Pdf\WarrantyCertificatePdfService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The PDF writer must never receive SVG bytes, whatever the logo file is
 * called. Every renderer shares FlowPdfLayout; each one is exercised here.
 */
final class PdfSvgLogoGuardTest extends TestCase
{
    private const SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><image href="https://evil.example/x.png"/><rect width="10" height="10"/></svg>';

    /** @var list<string> */
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) @unlink($file);
        parent::tearDown();
    }

    private function file(string $bytes, string $suffix): string
    {
        $base = tempnam(sys_get_temp_dir(), 'ald1n-logo-');
        self::assertIsString($base);
        $path = $base.$suffix;
        rename($base, $path);
        file_put_contents($path, $bytes);
        $this->files[] = $path;

        return $path;
    }

    /** @return array<string,array{string}> */
    public static function svgFiles(): array
    {
        return ['.svg name' => ['.svg'], 'disguised as .jpg' => ['.jpg'], 'disguised as .png' => ['.png']];
    }

    /** @return array<string,callable(string):string> */
    private function renderers(): array
    {
        return [
            'order document' => fn (string $logo): string => (new BusinessDocumentPdfService())->renderOrderDocument([
                'document_type' => 'invoice', 'document_number' => 'RAC-1', 'status' => 'issued',
                'company_name' => 'Test', 'company_logo_path' => $logo, 'customer_name' => 'Kupac',
            ], ['order_number' => 'ALD-1'], []),
            'orders report' => fn (string $logo): string => (new BusinessDocumentPdfService())->renderOrdersReport([
                'company_name' => 'Test', 'company_logo_path' => $logo, 'generated_at' => 'x', 'summary' => [], 'orders' => [],
            ]),
            'commission report' => fn (string $logo): string => (new CommissionReportPdfService())->render([
                'company_name' => 'Test', 'company_logo_path' => $logo, 'generated_at' => 'x', 'summary' => [], 'rows' => [],
            ]),
            'management report' => fn (string $logo): string => (new ManagementReportPdfService())->render([
                'company_name' => 'Test', 'company_logo_path' => $logo, 'segments' => [],
            ]),
            'warranty certificate' => fn (string $logo): string => (new WarrantyCertificatePdfService())->render([
                'company_name' => 'Test', 'logo_path' => $logo, 'warranty_number' => 'W-1',
            ]),
        ];
    }

    #[DataProvider('svgFiles')]
    public function test_svg_logo_is_never_handed_to_the_engine_by_any_renderer(string $suffix): void
    {
        $path = $this->file(self::SVG, $suffix);

        foreach ($this->renderers() as $name => $render) {
            $pdf = $render($path);
            self::assertStringStartsWith('%PDF-1.4', $pdf, $name);
            self::assertStringContainsString('%%EOF', $pdf, $name);
            self::assertStringNotContainsString('/Subtype /Image', $pdf, $name);
            self::assertStringNotContainsString('<svg', $pdf, $name);
            self::assertStringNotContainsString('evil.example', $pdf, $name);
        }
    }

    public function test_raster_logo_is_still_embedded_by_every_renderer(): void
    {
        $jpeg = file_get_contents(dirname(__DIR__).'/Fixtures/pdf-logo.jpg');
        self::assertIsString($jpeg);
        $path = $this->file($jpeg, '.jpg');

        foreach ($this->renderers() as $name => $render) {
            self::assertStringContainsString('/Subtype /Image', $render($path), $name);
        }
    }
}
