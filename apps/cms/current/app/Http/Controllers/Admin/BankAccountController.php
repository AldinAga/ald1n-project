<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Services\AuditLogger;
use App\Services\BankAccountNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class BankAccountController extends Controller
{
    public function index(): View
    {
        return view('admin.settings.bank-accounts', [
            'accounts' => BankAccount::query()->latest('updated_at')->get(),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $data = $this->validated($request);
        $data['created_by'] = (int) $request->user()->getAuthIdentifier();
        $data['updated_by'] = $data['created_by'];
        $account = BankAccount::query()->create($data);
        $audit->log('bank_account.created', 'Žiro račun', $account, null, $account->toArray());
        return back()->with('status', 'Žiro račun je dodat.');
    }

    public function update(Request $request, BankAccount $bankAccount, AuditLogger $audit): RedirectResponse
    {
        $before = $bankAccount->toArray();
        $data = $this->validated($request, $bankAccount);
        $data['updated_by'] = (int) $request->user()->getAuthIdentifier();
        $bankAccount->update($data);
        $audit->log('bank_account.updated', 'Žiro račun', $bankAccount, $before, $bankAccount->fresh()->toArray());
        return back()->with('status', 'Žiro račun je izmenjen.');
    }

    public function destroy(BankAccount $bankAccount, AuditLogger $audit): RedirectResponse
    {
        if ($bankAccount->orders()->exists()) {
            throw ValidationException::withMessages([
                'bank_account' => 'Račun se ne može obrisati jer ga koriste porudžbine. Deaktiviraj ga.',
            ]);
        }
        $before = $bankAccount->toArray();
        $bankAccount->delete();
        $audit->log('bank_account.deleted', 'Žiro račun', null, $before, null);
        return back()->with('status', 'Žiro račun je obrisan.');
    }

    /** @return array<string,mixed> */
    private function validated(Request $request, ?BankAccount $account = null): array
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'recipient_name' => ['required', 'string', 'max:70'],
            'recipient_address' => ['nullable', 'string', 'max:70'],
            'account_number' => ['required', 'string', 'max:40'],
            'payment_code' => ['required', 'digits:3'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $numbers = app(BankAccountNumberService::class);
        $digits = $numbers->normalize((string) $data['account_number']);
        if ($digits === null) {
            throw ValidationException::withMessages([
                'account_number' => 'Broj žiro računa nije ispravan. Unesi format npr. 160-1234567890123-45.',
            ]);
        }
        if (!$numbers->passesMod97($digits)) {
            throw ValidationException::withMessages([
                'account_number' => 'Kontrolne cifre žiro računa nisu ispravne (MOD 97 provera nije prošla).',
            ]);
        }
        if (BankAccount::query()->where('account_number', $digits)->when($account, static fn ($query) => $query->whereKeyNot($account->id))->exists()) {
            throw ValidationException::withMessages([
                'account_number' => 'Ovaj žiro račun je već dodat.',
            ]);
        }
        $data['account_number'] = $digits;
        $data['account_number_display'] = $numbers->format($digits);
        $data['is_active'] = $request->boolean('is_active');
        $data['label'] = trim($data['label']);
        $data['recipient_name'] = trim($data['recipient_name']);
        $data['recipient_address'] = trim((string) ($data['recipient_address'] ?? '')) ?: null;
        return $data;
    }
}
