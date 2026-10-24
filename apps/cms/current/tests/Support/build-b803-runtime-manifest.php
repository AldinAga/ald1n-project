<?php
declare(strict_types=1);
// Tracked runtime files only; never scans production directories or secrets.
if (getenv('APP_ENV')!=='testing' || !isset($argv[1])) exit(45);
$base='c4a5f060cc00b5d1be0e4d8068309524a4b99c99';
$head=trim((string)shell_exec('git rev-parse HEAD'));
if (!preg_match('/^[a-f0-9]{40}$/D',$head)) exit(46);
$prefix='apps/cms/current/';
$rows=[];
$paths=explode("\n",trim((string)shell_exec('git diff --name-only '.escapeshellarg($base).' HEAD -- apps/cms/current')));
foreach ($paths as $path) {
    if (!str_starts_with($path,$prefix)) continue;
    $relative=substr($path,strlen($prefix));
    if (str_starts_with($relative,'tests/') || str_starts_with($relative,'bin/')) continue;
    if (!is_file($path) || is_link($path)) throw new RuntimeException('Unexpected runtime deletion or symlink.');
    $old=trim((string)shell_exec('git rev-parse --verify '.escapeshellarg($base.':'.$path).' 2>/dev/null'));
    if ($old!=='' && !preg_match('/^[a-f0-9]{40}$/D',$old)) throw new RuntimeException('Invalid predecessor hash.');
    $bytes=file_get_contents($path);
    $rows[]=['path'=>$relative,'predecessor_blob'=>$old!==''?$old:null,
        'candidate_blob'=>sha1('blob '.strlen($bytes)."\0".$bytes),
        'candidate_sha256'=>hash('sha256',$bytes),'bytes'=>strlen($bytes)];
}
foreach (['app/Services/BackupService.php','app/Console/Commands/BackupVerifyCommand.php'] as $relative) {
    $bytes=file_get_contents($prefix.$relative);
    $helpers[$relative]=sha1('blob '.strlen($bytes)."\0".$bytes);
}
$out=['base'=>$base,'head'=>$head,'files'=>$rows,'backup_helpers'=>$helpers??[]];
file_put_contents($argv[1],json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n");
echo 'RUNTIME_MANIFEST_FILES='.count($rows)."\n";
