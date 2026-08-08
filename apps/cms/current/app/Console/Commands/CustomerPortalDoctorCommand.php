<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PortalConversation;
use App\Models\User;
use App\Services\CustomerPortalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ViewErrorBag;
use Throwable;

final class CustomerPortalDoctorCommand extends Command
{
    protected $signature = 'app:customer-portal-doctor {--repair : Pokreni migracije i seed pristupa} {--render : Renderuj ključne Customer Portal stranice}';
    protected $description = 'Proverava Customer Portal 2.0, aktivacije, sesije, komunikaciju i povezane poslovne podatke.';

    public function handle(CustomerPortalService $portal): int
    {
        if ($this->option('repair')) {
            try {
                Artisan::call('migrate', ['--force' => true]);
                $this->output->write(Artisan::output());
                Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\CoreAccessSeeder', '--force' => true]);
            } catch (Throwable $exception) {
                $this->error('Repair nije uspeo: '.$exception->getMessage());

                return self::FAILURE;
            }
        }

        $failed = false;
        $failed = !$this->checkColumns('notification_preferences', [
            'document_updates', 'after_sales_updates', 'warranty_updates', 'service_updates', 'receivable_updates',
        ], 'Preference Customer Portala su spremne.') || $failed;
        $failed = !$this->checkColumns('users', [
            'address', 'city', 'postal_code', 'email_verified_at', 'portal_activated_at',
        ], 'Profil i aktivacija korisnika su spremni.') || $failed;

        $tables = [
            'user_activation_tokens' => ['user_id', 'token_hash', 'expires_at', 'accepted_at'],
            'user_login_sessions' => ['user_id', 'session_hash', 'last_seen_at', 'revoked_at', 'logged_out_at'],
            'portal_conversations' => ['user_id', 'subject', 'status', 'last_message_at'],
            'portal_messages' => ['conversation_id', 'sender_id', 'visibility', 'body', 'sent_at'],
            'portal_order_link_history' => ['order_id', 'from_user_id', 'to_user_id', 'changed_by', 'reason'],
        ];
        foreach ($tables as $table => $columns) {
            $failed = !$this->checkColumns($table, $columns, 'Tabela '.$table.' je spremna.') || $failed;
        }

        $requiredRoutes = [
            'dashboard', 'portal.messages.index', 'portal.messages.store', 'portal.messages.show', 'portal.messages.reply',
            'customer-activation.show', 'customer-activation.store', 'account.profile', 'account.sessions.destroy',
            'account.sessions.destroy-others', 'admin.customer-portal.index', 'admin.customer-portal.users.show',
            'admin.customer-portal.users.invite', 'admin.customer-portal.users.orders.link',
            'admin.customer-portal.conversations.show', 'admin.customer-portal.conversations.reply',
        ];
        foreach ($requiredRoutes as $route) {
            if (!Route::has($route)) {
                $this->line('<fg=red>FAIL</> Nedostaje ruta '.$route.'.');
                $failed = true;
            }
        }
        if (!$failed) {
            $this->line('<fg=green>PASS</> Customer Portal 2.0 rute su registrovane.');
        }

        if ($this->option('render') && !$failed) {
            $failed = !$this->renderPortal($portal) || $failed;
        }

        if ($failed) {
            $this->warn('Pokreni: php artisan app:customer-portal-doctor --repair --render');

            return self::FAILURE;
        }

        $this->info('Customer Portal 2.0 je spreman.');

        return self::SUCCESS;
    }

    /** @param list<string> $columns */
    private function checkColumns(string $table, array $columns, string $success): bool
    {
        try {
            if (!Schema::hasTable($table)) {
                $this->line('<fg=red>FAIL</> Nedostaje tabela '.$table.'.');

                return false;
            }
            $missing = array_values(array_diff($columns, Schema::getColumnListing($table)));
            if ($missing !== []) {
                $this->line('<fg=red>FAIL</> '.$table.' nema kolone: '.implode(', ', $missing));

                return false;
            }
        } catch (Throwable $exception) {
            $this->line('<fg=red>FAIL</> Provera '.$table.' nije uspela: '.$exception->getMessage());

            return false;
        }

        $this->line('<fg=green>PASS</> '.$success);

        return true;
    }

    private function renderPortal(CustomerPortalService $portal): bool
    {
        $actor = User::query()->where('status', 'active')->whereHas('role')->whereHas('orders')->first()
            ?? User::query()->where('status', 'active')->whereHas('role')->first();
        if (!$actor) {
            $this->warn('Nema aktivnog korisnika za render test.');

            return true;
        }

        try {
            $data = $portal->build($actor);
            $data['notificationPreference'] = $portal->preference($actor);
            $html = view('dashboard.partials.customer-center', [
                'portal' => array_replace($data, [
                    'enabled' => true,
                    'warning' => null,
                ]),
                'statusTone' => static fn (string $status): string => match ($status) {
                    'completed', 'paid', 'verified', 'active', 'resolved', 'closed' => 'success',
                    'cancelled', 'rejected', 'void', 'expired', 'overdue' => 'danger',
                    'processing', 'confirmed', 'shipped', 'planned', 'en_route', 'on_site', 'partial', 'pending', 'awaiting_customer' => 'warning',
                    default => 'info',
                },
            ])->with('errors', new ViewErrorBag())->render();
            if (!str_contains($html, 'data-customer-center-ready="1"') || !str_contains($html, 'Jedinstveni korisnički centar') || !str_contains($html, 'Direktna podrška')) {
                $this->line('<fg=red>FAIL</> Početni dashboard nema očekivani integrisani Customer Portal sadržaj.');

                return false;
            }

            $conversation = PortalConversation::query()->where('user_id', $actor->id)->first();
            if ($conversation !== null) {
                $conversation->load(['order', 'assignee', 'publicMessages.sender.role']);
                $threadHtml = view('portal.messages.show', [
                    'conversation' => $conversation,
                    'statusLabels' => PortalConversation::statusLabels(),
                ])->with('errors', new ViewErrorBag())->render();
                if (!str_contains($threadHtml, $conversation->subject)) {
                    $this->line('<fg=red>FAIL</> Render komunikacije nema očekivani naslov.');

                    return false;
                }
            }

            $this->line('<fg=green>PASS</> Integrisani korisnički centar na početnom dashboardu je validan.');

            return true;
        } catch (Throwable $exception) {
            $this->error('Portal render nije uspeo: '.$exception->getMessage());

            return false;
        }
    }
}
