<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\UserLoginSession;
use App\Services\AuditLogger;
use App\Services\PortalSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class AccountSessionController extends Controller
{
    public function destroy(
        Request $request,
        UserLoginSession $loginSession,
        PortalSessionService $sessions,
        AuditLogger $audit,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($loginSession->user_id === $user->id, 404);

        $currentHash = $sessions->sessionHash($request);
        if ($currentHash !== null && hash_equals((string) $loginSession->getRawOriginal('session_hash'), $currentHash)) {
            return back()->withErrors([
                'session' => 'Trenutnu sesiju zatvorite standardnim dugmetom Odjava.',
            ]);
        }

        if ($sessions->revoke($user, $loginSession, $user)) {
            $audit->log(
                'account.session_revoked',
                'Opozvana aktivna prijava',
                $loginSession,
                metadata: ['device' => $loginSession->device_label, 'ip_address' => $loginSession->ip_address],
                user: $user,
                request: $request,
                level: 'warning',
            );
        }

        return back()->with('status', 'Izabrana prijava je opozvana.');
    }

    public function destroyOthers(
        Request $request,
        PortalSessionService $sessions,
        AuditLogger $audit,
    ): RedirectResponse {
        $user = $request->user();
        $count = $sessions->revokeOthers($request, $user, $user);
        $user->tokens()->delete();
        $audit->log(
            'account.other_sessions_revoked',
            'Opozvane ostale prijave',
            $user,
            metadata: ['revoked_count' => $count],
            user: $user,
            request: $request,
            level: 'warning',
        );

        return back()->with('status', 'Opozvano drugih prijava: '.$count.'.');
    }
}
