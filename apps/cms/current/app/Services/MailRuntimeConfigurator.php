<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Mail\MailManager;
use Throwable;

/**
 * Applies the database SMTP settings to the in-memory mail configuration.
 *
 * Called when the mail manager is first resolved, before every queued job
 * (long-running workers pick up changes) and by the public registration
 * transport guard. When the database transport is OFF or incomplete the
 * .env configuration stays exactly as loaded; after a switch back from the
 * database transport the configuration captured before the switch returns.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class MailRuntimeConfigurator
{
    /** @var array{default:mixed,smtp:mixed}|null */
    private ?array $envSnapshot = null;

    private ?string $appliedFingerprint = null;

    private string $source = 'env';

    private string $reason = 'disabled';

    /** @return 'env'|'database' */
    public function apply(?object $manager = null): string
    {
        try {
            $runtime = app(SmtpMailSettingsService::class)->runtimeConfig();
        } catch (Throwable) {
            $runtime = ['active' => false, 'reason' => 'unavailable', 'config' => null];
        }
        $this->reason = $runtime['reason'];

        try {
            if ($runtime['active'] && is_array($runtime['config'])) {
                $this->envSnapshot ??= [
                    'default' => config('mail.default'),
                    'smtp' => config('mail.mailers.smtp'),
                ];
                $fingerprint = hash('sha256', serialize($runtime['config']));
                if ($fingerprint !== $this->appliedFingerprint || config('mail.default') !== 'smtp') {
                    config([
                        'mail.default' => 'smtp',
                        'mail.mailers.smtp' => $runtime['config'],
                    ]);
                    $this->appliedFingerprint = $fingerprint;
                    $this->purge($manager);
                }
                $this->source = 'database';

                return 'database';
            }

            if ($this->envSnapshot !== null) {
                config([
                    'mail.default' => $this->envSnapshot['default'],
                    'mail.mailers.smtp' => $this->envSnapshot['smtp'],
                ]);
                $this->envSnapshot = null;
                $this->appliedFingerprint = null;
                $this->purge($manager);
            }
        } catch (Throwable) {
            // Never let mail configuration break boot, a request or a worker.
        }
        $this->source = 'env';

        return 'env';
    }

    /** Default mailer name after the database settings are taken into account. */
    public function effectiveDefaultMailer(): string
    {
        $this->apply();

        return (string) config('mail.default');
    }

    /** The .env mailer name, even while the database transport is active. */
    public function envDefaultMailer(): string
    {
        return (string) ($this->envSnapshot['default'] ?? config('mail.default'));
    }

    public function source(): string
    {
        return $this->source;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    private function purge(?object $manager): void
    {
        try {
            $manager ??= app()->resolved('mail.manager') ? app('mail.manager') : null;
            if ($manager instanceof MailManager) {
                $manager->purge('smtp');
            }
        } catch (Throwable) {
            // A stale mailer is retried on the next apply().
        }
    }
}
