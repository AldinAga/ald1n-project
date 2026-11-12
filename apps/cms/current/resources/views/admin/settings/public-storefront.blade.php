@extends('layouts.app')

@section('title','Javna prodavnica — uslovi trgovca')
@section('content')
@php
    $policy = $review['policy'];
    // The hidden fingerprint is always posted, so its presence in the old input
    // means this form was submitted: show exactly what was sent, including an
    // unticked list (an unticked checkbox group sends no key at all).
    $formResubmitted = old('approval_fingerprint') !== null;
    $selectedMethods = $formResubmitted ? (array) old('payment_methods', []) : $policy['methods'];
    $selectedDeliveries = $formResubmitted ? (array) old('delivery_methods', []) : $policy['deliveries'];
@endphp
<div class="page-heading">
    <div>
        <span class="eyebrow">Poslovanje / javna prodavnica</span>
        <h1>Uslovi javnog poručivanja</h1>
        <p>Priprema uslova prodaje, dostave i plaćanja. Čuvanje podešavanja ne otvara javnu prodavnicu.</p>
    </div>
</div>

@include('admin.settings.partials.context-nav', ['settingsSection'=>'Javna prodavnica'])

@if(session('status'))
    <div class="alert alert-success" role="status">{{ session('status') }}</div>
@endif
@if(session('approval_refused'))
    <div class="alert alert-warning" role="alert">{{ session('approval_refused') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Neke stavke zahtevaju proveru:</strong>
        <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<section class="panel form-section" aria-labelledby="store-switch-title">
    <h2 id="store-switch-title">Javna prodavnica ON / OFF</h2>
    <p>Trenutno: <strong>{{ $storeOpen ? 'ON' : 'OFF' }}</strong>.
        OFF zaustavlja nove kupovine; postojeće porudžbine i privatna dokumenta ostaju sačuvani.</p>
    @if($storeRequestedOpen && !$storeOpen)<p class="muted">Tehnička dozvola trenutno blokira otvaranje.</p>@endif
    @php $onBlocked = !$storeRequestedOpen && !$readiness['ready']; @endphp
    <form method="post" action="{{ route('admin.settings.public-storefront.toggle') }}">
        @csrf
        <input type="hidden" name="enabled" value="{{ $storeRequestedOpen ? '0' : '1' }}">
        <button type="submit" class="button button-primary" role="switch"
            aria-checked="{{ $storeOpen ? 'true' : 'false' }}"
            @if($onBlocked) disabled aria-describedby="store-on-blocked" @endif
            aria-label="Javna prodavnica ON / OFF">{{ $storeRequestedOpen ? 'OFF - Isključi prodavnicu' : 'ON - Uključi prodavnicu' }}</button>
    </form>
    @if($onBlocked)
    <p id="store-on-blocked" class="store-ready-reason" data-store-on-blocked><strong>ON još nije moguć.</strong> Nije ispunjeno: {{ implode('; ', $readiness['failed']) }}.</p>
    @endif
    <p class="muted">ON je moguć tek kada su ispunjena sva tri uslova ispod. Server ih ponovo proverava pri svakom uključivanju.</p>

    <h3 id="store-checklist-title">Uslovi za ON</h3>
    <ol class="store-checklist" aria-labelledby="store-checklist-title">
        @foreach($readiness['requirements'] as $reqKey => $requirement)
        <li class="store-check {{ $requirement['ok'] ? 'is-ok' : 'is-missing' }}" data-store-requirement="{{ $reqKey }}" data-ok="{{ $requirement['ok'] ? '1' : '0' }}">
            <details @if(!$requirement['ok']) open @endif>
                <summary>
                    <x-icon :name="$requirement['ok'] ? 'check-circle' : 'x-circle'" />
                    <span class="store-check-state">{{ $requirement['ok'] ? 'Ispunjeno' : 'Nije ispunjeno' }}:</span>
                    <strong>{{ $requirement['label'] }}</strong>
                </summary>
                <ul>
                    @foreach($requirement['items'] as $item)
                    <li class="store-check-item {{ $item['ok'] ? 'is-ok' : 'is-missing' }}" data-store-item="{{ $item['code'] }}" data-ok="{{ $item['ok'] ? '1' : '0' }}">
                        <span class="store-check-state">{{ $item['ok'] ? '✓ Ispunjeno' : '✗ Nedostaje' }}</span>
                        <span>{{ $item['label'] }}</span>
                        @if($item['help'] !== '')<small class="muted">{{ $item['help'] }}</small>@endif
                        @php
                            $link = match ($item['code']) {
                                'identity' => ['href'=>route('admin.settings.documents.index'),'label'=>'Otvori PDF i fakturisanje'],
                                'bank_account' => ['href'=>route('admin.settings.bank-accounts.index'),'label'=>'Otvori žiro račune'],
                                'terms_url','privacy_url' => ['href'=>'#legal-pages-title','label'=>'Idi na pravne stranice'],
                                'shipping_description','shipping_fee','payment_methods','delivery_methods','cash_delivery' => ['href'=>'#policy-delivery-title','label'=>'Idi na dostavu i plaćanje'],
                                'support_email' => ['href'=>'#policy-legal-title','label'=>'Idi na kontakt e-poštu'],
                                'approval','policy_complete' => ['href'=>'#policy-approval-title','label'=>'Idi na potvrdu uslova'],
                                default => null,
                            };
                        @endphp
                        @if($link && !$item['ok'])<a href="{{ $link['href'] }}">{{ $link['label'] }}</a>@endif
                    </li>
                    @endforeach
                </ul>
            </details>
        </li>
        @endforeach
    </ol>
</section>

<section class="panel form-section" aria-labelledby="legal-pages-title">
    <div class="section-heading-row"><div>
        <h2 id="legal-pages-title">Pravne stranice prodavnice</h2>
        <p class="muted">CMS objavljuje ove stranice i dok je prodavnica OFF. Ne prikazuju artikle, cene ni podatke kupaca.</p>
    </div></div>
    <div class="alert" role="note"><strong>Tekstovi su šablon.</strong> Pre otvaranja prodavnice vlasnik, a poželjno i pravnik, mora da pregleda svaki tekst. Svaka izmena teksta povlači potvrdu uslova.</div>
    <p class="muted" data-legal-durable-medium>Obrazac za odustanak i obaveštenje o pravu na odustanak kupac mora da dobije i na trajnom nosaču podataka, na primer u e-poruci sa potvrdom porudžbine (Zakon o zaštiti potrošača, "Sl. glasnik RS", br. 35/2026, član 32). Obrazac na sajtu uskladi sa obrascem koji propisuje ministar (član 29). Dok je tekst šablon ili uslovi nisu potvrđeni, javne stranice nose oznaku nacrta.</p>
    <ul class="stack-list legal-url-list">
        @foreach($legalUrls as $key => $legalUrl)
        <li class="setting-row" data-legal-url="{{ $key }}">
            <div>
                <strong>{{ $legalUrl['title'] }}</strong>
                <p class="muted"><code>{{ $legalUrl['url'] }}</code>@unless($legalUrl['valid']) — ne prolazi HTTPS proveru @endunless</p>
            </div>
            <span>
                <a href="{{ $legalUrl['path'] }}" target="_blank" rel="noopener">Pregled</a>
                @if(array_key_exists($key, $legalCustom))
                · <a href="{{ route('admin.settings.public-storefront.legal.edit', $key) }}">Uredi tekst</a>
                ({{ $legalCustom[$key] ? 'sopstveni tekst' : 'šablon' }})
                @else
                · generiše se iz podešavanja
                @endif
            </span>
        </li>
        @endforeach
    </ul>
    @if($legalUrls['terms']['valid'] && $legalUrls['privacy']['valid'])
    <button type="button" class="button" data-use-legal-urls
        data-terms-url="{{ $legalUrls['terms']['url'] }}" data-privacy-url="{{ $legalUrls['privacy']['url'] }}">Koristi ove adrese</button>
    <p class="muted" data-use-legal-urls-status aria-live="polite">Popunjava polja uslova prodaje i politike privatnosti ispod. Zatim sačuvaj podešavanja.</p>
    @else
    <p class="alert error" role="alert">APP_URL nije validna javna HTTPS adresa, pa ove adrese ne mogu da se koriste kao uslovi prodaje i politika privatnosti.</p>
    @endif
    @if($legalMissing !== [])
    <h3>Podaci koji nedostaju na pravnim stranicama</h3>
    <p class="muted">Stranice umesto njih prikazuju „podatak nije unet”.</p>
    <ul data-legal-missing>@foreach($legalMissing as $label)<li>{{ $label }}</li>@endforeach</ul>
    <p class="muted">Identitet, matični broj, telefon i e-pošta firme unose se u <a href="{{ route('admin.settings.documents.index') }}">PDF i fakturisanje</a>; dostava, plaćanje i e-pošta podrške ispod na ovoj stranici.</p>
    @else
    <p class="muted">Svi podaci za pravne stranice su uneti.</p>
    @endif
</section>

<section class="panel form-section" aria-labelledby="buyer-mail-title">
    <div class="section-heading-row"><div>
        <h2 id="buyer-mail-title">Email za potvrdu registracije Kupca</h2>
        <p class="muted">Samo za samostalnu registraciju Kupca; potvrde porudžbina i interne pozivnice zadržavaju postojeća pravila.</p>
    </div></div>
    <p>Trenutni pošiljalac: <strong>{{ $registrationMail['approved'] ? $registrationMail['address'] : 'Nije odobren' }}</strong>
        @if($registrationMail['approved']) — {{ $registrationMail['name'] }} @endif</p>
    @if(!$registrationMail['transport_ready'])
    <div class="alert alert-danger" role="alert">Produkcioni mail transport mora biti SMTP ili sendmail pre aktivacije registracije.</div>
    @endif
    <form method="post" action="{{ route('admin.settings.public-storefront.registration-sender') }}">
        @csrf @method('put')
        <div class="field-grid">
            <label class="field"><span>Email adresa pošiljaoca</span>
                <input type="email" name="registration_from_email" maxlength="254" required
                    value="{{ old('registration_from_email',$registrationMail['address']) }}"
                    placeholder="registracija@domen.rs" autocomplete="off"></label>
            <label class="field"><span>Naziv pošiljaoca</span>
                <input name="registration_from_name" maxlength="100" required
                    value="{{ old('registration_from_name',$registrationMail['name']) }}"
                    placeholder="AP Computers" autocomplete="off"></label>
        </div>
        <p class="muted">Sistemski MAIL_FROM_ADDRESS: {{ $systemMailFrom }} (informativno).
            SMTP provajder mora dopuštati odabranu adresu. SPF/DKIM/DMARC i stvarnu dostavu treba proveriti
            pre produkcionog ON. SMTP lozinka se ovde ne menja.</p>
        <label class="field"><input type="checkbox" name="confirm_authorized_sender" value="1" required>
            Potvrđujem da je pošiljalac pod našom kontrolom i autorizovan za postojeći SMTP provajder.</label>
        <button class="button button-primary" type="submit">Sačuvaj pošiljaoca registracije</button>
    </form>
    <p class="muted">Ovim se ne uključuje javna prodavnica i ne šalje testni email.</p>
</section>

<form method="post" action="{{ route('admin.settings.public-storefront.update') }}"
      class="admin-form-grid settings-primary-form">
    @csrf
    @method('put')
    <div class="form-main">
        <section class="panel form-section">
            <div class="section-heading-row">
                <div>
                    <h2>Kontrolna lista</h2>
                    <p class="muted">Ovaj ekran ne menja proizvodni režim, migracije, lager niti podatke kupaca.</p>
                </div>
            </div>
            <div class="stack-list">
                <div class="setting-row">
                    <div><strong>Javni katalog</strong><p class="muted">Zasebna produkciona dozvola.</p></div>
                    <strong>{{ $catalogEnabled ? 'Dozvoljen konfiguracijom' : 'Isključen' }}</strong>
                </div>
                <div class="setting-row">
                    <div><strong>Javno poručivanje</strong><p class="muted">Odobrava se tek posle svih release provera.</p></div>
                    <strong>{{ $checkoutEnabled ? 'Dozvoljen konfiguracijom' : 'Isključen' }}</strong>
                </div>
                <div class="setting-row">
                    <div><strong>Poslovna dokumentacija</strong><p class="muted">Identitet trgovca se podešava u PDF dokumentima.</p></div>
                    <a href="{{ route('admin.settings.documents.index') }}">Uredi identitet</a>
                </div>
                <div class="setting-row">
                    <div><strong>Potvrda stvarnih uslova</strong><p class="muted">Potpis se poništava pri svakoj kasnijoj izmeni uslova.</p></div>
                    <strong>{{ $review['approved'] ? 'Potvrđeno' : 'Nepotvrđeno' }}</strong>
                </div>
            </div>
            @if($review['issues'] !== [])
                <h3>Stavke koje nisu spremne</h3>
                <ul>@foreach($review['issues'] as $issue)<li>{{ $issue }}</li>@endforeach</ul>
            @else
                <p class="muted">Uneti podaci prolaze tehničku proveru. Stvarna sadržina pravnih uslova i dalje zahteva odobrenje trgovca.</p>
            @endif
        </section>

        <section class="panel form-section">
            <div class="section-heading-row"><div>
                <h2 id="policy-legal-title">Uslovi prodaje i privatnost</h2>
                <p class="muted">Koristi stvarne objavljene HTTPS adrese. Nisu unapred izmišljeni pravni dokumenti.</p>
            </div></div>
            <div class="field-grid">
                <label class="field">
                    <span>Uslovi prodaje — HTTPS adresa</span>
                    <input type="url" name="terms_url" id="merchant-terms-url" maxlength="500"
                           value="{{ old('terms_url',$policy['terms_url']) }}"
                           placeholder="https://.../uslovi-prodaje" autocomplete="off">
                </label>
                <label class="field">
                    <span>Politika privatnosti — HTTPS adresa</span>
                    <input type="url" name="privacy_url" id="merchant-privacy-url" maxlength="500"
                           value="{{ old('privacy_url',$policy['privacy_url']) }}"
                           placeholder="https://.../politika-privatnosti" autocomplete="off">
                </label>
                <label class="field">
                    <span>Kontakt e-mail za kupce</span>
                    <input type="email" name="support_email" maxlength="254"
                           value="{{ old('support_email',$policy['support_email']) }}"
                           placeholder="prodaja@domen.rs" autocomplete="off">
                </label>
            </div>
        </section>

        <section class="panel form-section">
            <div class="section-heading-row"><div>
                <h2 id="policy-delivery-title">Dostava i plaćanje</h2>
                <p class="muted">Naplata dostave nije integrisana u obračun iznosa porudžbine. Ne postoji automatska pretpostavka da je dostava besplatna.</p>
            </div></div>
            <div class="field-grid">
                <label class="field">
                    <span>Cena dostave u RSD — unesite izričito</span>
                    <input type="text" inputmode="decimal" name="shipping_fee_rsd"
                           value="{{ old('shipping_fee_rsd',$policy['shipping_fee_rsd']) }}"
                           placeholder="Nije potvrđeno" autocomplete="off">
                    <small class="muted">Trenutni checkout podržava samo izričito odobrenih 0 RSD. Svaka druga vrednost blokira javnu kupovinu.</small>
                </label>
                <label class="field">
                    <span>Stvarna pravila dostave</span>
                    <textarea name="shipping_description" rows="4" maxlength="500"
                              placeholder="Rokovi, područja i druga relevantna pravila">{{ old('shipping_description',$policy['shipping_description']) }}</textarea>
                </label>
                <div class="field">
                    <span>Dozvoljeni načini plaćanja</span>
                    <label><input type="checkbox" name="payment_methods[]" value="cash_on_delivery"
                        @checked(in_array('cash_on_delivery',$selectedMethods,true))> Plaćanje pouzećem</label>
                    <label><input type="checkbox" name="payment_methods[]" value="bank_transfer"
                        @checked(in_array('bank_transfer',$selectedMethods,true))> Bankovni transfer nakon provere uplate</label>
                    <label><input type="checkbox" name="payment_methods[]" value="cash"
                        @checked(in_array('cash',$selectedMethods,true))> Plaćanje pri ličnom preuzimanju/dostavi</label>
                    <small class="muted">Nema odloženog plaćanja za Kupca. Izbor načina nije potvrda uplate.</small>
                </div>
                <div class="field">
                    <span>Dostupni načini dostave</span>
                    @foreach(\App\Support\PublicRetailOptions::DELIVERY as $code=>$label)
                    <label><input type="checkbox" name="delivery_methods[]" value="{{ $code }}"
                        @checked(in_array($code,$selectedDeliveries,true))> {{ $label }}</label>
                    @endforeach
                </div>
                <label class="field">
                    <span>Aktivan žiro račun za bankovni transfer</span>
                    <select name="bank_account_id">
                        <option value="">Nije izabran</option>
                        @foreach($bankAccounts as $account)
                            <option value="{{ $account->id }}" @selected((string)old('bank_account_id',$policy['bank_account_id']) === (string)$account->id)>
                                {{ $account->label }} — {{ $account->recipient_name }}
                            </option>
                        @endforeach
                    </select>
                    <small class="muted">Za izbor ili promenu računa koristi <a href="{{ route('admin.settings.bank-accounts.index') }}">žiro račune</a>.</small>
                </label>
            </div>
        </section>
    </div>

    <aside class="form-side">
        <section class="panel sticky-card form-section">
            <h2 id="policy-approval-title">Potvrdi uslove</h2>
            <p class="store-approval-state {{ $review['approved'] ? 'is-ok' : 'is-missing' }}" data-approval-state="{{ $review['approved'] ? 'approved' : ($review['signature']['present'] ? 'stale' : 'none') }}">
                <x-icon :name="$review['approved'] ? 'check-circle' : 'x-circle'" />
                @if($review['approved'])
                    <strong>Potvrđeno</strong>
                    @if($review['signature']['at'] !== '') {{ \Illuminate\Support\Carbon::parse($review['signature']['at'])->timezone(config('app.timezone'))->format('d.m.Y. H:i') }}@endif
                    @if($approverName) · {{ $approverName }}@endif
                @elseif($review['signature']['present'])
                    <strong>Potvrda više ne važi</strong> — uslovi su se promenili posle potpisa.
                @else
                    <strong>Nije potvrđeno</strong>
                @endif
            </p>
            <p class="muted">Potvrda je tvoj potpis na tačan skup: identitet trgovca, adrese uslova i privatnosti, dostava, plaćanje, e-pošta podrške i tekstovi pravnih stranica. Svaka kasnija izmena bilo kog dela povlači potvrdu.</p>
            @unless($review['complete'])
            <p class="muted"><strong>Potvrda još nije moguća:</strong> prvo ispuni stavke iz kontrolne liste.</p>
            @endunless
            <p class="muted">Ako sačuvaš bez potvrde, prethodna potvrda se povlači. Javno poručivanje ostaje zaključano.</p>
            {{-- Exact state shown on this page. Approval is refused if anything changed elsewhere since loading.
                 After a refused (stale) submit the OLD fingerprint is kept, so sending the form again stays
                 stale until the page is loaded anew. --}}
            <input type="hidden" name="approval_fingerprint" value="{{ old('approval_fingerprint', $review['fingerprint']) }}" data-approval-fingerprint>
            <label>
                <input type="checkbox" name="merchant_approval" value="1"
                       @checked(old('merchant_approval')==='1')>
                <span>Ja kao SuperAdministrator potvrđujem da sam proverio i odobrio stvarne uslove prodaje, privatnosti, dostave i plaćanja.</span>
            </label>
            <button class="button button-primary" type="submit">Sačuvaj podešavanja</button>
            <p class="muted">Potvrda neće aktivirati produkciju, poslati e-mail, pokrenuti migracije ili napraviti porudžbinu.</p>
        </section>
    </aside>
</form>
@endsection

@push('styles')
<style>
.store-checklist{list-style:none;margin:12px 0 0;padding:0;display:grid;gap:10px}
.store-check{border:1px solid var(--line);border-radius:14px;padding:4px 14px}
.store-check summary{cursor:pointer;display:flex;flex-wrap:wrap;align-items:center;gap:8px;padding:10px 0}
.store-check ul{list-style:none;margin:0 0 12px;padding:0;display:grid;gap:8px}
.store-check-item{display:grid;gap:3px;padding:8px 10px;border-radius:10px;background:color-mix(in srgb,var(--line) 30%,transparent)}
.store-check.is-ok>details>summary .store-check-state,.store-check-item.is-ok .store-check-state,.store-approval-state.is-ok{color:var(--green,#1b8a5a)}
.store-check.is-missing>details>summary .store-check-state,.store-check-item.is-missing .store-check-state,.store-approval-state.is-missing{color:var(--red,#c23b4b)}
.store-check-state{font-weight:700}
.store-ready-reason{margin:10px 0;padding:10px 12px;border:1px solid var(--line);border-radius:12px}
.store-approval-state{display:flex;flex-wrap:wrap;align-items:center;gap:6px}
</style>
@endpush

@push('scripts')
<script nonce="{{ request()->attributes->get('_csp_nonce') }}">
(() => {
    const button = document.querySelector('[data-use-legal-urls]');
    if (!button) return;
    button.addEventListener('click', () => {
        const terms = document.getElementById('merchant-terms-url');
        const privacy = document.getElementById('merchant-privacy-url');
        if (!terms || !privacy) return;
        terms.value = button.dataset.termsUrl || '';
        privacy.value = button.dataset.privacyUrl || '';
        const status = document.querySelector('[data-use-legal-urls-status]');
        if (status) status.textContent = 'Adrese su upisane. Sačuvaj podešavanja da bi važile.';
        terms.focus();
    });
})();
</script>
@endpush
