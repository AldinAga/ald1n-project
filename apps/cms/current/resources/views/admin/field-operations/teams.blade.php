@extends('layouts.app')
@section('title', 'Terenske ekipe')
@section('content')
<a class="back-link" href="{{ route('admin.field-operations.index') }}">← Terenske operacije</a>
@include('admin.settings.partials.context-nav', ['settingsSection' => 'Poslovna pravila', 'settingsContextLabel' => 'Terenske operacije', 'settingsContextUrl' => route('admin.field-operations.index')])
<div class="page-heading"><div><span class="eyebrow">Resursi</span><h1>Terenske ekipe i servisni partneri</h1><p>Kontakti, vozila, teritorije i raspoloživost izvođača.</p></div></div>
<div class="settings-grid">
<section class="panel form-section"><h2>Dodaj ekipu ili partnera</h2><form method="post" action="{{ route('admin.field-service-teams.store') }}" class="form-grid">@csrf
    <label><span>Šifra</span><input name="code" required maxlength="50" value="{{ old('code') }}" placeholder="SERVIS-01"></label>
    <label><span>Naziv</span><input name="name" required maxlength="190" value="{{ old('name') }}"></label>
    <label><span>Tip</span><select name="team_type">@foreach($types as $value=>$label)<option value="{{ $value }}" @selected(old('team_type','internal')===$value)>{{ $label }}</option>@endforeach</select></label>
    <label><span>Kontakt osoba</span><input name="contact_person" maxlength="190" value="{{ old('contact_person') }}"></label>
    <label><span>Telefon</span><input name="phone" maxlength="80" value="{{ old('phone') }}"></label>
    <label><span>E-mail</span><input type="email" name="email" maxlength="190" value="{{ old('email') }}"></label>
    <label><span>Registracija vozila</span><input name="vehicle_registration" maxlength="80" value="{{ old('vehicle_registration') }}"></label>
    <label><span>Područje rada</span><input name="service_area" maxlength="255" value="{{ old('service_area') }}" placeholder="Beograd i okolina"></label>
    <label class="checkbox-field"><input type="checkbox" name="is_active" value="1" checked><span>Aktivna ekipa</span></label>
    <label><span>Interna napomena</span><textarea name="notes" maxlength="5000">{{ old('notes') }}</textarea></label>
    <button class="button button-primary" type="submit">Sačuvaj ekipu</button>
</form></section>
<section class="panel form-section"><div class="section-heading-row"><div><h2>Postojeće ekipe</h2><p class="muted">Deaktivirana ekipa ostaje u istoriji ranijih radnih naloga.</p></div><form method="get"><input name="q" value="{{ $q }}" placeholder="Pretraga..."></form></div>
<div class="field-team-list">
@forelse($teams as $team)<details class="field-team-card" @if($errors->any() && (int)old('team_id')===(int)$team->id) open @endif><summary><div><strong>{{ $team->name }}</strong><small>{{ $team->code }} · {{ $types[$team->team_type] ?? $team->team_type }} · {{ $team->active_work_orders_count }} aktivnih naloga</small></div><span class="status-badge {{ $team->is_active?'status-active':'status-inactive' }}">{{ $team->is_active?'Aktivna':'Neaktivna' }}</span></summary><form method="post" action="{{ route('admin.field-service-teams.update',$team) }}" class="form-grid two-columns">@csrf @method('PUT')<input type="hidden" name="team_id" value="{{ $team->id }}">
    <label><span>Šifra</span><input name="code" required maxlength="50" value="{{ $team->code }}"></label><label><span>Naziv</span><input name="name" required maxlength="190" value="{{ $team->name }}"></label>
    <label><span>Tip</span><select name="team_type">@foreach($types as $value=>$label)<option value="{{ $value }}" @selected($team->team_type===$value)>{{ $label }}</option>@endforeach</select></label><label><span>Kontakt</span><input name="contact_person" maxlength="190" value="{{ $team->contact_person }}"></label>
    <label><span>Telefon</span><input name="phone" maxlength="80" value="{{ $team->phone }}"></label><label><span>E-mail</span><input type="email" name="email" maxlength="190" value="{{ $team->email }}"></label>
    <label><span>Registracija</span><input name="vehicle_registration" maxlength="80" value="{{ $team->vehicle_registration }}"></label><label><span>Područje</span><input name="service_area" maxlength="255" value="{{ $team->service_area }}"></label>
    <label class="checkbox-field"><input type="checkbox" name="is_active" value="1" @checked($team->is_active)><span>Aktivna</span></label><label class="full-width"><span>Napomena</span><textarea name="notes" maxlength="5000">{{ $team->notes }}</textarea></label>
    <button class="button button-primary" type="submit">Sačuvaj izmene</button>
</form>@if($team->is_active)<form method="post" action="{{ route('admin.field-service-teams.destroy',$team) }}" class="field-team-disable-form">@csrf @method('DELETE')<button class="button button-danger button-small" type="submit">Deaktiviraj</button></form>@endif</details>
@empty<p class="muted">Još nema definisanih terenskih ekipa.</p>@endforelse
</div>{{ $teams->links() }}</section>
</div>
@endsection
