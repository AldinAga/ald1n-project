@extends('layouts.app')

@section('title', 'Podešavanja')

@section('content')
@php
    $settingsUser = auth()->user();
@endphp

<div class="settings-hub">
    <div class="page-heading settings-hub-heading">
        <div>
            <span class="eyebrow">Administracija</span>
            <h1>Podešavanja</h1>
            <p>Jedno mesto za poslovna pravila, izgled, komunikaciju, logistiku, šifarnike, pristup i sistemske opcije.</p>
        </div>
    </div>

    <section class="panel settings-hub-search-panel">
        <label class="settings-hub-search">
            <span>Pronađi podešavanje</span>
            <input type="search" placeholder="Npr. kurir, kurs, e-mail, garancija, kategorija..." data-settings-search autocomplete="off" aria-label="Pretraži podešavanja" aria-describedby="settings-search-status" aria-keyshortcuts="/">
        </label>
        <p id="settings-search-status" class="muted" data-settings-search-status aria-live="polite">Prikazana su sva podešavanja dostupna tvom nalogu. Pritisni / za brzu pretragu.</p>
    </section>

    <nav class="settings-hub-category-nav" aria-label="Kategorije podešavanja">
        <a href="#settings-business">Poslovanje</a>
        <a href="#settings-rules">Pravila i šifarnici</a>
        <a href="#settings-communication">Komunikacija</a>
        <a href="#settings-interface">Izgled i bezbednost</a>
        <a href="#settings-access">Pristup</a>
    </nav>

    <div class="settings-hub-sections" data-settings-list>
        @can('system.manage_settings')
        <section id="settings-business" class="settings-hub-section" data-settings-section>
            <div class="settings-hub-section-heading">
                <div>
                    <span class="eyebrow">Poslovanje</span>
                    <h2>Finansije, naplata i logistika</h2>
                </div>
            </div>
            <div class="settings-hub-grid">
                <a class="settings-hub-card" href="{{ route('admin.settings.exchange.index') }}" data-settings-item data-settings-keywords="kurs eur rsd valuta nbs finansije">
                    <span class="settings-hub-icon"><x-icon name="coins" /></span>
                    <span><strong>EUR/RSD kurs</strong><small>Automatski ili ručni kurs i centralna sinhronizacija.</small></span>
                </a>
                <a class="settings-hub-card" href="{{ route('admin.settings.bank-accounts.index') }}" data-settings-item data-settings-keywords="žiro racun račun banka uplata ips">
                    <span class="settings-hub-icon"><x-icon name="wallet" /></span>
                    <span><strong>Žiro računi</strong><small>Računi koji se koriste u poslovnim dokumentima i plaćanju.</small></span>
                </a>
                @if($settingsUser?->hasRole('superadmin'))
                <a class="settings-hub-card" href="{{ route('admin.settings.couriers.index') }}" data-settings-item data-settings-keywords="kurir kurirske službe tracking praćenje pošiljka post express d express bex aks gls">
                    <span class="settings-hub-icon"><x-icon name="truck" /></span>
                    <span><strong>Kurirske službe</strong><small>Dodavanje kurira, redosled, podrazumevani kurir i zvanični tracking linkovi.</small></span>
                </a>
                @endif
            </div>
        </section>
        @endcan

        @canany(['catalog.manage_taxonomy','receivables.manage','warranties.manage','field_operations.manage','service_parts.procurement'])
        <section id="settings-rules" class="settings-hub-section" data-settings-section>
            <div class="settings-hub-section-heading">
                <div>
                    <span class="eyebrow">Poslovna pravila</span>
                    <h2>Katalog, servis i automatizovana pravila</h2>
                </div>
            </div>
            <div class="settings-hub-grid">
                @can('catalog.manage_taxonomy')
                <article class="settings-hub-card settings-hub-card-group" data-settings-item data-settings-keywords="katalog šifarnici kategorije brendovi linije tipovi specifikacije">
                    <span class="settings-hub-icon"><x-icon name="boxes" /></span>
                    <span class="settings-hub-card-copy">
                        <strong>Šifarnici kataloga</strong>
                        <small>Kategorije, brendovi, linije, tipovi artikala i polja specifikacija.</small>
                        <span class="settings-hub-actions">
                            <a href="{{ route('admin.dictionary.index','categories') }}">Kategorije</a>
                            <a href="{{ route('admin.dictionary.index','brands') }}">Brendovi</a>
                            <a href="{{ route('admin.dictionary.index','product-lines') }}">Linije</a>
                            <a href="{{ route('admin.dictionary.index','product-types') }}">Tipovi</a>
                            <a href="{{ route('admin.dictionary.index','specification-fields') }}">Specifikacije</a>
                        </span>
                    </span>
                </article>
                @endcan

                @can('receivables.manage')
                <a class="settings-hub-card" href="{{ route('admin.receivables.index') }}" data-settings-item data-settings-keywords="potraživanja naplata reminder podsetnici rokovi automatsko kreiranje">
                    <span class="settings-hub-icon"><x-icon name="wallet" /></span>
                    <span><strong>Pravila potraživanja</strong><small>Automatsko kreiranje slučajeva, rokovi, podsetnici i primaoci.</small></span>
                </a>
                @endcan

                @can('warranties.manage')
                <a class="settings-hub-card" href="{{ route('admin.warranties.index') }}" data-settings-item data-settings-keywords="garancija garancije pravila održavanje backfill warranty">
                    <span class="settings-hub-icon"><x-icon name="shield" /></span>
                    <span><strong>Garancijska pravila</strong><small>Pravila izdavanja garancija, periodi i održavanje.</small></span>
                </a>
                @endcan

                @can('field_operations.manage')
                <a class="settings-hub-card" href="{{ route('admin.field-service-teams.index') }}" data-settings-item data-settings-keywords="terenske ekipe partneri servis raspored">
                    <span class="settings-hub-icon"><x-icon name="users" /></span>
                    <span><strong>Terenske ekipe i partneri</strong><small>Šifarnik ekipa koje se koriste u servisnim i terenskim nalozima.</small></span>
                </a>
                @endcan

                @can('service_parts.procurement')
                <a class="settings-hub-card" href="{{ route('admin.service-part-suppliers.index') }}" data-settings-item data-settings-keywords="dobavljači rezervni delovi servis nabavka">
                    <span class="settings-hub-icon"><x-icon name="receipt" /></span>
                    <span><strong>Dobavljači servisnih delova</strong><small>Centralni spisak dobavljača za nabavku rezervnih delova.</small></span>
                </a>
                @endcan
            </div>
        </section>
        @endcanany

        @can('system.manage_settings')
        <section id="settings-communication" class="settings-hub-section" data-settings-section>
            <div class="settings-hub-section-heading">
                <div>
                    <span class="eyebrow">Komunikacija</span>
                    <h2>Dokumenti, e-mail i automatizacija</h2>
                </div>
            </div>
            <div class="settings-hub-grid">
                <a class="settings-hub-card" href="{{ route('admin.settings.documents.index') }}" data-settings-item data-settings-keywords="pdf faktura fakturisanje dokumenti račun predračun otpremnica logo">
                    <span class="settings-hub-icon"><x-icon name="file-text" /></span>
                    <span><strong>PDF i fakturisanje</strong><small>Vizuelni i poslovni parametri dokumenata i fakturisanja.</small></span>
                </a>
                <a class="settings-hub-card" href="{{ route('admin.settings.order-emails.index') }}" data-settings-item data-settings-keywords="email e-mail poruke obaveštenja dokumenti korisnici">
                    <span class="settings-hub-icon"><x-icon name="mail" /></span>
                    <span><strong>E-mail obaveštenja</strong><small>Događaji, intervali, primaoci i dokumenti koji se šalju.</small></span>
                </a>
                @can('automation.manage')
                <a class="settings-hub-card" href="{{ route('admin.settings.automation.index') }}" data-settings-item data-settings-keywords="automatizacija upozorenja alert digest scheduler">
                    <span class="settings-hub-icon"><x-icon name="cog" /></span>
                    <span><strong>Automatizacija i upozorenja</strong><small>Automatske provere, upozorenja i dnevni pregledi.</small></span>
                </a>
                @endcan
            </div>
        </section>

        <section id="settings-interface" class="settings-hub-section" data-settings-section>
            <div class="settings-hub-section-heading">
                <div>
                    <span class="eyebrow">Interfejs i bezbednost</span>
                    <h2>Izgled, prijava i sistem</h2>
                </div>
            </div>
            <div class="settings-hub-grid">
                <a class="settings-hub-card" href="{{ route('admin.settings.appearance') }}" data-settings-item data-settings-keywords="izgled tema logo favicon footer prijava login background slideshow youtube">
                    <span class="settings-hub-icon"><x-icon name="monitor" /></span>
                    <span><strong>Izgled sajta i prijave</strong><small>Logo, favicon, footer, tema i pozadina stranice za prijavu.</small></span>
                </a>
                <a class="settings-hub-card" href="{{ route('admin.settings.turnstile.index') }}" data-settings-item data-settings-keywords="cloudflare turnstile captcha bezbednost prijava">
                    <span class="settings-hub-icon"><x-icon name="shield" /></span>
                    <span><strong>Cloudflare Turnstile</strong><small>Zaštita javnih formi i prijave od automatizovanih zloupotreba.</small></span>
                </a>
                @can('system.health')
                <a class="settings-hub-card" href="{{ route('admin.settings.system-health.index') }}" data-settings-item data-settings-keywords="system health backup baza scheduler storage bezbednost">
                    <span class="settings-hub-icon"><x-icon name="health" /></span>
                    <span><strong>System Health & Backup</strong><small>Zdravlje sistema, backup, scheduler, storage i bezbednosne provere.</small></span>
                </a>
                @endcan
            </div>
        </section>
        @endcan

        @can('system.manage_users')
        <section id="settings-access" class="settings-hub-section" data-settings-section>
            <div class="settings-hub-section-heading">
                <div>
                    <span class="eyebrow">Pristup</span>
                    <h2>Korisničke grupe i ovlašćenja</h2>
                </div>
            </div>
            <div class="settings-hub-grid">
                <a class="settings-hub-card" href="{{ route('admin.user-groups.index') }}" data-settings-item data-settings-keywords="grupe pristupa role permission dozvole korisnici">
                    <span class="settings-hub-icon"><x-icon name="user-check" /></span>
                    <span><strong>Grupe pristupa</strong><small>Organizacija korisnika i pravila pristupa funkcijama sistema.</small></span>
                </a>
            </div>
        </section>
        @endcan
    </div>

    <div class="empty-state settings-hub-empty" data-settings-empty hidden>
        <p>Nema podešavanja koja odgovaraju pretrazi.</p>
    </div>
</div>



@push('scripts')
<script>
(() => {
    const input = document.querySelector('[data-settings-search]');
    const list = document.querySelector('[data-settings-list]');
    const empty = document.querySelector('[data-settings-empty]');
    const status = document.querySelector('[data-settings-search-status]');
    if (!input || !list) return;

    const normalize = (value) => String(value || '')
        .toLocaleLowerCase('sr-Latn')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');

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
