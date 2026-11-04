<?php
declare(strict_types=1);
require dirname(__DIR__,4).'/apps/cms/current/app/Support/PublicRetailCartReplaySnapshot.php';
use App\Support\PublicRetailCartReplaySnapshot as Snap;
$tests=0;
function validateReplay(mixed $input, ?array $expected): void {
    global $tests;
    try {
        $actual=Snap::validated($input);
        if ($expected===null || $actual!==$expected) throw new RuntimeException('Unexpected replay result');
    } catch (InvalidArgumentException $e) {
        if ($expected!==null) throw $e;
    }
    $tests++;
}
validateReplay([19=>1,2=>3],[2=>3,19=>1]);
validateReplay(array_fill_keys(range(1,25),20),array_fill_keys(range(1,25),20));
foreach (['bad',[],array_fill_keys(range(1,26),1),[0=>1],[-1=>1],['001'=>1],[2=>0],[2=>21],[2=>'2'],[2=>2.0],[2=>true],[2=>['q'=>2]],[3=>1,4=>'x']] as $v) validateReplay($v,null);
echo "B1071_REPLAY_CASES=$tests\n";
