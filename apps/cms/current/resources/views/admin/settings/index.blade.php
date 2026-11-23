@extends('layouts.app')

@section('title', 'Podešavanja')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/ald1n-admin-settings.css') }}?v={{ @filemtime(public_path('assets/css/ald1n-admin-settings.css')) ?: config('app.version') }}">
@endpush

@section('content')
@php
    $settingsUser = auth()->user();
@endphp

<div class="a1-ops a1-set">
    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Administracija</span>
            <h1 class="a1-title">Podešavanja</h1>
            <p class="a1-lead">Jedno mesto za poslovna pravila, izgled, komunikaciju, logistiku, šifarnike, pristup i sistemske opcije.</p>
        </div>
    </header>

    <section class="a1-card a1-set-search">
        <label class="a1-field">
            <span class="a1-label">Pronađi podešavanje</span>
            <input class="a1-input" type="search" placeholder="Npr. kurir, kurs, e-mail, garancija, kategorija..." data-settings-search autocomplete="off" aria-label="Pretraži podešavanja" aria-describedby="settings-search-status" aria-keyshortcuts="/">
        </label>
        <p id="settings-search-status" class="a1-muted" data-settings-search-status aria-live="polite">Prikazana su sva podešavanja dostupna tvom nalogu. Pritisni / za brzu pretragu.</p>
    </section>

    <nav aria-label="Kategorije podešavanja">
        <ul class="a1-chips">
            <li><a class="a1-chip" href="#settings-business">Poslovanje</a></li>
            <li><a class="a1-chip" href="#settings-rules">Pravila i šifarnici</a></li>
            <li><a class="a1-chip" href="#settings-communication">Komunikacija</a></li>
            <li><a class="a1-chip" href="#settings-interface">Izgled i bezbednost</a></li>
            <li><a class="a1-chip" href="#settings-access">Pristup</a></li>
        </ul>
    </nav>

    <div class="a1-stack" data-settings-list>
        @can('system.manage_settings')
        <section id="settings-business" class="a1-card a1-set-hubsec" data-settings-section aria-labelledby="settings-business-title">
            <div class="a1-card__title">
                <span class="a1-eyebrow">Poslovanje</span>
                <h2 class="a1-h2" id="settings-business-title">Finansije, naplata i logistika</h2>
            </div>
            <div class="a1-list a1-list--grid">
                <a class="a1-item" href="{{ route('admin.settings.exchange.index') }}" data-settings-item data-settings-keywords="kurs eur rsd valuta nbs finansije">
                    <span class="a1-item__icon"><x-icon name="coins" :shell="true" /></span>
                    <span class="a1-item__main"><span class="a1-item__title">EUR/RSD kurs</span><span class="a1-item__sub">Automatski ili ručni kurs i centralna sinhronizacija.</span></span>
                </a>
                <a class="a1-item" href="{{ route('admin.settings.bank-accounts.index') }}" data-settings-item data-settings-keywords="žiro racun račun banka uplata ips">
                    <span class="a1-item__icon"><x-icon name="wallet" :shell="true" /></span>
                    <span class="a1-item__main"><span class="a1-item__title">Žiro računi</span><span class="a1-item__sub">Računi koji se koriste u poslovnim dokumentima i plaćanju.</span></span>
                </a>
                @if($settingsUser?->hasRole('superadmin'))
                <a class="a1-item" href="{{ route('admin.settings.public-storefront.index') }}" data-settings-item data-settings-keywords="javna prodavnica katalog korpa porucivanje uslovi trgovac privatnost dostava placanje webshop">
                    <span class="a1-item__icon"><x-icon name="shopping-bag" :shell="true" /></span>
                    <span class="a1-item__main"><span class="a1-item__title">Javna prodavnica</span><span class="a1-item__sub">Odvojeno podešavanje uslova trgovca bez uključivanja javne prodaje.</span></span>
                </a>
                @endif
                @if($settingsUser?->hasRole('superadmin'))
                <a class="a1-item" href="{{ route('admin.settings.couriers.index') }}" data-settings-item data-settings-keywords="kurir kurirske službe tracking praćenje pošiljka post express d express bex aks gls">
                    <span class="a1-item__icon"><x-icon name="truck" :shell="true" /></span>
                    <span class="a1-item__main"><span class="a1-item__title">Kurirske službe</span><span class="a1-item__sub">Dodavanje kurira, redosled, podrazumevani kurir i zvanični tracking linkovi.</span></span>
                </a>
                @endif
            </div>
        </section>
        @endcan

        @canany(['catalog.manage_taxonomy','receivables.manage','warranties.manage','field_operations.manage','service_parts.procurement'])
        <section id="settings-rules" class="a1-card a1-set-hubsec" data-settings-section aria-labelledby="settings-rules-title">
            <div class="a1-card__title">
                <span class="a1-eyebrow">Poslovna pravila</span>
                <h2 class="a1-h2" id="settings-rules-title">Katalog, servis i automatizovana pravila</h2>
            </div>
            <div class="a1-list a1-list--grid">
                @can('catalog.manage_taxonomy')
                <article class="a1-item a1-item--stack a1-item--static" data-settings-item data-settings-keywords="katalog šifarnici kategorije brendovi linije tipovi specifikacije">
                    <span class="a1-item__main">
                        <span class="a1-item__title"><x-icon name="boxes" :shell="true" /> Šifarnici kataloga</span>
                        <span class="a1-item__sub">Kategorije, brendovi, linije, tipovi artikala i polja specifikacija.</span>
                    </span>
                    <span class="a1-set-links">
                        <a class="a1-btn a1-btn--sm" href="{{ route('admin.dictionary.index','categories') }}">Kategorije</a>
                        <a class="a1-btn a1-btn--sm" href="{{ route('admin.dictionary.index','brands') }}">Brendovi</a>
                        <a class="a1-btn a1-btn--sm" href="{{ route('admin.dictionary.index','product-lines') }}">Linije</a>
                        <a class="a1-btn a1-btn--sm" href="{{ route('admin.dictionary.index','product-types') }}">Tipovi</a>
                        <a class="a1-btn a1-btn--sm" href="{{ route('admin.dictionary.index','specification-fields') }}">Specifikacije</a>
                    </span>
                </article>
                @endcan

                @can('receivables.manage')
                <a class="a1-item" href="{{ route('admin.receivables.index') }}" data-settings-item data-settings-keywords="potraživanja naplata reminder podsetnici rokovi automatsko kreiranje">
                    <span class="a1-item__icon"><x-icon name="wallet" :shell="true" /></span>
                    <span class="a1-item__main"><span class="a1-item__title">Pravila potraživanja</span><span class="a1-item__sub">Automatsko kreiranje slučajeva, rokovi, podsetnici i primaoci.</span></span>
                </a>
                @endcan

                @can('warranties.manage')
                <a class="a1-item" href="{{ route('admin.warranties.index') }}" data-settings-item data-settings-keywords="garancija garancije pravila održavanje backfill warranty">
                    <span class="a1-item__icon"><x-icon name="shield" :shell="true" /></span>
                    <span class="a1-item__main"><span class="a1-item__title">Garancijska pravila</span><span class="a1-item__sub">Pravila izdavanja garancija, periodi i održavanje.</span></span>
                </a>
                @endcan

                @can('field_operations.manage')
                <a class="a1-item" href="{{ route('admin.field-service-teams.index') }}" data-settings-item data-settings-keywords="terenske ekipe partneri servis raspored">
                    <span class="a1-item__icon"><x-icon name="users" :shell="true" /></span>
                    <span class="a1-item__main"><span class="a1-item__title">Terenske ekipe i partneri</span><span class="a1-item__sub">Šifarnik ekipa koje se koriste u servisnim i terenskim nalozima.</span></span>
                </a>
                @endcan

                @can('service_parts.procurement')
                <a class="a1-item" href="{{ route('admin.service-part-suppliers.index') }}" data-settings-item data-settings-keywords="dobavljači rezervni delovi servis nabavka">
                    <span class="a1-item__icon"><x-icon name="receipt" :shell="true" /></span>
                    <span class="a1-item__main"><span class="a1-item__title">Dobavljači servisnih delova</span><span class="a1-item__sub">Centralni spisak dobavljača za nabavku rezervnih delova.</span></span>
                </a>
                @endcan
            </div>
        </section>
        @endcanany

        @can('system.manage_settings')
        <section id="settings-communication" class="a1-card a1-set-hubsec" data-settings-section aria-labelledby="settings-communication-title">
            <div class="a1-card__title">
                <span class="a1-eyebrow">Komunikacija</span>
                <h2 class="a1-h2" id="settings-communication-title">Dokumenti, e-mail i automatizacija</h2>
            </div>
            <div class="a1-list a1-list--grid">
                <a class="a1-item" href="{{ route('admin.settings.documents.index') }}" data-settings-item data-settings-keywords="pdf faktura fakturisanje dokumenti račun predračun otpremnica logo">
                    <span class="a1-item__icon"><x-icon name="file-text" :shell="true" /></span>
                    <span class="a1-item__main"><span class="a1-item__title">PDF i fakturisanje</span><span class="a1-item__sub">Vizuelni i poslovni parametri dokumenata i fakturisanja.</span></span>
                </a>
                <a class="a1-item" href="{{ route('admin.settings.order-emails.index') }}" data-settings-item data-settings-keywords="email e-mail poruke obaveštenja dokumenti korisnici">
                    <span class="a1-item__icon"><x-icon name="mail" :shell="true" /></span>
                    <span class="a1-item__main"><span class="a1-item__title">E-mail obaveštenja</span><span class="a1-item__sub">Događaji, intervali, primaoci i dokumenti koji se šalju.</span></span>
                </a>
                @if($settingsUser?->hasRole('superadmin'))
                <a class="a1-item" href="{{ route('admin.settings.smtp.index') }}" data-settings-item data-settings-keywords="smtp email e-mail server slanje pošte lozinka cpanel noreply">
                    <span class="a1-item__icon"><x-icon name="mail" :shell="true" /></span>
                    <span class="a1-item__main"><span class="a1-item__title">E-mail (SMTP)</span><span class="a1-item__sub">SMTP server za slanje poruka, šifrovana lozinka i probni e-mail.</span></span>
                </a>
                @endif
                @can('automation.manage')
                <a class="a1-item" href="{{ route('admin.settings.automation.index') }}" data-settings-item data-settings-keywords="automatizacija upozorenja alert digest scheduler">
                    <span class="a1-item__icon"><x-icon name="cog" :shell="true" /></span>
                    <span class="a1-item__main"><span class="a1-item__title">Automatizacija i upozorenja</span><span class="a1-item__sub">Automatske provere, upozorenja i dnevni pregledi.</span></span>
                </a>
                @endcan
            </div>
        </section>

        <section id="settings-interface" class="a1-card a1-set-hubsec" data-settings-section aria-labelledby="settings-interface-title">
            <div class="a1-card__title">
                <span class="a1-eyebrow">Interfejs i bezbednost</span>
                <h2 class="a1-h2" id="settings-interface-title">Izgled, prijava i sistem</h2>
            </div>
            <div class="a1-list a1-list--grid">
                <a class="a1-item" href="{{ route('admin.settings.appearance') }}" data-settings-item data-settings-keywords="izgled tema logo favicon footer prijava login background slideshow youtube">
                    <span class="a1-item__icon"><x-icon name="monitor" :shell="true" /></span>
                    <span class="a1-item__main"><span class="a1-item__title">Izgled sajta i prijave</span><span class="a1-item__sub">Logo, favicon, footer, tema i pozadina stranice za prijavu.</span></span>
                </a>
                <a class="a1-item" href="{{ route('admin.settings.turnstile.index') }}" data-settings-item data-settings-keywords="cloudflare turnstile captcha bezbednost prijava">
                    <span class="a1-item__icon"><x-icon name="shield" :shell="true" /></span>
                    <span class="a1-item__main"><span class="a1-item__title">Cloudflare Turnstile</span><span class="a1-item__sub">Zaštita javnih formi i prijave od automatizovanih zloupotreba.</span></span>
                </a>
                @can('system.health')
                <a class="a1-item" href="{{ route('admin.settings.system-health.index') }}" data-settings-item data-settings-keywords="system health backup baza scheduler storage bezbednost">
                    <span class="a1-item__icon"><x-icon name="health" :shell="true" /></span>
                    <span class="a1-item__main"><span class="a1-item__title">System Health & Backup</span><span class="a1-item__sub">Zdravlje sistema, backup, scheduler, storage i bezbednosne provere.</span></span>
                </a>
                @endcan
            </div>
        </section>
        @endcan

        @can('system.manage_users')
        <section id="settings-access" class="a1-card a1-set-hubsec" data-settings-section aria-labelledby="settings-access-title">
            <div class="a1-card__title">
                <span class="a1-eyebrow">Pristup</span>
                <h2 class="a1-h2" id="settings-access-title">Korisničke grupe i ovlašćenja</h2>
            </div>
            <div class="a1-list a1-list--grid">
                <a class="a1-item" href="{{ route('admin.user-groups.index') }}" data-settings-item data-settings-keywords="grupe pristupa role permission dozvole korisnici">
                    <span class="a1-item__icon"><x-icon name="user-check" :shell="true" /></span>
                    <span class="a1-item__main"><span class="a1-item__title">Grupe pristupa</span><span class="a1-item__sub">Organizacija korisnika i pravila pristupa funkcijama sistema.</span></span>
                </a>
            </div>
        </section>
        @endcan
    </div>

    <div class="a1-empty" data-settings-empty hidden>
        <p>Nema podešavanja koja odgovaraju pretrazi.</p>
    </div>
</div>



@push('scripts')
<script nonce="{{ request()->attributes->get('_csp_nonce') }}">
(() => {
    const input = document.querySelector('[data-settings-search]');
    const list = document.querySelector('[data-settings-list]');
    const empty = document.querySelector('[data-settings-empty]');
    const status = document.querySelector('[data-settings-search-status]');
    if (!input || !list) return;

    const normalize = (value) => String(value || '')
        .toLocaleLowerCase('sr-Latn')
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '');

    const items = Array.from(list.querySelectorAll('[data-settings-item]'));
    const sections = Array.from(list.querySelectorAll('[data-settings-section]'));

    const apply = () => {
        const query = normalize(input.value.trim());
        let visible = 0;

        items.forEach((item) => {
            const haystack = normalize(`${item.textContent || ''} ${item.dataset.settingsKeywords || ''}`);
            const show = query === '' || haystack.includes(query);
            item.hidden = !show;
            if (show) visible += 1;
        });

        sections.forEach((section) => {
            const hasVisible = Array.from(section.querySelectorAll('[data-settings-item]'))
                .some((item) => !item.hidden);
            section.hidden = !hasVisible;
        });

        if (empty) empty.hidden = visible !== 0;
        if (status) {
            status.textContent = query === ''
                ? 'Prikazana su sva podešavanja dostupna tvom nalogu.'
                : visible === 1
                    ? 'Pronađeno je 1 podešavanje.'
                    : `Pronađeno je ${visible} podešavanja.`;
        }
    };

    document.addEventListener('keydown', (event) => {
        const target = event.target;
        const typing = target instanceof HTMLInputElement
            || target instanceof HTMLTextAreaElement
            || target instanceof HTMLSelectElement
            || target?.isContentEditable;

        if (event.key === '/' && !typing && !event.ctrlKey && !event.metaKey && !event.altKey) {
            event.preventDefault();
            input.focus();
            input.select();
            return;
        }

        if (event.key === 'Escape' && document.activeElement === input && input.value !== '') {
            input.value = '';
            apply();
            input.focus();
        }
    });

    input.addEventListener('input', apply);
    apply();
})();
</script>
@endpush
@endsection
