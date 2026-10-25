<?php
declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * B876: MariaDB UNSIGNED TINYINT role IDs are finite. A rolled-back test must
 * never permanently consume an AUTO_INCREMENT customer role ID.
 * All records are synthetic, no migration or production role reassignment.
 */
final class CoreAccessRoleIdStabilityB876Test extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_customer_role_id_is_stable_across_repeated_core_seeds(): void
    {
        $this->seed(CoreAccessSeeder::class);
        self::assertSame(4,(int)DB::table('roles')->where('slug','customer')->value('id'));
        $before=$this->nextRoleId();
        for($i=0;$i<6;$i++) $this->seed(CoreAccessSeeder::class);
        $after=$this->nextRoleId();
        self::assertSame($before,$after,'Idempotent seeding must not consume finite AUTO_INCREMENT IDs.');
        self::assertSame(4,(int)DB::table('roles')->where('slug','customer')->value('id'));
        self::assertSame(4,(int)DB::table('roles')->count());
    }

    public function test_existing_customer_id_and_user_fk_survive_repeat_seed(): void
    {
        $this->seed(CoreAccessSeeder::class);
        DB::table('roles')->where('slug','customer')->delete();
        DB::table('roles')->insert([
            'id'=>12,'name'=>'Earlier customer name','slug'=>'customer','created_at'=>now(),
        ]);
        DB::table('users')->insert([
            'role_id'=>12,'username'=>'b876-existing-buyer',
            'email'=>'b876-buyer@example.test','password_hash'=>'B876-disposable',
            'status'=>'active','created_at'=>now(),'updated_at'=>now(),
        ]);
        $this->seed(CoreAccessSeeder::class);
        self::assertSame(12,(int)DB::table('roles')->where('slug','customer')->value('id'));
        self::assertSame(12,(int)DB::table('users')->where('username','b876-existing-buyer')->value('role_id'));
        self::assertSame('Kupac',(string)DB::table('roles')->where('id',12)->value('name'));
        self::assertSame(4,(int)DB::table('roles')->count());
    }

    public function test_occupied_id_four_is_never_clobbered_when_customer_role_missing(): void
    {
        $this->seed(CoreAccessSeeder::class);
        DB::table('roles')->where('slug','customer')->delete();
        DB::table('roles')->insert([
            'id'=>4,'name'=>'Protected local role','slug'=>'protected-local','created_at'=>now(),
        ]);
        $this->seed(CoreAccessSeeder::class);
        self::assertSame('protected-local',(string)DB::table('roles')->where('id',4)->value('slug'));
        self::assertSame(5,(int)DB::table('roles')->where('slug','customer')->value('id'));
        self::assertSame(5,(int)DB::table('roles')->count());
    }

    private function nextRoleId(): int
    {
        $record=DB::selectOne(
            'SELECT AUTO_INCREMENT AS next_id FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?',
            ['roles']
        );
        self::assertNotNull($record,'Role ID readback requires genuine MariaDB.');
        return (int)$record->next_id;
    }
}
