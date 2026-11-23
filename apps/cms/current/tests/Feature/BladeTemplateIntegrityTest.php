<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use ParseError;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * Raw Blade must never reach the browser. Blade only compiles a directive when no word character sits directly
 * before the "@" (its regex starts with \B@), so "@empty@if(...)" or "@endif@endforelse" stay as literal text on the
 * page (production /warranties, B1156). This test walks every *.blade.php and fails on
 *  - a directive glued to a word character or to another directive without arguments,
 *  - a template that does not compile to valid PHP (unbalanced @if/@foreach/@for/@while/@switch and so on),
 *  - unbalanced @section, @push, @prepend, @once, @slot, @component and @php blocks (including an inline
 *    "@php(...)" that Blade swallows into the next @endphp).
 * Author: Ald1n <https://ald1n.com>
 */
final class BladeTemplateIntegrityTest extends TestCase
{
    /** Directives Blade compiles itself. Anything else ("@media", "@keyframes", an e-mail domain) is ignored. */
    private const DIRECTIVES = 'if|elseif|else|endif|unless|endunless|isset|endisset|empty|endempty|foreach|endforeach|forelse|endforelse|for|endfor|while|endwhile|switch|case|default|endswitch|break|continue|section|endsection|show|stop|append|overwrite|yield|extends|include|includeIf|includeWhen|includeUnless|includeFirst|each|push|endpush|prepend|endprepend|pushOnce|endPushOnce|prependOnce|endPrependOnce|pushIf|endPushIf|stack|php|endphp|auth|endauth|guest|endguest|can|cannot|canany|elsecan|elsecannot|endcan|endcannot|endcanany|error|enderror|csrf|method|props|aware|class|style|checked|selected|disabled|readonly|required|json|lang|endlang|once|endonce|env|endenv|production|endproduction|session|endsession|use|vite|slot|endslot|component|endcomponent|hasSection|sectionMissing|verbatim|endverbatim|fragment|endfragment|inject|dd|dump|unset|lang|livewire';

    /** @return list<string> absolute paths of every Blade template */
    private static function templates(): array
    {
        $paths = [];
        $root = dirname(__DIR__, 2).'/resources/views';
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
            if (str_ends_with($file->getFilename(), '.blade.php')) {
                $paths[] = $file->getPathname();
            }
        }
        sort($paths);

        return $paths;
    }

    /** Replace what Blade never compiles with spaces (newlines stay, so line numbers stay). */
    private static function mask(string $source): string
    {
        $blank = static fn (array $m): string => (string) preg_replace('/[^\n]/', ' ', $m[0]);
        foreach (['/\{\{--.*?--\}\}/s', '/@verbatim.*?@endverbatim/s', '/@@\w+/', '/@\{\{.*?\}\}/s', '/@\{!!.*?!!\}/s'] as $re) {
            $source = (string) preg_replace_callback($re, $blank, $source);
        }

        return $source;
    }

    /** @return list<string> "line N: ...excerpt" for every directive glued to a word character */
    public static function gluedDirectives(string $source): array
    {
        $masked = self::mask($source);
        // (?<=\w): a word character right before "@".
        preg_match_all('/(?<=\w)@('.self::DIRECTIVES.')(?!\w)/', $masked, $m, PREG_OFFSET_CAPTURE);
        $found = [];
        foreach ($m[0] as [$text, $offset]) {
            // "a@if.com" is an e-mail address, unless the word before the "@" is the tail of another directive
            // ("@endif@endif.", "@empty@if-"): that one is glued and stays raw.
            $after = substr($masked, $offset + strlen($text), 2);
            if (preg_match('/^[.-]\w$/', $after) && !preg_match('/@\w+$/', substr($masked, 0, $offset))) {
                continue;
            }
            $line = substr_count($masked, "\n", 0, $offset) + 1;
            $found[] = 'line '.$line.': '.trim(substr($masked, max(0, $offset - 20), 44));
        }

        return $found;
    }

    /** @return list<string> structural problems in the block directives that compile to no PHP block of their own */
    public static function unbalancedBlocks(string $source): array
    {
        $masked = self::mask($source);
        $problems = [];

        // Blade stores "@php ... @endphp" first, with the lazy regex below. An inline "@php(...)" followed by any
        // later @endphp is therefore swallowed into that block.
        $rest = (string) preg_replace_callback('/(?<!@)@php(.*?)@endphp/s', function (array $m) use (&$problems, $masked): string {
            if (str_starts_with($m[1], '(')) {
                $problems[] = 'inline @php(...) swallowed by a later @endphp near "'.trim(substr($m[0], 0, 40)).'"';
            }

            return str_repeat(' ', strlen($m[0]));
        }, $masked);
        if (preg_match('/(?<![@\w])@php(?!\s*\()/', $rest)) {
            $problems[] = '@php block without @endphp';
        }
        if (preg_match('/(?<![@\w])@endphp\b/', $rest)) {
            $problems[] = '@endphp without @php';
        }

        $families = [
            'section' => [['section'], ['endsection', 'show', 'stop', 'append', 'overwrite']],
            'push' => [['push'], ['endpush']],
            'prepend' => [['prepend'], ['endprepend']],
            'pushOnce' => [['pushOnce'], ['endPushOnce']],
            'prependOnce' => [['prependOnce'], ['endPrependOnce']],
            'pushIf' => [['pushIf'], ['endPushIf']],
            'once' => [['once'], ['endonce']],
            'slot' => [['slot'], ['endslot']],
            'component' => [['component'], ['endcomponent']],
        ];
        $depth = array_fill_keys(array_keys($families), 0);
        preg_match_all('/(?<![@\w])@(section|endsection|show|stop|append|overwrite|push|endpush|prepend|endprepend|pushOnce|endPushOnce|prependOnce|endPrependOnce|pushIf|endPushIf|once|endonce|slot|endslot|component|endcomponent)\b/', $rest, $m, PREG_OFFSET_CAPTURE);
        foreach ($m[1] as [$name, $offset]) {
            foreach ($families as $family => [$openers, $closers]) {
                if (in_array($name, $openers, true)) {
                    // "@section('title', 'text')" is the inline form and needs no closer.
                    if ($family === 'section' && self::topLevelComma(self::arguments($rest, $offset + strlen($name)))) {
                        continue;
                    }
                    $depth[$family]++;
                } elseif (in_array($name, $closers, true) && --$depth[$family] < 0) {
                    $problems[] = '@'.$name.' without an opening directive';
                    $depth[$family] = 0;
                }
            }
        }
        foreach ($depth as $family => $open) {
            if ($open > 0) {
                $problems[] = $open.' unclosed @'.$family;
            }
        }

        return $problems;
    }

    /** The "(...)" argument text that starts right at $pos (spaces and tabs allowed), or "". */
    private static function arguments(string $text, int $pos): string
    {
        $len = strlen($text);
        while ($pos < $len && ($text[$pos] === ' ' || $text[$pos] === "\t")) {
            $pos++;
        }
        if ($pos >= $len || $text[$pos] !== '(') {
            return '';
        }
        $depth = 0;
        $quote = '';
        for ($i = $pos; $i < $len; $i++) {
            $c = $text[$i];
            if ($quote !== '') {
                if ($c === '\\') {
                    $i++;
                } elseif ($c === $quote) {
                    $quote = '';
                }
            } elseif ($c === "'" || $c === '"') {
                $quote = $c;
            } elseif ($c === '(') {
                $depth++;
            } elseif ($c === ')' && --$depth === 0) {
                return substr($text, $pos + 1, $i - $pos - 1);
            }
        }

        return '';
    }

    private static function topLevelComma(string $args): bool
    {
        $depth = 0;
        $quote = '';
        for ($i = 0, $n = strlen($args); $i < $n; $i++) {
            $c = $args[$i];
            if ($quote !== '') {
                if ($c === '\\') {
                    $i++;
                } elseif ($c === $quote) {
                    $quote = '';
                }
            } elseif ($c === "'" || $c === '"') {
                $quote = $c;
            } elseif ($c === '(' || $c === '[') {
                $depth++;
            } elseif ($c === ')' || $c === ']') {
                $depth--;
            } elseif ($c === ',' && $depth === 0) {
                return true;
            }
        }

        return false;
    }

    public function test_the_detector_finds_the_production_bug_and_ignores_legitimate_at_signs(): void
    {
        $bad = [
            '@forelse($a as $b)x@empty@if($c)y@endif@endforelse',
            '<p>@if($a)aktivna@endif</p>',
            '@if($a)x@else@if($b)y@endif@endif',
            '@foreach($a as $b){{ $b }}@endforeach@endsection',
            '@if($a)x@endif@endif.',
            '@if($a)x@endif@endif-ok',
            '@if($a)@else@if.com@endif@endif',
            '@forelse($a as $b)x@empty@if-b($c)y@endif@endforelse',
        ];
        foreach ($bad as $source) {
            $this->assertNotSame([], self::gluedDirectives($source), $source);
        }
        $this->assertCount(2, self::gluedDirectives('@forelse($a as $b)<b></b>@empty@if($c)<i></i>@endif@endforelse'));

        $good = [
            'Mail: prodaja@example.com i info@if.rs, @@if(x) @{{ name }} @{!! y !!}',
            '<style>@media (min-width:600px){a{b:c}}@keyframes x{from{a:b}}@font-face{a:b}</style>',
            "@forelse(\$a as \$b)<b></b> @empty\n@if(\$c)<i></i> @endif\n@endforelse",
            '{{-- x@endif y@if --}} @verbatim a@if(b) @endverbatim',
            '@if($a)x @endif @foreach($a as $b)@if($b)<i></i>@endif @endforeach',
        ];
        foreach ($good as $source) {
            $this->assertSame([], self::gluedDirectives($source), $source);
        }
    }

    public function test_the_block_checker_finds_unbalanced_sections_and_php_blocks(): void
    {
        $this->assertNotSame([], self::unbalancedBlocks("@extends('a')\n@section('content')\nx"));
        $this->assertNotSame([], self::unbalancedBlocks("@php\n\$a = 1;\nx"));
        $this->assertNotSame([], self::unbalancedBlocks("x\n@endphp"));
        $this->assertNotSame([], self::unbalancedBlocks("@php(\$a = 1)\n<p>x</p>\n@php\n\$b = 2;\n@endphp"));
        $this->assertNotSame([], self::unbalancedBlocks("@push('a')\nx\n@endpush\n@endpush"));

        $this->assertSame([], self::unbalancedBlocks("@section('title', 'Moje')\n@section('content')\nx\n@endsection\n@php(\$a = 1)\n@push('s')<i></i>@endpush"));
        $this->assertSame([], self::unbalancedBlocks("@php\n\$a = 1;\n@endphp\n@section('a')<p></p> @show\n@section('b', trim(\$x, 'a,b'))"));
    }

    public function test_the_view_walk_is_not_empty(): void
    {
        $this->assertGreaterThan(100, count(self::templates()));
    }

    public function test_no_template_has_a_directive_glued_to_a_word_character(): void
    {
        $failures = [];
        foreach (self::templates() as $path) {
            foreach (self::gluedDirectives((string) file_get_contents($path)) as $hit) {
                $failures[] = $this->relative($path).' '.$hit;
            }
        }
        $this->assertSame([], $failures, "Blade leaves these directives as raw text in the browser:\n".implode("\n", $failures));
    }

    public function test_every_template_compiles_to_valid_php(): void
    {
        $failures = [];
        foreach (self::templates() as $path) {
            try {
                $compiled = Blade::compileString((string) file_get_contents($path));
                token_get_all($compiled, TOKEN_PARSE);
            } catch (ParseError $e) {
                $failures[] = $this->relative($path).': '.$e->getMessage().' (compiled line '.$e->getLine().')';
            }
        }
        $this->assertSame([], $failures, "Unbalanced or broken Blade:\n".implode("\n", $failures));
    }

    public function test_block_directives_are_balanced_in_every_template(): void
    {
        $failures = [];
        foreach (self::templates() as $path) {
            foreach (self::unbalancedBlocks((string) file_get_contents($path)) as $problem) {
                $failures[] = $this->relative($path).': '.$problem;
            }
        }
        $this->assertSame([], $failures, "Unbalanced block directives:\n".implode("\n", $failures));
    }

    private function relative(string $path): string
    {
        return str_replace('\\', '/', substr($path, strlen(dirname(__DIR__, 2)) + 1));
    }
}
