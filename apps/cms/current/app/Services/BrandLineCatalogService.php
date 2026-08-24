<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

// MOBILE_V1_0_BRAND_LINE_EXPANSION_BATCH22_V3
final class BrandLineCatalogService
{
    public const MAX_LINES_PER_TYPE = 10;

    /** @var array<string,array<string,list<string>>> */
    private const CATALOG = [
        'laptop' => [
            'dell' => ['Latitude', 'Precision', 'Vostro', 'Inspiron', 'XPS', 'Alienware', 'G Series'],
            'hp' => ['EliteBook', 'ProBook', 'Pavilion', 'Envy', 'ZBook', 'Spectre', 'Victus', 'OMEN'],
            'lenovo' => ['ThinkPad', 'ThinkBook', 'IdeaPad', 'Yoga', 'Legion', 'LOQ'],
            'asus' => ['Zenbook', 'Vivobook', 'ExpertBook', 'ProArt Studiobook', 'ROG', 'TUF Gaming'],
            'acer' => ['Aspire', 'Swift', 'TravelMate', 'Extensa', 'Nitro', 'Predator'],
            'msi' => ['Modern', 'Prestige', 'Summit', 'Creator', 'Stealth', 'Raider', 'Vector', 'Katana', 'Cyborg'],
            'apple' => ['MacBook Air', 'MacBook Pro'],
            'microsoft' => ['Surface Laptop', 'Surface Pro', 'Surface Book'],
            'huawei' => ['MateBook', 'MateBook D', 'MateBook X Pro'],
            'honor' => ['MagicBook'],
            'samsung' => ['Galaxy Book', 'Galaxy Book Pro', 'Galaxy Book Ultra'],
            'toshiba' => ['Satellite', 'Tecra', 'Portege'],
            'dynabook' => ['Satellite', 'Tecra', 'Portege'],
        ],
        'desktop' => [
            'dell' => ['OptiPlex', 'Precision', 'XPS', 'Alienware'],
            'hp' => ['ProDesk', 'EliteDesk', 'Z', 'OMEN', 'Victus'],
            'lenovo' => ['ThinkCentre', 'ThinkStation', 'IdeaCentre', 'Legion'],
            'asus' => ['ExpertCenter', 'ROG', 'ProArt'],
            'acer' => ['Veriton', 'Aspire', 'Predator', 'Nitro'],
            'apple' => ['Mac mini', 'Mac Studio', 'iMac', 'Mac Pro'],
        ],
        'ssd' => [
            'samsung' => ['870 EVO', '870 QVO', '970 EVO Plus', '980', '980 PRO', '990 EVO', '990 PRO'],
            'kingston' => ['A400', 'KC600', 'NV2', 'NV3', 'KC3000', 'FURY Renegade'],
            'westerndigital' => ['Green', 'Blue', 'Black', 'Red', 'Purple'],
            'crucial' => ['BX500', 'MX500', 'P3', 'P3 Plus', 'P5 Plus', 'T500'],
            'skhynix' => ['Gold P31', 'Platinum P41'],
            'micron' => ['2400', '2450', '2500', '2550', '3400', '3500'],
            'sandisk' => ['SSD Plus', 'Ultra 3D', 'Extreme'],
            'seagate' => ['BarraCuda SSD', 'FireCuda SSD'],
        ],
        'hdd' => [
            'westerndigital' => ['Blue', 'Black', 'Red', 'Purple', 'Gold'],
            'seagate' => ['BarraCuda', 'IronWolf', 'SkyHawk', 'Exos', 'FireCuda'],
            'toshiba' => ['P300', 'N300', 'X300', 'S300', 'MG Series'],
        ],
        'ram' => [
            'kingston' => ['ValueRAM', 'FURY Beast', 'FURY Impact', 'FURY Renegade', 'Server Premier'],
            'crucial' => ['Classic', 'Pro', 'Ballistix'],
            'corsair' => ['Vengeance', 'Dominator', 'ValueSelect'],
            'gskill' => ['Ripjaws', 'Trident Z', 'Flare X'],
            'patriot' => ['Signature', 'Viper Steel', 'Viper Venom'],
            'teamgroup' => ['Elite', 'T-Force Vulcan', 'T-Force Delta'],
        ],
        'monitor' => [
            'dell' => ['UltraSharp', 'P Series', 'S Series', 'E Series', 'Alienware'],
            'hp' => ['E Series', 'P Series', 'Z Display', 'OMEN'],
            'lenovo' => ['ThinkVision', 'Legion'],
            'asus' => ['ProArt', 'TUF Gaming', 'ROG Strix', 'ZenScreen'],
            'acer' => ['Nitro', 'Predator', 'Vero', 'Business'],
            'samsung' => ['Odyssey', 'ViewFinity', 'Smart Monitor', 'Essential Monitor'],
            'lg' => ['UltraGear', 'UltraWide', 'Ergo', 'MyView'],
            'msi' => ['Optix', 'MAG', 'MPG', 'Modern MD'],
            'benq' => ['MOBIUZ', 'ZOWIE', 'DesignVue', 'PhotoVue'],
        ],
        'gpu' => [
            'asus' => ['ROG Strix', 'TUF Gaming', 'Dual', 'ProArt'],
            'msi' => ['SUPRIM', 'Gaming X', 'Ventus', 'Expert'],
            'gigabyte' => ['AORUS', 'Gaming OC', 'Windforce', 'Eagle'],
            'zotac' => ['AMP', 'Trinity', 'Twin Edge', 'Solid'],
            'palit' => ['GameRock', 'JetStream', 'GamingPro', 'Dual'],
            'gainward' => ['Phantom', 'Phoenix', 'Ghost'],
            'sapphire' => ['NITRO+', 'PULSE', 'PURE'],
            'powercolor' => ['Red Devil', 'Hellhound', 'Fighter'],
            'pny' => ['XLR8', 'VERTO'],
            'xfx' => ['MERC', 'QICK', 'SWFT'],
        ],
        'motherboard' => [
            'asus' => ['ROG', 'TUF Gaming', 'Prime', 'ProArt'],
            'msi' => ['MEG', 'MPG', 'MAG', 'PRO'],
            'gigabyte' => ['AORUS', 'Gaming X', 'UD', 'AERO'],
            'asrock' => ['Taichi', 'Steel Legend', 'Phantom Gaming', 'Pro RS'],
            'biostar' => ['VALKYRIE', 'RACING', 'PRO'],
        ],
        'psu' => [
            'seasonic' => ['PRIME', 'FOCUS', 'CORE', 'VERTEX', 'G12'],
            'corsair' => ['RMx', 'RMe', 'HX', 'CX', 'SF'],
            'bequiet' => ['Dark Power', 'Straight Power', 'Pure Power', 'System Power'],
            'thermaltake' => ['Toughpower', 'Smart', 'TR2', 'Litepower'],
            'coolermaster' => ['MWE', 'V Series', 'XG', 'GX'],
            'asus' => ['ROG Thor', 'ROG Strix', 'TUF Gaming', 'Prime'],
            'msi' => ['MEG', 'MPG', 'MAG'],
            'gigabyte' => ['AORUS', 'UD', 'P Series'],
        ],
        'case' => [
            'coolermaster' => ['MasterBox', 'HAF', 'NR Series', 'COSMOS'],
            'corsair' => ['4000 Series', '5000 Series', '7000 Series', 'iCUE'],
            'nzxt' => ['H Series', 'Flow'],
            'thermaltake' => ['Core', 'View', 'Divider', 'The Tower'],
            'bequiet' => ['Pure Base', 'Silent Base', 'Dark Base', 'Light Base'],
            'fractaldesign' => ['Define', 'Meshify', 'Pop', 'North'],
        ],
        'cooling' => [
            'noctua' => ['NH Series', 'NF Series'],
            'coolermaster' => ['Hyper', 'MasterLiquid', 'MasterFan'],
            'corsair' => ['iCUE H Series', 'A115', 'RS Series'],
            'bequiet' => ['Dark Rock', 'Pure Rock', 'Silent Loop', 'Light Loop'],
            'thermaltake' => ['TOUGHLIQUID', 'TH Series', 'UX Series'],
        ],
        'keyboard' => [
            'logitech' => ['MX Keys', 'K Series', 'G Series', 'Wave Keys'],
            'steelseries' => ['Apex'],
            'razer' => ['BlackWidow', 'Huntsman', 'DeathStalker', 'Ornata'],
            'corsair' => ['K Series'],
            'hyperx' => ['Alloy'],
            'asus' => ['ROG', 'TUF Gaming'],
            'msi' => ['Vigor'],
        ],
        'mouse' => [
            'logitech' => ['MX Master', 'M Series', 'G Series', 'Lift'],
            'steelseries' => ['Rival', 'Aerox', 'Prime'],
            'razer' => ['DeathAdder', 'Basilisk', 'Viper', 'Naga'],
            'corsair' => ['M Series', 'Dark Core', 'Katar', 'Sabre'],
            'hyperx' => ['Pulsefire'],
            'asus' => ['ROG', 'TUF Gaming'],
        ],
        'headset' => [
            'steelseries' => ['Arctis'],
            'logitech' => ['G Series', 'ASTRO'],
            'razer' => ['BlackShark', 'Kraken', 'Barracuda'],
            'hyperx' => ['Cloud'],
            'corsair' => ['HS Series', 'VIRTUOSO', 'VOID'],
            'asus' => ['ROG', 'TUF Gaming'],
        ],
        'cpu' => [
            'intel' => ['Core i3', 'Core i5', 'Core i7', 'Core i9', 'Core Ultra', 'Xeon'],
            'amd' => ['Ryzen 3', 'Ryzen 5', 'Ryzen 7', 'Ryzen 9', 'Ryzen Threadripper', 'EPYC'],
        ],
    ];

    /** @var array<string,string> */
    private const BRAND_ALIASES = [
        'dell' => 'dell', 'hp' => 'hp', 'hpinc' => 'hp', 'hewlettpackard' => 'hp',
        'lenovo' => 'lenovo', 'asus' => 'asus', 'asustek' => 'asus', 'acer' => 'acer',
        'msi' => 'msi', 'microstar' => 'msi', 'apple' => 'apple', 'microsoft' => 'microsoft',
        'huawei' => 'huawei', 'honor' => 'honor', 'samsung' => 'samsung', 'toshiba' => 'toshiba',
        'dynabook' => 'dynabook', 'kingston' => 'kingston', 'kingstontechnology' => 'kingston',
        'westerndigital' => 'westerndigital', 'wd' => 'westerndigital', 'crucial' => 'crucial',
        'skhynix' => 'skhynix', 'hynix' => 'skhynix', 'micron' => 'micron', 'sandisk' => 'sandisk',
        'seagate' => 'seagate', 'corsair' => 'corsair', 'gskill' => 'gskill', 'patriot' => 'patriot',
        'teamgroup' => 'teamgroup', 'teamgroupinc' => 'teamgroup', 'lg' => 'lg', 'lgelectronics' => 'lg',
        'gigabyte' => 'gigabyte', 'zotac' => 'zotac', 'palit' => 'palit', 'gainward' => 'gainward',
        'sapphire' => 'sapphire', 'powercolor' => 'powercolor', 'pny' => 'pny', 'asrock' => 'asrock',
        'xfx' => 'xfx', 'biostar' => 'biostar', 'benq' => 'benq', 'seasonic' => 'seasonic',
        'bequiet' => 'bequiet', 'thermaltake' => 'thermaltake', 'coolermaster' => 'coolermaster',
        'logitech' => 'logitech', 'steelseries' => 'steelseries', 'razer' => 'razer', 'hyperx' => 'hyperx',
        'nzxt' => 'nzxt', 'fractaldesign' => 'fractaldesign', 'noctua' => 'noctua',
        'intel' => 'intel', 'amd' => 'amd',
    ];

    /** @return array<string,mixed> */
    public function preview(): array
    {
        return $this->process(false);
    }

    /** @return array<string,mixed> */
    public function apply(): array
    {
        return DB::transaction(function (): array {
            $productHash = $this->tableHash('products');
            $brandTypeHash = $this->tableHash('brand_product_type');
            $result = $this->process(true);
            $verification = $this->verifyCoreFamilies();
            if ($verification['failures'] !== []) {
                throw new RuntimeException('Brand line core verification failed: '.implode(' | ', $verification['failures']));
            }
            if ($productHash !== $this->tableHash('products')) {
                throw new RuntimeException('Product rows changed during brand line enrichment.');
            }
            if ($brandTypeHash !== $this->tableHash('brand_product_type')) {
                throw new RuntimeException('Brand/product-type scope changed during additive line enrichment.');
            }
            app(CatalogReferenceCache::class)->forget();
            $result['verification'] = $verification;
            return $result;
        }, 3);
    }

    /** @return array<string,mixed> */
    private function process(bool $apply): array
    {
        foreach (['brands', 'brand_product_type', 'product_types', 'product_lines', 'product_line_product_type'] as $table) {
            if (!Schema::hasTable($table)) throw new RuntimeException('Required catalog table missing: '.$table);
        }
        $typesByBrand = [];
        $rows = DB::table('brand_product_type as bpt')
            ->join('product_types as pt', 'pt.id', '=', 'bpt.product_type_id')
            ->leftJoin('categories as c', 'c.id', '=', 'pt.category_id')
            ->orderBy('bpt.brand_id')->orderBy('bpt.product_type_id')
            ->get(['bpt.brand_id', 'pt.id as product_type_id', 'pt.name as product_type_name', 'c.name as category_name']);
        foreach ($rows as $row) $typesByBrand[(int) $row->brand_id][] = $row;

        $stats = [
            'mode' => $apply ? 'apply' : 'preview', 'brands_considered' => 0, 'linked_types_considered' => 0,
            'catalog_type_matches' => 0, 'line_candidates' => 0, 'lines_created' => 0, 'links_created' => 0,
            'already_present' => 0, 'skipped_inactive_existing_line' => 0, 'by_group' => [],
        ];
        $brands = DB::table('brands')->orderBy('id')->get(['id', 'name']);
        foreach ($brands as $brand) {
            $brandKey = $this->brandKey((string) $brand->name);
            if ($brandKey === null) continue;
            $stats['brands_considered']++;
            foreach ($typesByBrand[(int) $brand->id] ?? [] as $type) {
                $stats['linked_types_considered']++;
                $group = $this->typeGroup((string) $type->product_type_name, $type->category_name !== null ? (string) $type->category_name : null);
                if ($group === null) continue;
                $families = self::CATALOG[$group][$brandKey] ?? [];
                if ($families === []) continue;
                if (count($families) > self::MAX_LINES_PER_TYPE) throw new RuntimeException('Curated catalog exceeds max line limit for '.$brandKey.'/'.$group);
                $stats['catalog_type_matches']++;
                $stats['by_group'][$group] = ((int) ($stats['by_group'][$group] ?? 0)) + 1;
                foreach ($families as $family) {
                    $stats['line_candidates']++;
                    $line = DB::table('product_lines')->where('brand_id', (int) $brand->id)
                        ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($family))])->first();
                    $lineId = $line !== null ? (int) $line->id : null;
                    if ($line !== null && property_exists($line, 'status') && (string) $line->status !== 'active') {
                        $stats['skipped_inactive_existing_line']++;
                        continue;
                    }
                    if ($lineId === null) {
                        if (!$apply) {
                            $stats['lines_created']++;
                            $stats['links_created']++;
                            continue;
                        }
                        $lineId = $this->createLine((int) $brand->id, (string) $brand->name, $family);
                        $stats['lines_created']++;
                    }
                    $linked = DB::table('product_line_product_type')->where('product_line_id', $lineId)
                        ->where('product_type_id', (int) $type->product_type_id)->exists();
                    if ($linked) {
                        $stats['already_present']++;
                        continue;
                    }
                    if ($apply) {
                        DB::table('product_line_product_type')->insertOrIgnore([
                            'product_line_id' => $lineId, 'product_type_id' => (int) $type->product_type_id,
                        ]);
                    }
                    $stats['links_created']++;
                }
            }
        }
        ksort($stats['by_group']);
        return $stats;
    }

    private function createLine(int $brandId, string $brandName, string $lineName): int
    {
        $row = ['brand_id' => $brandId, 'name' => $lineName, 'slug' => $this->uniqueSlug($brandName, $lineName)];
        if (Schema::hasColumn('product_lines', 'status')) $row['status'] = 'active';
        if (Schema::hasColumn('product_lines', 'sort_order')) {
            $row['sort_order'] = ((int) DB::table('product_lines')->where('brand_id', $brandId)->max('sort_order')) + 10;
        }
        if (Schema::hasColumn('product_lines', 'created_at')) $row['created_at'] = now();
        if (Schema::hasColumn('product_lines', 'updated_at')) $row['updated_at'] = now();
        return (int) DB::table('product_lines')->insertGetId($row);
    }

    private function uniqueSlug(string $brandName, string $lineName): string
    {
        $base = Str::slug($brandName.' '.$lineName);
        if ($base === '') $base = 'brand-line';
        $slug = $base;
        $counter = 2;
        while (DB::table('product_lines')->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }
        return $slug;
    }

    private function brandKey(string $name): ?string
    {
        $normalized = preg_replace('/[^a-z0-9]+/', '', Str::lower(Str::ascii(trim($name)))) ?: '';
        return self::BRAND_ALIASES[$normalized] ?? null;
    }

    private function typeGroup(string $typeName, ?string $categoryName): ?string
    {
        $text = Str::lower(Str::ascii(trim($typeName.' '.($categoryName ?? ''))));
        if ($this->containsAny($text, ['laptop', 'notebook', 'prenosni', 'portable'])) return 'laptop';
        if ($this->containsAny($text, ['ssd', 'solid state'])) return 'ssd';
        if ($this->containsAny($text, ['hdd', 'hard disk', 'harddisk'])) return 'hdd';
        if ($this->containsAny($text, ['ram', 'memorija', 'memory'])) return 'ram';
        if ($this->containsAny($text, ['monitor', 'display', 'ekran'])) return 'monitor';
        if ($this->containsAny($text, ['graficka', 'graphics', 'gpu', 'video kart'])) return 'gpu';
        if ($this->containsAny($text, ['maticna', 'motherboard'])) return 'motherboard';
        if ($this->containsAny($text, ['procesor', 'processor', 'cpu'])) return 'cpu';
        if ($this->containsAny($text, ['napajanje', 'power supply', 'psu'])) return 'psu';
        if ($this->containsAny($text, ['kuciste', 'case', 'chassis'])) return 'case';
        if ($this->containsAny($text, ['hladjenje', 'cooling', 'cooler', 'ventilator', 'fan'])) return 'cooling';
        if ($this->containsAny($text, ['tastatura', 'keyboard'])) return 'keyboard';
        if ($this->containsAny($text, ['mis', 'mouse'])) return 'mouse';
        if ($this->containsAny($text, ['slusalice', 'headset', 'headphone'])) return 'headset';
        if ($this->containsAny($text, ['desktop', 'workstation', 'radna stanica', 'all in one', 'all-in-one', 'aio'])) return 'desktop';
        return null;
    }

    /** @param list<string> $needles */
    private function containsAny(string $text, array $needles): bool
    {
        foreach ($needles as $needle) if (str_contains($text, $needle)) return true;
        return false;
    }

    /** @return array{checked:int,failures:list<string>} */
    private function verifyCoreFamilies(): array
    {
        $required = ['dell' => ['Vostro', 'Inspiron'], 'hp' => ['Envy', 'Pavilion'], 'lenovo' => ['ThinkBook']];
        $failures = [];
        $checked = 0;
        $brands = DB::table('brands')->orderBy('id')->get(['id', 'name']);
        foreach ($brands as $brand) {
            $key = $this->brandKey((string) $brand->name);
            if ($key === null || !isset($required[$key])) continue;
            $types = DB::table('brand_product_type as bpt')->join('product_types as pt', 'pt.id', '=', 'bpt.product_type_id')
                ->leftJoin('categories as c', 'c.id', '=', 'pt.category_id')->where('bpt.brand_id', (int) $brand->id)
                ->get(['pt.id', 'pt.name', 'c.name as category_name']);
            foreach ($types as $type) {
                if ($this->typeGroup((string) $type->name, $type->category_name !== null ? (string) $type->category_name : null) !== 'laptop') continue;
                foreach ($required[$key] as $family) {
                    $checked++;
                    $line = DB::table('product_lines')->where('brand_id', (int) $brand->id)
                        ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($family)])->first();
                    if ($line === null) {
                        $failures[] = $brand->name.' / '.$type->name.' missing '.$family;
                        continue;
                    }
                    if (property_exists($line, 'status') && (string) $line->status !== 'active') {
                        $failures[] = $brand->name.' / '.$family.' exists but is inactive';
                        continue;
                    }
                    if (!DB::table('product_line_product_type')->where('product_line_id', (int) $line->id)
                        ->where('product_type_id', (int) $type->id)->exists()) {
                        $failures[] = $brand->name.' / '.$type->name.' / '.$family.' is not type-scoped';
                    }
                }
            }
        }
        return ['checked' => $checked, 'failures' => $failures];
    }

    private function tableHash(string $table): string
    {
        if (!Schema::hasTable($table)) throw new RuntimeException('Cannot fingerprint missing table: '.$table);
        $columns = Schema::getColumnListing($table);
        sort($columns);
        $query = DB::table($table)->select($columns);
        if (in_array('id', $columns, true)) {
            $query->orderBy('id');
        } else {
            foreach (['brand_id', 'product_line_id', 'product_type_id'] as $column) {
                if (in_array($column, $columns, true)) $query->orderBy($column);
            }
        }
        $rows = $query->get()->map(static fn ($row): array => (array) $row)->all();
        return hash('sha256', serialize($rows));
    }
}
