<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use App\Services\AuditLogger;
use App\Services\PortalSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

final class AccountController extends Controller
{
    public function show(Request $request, PortalSessionService $sessions): View
    {
        $user = $request->user()->loadMissing(['role', 'group', 'notificationPreference']);
        $preference = $user->notificationPreference ?? new NotificationPreference([
            'in_app_enabled' => true,
            'email_enabled' => false,
            'order_updates' => true,
            'payment_alerts' => true,
            'document_updates' => true,
            'after_sales_updates' => true,
            'warranty_updates' => true,
            'service_updates' => true,
            'receivable_updates' => true,
            'commission_updates' => true,
            'stock_alerts' => $user->hasRole('admin', 'superadmin'),
            'daily_digest' => $user->hasRole('admin', 'superadmin'),
        ]);

        return view('account.show', [
            'user' => $user,
            'notificationPreference' => $preference,
            'activeSessions' => $sessions->activeFor($user, $request),
        ]);
    }

    public function profile(Request $request, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:20'],
        ]);

        $user = $request->user();
        $before = $user->only(['first_name', 'last_name', 'phone', 'address', 'city', 'postal_code']);
        $values = [];
        foreach (['first_name', 'last_name', 'phone', 'address', 'city', 'postal_code'] as $field) {
            if (!Schema::hasColumn('users', $field)) {
                continue;
            }
            $value = trim((string) ($data[$field] ?? ''));
            $values[$field] = $value !== '' ? $value : null;
        }
        if (array_key_exists('first_name', $values) && $values['first_name'] === null) {
            $values['first_name'] = trim((string) $data['first_name']);
        }

        $user->forceFill($values)->save();
        $audit->log(
            'account.profile_updated',
            'Ažuriran korisnički profil',
            $user,
            $before,
            $user->fresh()->only(array_keys($values)),
            user: $user,
            request: $request,
        );

        return back()->with('status', 'Podaci profila su sačuvani.');
    }

    public function notifications(Request $request, AuditLogger $audit): RedirectResponse
    {
        $user = $request->user();
        $before = $user->notificationPreference()->first()?->toArray();
        $values = [
            'in_app_enabled' => $request->boolean('in_app_enabled'),
            'email_enabled' => $request->boolean('email_enabled'),
            'order_updates' => $request->boolean('order_updates'),
            'payment_alerts' => $request->boolean('payment_alerts'),
            'document_updates' => $request->boolean('document_updates'),
            'after_sales_updates' => $request->boolean('after_sales_updates'),
            'warranty_updates' => $request->boolean('warranty_updates'),
            'service_updates' => $request->boolean('service_updates'),
            'receivable_updates' => $request->boolean('receivable_updates'),
            'commission_updates' => $request->boolean('commission_updates'),
            'stock_alerts' => $request->boolean('stock_alerts'),
            'daily_digest' => $request->boolean('daily_digest'),
        ];
        if (Schema::hasTable('notification_preferences')) {
            $existing = array_flip(Schema::getColumnListing('notification_preferences'));
            $values = array_intersect_key($values, $existing);
        }
        $preference = $user->notificationPreference()->updateOrCreate([], $values);
        $audit->log('account.notification_preferences_updated', 'Ažurirane postavke obaveštenja.', $preference, $before, $preference->toArray(), user: $user);

        return back()->with('status', 'Postavke obaveštenja su sačuvane.');
    }

    public function password(Request $request, AuditLogger $audit, PortalSessionService $sessions): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()],
        ]);
        $user = $request->user();
        if (!Hash::check((string) $data['current_password'], (string) $user->password_hash)) {
            return back()->withErrors(['current_password' => 'Trenutna lozinka nije ispravna.']);
        }
        $user->forceFill([
            'password_hash' => Hash::make((string) $data['password']),
            'password_changed_at' => now(),
            'remember_token' => Str::random(60),
        ])->save();
        $user->tokens()->delete();
        $revoked = $sessions->revokeOthers($request, $user, $user);
        $sessions->markLogout($request, $user);
        $request->session()->regenerate();
        $sessions->recordLogin($request, $user, false);
        $audit->log(
            'account.password_changed',
            'Moj nalog',
            $user,
            metadata: ['other_sessions_revoked' => $revoked],
            request: $request,
            level: 'warning',
        );

        return back()->with('status', 'Lozinka je promenjena. Opozvano drugih prijava: '.$revoked.'.');
    }
}
