<?php
declare(strict_types=1);
require_once __DIR__.'/../SourceManifestValidator.php';

$passed=0;
function expectManifest(string $label, string $root, array $pins, array $expected, bool $allow): void
{
    global $passed;
    $threw=false;
    try {
        $count=SourceManifestValidator::verify($root,$pins,$expected);
        if ($allow && $count!==count($expected)) {
            throw new RuntimeException("B1073_WRONG_COUNT_{$label}");
        }
    } catch (InvalidArgumentException $e) {
        $threw=true;
    }
    if ($threw===$allow) {
        throw new RuntimeException("B1073_FAILED_{$label}: expected ".($allow?'ALLOW':'DENY')." got ".($threw?'DENY':'ALLOW'));
    }
    $passed++;
    echo "PASS_B1073 $label\n";
}
$root=sys_get_temp_dir().'/b1073-manifest-'.bin2hex(random_bytes(6));
$real=$root.'/real';
mkdir($root,0700,true);
mkdir($real.'/isolated/candidate',0700,true);
file_put_contents($real.'/isolated/candidate/first.php','<?php echo 1;');
file_put_contents($real.'/isolated/candidate/second.php','<?php echo 2;');
$isolated=$real.'/isolated';
$a='candidate/first.php'; $b='candidate/second.php';
$ha=hash_file('sha256',$isolated.'/'.$a);$hb=hash_file('sha256',$isolated.'/'.$b);
$all=[$a=>$ha,$b=>$hb]; $expected=[$a,$b];
try {
    expectManifest('exact_complete', $isolated,$all,$expected,true);
    expectManifest('subset_pins_rejected',$isolated,[$a=>$ha],$expected,false);
    expectManifest('subset_expected_rejected',$isolated,[$a=>$ha],[$a],false);
    expectManifest('extra_expected_file_rejected',$isolated,$all,[$a,$b,'candidate/third.php'],false);
    expectManifest('missing_file_rejected',$isolated,[$a=>$ha,$b=>$hb],[$a,$b,'candidate/third.php'],false);
    expectManifest('duplicate_expected_rejected',$isolated,$all,[$a,$a,$b],false);
    expectManifest('hash_drift_rejected',$isolated,[$a=>str_repeat('0',64),$b=>$hb],$expected,false);
    symlink($real,$root.'/parent-link');
    expectManifest('symlink_ancestor_rejected',$root.'/parent-link/isolated',$all,$expected,false);
    unlink($root.'/parent-link');
    symlink($isolated.'/candidate/first.php',$isolated.'/candidate/hijack.php');
    expectManifest('unlisted_symlink_rejected',$isolated,$all,$expected,false);
    unlink($isolated.'/candidate/hijack.php');
    echo "B1073_MANIFEST_CASES=$passed\n";
} finally {
    @unlink($isolated.'/candidate/first.php');
    @unlink($isolated.'/candidate/second.php');
    @rmdir($isolated.'/candidate');
    @rmdir($isolated);@rmdir($real);@rmdir($root);
}
