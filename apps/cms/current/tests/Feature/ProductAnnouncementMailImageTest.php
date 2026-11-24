<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\OrderEmailOutbox;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Role;
use App\Models\User;
use App\Services\OrderEmailDispatcher;
use App\Services\ProductAnnouncementService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Mail o novom artiklu: prva slika dolazi prije opisa, napomene vide samo SubAgenti,
 * a slika se osvježava iz kataloga pri slanju.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class ProductAnnouncementMailImageTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        Storage::fake('public');
        config()->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        config()->set('app.url', 'https://cms.example.test');
        config()->set('mail.default', 'array');
        config()->set('mail.from.address', 'no-reply@example.test');
    }

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
        $html = $this->html(['product_image_url' => 'https://example.test/media/p/7/original?v=a&s=b', 'product_name' => 'Stolica "XL" <b>']);

        $image = strpos($html, '<img src="https://example.test/media/p/7/original?v=a&amp;s=b"');
        $description = strpos($html, 'Opis stolice od bukve.');

        self::assertIsInt($image);
        self::assertIsInt($description);
        self::assertLessThan($description, $image);
        self::assertStringContainsString('alt="Stolica &quot;XL&quot; &lt;b&gt;"', $html);
        self::assertStringContainsString('width="560"', $html);
        self::assertStringContainsString('height:auto', $html);
        self::assertStringContainsString('display:block', $html);
        self::assertStringContainsString('border="0"', $html);
        self::assertStringContainsString('bgcolor="#ffffff"', $html);
        self::assertStringNotContainsString('object-fit', $html);
    }

    public function test_no_image_means_no_img_and_no_empty_frame(): void
    {
        $html = $this->html([]);

        self::assertStringNotContainsString('<img', $html);
        self::assertStringNotContainsString('bgcolor="#ffffff"', $html);
        self::assertStringContainsString('Opis stolice od bukve.', $html);
    }

    public function test_service_picks_primary_image_then_lowest_sort_order(): void
    {
        $product = $this->product();
        $plain = $this->image($product, sort: 1, primary: false);
        $primary = $this->image($product, sort: 5, primary: true);
        $first = $this->image($product, sort: 0, primary: false);
        $service = app(ProductAnnouncementService::class);

        self::assertSame($primary->url, $service->absoluteImageUrl($product->fresh()));
        self::assertSame($primary->url, $service->absoluteImageUrl(Product::query()->with('images')->findOrFail($product->id)));

        $primary->update(['is_primary' => false]);
        self::assertSame($first->url, $service->absoluteImageUrl($product->fresh()));
        self::assertNotSame($plain->url, $first->url);
    }

    public function test_dispatcher_adds_catalog_image_before_description_when_metadata_has_none(): void
    {
        $product = $this->product();
        $image = $this->image($product, sort: 1, primary: true);
        $recipient = $this->user('customer');
        $this->outbox($recipient, $product);

        $html = $this->dispatchAndCapture();

        $position = strpos($html, '<img src="'.htmlspecialchars((string) $image->url, ENT_QUOTES).'"');
        self::assertIsInt($position);
        self::assertLessThan(strpos($html, 'Opis proizvoda.'), $position);
    }

    public function test_dispatcher_skips_inactive_product_image_and_notes(): void
    {
        $product = $this->product(['status' => 'inactive', 'notes' => 'Interna napomena']);
        $this->image($product, sort: 1, primary: true);
        $this->outbox($this->user('user'), $product);

        $html = $this->dispatchAndCapture();

        self::assertStringNotContainsString('<img', $html);
        self::assertStringNotContainsString('Napomena', $html);
    }

    public function test_subagent_sees_escaped_notes_after_image_and_description(): void
    {
        $product = $this->product(['notes' => "Rok isporuke 3 dana <script>x</script>\nRabat po dogovoru"]);
        $image = $this->image($product, sort: 1, primary: true);
        $this->outbox($this->user('user'), $product);

        $html = $this->dispatchAndCapture();

        self::assertStringContainsString('white-space:nowrap', $html);
        self::assertStringContainsString('Napomena', $html);
        self::assertStringContainsString('Rok isporuke 3 dana &lt;script&gt;x&lt;/script&gt;<br />', $html);
        self::assertStringNotContainsString('<script>', $html);
        self::assertLessThan(strpos($html, 'Opis proizvoda.'), strpos($html, '<img src="'.htmlspecialchars((string) $image->url, ENT_QUOTES).'"'));
        self::assertLessThan(strpos($html, 'Napomena'), strpos($html, 'Opis proizvoda.'));
    }

    public function test_subagent_without_notes_gets_no_notes_block(): void
    {
        $product = $this->product(['notes' => '  ']);
        $this->outbox($this->user('user'), $product);

        self::assertStringNotContainsString('Napomena', $this->dispatchAndCapture());
    }

    public function test_non_subagent_recipient_never_gets_notes(): void
    {
        $product = $this->product(['notes' => 'Samo za SubAgente']);
        $this->outbox($this->user('customer'), $product);

        $html = $this->dispatchAndCapture();

        self::assertStringNotContainsString('Napomena', $html);
        self::assertStringNotContainsString('Samo za SubAgente', $html);
    }

    private function dispatchAndCapture(): string
    {
        $result = app(OrderEmailDispatcher::class)->dispatch();
        self::assertSame(1, $result['sent']);
        $messages = app('mail.manager')->mailer('array')->getSymfonyTransport()->messages();
        self::assertCount(1, $messages);

        return (string) $messages[0]->getOriginalMessage()->getHtmlBody();
    }

    private function outbox(User $recipient, Product $product): OrderEmailOutbox
    {
        return OrderEmailOutbox::query()->create([
            'order_id' => null,
            'recipient_user_id' => $recipient->id,
            'document_id' => null,
            'recipient_email' => $recipient->email,
            'recipient_name' => 'Primalac',
            'event_type' => 'product_published',
            'batch_key' => 'product-published:'.$product->id,
            'dedupe_key' => hash('sha256', 'test|'.$product->id.'|'.$recipient->id),
            'subject' => 'Novi artikal: '.$product->name,
            'message' => 'Rezervna poruka',
            'action_url' => 'https://cms.example.test/katalog/'.$product->slug,
            'attach_document' => false,
            'attach_active_invoice' => false,
            'status' => 'pending',
            'attempt_count' => 0,
            'scheduled_for' => now()->subMinute(),
            // Bez product_image_url: dispatcher ga mora dopuniti iz kataloga.
            'metadata_json' => ['product_id' => $product->id, 'product_name' => $product->name, 'product_description' => 'Opis proizvoda.'],
        ]);
    }

    private function user(string $role): User
    {
        $n = ++$this->seq;

        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'user_group_id' => $role === 'user' ? 1 : null,
            'username' => 'mailimg'.$n,
            'email' => 'mailimg'.$n.'@example.test',
            'first_name' => 'Mailimg'.$n,
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
    }

    private function product(array $extra = []): Product
    {
        $n = ++$this->seq;

        return Product::query()->create($extra + [
            'sku' => 'MI-'.$n,
            'name' => 'Proizvod '.$n,
            'slug' => 'proizvod-'.$n,
            'price_amount' => 1000,
            'price_currency' => 'RSD',
            'description' => 'Opis proizvoda.',
            'stock_quantity' => 10,
            'low_stock_threshold' => 1,
            'status' => 'active',
            'created_by' => $this->user('admin')->id,
        ]);
    }

    private function image(Product $product, int $sort, bool $primary): ProductImage
    {
        $bytes = 'png-'.$this->seq.'-'.$sort;
        $path = 'products/'.$product->id.'/'.bin2hex(random_bytes(6)).'.png';
        Storage::disk('public')->put($path, $bytes);

        return ProductImage::query()->create([
            'product_id' => $product->id, 'storage_disk' => 'public', 'file_path' => $path,
            'original_filename' => 'p.png', 'mime_type' => 'image/png', 'file_size' => strlen($bytes),
            'file_hash' => hash('sha256', $bytes.$path), 'sort_order' => $sort, 'is_primary' => $primary,
        ]);
    }
}
