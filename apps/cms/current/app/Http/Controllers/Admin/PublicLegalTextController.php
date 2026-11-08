<?php
declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\PublicLegalPagesService;
use App\Services\PublicLegalTextStore;
use App\Services\SettingsService;
use App\Support\PublicLegalTemplates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * SuperAdministrator editor for the public legal page texts. A changed text
 * revokes the merchant approval (the effective texts are part of the signed
 * fingerprint). The audit log stores hashes only, never the text.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class PublicLegalTextController extends Controller
{
    public function edit(Request $request, string $page, PublicLegalTextStore $texts,
        PublicLegalPagesService $legal): View
    {
        $this->authorizeOwner($request);
        return view('admin.settings.public-storefront-legal', [
            'page' => $page,
            'meta' => PublicLegalTemplates::PAGES[$page],
            'text' => $texts->effective($page),
            'custom' => $texts->isCustom($page),
            'placeholders' => PublicLegalTemplates::PLACEHOLDERS,
            'values' => $legal->values(),
            'publicPath' => route(PublicLegalPagesService::routeName($page), [], false),
            'maxLength' => PublicLegalTextStore::MAX_LENGTH,
        ]);
    }

    public function update(Request $request, string $page, PublicLegalTextStore $texts,
        SettingsService $settings, AuditLogger $audit): RedirectResponse
    {
        $this->authorizeOwner($request);
        $data = $request->validate([
            'body' => ['required', 'string', 'max:'.PublicLegalTextStore::MAX_LENGTH],
        ], [
            'body.required' => 'Tekst ne može biti prazan. Za vraćanje šablona koristi „Vrati na šablon”.',
            'body.max' => 'Tekst može imati najviše '.PublicLegalTextStore::MAX_LENGTH.' znakova.',
        ]);
        $body = PublicLegalTextStore::normalize((string) $data['body']);
        if ($body === '') {
            return back()->withErrors(['body' => 'Tekst ne može biti prazan.'])->withInput();
        }
        $stored = $body === PublicLegalTemplates::body($page) ? '' : $body;
        $changed = $this->store($request, $page, $stored, $texts, $settings, $audit, 'settings.public_legal_text_updated');

        return redirect()->route('admin.settings.public-storefront.legal.edit', $page)->with('status', $changed
            ? 'Tekst je sačuvan. Potvrda uslova trgovca je povučena i mora se ponovo dati.'
            : 'Tekst nije promenjen.');
    }

    public function reset(Request $request, string $page, PublicLegalTextStore $texts,
        SettingsService $settings, AuditLogger $audit): RedirectResponse
    {
        $this->authorizeOwner($request);
        $changed = $this->store($request, $page, '', $texts, $settings, $audit, 'settings.public_legal_text_reset');

        return redirect()->route('admin.settings.public-storefront.legal.edit', $page)->with('status', $changed
            ? 'Vraćen je šablon. Potvrda uslova trgovca je povučena i mora se ponovo dati.'
            : 'Stranica već koristi šablon.');
    }

    private function store(Request $request, string $page, string $stored, PublicLegalTextStore $texts,
        SettingsService $settings, AuditLogger $audit, string $action): bool
    {
        $owner = $request->user();
        $before = $texts->hash($page);
        $after = hash('sha256', $stored === '' ? PublicLegalTemplates::body($page) : $stored);
        if ($before === $after && $texts->isCustom($page) === ($stored !== '')) {
            return false;
        }
        try {
            DB::transaction(static function () use ($settings, $page, $stored, $owner): void {
                $settings->putMany([
                    PublicLegalTextStore::key($page) => $stored,
                    // Explicitly revoke; the fingerprint change alone would too.
                    'public_checkout_approval_digest' => '',
                    'public_checkout_approved_at' => '',
                    'public_checkout_approved_by' => '',
                ], (int) $owner->id);
            }, 5);
        } finally {
            $settings->forgetCache();
        }
        $audit->log($action,
            'SuperAdministrator je izmenio pravni tekst javne prodavnice: '.PublicLegalTemplates::PAGES[$page]['title'].'.',
            null,
            ['page' => $page, 'text_sha256' => $before],
            ['page' => $page, 'text_sha256' => $after, 'template' => $stored === '', 'approval_revoked' => true],
            user: $owner,
        );
        return true;
    }

    private function authorizeOwner(Request $request): void
    {
        abort_unless($request->user()?->hasRole('superadmin') === true, 403);
    }
}
