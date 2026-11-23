<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Role;
use App\Models\User;
use App\Services\ProductImportService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** CSV uvoz: mapiranje, pregled bez upisa, izvršenje kao nacrt, limiti, kodiranje i dozvole. */
final class ProductCsvImportTest extends TestCase
{
    use RefreshDatabase;

    private Brand $brand;
    private ProductType $type;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        Storage::fake('local');
        $this->brand = Brand::query()->create(['name' => 'Lenovo', 'slug' => 'lenovo', 'status' => 'active']);
        $category = Category::query()->create(['name' => 'Laptopovi', 'slug' => 'laptopovi', 'status' => 'active']);
        $this->type = ProductType::query()->create(['category_id' => $category->id, 'name' => 'Laptopovi', 'slug' => 'laptopovi', 'status' => 'active']);
        DB::table('brand_product_type')->insert(['brand_id' => $this->brand->id, 'product_type_id' => $this->type->id]);
    }

    public function test_import_requires_manage_products_permission(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/admin/catalog/import')->assertForbidden();
        $this->post('/admin/catalog/import/upload', ['file' => $this->csv("Naziv\nA\n")])->assertForbidden();
        $this->post('/admin/catalog/import', ['token' => str_repeat('a', 40), 'mode' => 'execute'])->assertForbidden();
    }

    public function test_upload_guesses_column_mapping_from_headers(): void
    {
        $this->actingAs($this->admin('imp-map'));
        $page = $this->post('/admin/catalog/import/upload', ['file' => $this->csv("Naziv;Brend;Šifra dobavljača;Barkod;Cena\nT14;Lenovo;S-1;12345678;100\n")])->assertOk();

        $mapping = $page->viewData('mapping');
        self::assertSame('0', $mapping['name']);
        self::assertSame('1', $mapping['brand']);
        self::assertSame('2', $mapping['supplier_code']);
        self::assertSame('3', $mapping['ean']);
        self::assertSame('4', $mapping['price_amount']);
        self::assertSame('', $mapping['model_name']);
        $page->assertSee('Mapiranje kolona');
    }

    public function test_preview_validates_rows_and_warns_about_duplicates_without_writing(): void
    {
        $admin = $this->admin('imp-preview');
        Product::query()->create($this->existing($admin, ['model_name' => 'T14 Gen 3']));
        $content = "Naziv;Brend;Tip;Model;EAN;Cena\n"
            ."Dobar;Lenovo;Laptopovi;X1;12345678;100\n"
            ."Nepoznat brend;Nepostoji;Laptopovi;X2;;100\n"
            ."Duplikat;Lenovo;Laptopovi;t14 gen 3;;100\n"
            ."Loš EAN;Lenovo;Laptopovi;X3;12;100\n"
            .";Lenovo;;X4;;100\n";
        $before = Product::query()->count();

        $this->actingAs($admin);
        $token = (string) $this->post('/admin/catalog/import/upload', ['file' => $this->csv($content)])->viewData('token');
        $preview = $this->post('/admin/catalog/import', $this->mappingPost($token, 'preview', [
            'mapping' => ['name' => '0', 'brand' => '1', 'product_type' => '2', 'model_name' => '3', 'ean' => '4', 'price_amount' => '5'],
        ]))->assertOk();

        $analysis = $preview->viewData('analysis');
        self::assertSame(2, $analysis['valid']);
        self::assertSame(3, $analysis['errors']);
        self::assertSame(1, $analysis['duplicates']);
        self::assertStringContainsString('ne postoji', implode(' ', $analysis['rows'][1]['errors']));
        self::assertStringContainsString('Duplikat', implode(' ', $analysis['rows'][2]['warnings']));
        self::assertSame($before, Product::query()->count());
    }

    public function test_execute_creates_drafts_through_service_and_reports_errors_and_skipped_duplicates(): void
    {
        $admin = $this->admin('imp-exec');
        Product::query()->create($this->existing($admin, ['model_name' => 'T14 Gen 3']));
        $content = "Naziv;Brend;Tip;Model;Šifra dobavljača;EAN;Cena;Valuta;Opis\n"
            ."Prvi artikal;Lenovo;Laptopovi;X1;SUP-1;859 0123-4567;\"1.234,50\";RSD;Opis prvog\n"
            ."Nepoznat;Nepostoji;;X2;;;5;;\n"
            ."Duplikat;Lenovo;Laptopovi;T14 GEN 3;;;5;;\n";

        $this->actingAs($admin);
        $token = (string) $this->post('/admin/catalog/import/upload', ['file' => $this->csv($content)])->viewData('token');
        $this->post('/admin/catalog/import', $this->mappingPost($token, 'execute', ['skip_duplicates' => '1']))
            ->assertRedirect('/admin/catalog/import');

        $imported = Product::query()->where('name', 'Prvi artikal')->sole();
        self::assertSame('draft', $imported->status);
        self::assertSame(0, (int) $imported->stock_quantity);
        self::assertSame('1234.50', (string) $imported->price_amount);
        self::assertSame('RSD', $imported->price_currency);
        self::assertSame('SUP-1', $imported->supplier_code);
        self::assertSame('85901234567', $imported->ean);
        self::assertSame((int) $admin->id, (int) $imported->created_by);
        self::assertSame((int) $this->brand->id, (int) $imported->brand_id);
        self::assertNotSame('', (string) $imported->sku);
        self::assertTrue($imported->categories()->exists());
        self::assertTrue(AuditLog::query()->where('action', 'product.created')->exists());
        self::assertSame(2, Product::query()->count());

        $result = session('import_result');
        self::assertSame(1, $result['created']);
        self::assertSame(1, $result['skipped']);
        self::assertCount(1, $result['errors']);
        self::assertSame([], Storage::disk('local')->allFiles('product-imports'));

        $this->get('/admin/catalog/import')->assertOk()->assertSee('Uvoz je završen');
    }

    public function test_status_stock_and_commission_cannot_be_mapped(): void
    {
        $admin = $this->admin('imp-forced');
        $content = "Naziv;Status;Lager;Provizija\nForsiran;active;99;5\n";
        $this->actingAs($admin);
        $token = (string) $this->post('/admin/catalog/import/upload', ['file' => $this->csv($content)])->viewData('token');
        $this->post('/admin/catalog/import', $this->mappingPost($token, 'execute', ['mapping' => ['name' => '0', 'status' => '1', 'stock_quantity' => '2', 'manual_commission_eur' => '3']]));

        $product = Product::query()->where('name', 'Forsiran')->sole();
        self::assertSame('draft', $product->status);
        self::assertSame(0, (int) $product->stock_quantity);
        self::assertNull($product->manual_commission_eur);
    }

    public function test_windows_1250_semicolon_file_with_bom_keeps_diacritics(): void
    {
        $admin = $this->admin('imp-enc');
        $csv = "Naziv;Cena\nŠkolski računar ćžđ;10\n";
        $content = (string) iconv('UTF-8', 'Windows-1250', $csv);
        $this->actingAs($admin);
        $token = (string) $this->post('/admin/catalog/import/upload', ['file' => $this->csv($content)])->viewData('token');
        $this->post('/admin/catalog/import', $this->mappingPost($token, 'execute', ['mapping' => ['name' => '0', 'price_amount' => '1']]));

        self::assertTrue(Product::query()->where('name', 'Školski računar ćžđ')->exists());

        $bom = "\xEF\xBB\xBF".$csv;
        $token = (string) $this->post('/admin/catalog/import/upload', ['file' => $this->csv($bom)])->viewData('token');
        self::assertSame('Naziv', app(ProductImportService::class)->read($token, $admin)['headers'][0]);
    }

    public function test_size_and_row_limits_are_enforced(): void
    {
        $this->actingAs($this->admin('imp-limits'));
        $tooMany = "Naziv\n".implode("\n", array_map(static fn (int $i): string => 'Artikal '.$i, range(1, ProductImportService::MAX_ROWS + 1)))."\n";
        $this->post('/admin/catalog/import/upload', ['file' => $this->csv($tooMany)])->assertSessionHasErrors('file');

        $tooBig = "Naziv\n".str_repeat("Artikal sa dugim nazivom za test velicine fajla\n", 25000);
        self::assertGreaterThan(1048576, strlen($tooBig));
        $this->post('/admin/catalog/import/upload', ['file' => $this->csv($tooBig)])->assertSessionHasErrors('file');

        $this->post('/admin/catalog/import/upload', ['file' => $this->csv("Naziv\n")])->assertSessionHasErrors('file');
        self::assertSame(0, Product::query()->count());
    }

    public function test_uploaded_file_is_bound_to_the_uploading_user(): void
    {
        $owner = $this->admin('imp-owner');
        $other = $this->admin('imp-other');
        $token = (string) $this->actingAs($owner)->post('/admin/catalog/import/upload', ['file' => $this->csv("Naziv\nTuđ\n")])->viewData('token');

        $this->actingAs($other)->post('/admin/catalog/import', $this->mappingPost($token, 'execute', ['mapping' => ['name' => '0']]))
            ->assertSessionHasErrors('file');
        self::assertSame(0, Product::query()->count());
    }

    public function test_dot_is_a_thousands_separator_only_before_exactly_three_digits_and_price_is_shown(): void
    {
        $admin = $this->admin('imp-price');
        $content = "Naziv;Cena\nA;1.234\nB;12.5\nC;1.234,50\nD;0.500\nE;1234,5\n";
        $this->actingAs($admin);
        $token = (string) $this->post('/admin/catalog/import/upload', ['file' => $this->csv($content)])->viewData('token');
        $preview = $this->post('/admin/catalog/import', $this->mappingPost($token, 'preview', ['mapping' => ['name' => '0', 'price_amount' => '1']]))->assertOk();

        $prices = array_map(static fn (array $row): string => (string) $row['data']['price_amount'], $preview->viewData('analysis')['rows']);
        self::assertSame(['1234', '12.5', '1234.50', '0.500', '1234.5'], $prices);
        $preview->assertSee('data-import-price', false)->assertSee('1234 EUR');
    }

    public function test_second_submit_while_import_is_running_is_rejected(): void
    {
        $admin = $this->admin('imp-lock');
        $this->actingAs($admin);
        $token = (string) $this->post('/admin/catalog/import/upload', ['file' => $this->csv("Naziv\nDvaput\n")])->viewData('token');
        $lock = \Illuminate\Support\Facades\Cache::lock('product-import:'.$token, 60);
        self::assertTrue($lock->get());

        $this->post('/admin/catalog/import', $this->mappingPost($token, 'execute', ['mapping' => ['name' => '0']]))
            ->assertRedirect('/admin/catalog/import')->assertSessionHasErrors('file');
        self::assertSame(0, Product::query()->count());

        $lock->release();
        $this->post('/admin/catalog/import', $this->mappingPost($token, 'execute', ['mapping' => ['name' => '0']]))->assertRedirect('/admin/catalog/import');
        self::assertSame(1, Product::query()->count());
        // Treći pokušaj posle završetka: fajl je obrisan, nema novog artikla.
        $this->post('/admin/catalog/import', $this->mappingPost($token, 'execute', ['mapping' => ['name' => '0']]))->assertSessionHasErrors('file');
        self::assertSame(1, Product::query()->count());
    }

    public function test_retry_after_interruption_skips_rows_already_imported_and_writes_one_audit_record(): void
    {
        $admin = $this->admin('imp-resume');
        $svc = app(ProductImportService::class);
        $token = $svc->stash("Naziv\nPrvi\nDrugi\n", $admin, 'prekid.csv');
        $mapping = ['name' => '0'];
        $rows = $svc->read($token, $admin)['rows'];

        // Prvi pokušaj stiže samo do prvog reda (prekid), drugi dobija ceo fajl.
        $first = $svc->analyze([$rows[0]], $mapping, 'EUR', $admin);
        self::assertSame(1, $svc->execute($first, false, $admin, $token)['created']);

        $second = $svc->execute($svc->analyze($rows, $mapping, 'EUR', $admin), false, $admin, $token);
        self::assertSame(1, $second['created']);
        self::assertSame(1, $second['resumed']);
        self::assertSame(['Drugi', 'Prvi'], Product::query()->orderBy('name')->pluck('name')->all());

        $audits = AuditLog::query()->where('action', 'product.import')->get();
        self::assertCount(2, $audits);
        self::assertStringContainsString('prekid.csv', (string) $audits->last()->subject);
    }

    public function test_abandoned_import_files_are_removed_by_command_and_by_next_upload(): void
    {
        $admin = $this->admin('imp-clean');
        $disk = Storage::disk('local');
        $disk->put('product-imports/999/old.csv', "Naziv\nx\n");
        $disk->put('product-imports/999/fresh.csv', "Naziv\nx\n");
        touch($disk->path('product-imports/999/old.csv'), time() - 25 * 3600);

        $this->artisan('app:product-import-cleanup')->assertSuccessful();
        self::assertFalse($disk->exists('product-imports/999/old.csv'));
        self::assertTrue($disk->exists('product-imports/999/fresh.csv'));

        touch($disk->path('product-imports/999/fresh.csv'), time() - 25 * 3600);
        $this->actingAs($admin)->post('/admin/catalog/import/upload', ['file' => $this->csv("Naziv\nA\n")])->assertOk();
        self::assertFalse($disk->exists('product-imports/999/fresh.csv'));
    }

    public function test_long_cell_values_do_not_produce_unbounded_error_messages(): void
    {
        $admin = $this->admin('imp-long');
        $this->actingAs($admin);
        $token = (string) $this->post('/admin/catalog/import/upload', ['file' => $this->csv("Naziv;Brend\nA;".str_repeat('X', 5000)."\n")])->viewData('token');
        $analysis = $this->post('/admin/catalog/import', $this->mappingPost($token, 'preview', ['mapping' => ['name' => '0', 'brand' => '1']]))->viewData('analysis');
        self::assertNotEmpty($analysis['rows'][0]['errors']);
        foreach ($analysis['rows'][0]['errors'] as $message) {
            self::assertLessThanOrEqual(200, mb_strlen($message));
        }
    }

    private function csv(string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('artikli.csv', $content);
    }

    /** @param array<string,mixed> $extra @return array<string,mixed> */
    private function mappingPost(string $token, string $mode, array $extra = []): array
    {
        $post = array_replace([
            'token' => $token, 'mode' => $mode, 'default_currency' => 'EUR',
            'mapping' => ['name' => '0', 'brand' => '1', 'product_type' => '2', 'model_name' => '3', 'supplier_code' => '4', 'ean' => '5', 'price_amount' => '6', 'price_currency' => '7', 'description' => '8'],
        ], $extra);
        // Izvršenje traži hash mapiranja iz pregleda (kao skriveno polje u formi).
        $post += ['mapping_hash' => app(ProductImportService::class)->mappingHash($post['mapping'], $post['default_currency'])];

        return $post;
    }

    public function test_execute_requires_the_mapping_that_was_previewed(): void
    {
        $admin = $this->admin('imp-hash');
        $this->actingAs($admin);
        $token = (string) $this->post('/admin/catalog/import/upload', ['file' => $this->csv("Naziv;Opis\nHash;x\n")])->viewData('token');

        $stale = $this->mappingPost($token, 'execute', ['mapping' => ['name' => '0']]);
        $stale['mapping'] = ['name' => '1'];
        $this->post('/admin/catalog/import', $stale)->assertOk()->assertSee('Osveži pregled');
        self::assertSame(0, Product::query()->count());

        $missing = $this->mappingPost($token, 'execute', ['mapping' => ['name' => '0']]);
        unset($missing['mapping_hash']);
        $this->post('/admin/catalog/import', $missing)->assertOk()->assertSee('Osveži pregled');
        self::assertSame(0, Product::query()->count());

        $preview = $this->post('/admin/catalog/import', $this->mappingPost($token, 'preview', ['mapping' => ['name' => '0']]))->assertOk();
        $preview->assertSee('data-import-mapping-hash', false)->assertSee('data-import-price-rule', false);
        $this->post('/admin/catalog/import', $this->mappingPost($token, 'execute', ['mapping' => ['name' => '0']]))->assertRedirect('/admin/catalog/import');
        self::assertSame(1, Product::query()->count());
    }

    public function test_resume_bookkeeping_is_bound_to_the_mapping_hash(): void
    {
        $admin = $this->admin('imp-hash-run');
        $svc = app(ProductImportService::class);
        $token = $svc->stash("Naziv;Drugi\nPrvi;Zamena\n", $admin, 'hash.csv');
        $rows = $svc->read($token, $admin)['rows'];

        $a = ['name' => '0'];
        $b = ['name' => '1'];
        $first = $svc->execute($svc->analyze($rows, $a, 'EUR', $admin), false, $admin, $token, $svc->mappingHash($a, 'EUR'));
        $second = $svc->execute($svc->analyze($rows, $b, 'EUR', $admin), false, $admin, $token, $svc->mappingHash($b, 'EUR'));

        self::assertSame([1, 0, 1, 0], [$first['created'], $first['resumed'], $second['created'], $second['resumed']]);
        self::assertSame(['Prvi', 'Zamena'], Product::query()->orderBy('name')->pluck('name')->all());
    }

    public function test_row_left_pending_by_an_interrupted_attempt_is_not_duplicated(): void
    {
        $admin = $this->admin('imp-pending');
        $svc = app(ProductImportService::class);
        $token = $svc->stash("Naziv\nPadne\nOstaje\n", $admin, 'pad.csv');
        $mapping = ['name' => '0'];
        $hash = $svc->mappingHash($mapping, 'EUR');
        Storage::disk('local')->put('product-imports/'.$admin->id.'/'.$token.'.csv.meta', json_encode(['file' => 'pad.csv', 'runs' => [$hash => ['done' => [], 'pending' => [2]]]]));

        $result = $svc->execute($svc->analyze($svc->read($token, $admin)['rows'], $mapping, 'EUR', $admin), false, $admin, $token, $hash);

        self::assertSame(1, $result['created']);
        self::assertSame(['Ostaje'], Product::query()->pluck('name')->all());
        self::assertSame(2, $result['errors'][0]['line']);
        self::assertStringContainsString('možda već uvezen', $result['errors'][0]['message']);
        $meta = json_decode((string) Storage::disk('local')->get('product-imports/'.$admin->id.'/'.$token.'.csv.meta'), true);
        self::assertSame([2], $meta['runs'][$hash]['pending']);
        self::assertSame([3], $meta['runs'][$hash]['done']);
        self::assertSame([], array_filter(Storage::disk('local')->allFiles('product-imports'), static fn (string $f): bool => str_ends_with($f, '.tmp')));
    }

    public function test_corrupt_progress_file_is_an_error_not_an_empty_history(): void
    {
        $admin = $this->admin('imp-corrupt');
        $svc = app(ProductImportService::class);
        $token = $svc->stash("Naziv\nA\n", $admin, 'k.csv');
        Storage::disk('local')->put('product-imports/'.$admin->id.'/'.$token.'.csv.meta', '{ovo nije json');

        try {
            $svc->execute($svc->analyze($svc->read($token, $admin)['rows'], ['name' => '0'], 'EUR', $admin), false, $admin, $token, 'h');
            self::fail('Oštećena evidencija mora zaustaviti uvoz.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            self::assertStringContainsString('oštećena', $exception->errors()['file'][0]);
        }
        self::assertSame(0, Product::query()->count());
    }

    public function test_ambiguous_prices_follow_the_documented_rule_and_are_flagged(): void
    {
        $admin = $this->admin('imp-ambig');
        $this->actingAs($admin);
        $token = (string) $this->post('/admin/catalog/import/upload', ['file' => $this->csv("Naziv;Cena\nA;12.500\nB;1,234\nC;1.234,50\nD;12,5\n")])->viewData('token');
        $rows = $this->post('/admin/catalog/import', $this->mappingPost($token, 'preview', ['mapping' => ['name' => '0', 'price_amount' => '1']]))->assertOk()->viewData('analysis')['rows'];

        self::assertSame(['12500', '1.234', '1234.50', '12.5'], array_map(static fn (array $r): string => (string) $r['data']['price_amount'], $rows));
        self::assertStringContainsString('dvosmislena', implode(' ', $rows[0]['warnings']));
        self::assertStringContainsString('dvosmislena', implode(' ', $rows[1]['warnings']));
        self::assertSame([], $rows[2]['warnings']);
        self::assertSame([], $rows[3]['warnings']);
    }

    /** @param array<string,mixed> $extra @return array<string,mixed> */
    private function existing(User $admin, array $extra): array
    {
        return $extra + [
            'sku' => 'POSTOJI-1', 'name' => 'Postojeći artikal', 'slug' => 'postojeci-artikal', 'brand_id' => $this->brand->id,
            'price_amount' => 100, 'price_currency' => 'EUR', 'description' => 'Opis', 'stock_quantity' => 1,
            'low_stock_threshold' => 1, 'status' => 'active', 'created_by' => $admin->id, 'updated_by' => $admin->id,
        ];
    }

    private function admin(string $username): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', 'admin')->valueOrFail('id'),
            'username' => $username, 'email' => $username.'@test.local',
            'password_hash' => Hash::make('Secret123!'), 'status' => 'active',
        ]);
    }
}
