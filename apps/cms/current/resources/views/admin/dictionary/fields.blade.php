@php
    $isCompactProductType = $resource === 'product-types' && ($productTypeCompact ?? false);
    $fields = collect($fields ?? []);
    $orderedFields = collect($orderedFields ?? $fields);
@endphp
<div class="field-grid">
    <label><span>Naziv *</span><input name="name" required maxlength="120" value="{{ old('name',$item?->name) }}"></label>
    <label><span>Slug</span><input name="slug" maxlength="140" value="{{ old('slug',$item?->slug) }}" placeholder="automatski"></label>
    <label><span>Status</span><select name="status"><option value="active" @selected(old('status',$item?->status??'active')==='active')>Aktivno</option><option value="inactive" @selected(old('status',$item?->status)==='inactive')>Neaktivno</option></select></label>
    <label><span>Redosled</span><input name="sort_order" type="number" min="0" step="1" value="{{ old('sort_order',$item?->sort_order??0) }}" data-sort-order-input></label>

    @if($resource==='categories')
        <label><span>Nadređena kategorija</span><select name="parent_id"><option value="">Bez roditelja</option>@foreach($categories as $category)@if(!$item || $category->id!==$item->id)<option value="{{ $category->id }}" @selected((int)old('parent_id',$item?->parent_id)===$category->id)>{{ $category->name }}</option>@endif @endforeach</select></label>
        <label class="field-span-2"><span>Opis</span><textarea name="description">{{ old('description',$item?->description) }}</textarea></label>
    @endif

    @if($resource==='brands')
        <label><span>Web sajt</span><input name="website_url" value="{{ old('website_url',$item?->website_url) }}"></label>
        <label class="field-span-2"><span>Opis</span><textarea name="description">{{ old('description',$item?->description) }}</textarea></label>
    @endif

    @if($resource==='product-lines')
        <label><span>Brend *</span><select name="brand_id" required><option value="">Izaberi</option>@foreach($brands as $brand)<option value="{{ $brand->id }}" @selected((int)old('brand_id',$item?->brand_id)===$brand->id)>{{ $brand->name }}</option>@endforeach</select></label>
    @endif

    @if($resource==='product-types')
        <div class="field-span-2 automatic-category-card">
            <div><span class="field-label">Automatska sistemska kategorija</span><strong>{{ $item?->category?->name ?? 'Biće kreirana pri čuvanju' }}</strong></div>
            <small class="muted">Kategorija se više ne bira ručno. Sistem je automatski pronalazi ili kreira iz naziva tipa i zatim je dodeljuje svim artiklima tog tipa.</small>
        </div>

        @unless($isCompactProductType)
            <label class="field-span-2"><span>Opis</span><textarea name="description">{{ old('description',$item?->description) }}</textarea></label>
            <label><span>Podrazumevani status novog artikla</span><select name="default_product_status">@foreach(['draft'=>'Nacrt','active'=>'Aktivan','inactive'=>'Neaktivan'] as $value=>$label)<option value="{{ $value }}" @selected(old('default_product_status',$item?->default_product_status??'draft')===$value)>{{ $label }}</option>@endforeach</select></label>
            <label><span>Minimum kompletnosti za aktivaciju</span><input type="number" name="minimum_completeness_percent" min="0" max="100" step="1" value="{{ old('minimum_completeness_percent',$item?->minimum_completeness_percent??0) }}"><small class="muted">Artikal ispod ovog procenta ne može postati aktivan.</small></label>
            <label class="check-card"><input type="checkbox" name="auto_name_enabled" value="1" @checked(old('auto_name_enabled',$item?->auto_name_enabled))><span>Automatski formiraj naziv kada je polje prazno</span></label>
            <label class="field-span-2"><span>Šablon naziva</span><input name="name_template" maxlength="500" value="{{ old('name_template',$item?->name_template) }}" placeholder="{brand} {line} {model} {cpu_family} {cpu_detail} {ram} {storage}"><small class="muted">Koristi osnovne promenljive {brand}, {line}, {model}, {type}, {sku} i slug svakog dodeljenog polja.</small></label>

            <div class="field-span-2 template-core-fields">
                <strong>Obavezni osnovni podaci</strong>
                <div class="check-grid">
                    @php($requiredCore=(array)old('required_core_fields',$item?->required_core_fields_json??[]))
                    @foreach(['brand'=>'Brend','line'=>'Linija proizvoda','model'=>'Model proizvoda','description'=>'Opis','price'=>'Cena'] as $value=>$label)
                        <label class="check-card"><input type="checkbox" name="required_core_fields[]" value="{{ $value }}" @checked(in_array($value,$requiredCore,true))><span>{{ $label }}</span></label>
                    @endforeach
                </div>
                <small class="muted">Kategorija se ne bira ručno i zato nije zaseban uslov kompletnosti.</small>
            </div>

            <div class="field-span-2 template-field-config">
                <div class="dictionary-section-heading"><div><strong>Specifikacije za {{ $item?->name }}</strong><p class="muted">Uključi polje i podesi njegov prikaz. Redosled kartica menja se u režimu „Uredi raspored“.</p></div></div>
                <div class="template-field-table dictionary-sortable" data-dictionary-sortable data-reorder-url="{{ $item ? route('admin.dictionary.product-type.fields.reorder',$item) : '' }}" data-sort-scope="product-type-fields">
                    @foreach($orderedFields as $field)
                        @php($assigned=$item?->fields?->firstWhere('id',$field->id))
                        @php($pivot=$assigned?->pivot)
                        <article class="template-field-row dictionary-sort-item {{ $assigned ? 'is-enabled' : 'is-disabled' }}" data-sort-item data-sort-id="{{ $field->id }}">
                            <button class="dictionary-drag-handle" type="button" data-sort-handle aria-label="Prevuci polje {{ $field->name }}"><x-icon name="grip" size="18" /><span>Prevuci</span></button>
                            <label class="check-card template-field-enable"><input type="checkbox" name="field_config[{{ $field->id }}][enabled]" value="1" @checked(old('field_config.'.$field->id.'.enabled',(bool)$assigned))><span><strong>{{ $field->name }}</strong><small>{{ $field->slug }} · {{ $field->data_type }}@if($field->unit) · {{ $field->unit }}@endif</small></span></label>
                            <label><span>Redosled</span><input type="number" min="0" step="1" name="field_config[{{ $field->id }}][sort_order]" value="{{ old('field_config.'.$field->id.'.sort_order',$pivot?->sort_order??(($loop->index+1)*10)) }}" data-sort-order-input></label>
                            <label><span>Težina</span><input type="number" min="1" max="100" step="1" name="field_config[{{ $field->id }}][completeness_weight]" value="{{ old('field_config.'.$field->id.'.completeness_weight',$pivot?->completeness_weight??1) }}"></label>
                            <label><span>Podrazumevana vrednost</span><input name="field_config[{{ $field->id }}][default_value]" value="{{ old('field_config.'.$field->id.'.default_value',$pivot?->default_value) }}"></label>
                            @if($field->detail_input_enabled)<label><span>Podrazumevani detalj</span><input name="field_config[{{ $field->id }}][default_detail]" value="{{ old('field_config.'.$field->id.'.default_detail',$pivot?->default_detail) }}"></label>@endif
                            <div class="template-field-flags"><label><input type="checkbox" name="field_config[{{ $field->id }}][is_required]" value="1" @checked(old('field_config.'.$field->id.'.is_required',$pivot?->is_required))> Obavezno</label><label><input type="checkbox" name="field_config[{{ $field->id }}][is_filterable]" value="1" @checked(old('field_config.'.$field->id.'.is_filterable',$pivot?->is_filterable))> Filter</label><label><input type="checkbox" name="field_config[{{ $field->id }}][show_in_summary]" value="1" @checked(old('field_config.'.$field->id.'.show_in_summary',$pivot?->show_in_summary))> Sažetak</label><label><input type="checkbox" name="field_config[{{ $field->id }}][include_in_name]" value="1" @checked(old('field_config.'.$field->id.'.include_in_name',$pivot?->include_in_name))> Naziv</label></div>
                        </article>
                    @endforeach
                </div>
            </div>
        @endunless
    @endif

    @if($resource==='specification-fields')
        <label><span>Tip podatka</span><select name="data_type" data-spec-data-type>@foreach(['text','integer','decimal','select','boolean'] as $v)<option @selected(old('data_type',$item?->data_type??'text')===$v)>{{ $v }}</option>@endforeach</select></label>
        <label><span>Tip filtera</span><select name="filter_type">@foreach(['none','select','range','boolean','text'] as $v)<option @selected(old('filter_type',$item?->filter_type??'none')===$v)>{{ $v }}</option>@endforeach</select></label>
        <label><span>Jedinica</span><input name="unit" value="{{ old('unit',$item?->unit) }}" placeholder="npr. GB"></label>
        <label><span>Placeholder</span><input name="placeholder" value="{{ old('placeholder',$item?->placeholder) }}"></label>
        @php($numericStep = $item?->requiresWholeGigabytes() ? '1' : '0.0001')
        <label><span>Minimum</span><input type="number" step="{{ $numericStep }}" name="min_value" value="{{ old('min_value',$item?->min_value) }}"></label>
        <label><span>Maksimum</span><input type="number" step="{{ $numericStep }}" name="max_value" value="{{ old('max_value',$item?->max_value) }}"></label>
        <label class="field-span-2" data-select-setting><span>Opcije (jedna po redu)</span><textarea name="options_text" rows="10">{{ old('options_text',$item?->options_text) }}</textarea><small class="muted">Opcije se čuvaju kao strukturirane vrednosti i koriste za filtriranje i korelacije.</small></label>
        <label data-select-setting><span>Zavisi od polja</span><select name="parent_field_id"><option value="">Nema roditeljskog polja</option>@foreach($selectableFields as $parentField)@if(!$item || $parentField->id !== $item->id)<option value="{{ $parentField->id }}" @selected((int)old('parent_field_id',$item?->parent_field_id)===$parentField->id)>{{ $parentField->name }}</option>@endif @endforeach</select></label>
        <label class="check-card" data-select-setting><input type="checkbox" name="detail_input_enabled" value="1" @checked(old('detail_input_enabled',$item?->detail_input_enabled))><span>Prikaži dodatno tekstualno polje</span></label>
        <label data-select-setting><span>Naziv dodatnog polja</span><input name="detail_label" value="{{ old('detail_label',$item?->detail_label) }}" placeholder="npr. Tačan model procesora"></label>
        <label data-select-setting><span>Placeholder dodatnog polja</span><input name="detail_placeholder" value="{{ old('detail_placeholder',$item?->detail_placeholder) }}" placeholder="npr. 1135G7 ili PRO 5625U"></label>
        <label class="field-span-2" data-select-setting><span>Korelacije opcija</span><textarea name="dependency_map_text" rows="8" placeholder="Intel => Intel Core i3 | Intel Core i5&#10;AMD => AMD Ryzen 5 | AMD Ryzen 7">{{ old('dependency_map_text',$item ? ($dependencyMaps[$item->id] ?? '') : '') }}</textarea><small class="muted">Format: Roditeljska opcija =&gt; Zavisna opcija 1 | Zavisna opcija 2.</small></label>
        <label class="field-span-2"><span>Pomoćni tekst</span><input name="help_text" value="{{ old('help_text',$item?->help_text) }}"></label>
    @endif
</div>
