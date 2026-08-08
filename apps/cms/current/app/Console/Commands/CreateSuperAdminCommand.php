<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class CreateSuperAdminCommand extends Command
{
    protected $signature = 'app:create-superadmin
        {username : Korisničko ime}
        {email : E-mail adresa}
        {--password= : Lozinka; ako se izostavi biće bezbedno zatražena}
        {--first-name=Ald1n}
        {--last-name=Admin}';

    protected $description = 'Kreiraj ili aktiviraj početni SuperAdmin nalog';

    public function handle(): int
    {
        $username = trim((string) $this->argument('username'));
        $email = mb_strtolower(trim((string) $this->argument('email')));
        $password = (string) ($this->option('password') ?: $this->secret('Unesite lozinku (najmanje 12 karaktera)'));

        if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
            $this->error('Korisničko ime mora imati 3-50 dozvoljenih karaktera.');
            return self::FAILURE;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('E-mail adresa nije validna.');
            return self::FAILURE;
        }
        if (mb_strlen($password) < 12) {
            $this->error('Lozinka mora imati najmanje 12 karaktera.');
            return self::FAILURE;
        }

        $role = Role::query()->where('slug', 'superadmin')->first();
        if ($role === null) {
            $this->error('SuperAdmin uloga ne postoji. Pokrenite: php artisan db:seed');
            return self::FAILURE;
        }

        $conflict = User::query()
            ->where(static fn ($query) => $query->where('username', $username)->orWhere('email', $email))
            ->first();

        if ($conflict !== null && $conflict->username !== $username && $conflict->email !== $email) {
            $this->error('Korisničko ime ili e-mail pripadaju drugom nalogu.');
            return self::FAILURE;
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'role_id' => $role->id,
                'user_group_id' => null,
                'username' => $username,
                'password_hash' => Hash::make($password),
                'first_name' => trim((string) $this->option('first-name')) ?: null,
                'last_name' => trim((string) $this->option('last-name')) ?: null,
                'status' => 'active',
                'approved_at' => now(),
                'password_changed_at' => now(),
                'remember_token' => Str::random(60),
            ]
        );

        $user->tokens()->delete();
        $this->info('SuperAdmin je spreman: '.$user->username.' <'.$user->email.'>');
        return self::SUCCESS;
    }
}
