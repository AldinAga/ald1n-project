<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\AccountDeletionNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
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
    public const RESEND_PAUSE_MINUTES = 10;
    public const MAX_SENDS_PER_DAY = 3;

    public function __construct(
        private readonly UserRetirementService $retirement,
        private readonly UserRetirementWorkTransferService $work,
    ) {
    }

    public static function available(): bool
    {
        return Schema::hasTable('account_deletion_requests');
    }

    /** Kolona blocked_by_request može nastati kasnije od tabele; bez nje se ponaša kao da je 1. */
    private static function hasFlagColumn(): bool
    {
        return Schema::hasColumn('account_deletion_requests', 'blocked_by_request');
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
        if ($user !== null) {
            $this->requestFor($user, 'web');
        }
    }

    /**
     * Prijavljen korisnik: link ide na e-poštu naloga; brojač slanja je odvojen od javne forme.
     *
     * @return 'sent'|'skipped'|'failed' skipped = pauza ili dnevni limit, failed = mail nije otišao (red je obrisan)
     */
    public function requestForUser(User $user): string
    {
        return self::available() ? $this->requestFor($user, 'own') : 'skipped';
    }

    /** @return 'sent'|'skipped'|'failed' */
    private function requestFor(User $user, string $channel): string
    {
        if ($user->status !== 'active' || $user->retired_at !== null) {
            return 'skipped';
        }
        if ($this->successorProblem($user) !== null) {
            return 'skipped';
        }

        $plain = Str::random(80);
        $dailyKey = 'acct-del-send|'.$channel.'|'.$user->getKey();
        $created = DB::transaction(function () use ($user, $plain, $dailyKey, $channel): bool {
            // Zaključan red korisnika: dva istovremena zahtjeva ne prave dva pending reda niti brišu svjež red.
            // Redoslijed: users pa account_deletion_requests, kao startGrace. cancel/process zaključavaju prvo red zahtjeva,
            // ali samo red sa grace_until, koji ovaj put ne dira. shortcut: moguć rijedak deadlock preko index lockova
            // (InnoDB ga prekida, zahtjev se samo odbije); ako se pojavi u logu, ujednačiti redoslijed i tamo.
            User::query()->whereKey($user->getKey())->lockForUpdate()->first(['id']);
            // Pauza po nalogu (ne po upisanoj adresi, jer collation poklapa akcente i fullwidth varijante):
            // dok je nepotvrđen link mlađi od 10 min, ne šaljemo novi i ne pravimo novi token.
            $recent = DB::table('account_deletion_requests')
                ->where('user_id', $user->getKey())
                ->where('status', 'pending')
                ->whereNull('grace_until')
                ->where('requested_at', '>', now()->subMinutes(self::RESEND_PAUSE_MINUTES))
                ->exists();
            if ($recent || RateLimiter::tooManyAttempts($dailyKey, self::MAX_SENDS_PER_DAY)) {
                return false;
            }
            DB::table('account_deletion_requests')
                ->where('user_id', $user->getKey())
                ->where('status', 'pending')
                ->whereNull('grace_until')
                ->delete();
            if ($this->activeRequest((int) $user->getKey()) !== null) {
                return false;
            }
            DB::table('account_deletion_requests')->insert([
                'user_id' => (int) $user->getKey(),
                'requested_at' => now(),
                'status' => 'pending',
                'source' => $channel,
                'confirm_token_hash' => hash('sha256', $plain),
                'confirm_expires_at' => now()->addMinutes(self::CONFIRM_TTL_MINUTES),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            if ($channel === 'web') {
                // Javni kanal: brojač se troši i kad SMTP padne (odgovor ionako ništa ne otkriva).
                RateLimiter::hit($dailyKey, 86400);
            }

            return true;
        });
        if (!$created) {
            return 'skipped';
        }
        if ($this->send($user, new AccountDeletionNotification(AccountDeletionNotification::CONFIRM, $plain))) {
            if ($channel !== 'web') {
                RateLimiter::hit($dailyKey, 86400);
            }

            return 'sent';
        }
        if ($channel !== 'web') {
            // Prijavljeni korisnik mora znati da mail nije otišao: bez reda i bez potrošenog brojača može odmah ponovo.
            DB::table('account_deletion_requests')->where('confirm_token_hash', hash('sha256', $plain))->delete();
        }

        return 'failed';
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
    public function confirm(string $plain): string
    {
        if (!self::available()) {
            return 'unavailable';
        }
        $row = DB::table('account_deletion_requests')
            ->where('confirm_token_hash', hash('sha256', $plain))
            ->where('status', 'pending')
            ->whereNull('grace_until')
            ->where('confirm_expires_at', '>', now())
            ->first();
        if ($row === null) {
            return 'invalid';
        }
        $user = User::query()->find((int) $row->user_id);
        if ($user === null || $user->status !== 'active' || $user->retired_at !== null) {
            return 'invalid';
        }

        try {
            $this->startGrace($user, 'web');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            // 503 = e-pošta sa linkom za poništavanje nije otišla; to nije "nevažeći link".
            return $exception->getStatusCode() === 503 ? 'unavailable' : 'invalid';
        }

        return 'ok';
    }

    /**
     * Google identitet za brisanje: mora biti povezan sa ovim nalogom i izdat u poslednjih 5 minuta.
     *
     * @param array{sub:string,iat?:int} $identity rezultat GoogleIdentityVerifier::verify()
     */
    public function assertGoogleIdentity(User $user, array $identity): void
    {
        $issuedAt = (int) ($identity['iat'] ?? 0);
        if ($issuedAt <= 0 || $issuedAt < time() - 300 || $issuedAt > time() + 60) {
            throw \Illuminate\Validation\ValidationException::withMessages(['id_token' => ['Google potvrda je istekla. Prijavite se Google nalogom ponovo.']]);
        }
        $linked = \App\Models\UserExternalIdentity::query()
            ->where('user_id', $user->getKey())
            ->where('provider', 'google')
            ->where('provider_subject', (string) $identity['sub'])
            ->exists();
        if (!$linked) {
            throw \Illuminate\Validation\ValidationException::withMessages(['id_token' => ['Google nalog nije povezan sa ovim nalogom.']]);
        }
    }

    /**
     * Administrator menja status naloga dok zahtev za brisanje traje (poziva AdminUserService
     * u istoj transakciji, nad zaključanim nalogom). Odblokiranje zatvara zahtev; ručna blokada
     * preuzima blokadu, pa je poništavanje zahteva više ne skida.
     */
    public function adminChangedStatus(User $locked, string $previousStatus, string $nextStatus): void
    {
        $row = $this->activeRequest((int) $locked->getKey());
        if ($row === null || $row->grace_until === null) {
            return;
        }
        if ($previousStatus === 'blocked' && $nextStatus !== 'blocked') {
            DB::table('account_deletion_requests')->where('id', $row->id)->update([
                'status' => 'cancelled', 'cancel_token_hash' => null, 'updated_at' => now(),
            ]);
            $this->audit('account.deletion_cancelled', (int) $locked->getKey(), ['reason' => 'admin_unblocked']);
        } elseif ($nextStatus === 'blocked' && self::hasFlagColumn()) {
            DB::table('account_deletion_requests')->where('id', $row->id)->update(['blocked_by_request' => false, 'updated_at' => now()]);
        }
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
        return $this->superadmins((int) $user->getKey())->first();
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int,User> aktivni SuperAdministratori osim datog naloga */
    private function superadmins(int $exceptUserId, bool $lock = false): \Illuminate\Database\Eloquent\Collection
    {
        // Isto zaključavanje kao AdminUserService::update: blokada ili degradiranje nasljednika
        // ne može da prođe između provjere i prenosa.
        return User::query()
            ->where('status', 'active')
            ->whereNull('retired_at')
            ->where('id', '!=', $exceptUserId)
            ->whereIn('role_id', DB::table('roles')->where('slug', 'superadmin')->select('id'))
            ->orderBy('id')
            ->when($lock, static fn ($query) => $query->lockForUpdate())
            ->get();
    }

    private function startGrace(User $user, string $source): void
    {
        $plainCancel = Str::random(80);
        DB::transaction(function () use ($user, $source, $plainCancel): void {
            $locked = User::query()->lockForUpdate()->findOrFail((int) $user->getKey());
            abort_if($locked->status !== 'active' || $locked->retired_at !== null, 422, 'Nalog nije aktivan.');
            $this->superadmins((int) $locked->getKey(), true);
            $problem = $this->successorProblem($locked);
            abort_if($problem !== null, 422, (string) $problem);

            $row = DB::table('account_deletion_requests')
                ->where('user_id', (int) $locked->getKey())
                ->whereIn('status', ['pending', 'waiting_obligations'])
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();
            if ($row !== null && $row->grace_until !== null) {
                return;
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
            if (self::hasFlagColumn()) {
                $values['blocked_by_request'] = true;
            }
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

            // Link za poništavanje je jedini put nazad (nalog je blokiran, nema prijava): ako e-pošta
            // ne može da se pošalje, ceo zahtev se poništava umesto da korisnik ostane bez izlaza.
            try {
                $locked->notify(new AccountDeletionNotification(AccountDeletionNotification::SCHEDULED, $plainCancel));
            } catch (Throwable $exception) {
                Log::error('Account deletion confirmation e-mail failed; request rolled back.', ['exception' => $exception::class]);
                abort(503, 'E-pošta sa linkom za poništavanje nije mogla da se pošalje. Pokušajte kasnije.');
            }
        }, 3);
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
    /** @return 'restored'|'still_blocked'|'invalid' */
    public function cancel(string $plain): string
    {
        $row = $this->cancellableByToken($plain);
        if ($row === null) {
            return 'invalid';
        }

        return DB::transaction(function () use ($row): string {
            $current = DB::table('account_deletion_requests')->where('id', $row->id)->lockForUpdate()->first();
            if ($current === null || !in_array($current->status, ['pending', 'waiting_obligations'], true)
                || $current->grace_until === null) {
                return 'invalid';
            }
            $user = User::query()->lockForUpdate()->find((int) $current->user_id);
            if ($user === null || $user->retired_at !== null) {
                return 'invalid';
            }
            DB::table('account_deletion_requests')->where('id', $current->id)->update([
                'status' => 'cancelled',
                'cancel_token_hash' => null,
                'updated_at' => now(),
            ]);
            $restored = false;
            // Aktivira se samo nalog koji je blokirao sam zahtjev; administratorska blokada ostaje.
            if ($user->status === 'blocked' && (bool) ($current->blocked_by_request ?? true)) {
                $user->forceFill(['status' => 'active'])->save();
                $restored = true;
            }
            $this->audit('account.deletion_cancelled', (int) $user->getKey());

            return $restored ? 'restored' : 'still_blocked';
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
            if ($locked->status === 'blocked' && $locked->retired_at === null && (bool) ($current->blocked_by_request ?? true)) {
                $locked->forceFill(['status' => 'active'])->save();
            }
            $this->audit('account.deletion_cancelled', (int) $locked->getKey());

            return true;
        }, 3);
    }

    /**
     * Obrada isteklih zahteva (dnevno).
     *
     * @return array{completed:int,waiting:int,cancelled:int,failed:int,errors:list<string>}
     */
    public function processDue(): array
    {
        $result = ['completed' => 0, 'waiting' => 0, 'cancelled' => 0, 'failed' => 0, 'errors' => []];
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
                if (is_int($result[$outcome] ?? null)) {
                    $result[$outcome]++;
                }
            } catch (Throwable $exception) {
                $result['failed']++;
                $result['errors'][] = '#'.$id.' '.$exception::class.': '.$exception->getMessage();
                Log::error('Account deletion failed.', [
                    'request_id' => (int) $id,
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return $result;
    }

    /** @return 'completed'|'waiting'|'cancelled'|'skipped' */
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
            if ($user === null) {
                DB::table('account_deletion_requests')->where('id', $row->id)->update([
                    'status' => 'completed', 'completed_at' => now(), 'cancel_token_hash' => null, 'updated_at' => now(),
                ]);

                return 'completed';
            }

            // Nalog povučen običnim povlačenjem tokom roka: i dalje se anonimizuje (retire se ne ponavlja).
            $alreadyRetired = $user->retired_at !== null;
            if (!$alreadyRetired && $user->status !== 'blocked') {
                // Administrator je nalog vratio u upotrebu: brisanje bez saglasnosti vlasnika naloga se ne izvršava.
                DB::table('account_deletion_requests')->where('id', $row->id)->update([
                    'status' => 'cancelled', 'cancel_token_hash' => null, 'updated_at' => now(),
                ]);
                $this->audit('account.deletion_cancelled', (int) $user->getKey(), ['reason' => 'account_not_blocked']);

                return 'cancelled';
            }

            $this->superadmins((int) $user->getKey(), true);
            $successor = $this->successorFor($user);
            $needsSuccessor = $user->hasRole('admin', 'superadmin') || array_sum($this->work->counts((int) $user->getKey())) > 0;
            if (!$alreadyRetired && ($this->openObligations((int) $user->getKey()) > 0 || ($needsSuccessor && $successor === null))) {
                $update = ['status' => 'waiting_obligations', 'updated_at' => now()];
                if ($row->owner_notified_at === null) {
                    $update['owner_notified_at'] = now();
                    $notifyWaiting = $user;
                }
                DB::table('account_deletion_requests')->where('id', $row->id)->update($update);

                return 'waiting';
            }

            if (!$alreadyRetired) {
                $this->retirement->retireForAccountDeletion($user, $needsSuccessor ? $successor : null);
            }
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
            // Vlasnik poslovanja zatvara provizije i zaduženja, pa ga obaveštavamo bez ličnih podataka.
            foreach ($this->superadmins((int) $notifyWaiting->getKey()) as $owner) {
                $this->send($owner, new AccountDeletionNotification(AccountDeletionNotification::OWNER_WAITING, null, self::GRACE_DAYS, (int) $notifyWaiting->getKey()));
            }
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
            // otvoren kredit agentu (firma mu duguje) je takođe obaveza koja sprečava brisanje naloga
            $open += (int) DB::table('commission_adjustments')
                ->where('kind', 'credit')->where('status', 'confirmed')->where('amount_eur', '>', 0)
                ->whereIn('commission_id', DB::table('order_commissions')->where('user_id', $userId)->select('id'))
                ->count();
        }

        // Neizmirena potraživanja po porudžbinama naloga (otvoreni slučajevi naplate).
        if (Schema::hasTable('receivable_cases')) {
            $open += (int) DB::table('receivable_cases')
                ->whereIn('order_id', DB::table('orders')->where('user_id', $userId)->select('id'))
                ->where('status', '!=', 'closed')
                ->whereNull('closed_at')
                ->count();
        }

        return $open;
    }

    private function anonymize(int $id): void
    {
        // deleted_N je rezervisano u admin formi; ako je ipak zauzeto, sufiks sprečava da brisanje padne na unique ključu.
        $username = 'deleted_'.$id;
        $email = 'deleted_'.$id.'@invalid.local';
        if (User::query()->where('id', '!=', $id)->where(static fn ($q) => $q->where('username', $username)->orWhere('email', $email))->exists()) {
            $suffix = Str::lower(Str::random(8));
            $username .= '_'.$suffix;
            $email = 'deleted_'.$id.'_'.$suffix.'@invalid.local';
        }
        // Neuspjele prijave nemaju user_id, nego login_hash upisanog imena; hash računamo prije izmjene reda.
        $old = DB::table('users')->where('id', $id)->first(['email', 'username']);
        $loginHashes = array_values(array_unique(array_map(
            static fn (string $login): string => hash('sha256', mb_strtolower(trim($login))),
            array_filter([(string) ($old->email ?? ''), (string) ($old->username ?? '')], static fn (string $v): bool => $v !== ''),
        )));
        $values = [
            'username' => $username,
            'email' => $email,
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
            // Sesija starija od ovog trenutka se ne može vratiti (PortalSessionService poredi logged_in_at).
            'password_changed_at' => now(),
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
        // Sesije nose session_hash (identifikator) i uređaj; nalog je već povučen, pa redovi ne trebaju.
        DB::table('user_login_sessions')->where('user_id', $id)->delete();
        if (Schema::hasTable('notifications')) {
            // Obavještenja u aplikaciji mogu nositi ime kupca, broj porudžbine i e-poštu.
            DB::table('notifications')->where('notifiable_id', $id)
                ->whereIn('notifiable_type', array_unique([(new User())->getMorphClass(), User::class]))->delete();
        }
        if (Schema::hasTable('security_events')) {
            // Događaj ostaje (vrsta, vrijeme, ozbiljnost), a lični podaci odlaze.
            DB::table('security_events')->where('user_id', $id)
                ->update(['ip_address' => null, 'user_agent' => null, 'context_json' => null, 'request_id' => null]);
            if ($loginHashes !== []) {
                DB::table('security_events')->where('event_type', 'login.failed')
                    ->whereIn('context_json->login_hash', $loginHashes)
                    ->update(['ip_address' => null, 'user_agent' => null, 'context_json' => null, 'request_id' => null]);
            }
        }

        // Audit zapisi o samom korisniku nose IP, user agent i stare vrednosti profila.
        $morph = (new User())->getMorphClass();
        // Radnje koje je nalog izvršio ostaju kao istorija poslovanja; brišemo samo IP i uređaj.
        AuditLog::query()->where('user_id', $id)->update(['ip_address' => null, 'user_agent' => null]);
        // Zapisi o samom nalogu (profil, kreiranje, izmene): stare i nove vrijednosti, naslov i metapodaci mogu nositi ime,
        // e-poštu i telefon. Evidencija povlačenja i prenosa zaduženja nema lične podatke i ostaje kao dokaz.
        AuditLog::query()->where('auditable_type', $morph)->where('auditable_id', $id)->update([
            'before_json' => null, 'after_json' => null,
        ]);
        // IP i uređaj brišemo gdje je akter sam obrisani nalog (ili sistem); administratorska izmjena naloga zadržava forenzički IP administratora.
        AuditLog::query()->where('auditable_type', $morph)->where('auditable_id', $id)
            ->where(static fn ($q) => $q->where('user_id', $id)->orWhereNull('user_id'))
            ->update(['ip_address' => null, 'user_agent' => null]);
        AuditLog::query()->where('auditable_type', $morph)->where('auditable_id', $id)
            ->where('action', 'not like', 'account.deletion_%')
            ->whereNotIn('action', ['user.retired', 'user.retirement.assignment_transferred'])
            ->update(['metadata_json' => null, 'subject' => 'Korisnik (obrisan)']);
        if (Schema::hasTable('legacy_audit_logs')) {
            DB::table('legacy_audit_logs')->where('user_id', $id)->update(['ip_address' => null, 'user_agent' => null]);
            DB::table('legacy_audit_logs')->whereIn('entity_type', ['user', 'users'])->where('entity_id', $id)
                ->update(['old_values' => null, 'new_values' => null, 'ip_address' => null, 'user_agent' => null]);
        }
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

    private function send(User $user, AccountDeletionNotification $notification): bool
    {
        try {
            $user->notify($notification);

            return true;
        } catch (Throwable $exception) {
            Log::warning('Account deletion e-mail failed.', ['user_id' => $user->getKey(), 'exception' => $exception::class]);

            return false;
        }
    }
}
