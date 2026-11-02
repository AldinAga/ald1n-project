<?php
declare(strict_types=1);

/**
 * Fully executable PHP 8.4 isolated contract tests for B1048 ONLY.
 * This is NOT a Laravel/MariaDB/real-browser acceptance suite.
 */
$cms = getenv('B1049_TEST_CMS_ROOT');
if (!$cms || !is_file($cms.'/app/Support/PublicCatalogReadOnlyGate.php')) {
    fwrite(STDERR, "HOLD_B1049_DISPOSABLE_CMS_REQUIRED\n"); exit(2);
}
require $cms.'/app/Support/PublicCatalogReadOnlyGate.php';
use App\Support\PublicCatalogReadOnlyGate as Gate;

$pass=0;
function check(bool $result, string $why): void {
    global $pass;
    if (!$result) { fwrite(STDERR,"B1048_FAIL:$why\n");exit(4); }
    $pass++;
}
function expect(array $routes, bool $want, string $label, array $flags=[true,true,false,false,false]): void {
    $actual=Gate::permits(...array_merge($flags,[$routes]));
    check($actual===$want,$label.' expected '.($want?'YES':'NO').' got '.($actual?'YES':'NO'));
}
function named(string $name,string $uri,string $throttle,string $method='GET',bool $expanded=false): array {
    return [
        'name'=>$name,'uri'=>$uri,'methods'=>[$method,'HEAD'],
        'middleware'=>['web', $expanded ? 'Illuminate\\Routing\\Middleware\\ThrottleRequests:'.$throttle : 'throttle:'.$throttle],
    ];
}
$good=[
    named('public-shop.catalog','katalog','public-shop-browse'),
    named('public-shop.media','katalog/slika/{image}','public-shop-images'),
    named('public-shop.show','katalog/{slug}','public-shop-browse'),
];
expect($good,true,'exact 3 GET-only M13 routes and both new flags');
// 2^5 switch combinations: only catalogue environment and DB flags ON
// together with ALL original full-store/checkout flags OFF is accepted.
for($k=0;$k<32;$k++){
    $f=[]; for($i=0;$i<5;$i++) $f[]=boolval($k & (1<<$i));
    expect($good,$f===[true,true,false,false,false],'switch matrix '.$k,$f);
}

$cases=[
    ['no routes',[],false],
    ['missing catalog',array_slice($good,1),false],
    ['missing product',array_slice($good,0,2),false],
    ['extra unexpected public route',array_merge($good,[named('public-shop.cart','korpa','public-shop-browse')]),false],
    ['duplicate catalogue',array_merge($good,[$good[0]]),false],
    ['extra unrelated private route',array_merge($good,[named('internal.catalog','catalog','private')]),true],
];
foreach($cases as [$label,$r,$expected])expect($r,$expected,$label);
foreach(['korpa','korpa/dodaj/5','porucivanje','porucivanje/uslovi','registracija','registracija/dodaj'] as $uri){
    expect(array_merge($good,[named('', $uri,'random')]),false,'unnamed dangerous route '.$uri);
}
foreach (['POST','PUT','PATCH','DELETE','OPTIONS'] as $method) {
    $r=$good;$r[0]=named('public-shop.catalog','katalog','public-shop-browse',$method);
    expect($r,false,'method write or non-GET '.$method);
}
$r=$good;$r[2]['methods']=['HEAD'];expect($r,false,'HEAD without GET');
$r=$good;$r[0]['uri']='katalog-old';expect($r,false,'catalog route drift');
$r=$good;$r[1]['uri']='katalog/slika/{wrong}';expect($r,false,'image route drift');
$r=$good;$r[2]['uri']='katalog/{id}';expect($r,false,'detail route drift');
$r=$good;$r[0]['middleware']=['web','throttle:wrong'];expect($r,false,'wrong named limiter');
$r=$good;$r[1]['middleware']=['web'];expect($r,false,'missing media limiter');
$r=$good;$r[0]['methods']='GET|HEAD';expect($r,false,'malformed methods shape');
$r=$good;$r[2]['middleware']='throttle:public-shop-browse';expect($r,false,'malformed middleware shape');
$r=$good;$r[0]['name']='public-shop.checkout';expect($r,false,'checkout route masquerades as catalogue');
$r=$good;$r[1]['uri']='/katalog/slika/{image}';expect($r,true,'leading slash route normalized');
$r=$good;$r[0]['middleware']=['web','Illuminate\\Routing\\Middleware\\ThrottleRequests:public-shop-browse'];
expect($r,true,'Laravel expanded named middleware alias accepted without weakening policy');
$r=$good;$r[1]['middleware']=['web','Illuminate\\Routing\\Middleware\\ThrottleRequests:public-shop-images'];
expect($r,true,'Laravel expanded media middleware accepted');

// Every getter must use its new independent gate, and the legacy shop toggle
// still requires separately approved checkout (not rewritten in B1048).
$root=$cms;
$controller=file_get_contents($root.'/app/Http/Controllers/PublicShopController.php');
$state=file_get_contents($root.'/app/Services/PublicStorefrontStateService.php');
$config=file_get_contents($root.'/config/public_storefront.php');
check(substr_count($controller,'->isCatalogReadOnlyOpen()')===1, 'catalog controller uses independent gate');
check(str_contains($state,"'public_catalog_readonly_open'") && str_contains($config,'PUBLIC_CATALOG_READONLY_ENABLED'),'dual independent catalogue switches');
check(str_contains($state,'config(\'public_storefront.checkout_enabled\')!==true'),'legacy all-store checkout invariant preserved');
check(!str_contains($controller,'function add(') && !str_contains($controller,'function cart('),'read-only controller lacks buy methods');

// A purchase route surfacing after M14 integration must fail M13-only until
// an independent review explicitly revises the integration contract.
$r=$good;$r[]=['name'=>'public-shop.checkout','uri'=>'porucivanje','methods'=>['GET','HEAD'],'middleware'=>[]];
expect($r,false,'future checkout route without POST still holds catalog-only phase');

// B1049 RED proof: B1048 incorrectly allowed an unnamed /katalog/checkout POST.
foreach ([
    ['katalog/checkout', 'POST'],
    ['katalog/checkout', 'GET'],
    ['katalog/other', 'GET'],
    ['katalog/slika/999', 'GET'],
    ['katalog', 'POST'],
    ['checkout', 'POST'],
    ['kupovina', 'GET'],
] as [$uri, $verb]) {
    $unexpected=['name'=>'', 'uri'=>$uri, 'methods'=>[$verb], 'middleware'=>[]];
    expect(array_merge($good,[$unexpected]), false, 'B1049 unnamed route hold '.$verb.' '.$uri);
}
printf("B1049_PURE_PHP_GATE_TESTS=PASS_%d_ASSERTIONS\n",$pass);
