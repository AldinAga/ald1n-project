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
<div class="a1-ops a1-set">
<header class="a1-pagehead">
    <div class="a1-pagehead__text">
        <span class="a1-eyebrow">Poslovanje / javna prodavnica</span>
        <h1 class="a1-title">Uslovi javnog poručivanja</h1>
        <p class="a1-lead">Priprema uslova prodaje, dostave i plaćanja. Čuvanje podešavanja ne otvara javnu prodavnicu.</p>
    </div>
</header>

@include('admin.settings.partials.context-nav', ['settingsSection'=>'Javna prodavnica'])

@if(session('approval_refused'))
    <div class="a1-alert a1-alert--warning" role="alert"><x-icon name="alert" :size="20" :shell="true" /><div class="a1-alert__body"><p>{{ session('approval_refused') }}</p></div></div>
@endif

<section class="a1-card" aria-labelledby="store-switch-title">
    <h2 class="a1-h2" id="store-switch-title">Javna prodavnica ON / OFF</h2>
    <p>Trenutno: <strong>{{ $storeOpen ? 'ON' : 'OFF' }}</strong>.
        OFF zaustavlja nove kupovine; postojeće porudžbine i privatna dokumenta ostaju sačuvani.</p>
    @if($storeRequestedOpen && !$storeOpen)<p class="a1-muted">Tehnička dozvola trenutno blokira otvaranje.</p>@endif
    @php $onBlocked = !$storeRequestedOpen && !$readiness['ready']; @endphp
    <form method="post" action="{{ route('admin.settings.public-storefront.toggle') }}">
        @csrf
        <input type="hidden" name="enabled" value="{{ $storeRequestedOpen ? '0' : '1' }}">
        <button type="submit" class="a1-btn a1-btn--primary" role="switch"
            aria-checked="{{ $storeOpen ? 'true' : 'false' }}"
            @if($onBlocked) disabled aria-describedby="store-on-blocked" @endif
            aria-label="Javna prodavnica ON / OFF">{{ $storeRequestedOpen ? 'OFF - Isključi prodavnicu' : 'ON - Uključi prodavnicu' }}</button>
    </form>
    @if($onBlocked)
    <div id="store-on-blocked" class="a1-alert a1-alert--warning" data-store-on-blocked><x-icon name="alert" :size="20" :shell="true" /><div class="a1-alert__body"><p><strong>ON još nije moguć.</strong> Nije ispunjeno: {{ implode('; ', $readiness['failed']) }}.</p></div></div>
    @endif
    <p class="a1-muted">ON je moguć tek kada su ispunjena sva tri uslova ispod. Server ih ponovo proverava pri svakom uključivanju.</p>

    <h3 class="a1-h3" id="store-checklist-title">Uslovi za ON</h3>
    <ol class="a1-set-checklist" aria-labelledby="store-checklist-title">
        @foreach($readiness['requirements'] as $reqKey => $requirement)
        <li class="a1-set-req {{ $requirement['ok'] ? 'is-ok' : 'is-missing' }}" data-store-requirement="{{ $reqKey }}" data-ok="{{ $requirement['ok'] ? '1' : '0' }}">
            <details @if(!$requirement['ok']) open @endif>
                <summary>
                    <x-icon :name="$requirement['ok'] ? 'check-circle' : 'x-circle'" :shell="true" />
                    <span class="a1-set-state">{{ $requirement['ok'] ? 'Ispunjeno' : 'Nije ispunjeno' }}:</span>
                    <strong>{{ $requirement['label'] }}</strong>
                </summary>
                <ul>
                    @foreach($requirement['items'] as $item)
                    <li class="a1-set-req__item {{ $item['ok'] ? 'is-ok' : 'is-missing' }}" data-store-item="{{ $item['code'] }}" data-ok="{{ $item['ok'] ? '1' : '0' }}">
                        <span class="a1-set-state">{{ $item['ok'] ? '✓ Ispunjeno' : '✗ Nedostaje' }}</span>
                        <span>{{ $item['label'] }}</span>
                        @if($item['help'] !== '')<small class="a1-help">{{ $item['help'] }}</small>@endif
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
                        @if($link && !$item['ok'])<a class="a1-link" href="{{ $link['href'] }}">{{ $link['label'] }}</a>@endif
                    </li>
                    @endforeach
                </ul>
            </details>
        </li>
        @endforeach
    </ol>
</section>

<section class="a1-card" aria-labelledby="legal-pages-title">
    <div class="a1-card__title">
        <h2 class="a1-h2" id="legal-pages-title">Pravne stranice prodavnice</h2>
        <p class="a1-muted">CMS objavljuje ove stranice i dok je prodavnica OFF. Ne prikazuju artikle, cene ni podatke kupaca.</p>
    </div>
    <div class="a1-alert" role="note"><x-icon name="alert" :size="20" :shell="true" /><div class="a1-alert__body"><p><strong>Tekstovi su šablon.</strong> Pre otvaranja prodavnice vlasnik, a poželjno i pravnik, mora da pregleda svaki tekst. Svaka izmena teksta povlači potvrdu uslova.</p></div></div>
    <p class="a1-muted" data-legal-durable-medium>Obrazac za odustanak i obaveštenje o pravu na odustanak kupac mora da dobije i na trajnom nosaču podataka, na primer u e-poruci sa potvrdom porudžbine (Zakon o zaštiti potrošača, "Sl. glasnik RS", br. 35/2026, član 32). Obrazac na sajtu uskladi sa obrascem koji propisuje ministar (član 29). Dok je tekst šablon ili uslovi nisu potvrđeni, javne stranice nose oznaku nacrta.</p>
    <ul class="a1-list">
        @foreach($legalUrls as $key => $legalUrl)
        <li class="a1-item a1-item--plain a1-item--static" data-legal-url="{{ $key }}">
            <div class="a1-item__main">
                <span class="a1-item__title">{{ $legalUrl['title'] }}</span>
                <span class="a1-item__sub"><code>{{ $legalUrl['url'] }}</code>@unless($legalUrl['valid']) — ne prolazi HTTPS proveru @endunless</span>
            </div>
            <span class="a1-set-links">
                <a class="a1-link" href="{{ $legalUrl['path'] }}" target="_blank" rel="noopener">Pregled</a>
                @if(array_key_exists($key, $legalCustom))
                · <a class="a1-link" href="{{ route('admin.settings.public-storefront.legal.edit', $key) }}">Uredi tekst</a>
                ({{ $legalCustom[$key] ? 'sopstveni tekst' : 'šablon' }})
                @else
                · generiše se iz podešavanja
                @endif
            </span>
        </li>
        @endforeach
    </ul>
    @if($legalUrls['terms']['valid'] && $legalUrls['privacy']['valid'])
    <div>
        <button type="button" class="a1-btn" data-use-legal-urls
            data-terms-url="{{ $legalUrls['terms']['url'] }}" data-privacy-url="{{ $legalUrls['privacy']['url'] }}">Koristi ove adrese</button>
    </div>
    <p class="a1-muted" data-use-legal-urls-status aria-live="polite">Popunjava polja uslova prodaje i politike privatnosti ispod. Zatim sačuvaj podešavanja.</p>
    @else
    <div class="a1-alert a1-alert--danger" role="alert"><x-icon name="alert" :size="20" :shell="true" /><div class="a1-alert__body"><p>APP_URL nije validna javna HTTPS adresa, pa ove adrese ne mogu da se koriste kao uslovi prodaje i politika privatnosti.</p></div></div>
    @endif
    @if($legalMissing !== [])
    <h3 class="a1-h3">Podaci koji nedostaju na pravnim stranicama</h3>
    <p class="a1-muted">Stranice umesto njih prikazuju „podatak nije unet”.</p>
    <ul class="a1-set-ol" data-legal-missing>@foreach($legalMissing as $label)<li>{{ $label }}</li>@endforeach</ul>
    <p class="a1-muted">Identitet, matični broj, telefon i e-pošta firme unose se u <a class="a1-link a1-link--inline" href="{{ route('admin.settings.documents.index') }}">PDF i fakturisanje</a>; dostava, plaćanje i e-pošta podrške ispod na ovoj stranici.</p>
    @else
    <p class="a1-muted">Svi podaci za pravne stranice su uneti.</p>
    @endif
</section>

<section class="a1-card" aria-labelledby="buyer-mail-title">
    <div class="a1-card__title">
        <h2 class="a1-h2" id="buyer-mail-title">Email za potvrdu registracije Kupca</h2>
        <p class="a1-muted">Samo za samostalnu registraciju Kupca; potvrde porudžbina i interne pozivnice zadržavaju postojeća pravila.</p>
    </div>
    <p>Trenutni pošiljalac: <strong>{{ $registrationMail['approved'] ? $registrationMail['address'] : 'Nije odobren' }}</strong>
        @if($registrationMail['approved']) — {{ $registrationMail['name'] }} @endif</p>
    @if(!$registrationMail['transport_ready'])
    <div class="a1-alert a1-alert--danger" role="alert"><x-icon name="alert" :size="20" :shell="true" /><div class="a1-alert__body"><p>Produkcioni mail transport mora biti SMTP ili sendmail pre aktivacije registracije.</p></div></div>
    @endif
    <form method="post" action="{{ route('admin.settings.public-storefront.registration-sender') }}" class="a1-form">
        @csrf @method('put')
        <div class="a1-set-fields">
            <label class="a1-field"><span class="a1-label">Email adresa pošiljaoca</span>
                <input class="a1-input" type="email" name="registration_from_email" maxlength="254" required
                    value="{{ old('registration_from_email',$registrationMail['address']) }}"
                    placeholder="registracija@domen.rs" autocomplete="off"></label>
            <label class="a1-field"><span class="a1-label">Naziv pošiljaoca</span>
                <input class="a1-input" name="registration_from_name" maxlength="100" required
                    value="{{ old('registration_from_name',$registrationMail['name']) }}"
                    placeholder="AP Computers" autocomplete="off"></label>
        </div>
        <p class="a1-muted">Sistemski MAIL_FROM_ADDRESS: {{ $systemMailFrom }} (informativno).
            SMTP provajder mora dopuštati odabranu adresu. SPF/DKIM/DMARC i stvarnu dostavu treba proveriti
            pre produkcionog ON. SMTP lozinka se ovde ne menja.</p>
        <label class="a1-check"><input class="a1-check__input" type="checkbox" name="confirm_authorized_sender" value="1" required>
            <span class="a1-check__box" aria-hidden="true"></span>
            <span>Potvrđujem da je pošiljalac pod našom kontrolom i autorizovan za postojeći SMTP provajder.</span></label>
        <div><button class="a1-btn a1-btn--primary" type="submit">Sačuvaj pošiljaoca registracije</button></div>
    </form>
    <p class="a1-muted">Ovim se ne uključuje javna prodavnica i ne šalje testni email.</p>
</section>

<form method="post" action="{{ route('admin.settings.public-storefront.update') }}"
      class="a1-set-split">
    @csrf
    @method('put')
    <div class="a1-set-main">
        <section class="a1-card" aria-labelledby="policy-checklist-title">
            <div class="a1-card__title">
                <h2 class="a1-h2" id="policy-checklist-title">Kontrolna lista</h2>
                <p class="a1-muted">Ovaj ekran ne menja proizvodni režim, migracije, lager niti podatke kupaca.</p>
            </div>
            <div>
                <div class="a1-set-row">
                    <div><strong>Javni katalog</strong><p class="a1-muted">Zasebna produkciona dozvola.</p></div>
                    <strong>{{ $catalogEnabled ? 'Dozvoljen konfiguracijom' : 'Isključen' }}</strong>
                </div>
                <div class="a1-set-row">
                    <div><strong>Javno poručivanje</strong><p class="a1-muted">Odobrava se tek posle svih release provera.</p></div>
                    <strong>{{ $checkoutEnabled ? 'Dozvoljen konfiguracijom' : 'Isključen' }}</strong>
                </div>
                <div class="a1-set-row">
                    <div><strong>Poslovna dokumentacija</strong><p class="a1-muted">Identitet trgovca se podešava u PDF dokumentima.</p></div>
                    <a class="a1-link" href="{{ route('admin.settings.documents.index') }}">Uredi identitet</a>
                </div>
                <div class="a1-set-row">
                    <div><strong>Potvrda stvarnih uslova</strong><p class="a1-muted">Potpis se poništava pri svakoj kasnijoj izmeni uslova.</p></div>
                    <strong>{{ $review['approved'] ? 'Potvrđeno' : 'Nepotvrđeno' }}</strong>
                </div>
            </div>
            @if($review['issues'] !== [])
                <h3 class="a1-h3">Stavke koje nisu spremne</h3>
                <ul class="a1-set-ol">@foreach($review['issues'] as $issue)<li>{{ $issue }}</li>@endforeach</ul>
            @else
                <p class="a1-muted">Uneti podaci prolaze tehničku proveru. Stvarna sadržina pravnih uslova i dalje zahteva odobrenje trgovca.</p>
            @endif
        </section>

        <section class="a1-card" aria-labelledby="policy-legal-title">
            <div class="a1-card__title">
                <h2 class="a1-h2" id="policy-legal-title">Uslovi prodaje i privatnost</h2>
                <p class="a1-muted">Koristi stvarne objavljene HTTPS adrese. Nisu unapred izmišljeni pravni dokumenti.</p>
            </div>
            <div class="a1-set-fields">
                <label class="a1-field">
                    <span class="a1-label">Uslovi prodaje — HTTPS adresa</span>
                    <input class="a1-input" type="url" name="terms_url" id="merchant-terms-url" maxlength="500"
                           value="{{ old('terms_url',$policy['terms_url']) }}"
                           placeholder="https://.../uslovi-prodaje" autocomplete="off">
                </label>
                <label class="a1-field">
                    <span class="a1-label">Politika privatnosti — HTTPS adresa</span>
                    <input class="a1-input" type="url" name="privacy_url" id="merchant-privacy-url" maxlength="500"
                           value="{{ old('privacy_url',$policy['privacy_url']) }}"
                           placeholder="https://.../politika-privatnosti" autocomplete="off">
                </label>
                <label class="a1-field">
                    <span class="a1-label">Kontakt e-mail za kupce</span>
                    <input class="a1-input" type="email" name="support_email" maxlength="254"
                           value="{{ old('support_email',$policy['support_email']) }}"
                           placeholder="prodaja@domen.rs" autocomplete="off">
                </label>
            </div>
        </section>

        <section class="a1-card" aria-labelledby="policy-delivery-title">
            <div class="a1-card__title">
                <h2 class="a1-h2" id="policy-delivery-title">Dostava i plaćanje</h2>
                <p class="a1-muted">Naplata dostave nije integrisana u obračun iznosa porudžbine. Ne postoji automatska pretpostavka da je dostava besplatna.</p>
            </div>
            <div class="a1-set-fields">
                <label class="a1-field">
                    <span class="a1-label">Cena dostave u RSD — unesite izričito</span>
                    <input class="a1-input" type="text" inputmode="decimal" name="shipping_fee_rsd"
                           value="{{ old('shipping_fee_rsd',$policy['shipping_fee_rsd']) }}"
                           placeholder="Nije potvrđeno" autocomplete="off">
                    <small class="a1-help">Trenutni checkout podržava samo izričito odobrenih 0 RSD. Svaka druga vrednost blokira javnu kupovinu.</small>
                </label>
                <label class="a1-field">
                    <span class="a1-label">Stvarna pravila dostave</span>
                    <textarea class="a1-textarea" name="shipping_description" rows="4" maxlength="500"
                              placeholder="Rokovi, područja i druga relevantna pravila">{{ old('shipping_description',$policy['shipping_description']) }}</textarea>
                </label>
                <fieldset class="a1-set-group">
                    <legend>Dozvoljeni načini plaćanja</legend>
                    <label class="a1-check"><input class="a1-check__input" type="checkbox" name="payment_methods[]" value="cash_on_delivery"
                        @checked(in_array('cash_on_delivery',$selectedMethods,true))><span class="a1-check__box" aria-hidden="true"></span><span>Plaćanje pouzećem</span></label>
                    <label class="a1-check"><input class="a1-check__input" type="checkbox" name="payment_methods[]" value="bank_transfer"
                        @checked(in_array('bank_transfer',$selectedMethods,true))><span class="a1-check__box" aria-hidden="true"></span><span>Bankovni transfer nakon provere uplate</span></label>
                    <label class="a1-check"><input class="a1-check__input" type="checkbox" name="payment_methods[]" value="cash"
                        @checked(in_array('cash',$selectedMethods,true))><span class="a1-check__box" aria-hidden="true"></span><span>Plaćanje pri ličnom preuzimanju/dostavi</span></label>
                    <small class="a1-help">Nema odloženog plaćanja za Kupca. Izbor načina nije potvrda uplate.</small>
                </fieldset>
                <fieldset class="a1-set-group">
                    <legend>Dostupni načini dostave</legend>
                    @foreach(\App\Support\PublicRetailOptions::DELIVERY as $code=>$label)
                    <label class="a1-check"><input class="a1-check__input" type="checkbox" name="delivery_methods[]" value="{{ $code }}"
                        @checked(in_array($code,$selectedDeliveries,true))><span class="a1-check__box" aria-hidden="true"></span><span>{{ $label }}</span></label>
                    @endforeach
                </fieldset>
                <label class="a1-field">
                    <span class="a1-label">Aktivan žiro račun za bankovni transfer</span>
                    <select class="a1-select" name="bank_account_id">
                        <option value="">Nije izabran</option>
                        @foreach($bankAccounts as $account)
                            <option value="{{ $account->id }}" @selected((string)old('bank_account_id',$policy['bank_account_id']) === (string)$account->id)>
                                {{ $account->label }} — {{ $account->recipient_name }}
                            </option>
                        @endforeach
                    </select>
                    <small class="a1-help">Za izbor ili promenu računa koristi <a class="a1-link a1-link--inline" href="{{ route('admin.settings.bank-accounts.index') }}">žiro račune</a>.</small>
                </label>
            </div>
        </section>
    </div>

    <aside class="a1-set-side">
        <section class="a1-card" aria-labelledby="policy-approval-title">
            <h2 class="a1-h2" id="policy-approval-title">Potvrdi uslove</h2>
            <p class="a1-set-approval {{ $review['approved'] ? 'is-ok' : 'is-missing' }}" data-approval-state="{{ $review['approved'] ? 'approved' : ($review['signature']['present'] ? 'stale' : 'none') }}">
                <x-icon :name="$review['approved'] ? 'check-circle' : 'x-circle'" :shell="true" />
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
            <p class="a1-muted">Potvrda je tvoj potpis na tačan skup: identitet trgovca, adrese uslova i privatnosti, dostava, plaćanje, e-pošta podrške i tekstovi pravnih stranica. Svaka kasnija izmena bilo kog dela povlači potvrdu.</p>
            @unless($review['complete'])
            <p class="a1-muted"><strong>Potvrda još nije moguća:</strong> prvo ispuni stavke iz kontrolne liste.</p>
            @endunless
            <p class="a1-muted">Ako sačuvaš bez potvrde, prethodna potvrda se povlači. Javno poručivanje ostaje zaključano.</p>
            {{-- Exact state shown on this page. Approval is refused if anything changed elsewhere since loading.
                 After a refused (stale) submit the OLD fingerprint is kept, so sending the form again stays
                 stale until the page is loaded anew. --}}
            <input type="hidden" name="approval_fingerprint" value="{{ old('approval_fingerprint', $review['fingerprint']) }}" data-approval-fingerprint>
            <label class="a1-check">
                <input class="a1-check__input" type="checkbox" name="merchant_approval" value="1"
                       @checked(old('merchant_approval')==='1')>
                <span class="a1-check__box" aria-hidden="true"></span>
                <span>Ja kao SuperAdministrator potvrđujem da sam proverio i odobrio stvarne uslove prodaje, privatnosti, dostave i plaćanja.</span>
            </label>
            <button class="a1-btn a1-btn--primary" type="submit">Sačuvaj podešavanja</button>
            <p class="a1-muted">Potvrda neće aktivirati produkciju, poslati e-mail, pokrenuti migracije ili napraviti porudžbinu.</p>
        </section>
    </aside>
</form>
</div>
@endsection

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
