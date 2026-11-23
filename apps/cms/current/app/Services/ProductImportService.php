<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductLine;
use App\Models\ProductType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * CSV uvoz artikala: mapiranje kolona, pregled i izvršenje. Uvezeni artikli su uvek nacrt sa nultim lagerom;
 * kreiranje ide kroz ProductAdminService::create (SKU, slug, audit). Autor: Ald1n <https://ald1n.com>
 */
final class ProductImportService
{
    public const MAX_ROWS = 500;
    public const MAX_BYTES = 1048576;

    /** @var array<string,string> ciljno polje => oznaka */
    public const FIELDS = [
        'name' => 'Naziv',
        'brand' => 'Brend',
        'product_type' => 'Tip artikla',
        'product_line' => 'Linija proizvoda',
        'model_name' => 'Model',
        'supplier_code' => 'Šifra dobavljača',
        'ean' => 'EAN',
        'price_amount' => 'Cena',
        'price_currency' => 'Valuta',
        'purchase_price_rsd' => 'Nabavna cena RSD',
        'description' => 'Opis',
        'notes' => 'Interne napomene',
    ];

    private const ALIASES = [
        'name' => ['naziv', 'name', 'artikal', 'proizvod'],
        'brand' => ['brend', 'brand', 'proizvodjac', 'marka'],
        'product_type' => ['tip', 'tip artikla', 'type', 'kategorija'],
        'product_line' => ['linija', 'linija proizvoda', 'line', 'serija'],
        'model_name' => ['model', 'model proizvoda'],
        'supplier_code' => ['sifra dobavljaca', 'supplier code', 'supplier', 'sifra'],
        'ean' => ['ean', 'barkod', 'bar kod', 'barcode', 'gtin'],
        'price_amount' => ['cena', 'price', 'prodajna cena'],
        'price_currency' => ['valuta', 'currency'],
        'purchase_price_rsd' => ['nabavna cena', 'nabavna cena rsd', 'purchase price'],
        'description' => ['opis', 'description'],
        'notes' => ['napomena', 'napomene', 'notes'],
    ];

    public function __construct(private readonly ProductAdminService $products, private readonly AuditLogger $audit) {}

    /** Parsira sadržaj fajla i čuva ga pod nasumičnim tokenom vezanim za korisnika. */
    public function stash(string $raw, User $user, string $filename = ''): string
    {
        $this->parse($raw);
        $this->purgeOld();
        $token = Str::random(40);
        Storage::disk('local')->put($this->path($token, $user), $raw);
        $this->saveMeta($token, $user, ['file' => $filename, 'done' => []]);

        return $token;
    }

    /** @return array{headers:list<string>,rows:list<list<string>>} */
    public function read(string $token, User $user): array
    {
        if (!preg_match('/^[A-Za-z0-9]{40}$/', $token) || !Storage::disk('local')->exists($this->path($token, $user))) {
            throw ValidationException::withMessages(['file' => 'Otpremljeni fajl više nije dostupan. Otpremi ga ponovo.']);
        }

        return $this->parse((string) Storage::disk('local')->get($this->path($token, $user)));
    }

    public function forget(string $token, User $user): void
    {
        if (preg_match('/^[A-Za-z0-9]{40}$/', $token)) {
            Storage::disk('local')->delete([$this->path($token, $user), $this->path($token, $user).'.meta']);
        }
    }

    /** @param list<string> $headers @return array<string,string> ciljno polje => indeks kolone ili '' */
    public function guessMapping(array $headers): array
    {
        $normalized = array_map(fn (string $header): string => $this->plain($header), $headers);
        $mapping = [];
        foreach (self::ALIASES as $field => $aliases) {
            $mapping[$field] = '';
            foreach ($normalized as $index => $header) {
                if (in_array($header, $aliases, true)) {
                    $mapping[$field] = (string) $index;
                    break;
                }
            }
        }

        return $mapping;
    }

    /**
     * @param list<list<string>> $rows
     * @param array<string,mixed> $mapping
     * @return array{rows:list<array<string,mixed>>,valid:int,errors:int,duplicates:int}
     */
    public function analyze(array $rows, array $mapping, string $defaultCurrency, User $user): array
    {
        $brands = Brand::query()->where('status', 'active')->get(['id', 'name'])->keyBy(fn (Brand $b): string => mb_strtolower(trim((string) $b->name)));
        $types = ProductType::query()->where('status', 'active')->get()->keyBy(fn (ProductType $t): string => mb_strtolower(trim((string) $t->name)));
        $lines = ProductLine::query()->where('status', 'active')->get(['id', 'name', 'brand_id']);
        $brandTypes = DB::table('brand_product_type')->get(['brand_id', 'product_type_id'])->map(fn ($r): string => $r->brand_id.'|'.$r->product_type_id)->flip();
        $lineTypes = DB::table('product_line_product_type')->get(['product_line_id', 'product_type_id'])->map(fn ($r): string => $r->product_line_id.'|'.$r->product_type_id)->flip();
        $currency = in_array($defaultCurrency, ['RSD', 'EUR'], true) ? $defaultCurrency : 'EUR';
        $seen = [];
        $result = [];

        foreach ($rows as $index => $row) {
            $cell = fn (string $field): ?string => $this->cell($row, $mapping[$field] ?? '');
            $errors = [];
            $warnings = [];

            $brand = $cell('brand') !== null ? $brands->get(mb_strtolower((string) $cell('brand'))) : null;
            if ($cell('brand') !== null && $brand === null) $errors[] = 'Brend „'.$cell('brand').'“ ne postoji ili nije aktivan.';
            $type = $cell('product_type') !== null ? $types->get(mb_strtolower((string) $cell('product_type'))) : null;
            if ($cell('product_type') !== null && $type === null) $errors[] = 'Tip „'.$cell('product_type').'“ ne postoji ili nije aktivan.';
            $line = null;
            if ($cell('product_line') !== null) {
                $line = $brand !== null
                    ? $lines->first(fn (ProductLine $l): bool => (int) $l->brand_id === (int) $brand->id && mb_strtolower(trim((string) $l->name)) === mb_strtolower((string) $cell('product_line')))
                    : null;
                if ($line === null) $errors[] = 'Linija „'.$cell('product_line').'“ ne postoji za izabrani brend.';
            }
            if ($type !== null && $brand !== null && !$brandTypes->has($brand->id.'|'.$type->id)) $errors[] = 'Brend nije dostupan za izabrani tip artikla.';
            if ($type !== null && $line !== null && !$lineTypes->has($line->id.'|'.$type->id)) $errors[] = 'Linija nije dostupna za izabrani tip artikla.';

            $rowCurrency = $cell('price_currency') !== null ? mb_strtoupper((string) $cell('price_currency')) : $currency;
            $data = [
                'product_type_id' => $type?->id,
                'brand_id' => $brand?->id,
                'product_line_id' => $line?->id,
                'model_name' => $cell('model_name') !== null ? (preg_replace('/\s+/u', ' ', (string) $cell('model_name')) ?: null) : null,
                'supplier_code' => $cell('supplier_code'),
                'ean' => $cell('ean') !== null ? (preg_replace('/[\s-]+/', '', (string) $cell('ean')) ?: null) : null,
                'name' => (string) ($cell('name') ?? ''),
                'price_amount' => $this->number($cell('price_amount')) ?? '0',
                'price_currency' => $rowCurrency,
                'purchase_price_rsd' => $this->number($cell('purchase_price_rsd')),
                'description' => (string) ($cell('description') ?? ''),
                'notes' => $cell('notes'),
                'stock_quantity' => 0,
                'low_stock_threshold' => 1,
                'status' => 'draft',
                'category_ids' => $type?->category_id !== null ? [(int) $type->category_id] : [],
            ];

            if ($data['name'] === '' && !(bool) ($type?->auto_name_enabled ?? false)) $errors[] = 'Naziv je obavezan (ili tip sa automatskim nazivom).';
            $validator = Validator::make($data, [
                'name' => ['nullable', 'string', 'max:190'],
                'model_name' => ['nullable', 'string', 'max:190'],
                'supplier_code' => ['nullable', 'string', 'max:100'],
                'ean' => ['nullable', 'string', 'regex:/^[0-9]{8,14}$/'],
                'price_amount' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
                'price_currency' => ['required', 'in:RSD,EUR'],
                'purchase_price_rsd' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
                'description' => ['nullable', 'string', 'max:65000'],
                'notes' => ['nullable', 'string', 'max:65000'],
            ], [], ['name' => 'Naziv', 'model_name' => 'Model', 'supplier_code' => 'Šifra dobavljača', 'ean' => 'EAN', 'price_amount' => 'Cena', 'price_currency' => 'Valuta', 'purchase_price_rsd' => 'Nabavna cena', 'description' => 'Opis', 'notes' => 'Napomena']);
            foreach ($validator->errors()->all() as $message) $errors[] = (string) $message;

            $duplicate = false;
            if ($data['brand_id'] !== null && $data['model_name'] !== null) {
                $key = 'm|'.$data['brand_id'].'|'.mb_strtolower($data['model_name']);
                if (isset($seen[$key]) || Product::query()->whereNull('deleted_at')->where('brand_id', $data['brand_id'])->whereRaw('LOWER(model_name) = ?', [mb_strtolower($data['model_name'])])->exists()) {
                    $duplicate = true;
                    $warnings[] = 'Duplikat: isti brend i model već postoje'.(isset($seen[$key]) ? ' u fajlu (red '.$seen[$key].')' : ' u katalogu').'.';
                }
                $seen[$key] ??= $index + 2;
            }
            if ($data['ean'] !== null && Product::hasIdentifierColumns()) {
                $key = 'e|'.$data['ean'];
                if (isset($seen[$key]) || Product::query()->whereNull('deleted_at')->where('ean', $data['ean'])->exists()) {
                    $duplicate = true;
                    $warnings[] = 'Duplikat: EAN '.$data['ean'].' već postoji'.(isset($seen[$key]) ? ' u fajlu (red '.$seen[$key].')' : ' u katalogu').'.';
                }
                $seen[$key] ??= $index + 2;
            }

            $errors = array_map(static fn (string $m): string => Str::limit($m, 197), $errors);
            $warnings = array_map(static fn (string $m): string => Str::limit($m, 197), $warnings);
            $result[] = ['line' => $index + 2, 'label' => $data['name'] !== '' ? $data['name'] : (string) ($data['model_name'] ?? '—'), 'data' => $data, 'errors' => $errors, 'warnings' => $warnings, 'duplicate' => $duplicate];
        }

        return [
            'rows' => $result,
            'valid' => count(array_filter($result, static fn (array $r): bool => $r['errors'] === [])),
            'errors' => count(array_filter($result, static fn (array $r): bool => $r['errors'] !== [])),
            'duplicates' => count(array_filter($result, static fn (array $r): bool => $r['duplicate'])),
        ];
    }

    /**
     * @param array{rows:list<array<string,mixed>>} $analysis
     * @return array{created:int,skipped:int,errors:list<array{line:int,message:string}>}
     */
    public function execute(array $analysis, bool $skipDuplicates, User $user, string $token = ''): array
    {
        @set_time_limit(300);
        $meta = $token !== '' ? $this->meta($token, $user) : ['file' => '', 'done' => []];
        $done = array_flip($meta['done']);
        $created = 0;
        $resumed = 0;
        $skipped = 0;
        $errors = [];
        foreach ($analysis['rows'] as $row) {
            if (isset($done[$row['line']])) {
                // Red je uvezen u prekinutom pokušaju: ponovni pokušaj ga ne sme duplirati.
                $resumed++;
                continue;
            }
            if ($row['errors'] !== []) {
                $errors[] = ['line' => $row['line'], 'message' => Str::limit(implode(' ', $row['errors']), 300)];
                continue;
            }
            if ($skipDuplicates && $row['duplicate']) {
                $skipped++;
                continue;
            }
            try {
                $this->products->create($row['data'], $user);
                $created++;
                if ($token !== '') {
                    $meta['done'][] = $row['line'];
                    $this->saveMeta($token, $user, $meta);
                }
            } catch (ValidationException $exception) {
                $errors[] = ['line' => $row['line'], 'message' => Str::limit(implode(' ', $exception->validator->errors()->all()), 300)];
            } catch (\Throwable $exception) {
                // Jedan neočekivan red ne sme da prekine uvoz usred fajla i ostavi nejasno stanje.
                report($exception);
                $errors[] = ['line' => $row['line'], 'message' => 'Red nije uvezen zbog neočekivane greške.'];
            }
        }

        $this->audit->log(
            'product.import',
            'CSV uvoz artikala'.($meta['file'] !== '' ? ': '.Str::limit($meta['file'], 120) : '').' (uvezeno '.$created.', preskočeno '.$skipped.', grešaka '.count($errors).')',
            null,
            null,
            null,
            ['file' => Str::limit($meta['file'], 120), 'rows' => count($analysis['rows']), 'created' => $created, 'resumed' => $resumed, 'skipped' => $skipped, 'errors' => count($errors)],
            $user,
        );

        return ['created' => $created, 'skipped' => $skipped, 'resumed' => $resumed, 'errors' => $errors];
    }

    /** @return array{file:string,done:list<int>} */
    private function meta(string $token, User $user): array
    {
        $raw = Storage::disk('local')->exists($this->path($token, $user).'.meta') ? json_decode((string) Storage::disk('local')->get($this->path($token, $user).'.meta'), true) : null;

        return ['file' => (string) ($raw['file'] ?? ''), 'done' => array_values(array_map('intval', (array) ($raw['done'] ?? [])))];
    }

    /** @param array{file:string,done:list<int>} $meta */
    private function saveMeta(string $token, User $user, array $meta): void
    {
        Storage::disk('local')->put($this->path($token, $user).'.meta', json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    /** @return array{headers:list<string>,rows:list<list<string>>} */
    private function parse(string $raw): array
    {
        if (strlen($raw) > self::MAX_BYTES) {
            throw ValidationException::withMessages(['file' => 'Fajl je veći od 1 MB.']);
        }
        $raw = (string) preg_replace('/^\xEF\xBB\xBF/', '', $raw);
        if (!mb_check_encoding($raw, 'UTF-8')) {
            // mbstring nema Windows-1250, zato iconv; ISO-8859-2 je rezerva ako iconv ne prepozna kodnu stranu.
            $converted = function_exists('iconv') ? @iconv('Windows-1250', 'UTF-8//IGNORE', $raw) : false;
            $raw = $converted !== false ? $converted : mb_convert_encoding($raw, 'UTF-8', 'ISO-8859-2');
        }
        $first = (string) strtok($raw, "\n");
        $counts = [';' => substr_count($first, ';'), ',' => substr_count($first, ','), "\t" => substr_count($first, "\t")];
        arsort($counts);
        $delimiter = (string) array_key_first($counts);

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $raw);
        rewind($handle);
        $all = [];
        while (($record = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
            $record = array_map(static fn ($v): string => trim((string) $v), $record);
            if (implode('', $record) === '') continue;
            $all[] = $record;
            if (count($all) > self::MAX_ROWS + 1) break;
        }
        fclose($handle);

        if (count($all) < 2) {
            throw ValidationException::withMessages(['file' => 'Fajl mora imati red sa zaglavljem i bar jedan red sa podacima.']);
        }
        if (count($all) - 1 > self::MAX_ROWS) {
            throw ValidationException::withMessages(['file' => 'Fajl ima više od '.self::MAX_ROWS.' redova. Podeli ga na manje fajlove.']);
        }

        return ['headers' => array_shift($all), 'rows' => $all];
    }

    /** @param list<string> $row */
    private function cell(array $row, mixed $column): ?string
    {
        if ($column === '' || $column === null || !isset($row[(int) $column])) return null;
        $value = trim($row[(int) $column]);

        return $value === '' ? null : $value;
    }

    private function number(?string $value): ?string
    {
        if ($value === null) return null;
        $value = str_replace(' ', '', $value);
        // Tačka je separator hiljada kad iza nje slede tačno tri cifre (1.234 ili 1.234,50); inače je decimalna (12.5).
        $value = preg_match('/^[1-9]\d{0,2}(\.\d{3})+(,\d+)?$/', $value) ? str_replace('.', '', $value) : $value;

        return str_replace(',', '.', $value);
    }

    private function plain(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = strtr($value, ['š' => 's', 'đ' => 'dj', 'č' => 'c', 'ć' => 'c', 'ž' => 'z']);

        return trim((string) preg_replace('/[^a-z0-9]+/', ' ', $value));
    }

    private function path(string $token, User $user): string
    {
        return 'product-imports/'.$user->id.'/'.$token.'.csv';
    }

    /** Briše napuštene uvozne fajlove (svih korisnika) starije od zadatog broja sati; vraća broj obrisanih. */
    public function purgeOld(int $hours = 24): int
    {
        $disk = Storage::disk('local');
        $deleted = 0;
        foreach ($disk->allFiles('product-imports') as $file) {
            if ($disk->lastModified($file) < now()->subHours($hours)->getTimestamp()) {
                $disk->delete($file);
                $deleted++;
            }
        }

        return $deleted;
    }
}
