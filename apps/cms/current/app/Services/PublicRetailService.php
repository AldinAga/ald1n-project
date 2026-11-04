<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BankAccount;
use App\Models\Order;
use App\Models\Product;
use App\Models\PublicOrderAccess;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/** Public retail uses no customer account, never grants logged-in privileges, and never pays commission. */
final class PublicRetailService
{
    public const CART_KEY = 'public_retail_cart_v1';

    public function __construct(private readonly SettingsService $settings) {}

    public function enabled(): bool
    {
        return config('public_retail.enabled') === true
            && $this->settings->get('public_retail_merchant_approved', '0') === '1'
            && $this->settings->get('public_retail_free_shipping_approved', '0') === '1'
            && Schema::hasTable('public_order_accesses');
    }

    public function requireEnabled(): void
    {
        abort_unless($this->enabled(), 404);
    }

    public function assertMerchantReady(): void
    {
        $this->requireEnabled();
        foreach (['documents_company_name', 'documents_company_address', 'documents_company_city',
            'documents_company_tax_id', 'documents_company_email', 'documents_company_phone',
            'public_retail_terms_text'] as $key) {
            $value = trim((string) $this->settings->get($key, ''));
            if ($value === '' || ($key === 'documents_company_name' && $value === 'Ald1n')) {
                throw ValidationException::withMessages(['checkout' => 'Javna kupovina nije dostupna dok trgovac ne potvrdi sve zakonske i dostavne podatke.']);
            }
        }
        if (!$this->activeSupplier() instanceof User) {
            throw ValidationException::withMessages(['checkout' => 'Javna kupovina trenutno nije dostupna za obradu.']);
        }
    }

    public function publicProducts(): Builder
    {
        return Product::query()->publiclyVisible()->where('stock_quantity', '>', 0);
    }

    /** @return array<int,int> */
    public function normalizeCart(mixed $raw): array
    {
        if (!is_array($raw)) return [];
        $cart = [];
        foreach ($raw as $id => $quantity) {
            if (!(is_int($id) || (is_string($id) && ctype_digit($id)))) continue;
            if (!(is_int($quantity) || (is_string($quantity) && ctype_digit($quantity)))) continue;
            $id = (int) $id; $qty = (int) $quantity;
            if ($id < 1 || $qty < 1) continue;
            $cart[$id] = min((int) config('public_retail.max_quantity_per_item', 10), $qty);
        }
        ksort($cart, SORT_NUMERIC);
        return array_slice($cart, 0, (int) config('public_retail.max_distinct_items', 20), true);
    }

    /** @return array{lines:list<array<string,mixed>>,total_cents:int} */
    public function preview(array $cart): array
    {
        $this->requireEnabled();
        $cart = $this->normalizeCart($cart);
        $products = $this->publicProducts()->with('presentationImage')->whereIn('id', array_keys($cart))->get()->keyBy('id');
        $lines = []; $total = 0;
        foreach ($cart as $id => $quantity) {
            $product = $products->get($id);
            if (!$product instanceof Product) continue;
            $unit = $this->retailUnitCents($product);
            $line = $unit * $quantity;
            if ($unit < 1 || $line > 200000000000) continue;
            $lines[] = [
                'product_id' => (int) $product->id, 'slug' => (string) $product->slug,
                'name' => (string) $product->name, 'sku' => (string) $product->sku,
                'quantity' => $quantity, 'stock' => (int) $product->stock_quantity,
                'unit_cents' => $unit, 'line_cents' => $line,
                'image_id' => $product->presentationImage?->id,
            ];
            $total += $line;
        }
        return ['lines' => $lines, 'total_cents' => $total];
    }

    public function retailUnitCents(Product $product, ?float $rate = null): int
    {
        $amount = (float) $product->price_amount;
        if ($amount <= 0) return 0;
        if ((string) $product->price_currency === 'EUR') {
            $rate ??= $this->settings->eurRsdRate();
            if ($rate === null || $rate <= 0) {
                throw ValidationException::withMessages(['cart' => 'Kurs nije podešen za javnu prodaju.']);
            }
            $amount *= $rate;
        } elseif ((string) $product->price_currency !== 'RSD') {
            throw ValidationException::withMessages(['cart' => 'Nepodržana valuta artikla.']);
        }
        return (int) round($amount * 100);
    }

    /** @param array<string,mixed> $input
     * @return array{order:Order,token:string}
     */
    public function checkout(array $cart, array $input, string $key): array
    {
        $this->assertMerchantReady();
        $cart = $this->normalizeCart($cart);
        if ($cart === []) throw ValidationException::withMessages(['cart' => 'Korpa je prazna.']);
        if (preg_match('/^[a-f0-9]{64}$/D', $key) !== 1) {
            throw ValidationException::withMessages(['checkout' => 'Nevažeća potvrda korpe.']);
        }
        $method = (string) ($input['payment_method'] ?? '');
        if (!in_array($method, ['cash_on_delivery', 'bank_transfer'], true)) {
            throw ValidationException::withMessages(['payment_method' => 'Nepodržan način plaćanja.']);
        }
        $expected = (string) ($input['expected_total_cents'] ?? '');
        if (!ctype_digit($expected) || strlen($expected) > 14) {
            throw ValidationException::withMessages(['cart' => 'Osveži korpu i potvrdi aktuelni iznos.']);
        }
        $fingerprint = hash('sha256', json_encode(['cart' => $cart, 'contact' => $input], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        $checkoutHash = hash('sha256', $key);
        if (PublicOrderAccess::query()->where('checkout_key_hash', $checkoutHash)->exists()) {
            throw ValidationException::withMessages(['checkout' => 'Ova porudžbina je već poslata. Proveri prethodnu potvrdu.']);
        }
        $token = bin2hex(random_bytes(32));
        $result = DB::transaction(function () use ($cart, $input, $key, $token, $method, $expected, $fingerprint, $checkoutHash): Order {
            // Order-independent row locks prevent competing last-item checkouts from overselling.
            $products = $this->publicProducts()->with(['brand', 'line', 'type'])
                ->whereIn('id', array_keys($cart))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($products->count() !== count($cart)) {
                throw ValidationException::withMessages(['cart' => 'Neki artikli više nisu dostupni. Osveži korpu.']);
            }
            $rate = $this->settings->eurRsdRate();
            $total = 0; $rows = [];
            foreach ($cart as $id => $qty) {
                $product = $products->get($id);
                if (!$product instanceof Product || (int) $product->stock_quantity < $qty) {
                    throw ValidationException::withMessages(['cart' => 'Nedovoljno artikala na lageru. Osveži korpu.']);
                }
                $unitCents = $this->retailUnitCents($product, $rate);
                if ($unitCents < 1) throw ValidationException::withMessages(['cart' => 'Nevažeća cena artikla.']);
                $total += $unitCents * $qty;
                $rows[] = [$product, $qty, $unitCents];
            }
            if ((string) $total !== $expected || $total <= 0 || $total > 200000000000) {
                throw ValidationException::withMessages(['cart' => 'Cena se promenila. Osveži korpu i ponovo potvrdi iznos.']);
            }
            $account = null;
            if ($method === 'bank_transfer') {
                $account = BankAccount::query()->where('is_active', true)->orderBy('id')->first();
                if (!$account instanceof BankAccount || trim((string) $account->account_number) === '') {
                    throw ValidationException::withMessages(['payment_method' => 'Uplata na račun trenutno nije dostupna.']);
                }
            }
            $supplier = $this->activeSupplier();
            if (!$supplier instanceof User) throw ValidationException::withMessages(['checkout' => 'Nema dostupnog operatera.']);
            $order = Order::query()->create([
                'source_system' => 'laravel', 'sales_channel' => 'public_retail',
                'order_number' => 'TMP-'.strtoupper(bin2hex(random_bytes(12))),
                'idempotency_key_hash' => $checkoutHash, 'request_fingerprint' => $fingerprint,
                'user_id' => null, 'supplier_user_id' => $supplier->id,
                'supplier_name_snapshot' => $supplier->displayName(),
                'supplier_role_snapshot' => $supplier->roleName(), 'assigned_at' => now(),
                'status' => 'new', 'inventory_state' => 'reserved', 'inventory_reserved_at' => now(),
                'shipping_full_name' => (string) $input['shipping_full_name'],
                'shipping_address' => (string) $input['shipping_address'],
                'shipping_city' => (string) $input['shipping_city'],
                'shipping_postal_code' => (string) $input['shipping_postal_code'],
                'shipping_phone' => (string) $input['shipping_phone'],
                'customer_note' => trim((string) ($input['customer_note'] ?? '')) ?: null,
                'subtotal_rsd' => $total / 100, 'eur_rsd_rate' => $rate,
                'payment_method' => $method, 'payment_status' => 'pending', 'payment_state' => 'unpaid',
                'paid_total_rsd' => 0, 'bank_account_id' => $account?->id,
                'bank_account_label_snapshot' => $account?->label,
                'bank_account_number_snapshot' => $account?->account_number,
                'bank_account_number_display_snapshot' => $account?->account_number_display,
                'payment_recipient_name_snapshot' => $account?->recipient_name,
                'payment_recipient_address_snapshot' => $account?->recipient_address,
                'payment_code_snapshot' => $account?->payment_code,
            ]);
            $order->update([
                'order_number' => sprintf('APC-%s-%08d', now()->format('Ymd'), $order->id),
                'payment_purpose_snapshot' => $account ? 'Plaćanje porudžbine' : null,
                'payment_reference_snapshot' => $account ? (string) $order->id : null,
            ]);
            foreach ($rows as [$product, $qty, $unit]) {
                $before = (int) $product->stock_quantity;
                $after = $before - $qty;
                $purchase = $product->purchase_price_rsd !== null && (float) $product->purchase_price_rsd > 0
                    ? round((float) $product->purchase_price_rsd, 2) : null;
                $item = $order->items()->create([
                    'product_id' => $product->id, 'product_sku' => $product->sku, 'product_name' => $product->name,
                    'quantity' => $qty, 'unit_price_original' => $product->price_amount,
                    'original_currency' => $product->price_currency, 'unit_price_rsd' => $unit / 100,
                    'line_total_rsd' => $qty * $unit / 100, 'purchase_unit_rsd_snapshot' => $purchase,
                    'purchase_total_rsd_snapshot' => $purchase === null ? null : round($purchase * $qty, 2),
                    'cost_source_snapshot' => $purchase === null ? 'missing' : 'product',
                    'brand_name_snapshot' => $product->brand?->name,
                    'product_line_name_snapshot' => $product->line?->name,
                    'product_type_name_snapshot' => $product->type?->name,
                    // Existing schema permits this exempt source; zero commission/no commission row.
                    'commission_source_snapshot' => 'direct_sale', 'commission_rate_percent_snapshot' => null,
                    'commission_unit_eur_snapshot' => 0, 'commission_total_eur_snapshot' => 0,
                ]);
                $product->update(['stock_quantity' => $after]);
                StockMovement::query()->create([
                    'event_key' => 'order:'.$order->id.':item:'.$item->id.':sale',
                    'product_id' => $product->id, 'order_id' => $order->id,
                    'user_id' => $supplier->id, 'movement_type' => 'sale', 'source' => 'order',
                    'quantity_change' => -$qty, 'quantity_before' => $before, 'quantity_after' => $after,
                    'note' => 'Automatska rezervacija za javnu porudžbinu '.$order->order_number,
                    'metadata_json' => ['order_item_id' => $item->id, 'source_system' => 'public_retail'],
                ]);
            }
            DB::table('order_status_history')->insert([
                'order_id' => $order->id, 'changed_by' => null, 'old_status' => null,
                'new_status' => 'new', 'note' => 'Javna porudžbina, bez registracije korisnika.', 'created_at' => now(),
            ]);
            PublicOrderAccess::query()->create([
                'order_id' => $order->id, 'token_hash' => hash('sha256', $token),
                'checkout_key_hash' => $checkoutHash, 'request_hash' => $fingerprint,
                'guest_email' => mb_strtolower((string) $input['email']),
                'terms_snapshot_hash' => hash('sha256', (string) $this->settings->get('public_retail_terms_text')),
                'consent_at' => now(), 'expires_at' => now()->addDays((int) config('public_retail.access_days', 30)),
            ]);
            DB::afterCommit(function () use ($order, $supplier, $input, $token): void {
                try {
                    app(OperationalNotificationService::class)->order($supplier,
                        'order.public_created', 'Nova javna porudžbina',
                        'Nova kupovina bez registracije '.$order->order_number.'.', $order);
                } catch (Throwable $e) {
                    Log::warning('Public retail staff notification failed', ['order_id' => $order->id, 'error' => $e::class]);
                }
                if (config('public_retail.email_receipt') === true) {
                    try {
                        $url = route('public.order.show', ['token' => $token]);
                        Mail::raw('Vaša porudžbina '.$order->order_number.' je primljena. Status i dostava: '.$url,
                            static function ($message) use ($input): void {
                                $message->to((string) $input['email'])->subject('Potvrda porudžbine');
                            });
                    } catch (Throwable $e) {
                        Log::warning('Public retail receipt e-mail failed', ['order_id' => $order->id, 'error' => $e::class]);
                    }
                }
            });
            return $order;
        }, 3);
        return ['order' => $result, 'token' => $token];
    }

    public function authorizeAccess(string $token): PublicOrderAccess
    {
        $this->requireEnabled();
        abort_unless(preg_match('/^[a-f0-9]{64}$/D', $token) === 1, 404);
        $record = PublicOrderAccess::query()->where('token_hash', hash('sha256', $token))->first();
        abort_unless($record instanceof PublicOrderAccess
            && $record->revoked_at === null && $record->expires_at !== null
            && $record->expires_at->isFuture(), 404);
        $record->load('order.items', 'order.shipment.courier', 'order.documents');
        abort_unless($record->order instanceof Order && $record->order->sales_channel === 'public_retail'
            && $record->order->archived_at === null && $record->order->purged_at === null, 404);
        return $record;
    }

    public function activeSupplier(): ?User
    {
        return User::query()->where('status', 'active')
            ->whereHas('role', static fn (Builder $q) => $q->where('slug', 'superadmin'))
            ->orderBy('id')->first();
    }
}
