@extends('layouts.app')
@section('title', 'PDF dokumenti')
@section('content')
@include('admin.settings.partials.context-nav', ['settingsSection' => 'Komunikacija i dokumenti'])
<div class="page-heading">
    <div>
        <span class="eyebrow">Podešavanja</span>
        <h1>Reports, PDF i fakturisanje</h1>
        <p>Podesi identitet izdavaoca, logo, obračun i napomene koje se prikazuju na poslovnim dokumentima.</p>
    </div>
</div>

<form method="post" action="{{ route('admin.settings.documents.update') }}" enctype="multipart/form-data" class="admin-form-grid settings-primary-form">
    @csrf
    @method('put')
    <div class="form-main">
        <section class="panel form-section">
            <h2>Podaci izdavaoca</h2>
            <div class="field-grid">
                <label class="field-span-2"><span>Naziv firme *</span><input name="documents_company_name" required maxlength="190" value="{{ old('documents_company_name', $settings['documents_company_name']) }}"></label>
                <label class="field-span-2"><span>Adresa *</span><input name="documents_company_address" required maxlength="255" value="{{ old('documents_company_address', $settings['documents_company_address']) }}"></label>
                <label><span>Grad *</span><input name="documents_company_city" required maxlength="120" value="{{ old('documents_company_city', $settings['documents_company_city']) }}"></label>
                <label><span>Telefon</span><input name="documents_company_phone" maxlength="60" value="{{ old('documents_company_phone', $settings['documents_company_phone']) }}"></label>
                <label><span>PIB</span><input name="documents_company_tax_id" maxlength="40" value="{{ old('documents_company_tax_id', $settings['documents_company_tax_id']) }}"></label>
                <label><span>Matični broj</span><input name="documents_company_registration_number" maxlength="40" value="{{ old('documents_company_registration_number', $settings['documents_company_registration_number']) }}"></label>
                <label><span>E-mail izdavaoca</span><input type="email" name="documents_company_email" maxlength="190" value="{{ old('documents_company_email', $settings['documents_company_email']) }}"></label>
                <label><span>Web sajt</span><input type="url" name="documents_company_website" maxlength="190" value="{{ old('documents_company_website', $settings['documents_company_website']) }}"></label>
            </div>
        </section>

        <section class="panel form-section">
            <h2>Logo na PDF dokumentima</h2>
            <div class="pdf-logo-setting">
                <div class="asset-preview light pdf-logo-preview">
                    @if($documentLogoUrl)
                        <img src="{{ $documentLogoUrl }}" alt="PDF logo">
                    @else
                        <span>Nije postavljen poseban PDF logo</span>
                    @endif
                </div>
                <div>
                    <label>
                        <span>Učitaj logo</span>
                        <input type="file" name="documents_logo" accept=".png,.jpg,.jpeg,.webp">
                    </label>
                    <p class="muted">Podržani su PNG, JPG/JPEG i WebP do 4 MB. Logo se automatski priprema za pouzdano ugrađivanje u PDF. Ako poseban logo nije postavljen, koristi se logo svetle teme sajta.</p>
                    @if(!empty($settings['documents_logo_path']))
                        <button class="button button-danger button-small" type="submit" form="remove-document-logo" data-confirm="Ukloniti poseban PDF logo?">Ukloni PDF logo</button>
                    @endif
                </div>
            </div>
        </section>

        <section class="panel form-section">
            <h2>NBS IPS QR za uplatu na račun</h2>
            <div class="alpha-note">
                Predračun i račun automatski dobijaju zvanični NBS IPS QR kada porudžbina koristi uplatu na račun. QR sadrži snapshot primaoca, računa, poziva na broj i tačnog ukupnog iznosa dokumenta u RSD. Ako podaci nisu validni ili NBS servis nije dostupan, finansijski dokument se neće izdati bez QR koda.
            </div>
        </section>

        <section class="panel form-section">
            <h2>Obračun i napomene</h2>
            <div class="field-grid">
                <label><span>PDV stopa (%)</span><input type="number" step="0.01" min="0" max="100" name="documents_vat_rate" required value="{{ old('documents_vat_rate', $settings['documents_vat_rate']) }}"></label>
                <label><span>Rok plaćanja (dana)</span><input type="number" min="0" max="365" name="documents_payment_due_days" required value="{{ old('documents_payment_due_days', $settings['documents_payment_due_days']) }}"></label>
                <label class="field-span-2 check-row"><input type="checkbox" name="documents_vat_enabled" value="1" @checked(old('documents_vat_enabled', $settings['documents_vat_enabled']) === '1')><span>Prikaži PDV obračun u PDF dokumentima</span></label>
                <label class="field-span-2"><span>Podrazumevana napomena</span><textarea name="documents_default_note" rows="4" maxlength="2000">{{ old('documents_default_note', $settings['documents_default_note']) }}</textarea></label>
                <label class="field-span-2"><span>Footer PDF dokumenta</span><input name="documents_footer_note" maxlength="500" value="{{ old('documents_footer_note', $settings['documents_footer_note']) }}"></label>
            </div>
        </section>
    </div>

    <aside class="form-side">
        <section class="panel form-section sticky-card">
            <h2>PDF identitet</h2>
            <p class="muted">Dokument prikazuje podatke firme i isključivo podatke krajnjeg kupca iz adrese isporuke. E-mail naloga subagenta se ne prikazuje.</p>
            <div class="alpha-note">Račun koji generiše CMS nije fiskalni račun niti zamena za fiskalizaciju. Pravne i poreske podatke proveri pre produkcione upotrebe.</div>
            <button class="button button-primary button-large" type="submit">Sačuvaj podešavanja</button>
        </section>
    </aside>
</form>

<form id="remove-document-logo" method="post" action="{{ route('admin.settings.documents.logo.destroy') }}">
    @csrf
    @method('delete')
</form>
@endsection
