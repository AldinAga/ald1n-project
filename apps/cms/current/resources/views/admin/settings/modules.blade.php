@extends('layouts.app')

@section('title', 'Moduli sistema')

@section('content')
<div class="page-heading">
    <div>
        <span class="eyebrow">Podešavanja</span>
        <h1>Moduli sistema</h1>
        <p>Prikaži samo module koji su ti trenutno potrebni. Isključivanje ne briše podatke i ne menja poslovnu istoriju.</p>
    </div>
</div>

@include('admin.settings.partials.context-nav', ['settingsSection' => 'Moduli'])

@if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<form method="post" action="{{ route('admin.settings.modules.update') }}" class="admin-form-grid settings-primary-form">
    @csrf
    @method('put')

    <div class="form-main">
        <section class="panel form-section">
            <div class="section-heading-row">
                <div>
                    <h2>Opcioni moduli</h2>
                    <p class="muted">Svi moduli su podrazumevano uključeni. Isključen modul se uklanja iz glavne navigacije i dashboard prečica; postojeći podaci ostaju netaknuti.</p>
                </div>
            </div>

            <div class="stack-list">
                @foreach($modules as $module)
                    <div class="setting-row">
                        <div>
                            <strong>{{ $module['label'] }}</strong>
                            <p class="muted">{{ $module['description'] }}</p>
                        </div>
                        <div>
                            <input type="hidden" name="modules[{{ $module['key'] }}]" value="0">
                            <label>
                                <input type="checkbox" name="modules[{{ $module['key'] }}]" value="1" @checked($module['enabled'])>
                                <span>{{ $module['enabled'] ? 'Uključen' : 'Isključen' }}</span>
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="panel form-section">
            <h2>Osnovni moduli</h2>
            <p class="muted">Katalog, Porudžbine, Autentifikacija, Korisnici, Podešavanja, Dokumenti i Plaćanja su osnovni sistemski moduli i ne mogu se isključiti.</p>
        </section>

        <section class="panel form-section">
            <h2>Šta tačno radi deaktivacija?</h2>
            <p class="muted">Ova faza je namerno bezbedna: modul se skriva iz interfejsa i postojeći Mobile bootstrap feature flag se gasi kada postoji. Direktne URL rute, istorijski podaci, background poslovi i domain servisi se ne brišu niti prisilno zaustavljaju.</p>
        </section>
    </div>

    <aside class="form-side">
        <section class="panel sticky-card form-section">
            <h2>Sačuvaj</h2>
            <p class="muted">Promena je reverzibilna. Modul možeš ponovo uključiti u bilo kom trenutku.</p>
            <button class="button button-primary" type="submit">Sačuvaj module</button>
        </section>
    </aside>
</form>
@endsection
