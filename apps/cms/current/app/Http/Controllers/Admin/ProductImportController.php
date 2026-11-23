<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ProductImportService;
use Illuminate\Http\RedirectResponse;
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
        $token = $service->stash((string) file_get_contents($request->file('file')->getRealPath()), $request->user());

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
        $file = $service->read($data['token'], $request->user());
        $analysis = $service->analyze($file['rows'], $mapping, $data['default_currency'], $request->user());

        if ($data['mode'] === 'execute') {
            $result = $service->execute($analysis, $request->boolean('skip_duplicates'), $request->user());
            $service->forget($data['token'], $request->user());

            return redirect()->route('admin.products.import')->with('import_result', $result);
        }

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
            'currency' => $currency,
            'analysis' => $analysis,
            'result' => null,
        ]);
    }
}
