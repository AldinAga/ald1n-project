<?php
declare(strict_types=1);

namespace App\Support;

/**
 * Renders owner-edited legal text with a deliberately tiny markup set.
 * Every character the owner types is HTML-escaped; only the markup below
 * produces tags. No raw HTML, no attributes besides a validated https href.
 *
 *   ## Heading / ### Subheading
 *   - list item / 1. numbered item
 *   **bold**
 *   [text](https://example.rs/path)
 *   {{placeholder}}
 *
 * @author Ald1n <https://ald1n.com>
 */
final class LegalTextRenderer
{
    public const MISSING = '<span class="legal-missing">podatak nije unet</span>';

    /** @param array<string,?string> $values placeholder => value (null/'' = missing) */
    public function render(string $text, array $values): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $html = [];
        $paragraph = [];
        $list = null;
        $items = [];

        $flushParagraph = function () use (&$paragraph, &$html, $values): void {
            if ($paragraph !== []) {
                $lines = array_map(fn (string $l): string => $this->inline($l, $values), $paragraph);
                $html[] = '<p>'.implode('<br>', $lines).'</p>';
                $paragraph = [];
            }
        };
        $flushList = function () use (&$list, &$items, &$html): void {
            if ($list !== null) {
                $html[] = '<'.$list.'>'.implode('', array_map(
                    static fn (string $i): string => '<li>'.$i.'</li>', $items)).'</'.$list.'>';
                $list = null;
                $items = [];
            }
        };

        foreach (explode("\n", $text) as $raw) {
            $line = rtrim($raw);
            $trimmed = ltrim($line);
            if ($trimmed === '') {
                $flushParagraph();
                $flushList();
                continue;
            }
            if (preg_match('/^(#{2,3})\s+(.+)$/u', $trimmed, $m) === 1) {
                $flushParagraph();
                $flushList();
                $tag = strlen($m[1]) === 2 ? 'h2' : 'h3';
                $html[] = '<'.$tag.'>'.$this->inline($m[2], $values).'</'.$tag.'>';
                continue;
            }
            if (preg_match('/^-\s+(.+)$/u', $trimmed, $m) === 1
                || preg_match('/^\d{1,3}\.\s+(.+)$/u', $trimmed, $n) === 1) {
                $kind = isset($m[1]) ? 'ul' : 'ol';
                $content = isset($m[1]) ? $m[1] : $n[1];
                $flushParagraph();
                if ($list !== $kind) $flushList();
                $list = $kind;
                $items[] = $this->inline($content, $values);
                unset($m, $n);
                continue;
            }
            unset($m, $n);
            $flushList();
            $paragraph[] = $trimmed;
        }
        $flushParagraph();
        $flushList();

        return implode("\n", $html);
    }

    /** @param array<string,?string> $values */
    private function inline(string $text, array $values, bool $allowBold = true): string
    {
        $pattern = '/(\*\*.+?\*\*|\[[^\]\n]{1,200}\]\([^)\s]{1,500}\)|\{\{\s*[a-z_]{1,40}\s*\}\})/u';
        $parts = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) return e($text);
        $out = '';
        foreach ($parts as $index => $part) {
            if ($index % 2 === 0) {
                $out .= e($part);
                continue;
            }
            if (str_starts_with($part, '**')) {
                $inner = substr($part, 2, -2);
                $out .= $allowBold ? '<strong>'.$this->inline($inner, $values, false).'</strong>' : e($part);
                continue;
            }
            if (str_starts_with($part, '{{')) {
                $name = trim(substr($part, 2, -2));
                if (!array_key_exists($name, $values)) {
                    $out .= e($part);
                    continue;
                }
                $value = trim((string) $values[$name]);
                $out .= $value === '' ? self::MISSING : e($value);
                continue;
            }
            if (preg_match('/^\[([^\]]+)\]\(([^)]+)\)$/u', $part, $m) === 1 && self::safeHttpsUrl($m[2])) {
                $out .= '<a href="'.e($m[2]).'" rel="noopener noreferrer">'.e($m[1]).'</a>';
                continue;
            }
            $out .= e($part);
        }
        return $out;
    }

    public static function safeHttpsUrl(string $url): bool
    {
        if (preg_match('/[\x00-\x20\x7f"\'<>\\\\`]/', $url) === 1) return false;
        if (filter_var($url, FILTER_VALIDATE_URL) === false) return false;
        $parts = parse_url($url);
        return is_array($parts)
            && strtolower((string) ($parts['scheme'] ?? '')) === 'https'
            && !isset($parts['user']) && !isset($parts['pass'])
            && ($parts['host'] ?? '') !== '';
    }
}
