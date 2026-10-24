<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Services\CustomerActivationService;
use App\Services\PublicStorefrontStateService;
use App\Services\TurnstileService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

/** Self-registration is Kupac-only. No account is usable before email activation. */
final class PublicBuyerRegistrationController extends Controller
{
    private function gate(): void
    {
        abort_unless(app(PublicStorefrontStateService::class)->isOpen(),404);
    }

    public function create(TurnstileService $turnstile): View
    {
        $this->gate();
        return view('public-shop.register',[
            'turnstileEnabled'=>$turnstile->enabled(),
            'turnstileSiteKey'=>$turnstile->siteKey(),
        ]);
    }

    public function store(Request $request, TurnstileService $turnstile,
        CustomerActivationService $activations): RedirectResponse
    {
        $this->gate();
        $data=$request->validate([
            'first_name'=>['required','string','min:2','max:100'],
            'last_name'=>['required','string','min:2','max:100'],
            'email'=>['required','email:rfc','max:190'],
            'phone'=>['nullable','string','max:40'],
            'accepted_privacy'=>['accepted'],
            'website'=>['nullable','size:0'],
            'cf-turnstile-response'=>['nullable','string','max:4096'],
        ]);
        $proof=$turnstile->verify($request->input('cf-turnstile-response'),
            $request->ip(),'retail_registration');
        if (!$proof['success']) {
            throw ValidationException::withMessages(['turnstile'=>$proof['message']]);
        }
        $email=mb_strtolower(trim((string)$data['email']));
        // Avoid distinguishing existing employee/customer emails in public responses.
        $generic='Ako je email adresa slobodna ili čeka aktivaciju, proverite poštu za link. Nalog ne može poručivati pre aktivacije.';
        try {
            $buyer=DB::transaction(static function () use ($email,$data): ?User {
                $existing=User::query()->where('email',$email)->lockForUpdate()->first();
                if ($existing instanceof User) {
                    return $existing->hasRole('customer') && $existing->status==='pending'
                        && $existing->retired_at===null ? $existing : null;
                }
                $roleId=Role::query()->where('slug','customer')->valueOrFail('id');
                return User::query()->create([
                    'role_id'=>$roleId,'user_group_id'=>null,
                    'username'=>'kupac-'.bin2hex(random_bytes(10)),
                    'email'=>$email,
                    // An unguessable temporary password cannot be used to login.
                    // Password is set by the existing verified-email activation flow.
                    'password_hash'=>Hash::make(Str::random(64)),
                    'first_name'=>trim((string)$data['first_name']),
                    'last_name'=>trim((string)$data['last_name']),
                    'phone'=>trim((string)($data['phone']??''))?:null,
                    'status'=>'pending',
                ]);
            },3);
            if ($buyer instanceof User && $buyer->status==='pending'
                && $buyer->retired_at===null) {
                // Bound re-sends for pending accounts. No tokens to active/staff.
                $recent=$buyer->activationTokens()->whereNull('accepted_at')
                    ->where('sent_at','>',now()->subMinutes(15))->exists();
                if (!$recent) $activations->invite($buyer);
            }
        } catch (UniqueConstraintViolationException) {
            // Concurrent same-email registration: generic response, no role override.
        } catch (Throwable $exception) {
            Log::warning('Public Kupac signup not completed.',[
                'exception'=>$exception::class,
            ]);
            // Mail/DB failures are not falsely represented as delivered mail.
            throw ValidationException::withMessages([
                'registration'=>'Registracija trenutno nije dostupna. Pokušajte kasnije.',
            ]);
        }
        // Do not echo an untrusted Host into the post-signup Location.
        return (new RedirectResponse(route('public-shop.register',[],false)))
            ->setSession($request->session())
            ->with('status',$generic);
    }
}
