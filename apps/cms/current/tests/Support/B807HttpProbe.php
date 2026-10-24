<?php
declare(strict_types=1);
// Actual HTTP kernel, CSRF and throttle; only disposable localhost.
if (getenv('B807_ISOLATED')!=='1' || getenv('APP_ENV')!=='testing'
    || getenv('DB_DATABASE')!=='ald1n_test' || !isset($argv[1],$argv[2])
    || !str_starts_with((string)$argv[1],(string)getenv('RUNNER_TEMP'))
    || !str_starts_with((string)$argv[2],(string)getenv('RUNNER_TEMP'))) {
    fwrite(STDERR,"B807_HOLD_NOT_DISPOSABLE\n"); exit(70);
}
$productId=trim((string)file_get_contents($argv[1]));
if (!ctype_digit($productId) || (int)$productId<1) exit(71);
$cookie=tempnam((string)getenv('RUNNER_TEMP'),'b807-cookie-');
if ($cookie===false) exit(72);
chmod($cookie,0600);
$checks=[];
$http=static function(string $method,string $path,array $data=[]) use ($cookie): array {
    if (!str_starts_with($path,'/') || str_contains($path,'://')) throw new RuntimeException('Wrong target');
    $ch=curl_init('http://127.0.0.1:8873'.$path);
    if ($ch===false) throw new RuntimeException('Could not allocate cURL');
    curl_setopt_array($ch,[
        CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,
        CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>15,CURLOPT_FOLLOWLOCATION=>false,
        CURLOPT_COOKIEFILE=>$cookie,CURLOPT_COOKIEJAR=>$cookie,
        CURLOPT_USERAGENT=>'Ald1n-B807-synthetic-http-security',
        CURLOPT_CUSTOMREQUEST=>$method,
    ]);
    if (!in_array($method,['GET','HEAD'],true)) {
        curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($data,'','&',PHP_QUERY_RFC3986));
        curl_setopt($ch,CURLOPT_HTTPHEADER,['Content-Type: application/x-www-form-urlencoded']);
    }
    $raw=curl_exec($ch);
    $code=curl_getinfo($ch,CURLINFO_RESPONSE_CODE);
    $headerSize=curl_getinfo($ch,CURLINFO_HEADER_SIZE);
    $error=curl_error($ch);
    curl_close($ch);
    if (!is_string($raw)) throw new RuntimeException('HTTP transport failed '.$error);
    $headers=[];
    foreach (explode("\r\n",substr($raw,0,$headerSize)) as $line) {
        if (!str_contains($line,':')) continue;
        [$key,$value]=explode(':',$line,2);
        $headers[strtolower(trim($key))]=trim($value);
    }
    return ['code'=>$code,'headers'=>$headers,'body'=>substr($raw,$headerSize)];
};
$check=static function(bool $yes,string $name) use (&$checks): void {
    $checks[$name]=$yes?'PASS':'FAIL';
    if (!$yes) throw new RuntimeException('B807_CHECK_FAILED='.$name);
};
try {
    $catalog=$http('GET','/katalog');
    $check($catalog['code']===200,'public_catalog_http');
    $check(preg_match('/name="_token"\s+value="([^"]+)"/',$catalog['body'],$match)===1,
        'csrf_in_real_public_form');
    $token=$match[1];
    $cart=$http('GET','/korpa');
    $check($cart['code']===200 && str_contains(strtolower((string)($cart['headers']['cache-control']??'')),'no-store'),
        'cart_no_store');
    $check($http('POST','/korpa/dodaj/'.$productId,['quantity'=>1])['code']===419,
        'cart_no_csrf_419');
    $check($http('POST','/korpa/dodaj/'.$productId,['_token'=>str_repeat('f',40),'quantity'=>1])['code']===419,
        'cart_bad_csrf_419');
    $check($http('GET','/korpa/dodaj/'.$productId)['code']===405,
        'unsafe_get_cannot_add_cart');
    $empty=$http('GET','/korpa');
    $check($empty['code']===200 && str_contains($empty['body'],'Korpa je prazna'),
        'failed_csrf_has_no_cart_mutation');
    $check($http('POST','/korpa/dodaj/'.$productId,['_token'=>$token,'quantity'=>1])['code']===302,
        'valid_csrf_can_add_cart');
    $cart=$http('GET','/korpa');
    $check($cart['code']===200 && str_contains($cart['body'],'Synthetic B807 product'),
        'real_cookie_session_persists');
    $checkout=$http('GET','/porucivanje');
    $check($checkout['code']===200 && str_contains(strtolower((string)($checkout['headers']['cache-control']??'')),'no-store'),
        'checkout_no_store');
    $check($http('POST','/porucivanje',['shipping_full_name'=>'Synthetic'])['code']===419,
        'checkout_no_csrf_419');
    $check($http('PUT','/admin/settings/public-storefront',['terms_url'=>'https://attacker.example.test'])['code']===419,
        'merchant_update_no_csrf_419');
    $denied=$http('PUT','/admin/settings/public-storefront',['_token'=>$token,'terms_url'=>'https://attacker.example.test']);
    $check(in_array($denied['code'],[302,401,403],true),'anonymous_merchant_update_denied');
    for ($i=0;$i<31;$i++) {
        $private=$http('GET','/porudzbina/'.str_repeat('a',64));
        $expected=$i===30?429:404;
        if ($private['code']!==$expected) {
            throw new RuntimeException('B807_PRIVATE_RATE index='.$i.' got='.$private['code'].' expected='.$expected);
        }
    }
    $check(isset($private['headers']['retry-after']),'throttle_retry_after');
    $check(str_contains(strtolower((string)($private['headers']['referrer-policy']??'')),'no-referrer'),
        'private_error_referrer_policy');
    $check($private['code']===429,'private_capability_throttle_enforced');
    $db=new PDO('mysql:host=127.0.0.1;dbname=ald1n_test','ald1n_test','ald1n_test',[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    ]);
    foreach(['orders','order_payments','stock_movements','public_checkout_attempts'] as $table) {
        $num=(int)$db->query('SELECT COUNT(*) FROM '.chr(96).$table.chr(96))->fetchColumn();
        $check($num===0,'zero_'.$table.'_writes');
    }
    file_put_contents($argv[2],json_encode([
        'target'=>'disposable localhost','checks'=>$checks,'count'=>count($checks),
        'result'=>'PASS','production_writes'=>0,'source_sha'=>getenv('B807_SOURCE_SHA')?:'unknown'
    ],JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n");
    echo 'B807_REAL_HTTP=PASS CHECKS='.count($checks).' CSRF=PASS THROTTLE=PASS NO_SALE=PASS'."\n";
} catch(Throwable $e) {
    file_put_contents($argv[2],json_encode([
        'target'=>'disposable localhost','checks'=>$checks,'error'=>$e->getMessage(),'result'=>'FAIL',
    ],JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n");
    fwrite(STDERR,'B807_HTTP_FAIL '.$e->getMessage()."\n"); exit(75);
} finally { @unlink($cookie); }
