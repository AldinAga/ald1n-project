<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $this->extendUsers();
        $this->backfillExistingCustomers();
        $this->createActivationTokens();
        $this->createLoginSessions();
        $this->createConversations();
        $this->createMessages();
        $this->createOrderLinkHistory();
    }

    private function extendUsers(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        $columns = [
            'address' => static fn (Blueprint $table) => $table->string('address')->nullable(),
            'city' => static fn (Blueprint $table) => $table->string('city', 120)->nullable(),
            'postal_code' => static fn (Blueprint $table) => $table->string('postal_code', 20)->nullable(),
            'email_verified_at' => static fn (Blueprint $table) => $table->dateTime('email_verified_at')->nullable(),
            'portal_activated_at' => static fn (Blueprint $table) => $table->dateTime('portal_activated_at')->nullable(),
        ];

        foreach ($columns as $column => $definition) {
            if (Schema::hasColumn('users', $column)) {
                continue;
            }

            Schema::table('users', static function (Blueprint $table) use ($definition): void {
                $definition($table);
            });
        }
    }


    private function backfillExistingCustomers(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasTable('roles')) {
            return;
        }
        if (!Schema::hasColumn('users', 'email_verified_at') || !Schema::hasColumn('users', 'portal_activated_at')) {
            return;
        }

        $customerRoleIds = DB::table('roles')->where('slug', 'user')->pluck('id');
        if ($customerRoleIds->isEmpty()) {
            return;
        }

        DB::table('users')
            ->whereIn('role_id', $customerRoleIds)
            ->where('status', 'active')
            ->where(static function ($query): void {
                $query->whereNull('email_verified_at')->orWhereNull('portal_activated_at');
            })
            ->update([
                'email_verified_at' => DB::raw('COALESCE(`email_verified_at`, `approved_at`, `created_at`, CURRENT_TIMESTAMP)'),
                'portal_activated_at' => DB::raw('COALESCE(`portal_activated_at`, `approved_at`, `created_at`, CURRENT_TIMESTAMP)'),
            ]);
    }

    private function createActivationTokens(): void
    {
        if (Schema::hasTable('user_activation_tokens')) {
            return;
        }

        Schema::create('user_activation_tokens', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->char('token_hash', 64)->unique();
            $table->dateTime('expires_at');
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('accepted_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['user_id', 'expires_at'], 'activation_user_expiry_index');
        });
    }

    private function createLoginSessions(): void
    {
        if (Schema::hasTable('user_login_sessions')) {
            return;
        }

        Schema::create('user_login_sessions', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->char('session_hash', 64)->unique();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('device_label', 120)->nullable();
            $table->boolean('remembered')->default(false);
            $table->dateTime('logged_in_at');
            $table->dateTime('last_seen_at');
            $table->dateTime('revoked_at')->nullable();
            $table->unsignedBigInteger('revoked_by')->nullable();
            $table->dateTime('logged_out_at')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('revoked_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['user_id', 'last_seen_at'], 'login_sessions_user_seen_index');
            $table->index(['user_id', 'revoked_at', 'logged_out_at'], 'login_sessions_active_index');
        });
    }

    private function createConversations(): void
    {
        if (Schema::hasTable('portal_conversations')) {
            return;
        }

        Schema::create('portal_conversations', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('subject', 190);
            $table->string('status', 30)->default('waiting_staff');
            $table->string('priority', 20)->default('normal');
            $table->dateTime('last_message_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('closed_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['user_id', 'status', 'last_message_at'], 'portal_conversations_customer_index');
            $table->index(['status', 'priority', 'last_message_at'], 'portal_conversations_staff_index');
        });
    }

    private function createMessages(): void
    {
        if (Schema::hasTable('portal_messages')) {
            return;
        }

        Schema::create('portal_messages', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->string('visibility', 20)->default('public');
            $table->text('body');
            $table->dateTime('sent_at');
            $table->dateTime('read_by_customer_at')->nullable();
            $table->dateTime('read_by_staff_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('conversation_id')->references('id')->on('portal_conversations')->cascadeOnDelete();
            $table->foreign('sender_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['conversation_id', 'visibility', 'sent_at'], 'portal_messages_thread_index');
            $table->index(['conversation_id', 'read_by_customer_at'], 'portal_messages_customer_read_index');
            $table->index(['conversation_id', 'read_by_staff_at'], 'portal_messages_staff_read_index');
        });
    }

    private function createOrderLinkHistory(): void
    {
        if (Schema::hasTable('portal_order_link_history')) {
            return;
        }

        Schema::create('portal_order_link_history', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('from_user_id')->nullable();
            $table->unsignedBigInteger('to_user_id')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->string('reason', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
            $table->foreign('from_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('to_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('changed_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['order_id', 'created_at'], 'portal_order_link_order_index');
            $table->index(['to_user_id', 'created_at'], 'portal_order_link_user_index');
        });
    }

    public function down(): void
    {
        // Customer Portal 2.0 sadrži audit i bezbednosne podatke. Rollback ih namerno ne briše.
    }
};
