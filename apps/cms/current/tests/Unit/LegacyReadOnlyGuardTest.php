<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\LegacyReadOnlyGuard;
use LogicException;
use PHPUnit\Framework\TestCase;

final class LegacyReadOnlyGuardTest extends TestCase
{
    public function test_read_queries_and_read_only_transaction_setting_are_allowed(): void
    {
        $guard = new LegacyReadOnlyGuard();
        foreach ([
            'SELECT * FROM products WHERE id = ?',
            'SHOW GRANTS FOR CURRENT_USER()',
            'SHOW CREATE TABLE products',
            'DESCRIBE products',
            'WITH active AS (SELECT id FROM products) SELECT * FROM active',
            'SET SESSION TRANSACTION READ ONLY',
        ] as $query) {
            $guard->assertQueryAllowed($query);
        }
        self::assertTrue(true);
    }

    /** @dataProvider mutatingQueries */
    public function test_mutating_and_locking_queries_are_blocked(string $query): void
    {
        $this->expectException(LogicException::class);
        (new LegacyReadOnlyGuard())->assertQueryAllowed($query);
    }

    public static function mutatingQueries(): array
    {
        return [
            ['INSERT INTO products (name) VALUES (\'x\')'],
            ['UPDATE products SET name = \'x\''],
            ['DELETE FROM products'],
            ['SELECT * FROM products FOR UPDATE'],
            ['SELECT * FROM products INTO OUTFILE \'/tmp/x\''],
            ['SET FOREIGN_KEY_CHECKS=0'],
            ['CREATE TABLE forbidden (id INT)'],
        ];
    }

    public function test_grant_inspection_rejects_write_privileges(): void
    {
        $guard = new LegacyReadOnlyGuard();
        self::assertTrue($guard->grantsAreReadOnly([
            'GRANT USAGE ON *.* TO `reader`@`localhost`',
            'GRANT SELECT, SHOW VIEW ON `legacy`.* TO `reader`@`localhost`',
        ]));
        self::assertFalse($guard->grantsAreReadOnly([
            'GRANT SELECT, INSERT, UPDATE ON `legacy`.* TO `writer`@`localhost`',
        ]));
        self::assertFalse($guard->grantsAreReadOnly([
            'GRANT ALL PRIVILEGES ON `legacy`.* TO `root`@`localhost`',
        ]));
        self::assertFalse($guard->grantsAreReadOnly([
            'GRANT SELECT, FILE ON *.* TO `reader`@`localhost`',
        ]));
        self::assertFalse($guard->grantsAreReadOnly([
            'GRANT SELECT ON `legacy`.* TO `reader`@`localhost` WITH GRANT OPTION',
        ]));
        self::assertFalse($guard->grantsAreReadOnly([]));
    }
    public function test_grant_violations_explain_unexpected_privileges_and_roles(): void
    {
        $guard = new LegacyReadOnlyGuard();
        $violations = $guard->grantViolations([
            'GRANT SELECT, INSERT ON `legacy`.* TO `writer`@`localhost`',
            'GRANT `some_role`@`%` TO `writer`@`localhost`',
        ]);

        self::assertCount(2, $violations);
        self::assertStringContainsString('INSERT', $violations[0]);
        self::assertStringContainsString('Neprepoznat grant format', $violations[1]);
    }

}
