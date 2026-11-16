@extends('layouts.app')
@section('title', 'Terenske ekipe')
@section('content')
<div class="a1-ops">
    <nav aria-label="Putanja">
        <ol class="a1-crumbs">
            <li><a href="{{ route('admin.settings.index') }}">Sva podešavanja</a></li>
            <li><a href="{{ route('admin.field-operations.index') }}">Terenske operacije</a></li>
            <li aria-current="page">Terenske ekipe</li>
        </ol>
    </nav>

    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Resursi</span>
            <h1 class="a1-title">Terenske ekipe i servisni partneri</h1>
            <p class="a1-lead">Kontakti, vozila, teritorije i raspoloživost izvođača.</p>
        </div>
    </header>

    <div class="a1-split">
        <section class="a1-stack" aria-labelledby="ft-list-title">
            <div class="a1-card__title">
                <h2 class="a1-h3" id="ft-list-title">Postojeće ekipe</h2>
                <p class="a1-text">Deaktivirana ekipa ostaje u istoriji ranijih radnih naloga.</p>
            </div>
            <form class="a1-filters" method="get" role="search" aria-label="Pretraga ekipa">
                <div class="a1-filters__fields">
                    <div class="a1-field">
                        <label class="a1-label" for="ft-q">Pretraga</label>
                        <div class="a1-search"><x-icon name="search" :size="20" :shell="true" /><input class="a1-input" id="ft-q" name="q" value="{{ $q }}" placeholder="Pretraga..."></div>
                    </div>
                </div>
                <div class="a1-filters__actions">
                    <button class="a1-btn" type="submit">Pretraži</button>
                    @if($q !== '')<a class="a1-btn a1-btn--quiet" href="{{ route('admin.field-service-teams.index') }}">Resetuj</a>@endif
                </div>
            </form>
            @forelse($teams as $team)
            <details class="a1-acc a1-ow-team" @if($errors->any() && (int)old('team_id')===(int)$team->id) open @endif>
                <summary>
                    <span class="a1-ow-team__title"><strong>{{ $team->name }}</strong><span class="a1-ow-team__sub">{{ $team->code }} · {{ $types[$team->team_type] ?? $team->team_type }} · {{ $team->active_work_orders_count }} aktivnih naloga</span></span>
                    <span class="a1-pill {{ $team->is_active ? 'a1-pill--ok' : 'a1-pill--off' }}">{{ $team->is_active?'Aktivna':'Neaktivna' }}</span>
                </summary>
                <div class="a1-acc__body a1-stack">
                    <form class="a1-form" method="post" action="{{ route('admin.field-service-teams.update',$team) }}">@csrf @method('PUT')<input type="hidden" name="team_id" value="{{ $team->id }}">
                        <div class="a1-row">
                            <div class="a1-field"><label class="a1-label" for="ft-{{ $team->id }}-code">Šifra <span class="a1-req" aria-hidden="true">*</span></label><input class="a1-input" id="ft-{{ $team->id }}-code" name="code" required maxlength="50" value="{{ $team->code }}"></div>
                            <div class="a1-field"><label class="a1-label" for="ft-{{ $team->id }}-name">Naziv <span class="a1-req" aria-hidden="true">*</span></label><input class="a1-input" id="ft-{{ $team->id }}-name" name="name" required maxlength="190" value="{{ $team->name }}"></div>
                            <div class="a1-field"><label class="a1-label" for="ft-{{ $team->id }}-type">Tip</label><select class="a1-select" id="ft-{{ $team->id }}-type" name="team_type">@foreach($types as $value=>$label)<option value="{{ $value }}" @selected($team->team_type===$value)>{{ $label }}</option>@endforeach</select></div>
                            <div class="a1-field"><label class="a1-label" for="ft-{{ $team->id }}-contact">Kontakt</label><input class="a1-input" id="ft-{{ $team->id }}-contact" name="contact_person" maxlength="190" value="{{ $team->contact_person }}"></div>
                            <div class="a1-field"><label class="a1-label" for="ft-{{ $team->id }}-phone">Telefon</label><input class="a1-input" id="ft-{{ $team->id }}-phone" name="phone" maxlength="80" value="{{ $team->phone }}"></div>
                            <div class="a1-field"><label class="a1-label" for="ft-{{ $team->id }}-email">E-mail</label><input class="a1-input" id="ft-{{ $team->id }}-email" type="email" name="email" maxlength="190" value="{{ $team->email }}"></div>
                            <div class="a1-field"><label class="a1-label" for="ft-{{ $team->id }}-plate">Registracija</label><input class="a1-input" id="ft-{{ $team->id }}-plate" name="vehicle_registration" maxlength="80" value="{{ $team->vehicle_registration }}"></div>
                            <div class="a1-field"><label class="a1-label" for="ft-{{ $team->id }}-area">Područje</label><input class="a1-input" id="ft-{{ $team->id }}-area" name="service_area" maxlength="255" value="{{ $team->service_area }}"></div>
                        </div>
                        <div class="a1-field"><label class="a1-check"><input class="a1-check__input" type="checkbox" name="is_active" value="1" @checked($team->is_active)><span class="a1-check__box" aria-hidden="true"></span><span>Aktivna</span></label></div>
                        <div class="a1-field"><label class="a1-label" for="ft-{{ $team->id }}-notes">Napomena</label><textarea class="a1-textarea" id="ft-{{ $team->id }}-notes" name="notes" maxlength="5000">{{ $team->notes }}</textarea></div>
                        <div><button class="a1-btn" type="submit">Sačuvaj izmene</button></div>
                    </form>
                    @if($team->is_active)
                    <form class="a1-ow-inline" method="post" action="{{ route('admin.field-service-teams.destroy',$team) }}">@csrf @method('DELETE')<button class="a1-btn a1-btn--danger a1-btn--sm" type="submit" data-confirm="Deaktivirati ekipu {{ $team->name }}?" aria-label="Deaktiviraj ekipu {{ $team->name }}">Deaktiviraj</button></form>
                    @endif
                </div>
            </details>
            @empty
            <div class="a1-empty"><span class="a1-empty__icon"><x-icon name="users" :size="28" :shell="true" /></span><p>Još nema definisanih terenskih ekipa.</p></div>
            @endforelse
            @if($teams->hasPages())
            <nav class="a1-pager" aria-label="Straničenje">
                <p class="a1-pager__status">Strana {{ $teams->currentPage() }} od {{ $teams->lastPage() }}</p>
                <ul class="a1-pager__list">
                    <li>@if($teams->onFirstPage())<span class="a1-page" aria-disabled="true">Prethodna</span>@else<a class="a1-page" href="{{ $teams->previousPageUrl() }}" rel="prev">Prethodna</a>@endif</li>
                    <li>@if($teams->hasMorePages())<a class="a1-page" href="{{ $teams->nextPageUrl() }}" rel="next">Sledeća</a>@else<span class="a1-page" aria-disabled="true">Sledeća</span>@endif</li>
                </ul>
            </nav>
            @endif
        </section>

        <aside class="a1-card" aria-labelledby="ft-new-title">
            <h2 class="a1-h3" id="ft-new-title">Dodaj ekipu ili partnera</h2>
            <p class="a1-help">Polja sa zvezdicom su obavezna.</p>
            <form class="a1-form" method="post" action="{{ route('admin.field-service-teams.store') }}">@csrf
                <div class="a1-field"><label class="a1-label" for="ft-new-code">Šifra <span class="a1-req" aria-hidden="true">*</span></label><input class="a1-input" id="ft-new-code" name="code" required maxlength="50" value="{{ old('code') }}" placeholder="SERVIS-01"></div>
                <div class="a1-field"><label class="a1-label" for="ft-new-name">Naziv <span class="a1-req" aria-hidden="true">*</span></label><input class="a1-input" id="ft-new-name" name="name" required maxlength="190" value="{{ old('name') }}"></div>
                <div class="a1-field"><label class="a1-label" for="ft-new-type">Tip</label><select class="a1-select" id="ft-new-type" name="team_type">@foreach($types as $value=>$label)<option value="{{ $value }}" @selected(old('team_type','internal')===$value)>{{ $label }}</option>@endforeach</select></div>
                <div class="a1-field"><label class="a1-label" for="ft-new-contact">Kontakt osoba</label><input class="a1-input" id="ft-new-contact" name="contact_person" maxlength="190" value="{{ old('contact_person') }}"></div>
                <div class="a1-field"><label class="a1-label" for="ft-new-phone">Telefon</label><input class="a1-input" id="ft-new-phone" name="phone" maxlength="80" value="{{ old('phone') }}"></div>
                <div class="a1-field"><label class="a1-label" for="ft-new-email">E-mail</label><input class="a1-input" id="ft-new-email" type="email" name="email" maxlength="190" value="{{ old('email') }}"></div>
                <div class="a1-field"><label class="a1-label" for="ft-new-plate">Registracija vozila</label><input class="a1-input" id="ft-new-plate" name="vehicle_registration" maxlength="80" value="{{ old('vehicle_registration') }}"></div>
                <div class="a1-field"><label class="a1-label" for="ft-new-area">Područje rada</label><input class="a1-input" id="ft-new-area" name="service_area" maxlength="255" value="{{ old('service_area') }}" placeholder="Beograd i okolina"></div>
                <div class="a1-field"><label class="a1-check"><input class="a1-check__input" type="checkbox" name="is_active" value="1" checked><span class="a1-check__box" aria-hidden="true"></span><span>Aktivna ekipa</span></label></div>
                <div class="a1-field"><label class="a1-label" for="ft-new-notes">Interna napomena</label><textarea class="a1-textarea" id="ft-new-notes" name="notes" maxlength="5000">{{ old('notes') }}</textarea></div>
                <div><button class="a1-btn a1-btn--primary" type="submit">Sačuvaj ekipu</button></div>
            </form>
        </aside>
    </div>
</div>
@endsection
