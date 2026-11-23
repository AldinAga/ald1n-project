@extends('layouts.app')

@section('title', 'Korisnici i pristup')

@section('content')
@include('admin.partials.g5-styles')
@include('admin.settings.partials.context-nav', ['settingsSection' => 'Pristup i ovlašćenja'])

@php
    $statusLabels = [
        'active' => 'Aktivan',
        'pending' => 'Na čekanju',
        'blocked' => 'Blokiran',
    ];
    $statusTone = [
        'active' => ' a1-pill--ok',
        'pending' => ' a1-pill--warning',
        'blocked' => ' a1-pill--danger',
    ];
@endphp

<div class="a1-ops a1-g5" data-access-control-workspace="users">
    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Pristup i ovlašćenja</span>
            <h1 class="a1-title">Korisnici</h1>
            <p class="a1-lead">Upravljaj nalozima, ulogama i grupama pristupa bez menjanja postojećeg bezbednosnog modela. Administrator i SuperAdmin imaju puni pristup po ulozi, dok se pristup korisnika određuje aktivnom grupom.</p>
        </div>
    </header>

    <nav aria-label="Pristup i ovlašćenja">
        <ul class="a1-tabs">
            <li><a class="a1-tabs__link" href="{{ route('admin.users.index') }}" aria-current="page">Korisnici</a></li>
            <li><a class="a1-tabs__link" href="{{ route('admin.user-groups.index') }}">Grupe pristupa</a></li>
        </ul>
    </nav>

    <div class="a1-grid a1-grid--kpi" role="group" aria-label="Sažetak korisnika">
        <div class="a1-kpi">
            <p class="a1-kpi__label">Rezultati</p>
            <p class="a1-kpi__value">{{ $users->total() }}</p>
            <p class="a1-kpi__note">Korisnika odgovara trenutnim filterima.</p>
        </div>
        <div class="a1-kpi">
            <p class="a1-kpi__label">Sistemske uloge</p>
            <p class="a1-kpi__value">{{ $roles->count() }}</p>
            <p class="a1-kpi__note">Fiksne uloge koje određuju osnovni nivo pristupa.</p>
        </div>
        <div class="a1-kpi">
            <p class="a1-kpi__label">Grupe pristupa</p>
            <p class="a1-kpi__value">{{ $groups->count() }}</p>
            <p class="a1-kpi__note">Paketi dozvola dostupni korisničkoj ulozi.</p>
        </div>
    </div>

    <section class="a1-card" aria-labelledby="users-find-title">
        <div class="a1-os-block">
            <h2 class="a1-os-heading" id="users-find-title">Pronađi korisnika</h2>
            <p class="a1-os-lead">Pretraži po identitetu i suzi listu po statusu naloga.</p>
        </div>

        <form class="a1-form" method="get" action="{{ route('admin.users.index') }}">
            <div class="a1-row">
                <label class="a1-field">
                    <span class="a1-label">Pretraga</span>
                    <input class="a1-input" name="q" value="{{ request('q') }}" placeholder="Korisničko ime, e-mail ili ime">
                </label>
                <label class="a1-field">
                    <span class="a1-label">Status</span>
                    <select class="a1-select" name="status">
                        <option value="">Svi statusi</option>
                        @foreach($statusLabels as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <div class="a1-g5-actions">
                <button class="a1-btn a1-btn--primary" type="submit">Primeni filtere</button>
                <a class="a1-btn" href="{{ route('admin.users.index') }}">Resetuj</a>
            </div>
        </form>
    </section>

    <details class="a1-acc">
        <summary>
            <span class="a1-g5-acc-title">
                <strong>Novi korisnik</strong>
                <span class="a1-muted">Otvori samo kada dodaješ novi nalog.</span>
            </span>
        </summary>

        <div class="a1-acc__body">
            <form method="post" action="{{ route('admin.users.store') }}" class="a1-form">
                @csrf

                <div class="a1-row">
                    <label class="a1-field">
                        <span class="a1-label">Korisničko ime</span>
                        <input class="a1-input" name="username" value="{{ old('username') }}" required autocomplete="off">
                    </label>
                    <label class="a1-field">
                        <span class="a1-label">E-mail</span>
                        <input class="a1-input" type="email" name="email" value="{{ old('email') }}" required autocomplete="off">
                    </label>
                    <label class="a1-field">
                        <span class="a1-label">Telefon</span>
                        <input class="a1-input" name="phone" value="{{ old('phone') }}" autocomplete="off">
                    </label>
                    <label class="a1-field">
                        <span class="a1-label">Ime</span>
                        <input class="a1-input" name="first_name" value="{{ old('first_name') }}" autocomplete="off">
                    </label>
                    <label class="a1-field">
                        <span class="a1-label">Prezime</span>
                        <input class="a1-input" name="last_name" value="{{ old('last_name') }}" autocomplete="off">
                    </label>
                    <label class="a1-field">
                        <span class="a1-label">Početna lozinka</span>
                        <input class="a1-input" type="password" name="password" minlength="12" required autocomplete="new-password">
                    </label>
                    <label class="a1-field">
                        <span class="a1-label">Uloga</span>
                        <select class="a1-select" name="role_id" required>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" @selected((int) old('role_id') === (int) $role->id)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="a1-field">
                        <span class="a1-label">Grupa pristupa</span>
                        <select class="a1-select" name="user_group_id">
                            <option value="">Bez grupe</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}" @selected((int) old('user_group_id') === (int) $group->id)>{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="a1-field">
                        <span class="a1-label">Status</span>
                        <select class="a1-select" name="status">
                            @foreach($statusLabels as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', 'active') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <p class="a1-help">Za korisničku ulogu izaberi aktivnu grupu pristupa. Administrator i SuperAdmin imaju puni skup dozvola po postojećem backend pravilu.</p>

                <div class="a1-g5-actions">
                    <button class="a1-btn a1-btn--primary" type="submit">Dodaj korisnika</button>
                </div>
            </form>
        </div>
    </details>

    <section class="a1-stack" aria-label="Lista korisnika">
        @forelse($users as $user)
            @php
                $roleSlug = (string) ($user->role?->slug ?? '');
                $roleName = (string) ($user->role?->name ?? 'Bez uloge');
                $group = $user->group;
                $status = (string) ($user->status ?? 'pending');
                $statusLabel = $statusLabels[$status] ?? $status;
                $lastLogin = $user->last_login_at
                    ? \Illuminate\Support\Carbon::parse($user->last_login_at)->format('d.m.Y H:i')
                    : 'Nije evidentirano';
                $fullAccessRole = in_array($roleSlug, ['admin', 'superadmin'], true);
                $canManage = $roleSlug !== 'superadmin' || auth()->user()?->hasRole('superadmin');
            @endphp

            <article class="a1-card a1-g5-user" data-access-user="{{ $user->id }}">
                <div class="a1-g5-user__head" data-user-head>
                    <span class="a1-g5-avatar" data-user-avatar aria-hidden="true">{{ $user->displayInitial() }}</span>
                    <div class="a1-g5-identity" data-user-identity>
                        <span class="a1-eyebrow">Korisnički nalog</span>
                        <strong>{{ $user->displayName() }}</strong>
                        <span class="a1-muted">{{ $user->username }} · {{ $user->email }}</span>
                    </div>

                    <div class="a1-g5-user__badges" data-user-badges>
                        <span class="a1-pill{{ $statusTone[$status] ?? '' }}">{{ $statusLabel }}</span>
                        <span class="a1-pill a1-pill--off">{{ $roleName }}</span>
                    </div>
                </div>

                <dl class="a1-dl a1-g5-meta">
                    <div class="a1-dl__row"><dt>Grupa</dt><dd>{{ $group?->name ?? 'Bez grupe' }}</dd></div>
                    <div class="a1-dl__row"><dt>Pristup</dt><dd>{{ $fullAccessRole ? 'Puni pristup' : ($group?->status === 'active' ? 'Po grupi' : 'Ograničen') }}</dd></div>
                    <div class="a1-dl__row"><dt>Poslednja prijava</dt><dd>{{ $lastLogin }}</dd></div>
                    <div class="a1-dl__row"><dt>Telefon</dt><dd>{{ $user->phone ?: 'Nije unet' }}</dd></div>
                </dl>

                <p class="a1-os-note">
                    @if($roleSlug === 'superadmin')
                        SuperAdmin zadržava puni sistemski pristup i postojeću zaštitu da poslednji SuperAdmin ne može biti degradiran.
                    @elseif($roleSlug === 'admin')
                        Administrator ima puni permission pristup po ulozi; izbor grupe ne sužava njegove backend dozvole.
                    @elseif($group && $group->status === 'active')
                        Efektivni permission-i ovog naloga dolaze iz aktivne grupe „{{ $group->name }}“.
                    @elseif($group)
                        Dodeljena grupa „{{ $group->name }}“ nije aktivna, pa ne daje grupne permission-e.
                    @else
                        Korisničkoj ulozi nije dodeljena grupa pristupa.
                    @endif
                </p>

                @if($canManage)
                <details class="a1-acc" data-user-edit>
                    <summary>
                        <span class="a1-g5-acc-title">
                            <strong>Uredi profil i pristup</strong>
                            <span class="a1-muted">Promeni identitet, ulogu, grupu ili status naloga.</span>
                        </span>
                    </summary>

                    <div class="a1-acc__body">
                        <form method="post" action="{{ route('admin.users.update', $user) }}" class="a1-form">
                            @csrf
                            @method('put')

                            <div class="a1-form" data-user-editor-sections>
                                <fieldset class="a1-fieldset">
                                    <legend class="a1-legend">Osnovni podaci</legend>
                                    <div class="a1-row">
                                        <label class="a1-field">
                                            <span class="a1-label">Korisničko ime</span>
                                            <input class="a1-input" name="username" value="{{ $user->username }}" required autocomplete="off">
                                        </label>
                                        <label class="a1-field">
                                            <span class="a1-label">E-mail</span>
                                            <input class="a1-input" type="email" name="email" value="{{ $user->email }}" required autocomplete="off">
                                        </label>
                                        <label class="a1-field">
                                            <span class="a1-label">Telefon</span>
                                            <input class="a1-input" name="phone" value="{{ $user->phone }}" autocomplete="off">
                                        </label>
                                        <label class="a1-field">
                                            <span class="a1-label">Ime</span>
                                            <input class="a1-input" name="first_name" value="{{ $user->first_name }}" autocomplete="off">
                                        </label>
                                        <label class="a1-field">
                                            <span class="a1-label">Prezime</span>
                                            <input class="a1-input" name="last_name" value="{{ $user->last_name }}" autocomplete="off">
                                        </label>
                                    </div>
                                </fieldset>
                                <fieldset class="a1-fieldset">
                                    <legend class="a1-legend">Pristup i ovlašćenja</legend>
                                    <div class="a1-row">
                                        <label class="a1-field">
                                            <span class="a1-label">Uloga</span>
                                            <select class="a1-select" name="role_id" required>
                                                @foreach($roles as $role)
                                                    <option value="{{ $role->id }}" @selected((int) $user->role_id === (int) $role->id)>{{ $role->name }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                        <label class="a1-field">
                                            <span class="a1-label">Grupa pristupa</span>
                                            <select class="a1-select" name="user_group_id">
                                                <option value="">Bez grupe</option>
                                                @foreach($groups as $availableGroup)
                                                    <option value="{{ $availableGroup->id }}" @selected((int) $user->user_group_id === (int) $availableGroup->id)>{{ $availableGroup->name }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                        <label class="a1-field">
                                            <span class="a1-label">Status</span>
                                            <select class="a1-select" name="status">
                                                @foreach($statusLabels as $value => $label)
                                                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                    </div>
                                </fieldset>
                                <fieldset class="a1-fieldset">
                                    <legend class="a1-legend">Bezbednost</legend>
                                    <div class="a1-row">
                                        <label class="a1-field">
                                            <span class="a1-label">Nova lozinka</span>
                                            <input class="a1-input" type="password" name="password" minlength="12" autocomplete="new-password" placeholder="Ostavi prazno bez promene">
                                        </label>
                                    </div>
                                    <p class="a1-help">Lozinka ostaje nepromenjena ako polje ostaviš prazno. Najmanje 12 znakova za novu lozinku.</p>
                                </fieldset>
                            </div>

                            @if($roleSlug === 'superadmin')
                                <div class="a1-alert a1-alert--warning" data-superadmin-guard-note>
                                    <div class="a1-alert__body">
                                        <p>Backend zaštita poslednjeg SuperAdmin naloga ostaje aktivna. UI ne zaobilazi tu zaštitu.</p>
                                    </div>
                                </div>
                            @endif

                            <div class="a1-g5-actions">
                                <span class="a1-g5-note">Izmene se primenjuju tek nakon čuvanja.</span>
                                <button class="a1-btn" type="reset">Poništi unos</button>
                                <button class="a1-btn a1-btn--primary" type="submit">Sačuvaj korisnika</button>
                            </div>
                        </form>
                    </div>
                </details>
                @else
                    <div class="a1-alert" data-superadmin-readonly>
                        <div class="a1-alert__body">
                            <p>Nalog SuperAdministratora može da menja samo SuperAdministrator.</p>
                        </div>
                    </div>
                @endif

                @if(auth()->user()?->hasRole('superadmin') && (int) auth()->id() !== (int) $user->id && $roleSlug !== 'superadmin' && $status !== 'blocked')
                    <div class="a1-os-danger" data-user-danger-zone>
                        <div class="a1-g5-danger-text"><strong>Uklanjanje naloga</strong><span class="a1-muted">Najpre pregled zavisnosti i postojeća bezbednosna provera.</span></div>
                        <a class="a1-btn a1-btn--danger a1-btn--sm" href="{{ route('admin.users.retirement.preview', $user) }}">Ukloni korisnika</a>
                    </div>
                @endif
            </article>
        @empty
            <div class="a1-empty">
                <p>Nema korisnika koji odgovaraju trenutnim filterima.</p>
            </div>
        @endforelse
    </section>

    {{ $users->links('partials.ol-pager') }}
</div>
@endsection
