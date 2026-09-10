@extends('layouts.app')
@section('title', $product->exists ? 'Izmeni artikal' : 'Dodaj artikal')
@section('content')
<div data-product-edit-ready="1" data-product-exists="{{ $product->exists ? '1' : '0' }}" data-initial-product-status="{{ old('status',$product->status) }}" data-name-preview-url="{{ route('admin.products.name-preview') }}">
    <a class="back-link" href="{{ route('catalog.index') }}">← Nazad na artikle</a>
    <div class="page-heading">
        <div>
            <span class="eyebrow">Administracija kataloga</span>
            <h1>{{ $product->exists ? 'Izmeni artikal' : 'Dodaj novi artikal' }}</h1>
            <p>{{ $product->exists ? $product->name.' · '.$product->sku : 'Počni od tipa, brenda i modela — ostatak forme se prilagođava izboru.' }}</p>
        </div>
        @if($product->exists)
            <div class="page-heading-actions">

                <a class="button button-ghost" href="{{ route('admin.products.clone',$product) }}">Kloniraj artikal</a>
                @if($product->type?->auto_name_enabled || $product->type?->name_template)<button class="button button-ghost" type="submit" form="regenerate-name-form">Regeneriši naziv</button>@endif
                @can('catalog.manage_images')<a class="button button-ghost" href="{{ route('admin.products.images.index',$product) }}">Uredi slike</a>@endcan
            </div>
        @endif
    </div>

    {{-- ux-maximal-product-editor-phase1-v2 --}}
<section class="panel ux-product-workspace" data-ux-product-workspace>
    <div class="ux-product-workspace-head">
        <div>
            <span class="eyebrow">Brzi unos artikla</span>
            <h2>{{ $product->exists ? 'Uredi artikal kroz jasne radne celine' : 'Od identiteta do objave bez nepotrebnog lutanja' }}</h2>
            <p class="muted">Kao na modernim marketplace platformama, prvo definiši šta dodaješ. Tip artikla zatim sužava brendove, linije, specifikacije i automatsku kategoriju.</p>
        </div>
        <div class="ux-product-workspace-status">
            <span>Kompletnost artikla</span>
            <strong data-ux-product-completeness-mirror>—</strong>
            <small data-ux-product-completeness-copy>Postojeći backend indikator</small>
        </div>
    </div>

    <nav class="ux-product-task-map" aria-label="Radne celine Product Editor-a">
        <button type="button" data-ux-product-task="identity">
            <span class="ux-product-task-index">1</span>
            <span class="ux-product-task-copy"><strong>Osnovno</strong><small>tip, brend, linija, model i naziv</small></span>
            <span class="ux-product-task-state" data-ux-product-task-state>—</span>
        </button>
        <button type="button" data-ux-product-task="specifications">
            <span class="ux-product-task-index">2</span>
            <span class="ux-product-task-copy"><strong>Specifikacije</strong><small>ključna polja prvo, ostala na zahtev</small></span>
            <span class="ux-product-task-state" data-ux-product-task-state>—</span>
        </button>
        <button type="button" data-ux-product-task="commercial">
            <span class="ux-product-task-index">3</span>
            <span class="ux-product-task-copy"><strong>Prodaja</strong><small>status, cena i lager</small></span>
            <span class="ux-product-task-state" data-ux-product-task-state>—</span>
        </button>
        <button type="button" data-ux-product-task="media">
            <span class="ux-product-task-index">4</span>
            <span class="ux-product-task-copy"><strong>Fotografije</strong><small>slike, glavna slika i raspored</small></span>
            <span class="ux-product-task-state" data-ux-product-task-state>—</span>
        </button>
        <button type="button" data-ux-product-task="description">
            <span class="ux-product-task-index">5</span>
            <span class="ux-product-task-copy"><strong>Opis</strong><small>opis i interne napomene</small></span>
            <span class="ux-product-task-state" data-ux-product-task-state>—</span>
        </button>
    </nav>

    <div class="ux-product-command-row">
        <div class="ux-product-search-shell">
            <label>
                <span>Pronađi polje u ovoj formi</span>
                <input type="search" data-ux-product-field-search placeholder="Npr. RAM, cena, SKU, lager..." autocomplete="off">
            </label>
            <div class="ux-product-search-results" data-ux-product-search-results hidden aria-live="polite"></div>
        </div>

        <button class="button button-ghost" type="button" data-ux-product-next-missing>
            Idi na sledeće obavezno polje
        </button>
    </div>

    <div class="ux-product-workspace-meta">
        <div>
            <span>Obavezna polja</span>
            <strong data-ux-product-required-state>Provera...</strong>
        </div>
        <div>
            <span>Trenutna celina</span>
            <strong data-ux-product-current-task>Osnovno</strong>
        </div>
        <div>
            <span>Sledeće</span>
            <strong data-ux-product-next-label>Završna provera</strong>
        </div>
    </div>

    <details class="ux-product-review" data-ux-product-review>
        <summary>Pregled pre čuvanja</summary>
        <div class="ux-product-review-grid">
            <div><span>Tip</span><strong data-ux-product-review-value="product_type_id">—</strong></div>
            <div><span>Brend</span><strong data-ux-product-review-value="brand_id">—</strong></div>
            <div><span>Model</span><strong data-ux-product-review-value="model_name">—</strong></div>
            <div><span>Naziv</span><strong data-ux-product-review-value="name">—</strong></div>
            <div><span>Cena</span><strong data-ux-product-review-value="price_amount">—</strong></div>
            <div><span>Lager</span><strong data-ux-product-review-value="stock_quantity">—</strong></div>
            <div><span>Status</span><strong data-ux-product-review-value="status">—</strong></div>
            <div><span>Kompletnost</span><strong data-ux-product-review-value="completeness">—</strong></div>
        </div>
    </details>
</section>

@if($errors->any())
    <section class="alert alert-error ux-product-error-summary" role="alert" tabindex="-1" data-ux-product-error-summary>
        <div>
            <strong>Proveri podatke pre čuvanja</strong>
            <p>Sačuvan je tvoj unos. Izaberi grešku da odmah pređeš na odgovarajuće polje.</p>
        </div>
        <ul>
            @foreach($errors->getMessages() as $field => $messages)
                @foreach($messages as $message)
                    <li>
                        <button type="button" data-ux-product-error-target="{{ $field }}">{{ $message }}</button>
                    </li>
                @endforeach
            @endforeach
        </ul>
    </section>
@endif
<form
        id="product-editor-form"
        method="post"
        enctype="multipart/form-data" data-ux-sticky-actions
        action="{{ $product->exists ? route('admin.products.update',$product) : route('admin.products.store') }}"
        data-image-upload-form
        data-image-upload-mode="redirect"
    >
        @csrf
        @if($product->exists)@method('PUT')@endif
        <div class="admin-form-grid">
            <div class="form-main">
                <section class="panel form-section form-section-priority product-fast-start" data-product-fast-start>
                    <div class="form-section-number">1</div>
                    <div class="section-heading-row">
                        <div>
                            <span class="eyebrow">Brzi početak</span>
                            <h2>Šta dodaješ?</h2>
                            <p class="muted">Izaberi tip, zatim brend, liniju i model. Kategorija i dostupne specifikacije prilagođavaju se automatski.</p>
                        </div>
                    </div>
                    <div class="field-grid product-fast-start-grid">
                        <label class="specification-type-selector field-span-2"><span>Tip artikla</span><select name="product_type_id" data-product-type><option value="">Bez tipa / ručni unos</option>@foreach($types as $type)<option value="{{ $type->id }}" data-auto-name="{{ $type->auto_name_enabled ? '1' : '0' }}" data-name-template="{{ $type->name_template }}" data-min-completeness="{{ $type->minimum_completeness_percent ?? 0 }}" data-default-status="{{ $type->default_product_status ?? 'draft' }}" data-required-core="{{ implode(',',(array)($type->required_core_fields_json??[])) }}" data-category-id="{{ $type->category_id }}" data-category-name="{{ $type->category?->name }}" @selected((int)old('product_type_id',$product->product_type_id)===$type->id)>{{ $type->name }}</option>@endforeach</select><small class="muted" data-type-category-help>Kategorija će biti dodeljena automatski prema tipu artikla.</small></label>
                        <label><span>Brend</span><select name="brand_id" data-brand-select><option value="">Bez brenda</option>{{-- CATALOG_TYPE_SCOPED_TAXONOMY_V07 --}}@foreach($brands as $brand)<option value="{{ $brand->id }}" data-product-type-ids="{{ implode(',', $brandTypeIds[$brand->id] ?? []) }}" @selected((int)old('brand_id',$product->brand_id)===$brand->id)>{{ $brand->name }}</option>@endforeach</select></label>
                        <label><span>Linija proizvoda</span><select name="product_line_id" data-line-select><option value="">Bez linije</option>@foreach($lines as $line)<option value="{{ $line->id }}" data-brand-id="{{ $line->brand_id }}" data-product-type-ids="{{ implode(',', $lineTypeIds[$line->id] ?? []) }}" @selected((int)old('product_line_id',$product->product_line_id)===$line->id)>{{ $line->name }}</option>@endforeach</select><small class="muted" data-line-help>Prvo izaberi brend.</small></label>
                        <label class="field-span-2 product-model-field"><span>Model proizvoda</span><input name="model_name" maxlength="190" value="{{ old('model_name',$product->model_name) }}" placeholder="npr. 830 G8" data-product-model><small class="muted">Tačna oznaka modela ulazi u automatski naziv artikla.</small></label>
                        <div class="field-span-2 auto-category-card"><span>Automatska kategorija</span><strong data-auto-category-name>Biće određena prema izabranom tipu artikla</strong><small class="muted">Kategorija se ne bira ručno. Menja se u podešavanjima konkretnog tipa artikla.</small></div>
                        <div class="field-span-2 product-name-editor product-fast-name">
                            <label><span>Naziv artikla</span><input name="name" maxlength="190" value="{{ old('name',$product->name) }}" data-product-name></label>
                            <div class="inline-actions">
                                <button class="button button-ghost button-small" type="button" data-name-preview>Predloži naziv</button>
                                <label class="check-card compact"><input type="checkbox" name="regenerate_name" value="1" @checked(old('regenerate_name'))><span>Generiši pri čuvanju</span></label>
                            </div>
                            <small class="muted" data-name-template-help>Izaberi tip artikla da vidiš šablon naziva.</small>
                        </div>
                        @if($product->exists)
                            <label><span>SKU *</span><input name="sku" required maxlength="100" value="{{ old('sku',$product->sku) }}"></label>
                            <label class="check-card"><input type="checkbox" name="regenerate_sku" value="1" @checked(old('regenerate_sku'))><span>Automatski generiši novi SKU</span></label>
                        @endif
                    </div>
                </section>
                <section class="panel form-section form-section-priority product-specifications-section">
                    <div class="form-section-number">2</div>
                    <h2>Specifikacije</h2>
                    <p class="muted">Obavezna i najvažnija polja prikazana su odmah. Dodatne specifikacije ostaju dostupne jednim klikom, bez gubitka unetih vrednosti.</p>

                    @foreach($types as $type)
                        <div class="spec-panel" data-spec-panel="{{ $type->id }}" data-spec-priority-panel @if((int)old('product_type_id',$product->product_type_id)!==$type->id) hidden @endif>
                            @php
                                $renderableSpecFields = $type->fields->reject(fn ($candidate) => $candidate->isDerivedStorageTotalField());
                                $specFieldMap = $type->fields->keyBy(fn ($candidate) => (int) $candidate->id);
                                $prioritySpecIds = [];

                                foreach ($renderableSpecFields as $candidate) {
                                    $candidateIsRequired = (bool) ($candidate->pivot?->is_required ?? false);
                                    $candidateWeight = max(1, (int) ($candidate->pivot?->completeness_weight ?? 1));
                                    $candidateIsRepeatableStorage = $candidate->isRepeatableStorageField()
                                        && !$type->fields->contains(fn ($child) => (int) $child->parent_field_id === (int) $candidate->id);

                                    if ($candidateIsRequired || $candidateWeight >= 2 || $candidateIsRepeatableStorage) {
                                        $prioritySpecIds[(int) $candidate->id] = true;
                                    }
                                }

                                foreach (array_keys($prioritySpecIds) as $prioritySpecId) {
                                    $cursor = $specFieldMap->get((int) $prioritySpecId);
                                    $dependencyGuard = 0;
                                    while ($cursor && $cursor->parent_field_id && $dependencyGuard < 20) {
                                        $parentId = (int) $cursor->parent_field_id;
                                        $prioritySpecIds[$parentId] = true;
                                        $cursor = $specFieldMap->get($parentId);
                                        $dependencyGuard++;
                                    }
                                }

                                $prioritySpecCount = $renderableSpecFields
                                    ->filter(fn ($candidate) => isset($prioritySpecIds[(int) $candidate->id]))
                                    ->count();
                                $optionalSpecCount = max(0, $renderableSpecFields->count() - $prioritySpecCount);
                            @endphp
                            <div class="product-spec-priority-bar">
                                <div class="product-spec-priority-copy">
                                    <strong>Ključne specifikacije</strong>
                                    <small>Obavezna polja, važnija polja i njihove zavisnosti ostaju odmah vidljivi.</small>
                                </div>
                                <div class="product-spec-priority-actions">
                                    <span class="product-spec-priority-badge">{{ $prioritySpecCount }} odmah</span>
                                    @if($optionalSpecCount > 0)
                                        <button class="button button-ghost button-small product-spec-optional-toggle" type="button" data-spec-optional-toggle aria-expanded="false">
                                            <span data-spec-optional-toggle-label>Još specifikacija</span>
                                            <span class="product-spec-optional-count" data-spec-optional-count>{{ $optionalSpecCount }}</span>
                                        </button>
                                    @endif
                                </div>
                            </div>
                            <div class="field-grid" data-spec-field-grid>
                                @forelse($type->fields as $field)
                                    @continue($field->isDerivedStorageTotalField())
                                    @php
                                        $currentValue = old('specs.'.$field->id, $specValues[$field->id] ?? '');
                                        $currentDetail = old('spec_details.'.$field->id, $specDetails[$field->id] ?? '');
                                        $structuredOptions = $field->options->where('status', 'active');
                                        $isRepeatableStorage = $field->isRepeatableStorageField() && !$type->fields->contains(fn ($candidate) => (int) $candidate->parent_field_id === (int) $field->id);
                                        $storageTotalField = $isRepeatableStorage ? $type->fields->first(fn ($candidate) => $candidate->isStorageTotalField() && (int) $candidate->storage_source_field_id === (int) $field->id) : null;
                                        $wholeGigabytes = $field->requiresWholeGigabytes();
                                        $displayCurrentValue = $wholeGigabytes && is_numeric($currentValue) ? (string) (int) round((float) $currentValue) : $currentValue;
                                        $storageRows = old('spec_structured.'.$field->id, $specStructured[$field->id] ?? null);
                                        if (!is_array($storageRows) || $storageRows === []) {
                                            $storageRows = [];
                                            foreach (preg_split('/\s*\+\s*/u', trim((string) $currentValue), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $storedDisk) {
                                                preg_match('/^(.*?)(?:\s+(\d+)\s*GB)?$/iu', trim($storedDisk), $storedMatch);
                                                $storageRows[] = ['type' => trim((string) ($storedMatch[1] ?? $storedDisk)), 'capacity_gb' => ($storedMatch[2] ?? '') !== '' ? (int) $storedMatch[2] : null];
                                            }
                                        }
                                        if ($storageRows === []) $storageRows = [['type' => '', 'capacity_gb' => null]];
                                        $hasStorageCapacity = collect($storageRows)->contains(fn ($row) => isset($row['capacity_gb']) && $row['capacity_gb'] !== '' && $row['capacity_gb'] !== null);
                                        $storageCalculatedTotal = collect($storageRows)->sum(fn ($row) => max(0, (int) ($row['capacity_gb'] ?? 0)));
                                        $storageStoredTotal = $storageTotalField ? old('specs.'.$storageTotalField->id, $specValues[$storageTotalField->id] ?? 0) : 0;
                                        $storageTotalValue = $hasStorageCapacity ? $storageCalculatedTotal : max(0, (int) $storageStoredTotal);
                                        $fieldIsPriority = isset($prioritySpecIds[(int) $field->id]);
                                    @endphp
                                    <div class="spec-field-group {{ $isRepeatableStorage ? 'field-span-2' : '' }} {{ $fieldIsPriority ? 'spec-field-priority' : 'spec-field-optional' }}" data-spec-field-wrapper data-spec-priority="{{ $fieldIsPriority ? '1' : '0' }}" @if(!$fieldIsPriority) data-spec-optional="1" hidden @endif data-parent-field-id="{{ $field->parent_field_id }}" data-completeness-weight="{{ $field->pivot?->completeness_weight ?? 1 }}" data-required-field="{{ $field->pivot?->is_required ? '1' : '0' }}">
                                        @if($isRepeatableStorage)
                                            <div class="repeatable-storage-field" data-repeatable-storage data-storage-initial-total="{{ $storageTotalValue }}">
                                                <div class="repeatable-storage-heading">
                                                    <span>{{ $field->name }} @if($field->unit)({{ $field->unit }})@endif @if($field->pivot?->is_required)<b class="required-mark">*</b>@endif</span>
                                                    <button class="button button-small button-ghost" type="button" data-repeatable-add><x-icon name="plus-circle" size="16" /> Dodaj još jedan disk</button>
                                                </div>
                                                <input type="hidden" name="specs[{{ $field->id }}]" value="{{ $currentValue }}" data-repeatable-value data-spec-field-id="{{ $field->id }}" data-default-value="{{ $field->pivot?->default_value }}">
                                                <div class="repeatable-storage-list" data-repeatable-list>
                                                    @foreach($storageRows as $storageRow)
                                                        @php($storageType = trim((string) ($storageRow['type'] ?? '')))
                                                        @php($storageCapacity = ($storageRow['capacity_gb'] ?? '') === null ? '' : (string) ($storageRow['capacity_gb'] ?? ''))
                                                        <div class="repeatable-storage-row" data-repeatable-row>
                                                            <label class="repeatable-storage-type"><span>Tip diska</span>
                                                                @if($field->data_type === 'select')
                                                                    <select name="spec_lists[{{ $field->id }}][]" data-repeatable-input>
                                                                        <option value="">Izaberi disk</option>
                                                                        @if($structuredOptions->isNotEmpty())
                                                                            @foreach($structuredOptions as $option)<option value="{{ $option->value }}" @selected($storageType === (string)$option->value)>{{ $option->label }}</option>@endforeach
                                                                        @else
                                                                            @foreach(preg_split('/\R+/',trim((string)$field->options_text)) ?: [] as $option)<option value="{{ $option }}" @selected($storageType === (string)$option)>{{ $option }}</option>@endforeach
                                                                        @endif
                                                                    </select>
                                                                @else
                                                                    <input name="spec_lists[{{ $field->id }}][]" maxlength="255" value="{{ $storageType }}" placeholder="Npr. NVMe SSD" data-repeatable-input>
                                                                @endif
                                                            </label>
                                                            <label class="repeatable-storage-capacity"><span>Kapacitet (GB)</span><input name="spec_capacities[{{ $field->id }}][]" type="number" min="0" max="10000000" step="1" inputmode="numeric" value="{{ $storageCapacity }}" placeholder="512" data-repeatable-capacity></label>
                                                            <button class="repeatable-remove" type="button" data-repeatable-remove aria-label="Ukloni disk"><x-icon name="x" size="17" /></button>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                <template data-repeatable-template>
                                                    <div class="repeatable-storage-row" data-repeatable-row>
                                                        <label class="repeatable-storage-type"><span>Tip diska</span>
                                                            @if($field->data_type === 'select')
                                                                <select name="spec_lists[{{ $field->id }}][]" data-repeatable-input>
                                                                    <option value="">Izaberi disk</option>
                                                                    @if($structuredOptions->isNotEmpty())
                                                                        @foreach($structuredOptions as $option)<option value="{{ $option->value }}">{{ $option->label }}</option>@endforeach
                                                                    @else
                                                                        @foreach(preg_split('/\R+/',trim((string)$field->options_text)) ?: [] as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach
                                                                    @endif
                                                                </select>
                                                            @else
                                                                <input name="spec_lists[{{ $field->id }}][]" maxlength="255" placeholder="Npr. NVMe SSD" data-repeatable-input>
                                                            @endif
                                                        </label>
                                                        <label class="repeatable-storage-capacity"><span>Kapacitet (GB)</span><input name="spec_capacities[{{ $field->id }}][]" type="number" min="0" max="10000000" step="1" inputmode="numeric" placeholder="512" data-repeatable-capacity></label>
                                                        <button class="repeatable-remove" type="button" data-repeatable-remove aria-label="Ukloni disk"><x-icon name="x" size="17" /></button>
                                                    </div>
                                                </template>
                                                <small class="muted">Do 8 diskova. Redosled unosa je redosled prikaza.</small>
                                                @if($storageTotalField)
                                                    <div class="storage-total-card" data-storage-total-card>
                                                        <label>
                                                            <span>Ukupan kapacitet diskova (GB)</span>
                                                            <input type="number" min="0" step="1" inputmode="numeric" value="{{ $storageTotalValue }}" readonly aria-readonly="true" data-storage-total-display>
                                                        </label>
                                                        <input type="hidden" name="specs[{{ $storageTotalField->id }}]" value="{{ $storageTotalValue }}" data-storage-total-value data-spec-field-id="{{ $storageTotalField->id }}">
                                                        <small class="muted">Automatski zbir kapaciteta svih diskova iznad. Ovo polje se ne unosi ručno.</small>
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <label>
                                                <span>{{ $field->name }} @if($field->unit)({{ $field->unit }})@endif @if($field->pivot?->is_required)<b class="required-mark">*</b>@endif</span>
                                                @if($field->data_type==='boolean')
                                                    <select name="specs[{{ $field->id }}]" data-spec-field-id="{{ $field->id }}" data-default-value="{{ $field->pivot?->default_value }}"><option value="">Izaberi</option><option value="1" @selected((string)$currentValue==='1')>Da</option><option value="0" @selected((string)$currentValue==='0')>Ne</option></select>
                                                @elseif($field->data_type==='select')
                                                    <select name="specs[{{ $field->id }}]" data-spec-field-id="{{ $field->id }}" data-parent-field-id="{{ $field->parent_field_id }}" data-default-value="{{ $field->pivot?->default_value }}">
                                                        <option value="">Izaberi</option>
                                                        @if($structuredOptions->isNotEmpty())
                                                            @foreach($structuredOptions as $option)<option value="{{ $option->value }}" data-option-id="{{ $option->id }}" data-parent-option-ids="{{ $option->parentOptions->pluck('id')->implode(',') }}" @selected((string)$currentValue===(string)$option->value)>{{ $option->label }}</option>@endforeach
                                                        @else
                                                            @foreach(preg_split('/\R+/',trim((string)$field->options_text)) ?: [] as $option)<option value="{{ $option }}" @selected((string)$currentValue===$option)>{{ $option }}</option>@endforeach
                                                        @endif
                                                    </select>
                                                @else
                                                    <input name="specs[{{ $field->id }}]" data-spec-field-id="{{ $field->id }}" data-default-value="{{ $field->pivot?->default_value }}" type="{{ in_array($field->data_type,['integer','decimal'])?'number':'text' }}" step="{{ $wholeGigabytes || $field->data_type==='integer' ? '1' : '0.0001' }}" @if($wholeGigabytes) inputmode="numeric" min="0" @endif value="{{ $displayCurrentValue }}" placeholder="{{ $field->placeholder }}">
                                                @endif
                                                @if($field->help_text)<small class="muted">{{ $field->help_text }}</small>@endif
                                                @if($field->parent_field_id)<small class="muted" data-dependency-help>Prvo izaberi povezanu roditeljsku opciju.</small>@endif
                                            </label>
                                            @if($field->data_type==='select' && $field->detail_input_enabled)
                                                <label class="spec-detail-field"><span>{{ $field->detail_label ?: 'Tačan model / detalj' }}</span><input name="spec_details[{{ $field->id }}]" maxlength="500" value="{{ $currentDetail }}" placeholder="{{ $field->detail_placeholder }}" data-spec-detail-for="{{ $field->id }}" data-default-detail="{{ $field->pivot?->default_detail }}"></label>
                                            @endif
                                        @endif
                                    </div>
                                @empty
                                    <div class="empty-inline">Tip nema dodeljena polja.</div>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                    <div class="empty-inline" data-no-spec @if(old('product_type_id',$product->product_type_id)) hidden @endif>Izaberi tip artikla.</div>
                </section>

                <section class="panel form-section form-section-priority product-media-section">
                    <div class="form-section-number">3</div>
                    <div class="section-heading-row">
                        <div>
                            <h2>Dodavanje slika</h2>
                            <p class="muted">Odmah nakon izbora videćeš fotografije, broj fajlova i procenat slanja.</p>
                        </div>
                        @if($product->exists)
                            @can('catalog.manage_images')<a class="button button-ghost button-small" href="{{ route('admin.products.images.index',$product) }}">Otvori galeriju</a>@endcan
                        @endif
                    </div>
                    @include('admin.products.partials.image-upload', ['inputId' => 'product-editor-images'])

                    @if($product->exists && $product->images->isNotEmpty())
                        <div class="image-sort-heading">
                            <div><strong>Postojeće slike</strong><small>Glavnu sliku biraš zvezdicom. Ostale prevuci za promenu rasporeda.</small></div>
                            <span class="image-sort-status" data-image-sort-status>Raspored je sačuvan</span>
                        </div>
                        <div class="product-edit-image-grid image-sortable-grid" data-image-sortable data-reorder-url="{{ route('admin.products.images.reorder',$product) }}">
                            @foreach($product->images as $image)
                                @include('admin.products.partials.image-card', ['product' => $product, 'image' => $image, 'iteration' => $loop->iteration, 'allowDelete' => false])
                            @endforeach
                        </div>
                    @endif
                </section>

                <section class="panel form-section product-description-section">
                    <div class="form-section-number">4</div>
                    <h2>Opis i napomene</h2>
                    <label><span>Opis / specifikacije *</span><textarea name="description" rows="10" required>{{ old('description',$product->description) }}</textarea></label>
                    <label><span>Interne napomene</span><textarea name="notes" rows="4">{{ old('notes',$product->notes) }}</textarea></label>
                </section>
            </div>

            <aside class="form-side">
                <section class="panel form-section completeness-card" data-completeness-card>
                    <div class="completeness-heading"><h2>Kompletnost artikla</h2><strong data-completeness-percent>{{ $currentCompleteness }}%</strong></div>
                    <div class="completeness-track"><span data-completeness-bar style="width:{{ $currentCompleteness }}%"></span></div>
                    <p class="muted" data-completeness-message>Popuni podatke šablona kako bi artikal bio spreman za objavu.</p>
                </section>
                <section class="panel form-section sticky-card product-sale-panel" data-product-sale-panel>
                    <h2>Prodaja</h2>
                    <p class="muted">Najvažniji komercijalni podaci su na jednom mestu. Status je prvi da uvek znaš da li je artikal nacrt, aktivan ili neaktivan.</p>
                    <div class="product-publication-guidance" data-product-publication-guidance>Za nepotpun unos koristi dugme za cuvanje nacrta. Aktivacija i dalje zahteva kompletne podatke i pozitivnu cenu.</div>
                    <label class="product-status-field"><span>Status artikla</span><select name="status" data-product-status>@foreach(['draft'=>'Nacrt','active'=>'Aktivan','inactive'=>'Neaktivan'] as $value=>$label)<option value="{{ $value }}" @selected(old('status',$product->status)===$value)>{{ $label }}</option>@endforeach</select><small class="muted">Nacrt = u pripremi · Aktivan = vidljiv u katalogu · Neaktivan = privremeno skriven.</small></label>
                    <label><span>Cena *</span><input name="price_amount" type="number" step="0.01" min="0" required value="{{ old('price_amount',$product->price_amount) }}" data-publication-price></label>
                    <label><span>Valuta</span><select name="price_currency"><option @selected(old('price_currency',$product->price_currency)==='EUR')>EUR</option><option @selected(old('price_currency',$product->price_currency)==='RSD')>RSD</option></select></label>
                    <label><span>Nabavna cena RSD</span><input name="purchase_price_rsd" type="number" step="0.01" min="0" value="{{ old('purchase_price_rsd',$product->purchase_price_rsd) }}"><small>Koristi se za obračun marže i snapshotuje se pri prodaji.</small></label>
                    @if(auth()->user()?->hasRole('superadmin'))
                    <label><span>Ručna provizija EUR</span><input name="manual_commission_eur" type="number" step="0.01" min="0" value="{{ old('manual_commission_eur',$product->manual_commission_eur) }}"></label>
                    @endif
                    @if(!$product->exists || auth()->user()->can('stock.adjust'))
                        <label><span>Količina</span><input name="stock_quantity" type="number" min="0" required value="{{ old('stock_quantity',$product->stock_quantity??0) }}"></label>
                        <label><span>Prag niskog lagera</span><input name="low_stock_threshold" type="number" min="0" required value="{{ old('low_stock_threshold',$product->low_stock_threshold??1) }}"></label>
                    @else
                        <input type="hidden" name="stock_quantity" value="{{ $product->stock_quantity }}"><input type="hidden" name="low_stock_threshold" value="{{ $product->low_stock_threshold }}">
                        <label><span>Količina</span><input type="number" value="{{ $product->stock_quantity }}" disabled><small class="muted">Za promenu je potrebna dozvola Korekcija lagera.</small></label>
                        <label><span>Prag niskog lagera</span><input type="number" value="{{ $product->low_stock_threshold }}" disabled></label>
                    @endif

                    <div class="product-save-actions product-save-actions-sidebar">
                        <button class="button button-secondary" type="submit" name="save_draft" value="1" formnovalidate data-product-save-draft>Sa&#269;uvaj kao nacrt</button>
                        <button class="button button-primary button-large" type="submit" data-product-save-context>{{ $product->exists ? 'Sa&#269;uvaj izmene' : 'Kreiraj artikal' }}</button>
                    </div>
                </section>
                @if($product->exists)
                    <section class="panel danger-zone product-danger-zone">
                        <h2>Arhiva i trajno brisanje</h2>
                        <p class="muted">Arhiviranje skriva artikal, ali čuva sve podatke i poslovnu istoriju.</p>
                        @if($product->deleted_at)
                            <button form="restore-product" class="button button-secondary" type="submit"><x-icon name="refresh" size="18" /> Opozovi Arhiviranje</button>
                        @else
                            <button form="archive-product" class="button button-warning" type="submit" data-confirm="Arhivirati artikal {{ $product->sku }}?"><x-icon name="archive" size="18" /> Arhiviraj artikal</button>
                        @endif

                        <details class="product-purge-details">
                            <summary>Trajno obriši artikal</summary>
                            <div class="product-purge-content">
                                <p>Trajno brisanje uklanja artikal, specifikacije i zapise galerije. Ovu radnju nije moguće poništiti.</p>
                                @if($deletionBlockers !== [])
                                    <div class="product-delete-blockers">
                                        <strong>Trajno brisanje trenutno nije dozvoljeno zbog poslovne istorije:</strong>
                                        <ul>@foreach($deletionBlockers as $label => $count)<li>{{ $label }}: {{ $count }}</li>@endforeach</ul>
                                        <small>Koristi arhiviranje da porudžbine, lager i izveštaji ostanu ispravni.</small>
                                    </div>
                                @else
                                    <label><span>Za potvrdu upiši SKU: {{ $product->sku }}</span><input form="purge-product" name="confirmation" maxlength="100" autocomplete="off" placeholder="{{ $product->sku }}" value="{{ old('confirmation') }}"></label>
                                    @error('confirmation')<div class="field-error">{{ $message }}</div>@enderror
                                    <label class="check-card product-delete-images"><input form="purge-product" type="checkbox" name="delete_images" value="1" checked><span>Obriši i sve lokalne slike artikla sa servera</span></label>
                                    <small class="muted">Legacy read-only slike ne mogu fizički da se brišu sa izvornog servera, ali njihovi CMS zapisi biće uklonjeni.</small>
                                    <button form="purge-product" class="button button-danger button-large" type="submit" data-confirm="Trajno obrisati artikal {{ $product->sku }}? Ovu radnju nije moguće poništiti."><x-icon name="trash" size="18" /> Trajno obriši artikal</button>
                                @endif
                            </div>
                        </details>
                        @if($product->deleted_at && auth()->user()?->hasRole('superadmin'))
                            <details class="product-purge-details product-total-purge-details" data-total-product-purge-workspace>
                                <summary>Trajno obriši SVE tragove artikla</summary>
                                <div class="product-purge-content">
                                    <p><strong>Izuzetno destruktivna SuperAdmin akcija.</strong> Ovaj tok je odvojen od običnog trajnog brisanja i namenjen je arhiviranim artiklima sa poslovnom istorijom.</p>
                                    <p class="muted">Live CMS će ukloniti sam artikal, slike i specifikacije; istorijske porudžbine, lager i garancije ostaju kao poslovni događaji, ali bez veze ka obrisanom artiklu, SKU-a i naziva izabranog artikla.</p>
                                    <p class="muted">Audit/notification/async/data-quality tragovi biće obrisani ili redigovani. Live PDF i filesystem ZERO TRACE verifier se izvršava nakon commita.</p>
                                    <div class="product-delete-blockers">
                                        <strong>Retention granica:</strong>
                                        <small>Disaster-recovery backupi i eventualne off-host kopije se NE brišu ovom akcijom. Za njih važi zasebna retention odluka.</small>
                                    </div>

                                    <label>
                                        <span>Razlog Total Product Purge operacije</span>
                                        <textarea form="total-purge-product" name="total_reason" rows="4" maxlength="1000" required placeholder="Zašto identitet ovog artikla mora biti uklonjen iz live sistema?">{{ old('total_reason') }}</textarea>
                                    </label>
                                    @error('total_reason')<div class="field-error">{{ $message }}</div>@enderror

                                    <label>
                                        <span>1. potvrda — upiši tačan SKU: {{ $product->sku }}</span>
                                        <input form="total-purge-product" name="total_confirmation" maxlength="100" autocomplete="off" required placeholder="{{ $product->sku }}" value="{{ old('total_confirmation') }}">
                                    </label>
                                    @error('total_confirmation')<div class="field-error">{{ $message }}</div>@enderror

                                    <label>
                                        <span>2. nepovratna potvrda — upiši tačno: TRAJNO OBRIŠI SVE TRAGOVE</span>
                                        <input form="total-purge-product" name="total_irreversible_confirmation" maxlength="100" autocomplete="off" required placeholder="TRAJNO OBRIŠI SVE TRAGOVE" value="{{ old('total_irreversible_confirmation') }}">
                                    </label>
                                    @error('total_irreversible_confirmation')<div class="field-error">{{ $message }}</div>@enderror

                                    <label class="check-card">
                                        <input form="total-purge-product" type="checkbox" name="total_retention_acknowledged" value="1" required @checked(old('total_retention_acknowledged'))>
                                        <span>Razumem da se live CMS čisti do ZERO TRACE nivoa, ali disaster-recovery backupi i off-host kopije ostaju zasebna retention granica.</span>
                                    </label>
                                    @error('total_retention_acknowledged')<div class="field-error">{{ $message }}</div>@enderror

                                    <button
                                        form="total-purge-product"
                                        class="button button-danger button-large"
                                        type="submit"
                                        data-confirm="POZOR: Total Product Purge za {{ $product->sku }} nema normalan restore. Nastaviti samo ako želiš uklanjanje svih live tragova artikla."
                                    >
                                        <x-icon name="trash" size="18" /> Trajno obriši SVE live tragove artikla
                                    </button>
                                </div>
                            </details>
                        @endif
                    </section>
                @endif
            </aside>
        </div>

        <div class="product-editor-action-bar" data-product-editor-action-bar>
            <div class="product-editor-action-copy">
                <strong>{{ $product->exists ? 'Sacuvaj izmene artikla' : 'Kreiraj artikal' }}</strong>
                <span>{{ $product->exists ? 'Sve izmene na ovoj stranici cuvaju se jednim klikom.' : 'Sacuvaj novi artikal kada zavrsis unos.' }}</span>
            </div>
            <div class="product-editor-action-buttons">
                @if($product->exists && auth()->user()?->hasRole('superadmin'))
                    @if($product->deleted_at === null && in_array((string) $product->status, ['active', 'inactive'], true))
                        <a class="button button-secondary" href="{{ route('catalog.show', ['slug' => $product->slug]) }}#direct-sale-title">Direktna prodaja</a>
                    @else
                        <span class="product-direct-sale-note">Direktna prodaja je dostupna za aktivan ili neaktivan artikal koji nije arhiviran.</span>
                    @endif
                @endif
                <button class="button button-secondary" type="submit" name="save_draft" value="1" formnovalidate data-product-save-draft>Sa&#269;uvaj kao nacrt</button>
                <button class="button button-primary button-large" type="submit" data-product-save-primary>
                    {{ $product->exists ? 'Sacuvaj izmene' : 'Kreiraj artikal' }}
                </button>
            </div>
        </div>
    </form>

    @if($product->exists)
        @if($product->deleted_at)<form id="restore-product" method="post" action="{{ route('admin.products.restore',$product) }}">@csrf</form>@else<form id="archive-product" method="post" action="{{ route('admin.products.archive',$product) }}">@csrf @method('DELETE')</form>@endif
        <form id="purge-product" method="post" action="{{ route('admin.products.purge',$product) }}">@csrf @method('DELETE')</form>
        @if($product->deleted_at && auth()->user()?->hasRole('superadmin'))
            <form id="total-purge-product" method="post" action="{{ route('admin.products.total-purge',$product) }}">@csrf @method('DELETE')</form>
        @endif
        @can('catalog.manage_images')
            @foreach($product->images as $image)
                <form id="primary-{{ $image->id }}" method="post" action="{{ route('admin.products.images.primary', [$product, $image]) }}" data-image-ajax-form data-image-action="primary">@csrf @method('PATCH')</form>
                <form id="rotate-left-{{ $image->id }}" method="post" action="{{ route('admin.products.images.rotate', [$product, $image]) }}" data-image-ajax-form data-image-action="rotate" data-image-id="{{ $image->id }}">@csrf @method('PATCH')<input type="hidden" name="degrees" value="270"></form>
                <form id="rotate-right-{{ $image->id }}" method="post" action="{{ route('admin.products.images.rotate', [$product, $image]) }}" data-image-ajax-form data-image-action="rotate" data-image-id="{{ $image->id }}">@csrf @method('PATCH')<input type="hidden" name="degrees" value="90"></form>
            @endforeach
        @endcan
    @endif
    @if($product->exists)<form id="regenerate-name-form" method="post" action="{{ route('admin.products.regenerate-name',$product) }}">@csrf</form>@endif
</div>
@endsection



@push('scripts')
<script>
(() => {
    const boot = () => {
        const workspace = document.querySelector('[data-ux-product-workspace]');
        const saveButton = document.querySelector('[data-product-save-primary]');
        const form = saveButton?.closest('form');

        if (!workspace || !form) return;

        const search = workspace.querySelector('[data-ux-product-field-search]');
        const searchResults = workspace.querySelector('[data-ux-product-search-results]');
        const nextButton = workspace.querySelector('[data-ux-product-next-missing]');
        const requiredState = workspace.querySelector('[data-ux-product-required-state]');
        const currentTaskNode = workspace.querySelector('[data-ux-product-current-task]');
        const nextLabel = workspace.querySelector('[data-ux-product-next-label]');
        const completenessMirror = workspace.querySelector('[data-ux-product-completeness-mirror]');
        const completenessCopy = workspace.querySelector('[data-ux-product-completeness-copy]');
        const errorSummary = document.querySelector('[data-ux-product-error-summary]');
        const taskButtons = Array.from(workspace.querySelectorAll('[data-ux-product-task]'));

        const named = (name) => {
            const item = form.elements.namedItem(name);
            if (!item) return null;
            if (item instanceof RadioNodeList) return item[0] || null;
            return item;
        };

        const fieldNameFromErrorKey = (key) => {
            const parts = String(key || '').split('.');
            if (parts.length === 0) return '';
            return parts[0] + parts.slice(1).map((part) => `[${part}]`).join('');
        };

        const isVisibleControl = (control) => {
            if (!(control instanceof HTMLElement)) return false;
            if (control instanceof HTMLInputElement && control.type === 'hidden') return false;
            if (control.closest('[hidden]')) return false;
            return !control.hasAttribute('disabled');
        };

        const isSearchableControl = (control) => {
            if (!(control instanceof HTMLElement)) return false;
            if (control instanceof HTMLInputElement && control.type === 'hidden') return false;
            if (control.hasAttribute('disabled')) return false;

            const panel = control.closest('[data-spec-panel]');
            if (panel?.hidden) return false;

            const hiddenAncestor = control.closest('[hidden]');
            if (!hiddenAncestor) return true;

            return hiddenAncestor.matches('[data-spec-optional="1"]')
                && hiddenAncestor.closest('[data-spec-panel]') === panel;
        };

        const sectionFor = (control) => control?.closest('.form-section') || null;

        const firstNamed = (names) => {
            for (const name of names) {
                const control = named(name);
                if (control) return control;
            }
            return null;
        };

        const firstByPrefix = (prefixes) => {
            const matches = Array.from(form.querySelectorAll('[name]'))
                .filter((control) => prefixes.some((prefix) => String(control.name || '').startsWith(prefix)));
            return matches.find(isVisibleControl) || matches[0] || null;
        };

        const definitions = [
            {
                key: 'identity',
                label: 'Osnovno',
                control: () => firstNamed(['product_type_id','brand_id','product_line_id','model_name','name','sku']),
            },
            {
                key: 'specifications',
                label: 'Specifikacije',
                control: () => firstByPrefix(['specs[','spec_lists[','spec_structured[','spec_capacities[']),
            },
            {
                key: 'commercial',
                label: 'Prodaja',
                control: () => firstNamed(['status','price_amount','stock_quantity','low_stock_threshold']),
            },
            {
                key: 'media',
                label: 'Fotografije',
                control: () => form.querySelector('input[type="file"],[data-product-image-upload],.inline-upload'),
            },
            {
                key: 'description',
                label: 'Opis',
                control: () => firstNamed(['description','notes']),
            },
        ];

        const resolved = new Map();

        definitions.forEach((definition) => {
            const control = definition.control();
            const section = sectionFor(control);
            const button = taskButtons.find((candidate) => candidate.dataset.uxProductTask === definition.key);

            if (!control || !section || !button) {
                if (button) button.hidden = true;
                return;
            }

            section.dataset.uxProductSection = section.dataset.uxProductSection || definition.key;
            if (!section.id) section.id = `ux-product-${definition.key}`;

            resolved.set(definition.key, {
                ...definition,
                control,
                section,
                button,
                stateNode: button.querySelector('[data-ux-product-task-state]'),
            });
        });

        const labelForControl = (control) => {
            const label = control?.closest('label');
            const explicit = label?.querySelector(':scope > span');
            if (explicit?.textContent?.trim()) return explicit.textContent.trim();

            if (control?.id) {
                const linked = form.querySelector(`label[for="${control.id}"]`);
                if (linked?.textContent?.trim()) return linked.textContent.trim();
            }

            return control?.getAttribute('placeholder')
                || control?.getAttribute('aria-label')
                || control?.name
                || 'Polje';
        };

        const hasValue = (control) => {
            if (!control) return false;

            if (control instanceof HTMLInputElement && ['checkbox','radio'].includes(control.type)) {
                return control.checked;
            }

            if (control instanceof HTMLSelectElement && control.multiple) {
                return control.selectedOptions.length > 0;
            }

            return String(control.value || '').trim() !== '';
        };

        const optionalWrappers = (panel) =>
            Array.from(panel?.querySelectorAll('[data-spec-optional="1"]') || []);

        const updateOptionalToggle = (panel) => {
            if (!panel) return;
            const wrappers = optionalWrappers(panel);
            const toggle = panel.querySelector('[data-spec-optional-toggle]');
            if (!toggle || wrappers.length === 0) return;

            const expanded = panel.dataset.specOptionalExpanded === '1';
            wrappers.forEach((wrapper) => { wrapper.hidden = !expanded; });

            const filled = wrappers.filter((wrapper) =>
                Array.from(wrapper.querySelectorAll('input:not([type="hidden"]),select,textarea'))
                    .some((control) => hasValue(control))
            ).length;

            const label = toggle.querySelector('[data-spec-optional-toggle-label]');
            const count = toggle.querySelector('[data-spec-optional-count]');
            if (label) label.textContent = expanded ? 'Sakrij dodatne specifikacije' : 'Još specifikacija';
            if (count) count.textContent = filled > 0 ? `${filled}/${wrappers.length}` : String(wrappers.length);
            toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        };

        const setOptionalExpanded = (panel, expanded) => {
            if (!panel) return;
            panel.dataset.specOptionalExpanded = expanded ? '1' : '0';
            updateOptionalToggle(panel);
        };

        const revealOptionalForControl = (control) => {
            const wrapper = control?.closest('[data-spec-optional="1"]');
            if (!wrapper) return;
            setOptionalExpanded(wrapper.closest('[data-spec-panel]'), true);
        };

        form.querySelectorAll('[data-spec-optional-toggle]').forEach((toggle) => {
            const panel = toggle.closest('[data-spec-panel]');
            if (!panel) return;
            updateOptionalToggle(panel);
            toggle.addEventListener('click', () => {
                setOptionalExpanded(panel, panel.dataset.specOptionalExpanded !== '1');
            });
        });

        const visibleRequiredControls = () =>
            Array.from(form.querySelectorAll('[required]'))
                .filter(isVisibleControl);

        const missingRequiredControls = () =>
            visibleRequiredControls().filter((control) => !hasValue(control));

        const serverErrorTargets = () =>
            Array.from(document.querySelectorAll('[data-ux-product-error-target]'))
                .map((button) => named(fieldNameFromErrorKey(button.dataset.uxProductErrorTarget || '')))
                .filter(Boolean);

        const taskForControl = (control) => {
            if (!control) return null;
            const section = sectionFor(control);
            if (!section) return null;

            let match = null;
            resolved.forEach((definition) => {
                if (definition.section === section) match = definition;
            });
            return match;
        };

        const scrollToControl = (control) => {
            if (!control) return;
            revealOptionalForControl(control);
            const target = sectionFor(control) || control;
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            window.setTimeout(() => {
                try {
                    control.focus({ preventScroll: true });
                } catch (_) {
                    control.focus();
                }
                control.classList.add('ux-product-focus-ring');
                window.setTimeout(() => control.classList.remove('ux-product-focus-ring'), 1500);
            }, 180);
        };

        const jumpToTask = (definition) => {
            const target = definition.control || definition.section;
            if (!target) return;
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            target.classList.add('ux-product-focus-ring');
            window.setTimeout(() => target.classList.remove('ux-product-focus-ring'), 1400);
        };

        taskButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const definition = resolved.get(button.dataset.uxProductTask || '');
                if (definition) jumpToTask(definition);
            });
        });

        const updateCompleteness = () => {
            const authoritativePercent = document.querySelector('[data-completeness-percent]');
            const authoritativeMessage = document.querySelector('[data-completeness-message]');
            const percent = authoritativePercent?.textContent?.trim() || '—';

            if (completenessMirror) completenessMirror.textContent = percent;
            if (completenessCopy) {
                completenessCopy.textContent = authoritativeMessage?.textContent?.trim()
                    || 'Postojeći backend indikator';
            }

            const review = workspace.querySelector('[data-ux-product-review-value="completeness"]');
            if (review) review.textContent = percent;
        };

        const updateTaskStates = () => {
            const errors = serverErrorTargets();

            resolved.forEach((definition) => {
                const required = Array.from(definition.section.querySelectorAll('[required]'))
                    .filter(isVisibleControl);
                const missing = required.filter((control) => !hasValue(control));
                const hasServerError = errors.some((control) => sectionFor(control) === definition.section);

                let text = 'Dostupno';
                let state = 'available';

                if (hasServerError) {
                    text = 'Proveri';
                    state = 'error';
                } else if (missing.length > 0) {
                    text = `Nedostaje ${missing.length}`;
                    state = 'missing';
                } else if (required.length > 0) {
                    text = 'Spremno';
                    state = 'ready';
                } else if (definition.key === 'media') {
                    text = 'Opcionalno';
                    state = 'optional';
                }

                if (definition.stateNode) {
                    definition.stateNode.textContent = text;
                    definition.stateNode.dataset.state = state;
                }
            });
        };

        const updateRequiredSummary = () => {
            const required = visibleRequiredControls();
            const missing = missingRequiredControls();

            if (requiredState) {
                requiredState.textContent = missing.length === 0
                    ? `Spremno · ${required.length}/${required.length}`
                    : `Popunjeno ${required.length - missing.length}/${required.length}`;
            }

            if (nextLabel) {
                nextLabel.textContent = missing[0]
                    ? labelForControl(missing[0])
                    : 'Završna provera i čuvanje';
            }
        };

        const controlDisplayValue = (name) => {
            const control = named(name);
            if (!control) return '—';

            if (control instanceof HTMLSelectElement) {
                return control.selectedOptions[0]?.textContent?.trim() || '—';
            }

            return String(control.value || '').trim() || '—';
        };

        const updateReview = () => {
            workspace.querySelectorAll('[data-ux-product-review-value]').forEach((node) => {
                const key = node.dataset.uxProductReviewValue || '';
                if (key === 'completeness') return;

                let value = controlDisplayValue(key);

                if (key === 'price_amount' && value !== '—') {
                    const currency = controlDisplayValue('price_currency');
                    value = `${value} ${currency === '—' ? '' : currency}`.trim();
                }

                node.textContent = value;
            });

            updateCompleteness();
        };

        const setCurrentTask = (key) => {
            const definition = resolved.get(key);
            if (!definition) return;

            if (currentTaskNode) currentTaskNode.textContent = definition.label;

            taskButtons.forEach((button) => {
                button.classList.toggle('is-current', button.dataset.uxProductTask === key);
            });
        };

        const updateCurrentTask = () => {
            let best = null;
            let bestDistance = Number.POSITIVE_INFINITY;

            resolved.forEach((definition, key) => {
                const rect = definition.section.getBoundingClientRect();
                if (rect.bottom < 80) return;

                const distance = Math.abs(rect.top - 150);
                if (distance < bestDistance) {
                    bestDistance = distance;
                    best = key;
                }
            });

            if (best) setCurrentTask(best);
        };

        const searchableFields = () => {
            const entries = [];
            const seen = new Set();

            Array.from(form.querySelectorAll('label')).forEach((label) => {
                const control = label.querySelector('input:not([type="hidden"]),select,textarea');
                if (!control || !isSearchableControl(control) || seen.has(control)) return;

                const text = String(label.textContent || '').replace(/\s+/g, ' ').trim();
                if (!text) return;

                seen.add(control);
                entries.push({ control, text });
            });

            return entries;
        };

        const closeSearchResults = () => {
            if (!searchResults) return;
            searchResults.replaceChildren();
            searchResults.hidden = true;
        };

        const renderSearchResults = () => {
            if (!search || !searchResults) return;

            const query = String(search.value || '').trim().toLocaleLowerCase('sr-Latn');
            searchResults.replaceChildren();

            if (query.length < 2) {
                searchResults.hidden = true;
                return;
            }

            const matches = searchableFields()
                .filter((entry) => entry.text.toLocaleLowerCase('sr-Latn').includes(query))
                .slice(0, 7);

            if (matches.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'ux-product-search-empty';
                empty.textContent = 'Nema polja koje odgovara pretrazi.';
                searchResults.append(empty);
                searchResults.hidden = false;
                return;
            }

            matches.forEach((entry) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = labelForControl(entry.control);
                button.addEventListener('click', () => {
                    closeSearchResults();
                    search.value = '';
                    scrollToControl(entry.control);
                });
                searchResults.append(button);
            });

            searchResults.hidden = false;
        };

        search?.addEventListener('input', renderSearchResults);
        search?.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                search.value = '';
                closeSearchResults();
            }
        });

        document.addEventListener('click', (event) => {
            if (!workspace.contains(event.target)) closeSearchResults();
        });

        nextButton?.addEventListener('click', () => {
            const next = missingRequiredControls()[0];
            if (next) {
                scrollToControl(next);
                return;
            }

            const review = workspace.querySelector('[data-ux-product-review]');
            if (review instanceof HTMLDetailsElement) review.open = true;
            saveButton.scrollIntoView({ behavior: 'smooth', block: 'center' });
            window.setTimeout(() => saveButton.focus({ preventScroll: true }), 180);
        });

        document.querySelectorAll('[data-ux-product-error-target]').forEach((button) => {
            button.addEventListener('click', () => {
                const name = fieldNameFromErrorKey(button.dataset.uxProductErrorTarget || '');
                const control = named(name);

                if (control) {
                    scrollToControl(control);
                    return;
                }

                const rootName = String(button.dataset.uxProductErrorTarget || '').split('.')[0];
                const fallback = Array.from(form.querySelectorAll('[name]'))
                    .find((candidate) => String(candidate.name || '').startsWith(`${rootName}[`));

                if (fallback) scrollToControl(fallback);
            });
        });

        serverErrorTargets().forEach(revealOptionalForControl);

        const refresh = () => {
            updateRequiredSummary();
            updateTaskStates();
            updateReview();
        };

        form.addEventListener('input', refresh);
        form.addEventListener('change', () => {
            window.setTimeout(refresh, 0);
        });

        const completenessObserverTarget = document.querySelector('[data-completeness-card]');
        if (completenessObserverTarget && 'MutationObserver' in window) {
            const observer = new MutationObserver(updateCompleteness);
            observer.observe(completenessObserverTarget, {
                subtree: true,
                childList: true,
                characterData: true,
                attributes: true,
                attributeFilter: ['style'],
            });
        }

        let scrollScheduled = false;
        window.addEventListener('scroll', () => {
            if (scrollScheduled) return;
            scrollScheduled = true;
            window.requestAnimationFrame(() => {
                updateCurrentTask();
                scrollScheduled = false;
            });
        }, { passive: true });

        refresh();
        updateCurrentTask();

        if (errorSummary) {
            window.requestAnimationFrame(() => {
                errorSummary.focus({ preventScroll: true });
                errorSummary.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot, { once: true });
    } else {
        boot();
    }
})();
</script>
@endpush
@push('scripts')
<script>
(() => {
    const root = document.querySelector('[data-product-edit-ready]');
    const form = root?.querySelector(':scope > form');
    const typeSelect = form?.querySelector('[data-product-type]');
    const brandSelect = form?.querySelector('[data-brand-select]');
    const lineSelect = form?.querySelector('[data-line-select]');
    const modelInput = form?.querySelector('[data-product-model]');
    const lineHelp = form?.querySelector('[data-line-help]');
    const typeCategoryHelp = form?.querySelector('[data-type-category-help]');
    const autoCategoryName = form?.querySelector('[data-auto-category-name]');
    const nameInput = form?.querySelector('[data-product-name]');
    const nameHelp = form?.querySelector('[data-name-template-help]');
    const statusSelect = form?.querySelector('[name="status"]');
    const priceInput = form?.querySelector('[data-publication-price]');
    const publicationGuidance = form?.querySelector('[data-product-publication-guidance]');
    const completenessPercent = root?.querySelector('[data-completeness-percent]');
    const completenessBar = root?.querySelector('[data-completeness-bar]');
    const completenessMessage = root?.querySelector('[data-completeness-message]');

    const activeTypeOption = () => typeSelect?.selectedOptions?.[0] || null;
    const activePanel = () => root?.querySelector(`[data-spec-panel="${typeSelect?.value || ''}"]`) || null;

    const supportsProductType = (option, typeId) => {
        if (!option || !typeId) return false;
        return (option.dataset.productTypeIds || '').split(',').filter(Boolean).includes(typeId);
    };

    const updateBrands = () => {
        if (!brandSelect) return;
        const typeId = typeSelect?.value || '';
        let visibleCount = 0;
        Array.from(brandSelect.options).forEach((option, index) => {
            if (index === 0) return;
            const visible = supportsProductType(option, typeId);
            option.hidden = !visible;
            option.disabled = !visible;
            if (visible) visibleCount += 1;
        });
        const selected = brandSelect.selectedOptions[0];
        if (selected && selected.value !== '' && selected.disabled) {
            brandSelect.value = '';
            if (lineSelect) lineSelect.value = '';
        }
        brandSelect.disabled = typeId === '' || visibleCount === 0;
    };

    const updateProductLines = () => {
        if (!lineSelect) return;
        const typeId = typeSelect?.value || '';
        const brandId = brandSelect?.value || '';
        let visibleCount = 0;
        Array.from(lineSelect.options).forEach((option, index) => {
            if (index === 0) return;
            const visible = typeId !== '' && brandId !== '' && option.dataset.brandId === brandId && supportsProductType(option, typeId);
            option.hidden = !visible;
            option.disabled = !visible;
            if (visible) visibleCount += 1;
        });
        const selected = lineSelect.selectedOptions[0];
        if (selected && selected.value !== '' && selected.disabled) lineSelect.value = '';
        lineSelect.disabled = typeId === '' || brandId === '' || visibleCount === 0;
        if (lineHelp) lineHelp.textContent = typeId === '' ? 'Prvo izaberi tip artikla.' : (brandId === '' ? 'Prvo izaberi brend.' : (visibleCount ? 'Prikazane su samo linije izabranog brenda i tipa artikla.' : 'Za ovaj brend i tip nema aktivnih linija.'));
        refreshCompleteness();
    };

    const selectedOptionId = (select) => select?.selectedOptions?.[0]?.dataset?.optionId || '';

    const refreshChild = (childSelect) => {
        const parentFieldId = childSelect.dataset.parentFieldId || '';
        if (!parentFieldId) return false;
        const panel = childSelect.closest('[data-spec-panel]');
        const parent = panel?.querySelector(`[data-spec-field-id="${parentFieldId}"]`);
        const parentOptionId = selectedOptionId(parent);
        let visibleCount = 0;
        let selectedStillValid = childSelect.value === '';
        Array.from(childSelect.options).forEach((option, index) => {
            if (index === 0) return;
            const parents = (option.dataset.parentOptionIds || '').split(',').filter(Boolean);
            const visible = parentOptionId !== '' && parents.includes(parentOptionId);
            option.hidden = !visible;
            option.disabled = !visible;
            if (visible) visibleCount += 1;
            if (visible && option.selected) selectedStillValid = true;
        });
        if (!selectedStillValid) childSelect.value = '';
        childSelect.disabled = parentOptionId === '' || visibleCount === 0;
        const wrapper = childSelect.closest('[data-spec-field-wrapper]');
        wrapper?.classList.toggle('is-dependency-disabled', childSelect.disabled);
        wrapper?.classList.toggle('is-dependency-empty', parentOptionId !== '' && visibleCount === 0);
        const help = wrapper?.querySelector('[data-dependency-help]');
        if (help) help.textContent = parentOptionId === '' ? 'Prvo izaberi povezanu roditeljsku opciju.' : (visibleCount ? 'Prikazane su samo povezane opcije.' : 'Za ovaj izbor nema povezanih opcija.');
        const detail = panel?.querySelector(`[data-spec-detail-for="${childSelect.dataset.specFieldId}"]`);
        if (detail) detail.disabled = childSelect.disabled;
        return !selectedStillValid;
    };

    const refreshDependencies = (panel) => {
        if (!panel) return;
        const dependentSelects = Array.from(panel.querySelectorAll('select[data-parent-field-id]:not([data-parent-field-id=""])'));
        for (let pass = 0; pass < dependentSelects.length + 1; pass += 1) {
            let changed = false;
            dependentSelects.forEach((select) => { if (refreshChild(select)) changed = true; });
            if (!changed) break;
        }
    };

    const applyDefaults = (panel) => {
        if (!panel) return;
        panel.querySelectorAll('[data-spec-field-id]').forEach((input) => {
            if ((input.value ?? '') === '' && input.dataset.defaultValue) input.value = input.dataset.defaultValue;
        });
        panel.querySelectorAll('[data-spec-detail-for]').forEach((input) => {
            if ((input.value ?? '') === '' && input.dataset.defaultDetail) input.value = input.dataset.defaultDetail;
        });
    };

    const updateSpecificationPanel = (applyTypeDefaults = false) => {
        root?.querySelectorAll('[data-spec-panel]').forEach((panel) => {
            const active = panel.dataset.specPanel === typeSelect?.value;
            panel.hidden = !active;
            panel.querySelectorAll('input,select,textarea').forEach((input) => input.disabled = !active);
            if (active) {
                if (applyTypeDefaults) applyDefaults(panel);
                refreshDependencies(panel);
            }
        });
        const empty = root?.querySelector('[data-no-spec]');
        if (empty) empty.hidden = !!typeSelect?.value;
        const option = activeTypeOption();
        if (nameHelp) nameHelp.textContent = option?.value ? `Šablon: ${option.dataset.nameTemplate || 'podrazumevani šablon'}` : 'Izaberi tip artikla da vidiš šablon naziva.';
        const categoryName = option?.dataset.categoryName || '';
        if (typeCategoryHelp) typeCategoryHelp.textContent = option?.value ? (categoryName ? `Automatska kategorija: ${categoryName}.` : 'Za ovaj tip još nije povezana kategorija.') : 'Kategorija će biti dodeljena automatski prema tipu artikla.';
        if (autoCategoryName) autoCategoryName.textContent = categoryName || (option?.value ? 'Kategorija nije povezana' : 'Biće određena prema izabranom tipu artikla');
        if (applyTypeDefaults && root?.dataset.productExists !== '1' && statusSelect && option?.dataset.defaultStatus) statusSelect.value = option.dataset.defaultStatus;
        refreshCompleteness();
    };

    const coreFilled = (core) => {
        if (core === 'brand') return !!brandSelect?.value;
        if (core === 'line') return !!lineSelect?.value;
        if (core === 'model') return (modelInput?.value || '').trim() !== '';
        if (core === 'categories') return !!activeTypeOption()?.dataset.categoryId;
        if (core === 'description') return (form?.querySelector('[name="description"]')?.value || '').trim() !== '';
        if (core === 'price') {
            const value = Number(form?.querySelector('[name="price_amount"]')?.value || 0);
            return Number.isFinite(value) && value > 0;
        }
        return true;
    };

    function refreshCompleteness() {
        const panel = activePanel();
        if (!panel || !activeTypeOption()?.value) {
            if (completenessPercent) completenessPercent.textContent = '100%';
            if (completenessBar) completenessBar.style.width = '100%';
            if (completenessMessage) completenessMessage.textContent = 'Tip artikla nema aktivan šablon.';
            return;
        }
        let total = 0;
        let earned = 0;
        const missing = [];
        const requiredCore = (activeTypeOption()?.dataset.requiredCore || '').split(',').filter(Boolean);
        requiredCore.forEach((core) => {
            total += 2;
            if (coreFilled(core)) earned += 2;
            else missing.push({brand:'Brend',line:'Linija proizvoda',model:'Model proizvoda',categories:'Kategorija',description:'Opis',price:'Cena'}[core] || core);
        });
        panel.querySelectorAll('[data-spec-field-wrapper]').forEach((wrapper) => {
            const collapsedOptional = wrapper.dataset.specOptional === '1' && wrapper.hidden;
            if ((wrapper.hidden && !collapsedOptional) || wrapper.classList.contains('is-dependency-empty')) return;
            const input = wrapper.querySelector('[data-spec-field-id]');
            if (!input || input.disabled) return;
            const weight = Math.max(1, Number(wrapper.dataset.completenessWeight || 1));
            total += weight;
            if ((input.value ?? '') !== '') earned += weight;
            else missing.push(wrapper.querySelector('label > span')?.textContent?.replace('*','').trim() || 'Specifikacija');
        });
        const percent = total > 0 ? Math.max(0, Math.min(100, Math.round((earned / total) * 100))) : 100;
        const minimum = Number(activeTypeOption()?.dataset.minCompleteness || 0);
        if (completenessPercent) completenessPercent.textContent = `${percent}%`;
        if (completenessBar) completenessBar.style.width = `${percent}%`;
        if (completenessMessage) completenessMessage.textContent = missing.length ? `Nedostaje: ${missing.slice(0, 6).join(', ')}${missing.length > 6 ? '…' : ''}. Minimum za aktivaciju: ${minimum}%.` : 'Svi podaci šablona su popunjeni.';
        root?.querySelector('[data-completeness-card]')?.classList.toggle('is-below-minimum', percent < minimum);
    }

    const previewName = async () => {
        if (!form || !typeSelect?.value || !nameInput) return;
        const button = root?.querySelector('[data-name-preview]');
        button?.setAttribute('disabled', 'disabled');
        try {
            const requestData = new FormData(form);
            requestData.delete('_method');
            const response = await fetch(root.dataset.namePreviewUrl, {method:'POST', body:requestData, headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}});
            const payload = await response.json();
            if (!response.ok) throw new Error(payload?.message || 'Naziv nije mogao biti formiran.');
            if (payload.name) nameInput.value = payload.name;
        } catch (error) {
            window.alert(error.message || 'Naziv nije mogao biti formiran.');
        } finally {
            button?.removeAttribute('disabled');
        }
    };

    const refreshPublicationMode = () => {
        const status = statusSelect?.value || root?.dataset.initialProductStatus || 'draft';
        const active = status === 'active';
        if (priceInput) priceInput.min = active ? '0.01' : '0';
        if (publicationGuidance) publicationGuidance.textContent = active
            ? 'Aktivacija zahteva kompletne podatke i cenu vecu od nule.'
            : 'Za nepotpun unos koristi Sa\u010duvaj kao nacrt. Standardno cuvanje zadrzava puna pravila validacije.';
    };

    statusSelect?.addEventListener('change', refreshPublicationMode);
    brandSelect?.addEventListener('change', updateProductLines);
    lineSelect?.addEventListener('change', refreshCompleteness);
    typeSelect?.addEventListener('change', () => { updateBrands(); updateProductLines(); updateSpecificationPanel(true); });
    root?.querySelector('[data-name-preview]')?.addEventListener('click', previewName);
    form?.querySelectorAll('input,select,textarea').forEach((input) => input.addEventListener(input.matches('input[type="text"],input[type="number"],textarea') ? 'input' : 'change', refreshCompleteness));
    root?.querySelectorAll('[data-spec-field-id]').forEach((input) => input.addEventListener('change', () => { refreshDependencies(input.closest('[data-spec-panel]')); refreshCompleteness(); }));

    refreshPublicationMode();
    updateBrands();
    updateProductLines();
    updateSpecificationPanel(false);
    refreshCompleteness();
})();
</script>
@endpush

@push('scripts')
<script src="{{ asset('assets/js/product-media-manager.js') }}?v={{ config('app.version') }}" defer></script>
@endpush
