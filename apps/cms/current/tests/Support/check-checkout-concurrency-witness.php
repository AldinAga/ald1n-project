<?php
declare(strict_types=1);
require __DIR__.'/CheckoutConcurrencyWitness.php';
use Tests\Support\CheckoutConcurrencyWitness as Witness;
$product=static fn(int $id)=>(object)['ID'=>$id,'INFO'=>'select * from `products` where id = 3 for update'];
$attempt=static fn(int $id)=>(object)['ID'=>$id,'INFO'=>'insert into `public_checkout_attempts` (`key_hash`) values (?) on duplicate key update `key_hash` = values(`key_hash`)'];
$cases=[
    'two last-unit product connections'=>[[$product(10),$product(11)],'lastunit',true],
    'observed distinct-nonce mixed lock stage'=>[[$product(10),$attempt(11)],'lastunit',true],
    'same-nonce duplicate stage'=>[[$product(10),$attempt(11)],'duplicate',true],
    'only one product transaction'=>[[$product(10)],'lastunit',false],
    'only insertion transactions'=>[[$attempt(10),$attempt(11)],'lastunit',false],
    'single connection cannot impersonate concurrency'=>[[$product(10),$attempt(10)],'lastunit',false],
    'duplicate requires insert stage'=>[[$product(10),$product(11)],'duplicate',false],
    'unrelated observer row ignored'=>[[$product(10),(object)['ID'=>11,'INFO'=>'select INFO from information_schema.PROCESSLIST']],'lastunit',false],
    'duplicate same ID denied'=>[[$product(10),$attempt(10)],'duplicate',false],
    'unknown mode denied'=>[[$product(10),$attempt(11)],'unknown',false],
    'empty snapshot denied'=>[[],'lastunit',false],
    'zero process id rejected'=>[[$product(0),$attempt(11)],'lastunit',false],
];
$failures=0;
foreach($cases as $name=>[$rows,$mode,$expected]){
    if(Witness::inspect($rows,$mode)['ready']!==$expected){fwrite(STDERR,'FAIL '.$name."\n");$failures++;}
}
echo 'B804_WITNESS_CASES='.count($cases).' FAILED='.$failures."\n";
exit($failures===0?0:1);
