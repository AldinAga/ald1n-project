<?php
declare(strict_types=1);
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PublicStorefrontStateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class PublicStorefrontToggleController extends Controller
{
    public function __invoke(Request $request,PublicStorefrontStateService $store): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('superadmin'),403);
        $data=$request->validate(['enabled'=>['required','in:0,1']]);
        $enabled=(string)$data['enabled']==='1';
        $store->change($enabled,$request->user());
        return redirect()->route('admin.settings.public-storefront.index')
            ->with('status',$enabled?'Javna prodavnica je ON.':'Javna prodavnica je OFF. Postojece porudzbine su sacuvane.');
    }
}
