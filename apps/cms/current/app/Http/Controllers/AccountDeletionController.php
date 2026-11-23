<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\TurnstileService;
use App\Services\UserDeletionService;
use App\Support\AppUrlOrigin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Throwable;

/**
 * Javna stranica za brisanje naloga (Google Play zahteva javni URL) i potvrda e-poštom.
 * Odgovor na zahtev je isti bez obzira da li nalog postoji. Potvrda i poništavanje
 * rade preko POST-a, da skeneri e-pošte koji otvaraju linkove ne pokrenu radnju.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class AccountDeletionController extends Controller
{
    private const TOKEN_RULE = ['required', 'string', 'regex:/\A[A-Za-z0-9]{80}\z/D'];

    public function show(TurnstileService $turnstile): View
    {
        return view('public-shop.account-deletion.show', [
            'hideShopNav' => true,
            'turnstileEnabled' => $turnstile->enabled(),
            'turnstileSiteKey' => $turnstile->siteKey(),
            'graceDays' => UserDeletionService::GRACE_DAYS,
        ]);
    }

    public function request(Request $request, TurnstileService $turnstile, UserDeletionService $deletions): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email:rfc', 'max:190'],
            'cf-turnstile-response' => ['nullable', 'string', 'max:4096'],
        ], ['email.required' => 'Unesite e-mail adresu.', 'email.email' => 'Unesite ispravnu e-mail adresu.']);
        if ($validator->fails()) {
            return AppUrlOrigin::redirect($request, 'account-deletion.show')->withErrors($validator)->withInput($request->only('email'));
        }

        if (!UserDeletionService::available()) {
            return AppUrlOrigin::redirect($request, 'account-deletion.show')
                ->withErrors(['email' => 'Brisanje naloga je trenutno nedostupno. Pišite nam na e-poštu podrške.'])->withInput($request->only('email'));
        }

        try {
            $verified = $turnstile->verify($request->input('cf-turnstile-response'), $request->ip(), 'account_deletion_request');
        } catch (Throwable $exception) {
            Log::warning('Account deletion Turnstile check failed.', ['exception' => $exception::class]);
            $verified = ['success' => false, 'message' => 'Provera zaštite trenutno nije dostupna. Pokušajte ponovo.'];
        }
        if (!$verified['success']) {
            return AppUrlOrigin::redirect($request, 'account-deletion.show')
                ->withErrors(['turnstile' => $verified['message']])->withInput($request->only('email'));
        }

        $this->start($deletions, (string) $request->input('email'));

        return AppUrlOrigin::redirect($request, 'account-deletion.show')->with('status', $this->neutralMessage());
    }

    /** Prijavljen korisnik: isti tok, link stiže na e-poštu naloga. */
    public function requestOwn(Request $request, UserDeletionService $deletions): RedirectResponse
    {
        // Prijavljeni korisnik ne mora da se skriva od sebe: jasna poruka umesto lažnog "poslato".
        if (!UserDeletionService::available()) {
            return redirect()->route('account.show')->withErrors(['deletion' => 'Brisanje naloga je trenutno nedostupno.']);
        }
        $problem = $deletions->successorProblem($request->user());
        if ($problem !== null) {
            return redirect()->route('account.show')->withErrors(['deletion' => $problem]);
        }
        $this->start($deletions, (string) $request->user()->email);

        return redirect()->route('account.show')->with('status', 'Poslali smo link za potvrdu na e-poštu naloga. Link važi '.UserDeletionService::CONFIRM_TTL_MINUTES.' minuta.');
    }

    public function confirmShow(string $token, UserDeletionService $deletions): View
    {
        return view('public-shop.account-deletion.token', [
            'hideShopNav' => true,
            'mode' => 'confirm',
            'token' => $token,
            'valid' => $deletions->confirmTokenIsValid($token),
            'graceDays' => UserDeletionService::GRACE_DAYS,
        ]);
    }

    public function confirm(Request $request, UserDeletionService $deletions): View|RedirectResponse
    {
        $token = $this->token($request);
        $outcome = $token === null ? 'invalid' : $deletions->confirm($token);
        $done = $outcome === 'ok';

        return view('public-shop.account-deletion.result', [
            'hideShopNav' => true,
            'ok' => $done,
            'title' => match ($outcome) {
                'ok' => 'Brisanje naloga je zakazano',
                'unavailable' => 'Brisanje trenutno nije moguće',
                default => 'Link nije važeći',
            },
            'message' => match ($outcome) {
                'ok' => 'Nalog je blokiran, a konačno brisanje sledi posle '.UserDeletionService::GRACE_DAYS.' dana. U poruci koju smo vam poslali nalazi se link za poništavanje.',
                'unavailable' => 'Nismo mogli da pošaljemo poruku sa linkom za poništavanje, pa nalog nije promijenjen. Pokušajte ponovo kasnije.',
                default => 'Link je istekao, već je iskorišćen ili nalog ne ispunjava uslove. Zahtev možete ponoviti.',
            },
        ]);
    }

    public function cancelShow(string $token, UserDeletionService $deletions): View
    {
        return view('public-shop.account-deletion.token', [
            'hideShopNav' => true,
            'mode' => 'cancel',
            'token' => $token,
            'valid' => $deletions->cancelTokenIsValid($token),
            'graceDays' => UserDeletionService::GRACE_DAYS,
        ]);
    }

    public function cancel(Request $request, UserDeletionService $deletions): View
    {
        $token = $this->token($request);
        $done = $token !== null && $deletions->cancel($token);

        return view('public-shop.account-deletion.result', [
            'hideShopNav' => true,
            'ok' => $done,
            'title' => $done ? 'Brisanje naloga je poništeno' : 'Link nije važeći',
            'message' => $done
                ? 'Nalog je ponovo aktivan i možete se prijaviti.'
                : 'Rok za poništavanje je istekao ili je zahtev već poništen.',
        ]);
    }

    private function token(Request $request): ?string
    {
        $validator = Validator::make($request->all(), ['token' => self::TOKEN_RULE]);

        return $validator->fails() ? null : (string) $request->input('token');
    }

    private function start(UserDeletionService $deletions, string $email): void
    {
        try {
            $deletions->requestByEmail($email);
        } catch (Throwable $exception) {
            Log::error('Account deletion request failed.', ['exception' => $exception::class]);
        }
    }

    private function neutralMessage(): string
    {
        return 'Ako nalog sa tom e-mail adresom postoji i može da se obriše, poslali smo link za potvrdu. Link važi '.UserDeletionService::CONFIRM_TTL_MINUTES.' minuta.';
    }
}
