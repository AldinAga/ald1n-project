<?php

declare(strict_types=1);

namespace Tests\Support;

use PHPUnit\Framework\Assert;

/**
 * B1149 test helper: the storefront CSP allows exactly one script, the
 * nonce'd instant theme switch in the shop layout. These helpers read the
 * per-request nonce from the policy, build the exact strict policy and
 * remove that one script so the older "no other script" checks still apply
 * to everything else on the page.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class ShopThemeScript
{
    /** Nonce shape SecurityHeaders creates: base64 of 16 random bytes. */
    public const NONCE_PATTERN = '[A-Za-z0-9+/]{22}==';

    public static function strictPolicy(string $nonce): string
    {
        return "default-src 'none'; base-uri 'none'; object-src 'none'; ".
            "frame-ancestors 'none'; form-action 'self'; ".
            "script-src 'nonce-{$nonce}'; script-src-attr 'none'; script-src-elem 'nonce-{$nonce}'; ".
            "style-src 'self'; img-src 'self' data:; font-src 'self' data:; ".
            "connect-src 'none'; frame-src 'none'; worker-src 'none'; media-src 'none'";
    }

    /** The nonce named by a strict storefront policy (asserts its shape). */
    public static function nonceFromPolicy(?string $policy): string
    {
        Assert::assertIsString($policy);
        Assert::assertSame(1, preg_match("~script-src 'nonce-(".self::NONCE_PATTERN.")';~", $policy, $m), (string) $policy);

        return $m[1];
    }

    /** Asserts the exact strict policy and returns its nonce. */
    public static function assertStrictPolicy(?string $policy): string
    {
        $nonce = self::nonceFromPolicy($policy);
        Assert::assertSame(self::strictPolicy($nonce), $policy);

        return $nonce;
    }

    /**
     * Asserts the page carries exactly one nonce'd script, the theme switch,
     * with this response's nonce, and returns the HTML without it.
     */
    public static function strip(string $html, string $nonce, string $context = ''): string
    {
        Assert::assertSame(1, preg_match_all('~<script\b[^>]*\bnonce=~i', $html), $context);
        Assert::assertSame(1, preg_match('~<script nonce="([^"]*)">(.*?)</script>~s', $html, $m), $context);
        Assert::assertSame($nonce, $m[1], $context);
        $body = $m[2];
        Assert::assertStringContainsString("d.cookie='shop_theme='+t+'; path=/; max-age=31536000; SameSite=Lax'", $body, $context);
        Assert::assertLessThan(1536, strlen($body), $context);
        // Browser bar colour and back/forward-cache restore (B1149 round 2).
        Assert::assertStringContainsString("g=d.querySelectorAll('meta[name=\"theme-color\"]')", $body, $context);
        Assert::assertStringContainsString("window.addEventListener('pageshow',function(e){if(!e.persisted)return;", $body, $context);
        foreach (['eval', 'Function(', 'innerHTML', 'outerHTML', 'insertAdjacentHTML', 'document.write', 'fetch', 'XMLHttpRequest', 'import(', 'src=', '://', 'setTimeout', 'setInterval'] as $forbidden) {
            Assert::assertStringNotContainsString($forbidden, $body, $context.' '.$forbidden);
        }

        return str_replace($m[0], '', $html);
    }

    /** strip() for callers without the response headers: the nonce is read from the markup. */
    public static function stripFromMarkup(string $html, string $context = ''): string
    {
        Assert::assertSame(1, preg_match('~<script nonce="('.self::NONCE_PATTERN.')">~', $html, $m), $context);

        return self::strip($html, $m[1], $context);
    }
}
