<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExchangeRateHistory;
use App\Services\ExchangeRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

final class ExchangeRateController extends Controller
{
    public function index(ExchangeRateService $service): View
    {
        return view('admin.settings.exchange-rate', [
            'configuration' => $service->configuration(),
            'history' => ExchangeRateHistory::query()->with('updater')->latest('id')->limit(50)->get(),
        ]);
    }

    public function manual(Request $request, ExchangeRateService $service): RedirectResponse
    {
        $data = $request->validate(['rate' => ['required', 'numeric', 'min:50', 'max:250']]);
        try {
            $service->saveManual((float) $data['rate'], (int) $request->user()->getAuthIdentifier());
            return back()->with('status', 'Ručni EUR/RSD kurs je sačuvan.');
        } catch (RuntimeException $exception) {
            return back()->withErrors(['rate' => $exception->getMessage()])->withInput();
        }
    }

    public function automatic(Request $request, ExchangeRateService $service): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'stale_after_hours' => ['required', 'integer', 'min:1', 'max:720'],
        ]);
        $enabled = $request->boolean('enabled');
        $userId = (int) $request->user()->getAuthIdentifier();
        $service->setAutomatic($enabled, (int) $data['stale_after_hours'], $userId);
        if ($enabled) {
            try {
                $service->updateAutomatically('panel', $userId, true);
            } catch (RuntimeException $exception) {
                return back()->withErrors(['automatic' => 'Automatski režim je uključen, ali preuzimanje trenutno nije uspelo: '.$exception->getMessage()]);
            }
        }

        return back()->with('status', $enabled ? 'Automatsko ažuriranje kursa je uključeno.' : 'Ručni režim kursa je uključen.');
    }

    public function refresh(Request $request, ExchangeRateService $service): RedirectResponse|JsonResponse
    {
        try {
            $result = $service->updateAutomatically('panel', (int) $request->user()->getAuthIdentifier(), true);
            $configuration = $service->configuration();
            $message = 'Kurs je ažuriran na '.number_format((float) $result['rate'], 4, ',', '.').' RSD.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'rate' => (float) $result['rate'],
                    'source' => (string) $result['source'],
                    'provider_date' => (string) $result['date'],
                    'updated_at' => $configuration['updated_at'],
                    'mode' => (string) $configuration['mode'],
                    'is_stale' => (bool) $configuration['is_stale'],
                ]);
            }

            return back()->with('status', $message);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $exception->getMessage(),
                    'code' => 'exchange_rate_sync_failed',
                ], 502);
            }

            return back()->withErrors(['refresh' => $exception->getMessage()]);
        }
    }
}
