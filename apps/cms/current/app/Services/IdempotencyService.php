<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class IdempotencyService
{
    /**
     * @template TModel of Model
     * @param array<string,mixed> $payload
     * @param Closure():TModel $operation
     * @param Closure(int):TModel $replay
     * @return TModel
     */
    public function run(User $actor, string $scope, string $key, array $payload, string $responseType, Closure $operation, Closure $replay): Model
    {
        $normalizedKey = trim($key);
        if ($normalizedKey === '' || mb_strlen($normalizedKey) > 200) {
            throw ValidationException::withMessages(['idempotency_key' => 'Idempotency ključ je obavezan i može imati najviše 200 karaktera.']);
        }

        $actorKey = 'user:'.$actor->getKey();
        $keyHash = hash('sha256', $normalizedKey);
        $requestHash = hash('sha256', json_encode($this->canonicalize($payload), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return DB::transaction(function () use ($actorKey, $scope, $keyHash, $requestHash, $responseType, $operation, $replay): Model {
            $inserted = DB::table('idempotency_keys')->insertOrIgnore([
                'scope' => $scope,
                'actor_key' => $actorKey,
                'key_hash' => $keyHash,
                'request_hash' => $requestHash,
                'status' => 'processing',
                'locked_at' => now(),
                'expires_at' => now()->addDays(7),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $record = DB::table('idempotency_keys')
                ->where('scope', $scope)
                ->where('actor_key', $actorKey)
                ->where('key_hash', $keyHash)
                ->lockForUpdate()
                ->first();

            if ($record === null) {
                throw new RuntimeException('Idempotency zapis nije moguće zaključati.');
            }

            if (!hash_equals((string) $record->request_hash, $requestHash)) {
                throw ValidationException::withMessages([
                    'idempotency_key' => 'Isti idempotency ključ je već upotrebljen sa drugačijim podacima.',
                ]);
            }

            if ($inserted === 0 && (string) $record->status === 'completed' && $record->response_id !== null) {
                return $replay((int) $record->response_id);
            }

            DB::table('idempotency_keys')->where('id', $record->id)->update([
                'status' => 'processing',
                'locked_at' => now(),
                'updated_at' => now(),
            ]);

            $result = $operation();
            if (!$result->exists || $result->getKey() === null) {
                throw new RuntimeException('Idempotentna operacija mora vratiti sačuvan model.');
            }

            DB::table('idempotency_keys')->where('id', $record->id)->update([
                'status' => 'completed',
                'response_type' => $responseType,
                'response_id' => $result->getKey(),
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

            return $result;
        }, 5);
    }

    /** @param mixed $value @return mixed */
    private function canonicalize(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        if (!array_is_list($value)) {
            ksort($value);
        }

        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalize($item);
        }

        return $value;
    }
}
