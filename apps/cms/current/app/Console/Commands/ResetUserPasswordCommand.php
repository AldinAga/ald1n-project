<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\LegacyUserRecoveryService;
use App\Services\UserLoginResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;
use Throwable;

final class ResetUserPasswordCommand extends Command
{
    protected $signature = 'app:reset-user-password
        {login : Korisničko ime ili e-mail}
        {--no-legacy-recovery : Ne pokušavaj kontrolisani oporavak korisnika iz legacy baze}';

    protected $description = 'Pronađi ili oporavi korisnika iz legacy baze i bezbedno postavi novu lozinku';

    public function handle(UserLoginResolver $users, LegacyUserRecoveryService $recovery): int
    {
        $login = trim((string) $this->argument('login'));
        $user = $users->find($login);

        if ($user === null && !$this->option('no-legacy-recovery')) {
            try {
                $legacy = $recovery->findLegacy($login);
            } catch (Throwable $exception) {
                $this->error('Legacy provera nije uspela: '.$exception->getMessage());
                return self::FAILURE;
            }

            if ($legacy !== null) {
                $this->warn('Korisnik nije pronađen u Laravel bazi, ali postoji u legacy bazi.');
                if (!$this->confirm('Da li da ga bezbedno uvezem u Laravel bazu?', true)) {
                    return self::FAILURE;
                }

                try {
                    $user = $recovery->recover($login);
                    $this->info('Korisnik je oporavljen iz legacy baze.');
                } catch (Throwable $exception) {
                    $this->error('Oporavak korisnika nije uspeo: '.$exception->getMessage());
                    return self::FAILURE;
                }
            }
        }

        if ($user === null) {
            $this->error('Korisnik nije pronađen ni u Laravel ni u legacy bazi.');
            $this->line('Pokreni: php artisan app:auth-doctor '.escapeshellarg($login));
            return self::FAILURE;
        }

        $this->line(sprintf(
            'Pronađen nalog: #%d %s <%s> [%s]',
            $user->id,
            $user->username,
            $user->email,
            $user->status,
        ));

        $password = (string) $this->secret('Unesite novu lozinku (najmanje 12 karaktera)');
        $confirmation = (string) $this->secret('Ponovite novu lozinku');

        $validator = Validator::make([
            'password' => $password,
            'password_confirmation' => $confirmation,
        ], [
            'password' => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return self::FAILURE;
        }

        DB::transaction(function () use ($user, $password): void {
            $user->forceFill([
                'password_hash' => Hash::make($password),
                'password_changed_at' => now(),
                'remember_token' => Str::random(60),
            ])->save();

            $user->tokens()->delete();
            DB::table('password_reset_tokens')->where('user_id', $user->id)->delete();

            if (Schema::hasTable('audit_logs')) {
                AuditLog::query()->create([
                    'user_id' => null,
                    'action' => 'auth.password_reset.cli',
                    'auditable_type' => User::class,
                    'auditable_id' => $user->id,
                    'subject' => 'Lozinka promenjena kroz Terminal za '.$user->username,
                    'before_json' => null,
                    'after_json' => ['password_changed_at' => now()->toIso8601String()],
                    'metadata_json' => ['command' => 'app:reset-user-password'],
                    'ip_address' => null,
                    'user_agent' => 'artisan',
                    'created_at' => now(),
                ]);
            }
        }, 3);

        $this->info('Lozinka je promenjena za '.$user->username.' <'.$user->email.'>.');
        $this->line('Aktivni API tokeni i trajna prijava su opozvani.');

        return self::SUCCESS;
    }
}
