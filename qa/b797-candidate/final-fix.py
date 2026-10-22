from pathlib import Path
root=Path('apps/cms/current')
p=root/'database/migrations/2026_10_22_210000_create_warranty_issuance_and_expiry_delivery_ledgers.php'
s=p.read_text()
old="""        $before = (int)$connection->selectOne('SELECT @@SESSION.lock_wait_timeout AS value')->value;
        $connection->statement('SET SESSION lock_wait_timeout = 5');
        try { $operation(); } finally { $connection->statement('SET SESSION lock_wait_timeout = '.$before); }"""
new="""        $before = (int)$connection->selectOne('SELECT @@SESSION.lock_wait_timeout AS value')->value;
        $innodbBefore = (int)$connection->selectOne('SELECT @@SESSION.innodb_lock_wait_timeout AS value')->value;
        $connection->statement('SET SESSION lock_wait_timeout = 5');
        try {
            $connection->statement('SET SESSION innodb_lock_wait_timeout = 5');
            $operation();
        } finally {
            try { $connection->statement('SET SESSION innodb_lock_wait_timeout = '.$innodbBefore); }
            finally { $connection->statement('SET SESSION lock_wait_timeout = '.$before); }
        }"""
assert s.count(old)==1;s=s.replace(old,new);p.write_text(s)
p=root/'tests/Feature/UserRetirementMySqlIntegrationTest.php';s=p.read_text()
old="""        self::assertSame(
            'ee452183cf5585a14efcaf1e311c9f7b37dd2c8f90dbb9bd3b17eabcac3bac07',
            hash('sha256', implode("\\n", $manifest)."\\n"),
        );"""
new="""        $legacyManifest = array_values(array_filter($manifest, static fn (string $line): bool =>
            !str_starts_with($line, 'warranty_issuances.voided_by ')
            && !str_starts_with($line, 'warranty_expiry_deliveries.user_id ')));
        self::assertCount(121, $legacyManifest);
        self::assertSame(
            'ee452183cf5585a14efcaf1e311c9f7b37dd2c8f90dbb9bd3b17eabcac3bac07',
            hash('sha256', implode("\\n", $legacyManifest)."\\n"),
        );
        // The two additive B797 keys are classified, not mistaken for historical host evidence.
        self::assertCount(123, $manifest);
        self::assertSame('5e77d2e3ea390cf0f1e57c47a63a2273d77d4b63d6a440f304cc14ad3c23d48a',
            hash('sha256', implode("\\n", $manifest)."\\n"));"""
assert s.count(old)==1;s=s.replace(old,new);p.write_text(s)
p=root/'tests/Integration/Warranty797DatabaseSafetyTest.php';s=p.read_text()
needle="DB::statement('SET SESSION lock_wait_timeout = 13'); $started=microtime(true); $failure=null;"
replacement="""DB::statement('SET SESSION lock_wait_timeout = 13');
            DB::statement('SET SESSION innodb_lock_wait_timeout = 17');
            DB::listen(static function ($query): void {
                if ($query->time > 500) fwrite(STDERR, 'B797_SLOW_SQL_MS='.$query->time.' SQL='.$query->sql."\\n");
            });
            $started=microtime(true); $failure=null;"""
assert s.count(needle)==1;s=s.replace(needle,replacement)
s=s.replace("$elapsed=microtime(true)-$started;", "$elapsed=microtime(true)-$started;\n            fwrite(STDERR,'B797_DDL_WAIT_SECONDS='.$elapsed.' FAILURE='.($failure?->getMessage() ?? 'none').\"\\n\");\n            self::assertSame(17,(int)DB::selectOne('SELECT @@SESSION.innodb_lock_wait_timeout AS value')->value);")
p.write_text(s)
print('B797_BOUNDED_DDL_AND_EXACT_LEGACY_MANIFEST_PATCH=APPLIED')
