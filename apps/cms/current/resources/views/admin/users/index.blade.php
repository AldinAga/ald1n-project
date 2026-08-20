@extends('layouts.app')

@section('title', 'Korisnici i pristup')

@section('content')
@include('admin.settings.partials.context-nav', ['settingsSection' => 'Pristup i ovlašćenja'])
@include('admin.access-control.partials.workspace-style')

@php
    $statusLabels = [
        'active' => 'Aktivan',
        'pending' => 'Na čekanju',
        'blocked' => 'Blokiran',
    ];
@endphp

<div class="access-control-workspace" data-access-control-workspace="users">
    <header class="access-control-hero">
        <div class="access-control-hero-copy">
            <span class="eyebrow">Pristup i ovlašćenja</span>
            <h1>Korisnici</h1>
            <p>Upravljaj nalozima, ulogama i grupama pristupa bez menjanja postojećeg bezbednosnog modela. Administrator i SuperAdmin imaju puni pristup po ulozi, dok se pristup korisnika određuje aktivnom grupom.</p>
        </div>

        <nav class="access-control-tabs" aria-label="Pristup i ovlašćenja">
            <a class="access-control-tab is-active" href="{{ route('admin.users.index') }}" aria-current="page">Korisnici</a>
            <a class="access-control-tab" href="{{ route('admin.user-groups.index') }}">Grupe pristupa</a>
        </nav>
    </header>

    <div class="access-control-metrics" aria-label="Sažetak korisnika">
        <article class="access-control-metric">
            <span>Rezultati</span>
            <strong>{{ $users->total() }}</strong>
            <small>Korisnika odgovara trenutnim filterima.</small>
        </article>
        <article class="access-control-metric">
            <span>Sistemske uloge</span>
            <strong>{{ $roles->count() }}</strong>
            <small>Fiksne uloge koje određuju osnovni nivo pristupa.</small>
        </article>
        <article class="access-control-metric">
            <span>Grupe pristupa</span>
            <strong>{{ $groups->count() }}</strong>
            <small>Paketi dozvola dostupni korisničkoj ulozi.</small>
        </article>
    </div>

    <section class="panel access-control-panel">
        <div class="access-control-section-head">
            <div>
                <h2>Pronađi korisnika</h2>
                <p>Pretraži po identitetu i suzi listu po statusu naloga.</p>
            </div>
        </div>

        <form class="access-control-filter" method="get" action="{{ route('admin.users.index') }}">
            <label>
                <span>Pretraga</span>
                <input name="q" value="{{ request('q') }}" placeholder="Korisničko ime, e-mail ili ime">
            </label>

            <label>
                <span>Status</span>
                <select name="status">
                    <option value="">Svi statusi</option>
                    @foreach($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <div class="access-control-actions">
                <button class="button button-primary" type="submit">Primeni filtere</button>
                <a class="button button-ghost" href="{{ route('admin.users.index') }}">Resetuj</a>
            </div>
        </form>
    </section>

    <details class="panel access-control-panel access-control-disclosure">
        <summary>
            <span>
                <strong>Novi korisnik</strong>
                <small>Otvori samo kada dodaješ novi nalog.</small>
            </span>
        </summary>

        <div class="access-control-disclosure-body">
            <form method="post" action="{{ route('admin.users.store') }}" class="access-control-form">
                @csrf

                <div class="access-control-field-grid">
                    <label>
                        <span>Korisničko ime</span>
                        <input name="username" value="{{ old('username') }}" required autocomplete="off">
                    </label>
                    <label>
                        <span>E-mail</span>
                        <input type="email" name="email" value="{{ old('email') }}" required autocomplete="off">
                    </label>
                    <label>
                        <span>Telefon</span>
                        <input name="phone" value="{{ old('phone') }}" autocomplete="off">
                    </label>
                    <label>
                        <span>Ime</span>
                        <input name="first_name" value="{{ old('first_name') }}" autocomplete="off">
                    </label>
                    <label>
                        <span>Prezime</span>
                        <input name="last_name" value="{{ old('last_name') }}" autocomplete="off">
                    </label>
                    <label>
                        <span>Početna lozinka</span>
                        <input type="password" name="password" minlength="12" required autocomplete="new-password">
                    </label>
                    <label>
                        <span>Uloga</span>
                        <select name="role_id" required>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" @selected((int) old('role_id') === (int) $role->id)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span>Grupa pristupa</span>
                        <select name="user_group_id">
                            <option value="">Bez grupe</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}" @selected((int) old('user_group_id') === (int) $group->id)>{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span>Status</span>
                        <select name="status">
                            @foreach($statusLabels as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', 'active') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <p class="access-control-help">Za korisničku ulogu izaberi aktivnu grupu pristupa. Administrator i SuperAdmin imaju puni skup dozvola po postojećem backend pravilu.</p>

                <div class="access-control-actions access-control-actions-spaced">
                    <button class="button button-primary" type="submit">Dodaj korisnika</button>
                </div>
            </form>
        </div>
    </details>

    <section class="access-control-list" aria-label="Lista korisnika">
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
            @endphp

            <article class="panel access-control-user-card" data-access-user="{{ $user->id }}">
                <div class="access-control-user-head">
                    <div class="access-control-user-identity">
                        <strong>{{ $user->displayName() }}</strong>
                        <small>{{ $user->username }} · {{ $user->email }}</small>
                    </div>

                    <div class="access-control-user-badges">
                        <span class="access-control-badge is-{{ $status }}">{{ $statusLabel }}</span>
                        <span class="access-control-badge">{{ $roleName }}</span>
                    </div>
                </div>

                <div class="access-control-user-meta">
                    <div>
                        <span>Grupa</span>
                        <strong>{{ $group?->name ?? 'Bez grupe' }}</strong>
                    </div>
                    <div>
                        <span>Pristup</span>
                        <strong>{{ $fullAccessRole ? 'Puni pristup' : ($group?->status === 'active' ? 'Po grupi' : 'Ograničen') }}</strong>
                    </div>
                    <div>
                        <span>Poslednja prijava</span>
                        <strong>{{ $lastLogin }}</strong>
                    </div>
                    <div>
                        <span>Telefon</span>
                        <strong>{{ $user->phone ?: 'Nije unet' }}</strong>
                    </div>
                </div>

                <div class="access-control-effective">
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
                </div>

                <details class="access-control-disclosure access-control-user-edit">
                    <summary>
                        <span>
                            <strong>Uredi profil i pristup</strong>
                            <small>Promeni identitet, ulogu, grupu ili status naloga.</small>
                        </span>
                    </summary>

                    <div class="access-control-disclosure-body">
                        <form method="post" action="{{ route('admin.users.update', $user) }}" class="access-control-form">
                            @csrf
                            @method('put')

                            <div class="access-control-field-grid">
                                <label>
                                    <span>Korisničko ime</span>
                                    <input name="username" value="{{ $user->username }}" required autocomplete="off">
                                </label>
                                <label>
                                    <span>E-mail</span>
                                    <input type="email" name="email" value="{{ $user->email }}" required autocomplete="off">
                                </label>
                                <label>
                                    <span>Telefon</span>
                                    <input name="phone" value="{{ $user->phone }}" autocomplete="off">
                                </label>
                                <label>
                                    <span>Ime</span>
                                    <input name="first_name" value="{{ $user->first_name }}" autocomplete="off">
                                </label>
                                <label>
                                    <span>Prezime</span>
                                    <input name="last_name" value="{{ $user->last_name }}" autocomplete="off">
                                </label>
                                <label>
                                    <span>Nova lozinka</span>
                                    <input type="password" name="password" minlength="12" autocomplete="new-password" placeholder="Ostavi prazno bez promene">
                                </label>
                                <label>
                                    <span>Uloga</span>
                                    <select name="role_id" required>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->id }}" @selected((int) $user->role_id === (int) $role->id)>{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label>
                                    <span>Grupa pristupa</span>
                                    <select name="user_group_id">
                                        <option value="">Bez grupe</option>
                                        @foreach($groups as $availableGroup)
                                            <option value="{{ $availableGroup->id }}" @selected((int) $user->user_group_id === (int) $availableGroup->id)>{{ $availableGroup->name }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label>
                                    <span>Status</span>
                                    <select name="status">
                                        @foreach($statusLabels as $value => $label)
                                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            </div>

                            @if($roleSlug === 'superadmin')
                                <div class="access-control-callout access-control-callout-spaced" data-superadmin-guard-note>
                                    Backend zaštita poslednjeg SuperAdmin naloga ostaje aktivna. UI ne zaobilazi tu zaštitu.
                                </div>
                            @endif

                            <div class="access-control-actions access-control-actions-spaced">
                                <button class="button button-primary" type="submit">Sačuvaj korisnika</button>
                            </div>
                        </form>
                    </div>
                </details>
            </article>
        @empty
            <div class="panel access-control-empty">Nema korisnika koji odgovaraju trenutnim filterima.</div>
        @endforelse
    </section>

    <div class="access-control-pager">
        {{ $users->links() }}
    </div>
</div>
@endsection
