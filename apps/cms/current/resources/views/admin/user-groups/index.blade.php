@extends('layouts.app')

@section('title', 'Grupe pristupa')

@section('content')
@include('admin.partials.g5-styles')
@include('admin.settings.partials.context-nav', ['settingsSection' => 'Pristup i ovlašćenja'])

@php
    $permissionDomainLabels = [
        'catalog' => 'Katalog i artikli',
        'orders' => 'Porudžbine, dokumenti i uplate',
        'inventory' => 'Lager i inventar',
        'after_sales' => 'Postprodaja i garancije',
        'service' => 'Servis i teren',
        'finance' => 'Izveštaji i finansije',
        'notifications' => 'Obaveštenja',
        'system' => 'Sistem i bezbednost',
        'other' => 'Ostalo',
    ];

    $permissionDomain = static function ($permission): string {
        $slug = (string) ($permission->slug ?? '');

        if (str_starts_with($slug, 'catalog.')) {
            return 'catalog';
        }

        if (
            str_starts_with($slug, 'orders.')
            || str_starts_with($slug, 'invoices.')
            || str_starts_with($slug, 'payments.')
        ) {
            return 'orders';
        }

        if (str_starts_with($slug, 'inventory.') || str_starts_with($slug, 'stock.')) {
            return 'inventory';
        }

        if (str_starts_with($slug, 'after_sales.') || str_starts_with($slug, 'warranties.')) {
            return 'after_sales';
        }

        if (str_starts_with($slug, 'service_parts.') || str_starts_with($slug, 'field_operations.')) {
            return 'service';
        }

        if (
            str_starts_with($slug, 'reports.')
            || str_starts_with($slug, 'commissions.')
            || str_starts_with($slug, 'receivables.')
        ) {
            return 'finance';
        }

        if (str_starts_with($slug, 'notifications.')) {
            return 'notifications';
        }

        if (
            str_starts_with($slug, 'system.')
            || str_starts_with($slug, 'audit.')
            || str_starts_with($slug, 'security.')
            || str_starts_with($slug, 'automation.')
            || str_starts_with($slug, 'backups.')
        ) {
            return 'system';
        }

        return 'other';
    };

    $permissionGroups = $permissions->groupBy($permissionDomain);
    $activeGroupCount = $groups->where('status', 'active')->count();
    $assignedUserCount = $groups->sum('users_count');
@endphp

<div class="a1-ops a1-g5" data-access-control-workspace="groups">
    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Pristup i ovlašćenja</span>
            <h1 class="a1-title">Grupe pristupa</h1>
            <p class="a1-lead">Grupe su paketi permission-a i kataloškog scope-a za korisničku ulogu. Administrator i SuperAdmin zadržavaju puni pristup po postojećem backend pravilu.</p>
        </div>
    </header>

    <nav aria-label="Pristup i ovlašćenja">
        <ul class="a1-tabs">
            <li><a class="a1-tabs__link" href="{{ route('admin.users.index') }}">Korisnici</a></li>
            <li><a class="a1-tabs__link" href="{{ route('admin.user-groups.index') }}" aria-current="page">Grupe pristupa</a></li>
        </ul>
    </nav>

    <div class="a1-grid a1-grid--kpi" role="group" aria-label="Sažetak grupa pristupa">
        <div class="a1-kpi">
            <p class="a1-kpi__label">Grupe</p>
            <p class="a1-kpi__value">{{ $groups->count() }}</p>
            <p class="a1-kpi__note">Ukupan broj konfigurisanih paketa pristupa.</p>
        </div>
        <div class="a1-kpi">
            <p class="a1-kpi__label">Aktivne grupe</p>
            <p class="a1-kpi__value">{{ $activeGroupCount }}</p>
            <p class="a1-kpi__note">Samo aktivna grupa daje permission-e korisničkoj ulozi.</p>
        </div>
        <div class="a1-kpi">
            <p class="a1-kpi__label">Dodeljeni korisnici</p>
            <p class="a1-kpi__value">{{ $assignedUserCount }}</p>
            <p class="a1-kpi__note">Broj naloga trenutno povezanih sa grupama.</p>
        </div>
    </div>

    <div class="a1-alert">
        <div class="a1-alert__body">
            <strong class="a1-alert__title">Model pristupa ostaje nepromenjen.</strong>
            <p>Permission-i se čuvaju po tehničkom <code class="a1-g5-code">slug</code> ključu, a ovaj ekran prikazuje poslovne nazive i opise. Tehnički ključ je dostupan samo kroz napredni detalj.</p>
        </div>
    </div>

    <details class="a1-acc">
        <summary>
            <span class="a1-g5-acc-title">
                <strong>Nova grupa pristupa</strong>
                <span class="a1-muted">Kreiraj novi paket permission-a i kataloškog scope-a.</span>
            </span>
        </summary>

        <div class="a1-acc__body">
            <form method="post" action="{{ route('admin.user-groups.store') }}" class="a1-form" data-access-group-editor>
                @csrf

                <div class="a1-row">
                    <label class="a1-field">
                        <span class="a1-label">Naziv grupe</span>
                        <input class="a1-input" name="name" value="{{ old('name') }}" required>
                    </label>
                    <label class="a1-field">
                        <span class="a1-label">Slug</span>
                        <input class="a1-input" name="slug" value="{{ old('slug') }}" placeholder="Automatski ako ostane prazno">
                    </label>
                    <label class="a1-field">
                        <span class="a1-label">Status</span>
                        <select class="a1-select" name="status">
                            <option value="active" @selected(old('status', 'active') === 'active')>Aktivna</option>
                            <option value="inactive" @selected(old('status') === 'inactive')>Neaktivna</option>
                        </select>
                    </label>
                    <label class="a1-field">
                        <span class="a1-label">Redosled</span>
                        <input class="a1-input" type="number" name="sort_order" min="0" value="{{ old('sort_order', 0) }}">
                    </label>
                    <label class="a1-field">
                        <span class="a1-label">Pristup kategorijama</span>
                        <select class="a1-select" name="category_access_mode">
                            <option value="all" @selected(old('category_access_mode', 'all') === 'all')>Sve kategorije</option>
                            <option value="selected" @selected(old('category_access_mode') === 'selected')>Samo izabrane</option>
                            <option value="none" @selected(old('category_access_mode') === 'none')>Bez kategorija</option>
                        </select>
                    </label>
                </div>
                <label class="a1-field">
                    <span class="a1-label">Opis</span>
                    <textarea class="a1-textarea" name="description" rows="3" placeholder="Kome je grupa namenjena i koji posao pokriva">{{ old('description') }}</textarea>
                </label>
                <label class="a1-check">
                    <input type="hidden" name="include_uncategorized" value="0">
                    <input class="a1-check__input" type="checkbox" name="include_uncategorized" value="1" @checked((string) old('include_uncategorized', '1') === '1')>
                    <span class="a1-check__box" aria-hidden="true"></span>
                    <span class="a1-g5-check-copy">
                        <strong>Uključi nekategorisane artikle</strong>
                        <small>Važi prema postojećoj logici kataloškog scope-a.</small>
                    </span>
                </label>

                <div class="a1-g5-editor">
                    <section class="a1-g5-perms">
                        <label class="a1-field">
                            <span class="a1-label">Pronađi dozvolu</span>
                            <input class="a1-input" type="search" placeholder="Naziv, opis ili tehnički ključ" data-permission-filter>
                        </label>

                        <div class="a1-g5-domains">
                            @foreach($permissionDomainLabels as $domainKey => $domainLabel)
                                @php $domainPermissions = $permissionGroups->get($domainKey, collect()); @endphp
                                @if($domainPermissions->isNotEmpty())
                                    <section class="a1-g5-domain" data-permission-domain>
                                        <header class="a1-g5-domain__head">
                                            <strong>{{ $domainLabel }} · {{ $domainPermissions->count() }}</strong>
                                            <div class="a1-cluster">
                                                <button class="a1-btn a1-btn--sm" type="button" data-permission-set="all">Izaberi sve</button>
                                                <button class="a1-btn a1-btn--sm" type="button" data-permission-set="none">Poništi</button>
                                            </div>
                                        </header>

                                        @foreach($domainPermissions as $permission)
                                            <div class="a1-g5-perm" data-permission-item>
                                                <label class="a1-check">
                                                    <input class="a1-check__input" type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked(in_array((int) $permission->id, array_map('intval', old('permissions', [])), true))>
                                                    <span class="a1-check__box" aria-hidden="true"></span>
                                                    <span class="a1-g5-check-copy">
                                                        <strong>{{ $permission->name }}</strong>
                                                        @if($permission->description)
                                                            <small>{{ $permission->description }}</small>
                                                        @endif
                                                    </span>
                                                </label>
                                                <details class="a1-g5-key">
                                                    <summary>Tehnički ključ</summary>
                                                    <code>{{ $permission->slug }}</code>
                                                </details>
                                            </div>
                                        @endforeach
                                    </section>
                                @endif
                            @endforeach
                        </div>
                    </section>

                    <aside class="a1-g5-side">
                        <h3 class="a1-h3">Kataloški scope</h3>
                        <p>Izabrane kategorije se koriste samo kada postojeći režim pristupa kategorijama to zahteva.</p>

                        <div class="a1-g5-cats">
                            @forelse($categories as $category)
                                <div class="a1-g5-cat">
                                    <label class="a1-check">
                                        <input class="a1-check__input" type="checkbox" name="categories[]" value="{{ $category->id }}" @checked(in_array((int) $category->id, array_map('intval', old('categories', [])), true))>
                                        <span class="a1-check__box" aria-hidden="true"></span>
                                        <span>{{ $category->name }}</span>
                                    </label>
                                </div>
                            @empty
                                <p class="a1-help">Nema dostupnih kategorija.</p>
                            @endforelse
                        </div>
                    </aside>
                </div>

                <div class="a1-g5-actions">
                    <button class="a1-btn a1-btn--primary" type="submit">Kreiraj grupu</button>
                </div>
            </form>
        </div>
    </details>

    <section class="a1-stack" aria-label="Postojeće grupe pristupa">
        @forelse($groups as $group)
            @php
                $groupStatus = (string) ($group->status ?? 'inactive');
                $groupStatusLabel = $groupStatus === 'active' ? 'Aktivna' : 'Neaktivna';
                $categoryModeLabels = [
                    'all' => 'Sve kategorije',
                    'selected' => 'Samo izabrane',
                    'none' => 'Bez kategorija',
                ];
            @endphp

            <article class="a1-card a1-g5-group">
                <div class="a1-g5-group__head">
                    <div>
                        <h2 class="a1-h3">{{ $group->name }}</h2>
                        <p>{{ $group->description ?: 'Bez dodatnog opisa.' }}</p>
                    </div>

                    <div class="a1-os-pills">
                        <span class="a1-pill{{ $groupStatus === 'active' ? ' a1-pill--ok' : ' a1-pill--off' }}">{{ $groupStatusLabel }}</span>
                        <span class="a1-pill a1-pill--off">{{ $group->users_count }} korisnika</span>
                        <span class="a1-pill a1-pill--off">{{ $group->permissions->count() }} dozvola</span>
                        <span class="a1-pill a1-pill--off">{{ $group->categories->count() }} kategorija</span>
                    </div>
                </div>

                <details class="a1-acc">
                    <summary>
                        <span class="a1-g5-acc-title">
                            <strong>Uredi grupu</strong>
                            <span class="a1-muted">{{ $categoryModeLabels[$group->category_access_mode] ?? $group->category_access_mode }} · redosled {{ $group->sort_order }}</span>
                        </span>
                    </summary>

                    <div class="a1-acc__body">
                        <form method="post" action="{{ route('admin.user-groups.update', $group) }}" class="a1-form" data-access-group-editor>
                            @csrf
                            @method('put')

                            <div class="a1-row">
                                <label class="a1-field">
                                    <span class="a1-label">Naziv grupe</span>
                                    <input class="a1-input" name="name" value="{{ $group->name }}" required>
                                </label>
                                <label class="a1-field">
                                    <span class="a1-label">Slug</span>
                                    <input class="a1-input" name="slug" value="{{ $group->slug }}">
                                </label>
                                <label class="a1-field">
                                    <span class="a1-label">Status</span>
                                    <select class="a1-select" name="status">
                                        <option value="active" @selected($groupStatus === 'active')>Aktivna</option>
                                        <option value="inactive" @selected($groupStatus === 'inactive')>Neaktivna</option>
                                    </select>
                                </label>
                                <label class="a1-field">
                                    <span class="a1-label">Redosled</span>
                                    <input class="a1-input" type="number" name="sort_order" min="0" value="{{ $group->sort_order }}">
                                </label>
                                <label class="a1-field">
                                    <span class="a1-label">Pristup kategorijama</span>
                                    <select class="a1-select" name="category_access_mode">
                                        <option value="all" @selected($group->category_access_mode === 'all')>Sve kategorije</option>
                                        <option value="selected" @selected($group->category_access_mode === 'selected')>Samo izabrane</option>
                                        <option value="none" @selected($group->category_access_mode === 'none')>Bez kategorija</option>
                                    </select>
                                </label>
                            </div>
                            <label class="a1-field">
                                <span class="a1-label">Opis</span>
                                <textarea class="a1-textarea" name="description" rows="3">{{ $group->description }}</textarea>
                            </label>
                            <label class="a1-check">
                                <input type="hidden" name="include_uncategorized" value="0">
                                <input class="a1-check__input" type="checkbox" name="include_uncategorized" value="1" @checked((bool) $group->include_uncategorized)>
                                <span class="a1-check__box" aria-hidden="true"></span>
                                <span class="a1-g5-check-copy">
                                    <strong>Uključi nekategorisane artikle</strong>
                                    <small>Ne menja permission-e; utiče samo na postojeći kataloški scope.</small>
                                </span>
                            </label>

                            <div class="a1-g5-editor">
                                <section class="a1-g5-perms">
                                    <label class="a1-field">
                                        <span class="a1-label">Pronađi dozvolu</span>
                                        <input class="a1-input" type="search" placeholder="Naziv, opis ili tehnički ključ" data-permission-filter>
                                    </label>

                                    <div class="a1-g5-domains">
                                        @foreach($permissionDomainLabels as $domainKey => $domainLabel)
                                            @php $domainPermissions = $permissionGroups->get($domainKey, collect()); @endphp
                                            @if($domainPermissions->isNotEmpty())
                                                <section class="a1-g5-domain" data-permission-domain>
                                                    <header class="a1-g5-domain__head">
                                                        <strong>{{ $domainLabel }} · {{ $domainPermissions->count() }}</strong>
                                                        <div class="a1-cluster">
                                                            <button class="a1-btn a1-btn--sm" type="button" data-permission-set="all">Izaberi sve</button>
                                                            <button class="a1-btn a1-btn--sm" type="button" data-permission-set="none">Poništi</button>
                                                        </div>
                                                    </header>

                                                    @foreach($domainPermissions as $permission)
                                                        <div class="a1-g5-perm" data-permission-item>
                                                            <label class="a1-check">
                                                                <input class="a1-check__input" type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked($group->permissions->contains($permission->id))>
                                                                <span class="a1-check__box" aria-hidden="true"></span>
                                                                <span class="a1-g5-check-copy">
                                                                    <strong>{{ $permission->name }}</strong>
                                                                    @if($permission->description)
                                                                        <small>{{ $permission->description }}</small>
                                                                    @endif
                                                                </span>
                                                            </label>
                                                            <details class="a1-g5-key">
                                                                <summary>Tehnički ključ</summary>
                                                                <code>{{ $permission->slug }}</code>
                                                            </details>
                                                        </div>
                                                    @endforeach
                                                </section>
                                            @endif
                                        @endforeach
                                    </div>
                                </section>

                                <aside class="a1-g5-side">
                                    <h3 class="a1-h3">Kataloški scope</h3>
                                    <p>Trenutni režim: {{ $categoryModeLabels[$group->category_access_mode] ?? $group->category_access_mode }}.</p>

                                    <div class="a1-g5-cats">
                                        @forelse($categories as $category)
                                            <div class="a1-g5-cat">
                                                <label class="a1-check">
                                                    <input class="a1-check__input" type="checkbox" name="categories[]" value="{{ $category->id }}" @checked($group->categories->contains($category->id))>
                                                    <span class="a1-check__box" aria-hidden="true"></span>
                                                    <span>{{ $category->name }}</span>
                                                </label>
                                            </div>
                                        @empty
                                            <p class="a1-help">Nema dostupnih kategorija.</p>
                                        @endforelse
                                    </div>
                                </aside>
                            </div>

                            <div class="a1-g5-actions">
                                <button class="a1-btn a1-btn--primary" type="submit">Sačuvaj grupu</button>
                            </div>
                        </form>

                        <div class="a1-os-danger">
                            <p class="a1-os-muted">
                                @if($group->users_count > 0)
                                    Grupa je dodeljena korisnicima. Pre brisanja prvo ih premesti u drugu grupu ili ukloni dodelu.
                                @else
                                    Brisanje grupe je nepovratna konfiguraciona akcija i ostaje auditovana postojećim backendom.
                                @endif
                            </p>

                            <form id="delete-group-{{ $group->id }}" method="post" action="{{ route('admin.user-groups.destroy', $group) }}" data-confirm-message="Obrisati grupu pristupa „{{ $group->name }}“?">
                                @csrf
                                @method('delete')
                                <button class="a1-btn a1-btn--danger a1-btn--sm" type="submit" @disabled($group->users_count > 0)>Obriši grupu</button>
                            </form>
                        </div>
                    </div>
                </details>
            </article>
        @empty
            <div class="a1-empty">
                <p>Još nema grupa pristupa.</p>
            </div>
        @endforelse
    </section>
</div>

@push('scripts')
<script nonce="{{ request()->attributes->get('_csp_nonce') }}">
(() => {
    const normalize = (value) => String(value || '')
        .toLocaleLowerCase('sr-Latn')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');

    document.querySelectorAll('[data-access-group-editor]').forEach((editor) => {
        const filter = editor.querySelector('[data-permission-filter]');
        const items = Array.from(editor.querySelectorAll('[data-permission-item]'));
        const domains = Array.from(editor.querySelectorAll('[data-permission-domain]'));

        const applyFilter = () => {
            const query = normalize(filter ? filter.value.trim() : '');

            items.forEach((item) => {
                const show = query === '' || normalize(item.textContent).includes(query);
                item.hidden = !show;
            });

            domains.forEach((domain) => {
                const hasVisible = Array.from(domain.querySelectorAll('[data-permission-item]'))
                    .some((item) => !item.hidden);
                domain.hidden = !hasVisible;
            });
        };

        if (filter) {
            filter.addEventListener('input', applyFilter);
        }

        editor.querySelectorAll('[data-permission-set]').forEach((button) => {
            button.addEventListener('click', () => {
                const domain = button.closest('[data-permission-domain]');
                if (!domain) return;

                const checked = button.dataset.permissionSet === 'all';

                domain.querySelectorAll('input[type="checkbox"][name="permissions[]"]').forEach((checkbox) => {
                    if (!checkbox.closest('[data-permission-item]')?.hidden) {
                        checkbox.checked = checked;
                    }
                });
            });
        });
    });

    document.querySelectorAll('form[data-confirm-message]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const message = form.dataset.confirmMessage || 'Potvrdi akciju.';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });
})();
</script>
@endpush
@endsection
