<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ProductImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** CSV uvoz artikala (dozvola catalog.manage_products preko rute): otpremanje, mapiranje, pregled, izvršenje. */
final class ProductImportController extends Controller
{
    public function index(): View
    {
        return view('admin.products.import', ['result' => session('import_result')]);
    }

    public function upload(Request $request, ProductImportService $service): View
    {
        $request->validate(['file' => ['required', 'file', 'max:1024', 'extensions:csv,txt']]);
        $token = $service->stash(
            (string) file_get_contents($request->file('file')->getRealPath()),
            $request->user(),
            (string) $request->file('file')->getClientOriginalName(),
        );

        return $this->mappingView($service, $token, $request, null);
    }

    public function process(Request $request, ProductImportService $service): View|RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'size:40'],
            'mode' => ['required', Rule::in(['preview', 'execute'])],
            'mapping' => ['required', 'array'],
            'mapping.*' => ['nullable', 'integer', 'min:0', 'max:200'],
            'default_currency' => ['required', Rule::in(['RSD', 'EUR'])],
            'skip_duplicates' => ['nullable', 'boolean'],
        ]);
        $mapping = array_intersect_key($data['mapping'], ProductImportService::FIELDS);
        $hash = $service->mappingHash($mapping, $data['default_currency']);
        if ($data['mode'] === 'execute') {
            // Izvršenje važi samo za mapiranje koje je korisnik video u pregledu.
            if (!hash_equals($hash, (string) $request->input('mapping_hash', ''))) {
                return $this->mappingView($service, $data['token'], $request, null, $mapping, $data['default_currency'])
                    ->withErrors(['mapping' => 'Mapiranje ili valuta su promenjeni posle pregleda. Osveži pregled pa ponovo pokreni uvoz.']);
            }
            // Dvostruko slanje (dupli klik, drugi tab): samo jedan zahtev po tokenu sme da izvršava uvoz.
            // Lock traje 900 s: ako zahtev padne na ograničenju vremena, ponovni pokušaj je moguć tek posle isteka
            // i nastavlja od evidencije već uvezenih redova (isto mapiranje), bez duplikata.
            $lock = Cache::lock('product-import:'.$data['token'], 900);
            if (!$lock->get()) {
                return redirect()->route('admin.products.import')->withErrors(['file' => 'Uvoz ovog fajla je već u toku ili je prekinut pre manje od 15 minuta. Sačekaj pa pokušaj ponovo.']);
            }
            try {
                $file = $service->read($data['token'], $request->user());
                $analysis = $service->analyze($file['rows'], $mapping, $data['default_currency'], $request->user());
                $result = $service->execute($analysis, $request->boolean('skip_duplicates'), $request->user(), $data['token'], $hash);
                $service->forget($data['token'], $request->user());
            } finally {
                $lock->release();
            }

            return redirect()->route('admin.products.import')->with('import_result', $result);
        }

        $file = $service->read($data['token'], $request->user());
        $analysis = $service->analyze($file['rows'], $mapping, $data['default_currency'], $request->user());

        return $this->mappingView($service, $data['token'], $request, $analysis, $mapping, $data['default_currency']);
    }

    /** @param array<string,mixed>|null $analysis @param array<string,mixed>|null $mapping */
    private function mappingView(ProductImportService $service, string $token, Request $request, ?array $analysis, ?array $mapping = null, string $currency = 'EUR'): View
    {
        $file = $service->read($token, $request->user());

        return view('admin.products.import', [
            'token' => $token,
            'headers' => $file['headers'],
            'sample' => array_slice($file['rows'], 0, 3),
            'rowCount' => count($file['rows']),
            'mapping' => $mapping ?? $service->guessMapping($file['headers']),
            'mappingHash' => $service->mappingHash($mapping ?? $service->guessMapping($file['headers']), $currency),
            'currency' => $currency,
            'analysis' => $analysis,
            'result' => null,
        ]);
    }
}
