<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Support\PublicRetailCartReplaySnapshot;
use PHPUnit\Framework\TestCase;

final class PublicRetailCartReplaySnapshotB1071Test extends TestCase
{
    public function test_valid_snapshot_remains_sorted_and_strictly_typed(): void
    {
        self::assertSame([2 => 3, 19 => 1],
            PublicRetailCartReplaySnapshot::validated([19 => 1, 2 => 3]));
        self::assertCount(25,
            PublicRetailCartReplaySnapshot::validated(array_fill_keys(range(1,25),20)));
    }

    public function test_replay_rejects_malformed_entire_cart_instead_of_coercing_items(): void
    {
        foreach ([
            'not an array', [], array_fill_keys(range(1,26),1),
            [0=>1], [-1=>1], ['001'=>1], [2=>0], [2=>21],
            [2=>'2'], [2=>2.0], [2=>true], [2=>['qty'=>2]],
            [3=>1,4=>'invalid'],
        ] as $case) {
            try {
                PublicRetailCartReplaySnapshot::validated($case);
                self::fail('Invalid replay cart was accepted.');
            } catch (\InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
    }
}
