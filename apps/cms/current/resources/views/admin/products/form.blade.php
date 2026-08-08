@extends('layouts.app')
@section('title', $product->exists ? 'Izmeni artikal' : 'Dodaj artikal')
@section('content')
<div data-product-edit-ready="1" data-product-exists="{{ $product->exists ? '1' : '0' }}" data-name-preview-url="{{ route('admin.products.name-preview') }}">
    <a class="back-link" href="{{ route('catalog.index') }}">← Nazad na artikle</a>
    <div class="page-heading">
        <div>
            <span class="eyebrow">Administracija kataloga</span>
            <h1>{{ $product->exists ? 'Izmeni artikal' : 'Dodaj novi artikal' }}</h1>
            <p>{{ $product->exists ? $product->name.' · '.$product->sku : 'Naziv, fotografije i specifikacije su sada na vrhu forme.' }}</p>
        </div>
        @if($product->exists)
            <div class="page-heading-actions">
                <a class="button button-ghost" href="{{ route('admin.products.variants.index',$product) }}">Varijante @if($product->variants_enabled)({{ $product->variants()->count() }})@endif</a>
                <a class="button button-ghost" href="{{ route('admin.products.clone',$product) }}">Kloniraj artikal</a>
                @if($product->type?->auto_name_enabled || $product->type?->name_template)<button class="button button-ghost" type="submit" form="regenerate-name-form">Regeneriši naziv</button>@endif
                @can('catalog.manage_images')<a class="button button-ghost" href="{{ route('admin.products.images.index',$product) }}">Uredi slike</a>@endcan
            </div>
        @endif
    </div>

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
                <section class="panel form-section form-section-priority">
                    <div class="form-section-number">1</div>
                    <h2>Naziv artikla</h2>
                    <div class="field-grid">
                        <div class="field-span-2 product-name-editor">
                            <label><span>Naziv artikla</span><input name="name" maxlength="190" value="{{ old('name',$product->name) }}" data-product-name></label>
                            <div class="inline-actions">
                                <button class="button button-ghost button-small" type="button" data-name-preview>Predloži naziv</button>
                                <label class="check-card compact"><input type="checkbox" name="regenerate_name" value="1" @checked(old('regenerate_name'))><span>Generiši pri čuvanju</span></label>
                            </div>
                            <small class="muted" data-name-template-help>Izaberi tip artikla u delu Specifikacije da vidiš šablon naziva.</small>
                        </div>
                        @if($product->exists)
                            <label><span>SKU *</span><input name="sku" required maxlength="100" value="{{ old('sku',$product->sku) }}"></label>
                            <label class="check-card"><input type="checkbox" name="regenerate_sku" value="1" @checked(old('regenerate_sku'))><span>Automatski generiši novi SKU</span></label>
                        @endif
                    </div>
                </section>

                <section class="panel form-section form-section-priority product-media-section">
                    <div class="form-section-number">2</div>
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

                <section class="panel form-section form-section-priority">
                    <div class="form-section-number">3</div>
                    <h2>Specifikacije</h2>
                    <p class="muted">Prvo izaberi tip artikla. Za disk možeš dodati više stavki, na primer SSD + SSD + HDD.</p>
                    <label class="specification-type-selector"><span>Tip artikla</span><select name="product_type_id" data-product-type><option value="">Bez tipa</option>@foreach($types as $type)<option value="{{ $type->id }}" data-auto-name="{{ $type->auto_name_enabled ? '1' : '0' }}" data-name-template="{{ $type->name_template }}" data-min-completeness="{{ $type->minimum_completeness_percent ?? 0 }}" data-default-status="{{ $type->default_product_status ?? 'draft' }}" data-required-core="{{ implode(',',(array)($type->required_core_fields_json??[])) }}" data-category-id="{{ $type->category_id }}" data-category-name="{{ $type->category?->name }}" @selected((int)old('product_type_id',$product->product_type_id)===$type->id)>{{ $type->name }}</option>@endforeach</select><small class="muted" data-type-category-help>Kategorija će biti dodeljena automatski prema tipu artikla.</small></label>
                    @foreach($types as $type)
                        <div class="spec-panel" data-spec-panel="{{ $type->id }}" @if((int)old('product_type_id',$product->product_type_id)!==$type->id) hidden @endif>
                            <div class="field-grid">
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
                                    @endphp
                                    <div class="spec-field-group {{ $isRepeatableStorage ? 'field-span-2' : '' }}" data-spec-field-wrapper data-parent-field-id="{{ $field->parent_field_id }}" data-completeness-weight="{{ $field->pivot?->completeness_weight ?? 1 }}" data-required-field="{{ $field->pivot?->is_required ? '1' : '0' }}">
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

                <section class="panel form-section">
                    <h2>Klasifikacija artikla</h2>
                    <div class="field-grid">
                        <label><span>Brend</span><select name="brand_id" data-brand-select><option value="">Bez brenda</option>@foreach($brands as $brand)<option value="{{ $brand->id }}" @selected((int)old('brand_id',$product->brand_id)===$brand->id)>{{ $brand->name }}</option>@endforeach</select></label>
                        <label><span>Linija proizvoda</span><select name="product_line_id" data-line-select><option value="">Bez linije</option>@foreach($lines as $line)<option value="{{ $line->id }}" data-brand-id="{{ $line->brand_id }}" @selected((int)old('product_line_id',$product->product_line_id)===$line->id)>{{ $line->name }}</option>@endforeach</select><small class="muted" data-line-help>Prvo izaberi brend.</small></label>
                        <label class="field-span-2 product-model-field"><span>Model proizvoda</span><input name="model_name" maxlength="190" value="{{ old('model_name',$product->model_name) }}" placeholder="npr. 830 G8" data-product-model><small class="muted">Tačna oznaka modela ulazi u automatski naziv artikla, npr. HP EliteBook 830 G8.</small></label>
                        <div class="field-span-2 auto-category-card"><span>Automatska kategorija</span><strong data-auto-category-name>Biće određena prema izabranom tipu artikla</strong><small class="muted">Kategorija se više ne bira ručno. Menja se u podešavanjima konkretnog tipa artikla.</small></div>
                    </div>
                </section>

                <section class="panel form-section">
                    <h2>Opis</h2>
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
                <section class="panel form-section sticky-card">
                    <h2>Cena i lager</h2>
                    <label><span>Cena *</span><input name="price_amount" type="number" step="0.01" min="0" required value="{{ old('price_amount',$product->price_amount) }}"></label>
                    <label><span>Valuta</span><select name="price_currency"><option @selected(old('price_currency',$product->price_currency)==='EUR')>EUR</option><option @selected(old('price_currency',$product->price_currency)==='RSD')>RSD</option></select></label>
                    <label><span>Nabavna cena RSD</span><input name="purchase_price_rsd" type="number" step="0.01" min="0" value="{{ old('purchase_price_rsd',$product->purchase_price_rsd) }}"><small>Koristi se za obračun marže i snapshotuje se pri prodaji.</small></label>
                    <label><span>Ručna provizija EUR</span><input name="manual_commission_eur" type="number" step="0.01" min="0" value="{{ old('manual_commission_eur',$product->manual_commission_eur) }}"></label>
                    @if($product->exists && $product->variants_enabled)
                        <input type="hidden" name="stock_quantity" value="{{ $product->stock_quantity }}"><input type="hidden" name="low_stock_threshold" value="{{ $product->low_stock_threshold }}">
                        <label><span>Ukupan lager varijanti</span><input type="number" value="{{ $product->stock_quantity }}" disabled><small class="muted">Zbir aktivnih varijanti. Menja se na ekranu Varijante.</small></label>
                        <label><span>Ukupan prag</span><input type="number" value="{{ $product->low_stock_threshold }}" disabled><small class="muted">Zbir pragova aktivnih varijanti.</small></label>
                    @elseif(!$product->exists || auth()->user()->can('stock.adjust'))
                        <label><span>Količina</span><input name="stock_quantity" type="number" min="0" required value="{{ old('stock_quantity',$product->stock_quantity??0) }}"></label>
                        <label><span>Prag niskog lagera</span><input name="low_stock_threshold" type="number" min="0" required value="{{ old('low_stock_threshold',$product->low_stock_threshold??1) }}"></label>
                    @else
                        <input type="hidden" name="stock_quantity" value="{{ $product->stock_quantity }}"><input type="hidden" name="low_stock_threshold" value="{{ $product->low_stock_threshold }}">
                        <label><span>Količina</span><input type="number" value="{{ $product->stock_quantity }}" disabled><small class="muted">Za promenu je potrebna dozvola Korekcija lagera.</small></label>
                        <label><span>Prag niskog lagera</span><input type="number" value="{{ $product->low_stock_threshold }}" disabled></label>
                    @endif
                    <label><span>Status</span><select name="status">@foreach(['draft'=>'Nacrt','active'=>'Aktivan','inactive'=>'Neaktivan'] as $value=>$label)<option value="{{ $value }}" @selected(old('status',$product->status)===$value)>{{ $label }}</option>@endforeach</select></label>
                    <button class="button button-primary button-large" type="submit">{{ $product->exists ? 'Sačuvaj izmene' : 'Kreiraj artikal' }}</button>
                </section>
                @if($product->exists)
                    <section class="panel danger-zone product-danger-zone">
                        <h2>Arhiva i trajno brisanje</h2>
                        <p class="muted">Arhiviranje skriva artikal, ali čuva sve podatke i poslovnu istoriju.</p>
                        @if($product->deleted_at)
                            <button form="restore-product" class="button button-secondary" type="submit"><x-icon name="refresh" size="18" /> Vrati artikal</button>
                        @else
                            <button form="archive-product" class="button button-warning" type="submit" data-confirm="Arhivirati artikal {{ $product->sku }}?"><x-icon name="archive" size="18" /> Arhiviraj artikal</button>
                        @endif

                        <details class="product-purge-details">
                            <summary>Trajno obriši artikal</summary>
                            <div class="product-purge-content">
                                <p>Trajno brisanje uklanja artikal, specifikacije, varijante i zapise galerije. Ovu radnju nije moguće poništiti.</p>
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
                    </section>
                @endif
            </aside>
        </div>
    </form>

    @if($product->exists)
        @if($product->deleted_at)<form id="restore-product" method="post" action="{{ route('admin.products.restore',$product) }}">@csrf</form>@else<form id="archive-product" method="post" action="{{ route('admin.products.archive',$product) }}">@csrf @method('DELETE')</form>@endif
        <form id="purge-product" method="post" action="{{ route('admin.products.purge',$product) }}">@csrf @method('DELETE')</form>
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
    const completenessPercent = root?.querySelector('[data-completeness-percent]');
    const completenessBar = root?.querySelector('[data-completeness-bar]');
    const completenessMessage = root?.querySelector('[data-completeness-message]');

    const activeTypeOption = () => typeSelect?.selectedOptions?.[0] || null;
    const activePanel = () => root?.querySelector(`[data-spec-panel="${typeSelect?.value || ''}"]`) || null;

    const updateProductLines = () => {
        if (!lineSelect) return;
        const brandId = brandSelect?.value || '';
        let visibleCount = 0;
        Array.from(lineSelect.options).forEach((option, index) => {
            if (index === 0) return;
            const visible = brandId !== '' && option.dataset.brandId === brandId;
            option.hidden = !visible;
            option.disabled = !visible;
            if (visible) visibleCount += 1;
        });
        const selected = lineSelect.selectedOptions[0];
        if (selected && selected.value !== '' && selected.disabled) lineSelect.value = '';
        lineSelect.disabled = brandId === '' || visibleCount === 0;
        if (lineHelp) lineHelp.textContent = brandId === '' ? 'Prvo izaberi brend.' : (visibleCount ? 'Prikazane su samo linije izabranog brenda.' : 'Za ovaj brend nema aktivnih linija.');
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
        if (core === 'price') return (form?.querySelector('[name="price_amount"]')?.value || '') !== '';
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
            if (wrapper.hidden || wrapper.classList.contains('is-dependency-empty')) return;
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

    brandSelect?.addEventListener('change', updateProductLines);
    lineSelect?.addEventListener('change', refreshCompleteness);
    typeSelect?.addEventListener('change', () => updateSpecificationPanel(true));
    root?.querySelector('[data-name-preview]')?.addEventListener('click', previewName);
    form?.querySelectorAll('input,select,textarea').forEach((input) => input.addEventListener(input.matches('input[type="text"],input[type="number"],textarea') ? 'input' : 'change', refreshCompleteness));
    root?.querySelectorAll('[data-spec-field-id]').forEach((input) => input.addEventListener('change', () => { refreshDependencies(input.closest('[data-spec-panel]')); refreshCompleteness(); }));

    updateProductLines();
    updateSpecificationPanel(false);
    refreshCompleteness();
})();
</script>
@endpush

@push('scripts')
<script src="{{ asset('assets/js/product-media-manager.js') }}?v={{ config('app.version') }}" defer></script>
@endpush
