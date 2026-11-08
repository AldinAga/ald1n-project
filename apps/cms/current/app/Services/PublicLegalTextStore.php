<?php
declare(strict_types=1);

namespace App\Services;

use App\Support\PublicLegalTemplates;

/**
 * Stored owner-edited legal page bodies with the shipped neutral template as
 * the default. Depends only on SettingsService so the merchant fingerprint can
 * include a digest of the effective texts without a service cycle.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class PublicLegalTextStore
{
    public const KEY_PREFIX = 'public_legal_text_';
    public const MAX_LENGTH = 20000;

    /** Identity values used by placeholders but not already part of policy(). */
    private const EXTRA_IDENTITY = [
        'documents_company_registration_number',
        'documents_company_phone',
        'documents_company_email',
    ];

    public function __construct(private readonly SettingsService $settings) {}

    public static function key(string $page): string
    {
        return self::KEY_PREFIX.$page;
    }

    public function isCustom(string $page): bool
    {
        $this->assertEditable($page);
        return trim((string) $this->settings->get(self::key($page), '')) !== '';
    }

    /** Raw markup the public page renders: stored text, else the template. */
    public function effective(string $page): string
    {
        $this->assertEditable($page);
        $stored = (string) $this->settings->get(self::key($page), '');
        return trim($stored) !== '' ? self::normalize($stored) : PublicLegalTemplates::body($page);
    }

    public function hash(string $page): string
    {
        return hash('sha256', $this->effective($page));
    }

    /**
     * Digest of every effective legal text plus the identity values that only
     * placeholders use. Any edit changes it and so invalidates approval.
     */
    public function digest(): string
    {
        $texts = [];
        foreach (PublicLegalTemplates::EDITABLE as $page) {
            $texts[$page] = $this->hash($page);
        }
        $extra = [];
        foreach (self::EXTRA_IDENTITY as $key) {
            $extra[$key] = trim((string) $this->settings->get($key, ''));
        }
        return hash('sha256', json_encode(['texts' => $texts, 'identity' => $extra],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public static function normalize(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        // Drop control characters except newline and tab.
        $text = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);
        return trim($text);
    }

    private function assertEditable(string $page): void
    {
        if (!in_array($page, PublicLegalTemplates::EDITABLE, true)) {
            throw new \InvalidArgumentException('Unknown legal page.');
        }
    }
}
