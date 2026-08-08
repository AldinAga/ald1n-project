<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class OrderOptionsController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $suppliers = User::query()
            ->where('status', 'active')
            ->whereHas('role', static fn ($query) => $query->whereIn('slug', ['superadmin', 'admin']))
            ->with('role:id,name,slug')
            ->get()
            ->sortBy(static fn (User $supplier): int => $supplier->hasRole('superadmin') ? 0 : 1)
            ->values()
            ->map(static fn (User $supplier): array => [
                'id' => $supplier->id,
                'name' => $supplier->displayName(),
                'email' => $supplier->email,
                'phone' => $supplier->phone,
                'role' => $supplier->role?->slug,
            ]);

        return response()->json(['data' => [
            'idempotency_key' => (string) Str::uuid(),
            'payment_methods' => [
                ['value' => 'cash_on_delivery', 'label' => 'Plaćanje pouzećem', 'requires_bank_account' => false],
                ['value' => 'bank_transfer', 'label' => 'Uplata na račun', 'requires_bank_account' => true],
            ],
            'bank_accounts' => BankAccount::query()
                ->where('is_active', true)
                ->orderBy('label')
                ->get()
                ->map(static fn (BankAccount $account): array => [
                    'id' => $account->id,
                    'label' => $account->label,
                    'recipient_name' => $account->recipient_name,
                    'recipient_address' => $account->recipient_address,
                    'account_number' => $account->account_number_display ?: $account->account_number,
                    'payment_code' => $account->payment_code,
                ]),
            'suppliers' => $suppliers,
            'shipping_defaults' => [
                'full_name' => $user->displayName(),
                'address' => $user->address,
                'city' => $user->city,
                'postal_code' => $user->postal_code,
                'phone' => $user->phone,
            ],
            'limits' => [
                'max_items' => 50,
                'max_quantity_per_item' => 1000,
                'customer_note_max_length' => 5000,
            ],
        ]]);
    }
}
