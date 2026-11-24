@extends('layouts.app')

@section('title', 'Kurirske službe')

@section('content')
<div class="a1-ops a1-set">
    @include('admin.settings.partials.context-nav', ['settingsSection' => 'Poslovanje'])
    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Podešavanja · Super Administrator</span>
            <h1 class="a1-title">Kurirske službe</h1>
            <p class="a1-lead">Centralni šifarnik kurira i zvaničnih stranica za praćenje. Promene važe za buduće evidencije slanja; istorijski shipment snapshot ostaje nepromenjen.</p>
        </div>
    </header>

    <section class="a1-card" aria-labelledby="courier-add-title">
        <h2 class="a1-h2" id="courier-add-title">Dodaj kurirsku službu</h2>
        <form method="post" action="{{ route('admin.settings.couriers.store') }}" class="a1-form">
            @csrf
            <div class="a1-set-fields">
                <label class="a1-field"><span class="a1-label">Naziv</span><input class="a1-input" name="name" maxlength="120" required></label>
                <label class="a1-field"><span class="a1-label">Zvanični tracking URL</span><input class="a1-input" name="tracking_url" type="url" maxlength="500" placeholder="https://..." required></label>
                <label class="a1-field"><span class="a1-label">Redosled</span><input class="a1-input" name="sort_order" type="number" min="0" max="100000" value="100" required></label>
            </div>
            <div class="a1-set-checks a1-set-checks--cols">
                <label class="a1-check"><input type="hidden" name="is_active" value="0"><input class="a1-check__input" type="checkbox" name="is_active" value="1" checked><span class="a1-check__box" aria-hidden="true"></span><span>Aktivna</span></label>
                <label class="a1-check"><input type="hidden" name="is_default" value="0"><input class="a1-check__input" type="checkbox" name="is_default" value="1"><span class="a1-check__box" aria-hidden="true"></span><span>Podrazumevana</span></label>
            </div>
            <div><button class="a1-btn a1-btn--primary" type="submit">Dodaj kurira</button></div>
        </form>
    </section>

    <section class="a1-card" aria-labelledby="courier-list-title">
        <div class="a1-card__title">
            <h2 class="a1-h2" id="courier-list-title">Postojeće kurirske službe</h2>
            <p class="a1-muted">Tačno jedna aktivna služba ostaje podrazumevana.</p>
        </div>
        <div class="a1-stack">
            @foreach($couriers as $courier)
                <form method="post" action="{{ route('admin.settings.couriers.update', $courier) }}" class="a1-set-asset a1-form">
                    @csrf
                    @method('PUT')
                    <div class="a1-set-fields">
                        <label class="a1-field"><span class="a1-label">Naziv</span><input class="a1-input" name="name" maxlength="120" value="{{ $courier->name }}" required></label>
                        <label class="a1-field"><span class="a1-label">Zvanični tracking URL</span><input class="a1-input" name="tracking_url" type="url" maxlength="500" value="{{ $courier->tracking_url }}" required></label>
                        <label class="a1-field"><span class="a1-label">Redosled</span><input class="a1-input" name="sort_order" type="number" min="0" max="100000" value="{{ $courier->sort_order }}" required></label>
                    </div>
                    <div class="a1-set-checks a1-set-checks--cols">
                        <label class="a1-check"><input type="hidden" name="is_active" value="0"><input class="a1-check__input" type="checkbox" name="is_active" value="1" @checked($courier->is_active)><span class="a1-check__box" aria-hidden="true"></span><span>Aktivna</span></label>
                        <label class="a1-check"><input type="hidden" name="is_default" value="0"><input class="a1-check__input" type="checkbox" name="is_default" value="1" @checked($courier->is_default)><span class="a1-check__box" aria-hidden="true"></span><span>Podrazumevana</span></label>
                    </div>
                    <div class="a1-set-actions">
                        <button class="a1-btn" type="submit">Sačuvaj</button>
                        <a class="a1-link" href="{{ $courier->tracking_url }}" target="_blank" rel="noopener">Otvori zvaničnu tracking stranicu</a>
                    </div>
                </form>
            @endforeach
        </div>
    </section>
</div>

@endsection
