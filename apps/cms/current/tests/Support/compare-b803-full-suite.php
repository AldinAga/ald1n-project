<?php
declare(strict_types=1);
// CI-only complete-suite accounting. A green comparison is NOT a green full suite.
$dir=$argv[1]??'';
if ($dir==='' || getenv('APP_ENV')!=='testing' || getenv('DB_DATABASE')!=='ald1n_test') exit(40);
$parse=static function (string $path): array {
    if (!is_file($path)) throw new RuntimeException('Missing complete JUnit result.');
    $xml=simplexml_load_file($path);
    if ($xml===false) throw new RuntimeException('Invalid JUnit XML.');
    $cases=$xml->xpath('//testcase')?:[];
    if (count($cases)<300) throw new RuntimeException('Incomplete full-suite execution.');
    $failed=[];
    foreach ($cases as $case) {
        if (isset($case->failure) || isset($case->error)) {
            $failed[]=(string)$case['class'].'::'.(string)$case['name'];
        }
    }
    sort($failed,SORT_STRING);
    return ['cases'=>count($cases),'failed'=>$failed];
};
try {
    $base=$parse($dir.'/baseline-junit.xml');
    $candidate=$parse($dir.'/candidate-junit.xml');
    $new=array_values(array_diff($candidate['failed'],$base['failed']));
    $fixed=array_values(array_diff($base['failed'],$candidate['failed']));
    $result=['baseline'=>$base,'candidate'=>$candidate,'new_failures'=>$new,'fixed_failures'=>$fixed,
        'baseline_rc'=>trim((string)file_get_contents($dir.'/baseline-rc.txt')),
        'candidate_rc'=>trim((string)file_get_contents($dir.'/candidate-rc.txt')),
        'whole_suite_green'=>$candidate['failed']===[],
        'new_failure_gate'=>$new===[]?'PASS':'FAIL'];
    file_put_contents($dir.'/full-suite-comparison.json',json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n");
    echo 'BASELINE_CASES='.$base['cases'].' BASELINE_PROBLEMS='.count($base['failed'])."\n";
    echo 'CANDIDATE_CASES='.$candidate['cases'].' CANDIDATE_PROBLEMS='.count($candidate['failed'])."\n";
    echo 'NEW_FAILURES='.count($new).' WHOLE_SUITE_GREEN='.($candidate['failed']===[]?'YES':'NO')."\n";
    foreach ($new as $failure) echo 'NEW_FAILURE='.$failure."\n";
    exit($new===[]?0:41);
} catch (Throwable $e) {
    fwrite(STDERR,'HOLD_FULL_SUITE_EVIDENCE '.$e->getMessage()."\n");exit(42);
}
