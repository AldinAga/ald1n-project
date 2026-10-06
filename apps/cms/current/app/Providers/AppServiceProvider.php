<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\RestoreCredential;
use App\Models\User;
use App\Services\ExchangeRateService;
use App\Services\LegacyReadOnlyGuard;
use App\Services\SettingsService;
use App\Services\SiteAssetUrlService;
use App\Services\TurnstileService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Passkeys\Passkeys;
use Throwable;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Passkeys::ignoreRoutes();
        Passkeys::useUserModel(User::class);
        Passkeys::usePasskeyModel(RestoreCredential::class);
    }

    public function boot(): void
    {
        try {
            $legacy = DB::connection('legacy');
            if (method_exists($legacy, 'beforeExecuting')) {
                $legacy->beforeExecuting(static function (string $query): void {
                    app(LegacyReadOnlyGuard::class)->assertQueryAllowed($query);
                });
            }
        } catch (Throwable) {
            // Konekcija se proverava eksplicitno kroz legacy:check i deployment check.
        }

        View::composer(['layouts.app', 'layouts.guest', 'auth.*'], static function ($view): void {
            $siteSettings = [
                'site_name' => 'Ald1n CMS',
                'site_logo_alt' => 'Ald1n CMS',
                'site_logo_light_path' => '',
                'site_logo_dark_path' => '',
                'site_favicon_path' => '',
                'site_header_logo_height' => '38',
                'site_footer_show_logo' => '0',
                'site_footer_layout' => 'split',
                'site_footer_copyright_text' => '© {year} {site_name}',
                'site_footer_secondary_text' => '',
                'site_footer_links_new_tab' => '0',
                'site_footer_link_1_label' => '',
                'site_footer_link_1_url' => '',
                'site_footer_link_2_label' => '',
                'site_footer_link_2_url' => '',
                'site_footer_link_3_label' => '',
                'site_footer_link_3_url' => '',
            ];

            try {
                $siteSettings = array_replace($siteSettings, app(SettingsService::class)->all());
            } catch (Throwable) {
                // The authenticated shell must render even while settings/cache is being repaired.
            }

            $siteLogoLightUrl = null;
            $siteLogoDarkUrl = null;
            $siteFaviconUrl = null;
            try {
                $assetService = app(SiteAssetUrlService::class);
                $siteLogoLightUrl = $assetService->url($siteSettings['site_logo_light_path']);
                $siteLogoDarkUrl = $assetService->url($siteSettings['site_logo_dark_path']);
                $siteFaviconUrl = $assetService->url($siteSettings['site_favicon_path']);
            } catch (Throwable) {
                // Branding assets are optional and must never block login/dashboard rendering.
            }

            $turnstileEnabled = (bool) config('services.turnstile.enabled');
            $turnstileSiteKey = trim((string) config('services.turnstile.site_key'));
            try {
                $turnstile = app(TurnstileService::class);
                $turnstileEnabled = $turnstile->enabled();
                $turnstileSiteKey = $turnstile->siteKey();
            } catch (Throwable) {
                // .env konfiguracija ostaje fallback ako settings tabela nije dostupna.
            }

            $siteExchangeRate = ['rate' => null, 'is_stale' => true, 'source' => 'Nije podešeno'];
            try {
                $siteExchangeRate = array_replace(
                    $siteExchangeRate,
                    app(ExchangeRateService::class)->configuration(),
                );
            } catch (Throwable) {
                // Rate configuration is optional on the application shell.
            }

            $headerUserName = 'Korisnik';
            $headerUserInitial = 'K';
            $headerUserRole = 'Korisnik';
            $headerUnreadNotifications = 0;
            $authenticatedUser = null;
            try {
                $authenticatedUser = auth()->user();
            } catch (Throwable) {
                // A stale session must not prevent the login page from rendering.
            }

            if ($authenticatedUser instanceof User) {
                $headerUserName = $authenticatedUser->displayName();
                $headerUserInitial = $authenticatedUser->displayInitial();
                $headerUserRole = $authenticatedUser->roleName();
                try {
                    $headerUnreadNotifications = $authenticatedUser->unreadNotifications()->count();
                } catch (Throwable) {
                    $headerUnreadNotifications = 0;
                }
            }

            $renderTemplate = static function (string $text) use ($siteSettings): string {
                return strtr($text, [
                    '{year}' => date('Y'),
                    '{site_name}' => (string) $siteSettings['site_name'],
                    '{version}' => (string) config('app.version'),
                ]);
            };

            $siteFooterLinks = [];
            foreach ([1, 2, 3] as $index) {
                $label = trim((string) $siteSettings['site_footer_link_'.$index.'_label']);
                $url = trim((string) $siteSettings['site_footer_link_'.$index.'_url']);
                if ($label !== '' && $url !== '') {
                    $siteFooterLinks[] = ['label' => $label, 'url' => $url];
                }
            }

            $view->with([
                'siteSettings' => $siteSettings,
                'siteLogoLightUrl' => $siteLogoLightUrl,
                'siteLogoDarkUrl' => $siteLogoDarkUrl,
                'siteFaviconUrl' => $siteFaviconUrl,
                'siteExchangeRate' => $siteExchangeRate,
                'turnstileEnabled' => $turnstileEnabled,
                'turnstileSiteKey' => $turnstileSiteKey,
                'siteHeaderUserName' => $headerUserName,
                'siteHeaderUserInitial' => $headerUserInitial,
                'siteHeaderUserRole' => $headerUserRole,
                'siteHeaderUnreadNotifications' => $headerUnreadNotifications,
                'siteFooterCopyright' => $renderTemplate((string) $siteSettings['site_footer_copyright_text']),
                'siteFooterSecondary' => $renderTemplate((string) $siteSettings['site_footer_secondary_text']),
                'siteFooterLinks' => $siteFooterLinks,
            ]);
        });

        Gate::before(static function (User $user, string $ability): ?bool {
            if ($user->hasRole('superadmin', 'admin')) {
                return true;
            }

            return null;
        });

        Gate::define('catalog.view', static fn (User $user): bool => $user->hasPermission('catalog.view'));
        Gate::define('catalog.view_prices', static fn (User $user): bool => $user->hasPermission('catalog.view_prices'));
        Gate::define('orders.create', static fn (User $user): bool => $user->hasPermission('orders.create'));
        Gate::define('orders.view_own', static fn (User $user): bool => $user->hasPermission('orders.view_own'));
        Gate::define('orders.cancel_own', static fn (User $user): bool => $user->hasPermission('orders.cancel_own'));
        Gate::define('catalog.manage_products', static fn (User $user): bool => $user->hasPermission('catalog.manage_products'));
        Gate::define('catalog.manage_images', static fn (User $user): bool => $user->hasPermission('catalog.manage_images'));
        Gate::define('catalog.manage_taxonomy', static fn (User $user): bool => $user->hasPermission('catalog.manage_taxonomy'));
        Gate::define('catalog.audit', static fn (User $user): bool => $user->hasPermission('catalog.audit'));
        Gate::define('catalog.sync_legacy', static fn (User $user): bool => $user->hasPermission('catalog.sync_legacy'));
        Gate::define('orders.manage', static fn (User $user): bool => $user->hasPermission('orders.manage'));
        Gate::define('commissions.manage', static fn (User $user): bool => $user->hasPermission('commissions.manage'));
        Gate::define('commissions.view_own', static fn (User $user): bool => $user->hasPermission('commissions.view_own'));
        Gate::define('orders.reassign', static fn (User $user): bool => $user->hasPermission('orders.reassign'));
        Gate::define('orders.internal_notes', static fn (User $user): bool => $user->hasPermission('orders.internal_notes'));
        Gate::define('orders.confirm_delivery', static fn (User $user): bool => $user->hasPermission('orders.confirm_delivery'));
        Gate::define('orders.reopen', static fn (User $user): bool => $user->hasPermission('orders.reopen'));
        Gate::define('after_sales.create', static fn (User $user): bool => $user->hasPermission('after_sales.create'));
        Gate::define('after_sales.view_own', static fn (User $user): bool => $user->hasPermission('after_sales.view_own'));
        Gate::define('after_sales.manage', static fn (User $user): bool => $user->hasPermission('after_sales.manage'));
        Gate::define('after_sales.execute', static fn (User $user): bool => $user->hasPermission('after_sales.execute'));
        Gate::define('field_operations.view', static fn (User $user): bool => $user->hasPermission('field_operations.view'));
        Gate::define('field_operations.manage', static fn (User $user): bool => $user->hasPermission('field_operations.manage'));
        Gate::define('service_parts.view', static fn (User $user): bool => $user->hasPermission('service_parts.view'));
        Gate::define('service_parts.manage', static fn (User $user): bool => $user->hasPermission('service_parts.manage'));
        Gate::define('service_parts.procurement', static fn (User $user): bool => $user->hasPermission('service_parts.procurement'));
        Gate::define('warranties.view_own', static fn (User $user): bool => $user->hasPermission('warranties.view_own'));
        Gate::define('warranties.manage', static fn (User $user): bool => $user->hasPermission('warranties.manage'));
        Gate::define('receivables.manage', static fn (User $user): bool => $user->hasPermission('receivables.manage'));
        Gate::define('notifications.view', static fn (User $user): bool => $user->hasPermission('notifications.view'));
        Gate::define('stock.view', static fn (User $user): bool => $user->hasPermission('stock.view'));
        Gate::define('stock.adjust', static fn (User $user): bool => $user->hasPermission('stock.adjust'));
        Gate::define('system.manage_users', static fn (User $user): bool => $user->hasPermission('system.manage_users'));
        Gate::define('system.manage_settings', static fn (User $user): bool => $user->hasPermission('system.manage_settings'));
        Gate::define('reports.view', static fn (User $user): bool => $user->hasPermission('reports.view'));
        Gate::define('reports.export', static fn (User $user): bool => $user->hasPermission('reports.export'));
        Gate::define('reports.manage', static fn (User $user): bool => $user->hasPermission('reports.manage'));
        Gate::define('invoices.manage', static fn (User $user): bool => $user->hasPermission('invoices.manage'));
        Gate::define('invoices.view_own', static fn (User $user): bool => $user->hasPermission('invoices.view_own'));
        Gate::define('payments.manage', static fn (User $user): bool => $user->hasPermission('payments.manage'));
        Gate::define('payments.upload_proof', static fn (User $user): bool => $user->hasPermission('payments.upload_proof'));
        Gate::define('payments.view_own', static fn (User $user): bool => $user->hasPermission('payments.view_own'));
        Gate::define('inventory.receive', static fn (User $user): bool => $user->hasPermission('inventory.receive'));
        Gate::define('inventory.count', static fn (User $user): bool => $user->hasPermission('inventory.count'));
        Gate::define('inventory.export', static fn (User $user): bool => $user->hasPermission('inventory.export'));
        Gate::define('automation.manage', static fn (User $user): bool => $user->hasPermission('automation.manage'));
        Gate::define('system.health', static fn (User $user): bool => $user->hasPermission('system.health'));
        Gate::define('backups.manage', static fn (User $user): bool => $user->hasPermission('backups.manage'));
        Gate::define('audit.export', static fn (User $user): bool => $user->hasPermission('audit.export'));
        Gate::define('security.view', static fn (User $user): bool => $user->hasPermission('security.view'));

        RateLimiter::for('login', static function (Request $request): Limit {
            $identifier = mb_strtolower((string) $request->input('login', 'guest'));
            return Limit::perMinute(5)->by(hash('sha256', $identifier.'|'.$request->ip()));
        });

        RateLimiter::for('api-login', static function (Request $request): Limit {
            $identifier = mb_strtolower((string) $request->input('login', 'guest'));
            return Limit::perMinute(5)->by(hash('sha256', $identifier.'|'.$request->ip()));
        });

        RateLimiter::for('api-google-auth', static function (Request $request): array {
            $ip = (string) $request->ip();
            return [
                Limit::perMinute(10)->by('api-google-auth-minute|'.$ip),
                Limit::perHour(60)->by('api-google-auth-hour|'.$ip),
            ];
        });

        RateLimiter::for('orders', static function (Request $request): array {
            $actor = $request->user()?->getAuthIdentifier() ?? $request->ip();
            return [
                Limit::perMinute(10)->by('orders-minute|'.$actor),
                Limit::perHour(100)->by('orders-hour|'.$actor),
            ];
        });

        RateLimiter::for('api-restore-credentials', static function (Request $request): array {
            $actor = $request->user()?->getAuthIdentifier() ?? $request->ip();
            return [
                Limit::perMinute(10)->by('restore-credentials-minute|'.$actor),
                Limit::perHour(60)->by('restore-credentials-hour|'.$actor),
            ];
        });

        RateLimiter::for('api-devices', static function (Request $request): Limit {
            $actor = $request->user()?->getAuthIdentifier() ?? $request->ip();
            return Limit::perMinute(30)->by('api-devices|'.$actor);
        });

        RateLimiter::for('api-sensitive', static function (Request $request): Limit {
            $actor = $request->user()?->getAuthIdentifier() ?? $request->ip();
            return Limit::perMinute(5)->by('api-sensitive|'.$actor);
        });


        RateLimiter::for('password-reset-link', static function (Request $request): array {
            $email = mb_strtolower((string) $request->input('email', 'guest'));
            $key = hash('sha256', $email.'|'.$request->ip());

            return [
                Limit::perMinute(3)->by($key),
                Limit::perHour(10)->by($key),
            ];
        });

        RateLimiter::for('password-reset', static function (Request $request): Limit {
            $token = (string) $request->input('token', 'missing');
            return Limit::perMinute(5)->by(hash('sha256', $token.'|'.$request->ip()));
        });

        RateLimiter::for('customer-activation', static function (Request $request): array {
            $token = (string) $request->input('token', 'missing');
            $key = hash('sha256', $token.'|'.$request->ip());

            return [
                Limit::perMinute(5)->by('activation-minute|'.$key),
                Limit::perHour(20)->by('activation-hour|'.$key),
            ];
        });

        RateLimiter::for('portal-messages', static function (Request $request): array {
            $actor = $request->user()?->getAuthIdentifier() ?? $request->ip();

            return [
                Limit::perMinute(10)->by('portal-messages-minute|'.$actor),
                Limit::perHour(120)->by('portal-messages-hour|'.$actor),
            ];
        });

        RateLimiter::for('uploads', static function (Request $request): array {
            $actor = $request->user()?->getAuthIdentifier() ?? $request->ip();
            return [
                Limit::perMinute(10)->by('uploads-minute|'.$actor),
                Limit::perHour(60)->by('uploads-hour|'.$actor),
            ];
        });

        RateLimiter::for('exports', static function (Request $request): array {
            $actor = $request->user()?->getAuthIdentifier() ?? $request->ip();
            return [
                Limit::perMinute(5)->by('exports-minute|'.$actor),
                Limit::perHour(40)->by('exports-hour|'.$actor),
            ];
        });

        RateLimiter::for('admin-write', static function (Request $request): Limit {
            $actor = $request->user()?->getAuthIdentifier() ?? $request->ip();
            return Limit::perMinute(60)->by('admin-write|'.$actor);
        });

        RateLimiter::for('backup', static function (Request $request): Limit {
            $actor = $request->user()?->getAuthIdentifier() ?? $request->ip();
            return Limit::perHour(2)->by('backup|'.$actor);
        });
    }
}
