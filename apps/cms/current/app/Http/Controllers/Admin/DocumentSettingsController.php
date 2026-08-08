<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderDocument;
use App\Services\AuditLogger;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

final class DocumentSettingsController extends Controller
{
    public function index(SettingsService $settings): View
    {
        $values = $settings->all();

        return view('admin.settings.documents', [
            'settings' => $values,
            'documentLogoUrl' => $this->publicUrl((string) ($values['documents_logo_path'] ?? '')),
        ]);
    }

    public function update(Request $request, SettingsService $settings, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'documents_company_name' => ['required', 'string', 'max:190'],
            'documents_company_address' => ['required', 'string', 'max:255'],
            'documents_company_city' => ['required', 'string', 'max:120'],
            'documents_company_tax_id' => ['nullable', 'string', 'max:40'],
            'documents_company_registration_number' => ['nullable', 'string', 'max:40'],
            'documents_company_phone' => ['nullable', 'string', 'max:60'],
            'documents_company_email' => ['nullable', 'email', 'max:190'],
            'documents_company_website' => ['nullable', 'url', 'max:190'],
            'documents_vat_enabled' => ['nullable', Rule::in(['0', '1'])],
            'documents_vat_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'documents_payment_due_days' => ['required', 'integer', 'min:0', 'max:365'],
            'documents_default_note' => ['nullable', 'string', 'max:2000'],
            'documents_footer_note' => ['nullable', 'string', 'max:500'],
            'documents_logo' => ['nullable', 'file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096', 'dimensions:max_width=5000,max_height=5000'],
        ]);

        $before = $settings->all();
        $values = [];
        foreach ($data as $key => $value) {
            if ($key === 'documents_logo') continue;
            $values[$key] = is_string($value) ? trim($value) : (string) $value;
        }
        $values['documents_vat_enabled'] = $request->boolean('documents_vat_enabled') ? '1' : '0';

        $newLogoPath = null;
        $oldLogoPath = (string) ($before['documents_logo_path'] ?? '');
        if ($request->hasFile('documents_logo')) {
            $file = $request->file('documents_logo');
            if ($file instanceof UploadedFile && $file->isValid()) {
                $newLogoPath = $this->storePdfLogo($file);
                $values['documents_logo_path'] = $newLogoPath;
            }
        }

        try {
            $settings->putMany($values, (int) $request->user()->getAuthIdentifier());
        } catch (Throwable $exception) {
            if ($newLogoPath !== null) $this->deleteManagedLogo($newLogoPath);
            throw $exception;
        }
        if ($newLogoPath !== null && $oldLogoPath !== $newLogoPath) {
            $this->deleteManagedLogoIfUnused($oldLogoPath);
        }

        $auditValues = $values;
        if (isset($auditValues['documents_logo_path'])) {
            $auditValues['documents_logo_path'] = '[PDF logo updated]';
        }
        $audit->log('settings.documents_updated', 'Ažurirana podešavanja poslovnih dokumenata.', null, $before, $auditValues, user: $request->user());

        return back()->with('status', 'Podešavanja PDF dokumenata su sačuvana.');
    }

    public function removeLogo(Request $request, SettingsService $settings, AuditLogger $audit): RedirectResponse
    {
        $oldPath = (string) $settings->get('documents_logo_path', '');
        $settings->putMany(['documents_logo_path' => ''], (int) $request->user()->getAuthIdentifier());
        $this->deleteManagedLogoIfUnused($oldPath);
        $audit->log(
            'settings.documents_logo_removed',
            'Uklonjen PDF logotip.',
            null,
            ['documents_logo_path' => $oldPath !== '' ? '[configured]' : ''],
            ['documents_logo_path' => ''],
            user: $request->user(),
        );

        return back()->with('status', 'PDF logo je uklonjen. Ako je podešen logo sajta, koristiće se kao rezervni.');
    }

    private function storePdfLogo(UploadedFile $file): string
    {
        $disk = Storage::disk('public');
        $disk->makeDirectory('document-assets');
        $path = 'document-assets/pdf-logo-'.Str::uuid().'.jpg';
        $destination = $disk->path($path);
        $source = $file->getRealPath();

        if (!is_string($source) || $source === '' || !is_file($source)) {
            throw ValidationException::withMessages(['documents_logo' => 'Učitani logo nije dostupan za obradu.']);
        }

        $mime = strtolower((string) ($file->getMimeType() ?: ''));
        $canNormalize = function_exists('imagecreatefromstring') && function_exists('imagejpeg');

        if ($canNormalize) {
            $bytes = @file_get_contents($source);
            $image = is_string($bytes) ? @imagecreatefromstring($bytes) : false;
            if ($image === false) {
                throw ValidationException::withMessages(['documents_logo' => 'Logo nije moguće pročitati kao sliku.']);
            }

            try {
                $width = imagesx($image);
                $height = imagesy($image);
                if ($width < 1 || $height < 1) {
                    throw ValidationException::withMessages(['documents_logo' => 'Logo ima neispravne dimenzije.']);
                }

                $canvas = imagecreatetruecolor($width, $height);
                if ($canvas === false) {
                    throw ValidationException::withMessages(['documents_logo' => 'Nije moguće pripremiti PDF logo.']);
                }

                try {
                    $white = imagecolorallocate($canvas, 255, 255, 255);
                    imagefill($canvas, 0, 0, $white);
                    imagealphablending($canvas, true);
                    imagecopy($canvas, $image, 0, 0, 0, 0, $width, $height);
                    if (!imagejpeg($canvas, $destination, 92)) {
                        throw ValidationException::withMessages(['documents_logo' => 'Čuvanje PDF logotipa nije uspelo.']);
                    }
                } finally {
                    imagedestroy($canvas);
                }
            } finally {
                imagedestroy($image);
            }
        } elseif (in_array($mime, ['image/jpeg', 'image/jpg'], true)) {
            if (!@copy($source, $destination)) {
                throw ValidationException::withMessages(['documents_logo' => 'Čuvanje PDF logotipa nije uspelo.']);
            }
        } else {
            throw ValidationException::withMessages([
                'documents_logo' => 'Za PNG/WebP logo PHP GD ekstenzija mora biti aktivna. Alternativno učitaj JPG/JPEG logo.',
            ]);
        }

        if (!is_file($destination) || filesize($destination) < 100) {
            @unlink($destination);
            throw ValidationException::withMessages(['documents_logo' => 'Generisani PDF logo nije validan.']);
        }

        try {
            $disk->setVisibility($path, 'public');
        } catch (Throwable) {
            // Local public disk može već imati odgovarajuće dozvole.
        }

        return $path;
    }

    private function deleteManagedLogo(string $path): void
    {
        $path = trim($path);
        if ($path !== '' && str_starts_with($path, 'document-assets/')) {
            Storage::disk('public')->delete($path);
        }
    }

    private function deleteManagedLogoIfUnused(string $path): void
    {
        $path = trim($path);
        if ($path === '' || !str_starts_with($path, 'document-assets/')) return;

        try {
            if (OrderDocument::query()->where('company_logo_path', $path)->exists()) return;
        } catch (Throwable) {
            // Ako šema dokumenata nije dostupna, sigurnije je sačuvati fajl nego pokvariti stari PDF.
            return;
        }

        $this->deleteManagedLogo($path);
    }

    private function publicUrl(string $path): ?string
    {
        $path = trim($path);
        if ($path === '') return null;

        try {
            return Storage::disk('public')->url($path);
        } catch (Throwable) {
            return null;
        }
    }
}
