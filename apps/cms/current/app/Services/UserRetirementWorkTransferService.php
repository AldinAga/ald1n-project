<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Transfer ONLY active operational assignees. Terminal case/action/conversation
 * assignments remain historical and are not rewritten. Every changed FK has
 * before/after audit and supplier orders also retain assignment history.
 */
final class UserRetirementWorkTransferService
{
    /**
     * The five *operational responsibility* FKs among 121 classified user FKs.
     * status exclusions follow canonical Order/AfterSales/Portal/Receivables models.
     *
     * @var array<string,array{table:string,column:string,terminal:list<string>}>
     */
    private const WORK = [
        'supplier_orders' => [
            'table' => 'orders',
            'column' => 'supplier_user_id',
            'terminal' => ['cancelled'],
        ],
        'after_sales_cases' => [
            'table' => 'after_sales_cases',
            'column' => 'assigned_to',
            'terminal' => ['closed'],
        ],
        'after_sales_actions' => [
            'table' => 'after_sales_actions',
            'column' => 'assigned_to',
            'terminal' => ['completed', 'cancelled'],
        ],
        'portal_assignments' => [
            'table' => 'portal_conversations',
            'column' => 'assigned_to',
            'terminal' => ['closed'],
        ],
        'receivable_cases' => [
            'table' => 'receivable_cases',
            'column' => 'assigned_to',
            'terminal' => ['closed'],
        ],
    ];

    public function __construct(private readonly AuditLogger $audit)
    {
    }

    /** @return array<string,int> */
    public function counts(int $targetId): array
    {
        $counts = [];
        foreach (self::WORK as $name => $policy) {
            $query = DB::table($policy['table'])
                ->where($policy['column'], $targetId)
                ->whereNotIn('status', $policy['terminal']);
            if ($name === 'supplier_orders') {
                // Order completion is tracked by completed_at; the legacy
                // MySQL order status enum may not contain 'completed'.
                $query->whereNull('completed_at');
            }
            $counts[$name] = (int) $query->count();
        }

        return $counts;
    }

    /**
     * Called INSIDE UserRetirementService's transaction, after locking target
     * and SuperAdmin user records. No emails or external effects in transaction.
     *
     * @param array<string,int> $preview
     * @return array<string,int>
     */
    public function transfer(User $target, User $recipient, array $preview): array
    {
        $targetId = (int) $target->getKey();
        $recipientId = (int) $recipient->getKey();
        $transferred = [];

        foreach (self::WORK as $name => $policy) {
            $table = $policy['table'];
            $column = $policy['column'];
            $terminal = $policy['terminal'];

            $selection = DB::table($table)
                ->where($column, $targetId)
                ->whereNotIn('status', $terminal);
            if ($name === 'supplier_orders') {
                $selection->whereNull('completed_at');
            }
            $ids = $selection->orderBy('id')->lockForUpdate()->pluck('id');

            abort_if(
                $ids->count() !== (int) ($preview[$name] ?? -1),
                409,
                'Aktivna zaduženja su promenjena. Ponovite pregled pre uklanjanja.',
            );

            foreach ($ids as $recordId) {
                $record = DB::table($table)->where('id', (int) $recordId)->first();
                abort_if($record === null, 409, 'Zaduženje više nije dostupno.');

                $before = [$column => $targetId];
                $after = [$column => $recipientId];
                $updates = [$column => $recipientId, 'updated_at' => now()];

                if ($name === 'supplier_orders') {
                    $before['supplier_name_snapshot'] = $record->supplier_name_snapshot;
                    $before['accepted_by'] = $record->accepted_by;
                    DB::table('order_assignments')->insert([
                        'order_id' => (int) $recordId,
                        'old_supplier_user_id' => $targetId,
                        'new_supplier_user_id' => $recipientId,
                        'changed_by' => $recipientId,
                        'reason' => 'Povlačenje naloga prethodnog odgovornog lica (user.retired)',
                        'created_at' => now(),
                    ]);
                    $updates += [
                        'supplier_name_snapshot' => $recipient->displayName(),
                        'supplier_email_snapshot' => $recipient->email,
                        'supplier_phone_snapshot' => $recipient->phone,
                        'supplier_role_snapshot' => $recipient->roleName(),
                        'assigned_by' => $recipientId,
                        'assigned_at' => now(),
                        'reassigned_at' => now(),
                        'accepted_by' => null,
                        'accepted_at' => null,
                        'updated_by' => $recipientId,
                    ];
                    $after['supplier_name_snapshot'] = $updates['supplier_name_snapshot'];
                }

                $update = DB::table($table)
                    ->where('id', (int) $recordId)
                    ->where($column, $targetId)
                    ->whereNotIn('status', $terminal);
                if ($name === 'supplier_orders') {
                    $update->whereNull('completed_at');
                }
                $affected = $update->update($updates);

                abort_unless($affected === 1, 409, 'Zaduženje nije preneto. Sve promene su poništene.');

                $this->audit->log(
                    'user.retirement.assignment_transferred',
                    'Prenos zaduženja prilikom deaktivacije korisnika',
                    $target,
                    $before,
                    $after,
                    [
                        'resource_table' => $table,
                        'resource_id' => (int) $recordId,
                        'assignment_column' => $column,
                        'previous_user_id' => $targetId,
                        'recipient_user_id' => $recipientId,
                        'reason' => 'user.retired',
                    ],
                    user: $recipient,
                );
            }

            $transferred[$name] = $ids->count();
        }

        return $transferred;
    }
}
