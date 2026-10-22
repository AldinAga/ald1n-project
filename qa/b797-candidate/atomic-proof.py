from pathlib import Path
p=Path('apps/cms/current/tests/Integration/Warranty797DatabaseSafetyTest.php');s=p.read_text()
s=s.replace("self::assertNotNull($failure,'Expected a metadata-lock timeout');\n            self::assertStringContainsString('lock wait timeout',strtolower($failure->getMessage()));", """// MariaDB permits an inline atomic CREATE under this parent lock; MySQL waits.
            // Both outcomes must be fast and safe. Never weaken constraint assertions below.
            if ($failure !== null) {
                self::assertStringContainsString('lock wait timeout',strtolower($failure->getMessage()));
                self::assertFalse(Schema::hasTable('warranty_issuances'),'Failed atomic CREATE must leave no partial ledger');
            } else {
                self::assertTrue(Schema::hasTable('warranty_issuances'));
                $this->migration()->verifyTable('warranty_issuances');
            }""")
needle='    private function parallel(\\Closure $operation): void\n'
new=r'''    public function test_real_ledger_lock_times_out_without_dropping_tables(): void
    {
        $dir=sys_get_temp_dir().'/warranty797-owned-lock-'.bin2hex(random_bytes(6)); mkdir($dir,0700);
        DB::disconnect(); $pid=pcntl_fork(); self::assertNotSame(-1,$pid);
        if ($pid===0) {
            try {
                DB::purge(); DB::statement('LOCK TABLES warranty_issuances WRITE'); file_put_contents($dir.'/ready','ready');
                $until=microtime(true)+20;
                while (!is_file($dir.'/release') && microtime(true)<$until) usleep(20000);
                DB::statement('UNLOCK TABLES'); DB::disconnect(); exit(0);
            } catch (\Throwable $e) { file_put_contents($dir.'/error',$e::class.': '.$e->getMessage()); exit(1); }
        }
        DB::purge(); $until=microtime(true)+8;
        while (!is_file($dir.'/ready') && !is_file($dir.'/error') && microtime(true)<$until) usleep(20000);
        try {
            self::assertFileExists($dir.'/ready');
            DB::statement('SET SESSION lock_wait_timeout = 13'); DB::statement('SET SESSION innodb_lock_wait_timeout = 17');
            $start=microtime(true); $failure=null;
            try { $this->migration()->down(); } catch (\Throwable $e) { $failure=$e; }
            self::assertNotNull($failure,'This read of a WRITE-locked ledger must fail closed');
            self::assertStringContainsString('lock wait timeout',strtolower($failure->getMessage()));
            self::assertLessThan(12,microtime(true)-$start);
            self::assertSame(13,(int)DB::selectOne('SELECT @@SESSION.lock_wait_timeout AS value')->value);
            self::assertSame(17,(int)DB::selectOne('SELECT @@SESSION.innodb_lock_wait_timeout AS value')->value);
        } finally { file_put_contents($dir.'/release','release'); pcntl_waitpid($pid,$status); }
        self::assertSame(0,pcntl_wexitstatus($status));
        self::assertTrue(Schema::hasTable('warranty_issuances')); self::assertTrue(Schema::hasTable('warranty_expiry_deliveries'));
        foreach (glob($dir.'/*')?:[] as $f) unlink($f); rmdir($dir);
    }

    public function test_preexisting_partial_ledger_is_rejected_without_repair_or_history_change(): void
    {
        $this->migration()->down();
        DB::statement('CREATE TABLE warranty_issuances (id BIGINT UNSIGNED PRIMARY KEY) ENGINE=InnoDB');
        DB::table('warranty_issuances')->insert(['id'=>77]);
        try { $this->migration()->up(); self::fail('Unknown preexisting table must not be accepted'); }
        catch (\RuntimeException $e) { self::assertStringContainsString('column count mismatch',$e->getMessage()); }
        self::assertSame(1,DB::table('warranty_issuances')->count());
        self::assertSame(77,(int)DB::table('warranty_issuances')->value('id'));
        self::assertSame(1,count(Schema::getColumnListing('warranty_issuances')));
        self::assertFalse(Schema::hasTable('warranty_expiry_deliveries'));
    }

'''
assert s.count(needle)==1;s=s.replace(needle,new+needle);p.write_text(s)
print('B797_ATOMIC_CREATE_FAMILY_DIFFERENCE_AND_REAL_LOCK_NEGATIVE_TESTS=ADDED')
