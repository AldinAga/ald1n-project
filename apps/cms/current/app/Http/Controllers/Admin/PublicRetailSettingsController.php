<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\PublicRetailService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class PublicRetailSettingsController extends Controller
{
    public function index(Request $request, SettingsService $settings, PublicRetailService $retail): View
    {
        $this->authorizeSuperadmin($request);
        return view('admin.settings.public-retail', [
            'merchant' => $settings->all(),
            'isEnabled' => $retail->enabled(),
            'codeEnabled' => config('public_retail.enabled') === true,
            'schemaReady' => Schema::hasTable('public_order_accesses'),
        ]);
    }

    public function update(Request $request, SettingsService $settings, AuditLogger $audit): RedirectResponse
    {
        $this->authorizeSuperadmin($request);
        $data = $request->validate([
            'public_retail_terms_text' => ['required', 'string', 'min:80', 'max:25000'],
            'public_retail_merchant_approved' => ['required', 'in:0,1'],
            'public_retail_free_shipping_approved' => ['required', 'in:0,1'],
            'approval_phrase' => ['nullable', 'string', 'max:40'],
        ]);
        if ($data['public_retail_merchant_approved'] === '1' || $data['public_retail_free_shipping_approved'] === '1') {
            if (($data['approval_phrase'] ?? '') !== 'POTVRDJUJEM') {
                throw ValidationException::withMessages(['approval_phrase' => 'Za potvrdu upisi POTVRDJUJEM.']);
            }
            foreach (['documents_company_name', 'documents_company_address', 'documents_company_city',
                'documents_company_tax_id', 'documents_company_email', 'documents_company_phone'] as $key) {
                if (trim((string) $settings->get($key, '')) === '' || $settings->get($key) === 'Ald1n') {
                    throw ValidationException::withMessages(['public_retail_merchant_approved'
                        => 'Popuni tacne podatke trgovca u PDF i fakturisanje pre potvrde javne prodaje.']);
                }
            }
        }
        $before = [
            'merchant_approved' => $settings->get('public_retail_merchant_approved', '0'),
            'shipping_approved' => $settings->get('public_retail_free_shipping_approved', '0'),
            'terms_sha256' => hash('sha256', (string) $settings->get('public_retail_terms_text', '')),
        ];
        $settings->putMany([
            'public_retail_merchant_approved' => $data['public_retail_merchant_approved'],
            'public_retail_free_shipping_approved' => $data['public_retail_free_shipping_approved'],
            'public_retail_terms_text' => $data['public_retail_terms_text'],
        ], (int) $request->user()->getAuthIdentifier());
        $audit->log('settings.public_retail.updated', 'Javna maloprodaja: odobrenja i hash uslova', null,
            $before, [
                'merchant_approved' => $data['public_retail_merchant_approved'],
                'shipping_approved' => $data['public_retail_free_shipping_approved'],
                'terms_sha256' => hash('sha256', $data['public_retail_terms_text']),
            ]);
        return back()->with('status', 'Sacuvano. Javni katalog radi samo kada je produkcioni release dodatno odobren.');
    }

    private function authorizeSuperadmin(Request $request): void
    {
        abort_unless($request->user()?->hasRole('superadmin') === true, 403);
    }
}
