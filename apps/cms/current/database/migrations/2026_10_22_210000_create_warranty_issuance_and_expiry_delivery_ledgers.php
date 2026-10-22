<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = ['warranty_issuances', 'warranty_expiry_deliveries'];

    public function up(): void
    {
        $this->bounded(function (): void {
            if (DB::getDriverName() === 'sqlite') { $this->sqlite(); return; }
            $this->parentsAreInnoDb();
            foreach (self::TABLES as $table) {
                if (!Schema::hasTable($table)) DB::statement($this->definition($table));
                $this->verifyTable($table);
            }
        });
    }

    public function down(): void
    {
        $this->bounded(function (): void {
            foreach (self::TABLES as $table) {
                if (!Schema::hasTable($table)) continue;
                if (DB::getDriverName() !== 'sqlite') $this->verifyTable($table);
                if (DB::table($table)->exists()) {
                    throw new RuntimeException('Refusing to drop a populated warranty ledger: '.$table);
                }
            }
            Schema::dropIfExists('warranty_expiry_deliveries');
            Schema::dropIfExists('warranty_issuances');
        });
    }

    /** Inline constraints make each CREATE atomic; Blueprint's later ALTERs can leave a partial table on lock timeout. */
    private function definition(string $table): string
    {
        if ($table === 'warranty_issuances') return <<<'SQL'
CREATE TABLE `warranty_issuances` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `warranty_number` VARCHAR(60) NOT NULL,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `order_item_id` BIGINT UNSIGNED NOT NULL,
  `confirmed_payment_id` BIGINT UNSIGNED NULL,
  `issued_at` TIMESTAMP NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending_activation',
  `snapshot_json` JSON NOT NULL,
  `activated_warranty_id` BIGINT UNSIGNED NULL,
  `activated_at` TIMESTAMP NULL,
  `voided_at` TIMESTAMP NULL,
  `voided_by` BIGINT UNSIGNED NULL,
  `void_reason` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `warranty_issuances_number_unique` (`warranty_number`),
  UNIQUE KEY `warranty_issuances_item_unique` (`order_item_id`),
  UNIQUE KEY `warranty_issuances_active_unique` (`activated_warranty_id`),
  KEY `warranty_issuances_order_status_index` (`order_id`,`status`),
  CONSTRAINT `warranty_issuances_order_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `warranty_issuances_item_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `warranty_issuances_payment_foreign` FOREIGN KEY (`confirmed_payment_id`) REFERENCES `order_payments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `warranty_issuances_active_foreign` FOREIGN KEY (`activated_warranty_id`) REFERENCES `product_warranties` (`id`) ON DELETE SET NULL,
  CONSTRAINT `warranty_issuances_actor_foreign` FOREIGN KEY (`voided_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
SQL;
        return <<<'SQL'
CREATE TABLE `warranty_expiry_deliveries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_warranty_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `event` VARCHAR(60) NOT NULL,
  `expires_on` DATE NOT NULL,
  `claimed_at` TIMESTAMP NOT NULL,
  `state` VARCHAR(30) NOT NULL DEFAULT 'claimed',
  `channels_json` JSON NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `warranty_expiry_recipient_unique` (`product_warranty_id`,`user_id`,`event`,`expires_on`),
  CONSTRAINT `warranty_expiry_warranty_foreign` FOREIGN KEY (`product_warranty_id`) REFERENCES `product_warranties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `warranty_expiry_user_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
SQL;
    }

    /** Fail closed on pre-existing or partially created schema, without modifying it. */
    public function verifyTable(string $table): void
    {
        if (!in_array($table, self::TABLES, true)) throw new RuntimeException('Unknown warranty ledger.');
        $db = DB::getDatabaseName();
        $engine = DB::table('information_schema.TABLES')->where('TABLE_SCHEMA', $db)->where('TABLE_NAME', $table)->value('ENGINE');
        if (strtoupper((string) $engine) !== 'INNODB') throw new RuntimeException('Warranty ledger engine mismatch: '.$table);
        $expected = $table === 'warranty_issuances' ? [
            'id'=>['bigint','NO'], 'warranty_number'=>['varchar','NO'], 'order_id'=>['bigint','NO'],
            'order_item_id'=>['bigint','NO'], 'confirmed_payment_id'=>['bigint','YES'], 'issued_at'=>['timestamp','NO'],
            'status'=>['varchar','NO'], 'snapshot_json'=>['json','NO'], 'activated_warranty_id'=>['bigint','YES'],
            'activated_at'=>['timestamp','YES'], 'voided_at'=>['timestamp','YES'], 'voided_by'=>['bigint','YES'],
            'void_reason'=>['text','YES'], 'created_at'=>['timestamp','YES'], 'updated_at'=>['timestamp','YES'],
        ] : [
            'id'=>['bigint','NO'], 'product_warranty_id'=>['bigint','NO'], 'user_id'=>['bigint','NO'],
            'event'=>['varchar','NO'], 'expires_on'=>['date','NO'], 'claimed_at'=>['timestamp','NO'],
            'state'=>['varchar','NO'], 'channels_json'=>['json','NO'], 'created_at'=>['timestamp','YES'], 'updated_at'=>['timestamp','YES'],
        ];
        $columns = DB::table('information_schema.COLUMNS')->where('TABLE_SCHEMA', $db)->where('TABLE_NAME', $table)->get()->keyBy('COLUMN_NAME');
        if ($columns->count() !== count($expected)) throw new RuntimeException('Warranty ledger column count mismatch: '.$table);
        foreach ($expected as $name => [$type, $nullable]) {
            $column = $columns[$name] ?? null;
            if (!$column || $column->IS_NULLABLE !== $nullable || !in_array(strtolower($column->DATA_TYPE), $type === 'json' ? ['json','longtext'] : [$type], true)
                || ($type === 'bigint' && !str_contains(strtolower($column->COLUMN_TYPE), 'unsigned'))
                || ($type === 'varchar' && (int) $column->CHARACTER_MAXIMUM_LENGTH !== (in_array($name,['status','state'],true) ? 30 : 60))) {
                throw new RuntimeException('Warranty ledger column mismatch: '.$table.'.'.$name);
            }
            if ($type === 'json' && strtolower($column->DATA_TYPE) === 'longtext') {
                $checks = DB::table('information_schema.CHECK_CONSTRAINTS')->where('CONSTRAINT_SCHEMA',$db)->where('TABLE_NAME',$table)->pluck('CHECK_CLAUSE');
                if (!$checks->contains(static fn ($value): bool => (bool) preg_match('/json_valid\s*\(\s*`?'.preg_quote($name,'/').'`?\s*\)/i',(string)$value))) {
                    throw new RuntimeException('Warranty ledger JSON constraint mismatch: '.$table.'.'.$name);
                }
            }
        }
        if (!str_contains(strtolower((string)$columns['id']->EXTRA),'auto_increment')) throw new RuntimeException('Warranty ledger identity mismatch.');
        $state = $table === 'warranty_issuances' ? 'status' : 'state';
        $default = trim((string)$columns[$state]->COLUMN_DEFAULT, "'");
        if ($default !== ($state === 'status' ? 'pending_activation' : 'claimed')) throw new RuntimeException('Warranty ledger default mismatch.');
        $actualFks = [];
        $keys = DB::table('information_schema.KEY_COLUMN_USAGE as k')
            ->join('information_schema.REFERENTIAL_CONSTRAINTS as r', static function ($j): void {
                $j->on('k.CONSTRAINT_SCHEMA','=','r.CONSTRAINT_SCHEMA')->on('k.CONSTRAINT_NAME','=','r.CONSTRAINT_NAME')->on('k.TABLE_NAME','=','r.TABLE_NAME');
            })->where('k.TABLE_SCHEMA',$db)->where('k.TABLE_NAME',$table)
            ->get(['k.COLUMN_NAME','k.REFERENCED_TABLE_SCHEMA','k.REFERENCED_TABLE_NAME','k.REFERENCED_COLUMN_NAME','r.DELETE_RULE','r.UPDATE_RULE']);
        foreach ($keys as $key) {
            if ($key->REFERENCED_TABLE_SCHEMA !== $db || !in_array($key->UPDATE_RULE,['RESTRICT','NO ACTION'],true)) throw new RuntimeException('Warranty ledger foreign scope mismatch.');
            $actualFks[$key->COLUMN_NAME] = [$key->REFERENCED_TABLE_NAME,$key->REFERENCED_COLUMN_NAME,$key->DELETE_RULE];
        }
        $fks = $table === 'warranty_issuances' ? [
            'order_id'=>['orders','id','CASCADE'], 'order_item_id'=>['order_items','id','CASCADE'],
            'confirmed_payment_id'=>['order_payments','id','SET NULL'], 'activated_warranty_id'=>['product_warranties','id','SET NULL'],
            'voided_by'=>['users','id','SET NULL'],
        ] : ['product_warranty_id'=>['product_warranties','id','CASCADE'], 'user_id'=>['users','id','CASCADE']];
        ksort($fks); ksort($actualFks);
        if ($fks !== $actualFks || count($keys) !== count($fks)) throw new RuntimeException('Warranty ledger foreign key mismatch: '.$table);
        $indexes = DB::table('information_schema.STATISTICS')->where('TABLE_SCHEMA',$db)->where('TABLE_NAME',$table)->orderBy('SEQ_IN_INDEX')->get()->groupBy('INDEX_NAME');
        $unique = [];
        foreach ($indexes as $name => $index) if ((int)$index->first()->NON_UNIQUE === 0) $unique[] = implode(',', $index->pluck('COLUMN_NAME')->all());
        $expectedUnique = $table === 'warranty_issuances' ? ['id','warranty_number','order_item_id','activated_warranty_id'] : ['id','product_warranty_id,user_id,event,expires_on'];
        sort($unique); sort($expectedUnique);
        if ($unique !== $expectedUnique || !isset($indexes['PRIMARY']) || $indexes['PRIMARY']->pluck('COLUMN_NAME')->all() !== ['id']) {
            throw new RuntimeException('Warranty ledger unique index mismatch: '.$table);
        }
    }

    private function parentsAreInnoDb(): void
    {
        $parents = ['orders','order_items','order_payments','product_warranties','users'];
        $engines = DB::table('information_schema.TABLES')->where('TABLE_SCHEMA',DB::getDatabaseName())->whereIn('TABLE_NAME',$parents)->pluck('ENGINE');
        if ($engines->count() !== count($parents) || $engines->contains(static fn ($v): bool => strtoupper((string)$v) !== 'INNODB')) {
            throw new RuntimeException('Warranty ledger parents require verified InnoDB tables.');
        }
    }

    private function bounded(Closure $operation): void
    {
        $connection = DB::connection();
        if ($connection->getDriverName() === 'sqlite' && app()->environment('testing')) { $operation(); return; }
        $version = (string)$connection->selectOne('SELECT VERSION() AS version')->version;
        if ($connection->getDriverName() !== 'mysql' || !((preg_match('/^10\.11\.19(?:-|$)/',$version) && str_contains($version,'MariaDB')) || preg_match('/^8\.4\./',$version))) {
            throw new RuntimeException('Warranty ledger DDL requires verified MariaDB10.11.19 or MySQL8.4.');
        }
        $before = (int)$connection->selectOne('SELECT @@SESSION.lock_wait_timeout AS value')->value;
        $innodbBefore = (int)$connection->selectOne('SELECT @@SESSION.innodb_lock_wait_timeout AS value')->value;
        $connection->statement('SET SESSION lock_wait_timeout = 5');
        try {
            $connection->statement('SET SESSION innodb_lock_wait_timeout = 5');
            $operation();
        } finally {
            try { $connection->statement('SET SESSION innodb_lock_wait_timeout = '.$innodbBefore); }
            finally { $connection->statement('SET SESSION lock_wait_timeout = '.$before); }
        }
    }

    /** SQLite is allowed only in disposable tests; production always takes the attested atomic SQL path. */
    private function sqlite(): void
    {
        if (!Schema::hasTable('warranty_issuances')) Schema::create('warranty_issuances',static function (Blueprint $t): void {
            $t->id(); $t->string('warranty_number',60)->unique(); $t->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $t->foreignId('order_item_id')->unique()->constrained('order_items')->cascadeOnDelete();
            $t->foreignId('confirmed_payment_id')->nullable()->constrained('order_payments')->nullOnDelete();
            $t->timestamp('issued_at'); $t->string('status',30)->default('pending_activation'); $t->json('snapshot_json');
            $t->foreignId('activated_warranty_id')->nullable()->unique()->constrained('product_warranties')->nullOnDelete();
            $t->timestamp('activated_at')->nullable(); $t->timestamp('voided_at')->nullable();
            $t->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete(); $t->text('void_reason')->nullable();
            $t->timestamps(); $t->index(['order_id','status']);
        });
        if (!Schema::hasTable('warranty_expiry_deliveries')) Schema::create('warranty_expiry_deliveries',static function (Blueprint $t): void {
            $t->id(); $t->foreignId('product_warranty_id')->constrained('product_warranties')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete(); $t->string('event',60); $t->date('expires_on');
            $t->timestamp('claimed_at'); $t->string('state',30)->default('claimed'); $t->json('channels_json'); $t->timestamps();
            $t->unique(['product_warranty_id','user_id','event','expires_on'],'warranty_expiry_recipient_unique');
        });
    }
};
