@extends('layouts.app')

@section('title', 'Kurirske službe')

@section('content')
<div class="settings-page courier-settings-page">
    @include('admin.settings.partials.context-nav', ['settingsSection' => 'Poslovanje'])
<div class="page-heading">
        <div>
            <span class="eyebrow">Podešavanja · Super Administrator</span>
            <h1>Kurirske službe</h1>
            <p>Centralni šifarnik kurira i zvaničnih stranica za praćenje. Promene važe za buduće evidencije slanja; istorijski shipment snapshot ostaje nepromenjen.</p>
        </div>
    </div>

    <section class="panel form-section courier-create-card">
        <h2>Dodaj kurirsku službu</h2>
        <form method="post" action="{{ route('admin.settings.couriers.store') }}" class="courier-form-grid">
            @csrf
            <label><span>Naziv</span><input name="name" maxlength="120" required></label>
            <label class="courier-url-field"><span>Zvanični tracking URL</span><input name="tracking_url" type="url" maxlength="500" placeholder="https://..." required></label>
            <label><span>Redosled</span><input name="sort_order" type="number" min="0" max="100000" value="100" required></label>
            <label class="check-row"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" checked> Aktivna</label>
            <label class="check-row"><input type="hidden" name="is_default" value="0"><input type="checkbox" name="is_default" value="1"> Podrazumevana</label>
            <button class="button button-primary" type="submit">Dodaj kurira</button>
        </form>
    </section>

    <section class="panel form-section">
        <div class="section-heading-row"><div><h2>Postojeće kurirske službe</h2><p class="muted">Tačno jedna aktivna služba ostaje podrazumevana.</p></div></div>
        <div class="courier-settings-list">
            @foreach($couriers as $courier)
                <form method="post" action="{{ route('admin.settings.couriers.update', $courier) }}" class="courier-row-card">
                    @csrf
                    @method('PUT')
                    <div class="courier-form-grid">
                        <label><span>Naziv</span><input name="name" maxlength="120" value="{{ $courier->name }}" required></label>
                        <label class="courier-url-field"><span>Zvanični tracking URL</span><input name="tracking_url" type="url" maxlength="500" value="{{ $courier->tracking_url }}" required></label>
                        <label><span>Redosled</span><input name="sort_order" type="number" min="0" max="100000" value="{{ $courier->sort_order }}" required></label>
                        <label class="check-row"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($courier->is_active)> Aktivna</label>
                        <label class="check-row"><input type="hidden" name="is_default" value="0"><input type="checkbox" name="is_default" value="1" @checked($courier->is_default)> Podrazumevana</label>
                        <button class="button button-ghost" type="submit">Sačuvaj</button>
                    </div>
                    <a href="{{ $courier->tracking_url }}" target="_blank" rel="noopener">Otvori zvaničnu tracking stranicu</a>
                </form>
            @endforeach
        </div>
    </section>
</div>

@endsection
