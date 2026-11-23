<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\AccountDeletionNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * Brisanje naloga na zahtev korisnika (Google Play: brisanje u aplikaciji i na javnom URL-u).
 *
 * Tok: zahtev (aplikacija sa lozinkom/Google tokenom ili web sa potvrdom e-poštom) -> rok
 * mirovanja od 14 dana (nalog blokiran, sesije opozvane, poništavanje moguće linkom iz e-pošte)
 * -> komanda app:account-deletions-process radi konačno brisanje: povlačenje naloga kroz
 * UserRetirementService (sistem kao aktor), anonimizacija reda u users i brisanje identifikatora.
 * Porudžbine, provizije, garancije, razgovori i CRM ostaju vezani za anonimizovanog korisnika.
 * Otvorene provizije i zaduženja blokiraju brisanje (status waiting_obligations).
 *
 * Tabela account_deletion_requests ne sadrži lične podatke. Audit zapisi ovog toka se pišu
 * bez IP adrese i user agenta.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class UserDeletionService
{
    public const GRACE_DAYS = 14;
    public const CONFIRM_TTL_MINUTES = 60;

    public function __construct(
        private readonly UserRetirementService $retirement,
        private readonly UserRetirementWorkTransferService $work,
    ) {
    }

    public static function available(): bool
    {
        return Schema::hasTable('account_deletion_requests');
    }

    /**
     * Aktivan (nezavršen i neponišten) zahtev korisnika ili null.
     */
    public function activeRequest(int $userId): ?object
    {
        if (!self::available()) {
            return null;
        }

        return DB::table('account_deletion_requests')
            ->where('user_id', $userId)
            ->whereIn('status', ['pending', 'waiting_obligations'])
            ->orderByDesc('id')
            ->first();
    }

    /** @return array{status:string,grace_until:?string,requested_at:?string} */
    public function state(User $user): array
    {
        $row = $this->activeRequest((int) $user->getKey());
        if ($row === null || $row->grace_until === null) {
            return ['status' => 'none', 'grace_until' => null, 'requested_at' => null];
        }

        return [
            'status' => (string) $row->status,
            'grace_until' => \Illuminate\Support\Carbon::parse($row->grace_until)->toIso8601String(),
            'requested_at' => \Illuminate\Support\Carbon::parse($row->requested_at)->toIso8601String(),
        ];
    }

    /**
     * Korak 1 (web): zahtev e-poštom. Odgovor je uvek isti, bez obzira da li nalog postoji.
     * Nalog se ne menja dok vlasnik ne potvrdi link.
     */
    public function requestByEmail(string $email): void
    {
        if (!self::available()) {
            return;
        }

        $user = User::query()->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($email))])->first();
        if ($user === null || $user->status !== 'active' || $user->retired_at !== null) {
            return;
        }
        if ($this->successorProblem($user) !== null) {
            return;
        }

        $plain = Str::random(80);
        DB::transaction(function () use ($user, $plain): void {
            DB::table('account_deletion_requests')
                ->where('user_id', $user->getKey())
                ->where('status', 'pending')
                ->whereNull('grace_until')
                ->delete();
            if ($this->activeRequest((int) $user->getKey()) !== null) {
                return;
            }
            DB::table('account_deletion_requests')->insert([
                'user_id' => (int) $user->getKey(),
                'requested_at' => now(),
                'status' => 'pending',
                'source' => 'web',
                'confirm_token_hash' => hash('sha256', $plain),
                'confirm_expires_at' => now()->addMinutes(self::CONFIRM_TTL_MINUTES),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        if (DB::table('account_deletion_requests')->where('user_id', $user->getKey())->where('confirm_token_hash', hash('sha256', $plain))->exists()) {
            $this->send($user, new AccountDeletionNotification(AccountDeletionNotification::CONFIRM, $plain));
        }
    }

    public function confirmTokenIsValid(string $plain): bool
    {
        return self::available() && DB::table('account_deletion_requests')
            ->where('confirm_token_hash', hash('sha256', $plain))
            ->where('status', 'pending')
            ->whereNull('grace_until')
            ->where('confirm_expires_at', '>', now())
            ->exists();
    }

    /** Korak 2 (web): potvrda linkom iz e-pošte pokreće rok mirovanja. */
    public function confirm(string $plain): bool
    {
        if (!self::available()) {
            return false;
        }
        $row = DB::table('account_deletion_requests')
            ->where('confirm_token_hash', hash('sha256', $plain))
            ->where('status', 'pending')
            ->whereNull('grace_until')
            ->where('confirm_expires_at', '>', now())
            ->first();
        if ($row === null) {
            return false;
        }
        $user = User::query()->find((int) $row->user_id);
        if ($user === null || $user->status !== 'active' || $user->retired_at !== null) {
            return false;
        }

        try {
            $this->startGrace($user, 'web');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException) {
            return false;
        }

        return true;
    }

    /**
     * Zahtev iz aplikacije: pozivalac je već proverio lozinku ili Google identitet.
     *
     * @return array{status:string,grace_until:?string,requested_at:?string}
     */
    public function startForVerifiedUser(User $user): array
    {
        $this->startGrace($user, 'app');

        return $this->state($user);
    }

    /** Razlog zbog kog nalog ne sme da pokrene brisanje ili null. */
    public function successorProblem(User $user): ?string
    {
        $needs = $user->hasRole('admin', 'superadmin') || array_sum($this->work->counts((int) $user->getKey())) > 0;
        if ($needs && $this->successorFor($user) === null) {
            return 'Brisanje naloga zahteva drugog aktivnog SuperAdministratora koji preuzima porudžbine i aktivan rad. Takav nalog trenutno ne postoji.';
        }

        return null;
    }

    public function successorFor(User $user): ?User
    {
        return User::query()
            ->where('status', 'active')
            ->whereNull('retired_at')
            ->where('id', '!=', (int) $user->getKey())
            ->whereIn('role_id', DB::table('roles')->where('slug', 'superadmin')->select('id'))
            ->orderBy('id')
            ->first();
    }

    private function startGrace(User $user, string $source): void
    {
        $plainCancel = Str::random(80);
        $started = DB::transaction(function () use ($user, $source, $plainCancel): bool {
            $locked = User::query()->lockForUpdate()->findOrFail((int) $user->getKey());
            abort_if($locked->status !== 'active' || $locked->retired_at !== null, 422, 'Nalog nije aktivan.');
            $problem = $this->successorProblem($locked);
            abort_if($problem !== null, 422, (string) $problem);

            $row = DB::table('account_deletion_requests')
                ->where('user_id', (int) $locked->getKey())
                ->whereIn('status', ['pending', 'waiting_obligations'])
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();
            if ($row !== null && $row->grace_until !== null) {
                return false;
            }

            $values = [
                'status' => 'pending',
                'source' => $source,
                'grace_until' => now()->addDays(self::GRACE_DAYS),
                'confirm_token_hash' => null,
                'confirm_expires_at' => null,
                'cancel_token_hash' => hash('sha256', $plainCancel),
                'updated_at' => now(),
            ];
            if ($row !== null) {
                DB::table('account_deletion_requests')->where('id', $row->id)->update($values);
            } else {
                DB::table('account_deletion_requests')->insert($values + [
                    'user_id' => (int) $locked->getKey(),
                    'requested_at' => now(),
                    'created_at' => now(),
                ]);
            }

            $this->lockOut($locked);
            $this->audit('account.deletion_requested', (int) $locked->getKey(), ['source' => $source, 'grace_days' => self::GRACE_DAYS]);

            return true;
        }, 3);

        if ($started) {
            $this->send($user, new AccountDeletionNotification(AccountDeletionNotification::SCHEDULED, $plainCancel));
        }
    }

    /** Blokira nalog i opoziva sve pristupe tokom roka mirovanja. */
    private function lockOut(User $user): void
    {
        $id = (int) $user->getKey();
        $user->forceFill(['status' => 'blocked', 'remember_token' => null])->save();
        DB::table('user_login_sessions')->where('user_id', $id)->whereNull('revoked_at')->whereNull('logged_out_at')
            ->update(['revoked_at' => now()]);
        DB::table('mobile_devices')->where('user_id', $id)->update([
            'revoked_at' => now(),
            'notifications_enabled' => false,
            'push_token' => null,
            'push_token_hash' => null,
            'updated_at' => now(),
        ]);
        $user->tokens()->delete();
    }

    public function cancelTokenIsValid(string $plain): bool
    {
        return $this->cancellableByToken($plain) !== null;
    }

    /** Poništavanje linkom iz e-pošte (jedini put koji radi i bez ijedne važeće prijave). */
    public function cancel(string $plain): bool
    {
        $row = $this->cancellableByToken($plain);
        if ($row === null) {
            return false;
        }

        return DB::transaction(function () use ($row): bool {
            $current = DB::table('account_deletion_requests')->where('id', $row->id)->lockForUpdate()->first();
            if ($current === null || !in_array($current->status, ['pending', 'waiting_obligations'], true)
                || $current->grace_until === null) {
                return false;
            }
            $user = User::query()->lockForUpdate()->find((int) $current->user_id);
            if ($user === null || $user->retired_at !== null) {
                return false;
            }
            DB::table('account_deletion_requests')->where('id', $current->id)->update([
                'status' => 'cancelled',
                'cancel_token_hash' => null,
                'updated_at' => now(),
            ]);
            if ($user->status === 'blocked') {
                $user->forceFill(['status' => 'active'])->save();
            }
            $this->audit('account.deletion_cancelled', (int) $user->getKey());

            return true;
        }, 3);
    }

    private function cancellableByToken(string $plain): ?object
    {
        if (!self::available() || $plain === '') {
            return null;
        }

        return DB::table('account_deletion_requests')
            ->where('cancel_token_hash', hash('sha256', $plain))
            ->whereIn('status', ['pending', 'waiting_obligations'])
            ->whereNotNull('grace_until')
            ->first();
    }

    /** Poništavanje iz prijavljene sesije (ako je nalog još upotrebljiv). */
    public function cancelForUser(User $user): bool
    {
        $row = $this->activeRequest((int) $user->getKey());
        if ($row === null || $row->cancel_token_hash === null) {
            return false;
        }

        return DB::transaction(function () use ($row, $user): bool {
            $current = DB::table('account_deletion_requests')->where('id', $row->id)->lockForUpdate()->first();
            if ($current === null || !in_array($current->status, ['pending', 'waiting_obligations'], true)) {
                return false;
            }
            DB::table('account_deletion_requests')->where('id', $current->id)->update([
                'status' => 'cancelled',
                'cancel_token_hash' => null,
                'updated_at' => now(),
            ]);
            $locked = User::query()->lockForUpdate()->findOrFail((int) $user->getKey());
            if ($locked->status === 'blocked' && $locked->retired_at === null) {
                $locked->forceFill(['status' => 'active'])->save();
            }
            $this->audit('account.deletion_cancelled', (int) $locked->getKey());

            return true;
        }, 3);
    }

    /**
     * Obrada isteklih zahteva (dnevno).
     *
     * @return array{completed:int,waiting:int,failed:int}
     */
    public function processDue(): array
    {
        $result = ['completed' => 0, 'waiting' => 0, 'failed' => 0];
        if (!self::available()) {
            return $result;
        }

        $ids = DB::table('account_deletion_requests')
            ->where(function ($query): void {
                $query->where(function ($due): void {
                    $due->where('status', 'pending')->whereNotNull('grace_until')->where('grace_until', '<=', now());
                })->orWhere(function ($waiting): void {
                    $waiting->where('status', 'waiting_obligations')->whereNotNull('grace_until');
                });
            })
            ->orderBy('id')
            ->pluck('id');

        foreach ($ids as $id) {
            try {
                $outcome = $this->complete((int) $id);
                if (isset($result[$outcome])) {
                    $result[$outcome]++;
                }
            } catch (Throwable $exception) {
                $result['failed']++;
                Log::error('Account deletion failed.', [
                    'request_id' => (int) $id,
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return $result;
    }

    /** @return 'completed'|'waiting'|'skipped' */
    public function complete(int $requestId): string
    {
        $notifyWaiting = null;
        $outcome = DB::transaction(function () use ($requestId, &$notifyWaiting): string {
            $row = DB::table('account_deletion_requests')->where('id', $requestId)->lockForUpdate()->first();
            if ($row === null || $row->grace_until === null || !in_array($row->status, ['pending', 'waiting_obligations'], true)
                || ($row->status === 'pending' && \Illuminate\Support\Carbon::parse($row->grace_until)->isFuture())) {
                return 'skipped';
            }

            $user = User::query()->lockForUpdate()->find((int) $row->user_id);
            if ($user === null || $user->retired_at !== null) {
                DB::table('account_deletion_requests')->where('id', $row->id)->update([
                    'status' => 'completed', 'completed_at' => now(), 'cancel_token_hash' => null, 'updated_at' => now(),
                ]);

                return 'completed';
            }

            $successor = $this->successorFor($user);
            $needsSuccessor = $user->hasRole('admin', 'superadmin') || array_sum($this->work->counts((int) $user->getKey())) > 0;
            if ($this->openObligations((int) $user->getKey()) > 0 || ($needsSuccessor && $successor === null)) {
                $update = ['status' => 'waiting_obligations', 'updated_at' => now()];
                if ($row->owner_notified_at === null) {
                    $update['owner_notified_at'] = now();
                    $notifyWaiting = $user;
                }
                DB::table('account_deletion_requests')->where('id', $row->id)->update($update);

                return 'waiting';
            }

            $this->retirement->retireForAccountDeletion($user, $needsSuccessor ? $successor : null);
            $this->anonymize((int) $user->getKey());
            DB::table('account_deletion_requests')->where('id', $row->id)->update([
                'status' => 'completed',
                'completed_at' => now(),
                'cancel_token_hash' => null,
                'confirm_token_hash' => null,
                'updated_at' => now(),
            ]);
            // Posle čišćenja, da ni sam zapis ne nosi trag identiteta.
            $this->audit('account.deletion_completed', (int) $user->getKey());

            return 'completed';
        }, 3);

        if ($notifyWaiting instanceof User) {
            $this->send($notifyWaiting, new AccountDeletionNotification(AccountDeletionNotification::WAITING));
        }

        return $outcome;
    }

    /**
     * Otvorene provizije (na čekanju ili odobrene, nisu isplaćene) i nepokrivena potvrđena zaduženja.
     */
    public function openObligations(int $userId): int
    {
        $open = (int) DB::table('order_commissions')
            ->where('user_id', $userId)
            ->whereIn('status', ['pending', 'approved'])
            ->where('total_eur', '>', 0)
            ->count();

        if (Schema::hasTable('commission_adjustments')) {
            $debits = DB::table('commission_adjustments')
                ->where('kind', 'debit')->where('status', 'confirmed')
                ->whereIn('commission_id', DB::table('order_commissions')->where('user_id', $userId)->select('id'))
                ->get(['id', 'amount_eur']);
            $settled = Schema::hasTable('commission_debit_settlements') && $debits->isNotEmpty()
                ? DB::table('commission_debit_settlements')->whereIn('adjustment_id', $debits->pluck('id'))
                    ->selectRaw('adjustment_id, SUM(amount_eur) as paid')->groupBy('adjustment_id')->pluck('paid', 'adjustment_id')
                : collect();
            foreach ($debits as $debit) {
                if (round((float) $debit->amount_eur * 100) > round((float) ($settled[$debit->id] ?? 0) * 100)) {
                    $open++;
                }
            }
        }

        return $open;
    }

    private function anonymize(int $id): void
    {
        $values = [
            'username' => 'deleted_'.$id,
            'email' => 'deleted_'.$id.'@invalid.local',
            'first_name' => 'Obrisan',
            'last_name' => null,
            'phone' => null,
            'address' => null,
            'city' => null,
            'postal_code' => null,
            'password_hash' => Hash::make(Str::random(64)),
            'remember_token' => null,
            'email_verified_at' => null,
            'portal_activated_at' => null,
            'updated_at' => now(),
        ];
        foreach (array_keys($values) as $column) {
            if (!Schema::hasColumn('users', $column)) {
                unset($values[$column]);
            }
        }
        DB::table('users')->where('id', $id)->update($values);

        foreach (['user_external_identities', 'restore_credentials', 'passkeys', 'mobile_devices', 'mobile_push_outbox'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->where('user_id', $id)->delete();
            }
        }
        DB::table('user_login_sessions')->where('user_id', $id)
            ->update(['ip_address' => null, 'user_agent' => null, 'device_label' => null]);
        if (Schema::hasTable('security_events')) {
            DB::table('security_events')->where('user_id', $id)->update(['ip_address' => null, 'user_agent' => null, 'context_json' => null]);
        }

        // Audit zapisi o samom korisniku nose IP, user agent i stare vrednosti profila.
        $morph = (new User())->getMorphClass();
        AuditLog::query()->where('user_id', $id)->update([
            'ip_address' => null, 'user_agent' => null, 'before_json' => null, 'after_json' => null, 'metadata_json' => null,
        ]);
        AuditLog::query()->where('auditable_type', $morph)->where('auditable_id', $id)->update([
            'ip_address' => null, 'user_agent' => null, 'before_json' => null, 'after_json' => null,
        ]);
    }

    /** @param array<string,mixed> $metadata */
    private function audit(string $action, int $userId, array $metadata = []): void
    {
        AuditLog::query()->create([
            'user_id' => null,
            'action' => $action,
            'level' => 'info',
            'auditable_type' => (new User())->getMorphClass(),
            'auditable_id' => $userId,
            'subject' => 'Brisanje naloga',
            'metadata_json' => $metadata === [] ? null : $metadata,
            'ip_address' => null,
            'user_agent' => null,
            'created_at' => now(),
        ]);
    }

    private function send(User $user, AccountDeletionNotification $notification): void
    {
        try {
            $user->notify($notification);
        } catch (Throwable $exception) {
            Log::warning('Account deletion e-mail failed.', ['user_id' => $user->getKey(), 'exception' => $exception::class]);
        }
    }
}
