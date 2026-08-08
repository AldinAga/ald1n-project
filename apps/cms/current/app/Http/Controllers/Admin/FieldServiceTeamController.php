<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFieldServiceTeamRequest;
use App\Http\Requests\UpdateFieldServiceTeamRequest;
use App\Models\FieldServiceTeam;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class FieldServiceTeamController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $teams = FieldServiceTeam::query()
            ->withCount(['workOrders as active_work_orders_count' => static fn ($query) => $query->whereIn('status', ['planned', 'en_route', 'on_site'])])
            ->when($q !== '', static fn ($query) => $query->where(function ($nested) use ($q): void {
                $needle = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
                $nested->where('name', 'like', $needle)->orWhere('code', 'like', $needle)
                    ->orWhere('contact_person', 'like', $needle)->orWhere('vehicle_registration', 'like', $needle);
            }))
            ->orderByDesc('is_active')->orderBy('name')->paginate(40)->withQueryString();

        return view('admin.field-operations.teams', [
            'teams' => $teams,
            'types' => FieldServiceTeam::typeLabels(),
            'q' => $q,
        ]);
    }

    public function store(StoreFieldServiceTeamRequest $request, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validated();
        $team = FieldServiceTeam::query()->create($data + [
            'is_active' => $request->boolean('is_active'),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);
        $audit->log('field_team.created', 'Kreirana terenska ekipa '.$team->name, $team, null, $team->toArray(), null, $request->user());
        return back()->with('status', 'Terenska ekipa je kreirana.');
    }

    public function update(UpdateFieldServiceTeamRequest $request, FieldServiceTeam $team, AuditLogger $audit): RedirectResponse
    {
        if (!$request->boolean('is_active') && $team->is_active
            && $team->workOrders()->whereIn('status', ['planned', 'en_route', 'on_site'])->exists()) {
            throw ValidationException::withMessages(['is_active' => 'Ekipa ima aktivne radne naloge. Prvo ih prerasporedite ili završite.']);
        }
        $before = $team->toArray();
        $team->fill($request->validated());
        $team->is_active = $request->boolean('is_active');
        $team->updated_by = $request->user()->id;
        $team->save();
        $audit->log('field_team.updated', 'Ažurirana terenska ekipa '.$team->name, $team, $before, $team->toArray(), null, $request->user());
        return back()->with('status', 'Terenska ekipa je ažurirana.');
    }

    public function destroy(Request $request, FieldServiceTeam $team, AuditLogger $audit): RedirectResponse
    {
        if ($team->workOrders()->whereIn('status', ['planned', 'en_route', 'on_site'])->exists()) {
            throw ValidationException::withMessages(['team' => 'Ekipa ima aktivne radne naloge. Prvo ih prerasporedite ili završite.']);
        }
        $before = $team->toArray();
        $team->forceFill(['is_active' => false, 'updated_by' => $request->user()->id])->save();
        $audit->log('field_team.deactivated', 'Deaktivirana terenska ekipa '.$team->name, $team, $before, $team->toArray(), null, $request->user());
        return back()->with('status', 'Terenska ekipa je deaktivirana.');
    }
}
