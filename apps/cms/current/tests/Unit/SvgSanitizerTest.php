<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\SvgSanitizer;
use App\Services\SvgSanitizerException;
use DOMDocument;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Adversarial unit tests for the strict SVG logo sanitizer: every hostile
 * construct must be gone from the output (or the file refused), the benign
 * shapes must survive, and the output must be stable when cleaned again.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class SvgSanitizerTest extends TestCase
{
    private const OPEN = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 120 40">';

    /** The benign shape every hostile sample carries next to its payload. */
    private const SHAPE = '<rect id="mark" x="2" y="4" width="30" height="30" fill="#2d50bf"/>';

    private SvgSanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = new SvgSanitizer();
    }

    private function wrap(string $payload): string
    {
        return self::OPEN.self::SHAPE.$payload.'</svg>';
    }

    /** Parses the output again (no DTD, no network) and returns it. */
    private function reparse(string $svg): DOMDocument
    {
        $document = new DOMDocument();
        self::assertTrue($document->loadXML($svg, LIBXML_NONET));
        self::assertNull($document->doctype);
        self::assertSame('svg', $document->documentElement?->localName);
        self::assertSame(SvgSanitizer::SVG_NS, $document->documentElement?->namespaceURI);

        return $document;
    }

    private function assertBenignShapeKept(string $svg): void
    {
        $document = $this->reparse($svg);
        $rects = $document->getElementsByTagNameNS(SvgSanitizer::SVG_NS, 'rect');
        self::assertGreaterThanOrEqual(1, $rects->length);
        $found = false;
        foreach ($rects as $rect) {
            if ($rect->getAttribute('id') === 'mark') {
                $found = true;
                self::assertSame('#2d50bf', $rect->getAttribute('fill'));
                self::assertSame('30', $rect->getAttribute('width'));
            }
        }
        self::assertTrue($found, 'benign rect#mark must survive');
    }

    /** @return array<string,array{0:string,1:list<string>,2:string}> payload, forbidden output fragments, expected category */
    public static function hostilePayloads(): array
    {
        return [
            'script element' => ['<script>alert(1)</script>', ['<script', 'alert(1)'], 'script'],
            'script in CDATA' => ['<script><![CDATA[alert(1)]]></script>', ['<script', 'alert'], 'script'],
            'onload on shape' => ['<circle cx="60" cy="20" r="5" onload="alert(1)"/>', ['onload', 'alert'], 'event_handler'],
            'onclick upper case' => ['<circle cx="60" cy="20" r="5" OnClick="alert(1)"/>', ['onclick', 'alert'], 'event_handler'],
            'javascript href on a' => ['<a href="javascript:alert(1)"><circle r="3"/></a>', ['javascript', '<a'], 'element'],
            'javascript in use href' => ['<use href="javascript:alert(1)"/>', ['javascript', '<use'], 'external_reference'],
            'external xlink:href' => ['<use xlink:href="http://evil.example/x.svg#a"/>', ['evil.example', '<use'], 'external_reference'],
            'external href' => ['<use href="https://evil.example/x.svg#a"/>', ['evil.example', '<use'], 'external_reference'],
            'external use with fragment' => ['<use href="http://evil/x.svg#a"/>', ['http://evil', '<use'], 'external_reference'],
            'data uri gradient href' => ['<linearGradient id="g" href="data:image/svg+xml;base64,PHN2Zz4="/><path d="M0 0h1v1z" fill="url(#g)"/>', ['data:', 'base64'], 'external_reference'],
            'foreignObject with html' => ['<foreignObject width="10" height="10"><div xmlns="http://www.w3.org/1999/xhtml"><script>alert(1)</script><img src="x" onerror="alert(2)"/></div></foreignObject>', ['foreignObject', '<div', 'onerror', 'alert'], 'embedded_content'],
            'image with external href' => ['<image href="http://evil.example/track.png" width="10" height="10"/>', ['<image', 'evil.example'], 'embedded_content'],
            'image with data uri' => ['<image xlink:href="data:image/png;base64,iVBORw0KGgo=" width="1" height="1"/>', ['<image', 'data:'], 'embedded_content'],
            'iframe embed object' => ['<iframe src="http://evil.example"/><embed src="x"/><object data="x"/>', ['iframe', 'embed', 'object', 'evil'], 'embedded_content'],
            'css url in style attribute' => ['<circle r="3" style="fill:url(http://evil.example/p.svg#x);stroke:#000"/>', ['evil.example', 'style='], 'external_reference'],
            'css url in fill attribute' => ['<circle r="3" fill="url(http://evil.example/p.svg#x)"/>', ['evil.example'], 'external_reference'],
            'css url in style element' => ['<style>circle{fill:url(http://evil.example/p.svg#x)}</style>', ['<style', 'evil.example'], 'style'],
            'css import in style element' => ['<style>@import url("http://evil.example/x.css");</style>', ['@import', 'evil.example', '<style'], 'style'],
            'css import in style attribute' => ['<circle r="3" style="@import url(http://evil.example/x.css)"/>', ['@import', 'evil.example'], 'style'],
            'css expression' => ['<circle r="3" style="fill:expression(alert(1))"/>', ['expression', 'alert'], 'style'],
            'smil animate href' => ['<a><animate attributeName="href" to="javascript:alert(1)"/></a><animate attributeName="fill" to="red"/>', ['<animate', 'javascript'], 'animation'],
            'smil set' => ['<set attributeName="onmouseover" to="alert(1)"/>', ['<set', 'alert'], 'animation'],
            'animateTransform' => ['<animateTransform attributeName="transform" type="rotate" from="0" to="360" dur="1s"/>', ['animateTransform'], 'animation'],
            'xhtml script namespace' => ['<html:script xmlns:html="http://www.w3.org/1999/xhtml">alert(1)</html:script>', ['script', 'alert', 'xhtml'], 'script'],
            'polyglot html body inside svg' => ['<html:body xmlns:html="http://www.w3.org/1999/xhtml"><html:img src="x" onerror="alert(1)"/></html:body>', ['body', 'onerror', 'alert', 'xhtml'], 'embedded_content'],
            'filter with feImage' => ['<filter id="f"><feImage href="http://evil.example/x.png"/></filter>', ['filter', 'feImage', 'evil.example'], 'element'],
            'external mask reference' => ['<circle r="3" mask="url(http://evil.example/m.svg#m)"/>', ['evil.example'], 'external_reference'],
            'protocol relative clip path' => ['<circle r="3" clip-path="url(//evil.example/c.svg#c)"/>', ['evil.example'], 'external_reference'],
            'inkscape metadata' => ['<metadata><rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"/></metadata>', ['metadata', 'rdf'], 'element'],
            'comment' => ['<!-- <script>alert(1)</script> -->', ['<!--', 'script'], 'comment'],
            'xml:base and foreign attribute' => ['<circle r="3" xml:base="http://evil.example/" xlink:title="x"/>', ['xml:base', 'evil.example', 'xlink:title'], 'attribute'],
            'textPath href' => ['<text><textPath href="http://evil.example/p">x</textPath></text>', ['textPath', 'evil.example'], 'element'],
            'vbscript' => ['<circle r="3" fill="vbscript:msgbox(1)"/>', ['vbscript'], 'script'],
            'escaped css' => ['<circle r="3" style="fill:\75 rl(http://evil.example)"/>', ['evil.example', '\\'], 'style'],
        ];
    }

    #[DataProvider('hostilePayloads')]
    public function test_hostile_constructs_are_removed_and_the_benign_shape_survives(string $payload, array $forbidden, string $category): void
    {
        $result = $this->sanitizer->sanitize($this->wrap($payload));

        foreach ($forbidden as $fragment) {
            self::assertStringNotContainsStringIgnoringCase($fragment, $result->svg, 'output still contains '.$fragment);
        }
        self::assertStringNotContainsStringIgnoringCase('<script', $result->svg);
        self::assertDoesNotMatchRegularExpression('/\son[a-z]+\s*=/i', $result->svg);
        self::assertDoesNotMatchRegularExpression('/(?:href|src)\s*=\s*"(?!#)/i', $result->svg);
        self::assertArrayHasKey($category, $result->removed, json_encode($result->removed));
        self::assertGreaterThan(0, $result->removedTotal());
        $this->assertBenignShapeKept($result->svg);
        // Idempotent: cleaning the cleaned file changes nothing and removes nothing.
        $again = $this->sanitizer->sanitize($result->svg);
        self::assertSame($result->svg, $again->svg);
        self::assertSame([], $again->removed);
    }

    public function test_benign_logo_keeps_shapes_text_gradients_and_fragment_references(): void
    {
        $svg = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="300px" height="72" viewBox="0 0 150 36">'
            .'<title>AP.Computers</title><desc>Logo</desc>'
            .'<defs><linearGradient id="g1" x1="0" x2="1"><stop offset="0" stop-color="#2d50bf"/><stop offset="1" stop-color="#4a6fe0" stop-opacity=".8"/></linearGradient>'
            .'<clipPath id="c"><rect width="150" height="36" rx="6"/></clipPath><symbol id="s" viewBox="0 0 10 10"><circle cx="5" cy="5" r="4"/></symbol></defs>'
            .'<g clip-path="url(#c)" transform="translate(1 1)"><rect x="2" y="4" width="28" height="28" fill="url(#g1)"/>'
            .'<use xlink:href="#s" x="4" y="8" width="8" height="8"/>'
            .'<path d="M40 10 L60 10 L60 30 Z" style="fill:#121b24;stroke:#000;stroke-width:0.5"/>'
            .'<text x="70" y="24" font-family="Arial, sans-serif" font-size="14" fill="#121b24">AP<tspan font-weight="700">.Computers</tspan> &amp; Đ</text></g></svg>';
        $result = $this->sanitizer->sanitize($svg);

        self::assertSame([], $result->removed);
        self::assertSame(150.0, $result->width);
        self::assertSame(36.0, $result->height);
        self::assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>'."\n".'<svg xmlns="http://www.w3.org/2000/svg"', $result->svg);
        self::assertStringContainsString('fill="url(#g1)"', $result->svg);
        self::assertStringContainsString('clip-path="url(#c)"', $result->svg);
        // xlink:href becomes plain href, still same-document only.
        self::assertStringContainsString('<use height="8" href="#s" width="8" x="4" y="8"/>', $result->svg);
        // Style declarations become presentation attributes.
        self::assertStringContainsString('<path d="M40 10 L60 10 L60 30 Z" fill="#121b24" stroke="#000" stroke-width="0.5"/>', $result->svg);
        // Root size without "px", attributes sorted.
        self::assertStringContainsString('<svg xmlns="http://www.w3.org/2000/svg" height="72" viewBox="0 0 150 36" width="300">', $result->svg);
        self::assertStringContainsString('<title>AP.Computers</title>', $result->svg);
        $document = $this->reparse($result->svg);
        self::assertSame(1, $document->getElementsByTagNameNS(SvgSanitizer::SVG_NS, 'tspan')->length);
        self::assertSame('AP.Computers & Đ', $document->getElementsByTagNameNS(SvgSanitizer::SVG_NS, 'text')->item(0)?->textContent);
        self::assertSame($result->svg, $this->sanitizer->sanitize($result->svg)->svg);
        self::assertSame(hash('sha256', $result->svg), $result->sha256());
    }

    public function test_missing_namespace_bom_and_missing_viewbox_are_normalised(): void
    {
        $result = $this->sanitizer->sanitize("\xEF\xBB\xBF".'<svg width="40" height="20"><circle cx="5" cy="5" r="4"/></svg>');

        self::assertStringNotContainsString("\xEF\xBB\xBF", $result->svg);
        self::assertStringContainsString('xmlns="http://www.w3.org/2000/svg"', $result->svg);
        self::assertStringContainsString('viewBox="0 0 40 20"', $result->svg);
        self::assertSame(40.0, $result->width);
        self::assertSame(20.0, $result->height);
        self::assertSame($result->svg, $this->sanitizer->sanitize($result->svg)->svg);
    }

    /** @return array<string,array{0:string,1:string}> */
    public static function rejectedDocuments(): array
    {
        $shape = '<rect width="1" height="1"/>';

        return [
            'xxe external entity reading /etc/passwd' => [
                '<?xml version="1.0"?><!DOCTYPE svg [<!ENTITY xxe SYSTEM "file:///etc/passwd">]><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"><text>&xxe;</text>'.$shape.'</svg>',
                SvgSanitizer::REJECT_DOCTYPE,
            ],
            'xxe parameter entity' => [
                '<!DOCTYPE svg [<!ENTITY % p SYSTEM "http://evil.example/x.dtd"> %p;]><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1">'.$shape.'</svg>',
                SvgSanitizer::REJECT_DOCTYPE,
            ],
            'billion laughs' => [
                '<?xml version="1.0"?><!DOCTYPE lolz [<!ENTITY lol "lol"><!ENTITY lol1 "&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;"><!ENTITY lol2 "&lol1;&lol1;&lol1;&lol1;&lol1;&lol1;&lol1;&lol1;&lol1;&lol1;"><!ENTITY lol3 "&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;">]><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"><text>&lol3;</text></svg>',
                SvgSanitizer::REJECT_DOCTYPE,
            ],
            'public doctype' => [
                '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1">'.$shape.'</svg>',
                SvgSanitizer::REJECT_DOCTYPE,
            ],
            'xml-stylesheet processing instruction' => [
                '<?xml version="1.0"?><?xml-stylesheet type="text/css" href="http://evil.example/x.css"?><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1">'.$shape.'</svg>',
                SvgSanitizer::REJECT_PROCESSING_INSTRUCTION,
            ],
            'processing instruction inside' => [
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"><?php echo 1; ?>'.$shape.'</svg>',
                SvgSanitizer::REJECT_PROCESSING_INSTRUCTION,
            ],
            'utf-16 declared' => [
                '<?xml version="1.0" encoding="UTF-16"?><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1">'.$shape.'</svg>',
                SvgSanitizer::REJECT_INVALID_ENCODING,
            ],
            'invalid utf-8' => [
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"><text>'."\xC3\x28\xA0\xA1".'</text>'.$shape.'</svg>',
                SvgSanitizer::REJECT_INVALID_ENCODING,
            ],
            'null byte' => ["<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 1 1\">\0".$shape.'</svg>', SvgSanitizer::REJECT_INVALID_ENCODING],
            'empty' => ['   ', SvgSanitizer::REJECT_INVALID_ENCODING],
            'not xml' => ['this is not an svg', SvgSanitizer::REJECT_INVALID_XML],
            'broken xml' => ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"><rect>', SvgSanitizer::REJECT_INVALID_XML],
            'undefined entity' => ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"><text>&nbsp;</text>'.$shape.'</svg>', SvgSanitizer::REJECT_INVALID_XML],
            'html root polyglot' => ['<html><body><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1">'.$shape.'</svg><script>alert(1)</script></body></html>', SvgSanitizer::REJECT_NOT_SVG],
            'xhtml namespaced svg root' => ['<svg xmlns="http://www.w3.org/1999/xhtml" viewBox="0 0 1 1">'.$shape.'</svg>', SvgSanitizer::REJECT_NOT_SVG],
            'png bytes' => ["\x89PNG\r\n\x1a\n\0\0\0\rIHDR", SvgSanitizer::REJECT_INVALID_ENCODING],
            'no viewBox and no size' => ['<svg xmlns="http://www.w3.org/2000/svg">'.$shape.'</svg>', SvgSanitizer::REJECT_NO_DIMENSIONS],
            'percent size only' => ['<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%">'.$shape.'</svg>', SvgSanitizer::REJECT_NO_DIMENSIONS],
            'zero viewBox' => ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 0 0">'.$shape.'</svg>', SvgSanitizer::REJECT_NO_DIMENSIONS],
            'only hostile content' => ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"><script>alert(1)</script><foreignObject/></svg>', SvgSanitizer::REJECT_EMPTY],
            'huge file' => ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1">'.$shape.'<desc>'.str_repeat('A', SvgSanitizer::MAX_BYTES).'</desc></svg>', SvgSanitizer::REJECT_TOO_LARGE],
            'deeply nested' => ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1">'.str_repeat('<g>', 40).$shape.str_repeat('</g>', 40).'</svg>', SvgSanitizer::REJECT_TOO_DEEP],
            '100k elements' => ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1">'.str_repeat('<g/>', 100000).'</svg>', SvgSanitizer::REJECT_TOO_LARGE],
            '6000 elements under the size cap' => ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1">'.str_repeat('<g/>', 6000).'</svg>', SvgSanitizer::REJECT_TOO_COMPLEX],
        ];
    }

    #[DataProvider('rejectedDocuments')]
    public function test_unsafe_or_unusable_documents_are_refused_with_a_reason_code(string $svg, string $reason): void
    {
        try {
            $this->sanitizer->sanitize($svg);
            self::fail('expected rejection: '.$reason);
        } catch (SvgSanitizerException $exception) {
            self::assertSame($reason, $exception->reason);
            self::assertStringNotContainsString('passwd', $exception->getMessage());
        }
    }

    public function test_xxe_never_reads_the_local_file_even_when_the_doctype_is_hidden_behind_whitespace(): void
    {
        $secret = tempnam(sys_get_temp_dir(), 'svgxxe');
        self::assertIsString($secret);
        file_put_contents($secret, 'TOP-SECRET-LOCAL-FILE');
        try {
            foreach ([
                "<?xml version=\"1.0\"?>\n<!DOCTYPE svg [\n <!ENTITY x SYSTEM \"file://".$secret."\">\n]>\n<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 1 1\"><text>&x;</text><rect width=\"1\" height=\"1\"/></svg>",
                "<!  DOCTYPE svg [<!ENTITY x SYSTEM \"".$secret."\">]><svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 1 1\"><text>&x;</text><rect width=\"1\" height=\"1\"/></svg>",
            ] as $svg) {
                try {
                    $result = $this->sanitizer->sanitize($svg);
                    self::assertStringNotContainsString('TOP-SECRET', $result->svg);
                } catch (SvgSanitizerException $exception) {
                    self::assertContains($exception->reason, [SvgSanitizer::REJECT_DOCTYPE, SvgSanitizer::REJECT_INVALID_XML]);
                    self::assertStringNotContainsString('TOP-SECRET', $exception->getMessage());
                }
            }
        } finally {
            @unlink($secret);
        }
    }

    public function test_cdata_text_trick_is_escaped_text_not_markup(): void
    {
        $result = $this->sanitizer->sanitize('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><rect width="1" height="1"/><text><![CDATA[</text><script>alert(1)</script>]]></text></svg>');

        self::assertStringNotContainsString('<script', $result->svg);
        self::assertStringContainsString('&lt;script&gt;', $result->svg);
        $document = $this->reparse($result->svg);
        self::assertSame(0, $document->getElementsByTagName('script')->length);
    }

    public function test_output_is_identical_for_identical_input_and_counts_are_reported(): void
    {
        $hostile = $this->wrap('<script>a()</script><circle r="2" onload="b()" onclick="c()"/><use href="http://x/y.svg#z"/>');
        $first = $this->sanitizer->sanitize($hostile);
        $second = $this->sanitizer->sanitize($hostile);

        self::assertSame($first->svg, $second->svg);
        self::assertSame(['script' => 1, 'event_handler' => 2, 'external_reference' => 1], $first->removed);
        self::assertSame(4, $first->removedTotal());
    }

    public function test_use_amplification_chain_is_cut_and_the_result_is_stable(): void
    {
        // Classic "billion laughs" for <use>: every level references the
        // previous one ten times, so 10 levels would render 10^10 rectangles
        // from a 1 KB file.
        $levels = '<defs><g id="l0"><rect width="1" height="1"/></g>';
        for ($level = 1; $level <= 10; $level++) {
            $levels .= '<g id="l'.$level.'">'.str_repeat('<use href="#l'.($level - 1).'"/>', 10).'</g>';
        }
        $svg = $this->wrap($levels.'</defs><use href="#l10"/>');

        try {
            $this->sanitizer->sanitize($svg);
            self::fail('more than '.SvgSanitizer::MAX_USE.' use elements must be refused');
        } catch (SvgSanitizerException $exception) {
            self::assertSame(SvgSanitizer::REJECT_TOO_COMPLEX, $exception->reason);
        }

        // Below the count limit, use-of-use is removed: only the use that
        // points at plain shapes survives.
        $small = $this->wrap('<defs><g id="a"><rect width="1" height="1"/></g>'
            .'<g id="b"><use href="#a"/><use href="#a"/></g><g id="c"><use href="#b"/><use href="#b"/></g></defs>'
            .'<use href="#c"/><use href="#a" x="5"/><use id="self" href="#self"/>');
        $result = $this->sanitizer->sanitize($small);
        $document = $this->reparse($result->svg);
        $hrefs = [];
        foreach ($document->getElementsByTagNameNS(SvgSanitizer::SVG_NS, 'use') as $use) {
            $hrefs[] = $use->getAttribute('href');
        }
        self::assertSame(['#a', '#a', '#a'], $hrefs, 'only uses whose target holds no other use remain');
        self::assertSame(4, $result->removed['reference'] ?? 0, 'c->b, b->a (x2 kept), #c, #self');
        self::assertSame($result->svg, $this->sanitizer->sanitize($result->svg)->svg, 'idempotent');
        self::assertSame([], $this->sanitizer->sanitize($result->svg)->removed);
    }

    public function test_nested_pattern_references_and_uses_inside_patterns_are_removed(): void
    {
        $svg = $this->wrap('<defs>'
            .'<pattern id="p1" width="2" height="2" patternUnits="userSpaceOnUse"><rect width="1" height="1" fill="#000"/></pattern>'
            .'<pattern id="p2" width="2" height="2" patternUnits="userSpaceOnUse" fill="url(#p1)"><rect width="2" height="2" fill="url(#p1)"/><use href="#dot"/></pattern>'
            .'<pattern id="p3" href="#p1" patternTransform="scale(2)"/>'
            .'<g id="dot"><circle r="1"/></g><g id="tile"><rect width="3" height="3" fill="url(#p1)"/></g><g id="holder"><pattern id="p4" width="1" height="1"><rect width="1" height="1"/></pattern></g>'
            .'</defs><rect x="10" width="20" height="20" fill="url(#p2)"/><use href="#tile"/><use href="#holder"/><use href="#p1"/>');
        $result = $this->sanitizer->sanitize($svg);
        $document = $this->reparse($result->svg);

        $patterns = [];
        foreach ($document->getElementsByTagNameNS(SvgSanitizer::SVG_NS, 'pattern') as $pattern) {
            $patterns[$pattern->getAttribute('id')] = $pattern;
        }
        // Inside p2: the pattern paint and the use are gone, the shape stays.
        self::assertFalse($patterns['p2']->hasAttribute('fill'));
        self::assertSame(0, $patterns['p2']->getElementsByTagNameNS(SvgSanitizer::SVG_NS, 'use')->length);
        self::assertSame(1, $patterns['p2']->getElementsByTagNameNS(SvgSanitizer::SVG_NS, 'rect')->length);
        self::assertFalse($patterns['p2']->getElementsByTagNameNS(SvgSanitizer::SVG_NS, 'rect')->item(0)?->hasAttribute('fill'));
        // Pattern attribute inheritance (Inkscape) is not tiling and stays.
        self::assertSame('#p1', $patterns['p3']->getAttribute('href'));
        // A pattern used as paint outside patterns is fine.
        self::assertStringContainsString('fill="url(#p2)"', $result->svg);
        // Uses pointing at a pattern or at a group holding one are removed.
        $hrefs = [];
        foreach ($document->getElementsByTagNameNS(SvgSanitizer::SVG_NS, 'use') as $use) {
            $hrefs[] = $use->getAttribute('href');
        }
        self::assertSame(['#tile'], $hrefs);
        self::assertSame(5, $result->removed['reference'] ?? 0);
        self::assertSame($result->svg, $this->sanitizer->sanitize($result->svg)->svg, 'idempotent');
    }

    public function test_expanded_size_of_kept_uses_is_bounded(): void
    {
        // 100 uses (the maximum) of a 300-element group: 30,000 rendered
        // elements from a 400-element file.
        $group = '<g id="big">'.str_repeat('<rect width="1" height="1"/>', 300).'</g>';
        $svg = $this->wrap('<defs>'.$group.'</defs>'.str_repeat('<use href="#big"/>', 100));
        try {
            $this->sanitizer->sanitize($svg);
            self::fail('expanded size above '.SvgSanitizer::MAX_EXPANDED_ELEMENTS.' must be refused');
        } catch (SvgSanitizerException $exception) {
            self::assertSame(SvgSanitizer::REJECT_TOO_COMPLEX, $exception->reason);
        }
        // The same group used a few times is an ordinary logo.
        $ok = $this->sanitizer->sanitize($this->wrap('<defs>'.$group.'</defs>'.str_repeat('<use href="#big"/>', 3)));
        self::assertSame(3, substr_count($ok->svg, '<use '));
    }
}
