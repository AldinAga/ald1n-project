@extends('layouts.app')
@section('title', 'Dobavljači rezervnih delova')
@section('content')
@include('admin.settings.partials.context-nav', ['settingsSection' => 'Poslovna pravila', 'settingsContextLabel' => 'Servisni lager', 'settingsContextUrl' => route('admin.service-parts.index')])
<div class="a1-ops">
    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Nabavka</span>
            <h1 class="a1-title">Dobavljači rezervnih delova</h1>
            <p class="a1-lead">Kontakti, rokovi isporuke i preferirani izvori nabavke.</p>
        </div>
        <div class="a1-pagehead__actions">
            <a class="a1-btn a1-btn--primary" href="{{ route('admin.service-part-purchases.index') }}">Zahtevi za nabavku</a>
        </div>
    </header>

    <section class="a1-card" aria-labelledby="sps-new-title">
        <h2 class="a1-h3" id="sps-new-title">Novi dobavljač</h2>
        <form class="a1-form" method="post" action="{{ route('admin.service-part-suppliers.store') }}">@csrf
            <div class="a1-row">
                <div class="a1-field"><label class="a1-label" for="sps-new-code">Šifra</label><input class="a1-input" id="sps-new-code" name="code" required></div>
                <div class="a1-field"><label class="a1-label" for="sps-new-name">Naziv</label><input class="a1-input" id="sps-new-name" name="name" required></div>
                <div class="a1-field"><label class="a1-label" for="sps-new-contact">Kontakt osoba</label><input class="a1-input" id="sps-new-contact" name="contact_person"></div>
                <div class="a1-field"><label class="a1-label" for="sps-new-phone">Telefon</label><input class="a1-input" id="sps-new-phone" name="phone"></div>
                <div class="a1-field"><label class="a1-label" for="sps-new-email">E-mail</label><input class="a1-input" id="sps-new-email" type="email" name="email"></div>
                <div class="a1-field"><label class="a1-label" for="sps-new-lead">Rok isporuke (dana)</label><input class="a1-input" id="sps-new-lead" type="number" min="0" name="lead_time_days" value="0"></div>
            </div>
            <div class="a1-field"><label class="a1-label" for="sps-new-address">Adresa</label><textarea class="a1-textarea" id="sps-new-address" name="address"></textarea></div>
            <div class="a1-field"><label class="a1-label" for="sps-new-notes">Napomena</label><textarea class="a1-textarea" id="sps-new-notes" name="notes"></textarea></div>
            <div class="a1-field"><label class="a1-check"><input class="a1-check__input" type="checkbox" name="is_active" value="1" checked><span class="a1-check__box" aria-hidden="true"></span><span>Aktivan</span></label></div>
            <div><button class="a1-btn a1-btn--primary" type="submit">Kreiraj dobavljača</button></div>
        </form>
    </section>

    <form class="a1-filters a1-ow-filters" method="get" role="search" aria-label="Pretraga dobavljača">
        <div class="a1-filters__fields">
            <div class="a1-field">
                <label class="a1-label" for="sps-q">Naziv, šifra ili kontakt</label>
                <div class="a1-search"><x-icon name="search" :size="20" :shell="true" /><input class="a1-input" id="sps-q" name="q" value="{{ $q }}"></div>
            </div>
        </div>
        <div class="a1-filters__actions">
            <button class="a1-btn" type="submit">Pretraži</button>
        </div>
    </form>

    <section class="a1-stack" aria-labelledby="sps-list-title">
        <h2 class="a1-h3" id="sps-list-title">Dobavljači</h2>
        @if($suppliers->isNotEmpty())
        <div class="a1-tablewrap a1-tablewrap--sticky" role="region" aria-label="Dobavljači rezervnih delova" tabindex="0">
            <table class="a1-table a1-table--cards" role="table">
                <caption class="a1-sr">Dobavljači rezervnih delova</caption>
                <thead role="rowgroup"><tr role="row"><th scope="col" role="columnheader">Dobavljač</th><th scope="col" role="columnheader">Kontakt</th><th scope="col" role="columnheader">Rok</th><th scope="col" role="columnheader" class="a1-table__num">Delovi</th><th scope="col" role="columnheader" class="a1-table__num">Nabavke</th><th scope="col" role="columnheader">Status</th><th scope="col" role="columnheader">Izmena</th></tr></thead>
                <tbody role="rowgroup">
                @foreach($suppliers as $supplier)
                    <tr role="row">
                        <td class="a1-table__primary" role="cell" data-label="Dobavljač"><strong>{{ $supplier->name }}</strong><span class="a1-table__sub">{{ $supplier->code }}</span></td>
                        <td role="cell" data-label="Kontakt">{{ $supplier->contact_person ?: '—' }}<span class="a1-table__sub">{{ $supplier->phone ?: $supplier->email }}</span></td>
                        <td role="cell" data-label="Rok">{{ $supplier->lead_time_days }} dana</td>
                        <td class="a1-table__num" role="cell" data-label="Delovi">{{ $supplier->parts_count }}</td>
                        <td class="a1-table__num" role="cell" data-label="Nabavke">{{ $supplier->purchase_requests_count }}</td>
                        <td role="cell" data-label="Status"><span class="a1-pill {{ $supplier->is_active ? 'a1-pill--ok' : 'a1-pill--off' }}">{{ $supplier->is_active ? 'Aktivan' : 'Neaktivan' }}</span></td>
                        <td class="a1-table__actions" role="cell" data-label="Izmena">
                            <details class="a1-ow-edit">
                                <summary class="a1-btn a1-btn--sm" aria-label="Uredi dobavljača {{ $supplier->code }}">Uredi</summary>
                                <div class="a1-ow-edit__panel">
                                    <form class="a1-form" method="post" action="{{ route('admin.service-part-suppliers.update',$supplier) }}">@csrf @method('PUT')
                                        <div class="a1-field"><label class="a1-label" for="sps-{{ $supplier->id }}-code">Šifra</label><input class="a1-input" id="sps-{{ $supplier->id }}-code" name="code" value="{{ $supplier->code }}" required></div>
                                        <div class="a1-field"><label class="a1-label" for="sps-{{ $supplier->id }}-name">Naziv</label><input class="a1-input" id="sps-{{ $supplier->id }}-name" name="name" value="{{ $supplier->name }}" required></div>
                                        <div class="a1-field"><label class="a1-label" for="sps-{{ $supplier->id }}-contact">Kontakt osoba</label><input class="a1-input" id="sps-{{ $supplier->id }}-contact" name="contact_person" value="{{ $supplier->contact_person }}"></div>
                                        <div class="a1-field"><label class="a1-label" for="sps-{{ $supplier->id }}-phone">Telefon</label><input class="a1-input" id="sps-{{ $supplier->id }}-phone" name="phone" value="{{ $supplier->phone }}"></div>
                                        <div class="a1-field"><label class="a1-label" for="sps-{{ $supplier->id }}-email">E-mail</label><input class="a1-input" id="sps-{{ $supplier->id }}-email" type="email" name="email" value="{{ $supplier->email }}"></div>
                                        <div class="a1-field"><label class="a1-label" for="sps-{{ $supplier->id }}-lead">Rok isporuke (dana)</label><input class="a1-input" id="sps-{{ $supplier->id }}-lead" type="number" min="0" name="lead_time_days" value="{{ $supplier->lead_time_days }}"></div>
                                        <div class="a1-field"><label class="a1-label" for="sps-{{ $supplier->id }}-address">Adresa</label><textarea class="a1-textarea" id="sps-{{ $supplier->id }}-address" name="address">{{ $supplier->address }}</textarea></div>
                                        <div class="a1-field"><label class="a1-label" for="sps-{{ $supplier->id }}-notes">Napomena</label><textarea class="a1-textarea" id="sps-{{ $supplier->id }}-notes" name="notes">{{ $supplier->notes }}</textarea></div>
                                        <div class="a1-field"><label class="a1-check"><input class="a1-check__input" type="checkbox" name="is_active" value="1" @checked($supplier->is_active)><span class="a1-check__box" aria-hidden="true"></span><span>Aktivan</span></label></div>
                                        <div><button class="a1-btn a1-btn--primary" type="submit">Sačuvaj</button></div>
                                    </form>
                                </div>
                            </details>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="a1-empty"><span class="a1-empty__icon"><x-icon name="cube" :size="28" :shell="true" /></span><p>Nema dobavljača.</p></div>
        @endif
        {{ $suppliers->links() }}
    </section>
</div>
@endsection
