<?php
declare(strict_types=1);
require __DIR__.'/../ScopedQaGrantValidator.php';
require __DIR__.'/../SourceManifestValidator.php';
$ok=0;
function qa_case(string $name, callable $action, bool $allowed, int &$ok): void {
    try { $action(); if (!$allowed) throw new RuntimeException("Uncaught risky case: $name"); }
    catch (InvalidArgumentException $e) { if ($allowed) throw $e; }
    $ok++; echo "PASS QA $name\n";
}
$db='icaffeco_ald1n_b913_test';
$escaped=str_replace('_','\\_',$db);
$u='`icaffeco_b913qa`@`localhost`';
$usage="GRANT USAGE ON *.* TO $u";
$good="GRANT ALL PRIVILEGES ON `$escaped`.* TO $u";
$unsafe="GRANT ALL PRIVILEGES ON `$db`.* TO $u";
$G=static fn(array $rows)=>ScopedQaGrantValidator::assertExactQaGrants($rows);
qa_case('escaped_scope_valid',fn()=> $G([$usage,$good]),true,$ok);
qa_case('order_independent',fn()=> $G([$good,$usage]),true,$ok);
qa_case('unescaped_underscore_wildcard',fn()=> $G([$usage,$unsafe]),false,$ok);
qa_case('global_privilege',fn()=> $G([$good,"GRANT ALL PRIVILEGES ON *.* TO $u"]),false,$ok);
qa_case('other_database',fn()=> $G([$usage,"GRANT ALL PRIVILEGES ON `production`.* TO $u"]),false,$ok);
qa_case('role_assignment',fn()=> $G([$usage,$good,'GRANT `ops`@`%` TO '.$u]),false,$ok);
qa_case('grant_option',fn()=> $G([$usage,$good.' WITH GRANT OPTION']),false,$ok);
qa_case('table_scope',fn()=> $G([$usage,"GRANT SELECT ON `$escaped`.`products` TO $u"]),false,$ok);
qa_case('missing_scope',fn()=> $G([$usage]),false,$ok);
qa_case('duplicate_scope',fn()=> $G([$usage,$good,$good]),false,$ok);
qa_case('foreign_account',fn()=> $G([$usage,str_replace('icaffeco_b913qa','root',$good)]),false,$ok);
qa_case('malformed_data',fn()=> $G([$usage,42]),false,$ok);
$folder=sys_get_temp_dir().'/b1071-qa-'.bin2hex(random_bytes(5));
mkdir($folder);mkdir($folder.'/candidate');
file_put_contents($folder.'/candidate/good.php','<?php echo 1;');
$hash=hash_file('sha256',$folder.'/candidate/good.php');
$M=static fn(array $pins)=>SourceManifestValidator::verify($folder,$pins,['candidate/good.php']);
qa_case('valid_source_manifest',fn()=> $M(['candidate/good.php'=>$hash]),true,$ok);
qa_case('empty_source_manifest',fn()=> $M([]),false,$ok);
qa_case('missing_source_path',fn()=> $M(['candidate/absent.php'=>$hash]),false,$ok);
qa_case('hash_drift',fn()=> $M(['candidate/good.php'=>str_repeat('0',64)]),false,$ok);
qa_case('directory_escape',fn()=> $M(['../candidate/good.php'=>$hash]),false,$ok);
qa_case('absolute_path',fn()=> $M(['/etc/passwd'=>$hash]),false,$ok);
qa_case('hidden_secret_path',fn()=> $M(['candidate/.env'=>$hash]),false,$ok);
qa_case('outside_allowlist',fn()=> $M(['production/good.php'=>$hash]),false,$ok);
symlink($folder.'/candidate/good.php',$folder.'/candidate/link.php');
qa_case('symlink_rejected',fn()=> $M(['candidate/link.php'=>$hash]),false,$ok);
unlink($folder.'/candidate/link.php');unlink($folder.'/candidate/good.php');rmdir($folder.'/candidate');rmdir($folder);
echo "QA_TESTS=$ok\n";
