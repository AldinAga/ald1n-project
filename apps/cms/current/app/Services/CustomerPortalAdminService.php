<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MobileDevice;
use App\Models\CustomerCrmNote;
use App\Models\Order;
use App\Models\PortalConversation;
use App\Models\PortalOrderLinkHistory;
use App\Models\ProductWarranty;
use App\Models\Role;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class CustomerPortalAdminService
{
    public function __construct(
        private readonly CustomerActivationService $activations,
        private readonly PortalSessionService $sessions,
        private readonly AuditLogger $audit,
    ) {
    }

    /** @param array<string,mixed> $data
     *  @return array{user:User,invitation_sent:bool,invitation_error:?string}
     */
    public function createCustomer(array $data, User $actor): array
    {
        $role = Role::query()->where('slug', 'user')->firstOrFail();
        $group = UserGroup::query()->where('slug', 'standardni-korisnik')->first();
        $user = User::query()->create([
            'role_id' => $role->id,
            'user_group_id' => $group?->id,
            'username' => $this->uniqueUsername((string) $data['email']),
            'email' => mb_strtolower(trim((string) $data['email'])),
            'password_hash' => Hash::make(Str::random(64)),
            'first_name' => trim((string) $data['first_name']),
            'last_name' => trim((string) ($data['last_name'] ?? '')) ?: null,
            'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
            'address' => trim((string) ($data['address'] ?? '')) ?: null,
            'city' => trim((string) ($data['city'] ?? '')) ?: null,
            'postal_code' => trim((string) ($data['postal_code'] ?? '')) ?: null,
            'status' => 'pending',
        ]);

        $this->audit->log(
            'customer_portal.user_created',
            'Kreiran korisnik portala',
            $user,
            null,
            $user->toArray(),
            user: $actor,
        );

        try {
            $this->activations->invite($user, $actor);
            return ['user' => $user, 'invitation_sent' => true, 'invitation_error' => null];
        } catch (Throwable $exception) {
            return [
                'user' => $user,
                'invitation_sent' => false,
                'invitation_error' => $exception->getMessage(),
            ];
        }
    }

    public function invite(User $customer, User $actor): mixed
    {
        $this->assertCustomer($customer);
        if ($customer->status === 'blocked') {
            throw ValidationException::withMessages([
                'invitation' => ['Blokiranom nalogu nije moguće poslati aktivacioni poziv.'],
            ]);
        }

        $token = $this->activations->invite($customer, $actor);
        $this->audit->log(
            'customer_portal.invitation_sent',
            'Poslat aktivacioni poziv',
            $customer,
            metadata: ['expires_at' => $token->expires_at?->toIso8601String()],
            user: $actor,
        );

        return $token;
    }

    public function appendCrmNote(User $customer, string $body, User $actor): CustomerCrmNote
    {
        $this->assertCustomer($customer);
        $body = trim($body);
        $length = mb_strlen($body);
        if ($length < 2 || $length > 5000) {
            throw ValidationException::withMessages([
                'body' => ['CRM napomena mora imati izmedju 2 i 5000 karaktera.'],
            ]);
        }

        return DB::transaction(function () use ($customer, $body, $length, $actor): CustomerCrmNote {
            $note = CustomerCrmNote::query()->create([
                'user_id' => $customer->id,
                'author_user_id' => $actor->id,
                'body' => $body,
            ]);

            $this->audit->log(
                'customer_crm.note_created',
                'Dodata interna CRM napomena',
                $note,
                metadata: [
                    'customer_id' => (int) $customer->id,
                    'note_id' => (int) $note->id,
                    'body_length' => $length,
                ],
                user: $actor,
            );

            return $note;
        }, 3);
    }
    /** @return array{order:Order,previous_user_id:int,warranties_updated:int,conversations_updated:int,changed:bool} */
    public function linkOrder(
        User $customer,
        int $orderId,
        string $reason,
        bool $confirmReassign,
        bool $moveRelatedPortalData,
        User $actor,
    ): array {
        $this->assertCustomer($customer);
        $currentOrder = Order::query()->findOrFail($orderId);
        if ((int) $currentOrder->user_id !== (int) $customer->id && !$confirmReassign) {
            throw ValidationException::withMessages([
                'confirm_reassign' => ['Porudžbina je već povezana sa drugim korisnikom. Potvrdite prenos vlasništva.'],
            ]);
        }

        $result = DB::transaction(function () use (
            $customer,
            $orderId,
            $reason,
            $moveRelatedPortalData,
            $actor,
        ): array {
            $order = Order::query()->lockForUpdate()->findOrFail($orderId);
            $previousUserId = (int) $order->user_id;
            if ($previousUserId === (int) $customer->id) {
                return [
                    'order' => $order,
                    'previous_user_id' => $previousUserId,
                    'warranties_updated' => 0,
                    'conversations_updated' => 0,
                    'changed' => false,
                ];
            }

            $order->forceFill(['user_id' => $customer->id, 'updated_by' => $actor->id])->save();
            $warranties = ProductWarranty::query()
                ->where('order_id', $order->id)
                ->update(['user_id' => $customer->id, 'updated_at' => now()]);

            $conversations = 0;
            if ($moveRelatedPortalData && Schema::hasTable('portal_conversations')) {
                $conversations = PortalConversation::query()
                    ->where('order_id', $order->id)
                    ->update(['user_id' => $customer->id, 'updated_at' => now()]);
            }

            PortalOrderLinkHistory::query()->create([
                'order_id' => $order->id,
                'from_user_id' => $previousUserId,
                'to_user_id' => $customer->id,
                'changed_by' => $actor->id,
                'reason' => trim($reason),
                'created_at' => now(),
            ]);

            return [
                'order' => $order,
                'previous_user_id' => $previousUserId,
                'warranties_updated' => (int) $warranties,
                'conversations_updated' => (int) $conversations,
                'changed' => true,
            ];
        }, 3);

        if ($result['changed']) {
            $this->audit->log(
                'customer_portal.order_relinked',
                'Promenjen vlasnik porudžbine',
                $result['order'],
                ['user_id' => $result['previous_user_id']],
                ['user_id' => $customer->id],
                [
                    'reason' => trim($reason),
                    'warranties_updated' => $result['warranties_updated'],
                    'conversations_updated' => $result['conversations_updated'],
                ],
                user: $actor,
            );
        }

        return $result;
    }

    /** @return array{revoked_web_sessions:int,revoked_api_sessions:int,revoked_mobile_devices:int} */
    public function revokeSessions(User $customer, User $actor): array
    {
        $this->assertCustomer($customer);
        return DB::transaction(function () use ($customer, $actor): array {
            $webCount = $this->sessions->revokeAll($customer, $actor);
            $tokenIds = $customer->tokens()->pluck('id')->map(static fn ($id): int => (int) $id)->all();
            $apiCount = count($tokenIds);
            $deviceCount = 0;

            if ($tokenIds !== [] && Schema::hasTable('mobile_devices')) {
                $deviceCount = MobileDevice::query()
                    ->where('user_id', $customer->id)
                    ->whereIn('personal_access_token_id', $tokenIds)
                    ->update([
                        'personal_access_token_id' => null,
                        'push_token' => null,
                        'push_token_hash' => null,
                        'notifications_enabled' => false,
                        'revoked_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

            $customer->tokens()->delete();
            $customer->forceFill(['remember_token' => Str::random(60)])->save();

            $this->audit->log(
                'customer_portal.sessions_revoked',
                'Opozvane sve prijave kupca',
                $customer,
                metadata: [
                    'revoked_web_sessions' => $webCount,
                    'revoked_api_sessions' => $apiCount,
                    'revoked_mobile_devices' => $deviceCount,
                ],
                user: $actor,
                level: 'warning',
            );

            return [
                'revoked_web_sessions' => (int) $webCount,
                'revoked_api_sessions' => $apiCount,
                'revoked_mobile_devices' => (int) $deviceCount,
            ];
        }, 3);
    }

    private function assertCustomer(User $user): void
    {
        if (!$user->hasRole('user')) {
            throw ValidationException::withMessages([
                'user' => ['Izabrani nalog nije korisnički portal nalog.'],
            ]);
        }
    }

    private function uniqueUsername(string $email): string
    {
        $base = Str::slug(Str::before($email, '@'));
        $base = mb_substr($base !== '' ? $base : 'kupac', 0, 42);
        $candidate = $base;
        $suffix = 1;
        while (User::query()->where('username', $candidate)->exists()) {
            $candidate = mb_substr($base, 0, 42).'-'.$suffix;
            $suffix++;
        }
        return $candidate;
    }
}
