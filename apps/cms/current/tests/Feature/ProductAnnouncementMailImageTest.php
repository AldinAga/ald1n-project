<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Mail o novom artiklu: prva slika dolazi prije opisa; bez slike nema praznog okvira.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class ProductAnnouncementMailImageTest extends TestCase
{
    private function html(array $metadata): string
    {
        $events = collect([(object) [
            'event_type' => 'product_published',
            'subject' => 'Novi artikal: Stolica',
            'message' => 'Rezervna poruka',
            'action_url' => 'https://example.test/katalog/stolica',
            'metadata_json' => $metadata + ['product_name' => 'Stolica', 'product_description' => 'Opis stolice od bukve.'],
        ]]);

        return view('emails.order-events', ['events' => $events, 'recipientName' => 'Pera'])->render();
    }

    public function test_image_is_rendered_before_description_with_alt_and_absolute_url(): void
    {
        $html = $this->html(['product_image_url' => 'https://example.test/media/p/7/original?v=a&s=b']);

        $image = strpos($html, '<img src="https://example.test/media/p/7/original?v=a&amp;s=b"');
        $description = strpos($html, 'Opis stolice od bukve.');

        self::assertIsInt($image);
        self::assertIsInt($description);
        self::assertLessThan($description, $image);
        self::assertStringContainsString('alt="Stolica"', $html);
        self::assertStringContainsString('max-width:560px', $html);
    }

    public function test_no_image_means_no_img_and_no_empty_frame(): void
    {
        $html = $this->html([]);

        self::assertStringNotContainsString('<img src="https://example.test/media', $html);
        self::assertStringNotContainsString('padding:12px;text-align:center', $html);
        self::assertStringContainsString('Opis stolice od bukve.', $html);
    }
}
