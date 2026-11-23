@extends('layouts.app')
@section('title', 'PDF dokumenti')
@section('content')
<div class="a1-ops a1-set">
@include('admin.settings.partials.context-nav', ['settingsSection' => 'Komunikacija i dokumenti'])
<header class="a1-pagehead">
    <div class="a1-pagehead__text">
        <span class="a1-eyebrow">Podešavanja</span>
        <h1 class="a1-title">Reports, PDF i fakturisanje</h1>
        <p class="a1-lead">Podesi identitet izdavaoca, logo, obračun i napomene koje se prikazuju na poslovnim dokumentima.</p>
    </div>
</header>

<form method="post" action="{{ route('admin.settings.documents.update') }}" enctype="multipart/form-data" class="a1-set-split">
    @csrf
    @method('put')
    <div class="a1-set-main">
        <section class="a1-card" aria-labelledby="doc-issuer-title">
            <h2 class="a1-h2" id="doc-issuer-title">Podaci izdavaoca</h2>
            <div class="a1-set-fields">
                <label class="a1-field a1-set-wide"><span class="a1-label">Naziv firme *</span><input class="a1-input" name="documents_company_name" required maxlength="190" value="{{ old('documents_company_name', $settings['documents_company_name']) }}"></label>
                <label class="a1-field a1-set-wide"><span class="a1-label">Adresa *</span><input class="a1-input" name="documents_company_address" required maxlength="255" value="{{ old('documents_company_address', $settings['documents_company_address']) }}"></label>
                <label class="a1-field"><span class="a1-label">Grad *</span><input class="a1-input" name="documents_company_city" required maxlength="120" value="{{ old('documents_company_city', $settings['documents_company_city']) }}"></label>
                <label class="a1-field"><span class="a1-label">Telefon</span><input class="a1-input" name="documents_company_phone" maxlength="60" value="{{ old('documents_company_phone', $settings['documents_company_phone']) }}"></label>
                <label class="a1-field"><span class="a1-label">PIB</span><input class="a1-input" name="documents_company_tax_id" maxlength="40" value="{{ old('documents_company_tax_id', $settings['documents_company_tax_id']) }}"></label>
                <label class="a1-field"><span class="a1-label">Matični broj</span><input class="a1-input" name="documents_company_registration_number" maxlength="40" value="{{ old('documents_company_registration_number', $settings['documents_company_registration_number']) }}"></label>
                <label class="a1-field"><span class="a1-label">E-mail izdavaoca</span><input class="a1-input" type="email" name="documents_company_email" maxlength="190" value="{{ old('documents_company_email', $settings['documents_company_email']) }}"></label>
                <label class="a1-field"><span class="a1-label">Web sajt</span><input class="a1-input" type="url" name="documents_company_website" maxlength="190" value="{{ old('documents_company_website', $settings['documents_company_website']) }}"></label>
            </div>
        </section>

        <section class="a1-card" aria-labelledby="doc-logo-title">
            <h2 class="a1-h2" id="doc-logo-title">Logo na PDF dokumentima</h2>
            <div class="a1-set-fields">
                <div class="a1-set-preview a1-set-preview--light">
                    @if($documentLogoUrl)
                        <img src="{{ $documentLogoUrl }}" alt="PDF logo">
                    @else
                        <span>Nije postavljen poseban PDF logo</span>
                    @endif
                </div>
                <div class="a1-stack">
                    <label class="a1-field a1-set-file">
                        <span class="a1-label">Učitaj logo</span>
                        <input type="file" name="documents_logo" accept=".png,.jpg,.jpeg,.webp">
                    </label>
                    <label class="a1-field"><span class="a1-label">Visina logotipa na PDF-u (pt)</span><input class="a1-input" type="number" name="documents_logo_height" min="16" max="36" step="1" value="{{ old('documents_logo_height', $settings['documents_logo_height']) }}" required aria-describedby="hint-documents_logo_height"><small class="a1-help" id="hint-documents_logo_height">Od 16 do 36 pt (1 pt = 1/72 inča), podrazumevano 26 pt. Širina je najviše 48 pt. Kada je logo prikazan, ime firme se u zaglavlju PDF-a ne ispisuje pored njega.</small>@error('documents_logo_height')<span class="a1-error" role="alert">{{ $message }}</span>@enderror</label>
                    <p class="a1-muted">Podržani su PNG, JPG/JPEG i WebP do 4 MB. Logo se automatski priprema za pouzdano ugrađivanje u PDF. Ako poseban logo nije postavljen, koristi se logo svetle teme sajta.</p>
                    @if(!empty($settings['documents_logo_path']))
                        <div><button class="a1-btn a1-btn--danger a1-btn--sm" type="submit" form="remove-document-logo" data-confirm="Ukloniti poseban PDF logo?">Ukloni PDF logo</button></div>
                    @endif
                </div>
            </div>
        </section>

        <section class="a1-card" aria-labelledby="doc-qr-title">
            <h2 class="a1-h2" id="doc-qr-title">NBS IPS QR za uplatu na račun</h2>
            <div class="a1-alert"><x-icon name="alert" :size="20" :shell="true" /><div class="a1-alert__body"><p>Predračun i račun automatski dobijaju zvanični NBS IPS QR kada porudžbina koristi uplatu na račun. QR sadrži snapshot primaoca, računa, poziva na broj i tačnog ukupnog iznosa dokumenta u RSD. Ako podaci nisu validni ili NBS servis nije dostupan, finansijski dokument se neće izdati bez QR koda.</p></div></div>
        </section>

        <section class="a1-card" aria-labelledby="doc-calc-title">
            <h2 class="a1-h2" id="doc-calc-title">Obračun i napomene</h2>
            <div class="a1-set-fields">
                <label class="a1-field"><span class="a1-label">PDV stopa (%)</span><input class="a1-input" type="number" step="0.01" min="0" max="100" name="documents_vat_rate" required value="{{ old('documents_vat_rate', $settings['documents_vat_rate']) }}"></label>
                <label class="a1-field"><span class="a1-label">Rok plaćanja (dana)</span><input class="a1-input" type="number" min="0" max="365" name="documents_payment_due_days" required value="{{ old('documents_payment_due_days', $settings['documents_payment_due_days']) }}"></label>
                <label class="a1-check a1-set-wide"><input class="a1-check__input" type="checkbox" name="documents_vat_enabled" value="1" @checked(old('documents_vat_enabled', $settings['documents_vat_enabled']) === '1')><span class="a1-check__box" aria-hidden="true"></span><span>Prikaži PDV obračun u PDF dokumentima</span></label>
                <label class="a1-field a1-set-wide"><span class="a1-label">Podrazumevana napomena</span><textarea class="a1-textarea" name="documents_default_note" rows="4" maxlength="2000">{{ old('documents_default_note', $settings['documents_default_note']) }}</textarea></label>
                <label class="a1-field a1-set-wide"><span class="a1-label">Footer PDF dokumenta</span><input class="a1-input" name="documents_footer_note" maxlength="500" value="{{ old('documents_footer_note', $settings['documents_footer_note']) }}"></label>
            </div>
        </section>
    </div>

    <aside class="a1-set-side">
        <section class="a1-card" aria-labelledby="doc-identity-title">
            <h2 class="a1-h2" id="doc-identity-title">PDF identitet</h2>
            <p class="a1-muted">Dokument prikazuje podatke firme i isključivo podatke krajnjeg kupca iz adrese isporuke. E-mail naloga subagenta se ne prikazuje.</p>
            <div class="a1-alert a1-alert--warning"><x-icon name="alert" :size="20" :shell="true" /><div class="a1-alert__body"><p>Račun koji generiše CMS nije fiskalni račun niti zamena za fiskalizaciju. Pravne i poreske podatke proveri pre produkcione upotrebe.</p></div></div>
            <button class="a1-btn a1-btn--primary" type="submit">Sačuvaj podešavanja</button>
        </section>
    </aside>
</form>

<form id="remove-document-logo" method="post" action="{{ route('admin.settings.documents.logo.destroy') }}">
    @csrf
    @method('delete')
</form>
</div>
@endsection
