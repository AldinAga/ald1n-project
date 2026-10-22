<?php
declare(strict_types=1);
// Read-only CLI render smoke. Never logs HTML, credentials, or customer data.
function b794_readonly_render(string $cms): void
{
    $loader=require $cms.'/vendor/autoload.php';
    $app=require $cms.'/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $connection=Illuminate\Support\Facades\DB::connection();
    if($connection->getDriverName()!=='mysql') throw new RuntimeException('RENDER_REQUIRES_PRODUCTION_MYSQL_FAMILY_READONLY_TRANSACTION');
    $connection->unprepared('SET TRANSACTION READ ONLY');
    $connection->beginTransaction();
    try {
        $actor=App\Models\User::query()->where('status','active')->whereHas('role',static fn($q)=>$q->where('slug','superadmin'))->first();
        if(!$actor) throw new RuntimeException('READONLY_RENDER_ACTIVE_SUPERADMIN_NOT_FOUND');
        Illuminate\Support\Facades\Auth::setUser($actor);
        $session=new Illuminate\Session\Store('b794-readonly',new Illuminate\Session\ArraySessionHandler(120));
        $session->start();
        Illuminate\Support\Facades\View::share('errors',new Illuminate\Support\ViewErrorBag());
        $cases=[
            ['name'=>'dashboard','path'=>'/','markers'=>['data-dashboard-command-row','ald1n-ui-refinement.css']],
            ['name'=>'users','path'=>'/admin/users','markers'=>['data-user-editor-sections','access-control-user-avatar']],
        ];
        $product=App\Models\Product::query()->publiclyVisible()->first();
        if(!$product) throw new RuntimeException('READONLY_RENDER_VISIBLE_PRODUCT_NOT_FOUND');
        $cases[]=['name'=>'product','path'=>'/catalog/'.rawurlencode((string)$product->slug),'markers'=>['data-product-gallery','build16-product-summary','ald1n-ui-refinement.css']];
        foreach($cases as $case) {
            $request=Illuminate\Http\Request::create('https://cms.ald1n.com'.$case['path'],'GET');
            $request->setUserResolver(static fn()=>$actor);
            $request->setLaravelSession($session);
            $app->instance('request',$request);
            Illuminate\Support\Facades\URL::setRequest($request);
            $route=$app['router']->getRoutes()->match($request);
            $route->bind($request);
            $request->setRouteResolver(static fn()=>$route);
            $app['router']->substituteBindings($route);
            $view=$route->run();
            if(!($view instanceof Illuminate\Contracts\View\View)) throw new RuntimeException('READONLY_RENDER_NOT_VIEW '.$case['name']);
            $html=$view->render();
            foreach($case['markers'] as $marker) if(!str_contains($html,$marker)) throw new RuntimeException('READONLY_RENDER_MARKER_MISSING '.$case['name'].' '.$marker);
            echo 'CLI_AUTHENTICATED_RENDER=PASS page='.$case['name'].' html_not_logged=YES business_writes=PROHIBITED_BY_READONLY_TRANSACTION' . "\n";
        }
    } finally {
        if($connection->transactionLevel()>0) $connection->rollBack();
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    try { b794_readonly_render(dirname(__DIR__)); exit(0); }
    catch (Throwable $error) {
        $message = $error->getMessage();
        $safe = preg_match('/\A(?:READONLY_RENDER_[A-Z_]+|RENDER_REQUIRES_[A-Z_]+)(?: [A-Za-z0-9_.-]+){0,2}\z/D', $message) === 1
            ? $message : 'DETAILS_REDACTED fingerprint='.hash('sha256', $message);
        fwrite(STDERR, 'READONLY_RENDER=FAIL '.get_class($error).' '.$safe."\n");
        exit(1);
    }
}
