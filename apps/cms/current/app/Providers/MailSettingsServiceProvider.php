<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\MailRuntimeConfigurator;
use App\Services\SmtpTestTransportFactory;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Throwable;

/**
 * Wires the optional database SMTP transport into every mail path:
 * the Mail facade, Mailables and Notifications resolve "mail.manager"
 * (configured on first resolution), and queue workers re-read the
 * settings before each job so a saved change takes effect without a
 * worker restart. Nothing runs at boot when no mail is sent, and any
 * failure keeps the .env mailer.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class MailSettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MailRuntimeConfigurator::class);
        $this->app->singleton(SmtpTestTransportFactory::class);
    }

    public function boot(): void
    {
        $this->app->afterResolving('mail.manager', static function (object $manager): void {
            try {
                app(MailRuntimeConfigurator::class)->apply($manager);
            } catch (Throwable) {
                // .env mail configuration remains in force.
            }
        });

        if ($this->app->resolved('mail.manager')) {
            try {
                $this->app->make(MailRuntimeConfigurator::class)->apply();
            } catch (Throwable) {
            }
        }

        Event::listen(JobProcessing::class, static function (): void {
            try {
                app(MailRuntimeConfigurator::class)->apply();
            } catch (Throwable) {
            }
        });

        RateLimiter::for('smtp-test', static function (Request $request): array {
            $actor = $request->user()?->getAuthIdentifier() ?? $request->ip();

            return [
                Limit::perMinute(3)->by('smtp-test-minute|'.$actor),
                Limit::perHour(10)->by('smtp-test-hour|'.$actor),
            ];
        });
    }
}
