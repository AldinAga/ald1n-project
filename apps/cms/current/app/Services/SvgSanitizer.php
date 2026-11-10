<?php

declare(strict_types=1);

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Strict allowlist sanitizer for uploaded SVG logos and favicons.
 *
 * The input is parsed as XML without network access, external entities or a
 * DTD (LIBXML_NONET, no LIBXML_NOENT/DTDLOAD; DOCTYPE and ENTITY are refused
 * before parsing, which also stops billion-laughs expansion). A NEW document
 * is then built from scratch: only allowlisted SVG elements and attributes
 * are copied, every href must be a same-document "#fragment", url() values
 * must point to "#fragment", and style attributes are converted into
 * allowlisted presentation attributes. Scripts, event handlers, external or
 * data: references, foreignObject/image/iframe/embed/object, SMIL animation,
 * <style> elements, comments, metadata and foreign namespaces are removed and
 * counted. The output is canonical (sorted attributes, no comments, no
 * whitespace between elements), so sanitize(sanitize(x)) === sanitize(x).
 *
 * The result is only ever shown through <img> and served with a sandboxed
 * CSP; the sanitizer is the first of these layers, not the only one.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class SvgSanitizer
{
    public const SVG_NS = 'http://www.w3.org/2000/svg';

    public const MAX_BYTES = 204800;

    public const MAX_ELEMENTS = 5000;

    public const MAX_DEPTH = 32;

    /**
     * Bump whenever the output of sanitize() can change. It is part of the
     * served-SVG cache key and of the site-assets URL version, so stored
     * files are cleaned again and browsers fetch the new form (Cloudflare:
     * purge /site-assets/* after a bump).
     */
    public const VERSION = '2';

    /** Most <use> elements an SVG logo may keep. */
    public const MAX_USE = 100;

    /** Elements after expanding every kept <use> once (amplification bound). */
    public const MAX_EXPANDED_ELEMENTS = 20000;

    public const REJECT_TOO_LARGE = 'too_large';
    public const REJECT_TOO_COMPLEX = 'too_complex';
    public const REJECT_TOO_DEEP = 'too_deep';
    public const REJECT_INVALID_ENCODING = 'invalid_encoding';
    public const REJECT_INVALID_XML = 'invalid_xml';
    public const REJECT_NOT_SVG = 'not_svg';
    public const REJECT_DOCTYPE = 'doctype';
    public const REJECT_PROCESSING_INSTRUCTION = 'processing_instruction';
    public const REJECT_NO_DIMENSIONS = 'no_dimensions';
    public const REJECT_EMPTY = 'empty';

    /** Removal categories reported to the user and the audit log (counts only). */
    public const REMOVED_CATEGORIES = [
        'script', 'event_handler', 'external_reference', 'embedded_content',
        'animation', 'style', 'reference', 'element', 'attribute', 'comment',
    ];

    private const XLINK_NS = 'http://www.w3.org/1999/xlink';

    private const MAX_ATTRIBUTE_LENGTH = 2048;

    private const MAX_PATH_DATA_LENGTH = 131072;

    private const ELEMENTS = [
        'svg', 'g', 'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon',
        'text', 'tspan', 'defs', 'linearGradient', 'radialGradient', 'stop', 'clipPath',
        'mask', 'pattern', 'symbol', 'use', 'title', 'desc',
    ];

    private const TEXT_ELEMENTS = ['text', 'tspan', 'title', 'desc'];

    private const DRAWABLE_ELEMENTS = ['path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon', 'text', 'use'];

    /** Elements whose href may point to another element of the same document. */
    private const HREF_ELEMENTS = ['use', 'linearGradient', 'radialGradient', 'pattern'];

    /** Lower-case local names, used only to label what was removed. */
    private const SCRIPT_ELEMENTS = ['script', 'handler', 'listener'];
    private const EMBEDDED_ELEMENTS = [
        'foreignobject', 'image', 'iframe', 'embed', 'object', 'audio', 'video', 'canvas',
        'feimage', 'html', 'body', 'img', 'frame', 'frameset', 'link', 'meta', 'base', 'form',
    ];
    private const ANIMATION_ELEMENTS = ['animate', 'set', 'animatetransform', 'animatemotion', 'animatecolor', 'discard', 'mpath'];

    private const ATTRIBUTES = [
        // geometry
        'x', 'y', 'x1', 'y1', 'x2', 'y2', 'cx', 'cy', 'r', 'rx', 'ry', 'fx', 'fy', 'fr',
        'width', 'height', 'd', 'points', 'pathLength',
        // viewport
        'viewBox', 'preserveAspectRatio', 'version',
        // text
        'dx', 'dy', 'rotate', 'textLength', 'lengthAdjust', 'text-anchor', 'dominant-baseline',
        'alignment-baseline', 'baseline-shift', 'font-family', 'font-size', 'font-style',
        'font-variant', 'font-weight', 'letter-spacing', 'word-spacing', 'text-decoration', 'writing-mode',
        // paint
        'fill', 'fill-opacity', 'fill-rule', 'stroke', 'stroke-width', 'stroke-opacity',
        'stroke-linecap', 'stroke-linejoin', 'stroke-miterlimit', 'stroke-dasharray',
        'stroke-dashoffset', 'opacity', 'color', 'paint-order', 'vector-effect',
        'shape-rendering', 'text-rendering', 'color-interpolation',
        // structure
        'transform', 'clip-path', 'clip-rule', 'mask', 'display', 'visibility', 'overflow',
        // gradients, patterns, clipping and masking
        'offset', 'stop-color', 'stop-opacity', 'gradientUnits', 'gradientTransform', 'spreadMethod',
        'patternUnits', 'patternContentUnits', 'patternTransform', 'clipPathUnits', 'maskUnits', 'maskContentUnits',
        'id', 'class',
    ];

    /** Attributes that may hold a url(#fragment) paint or reference. */
    private const URL_ATTRIBUTES = ['fill', 'stroke', 'clip-path', 'mask'];

    /** CSS properties of a style attribute that become presentation attributes. */
    private const STYLE_PROPERTIES = [
        'fill', 'fill-opacity', 'fill-rule', 'stroke', 'stroke-width', 'stroke-opacity',
        'stroke-linecap', 'stroke-linejoin', 'stroke-miterlimit', 'stroke-dasharray',
        'stroke-dashoffset', 'opacity', 'color', 'display', 'visibility', 'overflow',
        'stop-color', 'stop-opacity', 'clip-path', 'clip-rule', 'mask',
        'font-family', 'font-size', 'font-style', 'font-variant', 'font-weight',
        'letter-spacing', 'word-spacing', 'text-anchor', 'dominant-baseline', 'text-decoration',
        'paint-order', 'vector-effect', 'shape-rendering', 'text-rendering',
    ];

    private const NUMBER = '[-+]?(?:\d+\.?\d*|\.\d+)(?:[eE][-+]?\d+)?';

    private const FRAGMENT = '[A-Za-z_][A-Za-z0-9_.:-]{0,127}';

    private ?DOMDocument $out = null;

    /** @var array<string,int> */
    private array $removed = [];

    private int $elements = 0;

    private int $drawable = 0;

    /**
     * @throws SvgSanitizerException when the input is not a safe, usable SVG
     */
    public function sanitize(string $raw): SvgSanitizeResult
    {
        $this->removed = [];
        $this->elements = 0;
        $this->drawable = 0;

        if (strlen($raw) > self::MAX_BYTES) {
            throw new SvgSanitizerException(self::REJECT_TOO_LARGE);
        }
        $xml = str_starts_with($raw, "\xEF\xBB\xBF") ? substr($raw, 3) : $raw;
        if (trim($xml) === '' || str_contains($xml, "\0") || preg_match('//u', $xml) !== 1) {
            throw new SvgSanitizerException(self::REJECT_INVALID_ENCODING);
        }
        if (preg_match('/[\x01-\x08\x0B\x0C\x0E-\x1F]/', $xml) === 1) {
            throw new SvgSanitizerException(self::REJECT_INVALID_XML);
        }
        // No DTD at all: this refuses external entities (XXE), internal
        // entity expansion (billion laughs) and parameter entities.
        if (preg_match('/<!\s*(?:DOCTYPE|ENTITY|ELEMENT|ATTLIST|NOTATION)/i', $xml) === 1) {
            throw new SvgSanitizerException(self::REJECT_DOCTYPE);
        }
        // Only the XML declaration itself, at the very start; xml-stylesheet
        // and every other processing instruction is refused.
        if (preg_match_all('/<\?/', $xml, $instructions, PREG_OFFSET_CAPTURE) > 0) {
            foreach ($instructions[0] as [, $offset]) {
                if ($offset !== 0 || preg_match('/^<\?xml\s[^<>?]*\?>/', $xml) !== 1) {
                    throw new SvgSanitizerException(self::REJECT_PROCESSING_INSTRUCTION);
                }
            }
            if (preg_match('/^<\?xml\s[^?]*?encoding\s*=\s*["\']([^"\']*)["\']/', $xml, $encoding) === 1
                && !in_array(strtolower($encoding[1]), ['utf-8', 'us-ascii'], true)) {
                throw new SvgSanitizerException(self::REJECT_INVALID_ENCODING);
            }
        }
        if (preg_match_all('/<[A-Za-z_]/', $xml) > self::MAX_ELEMENTS) {
            throw new SvgSanitizerException(self::REJECT_TOO_COMPLEX);
        }

        $source = $this->parse($xml);
        $root = $source->documentElement;
        if (!$root instanceof DOMElement || $root->localName !== 'svg'
            || !in_array($root->namespaceURI, [self::SVG_NS, null], true)) {
            throw new SvgSanitizerException(self::REJECT_NOT_SVG);
        }
        $nullNamespace = $root->namespaceURI === null;

        $this->out = new DOMDocument('1.0', 'UTF-8');
        $svg = $this->out->createElementNS(self::SVG_NS, 'svg');
        $this->out->appendChild($svg);

        $attributes = $this->attributes($root, 'svg') ?? [];
        [$attributes, $width, $height] = $this->viewport($attributes);
        foreach ($attributes as $name => $value) {
            $svg->setAttribute($name, $value);
        }
        $this->elements = 1;
        $this->copyChildren($root, $svg, 1, $nullNamespace, 'svg');
        $this->limitReferences($svg);

        if ($this->drawable === 0) {
            throw new SvgSanitizerException(self::REJECT_EMPTY);
        }

        $body = $this->out->saveXML($svg);
        $this->out = null;
        if (!is_string($body) || $body === '') {
            throw new SvgSanitizerException(self::REJECT_INVALID_XML);
        }
        $result = '<?xml version="1.0" encoding="UTF-8"?>'."\n".$body."\n";
        if (strlen($result) > self::MAX_BYTES) {
            throw new SvgSanitizerException(self::REJECT_TOO_LARGE);
        }

        $removed = [];
        foreach (self::REMOVED_CATEGORIES as $category) {
            if (($this->removed[$category] ?? 0) > 0) {
                $removed[$category] = $this->removed[$category];
            }
        }

        return new SvgSanitizeResult($result, $removed, $width, $height);
    }

    private function parse(string $xml): DOMDocument
    {
        $previous = libxml_use_internal_errors(true);
        try {
            $document = new DOMDocument();
            $document->resolveExternals = false;
            $document->substituteEntities = false;
            $document->validateOnParse = false;
            // LIBXML_NONET: no network. Deliberately without LIBXML_NOENT,
            // LIBXML_DTDLOAD, LIBXML_DTDATTR and LIBXML_PARSEHUGE. CDATA is
            // merged into plain text, which is only kept inside text
            // elements and escaped on output.
            $loaded = $document->loadXML($xml, LIBXML_NONET | LIBXML_NOCDATA);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        if ($loaded !== true) {
            throw new SvgSanitizerException(self::REJECT_INVALID_XML);
        }
        if ($document->doctype !== null) {
            throw new SvgSanitizerException(self::REJECT_DOCTYPE);
        }

        return $document;
    }

    private function copyChildren(DOMNode $from, DOMElement $to, int $depth, bool $nullNamespace, string $parentName): void
    {
        foreach (iterator_to_array($from->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $this->copyElement($child, $to, $depth + 1, $nullNamespace);
            } elseif ($child->nodeType === XML_TEXT_NODE || $child->nodeType === XML_CDATA_SECTION_NODE) {
                $text = (string) $child->nodeValue;
                if ($text !== '' && in_array($parentName, self::TEXT_ELEMENTS, true)) {
                    $to->appendChild($this->document()->createTextNode($text));
                }
            } elseif ($child->nodeType === XML_COMMENT_NODE) {
                $this->remove('comment');
            } elseif ($child->nodeType === XML_PI_NODE) {
                $this->remove('element');
            }
        }
    }

    private function copyElement(DOMElement $element, DOMElement $parent, int $depth, bool $nullNamespace): void
    {
        if ($depth > self::MAX_DEPTH) {
            throw new SvgSanitizerException(self::REJECT_TOO_DEEP);
        }
        if (++$this->elements > self::MAX_ELEMENTS) {
            throw new SvgSanitizerException(self::REJECT_TOO_COMPLEX);
        }
        $name = (string) $element->localName;
        $namespace = $element->namespaceURI;
        $inSvg = $namespace === self::SVG_NS || ($namespace === null && $nullNamespace);
        if (!$inSvg || !in_array($name, self::ELEMENTS, true)) {
            $this->remove($this->elementCategory($name));

            return;
        }
        $attributes = $this->attributes($element, $name);
        if ($attributes === null) {
            return;
        }
        // Children are created without their own namespace node; they are
        // serialised inside the root's default SVG namespace.
        $node = $this->document()->createElement($name);
        foreach ($attributes as $attribute => $value) {
            $node->setAttribute($attribute, $value);
        }
        $parent->appendChild($node);
        if (in_array($name, self::DRAWABLE_ELEMENTS, true)) {
            $this->drawable++;
        }
        $this->copyChildren($element, $node, $depth, $nullNamespace, $name);
    }

    /**
     * Allowlisted attributes of one element, sorted by name; null when the
     * whole element must go (a <use> pointing outside the document).
     *
     * @return array<string,string>|null
     */
    private function attributes(DOMElement $element, string $elementName): ?array
    {
        $clean = [];
        $fromStyle = [];
        foreach (iterator_to_array($element->attributes) as $attribute) {
            $namespace = $attribute->namespaceURI;
            $name = (string) $attribute->localName;
            $value = (string) $attribute->value;
            if (str_starts_with(strtolower($name), 'on')) {
                $this->remove('event_handler');
                continue;
            }
            if ($name === 'href' && ($namespace === null || $namespace === self::XLINK_NS)) {
                $target = trim($value);
                if (in_array($elementName, self::HREF_ELEMENTS, true)
                    && preg_match('/^#'.self::FRAGMENT.'$/D', $target) === 1) {
                    $clean['href'] = $target;
                    continue;
                }
                $this->remove('external_reference');
                if ($elementName === 'use') {
                    return null;
                }
                continue;
            }
            if ($namespace !== null) {
                $this->remove('attribute');
                continue;
            }
            if ($name === 'style') {
                $fromStyle = array_replace($fromStyle, $this->styleDeclarations($value));
                continue;
            }
            $sanitized = $this->attributeValue($name, $value);
            if ($sanitized !== null) {
                $clean[$name] = $sanitized;
            }
        }
        // A style declaration overrides the presentation attribute, as in CSS.
        $clean = array_replace($clean, $fromStyle);
        ksort($clean, SORT_STRING);

        return $clean;
    }

    private function attributeValue(string $name, string $value): ?string
    {
        if (!in_array($name, self::ATTRIBUTES, true)) {
            $this->remove('attribute');

            return null;
        }
        $value = trim($value);
        $limit = in_array($name, ['d', 'points'], true) ? self::MAX_PATH_DATA_LENGTH : self::MAX_ATTRIBUTE_LENGTH;
        if (strlen($value) > $limit) {
            $this->remove('attribute');

            return null;
        }
        $flat = strtolower((string) preg_replace('/[\x00-\x20]+/', '', $value));
        if (str_contains($flat, 'javascript:') || str_contains($flat, 'vbscript:')) {
            $this->remove('script');

            return null;
        }
        if (str_contains($flat, 'data:') || str_contains($flat, '//')) {
            $this->remove('external_reference');

            return null;
        }
        if (str_contains($flat, 'expression(') || str_contains($flat, '@import') || str_contains($value, '\\')) {
            $this->remove('style');

            return null;
        }
        if (str_contains($flat, 'url(')) {
            $fragment = in_array($name, self::URL_ATTRIBUTES, true) ? $this->fragmentUrl($value) : null;
            if ($fragment === null) {
                $this->remove('external_reference');
            }

            return $fragment;
        }
        if ($name === 'id' && preg_match('/^'.self::FRAGMENT.'$/D', $value) !== 1) {
            $this->remove('attribute');

            return null;
        }
        if ($name === 'class' && preg_match('/^[A-Za-z0-9_\- ]{1,256}$/D', $value) !== 1) {
            $this->remove('attribute');

            return null;
        }

        return $value;
    }

    /** "url(#id)" with an optional plain fallback colour, or null. */
    private function fragmentUrl(string $value): ?string
    {
        if (preg_match('/^url\(\s*([\'"]?)#('.self::FRAGMENT.')\1\s*\)(?:\s+([A-Za-z]{1,20}|#[0-9A-Fa-f]{3,8}))?$/D', trim($value), $m) !== 1) {
            return null;
        }

        return 'url(#'.$m[2].')'.(($m[3] ?? '') !== '' ? ' '.$m[3] : '');
    }

    /**
     * Allowlisted declarations of a style attribute, as presentation
     * attributes. Anything that is not a plain allowlisted property with a
     * plain value (comments, escapes, at-rules, braces, !important, external
     * url()) is dropped.
     *
     * @return array<string,string>
     */
    private function styleDeclarations(string $css): array
    {
        if (strlen($css) > self::MAX_ATTRIBUTE_LENGTH || str_contains($css, '/*')
            || preg_match('/[\\\\@{}<>]/', $css) === 1) {
            $this->remove('style');

            return [];
        }
        $result = [];
        foreach (explode(';', $css) as $declaration) {
            if (trim($declaration) === '') {
                continue;
            }
            [$property, $value] = array_pad(explode(':', $declaration, 2), 2, '');
            $property = strtolower(trim($property));
            $value = trim($value);
            $plain = preg_match('/^[#A-Za-z0-9 .,%()\'"+\-_\/]{1,256}$/D', $value) === 1
                // url() goes to the strict #fragment check (and is counted
                // as an external reference when it points anywhere else).
                || str_contains(strtolower($value), 'url(');
            if ($value === '' || !in_array($property, self::STYLE_PROPERTIES, true) || !$plain) {
                $this->remove('style');
                continue;
            }
            $sanitized = $this->attributeValue($property, $value);
            if ($sanitized !== null) {
                $result[$property] = $sanitized;
            }
        }

        return $result;
    }

    /**
     * Requires a usable viewBox: a valid one is kept (normalised), otherwise
     * one is derived from numeric width and height.
     *
     * @param array<string,string> $attributes
     * @return array{0:array<string,string>,1:float,2:float}
     */
    private function viewport(array $attributes): array
    {
        $number = self::NUMBER;
        $length = static function (?string $value) use ($number): ?string {
            return $value !== null && preg_match('/^\s*('.$number.')\s*(?:px)?\s*$/D', $value, $m) === 1 && (float) $m[1] > 0
                ? $m[1] : null;
        };
        $width = $length($attributes['width'] ?? null);
        $height = $length($attributes['height'] ?? null);
        unset($attributes['width'], $attributes['height']);
        if ($width !== null && $height !== null) {
            $attributes['width'] = $width;
            $attributes['height'] = $height;
        }

        $viewBox = null;
        if (isset($attributes['viewBox'])
            && preg_match('/^\s*('.$number.')[\s,]+('.$number.')[\s,]+('.$number.')[\s,]+('.$number.')\s*$/D', $attributes['viewBox'], $m) === 1
            && (float) $m[3] > 0 && (float) $m[4] > 0) {
            $viewBox = [$m[1], $m[2], $m[3], $m[4]];
        } elseif ($width !== null && $height !== null) {
            $viewBox = ['0', '0', $width, $height];
        }
        if ($viewBox === null || !is_finite((float) $viewBox[2]) || !is_finite((float) $viewBox[3])) {
            throw new SvgSanitizerException(self::REJECT_NO_DIMENSIONS);
        }
        $attributes['viewBox'] = implode(' ', $viewBox);
        ksort($attributes, SORT_STRING);

        return [$attributes, (float) $viewBox[2], (float) $viewBox[3]];
    }

    /**
     * Reference amplification limits on the cleaned tree. At most MAX_USE
     * <use> elements; a <use> whose target is or contains a <use> or a
     * <pattern> is removed (no use-of-use chains or cycles); inside a
     * pattern, <use> elements and url() references to another pattern are
     * removed (no pattern-in-pattern tiling). Every decision is taken on the
     * tree before any removal and only shrinks it, so the result is stable
     * under a second sanitize().
     */
    private function limitReferences(DOMElement $root): void
    {
        $elements = [$root];
        foreach ($root->getElementsByTagName('*') as $element) {
            $elements[] = $element;
        }
        $byId = [];
        $uses = [];
        foreach ($elements as $element) {
            if ($element->hasAttribute('id')) {
                $byId[$element->getAttribute('id')][] = $element;
            }
            if ($element->localName === 'use') {
                $uses[] = $element;
            }
        }
        if (count($uses) > self::MAX_USE) {
            throw new SvgSanitizerException(self::REJECT_TOO_COMPLEX);
        }

        $contains = static fn (DOMElement $element, string $name): bool => $element->localName === $name
            || $element->getElementsByTagName($name)->length > 0;
        $inPattern = static function (DOMElement $element): bool {
            for ($parent = $element->parentNode; $parent instanceof DOMElement; $parent = $parent->parentNode) {
                if ($parent->localName === 'pattern') {
                    return true;
                }
            }

            return false;
        };
        $targetsPattern = static function (string $value) use ($byId): bool {
            if (preg_match('/^(?:url\(#('.self::FRAGMENT.')\)|#('.self::FRAGMENT.'))/', $value, $m) !== 1) {
                return false;
            }
            foreach ($byId[$m[1] !== '' ? $m[1] : $m[2]] ?? [] as $target) {
                if ($target->localName === 'pattern') {
                    return true;
                }
            }

            return false;
        };

        $dropElements = [];
        $dropAttributes = [];
        $expanded = count($elements);
        foreach ($uses as $use) {
            if ($inPattern($use)) {
                $dropElements[] = $use;
                continue;
            }
            $bad = false;
            $size = 0;
            foreach ($byId[substr($use->getAttribute('href'), 1)] ?? [] as $target) {
                if ($contains($target, 'use') || $contains($target, 'pattern')) {
                    $bad = true;
                }
                $size = max($size, 1 + $target->getElementsByTagName('*')->length);
            }
            if ($bad) {
                $dropElements[] = $use;
            } else {
                $expanded += $size;
            }
        }
        foreach ($elements as $element) {
            $isPattern = $element->localName === 'pattern';
            if (!$isPattern && !$inPattern($element)) {
                continue;
            }
            // A pattern's own href (attribute inheritance, as Inkscape
            // writes it) is kept; only paint references are tiling.
            $names = $isPattern ? self::URL_ATTRIBUTES : [...self::URL_ATTRIBUTES, 'href'];
            foreach ($names as $name) {
                if ($element->hasAttribute($name) && $targetsPattern($element->getAttribute($name))) {
                    $dropAttributes[] = [$element, $name];
                }
            }
        }
        if ($expanded > self::MAX_EXPANDED_ELEMENTS) {
            throw new SvgSanitizerException(self::REJECT_TOO_COMPLEX);
        }

        foreach ($dropAttributes as [$element, $name]) {
            $element->removeAttribute($name);
            $this->remove('reference');
        }
        foreach ($dropElements as $element) {
            if ($element->parentNode !== null) {
                $element->parentNode->removeChild($element);
                $this->remove('reference');
            }
        }

        $this->drawable = 0;
        foreach (array_merge([$root], iterator_to_array($root->getElementsByTagName('*'))) as $element) {
            if (in_array($element->localName, self::DRAWABLE_ELEMENTS, true)) {
                $this->drawable++;
            }
        }
    }

    private function elementCategory(string $name): string
    {
        $name = strtolower($name);

        return match (true) {
            in_array($name, self::SCRIPT_ELEMENTS, true) => 'script',
            in_array($name, self::EMBEDDED_ELEMENTS, true) => 'embedded_content',
            in_array($name, self::ANIMATION_ELEMENTS, true) => 'animation',
            $name === 'style' => 'style',
            default => 'element',
        };
    }

    private function remove(string $category): void
    {
        $this->removed[$category] = ($this->removed[$category] ?? 0) + 1;
    }

    private function document(): DOMDocument
    {
        return $this->out ?? throw new SvgSanitizerException(self::REJECT_INVALID_XML);
    }
}
