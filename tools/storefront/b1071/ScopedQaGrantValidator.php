<?php
declare(strict_types=1);

/**
 * Offline, read-only evaluation of SHOW GRANTS rows for one disposable QA user.
 * Deliberately conservative: unknown grant forms remain HOLD, never PASS.
 * Not connected to production, and never creates/changes a database grant.
 */
final class ScopedQaGrantValidator
{
    public static function assertExactQaGrants(array $rows): void
    {
        $target='`icaffeco_b913qa`@`localhost`';
        $schema='`icaffeco\\_ald1n\\_b913\\_test`';
        $expected=[
            'GRANT USAGE ON *.* TO '.$target,
            'GRANT ALL PRIVILEGES ON '.$schema.'.* TO '.$target,
        ];
        if (count($rows) !== 2) {
            throw new \InvalidArgumentException('Unexpected number of QA grants.');
        }
        foreach ($rows as $grant) {
            if (!is_string($grant) || !in_array(trim($grant), $expected, true)) {
                throw new \InvalidArgumentException('Unapproved or wildcard QA grant.');
            }
        }
        if (count(array_unique(array_map('trim', $rows))) !== 2) {
            throw new \InvalidArgumentException('Duplicate QA grant rows.');
        }
    }
}
