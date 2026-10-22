from pathlib import Path
import os, subprocess
assert os.environ.get('GITHUB_REF_NAME') == 'feat/b796-operator-orders-glass-tracking-20261022'
assert os.environ.get('APP_ENV') == 'testing'
root = Path('apps/cms/current')

def replace(path, old, new, count=1):
    p = root/path
    s = p.read_text()
    assert s.count(old) == count, (path, s.count(old), old[:100])
    p.write_text(s.replace(old, new))

replace('app/Models/User.php', '    private bool $roleSlugResolved = false;', '''    /** The operator role must never enter the personal purchase transport. */
    public const PERSONAL_ORDER_ABILITIES = ['orders.create', 'orders.view_own', 'orders.cancel_own'];

    private bool $roleSlugResolved = false;''')
replace('app/Models/User.php', '        $permissions = $this->permissionSlugs();', '''        if ($this->hasRole('superadmin') && in_array($permission, self::PERSONAL_ORDER_ABILITIES, true)) {
            return false;
        }
        $permissions = $this->permissionSlugs();''')
replace('app/Models/User.php', '                return array_keys($this->resolvedPermissionSlugs);', '''                $permissions = array_keys($this->resolvedPermissionSlugs);
                return $this->hasRole('superadmin')
                    ? array_values(array_diff($permissions, [...self::PERSONAL_ORDER_ABILITIES, '*']))
                    : $permissions;''')
replace('app/Models/User.php', "return $this->hasRole('admin', 'superadmin') ? ['*'] : [];", "return $this->hasRole('admin') ? ['*'] : [];")
replace('app/Providers/AppServiceProvider.php', "        Gate::before(static function (User $user, string $ability): ?bool {", '''        Gate::before(static function (User $user, string $ability): ?bool {
            if ($user->hasRole('superadmin') && in_array($ability, User::PERSONAL_ORDER_ABILITIES, true)) {
                return false;
            }''')
replace('app/Services/OrderService.php', "    public function create(User $user, array $data, string $idempotencyKey): Order\n    {", '''    public function create(User $user, array $data, string $idempotencyKey): Order
    {
        // Defence in depth: HTTP permissions are not the only caller of this service.
        abort_if($user->hasRole('superadmin'), 403, 'SuperAdministrator koristi korisničke porudžbine ili direktnu prodaju.');''')
replace('app/Http/Controllers/DashboardController.php', "            $query = Order::query()->operational();\n            if ($access['orders_manage']", "            $query = Order::query()->operational()->with('user');\n            if ($access['orders_manage']")
replace('app/Http/Controllers/DashboardController.php', "                    'number' => $order->order_number,", """                    'number' => $order->order_number,
                    'seller_name' => (string) $order->sales_channel === 'direct_sale' ? 'Direktna prodaja' : ($order->user?->displayName() ?? 'Korisnik'),
                    'buyer_name' => trim((string) $order->shipping_full_name) ?: 'Kupac',""")
replace('resources/views/dashboard/index.blade.php', "    $orderIndex = $access['orders_manage']", "    $isSuperAdmin = $user->hasRole('superadmin');\n    $orderIndex = $access['orders_manage']")
replace('resources/views/dashboard/index.blade.php', "                : 'Poslovni pokazatelji, operativni prioriteti i lični korisnički centar objedinjeni su na jednoj početnoj strani.' }}", "                : ($isSuperAdmin ? 'Poslovni pokazatelji, korisničke porudžbine i operativni prioriteti na jednom mestu.' : 'Poslovni pokazatelji, operativni prioriteti i lični korisnički centar objedinjeni su na jednoj početnoj strani.') }}")
replace('resources/views/dashboard/index.blade.php', '        <div class="dashboard-quick-actions">', '''        <div class="dashboard-quick-actions">
            @if($isSuperAdmin)
                <a class="button button-primary" href="{{ route('admin.orders.index') }}"><x-icon name="orders" /> Porudžbine korisnika</a>
            @endif''')
replace('resources/views/dashboard/index.blade.php', '''    <div class="dashboard-secondary-grid">
        <section class="panel dashboard-panel">''', '''    <div class="dashboard-secondary-grid">
        <section class="panel dashboard-panel" @if($isSuperAdmin) data-operator-order-center="1" @endif>''')
replace('resources/views/dashboard/index.blade.php', '<h2>Poslednje porudžbine</h2>', "<h2>{{ $isSuperAdmin ? 'Porudžbine korisnika' : 'Poslednje porudžbine' }}</h2>")
replace('resources/views/dashboard/index.blade.php', "{{ $order['updated_at']?->format('d.m.Y H:i') }}</small></div>", "{{ $order['updated_at']?->format('d.m.Y H:i') }}</small>@if($isSuperAdmin)<span class=\"dashboard-order-parties\">Korisnik: {{ $order['seller_name'] }}<br>Kupac: {{ $order['buyer_name'] }}</span>@endif</div>")
replace('resources/views/dashboard/index.blade.php', '            <div class="dashboard-action-grid">', '''            <div class="dashboard-action-grid">
                @if($isSuperAdmin)<a href="{{ route('admin.orders.index') }}"><span class="tone-blue"><x-icon name="orders" /></span><strong>Porudžbine korisnika</strong><small>Obrada, uplate i isporuka</small></a>@endif''')
replace('app/Services/OrderDetailPresenter.php', "'tracking_url' => $trackingUrl !== '' ? $trackingUrl : null,", "'tracking_url' => \\App\\Support\\ShipmentTrackingUrl::safe($trackingUrl),")
replace('app/Services/OrderDetailPresenter.php', "        $detail['mode'] = 'admin';\n        $detail['permissions'] = [", "        $detail['mode'] = 'admin';\n        $detail['permissions'] = [\n            'buyer_tracking' => $actor->hasRole('superadmin'),")
p = root/'resources/views/admin/orders/show.blade.php'
s = p.read_text()
start = s.index('id="order-workspace-customer"')
end = s.index('</section>', start)
block = '''
            @if($permissions['buyer_tracking'] ?? false)
                @php
                    $buyerShipment = $detail['shipment'] ?? null;
                    $buyerTrackingNumber = trim((string) ($buyerShipment['tracking_number'] ?? ''));
                    $hasBuyerTracking = $buyerTrackingNumber !== '' && $buyerTrackingNumber !== '—';
                @endphp
                <div class="order-buyer-tracking">
                    @if($hasBuyerTracking)
                        <span class="eyebrow">Pošiljka</span>
                        <dl class="detail-list">
                            <dt>Kurirska služba</dt><dd>{{ $buyerShipment['courier_name'] }}</dd>
                            <dt>Broj pošiljke</dt><dd><strong>{{ $buyerTrackingNumber }}</strong></dd>
                        </dl>
                        @if($buyerShipment['tracking_url'] ?? null)
                            <a class="button button-ghost" data-buyer-shipment-tracking href="{{ $buyerShipment['tracking_url'] }}" target="_blank" rel="noopener noreferrer"><x-icon name="truck" /> Prati pošiljku</a>
                        @endif
                    @else
                        <span data-buyer-shipment-empty>Broj pošiljke još nije evidentiran.</span>
                    @endif
                </div>
            @endif
        '''
s = s[:end]+block+s[end:]
s = s.replace('rel="noopener"', 'rel="noopener noreferrer"').replace('            </dl>\n        \n', '            </dl>\n\n')
p.write_text(s)
replace('tests/Feature/OperatorWorkspace796Test.php', "'sales_channel'=>'direct'", "'sales_channel'=>'direct_sale'")

p = root/'app/Support/ShipmentTrackingUrl.php'
assert not p.exists()
p.write_text(r'''<?php

declare(strict_types=1);

namespace App\Support;

/** Validate an existing carrier destination; never construct one from untrusted input. */
final class ShipmentTrackingUrl
{
    public static function safe(?string $value): ?string
    {
        if ($value === null || strlen($value) > 2048) return null;
        // Reject controls before trimming, including encoded CR/LF and URL parser ambiguities.
        if (preg_match('/[\x00-\x1f\x7f\\\\]|%(?:0[0-9a-f]|1[0-9a-f]|7f)/i', $value)) return null;
        $url = trim($value);
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) return null;
        $parts = parse_url($url);
        if (!is_array($parts) || strtolower($parts['scheme'] ?? '') !== 'https'
            || isset($parts['user']) || isset($parts['pass'])
            || (isset($parts['port']) && $parts['port'] !== 443)) return null;
        $host = strtolower(rtrim($parts['host'] ?? '', '.'));
        if (!str_contains($host, '.') || preg_match('/(?:\.localhost|\.local|\.internal)\z/i', $host)
            || preg_match('/\A(?:0x[0-9a-f]+|[0-9]+)(?:\.(?:0x[0-9a-f]+|[0-9]+))*\z/i', $host)
            || filter_var(trim($host, '[]'), FILTER_VALIDATE_IP)
            || !filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) return null;
        return $url;
    }
}
''')
p = root/'bin/operator796-link-smoke.php'
assert not p.exists()
p.write_text(r'''<?php

declare(strict_types=1);

// Pure URL contract: no Laravel bootstrap, database, network or business writes.
$helper = dirname(__DIR__).'/app/Support/ShipmentTrackingUrl.php';
if (!is_file($helper)) {
    fwrite(STDERR, "FAIL missing production ShipmentTrackingUrl helper\n");
    exit(1);
}
require $helper;
$cases = [
    ['https://carrier.example.test/track?code=ABC-12', 'https://carrier.example.test/track?code=ABC-12'],
    [' https://www.posta.rs/ ', 'https://www.posta.rs/'],
    ['https://carrier.example.test:443/track', 'https://carrier.example.test:443/track'],
    ['javascript:alert(1)', null], ['data:text/html,test', null], ['//carrier.example.test/track', null],
    ['https://user:pass@carrier.example.test/', null], ['http://carrier.example.test/', null],
    ['https://localhost/track', null], ['https://127.0.0.1/', null], ['https://[::1]/', null],
    ['https://10.0.0.1/', null], ['https://internal/track', null], ['https://sub.localhost/', null],
    ["https://carrier.example.test/\ntrack", null], ['https://carrier.example.test/%0atrack', null],
    ['https://carrier.example.test\\@evil.test/', null], ['https://carrier.example.test:8443/', null],
    ['https://0177.0.0.1/', null], ['https://0x7f.0.0.1/', null],
    ['https://carrier.local/', null], ['https://carrier.internal/', null],
    ['', null], [null, null], [str_repeat('x', 2049), null],
];
foreach ($cases as $i => [$input, $expected]) {
    $actual = App\Support\ShipmentTrackingUrl::safe($input);
    if ($actual !== $expected) {
        fwrite(STDERR, 'FAIL URL case '.($i + 1)."\n");
        exit(1);
    }
}
echo 'OPERATOR796_LINKS=PASS CASES='.count($cases)."\n";
''')
p = root/'public/assets/css/ald1n-ui-refinement.css'
p.write_text(p.read_text()+'''

/* B796: optical movement on the surface, not on text or layout. */
.page-shell :is(.dashboard-action-grid > a, a.dashboard-module-card, a.portal-order-card, a.attention-card, a.dashboard-priority-item, a.dashboard-kpi-card) {
  position: relative; isolation: isolate; overflow: hidden;
}
.page-shell :is(.dashboard-action-grid > a, a.dashboard-module-card, a.portal-order-card, a.attention-card, a.dashboard-priority-item, a.dashboard-kpi-card)::after {
  content: ''; position: absolute; inset: 0; z-index: 0; border-radius: inherit;
  pointer-events: none; opacity: 0;
  background-image: linear-gradient(115deg, transparent 36%, var(--lg5-sheen, rgba(255,255,255,.12)) 49%, transparent 62%);
  background-size: 260% 100%; background-position: 100% 0;
}
.page-shell :is(.dashboard-action-grid > a, a.dashboard-module-card, a.portal-order-card, a.attention-card, a.dashboard-priority-item, a.dashboard-kpi-card) > * { position: relative; z-index: 1; }
.build16-home-shell .dashboard-action-grid > a:is(:hover, :active),
.build16-home-shell .dashboard-module-grid > .dashboard-module-card:is(:hover, :active) { transform: none; }
@keyframes ald1n-glass-sweep {
  0% { background-position: 100% 0; opacity: 0; }
  18% { opacity: .8; }
  82% { opacity: .8; }
  100% { background-position: 0 0; opacity: 0; }
}
@media (hover: hover) and (pointer: fine) {
  .page-shell :is(.dashboard-action-grid > a, a.dashboard-module-card, a.portal-order-card, a.attention-card, a.dashboard-priority-item, a.dashboard-kpi-card):hover::after {
    animation: ald1n-glass-sweep 700ms ease-out 1;
  }
}
.page-shell :is(.dashboard-action-grid > a, a.dashboard-module-card, a.portal-order-card, a.attention-card, a.dashboard-priority-item, a.dashboard-kpi-card):focus-visible::after {
  animation: ald1n-glass-sweep 700ms ease-out 1;
}
@media (prefers-reduced-motion: reduce), (prefers-reduced-transparency: reduce), (forced-colors: active) {
  .page-shell :is(.dashboard-action-grid > a, a.dashboard-module-card, a.portal-order-card, a.attention-card, a.dashboard-priority-item, a.dashboard-kpi-card)::after {
    display: none; animation: none !important;
  }
}
.build16-home-shell .dashboard-order-parties { display: block; margin-top: 5px; line-height: 1.5; color: var(--ald-muted); overflow-wrap: anywhere; }
.order-workspace-customer .order-buyer-tracking { margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--ald-border); }
.order-workspace-customer .order-buyer-tracking :is(strong, span) { overflow-wrap: anywhere; }
.order-workspace-customer .order-buyer-tracking .button { margin-top: 12px; }
''')
print('B796_SOURCE_AUTHORED: ten runtime files plus one fixture correction; no production access')
