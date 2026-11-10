<?php

declare(strict_types=1);

namespace App\Services;

use finfo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Site logo and favicon files (Podešavanja > Izgled sajta), raster or SVG.
 *
 * Upload: the real content decides, not the name or the browser's MIME.
 * A PNG/JPEG/WebP/ICO must carry its own signature and match its extension;
 * an SVG must pass SvgSanitizer and only the sanitized bytes are stored
 * (never the original), under site-assets/<40 random chars>.svg.
 *
 * Serving: every site asset whose stored name matches the strict pattern
 * (site-assets/<random>.<png|jpg|jpeg|webp|ico|svg>, plus the raster login
 * backgrounds in site-assets/login/ and site-assets/login/slides/) is linked
 * through the site-assets.show route, never through /storage: on the
 * production host the /storage symlink chain no longer resolves at the origin.
 * Raster files are streamed after their signature is checked against the
 * extension. An SVG is re-sanitized on every cache miss (keyed by mtime and
 * size, so a file changed on disk is cleaned again before it is served) and
 * gets image/svg+xml, nosniff and a sandboxed CSP that also applies when the
 * file is opened directly. Only URL generation changes; stored paths stay.
 *
 * Places that cannot render SVG get a defined fallback: PDF documents use the
 * dedicated raster PDF logo or the built-in mark (FlowPdfLayout only embeds
 * JPEG/PNG), and emailLogoUrl() returns a raster logo or null (text brand).
 *
 * @author Ald1n <https://ald1n.com>
 */
final class SiteLogoAssetService
{
    public const ROUTE = 'site-assets.show';

    /**
     * Route parameter pattern, relative to site-assets/: a random stored name
     * (Laravel's hashName or this service's SVG name), optionally in the
     * login background directories. Nothing else is reachable.
     */
    public const FILE_PATTERN = '(?:login/(?:slides/)?)?[A-Za-z0-9_-]{20,64}\.(?:png|jpe?g|webp|ico|svg)';

    /** Largest raster file the route streams (login backgrounds allow 8 MB). */
    public const MAX_RASTER_BYTES = 8 * 1024 * 1024;

    public const KIND_LOGO = 'logo';

    public const KIND_FAVICON = 'favicon';

    private const EXTENSIONS = [
        self::KIND_LOGO => ['png', 'jpg', 'jpeg', 'webp', 'svg'],
        self::KIND_FAVICON => ['png', 'ico', 'svg'],
    ];

    private const CONTENT_TYPES = [
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'ico' => 'image/x-icon',
        'svg' => 'image/svg+xml',
    ];

    /** Setting keys whose SVG files the route may serve. */
    private const SERVABLE_KEYS = ['site_logo_light_path', 'site_logo_dark_path', 'site_favicon_path', 'site_logo_path'];

    private const CACHE_SECONDS = 604800;

    /** Short reason code => Serbian message (shown after the field label). */
    public const MESSAGES = [
        SvgSanitizer::REJECT_TOO_LARGE => 'SVG je prevelik (najviše 200 KB).',
        SvgSanitizer::REJECT_TOO_COMPLEX => 'SVG je previše složen (najviše 5.000 elemenata).',
        SvgSanitizer::REJECT_TOO_DEEP => 'SVG ima previše ugnežđenih nivoa (najviše 32).',
        SvgSanitizer::REJECT_INVALID_ENCODING => 'SVG nije ispravno kodiran (dozvoljen je samo UTF-8).',
        SvgSanitizer::REJECT_INVALID_XML => 'nije ispravan SVG (XML sadržaj je oštećen).',
        SvgSanitizer::REJECT_NOT_SVG => 'nije ispravan SVG (osnovni element mora biti svg).',
        SvgSanitizer::REJECT_DOCTYPE => 'SVG sadrži DOCTYPE ili ENTITY deklaraciju, što nije dozvoljeno.',
        SvgSanitizer::REJECT_PROCESSING_INSTRUCTION => 'SVG sadrži XML instrukciju obrade (npr. xml-stylesheet), što nije dozvoljeno.',
        SvgSanitizer::REJECT_NO_DIMENSIONS => 'SVG nema dimenzije: dodajte viewBox ili width i height.',
        SvgSanitizer::REJECT_EMPTY => 'SVG nema vidljiv sadržaj posle čišćenja.',
        'extension_mismatch' => 'sadržaj datoteke ne odgovara ekstenziji (npr. PNG preimenovan u .svg).',
        'unsupported_type' => 'format nije podržan.',
        'invalid_image' => 'slika nije ispravna ili je oštećena.',
        'unreadable' => 'datoteku nije moguće pročitati.',
    ];

    /** Removal category => Serbian label for the "SVG je očišćen" note. */
    public const REMOVED_LABELS = [
        'script' => 'skripte',
        'event_handler' => 'obrađivači događaja (on…)',
        'external_reference' => 'spoljne veze',
        'embedded_content' => 'ugrađeni sadržaj (slike, HTML)',
        'animation' => 'animacije',
        'style' => 'CSS stilovi',
        'element' => 'ostali nedozvoljeni elementi',
        'attribute' => 'ostali nedozvoljeni atributi',
        'comment' => 'komentari',
    ];

    private const INPUT_LABELS = [
        'site_logo_light' => 'Logo za svetlu temu',
        'site_logo_dark' => 'Logo za tamnu temu',
        'site_favicon' => 'Favicon',
    ];

    /** @var array<string,array{sha256:string,removed:array<string,int>}> */
    private array $storedSvgs = [];

    public function __construct(private readonly SvgSanitizer $sanitizer)
    {
    }

    /** A stored SVG logo or favicon name (site-assets/ root only). */
    public static function isSvgPath(?string $path): bool
    {
        return preg_match('~^site-assets/[A-Za-z0-9_-]{20,64}\.svg$~D', trim((string) $path)) === 1;
    }

    /** Whether the site-assets.show route can serve this stored path. */
    public static function isRoutable(?string $path): bool
    {
        $path = trim((string) $path);
        if (preg_match('~^site-assets/'.self::FILE_PATTERN.'$~D', $path) !== 1) {
            return false;
        }

        // SVG is accepted for logos and the favicon only, never in login/.
        return !str_ends_with(strtolower($path), '.svg') || self::isSvgPath($path);
    }

    public static function inputLabel(string $input): string
    {
        return self::INPUT_LABELS[$input] ?? 'Datoteka';
    }

    public function message(string $code, string $kind = self::KIND_LOGO): string
    {
        if ($code === 'unsupported_type') {
            return 'format nije podržan. Dozvoljeni su '.($kind === self::KIND_FAVICON ? 'PNG, ICO ili SVG.' : 'PNG, JPG, WEBP ili SVG.');
        }

        return self::MESSAGES[$code] ?? self::MESSAGES['invalid_image'];
    }

    /**
     * Checks an uploaded logo or favicon by its content. Returns null when it
     * is acceptable, otherwise a short reason code (see MESSAGES).
     */
    public function inspect(UploadedFile $file, string $kind): ?string
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        if (!in_array($extension, self::EXTENSIONS[$kind] ?? [], true)) {
            return 'unsupported_type';
        }
        $bytes = $this->read($file, $extension === 'svg' ? SvgSanitizer::MAX_BYTES : 8 * 1024 * 1024);
        if ($bytes === null) {
            return $extension === 'svg' ? SvgSanitizer::REJECT_TOO_LARGE : 'unreadable';
        }
        $signature = self::rasterSignature($bytes);

        if ($extension === 'svg') {
            if ($signature !== null || !self::looksLikeMarkup($bytes)) {
                return 'extension_mismatch';
            }
            try {
                $this->sanitizer->sanitize($bytes);
            } catch (SvgSanitizerException $exception) {
                return $exception->reason;
            }

            return null;
        }

        // An SVG (or any markup) under a raster name is refused, as is a
        // raster whose signature belongs to another format.
        if (self::looksLikeMarkup($bytes)) {
            return 'extension_mismatch';
        }
        $expected = ['png' => 'png', 'jpg' => 'jpeg', 'jpeg' => 'jpeg', 'webp' => 'webp', 'ico' => 'ico'][$extension];
        if ($signature !== $expected) {
            return $signature === null ? 'invalid_image' : 'extension_mismatch';
        }
        if ($expected === 'ico') {
            // ICO header: reserved 0, type 1, at least one image entry.
            return strlen($bytes) >= 22 && unpack('v', substr($bytes, 4, 2))[1] >= 1 ? null : 'invalid_image';
        }
        $mime = '';
        try {
            $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        } catch (Throwable) {
            $mime = '';
        }
        if ($mime !== 'image/'.$expected) {
            return 'invalid_image';
        }
        $size = @getimagesizefromstring($bytes);
        if (!is_array($size) || ($size[0] ?? 0) < 1 || ($size[1] ?? 0) < 1) {
            return 'invalid_image';
        }

        return null;
    }

    /**
     * Stores an already validated upload on the public disk and returns its
     * path. SVG: the sanitized bytes only, under a random name.
     */
    public function store(UploadedFile $file, string $input): string
    {
        if (strtolower((string) $file->getClientOriginalExtension()) !== 'svg') {
            return (string) $file->storePublicly('site-assets', 'public');
        }
        $bytes = $this->read($file, SvgSanitizer::MAX_BYTES);
        $clean = $this->sanitizer->sanitize((string) $bytes);
        $path = 'site-assets/'.Str::random(40).'.svg';
        if (!Storage::disk('public')->put($path, $clean->svg, ['visibility' => 'public'])) {
            throw new \RuntimeException('SVG logo could not be stored.');
        }
        $this->storedSvgs[$input] = ['sha256' => $clean->sha256(), 'removed' => $clean->removed];

        return $path;
    }

    /**
     * Call after the settings were saved. Flashes the Serbian "SVG je
     * očišćen" note (counts only) and returns the audit metadata for the
     * settings entry: sha256 of the stored file and removed counts, never
     * content. Null when no SVG was stored in this request.
     *
     * @return array{svg_uploads:array<string,array{sha256:string,removed:array<string,int>}>}|null
     */
    public function finishUpload(): ?array
    {
        if ($this->storedSvgs === []) {
            return null;
        }
        $notes = [];
        foreach ($this->storedSvgs as $input => $report) {
            if ($report['removed'] === []) {
                continue;
            }
            $parts = [];
            foreach ($report['removed'] as $category => $count) {
                $parts[] = (self::REMOVED_LABELS[$category] ?? $category).': '.$count;
            }
            $notes[] = self::inputLabel($input).' (SVG) je očišćen pre čuvanja. Uklonjeno – '.implode(', ', $parts).'.';
        }
        try {
            if ($notes !== [] && request()->hasSession()) {
                request()->session()->flash('site_logo_svg_notice', $notes);
            }
        } catch (Throwable) {
            // The note is informational; saving already succeeded.
        }
        $metadata = ['svg_uploads' => $this->storedSvgs];
        $this->storedSvgs = [];

        return $metadata;
    }

    /**
     * site-assets.show URL for a stored site asset, or null when the path is
     * not routable. With $requireFile the file must exist (null otherwise);
     * the ?v= version is the file's mtime, so a file rewritten in place gets
     * a new URL despite the immutable cache.
     */
    public function assetUrl(string $path, bool $absolute = true, bool $requireFile = true): ?string
    {
        $path = trim($path);
        if (!self::isRoutable($path)) {
            return null;
        }
        $parameters = ['file' => substr($path, strlen('site-assets/'))];
        try {
            $disk = Storage::disk('public');
            if ($disk->exists($path)) {
                $parameters['v'] = dechex((int) $disk->lastModified($path));
            } elseif ($requireFile) {
                return null;
            }

            return route(self::ROUTE, $parameters, $absolute);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Bytes and content type of a stored raster asset the route may stream,
     * or null. The content must carry the signature of its extension (and,
     * for PNG/JPEG/WebP, the matching finfo type), so a renamed or replaced
     * file on disk is not served under a wrong type.
     *
     * @return array{bytes:string,type:string,mtime:int}|null
     */
    public function rasterServable(string $path): ?array
    {
        $path = trim($path);
        if (!self::isRoutable($path) || self::isSvgPath($path)) {
            return null;
        }
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $expected = ['png' => 'png', 'jpg' => 'jpeg', 'jpeg' => 'jpeg', 'webp' => 'webp', 'ico' => 'ico'][$extension] ?? null;
        if ($expected === null) {
            return null;
        }
        try {
            $disk = Storage::disk('public');
            if (!$disk->exists($path)) {
                return null;
            }
            $size = (int) $disk->size($path);
            if ($size < 1 || $size > self::MAX_RASTER_BYTES) {
                return null;
            }
            $bytes = (string) $disk->get($path);
            if (self::rasterSignature($bytes) !== $expected) {
                return null;
            }
            if ($expected !== 'ico') {
                $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
                if ($mime !== 'image/'.$expected) {
                    return null;
                }
            }

            return ['bytes' => $bytes, 'type' => self::CONTENT_TYPES[$extension], 'mtime' => (int) $disk->lastModified($path)];
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Sanitized bytes to serve for a stored SVG logo, or null. The stored
     * file is cleaned again (cached per mtime and size); a file that no
     * longer passes is not served at all.
     */
    public function servable(string $path): ?string
    {
        if (!self::isSvgPath($path)) {
            return null;
        }
        try {
            $disk = Storage::disk('public');
            if (!$disk->exists($path)) {
                return null;
            }
            $size = (int) $disk->size($path);
            if ($size < 1 || $size > SvgSanitizer::MAX_BYTES) {
                return null;
            }
            $key = 'site-logo-svg:'.hash('sha256', $path.'|'.$disk->lastModified($path).'|'.$size);
            $clean = $this->remember($key, function () use ($disk, $path): string {
                $raw = (string) $disk->get($path);
                try {
                    $result = $this->sanitizer->sanitize($raw);
                } catch (SvgSanitizerException $exception) {
                    Log::warning('Stored SVG logo no longer passes the sanitizer and is not served.', ['reason' => $exception->reason]);

                    return '';
                }
                if ($result->svg !== $raw) {
                    Log::warning('Stored SVG logo differed from its sanitized form; the sanitized form is served.', [
                        'sha256' => hash('sha256', $raw),
                    ]);
                }

                return $result->svg;
            });

            return $clean !== '' ? $clean : null;
        } catch (Throwable) {
            return null;
        }
    }

    /** Whether a stored file name may be served: configured as a site logo or favicon right now. */
    public function isConfigured(string $path, SettingsService $settings): bool
    {
        try {
            $values = $settings->all();
        } catch (Throwable) {
            return false;
        }
        foreach (self::SERVABLE_KEYS as $key) {
            if (trim((string) ($values[$key] ?? '')) === $path) {
                return true;
            }
        }

        return false;
    }

    /**
     * viewBox width and height of a stored SVG logo (for the img width
     * attribute), or false.
     *
     * @return array{0:float,1:float}|false
     */
    public function svgDimensions(string $path): array|false
    {
        $svg = $this->servable(trim($path));
        if ($svg === null || preg_match('~<svg\b[^>]*\sviewBox="[^"\s]+ [^"\s]+ ([^"\s]+) ([^"\s]+)"~', $svg, $m) !== 1) {
            return false;
        }
        $width = (float) $m[1];
        $height = (float) $m[2];

        return $width > 0 && $height > 0 ? [$width, $height] : false;
    }

    /** Content type hint for a stored asset path (for the mobile API), or null. */
    public static function contentType(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }
        $extension = strtolower(pathinfo((string) parse_url($path, PHP_URL_PATH), PATHINFO_EXTENSION));

        return self::CONTENT_TYPES[$extension] ?? null;
    }

    /**
     * Logo URL for e-mail templates. E-mail clients do not render SVG, so
     * only a raster logo qualifies: the light-theme site logo, then the PDF
     * logo (always a re-encoded JPEG). Null means "use the text brand".
     *
     * @param array<string,string> $settings
     */
    public function emailLogoUrl(array $settings): ?string
    {
        foreach (['site_logo_light_path', 'documents_logo_path'] as $key) {
            $path = trim((string) ($settings[$key] ?? ''));
            $type = self::contentType($path);
            if ($path === '' || $type === null || $type === 'image/svg+xml' || $type === 'image/x-icon') {
                continue;
            }
            if (!str_starts_with($path, 'site-assets/') && !str_starts_with($path, 'document-assets/')) {
                continue;
            }
            try {
                if (Storage::disk('public')->exists($path)) {
                    return $this->assetUrl($path) ?? Storage::disk('public')->url($path);
                }
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }

    private function remember(string $key, \Closure $callback): string
    {
        try {
            return (string) Cache::remember($key, self::CACHE_SECONDS, $callback);
        } catch (Throwable) {
            return (string) $callback();
        }
    }

    private function read(UploadedFile $file, int $limit): ?string
    {
        $real = $file->getRealPath();
        if (!is_string($real) || $real === '' || !is_file($real)) {
            return null;
        }
        $size = @filesize($real);
        if (!is_int($size) || $size < 1 || $size > $limit) {
            return null;
        }
        $bytes = @file_get_contents($real);

        return is_string($bytes) && $bytes !== '' ? $bytes : null;
    }

    private static function rasterSignature(string $bytes): ?string
    {
        return match (true) {
            str_starts_with($bytes, "\x89PNG\r\n\x1a\n") => 'png',
            str_starts_with($bytes, "\xFF\xD8\xFF") => 'jpeg',
            strlen($bytes) >= 12 && str_starts_with($bytes, 'RIFF') && substr($bytes, 8, 4) === 'WEBP' => 'webp',
            str_starts_with($bytes, "\x00\x00\x01\x00") => 'ico',
            str_starts_with($bytes, 'GIF87a') || str_starts_with($bytes, 'GIF89a') => 'gif',
            default => null,
        };
    }

    /** Text that starts like XML/HTML/SVG markup (after an optional BOM and whitespace). */
    private static function looksLikeMarkup(string $bytes): bool
    {
        $head = substr($bytes, 0, 4096);
        if (str_starts_with($head, "\xEF\xBB\xBF")) {
            $head = substr($head, 3);
        }
        $head = ltrim($head);

        return str_starts_with($head, '<') || stripos($head, '<svg') !== false;
    }
}
