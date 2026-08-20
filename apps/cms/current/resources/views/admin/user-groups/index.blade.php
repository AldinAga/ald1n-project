@extends('layouts.app')

@section('title', 'Grupe pristupa')

@section('content')
@include('admin.settings.partials.context-nav', ['settingsSection' => 'Pristup i ovlašćenja'])
@include('admin.access-control.partials.workspace-style')

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

<div class="access-control-workspace" data-access-control-workspace="groups">
    <header class="access-control-hero">
        <div class="access-control-hero-copy">
            <span class="eyebrow">Pristup i ovlašćenja</span>
            <h1>Grupe pristupa</h1>
            <p>Grupe su paketi permission-a i kataloškog scope-a za korisničku ulogu. Administrator i SuperAdmin zadržavaju puni pristup po postojećem backend pravilu.</p>
        </div>

        <nav class="access-control-tabs" aria-label="Pristup i ovlašćenja">
            <a class="access-control-tab" href="{{ route('admin.users.index') }}">Korisnici</a>
            <a class="access-control-tab is-active" href="{{ route('admin.user-groups.index') }}" aria-current="page">Grupe pristupa</a>
        </nav>
    </header>

    <div class="access-control-metrics" aria-label="Sažetak grupa pristupa">
        <article class="access-control-metric">
            <span>Grupe</span>
            <strong>{{ $groups->count() }}</strong>
            <small>Ukupan broj konfigurisanih paketa pristupa.</small>
        </article>
        <article class="access-control-metric">
            <span>Aktivne grupe</span>
            <strong>{{ $activeGroupCount }}</strong>
            <small>Samo aktivna grupa daje permission-e korisničkoj ulozi.</small>
        </article>
        <article class="access-control-metric">
            <span>Dodeljeni korisnici</span>
            <strong>{{ $assignedUserCount }}</strong>
            <small>Broj naloga trenutno povezanih sa grupama.</small>
        </article>
    </div>

    <div class="access-control-callout">
        <strong>Model pristupa ostaje nepromenjen.</strong>
        Permission-i se čuvaju po tehničkom <code>slug</code> ključu, a ovaj ekran prikazuje poslovne nazive i opise. Tehnički ključ je dostupan samo kroz napredni detalj.
    </div>

    <details class="panel access-control-panel access-control-disclosure">
        <summary>
            <span>
                <strong>Nova grupa pristupa</strong>
                <small>Kreiraj novi paket permission-a i kataloškog scope-a.</small>
            </span>
        </summary>

        <div class="access-control-disclosure-body">
            <form method="post" action="{{ route('admin.user-groups.store') }}" class="access-control-form" data-access-group-editor>
                @csrf

                <div class="access-control-field-grid">
                    <label>
                        <span>Naziv grupe</span>
                        <input name="name" value="{{ old('name') }}" required>
                    </label>
                    <label>
                        <span>Slug</span>
                        <input name="slug" value="{{ old('slug') }}" placeholder="Automatski ako ostane prazno">
                    </label>
                    <label>
                        <span>Status</span>
                        <select name="status">
                            <option value="active" @selected(old('status', 'active') === 'active')>Aktivna</option>
                            <option value="inactive" @selected(old('status') === 'inactive')>Neaktivna</option>
                        </select>
                    </label>
                    <label class="span-2">
                        <span>Opis</span>
                        <textarea name="description" rows="3" placeholder="Kome je grupa namenjena i koji posao pokriva">{{ old('description') }}</textarea>
                    </label>
                    <label>
                        <span>Redosled</span>
                        <input type="number" name="sort_order" min="0" value="{{ old('sort_order', 0) }}">
                    </label>
                    <label>
                        <span>Pristup kategorijama</span>
                        <select name="category_access_mode">
                            <option value="all" @selected(old('category_access_mode', 'all') === 'all')>Sve kategorije</option>
                            <option value="selected" @selected(old('category_access_mode') === 'selected')>Samo izabrane</option>
                            <option value="none" @selected(old('category_access_mode') === 'none')>Bez kategorija</option>
                        </select>
                    </label>
                    <label class="span-2 access-control-checkbox-row">
                        <input type="hidden" name="include_uncategorized" value="0">
                        <input type="checkbox" name="include_uncategorized" value="1" @checked((string) old('include_uncategorized', '1') === '1')>
                        <span>
                            <strong>Uključi nekategorisane artikle</strong>
                            <small>Važi prema postojećoj logici kataloškog scope-a.</small>
                        </span>
                    </label>
                </div>

                <div class="access-control-editor-columns">
                    <section class="access-control-permissions">
                        <div class="access-control-permission-tools">
                            <label>
                                <span>Pronađi dozvolu</span>
                                <input type="search" placeholder="Naziv, opis ili tehnički ključ" data-permission-filter>
                            </label>
                        </div>

                        <div class="access-control-permission-domains">
                            @foreach($permissionDomainLabels as $domainKey => $domainLabel)
                                @php $domainPermissions = $permissionGroups->get($domainKey, collect()); @endphp
                                @if($domainPermissions->isNotEmpty())
                                    <section class="access-control-permission-domain" data-permission-domain>
                                        <header class="access-control-permission-domain-head">
                                            <strong>{{ $domainLabel }} · {{ $domainPermissions->count() }}</strong>
                                            <div class="access-control-permission-domain-actions">
                                                <button class="access-control-mini-button" type="button" data-permission-set="all">Izaberi sve</button>
                                                <button class="access-control-mini-button" type="button" data-permission-set="none">Poništi</button>
                                            </div>
                                        </header>

                                        <div class="access-control-permission-list">
                                            @foreach($domainPermissions as $permission)
                                                <label class="access-control-permission-item" data-permission-item>
                                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked(in_array((int) $permission->id, array_map('intval', old('permissions', [])), true))>
                                                    <span class="access-control-permission-copy">
                                                        <strong>{{ $permission->name }}</strong>
                                                        @if($permission->description)
                                                            <small>{{ $permission->description }}</small>
                                                        @endif
                                                        <details class="access-control-technical-key">
                                                            <summary>Tehnički ključ</summary>
                                                            <code>{{ $permission->slug }}</code>
                                                        </details>
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </section>
                                @endif
                            @endforeach
                        </div>
                    </section>

                    <aside class="access-control-category-panel">
                        <h4>Kataloški scope</h4>
                        <p>Izabrane kategorije se koriste samo kada postojeći režim pristupa kategorijama to zahteva.</p>

                        <div class="access-control-category-list">
                            @forelse($categories as $category)
                                <label class="access-control-category-item">
                                    <input type="checkbox" name="categories[]" value="{{ $category->id }}" @checked(in_array((int) $category->id, array_map('intval', old('categories', [])), true))>
                                    <span>{{ $category->name }}</span>
                                </label>
                            @empty
                                <div class="access-control-help">Nema dostupnih kategorija.</div>
                            @endforelse
                        </div>
                    </aside>
                </div>

                <div class="access-control-actions access-control-actions-spaced">
                    <button class="button button-primary" type="submit">Kreiraj grupu</button>
                </div>
            </form>
        </div>
    </details>

    <section class="access-control-group-grid" aria-label="Postojeće grupe pristupa">
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

            <article class="panel access-control-group-card">
                <div class="access-control-group-summary">
                    <div>
                        <h3>{{ $group->name }}</h3>
                        <p>{{ $group->description ?: 'Bez dodatnog opisa.' }}</p>
                    </div>

                    <div class="access-control-group-counts">
                        <span class="access-control-badge is-{{ $groupStatus }}">{{ $groupStatusLabel }}</span>
                        <span class="access-control-badge">{{ $group->users_count }} korisnika</span>
                        <span class="access-control-badge">{{ $group->permissions->count() }} dozvola</span>
                        <span class="access-control-badge">{{ $group->categories->count() }} kategorija</span>
                    </div>
                </div>

                <details class="access-control-disclosure access-control-group-editor">
                    <summary>
                        <span>
                            <strong>Uredi grupu</strong>
                            <small>{{ $categoryModeLabels[$group->category_access_mode] ?? $group->category_access_mode }} · redosled {{ $group->sort_order }}</small>
                        </span>
                    </summary>

                    <div class="access-control-disclosure-body">
                        <form method="post" action="{{ route('admin.user-groups.update', $group) }}" class="access-control-form" data-access-group-editor>
                            @csrf
                            @method('put')

                            <div class="access-control-field-grid">
                                <label>
                                    <span>Naziv grupe</span>
                                    <input name="name" value="{{ $group->name }}" required>
                                </label>
                                <label>
                                    <span>Slug</span>
                                    <input name="slug" value="{{ $group->slug }}">
                                </label>
                                <label>
                                    <span>Status</span>
                                    <select name="status">
                                        <option value="active" @selected($groupStatus === 'active')>Aktivna</option>
                                        <option value="inactive" @selected($groupStatus === 'inactive')>Neaktivna</option>
                                    </select>
                                </label>
                                <label class="span-2">
                                    <span>Opis</span>
                                    <textarea name="description" rows="3">{{ $group->description }}</textarea>
                                </label>
                                <label>
                                    <span>Redosled</span>
                                    <input type="number" name="sort_order" min="0" value="{{ $group->sort_order }}">
                                </label>
                                <label>
                                    <span>Pristup kategorijama</span>
                                    <select name="category_access_mode">
                                        <option value="all" @selected($group->category_access_mode === 'all')>Sve kategorije</option>
                                        <option value="selected" @selected($group->category_access_mode === 'selected')>Samo izabrane</option>
                                        <option value="none" @selected($group->category_access_mode === 'none')>Bez kategorija</option>
                                    </select>
                                </label>
                                <label class="span-2 access-control-checkbox-row">
                                    <input type="hidden" name="include_uncategorized" value="0">
                                    <input type="checkbox" name="include_uncategorized" value="1" @checked((bool) $group->include_uncategorized)>
                                    <span>
                                        <strong>Uključi nekategorisane artikle</strong>
                                        <small>Ne menja permission-e; utiče samo na postojeći kataloški scope.</small>
                                    </span>
                                </label>
                            </div>

                            <div class="access-control-editor-columns">
                                <section class="access-control-permissions">
                                    <div class="access-control-permission-tools">
                                        <label>
                                            <span>Pronađi dozvolu</span>
                                            <input type="search" placeholder="Naziv, opis ili tehnički ključ" data-permission-filter>
                                        </label>
                                    </div>

                                    <div class="access-control-permission-domains">
                                        @foreach($permissionDomainLabels as $domainKey => $domainLabel)
                                            @php $domainPermissions = $permissionGroups->get($domainKey, collect()); @endphp
                                            @if($domainPermissions->isNotEmpty())
                                                <section class="access-control-permission-domain" data-permission-domain>
                                                    <header class="access-control-permission-domain-head">
                                                        <strong>{{ $domainLabel }} · {{ $domainPermissions->count() }}</strong>
                                                        <div class="access-control-permission-domain-actions">
                                                            <button class="access-control-mini-button" type="button" data-permission-set="all">Izaberi sve</button>
                                                            <button class="access-control-mini-button" type="button" data-permission-set="none">Poništi</button>
                                                        </div>
                                                    </header>

                                                    <div class="access-control-permission-list">
                                                        @foreach($domainPermissions as $permission)
                                                            <label class="access-control-permission-item" data-permission-item>
                                                                <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked($group->permissions->contains($permission->id))>
                                                                <span class="access-control-permission-copy">
                                                                    <strong>{{ $permission->name }}</strong>
                                                                    @if($permission->description)
                                                                        <small>{{ $permission->description }}</small>
                                                                    @endif
                                                                    <details class="access-control-technical-key">
                                                                        <summary>Tehnički ključ</summary>
                                                                        <code>{{ $permission->slug }}</code>
                                                                    </details>
                                                                </span>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                </section>
                                            @endif
                                        @endforeach
                                    </div>
                                </section>

                                <aside class="access-control-category-panel">
                                    <h4>Kataloški scope</h4>
                                    <p>Trenutni režim: {{ $categoryModeLabels[$group->category_access_mode] ?? $group->category_access_mode }}.</p>

                                    <div class="access-control-category-list">
                                        @forelse($categories as $category)
                                            <label class="access-control-category-item">
                                                <input type="checkbox" name="categories[]" value="{{ $category->id }}" @checked($group->categories->contains($category->id))>
                                                <span>{{ $category->name }}</span>
                                            </label>
                                        @empty
                                            <div class="access-control-help">Nema dostupnih kategorija.</div>
                                        @endforelse
                                    </div>
                                </aside>
                            </div>

                            <div class="access-control-actions access-control-actions-spaced">
                                <button class="button button-primary" type="submit">Sačuvaj grupu</button>
                            </div>
                        </form>

                        <div class="access-control-danger-zone">
                            <p>
                                @if($group->users_count > 0)
                                    Grupa je dodeljena korisnicima. Pre brisanja prvo ih premesti u drugu grupu ili ukloni dodelu.
                                @else
                                    Brisanje grupe je nepovratna konfiguraciona akcija i ostaje auditovana postojećim backendom.
                                @endif
                            </p>

                            <form id="delete-group-{{ $group->id }}" method="post" action="{{ route('admin.user-groups.destroy', $group) }}" data-confirm-message="Obrisati grupu pristupa „{{ $group->name }}“?">
                                @csrf
                                @method('delete')
                                <button class="button button-danger button-small" type="submit" @disabled($group->users_count > 0)>Obriši grupu</button>
                            </form>
                        </div>
                    </div>
                </details>
            </article>
        @empty
            <div class="panel access-control-empty">Još nema grupa pristupa.</div>
        @endforelse
    </section>
</div>

@push('scripts')
<script>
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
