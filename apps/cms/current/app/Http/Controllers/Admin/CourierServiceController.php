<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourierService;
use App\Services\CourierDirectoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class CourierServiceController extends Controller
{
    public function index(Request $request, CourierDirectoryService $directory): View
    {
        $this->assertSuperAdmin($request);
        return view('admin.settings.couriers', ['couriers' => $directory->all()]);
    }

    public function store(Request $request, CourierDirectoryService $directory): RedirectResponse
    {
        $this->assertSuperAdmin($request);
        $data = $this->validated($request);
        $directory->create($data, $request->user());
        return back()->with('status', 'Kurirska služba je dodata.');
    }

    public function update(Request $request, CourierService $courier, CourierDirectoryService $directory): RedirectResponse
    {
        $this->assertSuperAdmin($request);
        $data = $this->validated($request, $courier);
        $directory->update($courier, $data, $request->user());
        return back()->with('status', 'Kurirska služba je sačuvana.');
    }

    /** @return array<string,mixed> */
    private function validated(Request $request, ?CourierService $courier = null): array
    {
        $nameRule = Rule::unique('courier_services', 'name');
        if ($courier instanceof CourierService) $nameRule->ignore($courier->id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', $nameRule],
            'tracking_url' => ['required', 'string', 'url', 'max:500'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['required', 'boolean'],
            'is_default' => ['required', 'boolean'],
        ]);
        if (!str_starts_with(strtolower(trim((string) $data['tracking_url'])), 'https://')) {
            throw ValidationException::withMessages(['tracking_url' => 'Tracking URL mora koristiti HTTPS.']);
        }
        return $data;
    }

    private function assertSuperAdmin(Request $request): void
    {
        abort_unless($request->user()?->hasRole('superadmin'), 403);
    }
}
