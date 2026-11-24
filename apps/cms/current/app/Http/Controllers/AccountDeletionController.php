<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PasswordChangeThrottle;
use App\Services\TurnstileService;
use App\Services\UserDeletionService;
use App\Support\AppUrlOrigin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Throwable;

use function Illuminate\Support\defer;

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
            return AppUrlOrigin::redirect($request, 'account-deletion.show')->withErrors($validator)->withInput($this->oldEmail($request));
        }

        if (!UserDeletionService::available()) {
            return AppUrlOrigin::redirect($request, 'account-deletion.show')
                ->withErrors(['email' => 'Brisanje naloga je trenutno nedostupno. Pišite nam na e-poštu podrške.'])->withInput($this->oldEmail($request));
        }

        try {
            $verified = $turnstile->verify($request->input('cf-turnstile-response'), $request->ip(), 'account_deletion_request');
        } catch (Throwable $exception) {
            Log::warning('Account deletion Turnstile check failed.', ['exception' => $exception::class]);
            $verified = ['success' => false, 'message' => 'Provera zaštite trenutno nije dostupna. Pokušajte ponovo.'];
        }
        if (!$verified['success']) {
            return AppUrlOrigin::redirect($request, 'account-deletion.show')
                ->withErrors(['turnstile' => $verified['message']])->withInput($this->oldEmail($request));
        }

        $email = (string) $request->input('email');
        $this->afterResponse(static fn () => $deletions->requestByEmail($email));

        return AppUrlOrigin::redirect($request, 'account-deletion.show')->with('status', $this->neutralMessage());
    }

    /**
     * Prijavljen korisnik: isti tok, link stiže na e-poštu naloga. Pre slanja se traži trenutna
     * lozinka, kao na API ruti. Nalog napravljen Google prijavom nema lozinku koju vlasnik zna
     * (UserDeletionService::needsPassword); za njega ostaje samo link u e-pošti, jer web nema
     * svež Google token kao aplikacija.
     */
    public function requestOwn(Request $request, UserDeletionService $deletions, PasswordChangeThrottle $throttle): RedirectResponse
    {
        // Prijavljeni korisnik ne mora da se skriva od sebe: jasna poruka umesto lažnog "poslato".
        if (!UserDeletionService::available()) {
            return redirect()->route('account.show')->withErrors(['deletion' => 'Brisanje naloga je trenutno nedostupno.']);
        }
        $user = $request->user();
        if ($deletions->needsPassword($user)) {
            $validator = Validator::make($request->all(), ['deletion_password' => ['required', 'string', 'max:4096']], [
                'deletion_password.*' => 'Unesite trenutnu lozinku.',
            ]);
            if ($validator->fails()) {
                return redirect()->route('account.show')->withErrors($validator);
            }
            $data = $validator->validated();
            // Isti budžet pokušaja kao promena lozinke i API brisanje (5 u 15 min po nalogu): broji se pre
            // poređenja, zaključan nalog odbija i tačnu lozinku, pa forma ne služi za pogađanje.
            $attempt = $throttle->begin($user);
            if ($attempt === null) {
                return redirect()->route('account.show')->withErrors(['deletion_password' => $throttle->message($user)]);
            }
            if (!Hash::check((string) $data['deletion_password'], (string) $user->password_hash)) {
                $throttle->failed($user, $request, 'web-deletion', $attempt);

                return redirect()->route('account.show')->withErrors(['deletion_password' => 'Lozinka nije ispravna.']);
            }
            $throttle->clear($user);
        }
        $problem = $deletions->successorProblem($request->user());
        if ($problem !== null) {
            return redirect()->route('account.show')->withErrors(['deletion' => $problem]);
        }
        // Sinhrono: prijavljenom korisniku vrijeme odgovora ne odaje ništa novo, a poruka mora odgovarati onome što se desilo.
        try {
            $outcome = $deletions->requestForUser($request->user());
        } catch (Throwable $exception) {
            Log::error('Account deletion request failed.', ['exception' => $exception::class]);
            $outcome = 'failed';
        }
        if ($outcome === 'failed') {
            return redirect()->route('account.show')->withErrors(['deletion' => 'Nismo uspeli da pošaljemo e-poruku sa linkom. Pokušajte ponovo kasnije.']);
        }

        return redirect()->route('account.show')->with('status', $outcome === 'sent'
            ? 'Poslali smo link za potvrdu na e-poštu naloga. Link važi '.UserDeletionService::CONFIRM_TTL_MINUTES.' minuta.'
            // Pauza i dnevni limit dobijaju isti tekst: korisnik zna samo da link treba da potraži u pošti, ne koje ograničenje važi.
            : 'Ako je potrebno, link je već poslat u poslednjih '.UserDeletionService::RESEND_PAUSE_MINUTES.' minuta; proverite poštu.');
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
        $outcome = $token === null ? 'invalid' : $deletions->cancel($token);

        return view('public-shop.account-deletion.result', [
            'hideShopNav' => true,
            'ok' => $outcome !== 'invalid',
            'title' => $outcome === 'invalid' ? 'Link nije važeći' : 'Brisanje naloga je poništeno',
            'message' => match ($outcome) {
                'restored' => 'Nalog je ponovo aktivan i možete se prijaviti.',
                'still_blocked' => 'Brisanje je poništeno, ali je nalog i dalje blokiran jer ga je blokirao administrator. Za otključavanje se obratite podršci.',
                default => 'Rok za poništavanje je istekao ili je zahtev već poništen.',
            },
        ]);
    }

    private function token(Request $request): ?string
    {
        $validator = Validator::make($request->all(), ['token' => self::TOKEN_RULE]);

        return $validator->fails() ? null : (string) $request->input('token');
    }

    /** email[]=x ne smije u old('email'): forma ga ispisuje kao string. @return array{email:string} */
    private function oldEmail(Request $request): array
    {
        $email = $request->input('email');

        return ['email' => is_string($email) ? $email : ''];
    }

    private function afterResponse(\Closure $work): void
    {
        // Posle odgovora: vrijeme odgovora ne smije odavati da nalog postoji (SMTP traje stotine ms).
        // defer() se izvrši jednom i ispražnjava listu (app()->terminating bi se ponavljao u istom procesu).
        defer(static function () use ($work): void {
            try {
                $work();
            } catch (Throwable $exception) {
                Log::error('Account deletion request failed.', ['exception' => $exception::class]);
            }
        }, always: true);
    }

    private function neutralMessage(): string
    {
        return 'Ako nalog sa tom e-mail adresom postoji i može da se obriše, poslali smo link za potvrdu. Link važi '.UserDeletionService::CONFIRM_TTL_MINUTES.' minuta.';
    }
}
