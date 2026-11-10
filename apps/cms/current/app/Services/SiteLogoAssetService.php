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
 * Site logo, favicon, login background and PDF logo files: upload checks and
 * the URLs and bytes of the site-assets.show route.
 *
 * Upload: the real content decides, not the name or the browser's MIME.
 * A file with a known image extension must carry that format's signature (a
 * PNG renamed to .svg and an SVG renamed to .png are refused). A file with no
 * or an unknown extension (the Android app may send a content:// name such as
 * "image:1000") is typed by its content: PNG/JPEG/WebP/ICO magic bytes, or
 * markup that passes SvgSanitizer. Only the sanitized bytes of an SVG are
 * stored, and every logo is stored under a random name with the extension of
 * its real type.
 *
 * Serving: every stored name that matches the strict pattern (site-assets/,
 * site-assets/login/, site-assets/login/slides/ and the PDF logos in
 * document-assets/) is linked through the site-assets.show route, never
 * through /storage: on the production host the /storage symlink chain no
 * longer resolves at the origin. URLs carry ?v=<version>; the version is the
 * file mtime (plus the sanitizer version for SVG), so the route can refuse
 * any other value without reading the file. Raster files are streamed after a
 * signature check of the first bytes. An SVG is re-sanitized on every cache
 * miss (keyed by mtime, size and sanitizer version) and served with
 * image/svg+xml, nosniff and a sandboxed CSP. Only URL generation changes;
 * stored paths stay as they are.
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
     * Route parameter pattern: a random stored name (Laravel's hashName or
     * this service's names), optionally in the login background
     * directories, or "documents/" + exactly the name
     * DocumentSettingsController::storePdfLogo writes (pdf-logo-<uuid>.jpg).
     */
    public const FILE_PATTERN = '(?:(?:login/(?:slides/)?)?[A-Za-z0-9_-]{20,64}\.(?:png|jpe?g|webp|ico|svg)|'.self::PDF_LOGO_PATTERN.')';

    /** documents/ route parameter of a stored PDF logo. */
    private const PDF_LOGO_PATTERN = 'documents/pdf-logo-[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.jpg';

    /** Largest raster file the route streams (login backgrounds allow 8 MB). */
    public const MAX_RASTER_BYTES = 8 * 1024 * 1024;

    /** Bytes read for the signature and finfo check of a served raster file. */
    private const SNIFF_BYTES = 4096;

    public const KIND_LOGO = 'logo';

    public const KIND_FAVICON = 'favicon';

    private const EXTENSIONS = [
        self::KIND_LOGO => ['png', 'jpg', 'jpeg', 'webp', 'svg'],
        self::KIND_FAVICON => ['png', 'ico', 'svg'],
    ];

    /**
     * Image extensions the client name is trusted to claim (and checked
     * against). Any other extension, or none, means "type it by content".
     */
    private const KNOWN_IMAGE_EXTENSIONS = [
        'png', 'jpg', 'jpeg', 'jpe', 'webp', 'ico', 'svg', 'svgz', 'gif', 'bmp', 'tif', 'tiff', 'avif', 'heic', 'heif',
    ];

    private const CONTENT_TYPES = [
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'ico' => 'image/x-icon',
        'svg' => 'image/svg+xml',
    ];

    /** Signature name => stored extension. */
    private const SIGNATURE_EXTENSIONS = ['png' => 'png', 'jpeg' => 'jpg', 'webp' => 'webp', 'ico' => 'ico'];

    /** Setting keys whose SVG files the route may serve. */
    private const SERVABLE_KEYS = ['site_logo_light_path', 'site_logo_dark_path', 'site_favicon_path', 'site_logo_path'];

    private const CACHE_SECONDS = 604800;

    /** Short reason code => Serbian message (shown after the field label). */
    public const MESSAGES = [
        SvgSanitizer::REJECT_TOO_LARGE => 'SVG je prevelik (najviše 200 KB).',
        SvgSanitizer::REJECT_TOO_COMPLEX => 'SVG je previše složen (najviše 5.000 elemenata; use, mask, clipPath i pattern reference su ograničene).',
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
        'reference' => 'ugnežđene use/pattern reference',
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

    /** Route parameter for a stored path, or null when the route cannot serve it. */
    public static function routeFile(?string $path): ?string
    {
        $path = trim((string) $path);
        $file = match (true) {
            str_starts_with($path, 'site-assets/') => substr($path, strlen('site-assets/')),
            str_starts_with($path, 'document-assets/') => 'documents/'.substr($path, strlen('document-assets/')),
            default => null,
        };
        if ($file === null || preg_match('~^'.self::FILE_PATTERN.'$~D', $file) !== 1) {
            return null;
        }
        // document-assets/ holds other files too: only the PDF logo name.
        if (str_starts_with($file, 'documents/') && preg_match('~^'.self::PDF_LOGO_PATTERN.'$~D', $file) !== 1) {
            return null;
        }
        // SVG is accepted for logos and the favicon only (site-assets/ root).
        if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'svg' && !self::isSvgPath($path)) {
            return null;
        }

        return $file;
    }

    /** Stored path on the public disk for a route parameter, or null. */
    public static function storagePath(string $file): ?string
    {
        $path = str_starts_with($file, 'documents/')
            ? 'document-assets/'.substr($file, strlen('documents/'))
            : 'site-assets/'.$file;

        return self::routeFile($path) === $file ? $path : null;
    }

    /** Whether the site-assets.show route can serve this stored path. */
    public static function isRoutable(?string $path): bool
    {
        return self::routeFile($path) !== null;
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
        $resolved = $this->resolve($file, $kind);

        return is_array($resolved) ? null : $resolved;
    }

    /**
     * Stores an already validated upload on the public disk and returns its
     * path: site-assets/<40 random chars>.<real type>. SVG: the sanitized
     * bytes only.
     */
    public function store(UploadedFile $file, string $input): string
    {
        $kind = $input === 'site_favicon' ? self::KIND_FAVICON : self::KIND_LOGO;
        $resolved = $this->resolve($file, $kind);
        if (!is_array($resolved)) {
            throw new \RuntimeException('Site logo upload was not validated.');
        }
        [$extension, $bytes] = $resolved;
        $path = 'site-assets/'.Str::random(40).'.'.$extension;
        $content = $bytes;
        if ($extension === 'svg') {
            $clean = $this->sanitizer->sanitize($bytes);
            $content = $clean->svg;
            $this->storedSvgs[$input] = ['sha256' => $clean->sha256(), 'removed' => $clean->removed];
        }
        if (!Storage::disk('public')->put($path, $content, ['visibility' => 'public'])) {
            throw new \RuntimeException('Site logo could not be stored.');
        }

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
     * Version a URL of this stored file must carry: mtime and size in hex,
     * plus the sanitizer version for SVG. Null when the file is missing.
     */
    public function version(string $path): ?string
    {
        try {
            $disk = Storage::disk('public');
            if (!$disk->exists($path)) {
                return null;
            }
            return self::versionOf($path, (int) $disk->lastModified($path), (int) $disk->size($path));
        } catch (Throwable) {
            return null;
        }
    }

    /** "<mtime hex>-<size hex>", plus "-s<sanitizer version>" for SVG. */
    private static function versionOf(string $path, int $mtime, int $size): string
    {
        $version = dechex($mtime).'-'.dechex($size);

        return self::isSvgPath($path) ? $version.'-s'.SvgSanitizer::VERSION : $version;
    }

    /**
     * site-assets.show URL for a stored asset, or null when the path is not
     * routable. With $requireFile the file must exist; otherwise a missing
     * file still gets its (unversioned) URL, which answers 404.
     */
    public function assetUrl(string $path, bool $absolute = true, bool $requireFile = true): ?string
    {
        $path = trim($path);
        $file = self::routeFile($path);
        if ($file === null) {
            return null;
        }
        $parameters = ['file' => $file];
        $version = $this->version($path);
        if ($version !== null) {
            $parameters['v'] = $version;
        } elseif ($requireFile) {
            return null;
        }
        try {
            return route(self::ROUTE, $parameters, $absolute);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * What the route needs before reading any content: type, size, mtime,
     * version and ETag of a stored file. Raster files must also carry the
     * signature (and finfo type) of their extension in the first bytes; an
     * SVG must be configured as a site logo or favicon right now.
     *
     * @return array{path:string,type:string,size:int,mtime:int,version:string,etag:string,svg:bool}|null
     */
    public function describe(string $path, ?SettingsService $settings = null): ?array
    {
        $file = self::routeFile($path);
        if ($file === null) {
            return null;
        }
        $svg = self::isSvgPath($path);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        try {
            $disk = Storage::disk('public');
            if (!$disk->exists($path)) {
                return null;
            }
            $size = (int) $disk->size($path);
            $mtime = (int) $disk->lastModified($path);
            if ($size < 1 || $size > ($svg ? SvgSanitizer::MAX_BYTES : self::MAX_RASTER_BYTES)) {
                return null;
            }
            if ($svg) {
                if ($settings === null || !$this->isConfigured($path, $settings)) {
                    return null;
                }
            } elseif (!$this->rasterHeadMatches($disk->readStream($path), $extension)) {
                return null;
            }
        } catch (Throwable) {
            return null;
        }
        $version = self::versionOf($path, $mtime, $size);

        return [
            'path' => $path,
            'type' => self::CONTENT_TYPES[$extension],
            'size' => $size,
            'mtime' => $mtime,
            'version' => $version,
            'etag' => '"'.$version.'"',
            'svg' => $svg,
        ];
    }

    /** Local filesystem path of a stored file (for streaming), or null. */
    public function localPath(string $path): ?string
    {
        try {
            $local = Storage::disk('public')->path($path);

            return is_file($local) ? $local : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Sanitized bytes to serve for a stored SVG logo, or null. The stored
     * file is cleaned again (cached per mtime, size and sanitizer version); a
     * file that no longer passes is not served at all.
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
            $key = 'site-logo-svg:'.SvgSanitizer::VERSION.':'.hash('sha256', $path.'|'.$disk->lastModified($path).'|'.$size);
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

    /** Whether a stored SVG may be served: configured as a site logo or favicon right now. */
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

    /**
     * Whether a stored file is still used after a slot was replaced or
     * cleared: by another appearance or document setting, or by an issued
     * order document's logo snapshot. On any doubt (query failure) the
     * answer is yes, so the file is kept.
     *
     * @param array<string,mixed> $settings current (saved) settings
     */
    public static function stillReferenced(string $path, array $settings): bool
    {
        $path = trim($path);
        if ($path === '') {
            return false;
        }
        $keys = [
            'site_logo_light_path', 'site_logo_dark_path', 'site_favicon_path', 'site_logo_path', 'documents_logo_path',
            'login_background_image_path', 'login_background_fallback_path',
        ];
        for ($slot = 1; $slot <= 8; $slot++) {
            $keys[] = 'login_background_slide_'.$slot.'_path';
        }
        foreach ($keys as $key) {
            if (trim((string) ($settings[$key] ?? '')) === $path) {
                return true;
            }
        }
        try {
            return \App\Models\OrderDocument::query()->where('company_logo_path', $path)->exists();
        } catch (Throwable) {
            return true;
        }
    }

    /**
     * Stored asset settings whose value is a relative site-assets/ or
     * document-assets/ path the route cannot serve (a name outside the
     * strict pattern). Those keep their former /storage URL.
     *
     * @param array<string,mixed> $settings
     * @return array<string,string> setting key => stored path
     */
    public static function unroutableStoredPaths(array $settings): array
    {
        $found = [];
        foreach ($settings as $key => $value) {
            $value = trim((string) $value);
            if ((str_ends_with((string) $key, '_path'))
                && (str_starts_with($value, 'site-assets/') || str_starts_with($value, 'document-assets/'))
                && !self::isRoutable($value)) {
                $found[(string) $key] = $value;
            }
        }
        ksort($found);

        return $found;
    }

    /**
     * Extension of the upload's real type and its bytes, or a reason code.
     *
     * @return array{0:string,1:string}|string
     */
    private function resolve(UploadedFile $file, string $kind): array|string
    {
        $allowed = self::EXTENSIONS[$kind] ?? [];
        $claimed = strtolower((string) $file->getClientOriginalExtension());
        $trusted = in_array($claimed, self::KNOWN_IMAGE_EXTENSIONS, true);
        if ($trusted && !in_array($claimed, $allowed, true)) {
            return 'unsupported_type';
        }
        $limit = $claimed === 'svg' ? SvgSanitizer::MAX_BYTES : 8 * 1024 * 1024;
        $bytes = $this->read($file, $limit);
        if ($bytes === null) {
            return $claimed === 'svg' ? SvgSanitizer::REJECT_TOO_LARGE : 'unreadable';
        }
        $signature = self::rasterSignature($bytes);
        $markup = self::looksLikeMarkup($bytes);

        if (!$trusted) {
            // No usable extension: the content decides.
            if ($signature !== null) {
                $extension = self::SIGNATURE_EXTENSIONS[$signature] ?? null;
                if ($extension === null || !in_array($extension, $allowed, true)) {
                    return 'unsupported_type';
                }
                $problem = $this->rasterProblem($bytes, $signature);

                return $problem ?? [$extension, $bytes];
            }
            if ($markup && in_array('svg', $allowed, true)) {
                if (strlen($bytes) > SvgSanitizer::MAX_BYTES) {
                    return SvgSanitizer::REJECT_TOO_LARGE;
                }

                return $this->svgProblem($bytes) ?? ['svg', $bytes];
            }

            return 'unsupported_type';
        }

        if ($claimed === 'svg') {
            if ($signature !== null || !$markup) {
                return 'extension_mismatch';
            }

            return $this->svgProblem($bytes) ?? ['svg', $bytes];
        }

        // An SVG (or any markup) under a raster name is refused, as is a
        // raster whose signature belongs to another format.
        if ($markup) {
            return 'extension_mismatch';
        }
        $expected = ['png' => 'png', 'jpg' => 'jpeg', 'jpeg' => 'jpeg', 'webp' => 'webp', 'ico' => 'ico'][$claimed];
        if ($signature !== $expected) {
            return $signature === null ? 'invalid_image' : 'extension_mismatch';
        }
        $problem = $this->rasterProblem($bytes, $expected);

        return $problem ?? [self::SIGNATURE_EXTENSIONS[$expected], $bytes];
    }

    private function svgProblem(string $bytes): ?string
    {
        try {
            $this->sanitizer->sanitize($bytes);
        } catch (SvgSanitizerException $exception) {
            return $exception->reason;
        }

        return null;
    }

    private function rasterProblem(string $bytes, string $signature): ?string
    {
        if ($signature === 'ico') {
            // ICO header: reserved 0, type 1, at least one image entry.
            return strlen($bytes) >= 22 && unpack('v', substr($bytes, 4, 2))[1] >= 1 ? null : 'invalid_image';
        }
        try {
            $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        } catch (Throwable) {
            $mime = '';
        }
        if ($mime !== 'image/'.$signature) {
            return 'invalid_image';
        }
        $size = @getimagesizefromstring($bytes);
        if (!is_array($size) || ($size[0] ?? 0) < 1 || ($size[1] ?? 0) < 1) {
            return 'invalid_image';
        }

        return null;
    }

    /** @param resource|null $stream */
    private function rasterHeadMatches($stream, string $extension): bool
    {
        if (!is_resource($stream)) {
            return false;
        }
        try {
            $head = (string) fread($stream, self::SNIFF_BYTES);
        } finally {
            fclose($stream);
        }
        $expected = ['png' => 'png', 'jpg' => 'jpeg', 'jpeg' => 'jpeg', 'webp' => 'webp', 'ico' => 'ico'][$extension] ?? null;
        if ($expected === null || self::rasterSignature($head) !== $expected) {
            return false;
        }
        if ($expected === 'ico') {
            return strlen($head) >= 6 && unpack('v', substr($head, 4, 2))[1] >= 1;
        }
        try {
            return (string) (new finfo(FILEINFO_MIME_TYPE))->buffer($head) === 'image/'.$expected;
        } catch (Throwable) {
            return false;
        }
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
