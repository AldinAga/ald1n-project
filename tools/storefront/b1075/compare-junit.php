<?php
declare(strict_types=1);
function analyze(string $file): array {
    $xml=simplexml_load_file($file,SimpleXMLElement::class,LIBXML_NONET);
    if(!$xml)throw new RuntimeException('Unreadable JUnit');
    $rows=$xml->xpath('//testcase');
    $ids=[];$failed=[];$skipped=[];$errors=0;$failures=0;
    foreach($rows as $row){
        $class=str_replace('\\','.',trim((string)$row['classname']));
        $name=trim((string)$row['name']);
        if($class===''||$name==='')throw new RuntimeException('Missing JUnit identity');
        $id=$class.'::'.$name;
        if(isset($ids[$id]))throw new RuntimeException('Duplicate JUnit identity: '.$id);
        $ids[$id]=true;
        if(count($row->error)){++$errors;$failed[$id]=(string)$row->error[0];}
        elseif(count($row->failure)){++$failures;$failed[$id]=(string)$row->failure[0];}
        if(count($row->skipped))$skipped[]=$id;
    }
    return ['count'=>count($rows),'errors'=>$errors,'failures'=>$failures,
        'failedCount'=>count($failed),'skippedCount'=>count($skipped),
        'ids'=>array_keys($ids),'failed'=>$failed,'skipped'=>$skipped];
}
$before=analyze($argv[1]);$after=analyze($argv[2]);
$new=array_values(array_diff(array_keys($after['failed']),array_keys($before['failed'])));
$resolved=array_values(array_diff(array_keys($before['failed']),array_keys($after['failed'])));
$missing=array_values(array_diff($before['ids'],$after['ids']));
$result=['before'=>$before,'after'=>$after,'newFailed'=>$new,'resolved'=>$resolved,'missingOriginalCases'=>$missing];
file_put_contents($argv[3],json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
foreach(['before','after'] as $key)echo $key.': '.json_encode(array_intersect_key($result[$key],array_flip(['count','errors','failures','failedCount','skippedCount']))).PHP_EOL;
echo 'newFailed='.count($new).' resolved='.count($resolved).' missingOriginalCases='.count($missing).PHP_EOL;
