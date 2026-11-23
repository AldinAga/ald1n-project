@extends('layouts.app')

@section('title', 'Moduli sistema')

@section('content')
<div class="a1-ops a1-set">
<header class="a1-pagehead">
    <div class="a1-pagehead__text">
        <span class="a1-eyebrow">Podešavanja</span>
        <h1 class="a1-title">Moduli sistema</h1>
        <p class="a1-lead">Prikaži samo module koji su ti trenutno potrebni. Isključivanje ne briše podatke i ne menja poslovnu istoriju.</p>
    </div>
</header>

@include('admin.settings.partials.context-nav', ['settingsSection' => 'Moduli'])

<form method="post" action="{{ route('admin.settings.modules.update') }}" class="a1-set-split">
    @csrf
    @method('put')

    <div class="a1-set-main">
        <section class="a1-card" aria-labelledby="mod-optional-title">
            <div class="a1-card__title">
                <h2 class="a1-h2" id="mod-optional-title">Opcioni moduli</h2>
                <p class="a1-muted">Svi moduli su podrazumevano uključeni. Isključen modul se uklanja iz glavne navigacije i dashboard prečica; postojeći podaci ostaju netaknuti.</p>
            </div>

            <div>
                @foreach($modules as $module)
                    <div class="a1-set-row">
                        <div>
                            <strong>{{ $module['label'] }}</strong>
                            <p class="a1-muted">{{ $module['description'] }}</p>
                        </div>
                        <div>
                            <input type="hidden" name="modules[{{ $module['key'] }}]" value="0">
                            <label class="a1-check">
                                <input class="a1-check__input" type="checkbox" name="modules[{{ $module['key'] }}]" value="1" @checked($module['enabled'])>
                                <span class="a1-check__box" aria-hidden="true"></span>
                                <span>{{ $module['enabled'] ? 'Uključen' : 'Isključen' }}</span>
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="a1-card" aria-labelledby="mod-core-title">
            <h2 class="a1-h2" id="mod-core-title">Osnovni moduli</h2>
            <p class="a1-muted">Katalog, Porudžbine, Autentifikacija, Korisnici, Podešavanja, Dokumenti i Plaćanja su osnovni sistemski moduli i ne mogu se isključiti.</p>
        </section>

        <section class="a1-card" aria-labelledby="mod-what-title">
            <h2 class="a1-h2" id="mod-what-title">Šta tačno radi deaktivacija?</h2>
            <p class="a1-muted">Ova faza je namerno bezbedna: modul se skriva iz interfejsa i postojeći Mobile bootstrap feature flag se gasi kada postoji. Direktne URL rute, istorijski podaci, background poslovi i domain servisi se ne brišu niti prisilno zaustavljaju.</p>
        </section>
    </div>

    <aside class="a1-set-side">
        <section class="a1-card" aria-labelledby="mod-save-title">
            <h2 class="a1-h2" id="mod-save-title">Sačuvaj</h2>
            <p class="a1-muted">Promena je reverzibilna. Modul možeš ponovo uključiti u bilo kom trenutku.</p>
            <button class="a1-btn a1-btn--primary" type="submit">Sačuvaj module</button>
        </section>
    </aside>
</form>

<form method="post" action="{{ route('admin.settings.modules.operations') }}" class="a1-set-split" data-operations-form>
    @csrf
    @method('put')
    <div class="a1-set-main">
        <section class="a1-card" aria-labelledby="mod-ops-title">
            <div class="a1-card__title">
                <h2 class="a1-h2" id="mod-ops-title">Operativni prag početne</h2>
                <p class="a1-muted">Potvrđena porudžbina koja nije poslata duže od ovog broja dana pojavljuje se u prioritetnim aktivnostima.</p>
            </div>
            <div class="a1-field">
                <label class="a1-label" for="dashboard_confirmed_ship_days">Dana do slanja potvrđene porudžbine</label>
                <input class="a1-input" id="dashboard_confirmed_ship_days" name="dashboard_confirmed_ship_days" type="number" min="1" max="30" step="1" required value="{{ old('dashboard_confirmed_ship_days', $confirmedShipDays) }}">
                @error('dashboard_confirmed_ship_days')<p class="a1-error" role="alert">{{ $message }}</p>@enderror
            </div>
        </section>
    </div>
    <aside class="a1-set-side">
        <section class="a1-card">
            <button class="a1-btn a1-btn--primary" type="submit">Sačuvaj prag</button>
        </section>
    </aside>
</form>
</div>
@endsection
