<?php

declare(strict_types=1);

namespace App\Services;

use LogicException;

final class LegacyReadOnlyGuard
{
    private const ALLOWED_GRANTS = ['USAGE', 'SELECT', 'SHOW VIEW'];

    public function assertQueryAllowed(string $query): void
    {
        $sql = trim(preg_replace('/\s+/', ' ', $this->stripComments($query)) ?? $query);
        if ($sql === '') {
            return;
        }

        if (preg_match('/\bINTO\s+(OUTFILE|DUMPFILE)\b|\bFOR\s+UPDATE\b|\bGET_LOCK\s*\(|\bRELEASE_LOCK\s*\(/i', $sql)) {
            throw new LogicException('Legacy konekcija je read-only: zaključavanje ili upis kroz SELECT nije dozvoljen.');
        }

        // Metadata introspection is read-only even when the statement contains words
        // such as CREATE (for example: SHOW CREATE TABLE).
        if (preg_match('/^(SHOW|DESCRIBE|DESC)\b/i', $sql)) {
            return;
        }

        if (preg_match('/\b(INSERT|UPDATE|DELETE|REPLACE|ALTER|CREATE|DROP|TRUNCATE|RENAME|GRANT|REVOKE|LOCK|UNLOCK|CALL|LOAD|HANDLER|ANALYZE|OPTIMIZE|REPAIR)\b/i', $sql)) {
            throw new LogicException('Legacy konekcija je read-only: mutirajući SQL je blokiran pre izvršavanja.');
        }

        if (preg_match('/^SET\b/i', $sql) && !preg_match('/^SET\s+(SESSION\s+)?TRANSACTION\s+READ\s+ONLY\b/i', $sql)) {
            throw new LogicException('Legacy konekcija je read-only: SET naredba nije dozvoljena.');
        }
    }

    /** @param array<int|string,mixed> $grants */
    public function grantsAreReadOnly(array $grants): bool
    {
        return $grants !== [] && $this->grantViolations($grants) === [];
    }

    /**
     * @param array<int|string,mixed> $grants
     * @return list<string>
     */
    public function grantViolations(array $grants): array
    {
        $violations = [];

        foreach ($grants as $grant) {
            $raw = trim((string) $grant);
            $normalized = mb_strtoupper($raw);

            if ($normalized === '') {
                $violations[] = 'Prazan SHOW GRANTS red.';
                continue;
            }

            if (str_contains($normalized, 'WITH GRANT OPTION')) {
                $violations[] = 'WITH GRANT OPTION nije dozvoljen: '.$raw;
                continue;
            }

            if (!preg_match('/^GRANT\s+(.+?)\s+ON\s+/i', $normalized, $matches)) {
                $violations[] = 'Neprepoznat grant format ili aktivna DB uloga: '.$raw;
                continue;
            }

            $privileges = array_map(
                static fn (string $privilege): string => trim(preg_replace('/\s+/', ' ', $privilege) ?? $privilege),
                explode(',', $matches[1]),
            );

            foreach ($privileges as $privilege) {
                if (!in_array($privilege, self::ALLOWED_GRANTS, true)) {
                    $violations[] = 'Nedozvoljena privilegija '.$privilege.': '.$raw;
                }
            }
        }

        return array_values(array_unique($violations));
    }

    private function stripComments(string $sql): string
    {
        $sql = preg_replace('#/\*.*?\*/#s', ' ', $sql) ?? $sql;
        $sql = preg_replace('/--[^\r\n]*/', ' ', $sql) ?? $sql;
        return preg_replace('/#[^\r\n]*/', ' ', $sql) ?? $sql;
    }
}
