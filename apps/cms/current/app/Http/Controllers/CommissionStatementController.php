<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CommissionPaymentBatch;
use App\Models\OrderCommission;
use App\Models\User;
use App\Services\CommissionStatementService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * PDF obračun i PDF potvrda isplate. Ista akcija služi dve rute:
 * SubAgent (/commissions/...) uvek dobija samo svoje; SuperAdmin (/admin/commissions/...) bira SubAgenta.
 * Admin bez SuperAdmin uloge nema pristup (isplatu radi samo SuperAdmin).
 */
final class CommissionStatementController extends Controller
{
    public function statement(Request $request, CommissionStatementService $statements): Response
    {
        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'user_id' => ['nullable', 'integer'],
        ]);
        $agent = $this->target($request, $data['user_id'] ?? null);

        return $this->pdf($statements->monthly($agent, (string) $data['month']), 'obracun-provizija-'.$data['month'].'-'.$agent->id);
    }

    public function payout(Request $request, int $batch, CommissionStatementService $statements): Response
    {
        $data = $request->validate(['user_id' => ['nullable', 'integer']]);
        $this->authorizeAdminRoute($request);
        $model = CommissionPaymentBatch::query()->with('payer')->findOrFail($batch);
        $userId = $this->isAdminRoute($request) ? ($data['user_id'] ?? null) : (int) $request->user()->id;
        // 404 (ne 403) kad SubAgent nema nijednu proviziju u toj isplati, da se ne otkriva postojanje tuđe isplate.
        abort_unless(OrderCommission::query()->where('payment_batch_id', $model->id)->when($userId !== null, static fn ($q) => $q->where('user_id', (int) $userId))->exists(), 404);

        return $this->pdf($statements->payout($model, $userId === null ? null : (int) $userId), 'potvrda-isplate-'.$model->batch_number);
    }

    private function target(Request $request, mixed $requestedUserId): User
    {
        $this->authorizeAdminRoute($request);
        if (!$this->isAdminRoute($request)) {
            return $request->user();
        }
        abort_if($requestedUserId === null, 422, 'Izaberi SubAgenta.');

        return User::query()->findOrFail((int) $requestedUserId);
    }

    private function isAdminRoute(Request $request): bool
    {
        return $request->routeIs('admin.*');
    }

    private function authorizeAdminRoute(Request $request): void
    {
        if ($this->isAdminRoute($request)) {
            abort_unless($request->user()->hasRole('superadmin'), 403);
        }
    }

    private function pdf(string $content, string $name): Response
    {
        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.preg_replace('/[^A-Za-z0-9._-]/', '-', $name).'.pdf"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}
