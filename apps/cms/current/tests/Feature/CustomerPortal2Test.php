<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PortalConversation;
use App\Models\Role;
use App\Models\User;
use App\Notifications\CustomerPortalInvitationNotification;
use App\Services\CustomerActivationService;
use App\Services\PortalConversationService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class CustomerPortal2Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_customer_invitation_can_be_activated_only_once(): void
    {
        Notification::fake();
        $customer = $this->customer('portal-activation@example.test', 'pending');
        $service = app(CustomerActivationService::class);
        $service->invite($customer);

        $plainToken = null;
        Notification::assertSentTo($customer, CustomerPortalInvitationNotification::class, static function (CustomerPortalInvitationNotification $notification) use (&$plainToken): bool {
            $plainToken = $notification->token;

            return strlen($notification->token) === 80;
        });

        self::assertIsString($plainToken);
        self::assertTrue($service->tokenIsValid($plainToken));
        $activated = $service->activate($plainToken, 'PortalLozinka123');
        self::assertNotNull($activated);
        self::assertSame('active', $activated->status);
        self::assertTrue(Hash::check('PortalLozinka123', (string) $activated->password_hash));
        self::assertFalse($service->tokenIsValid($plainToken));
        self::assertNull($service->activate($plainToken, 'DrugaLozinka123'));
    }

    public function test_new_invitation_does_not_disable_an_already_active_customer(): void
    {
        Notification::fake();
        $customer = $this->customer('portal-active-reinvite@example.test', 'active');

        app(CustomerActivationService::class)->invite($customer, $this->staff());

        self::assertSame('active', $customer->fresh()->status);
        Notification::assertSentTo($customer, CustomerPortalInvitationNotification::class);
    }

    public function test_customer_can_only_open_own_conversation_and_internal_note_is_hidden(): void
    {
        Notification::fake();
        $customer = $this->customer('portal-owner@example.test');
        $other = $this->customer('portal-other@example.test');
        $staff = $this->staff();
        $service = app(PortalConversationService::class);
        $conversation = $service->createForCustomer($customer, 'Pitanje o porudžbini', 'Molim vas za informaciju.');
        $service->staffReply($conversation, $staff, 'Interna napomena', 'internal', 'open');
        $service->staffReply($conversation, $staff, 'Javni odgovor', 'public', 'waiting_customer');

        $this->actingAs($customer)->get(route('portal.messages.show', $conversation))
            ->assertOk()->assertSee('Javni odgovor')->assertDontSee('Interna napomena');
        $this->actingAs($other)->get(route('portal.messages.show', $conversation))->assertNotFound();
    }

    private function staff(): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', 'superadmin')->valueOrFail('id'),
            'username' => 'portal-admin',
            'email' => 'portal-admin@example.test',
            'password_hash' => password_hash('AdminLozinka123', PASSWORD_DEFAULT),
            'first_name' => 'Portal',
            'last_name' => 'Administrator',
            'status' => 'active',
        ]);
    }

    private function customer(string $email, string $status = 'active'): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', 'user')->valueOrFail('id'),
            'user_group_id' => 1,
            'username' => str_replace(['@', '.'], '-', $email),
            'email' => $email,
            'password_hash' => password_hash('PortalLozinka123', PASSWORD_DEFAULT),
            'first_name' => 'Portal',
            'last_name' => 'Kupac',
            'status' => $status,
        ]);
    }
}
