@php
    $isCompactProductType = $resource === 'product-types' && ($productTypeCompact ?? false);
    $fields = collect($fields ?? []);
    $orderedFields = collect($orderedFields ?? $fields);
@endphp
<div class="a1-pc-grid">
    <label class="a1-field"><span class="a1-label">Naziv *</span><input class="a1-input" name="name" required maxlength="120" value="{{ old('name',$item?->name) }}"></label>
    <label class="a1-field"><span class="a1-label">Slug</span><input class="a1-input" name="slug" maxlength="140" value="{{ old('slug',$item?->slug) }}" placeholder="automatski"></label>
    <label class="a1-field"><span class="a1-label">Status</span><select class="a1-select" name="status"><option value="active" @selected(old('status',$item?->status??'active')==='active')>Aktivno</option><option value="inactive" @selected(old('status',$item?->status)==='inactive')>Neaktivno</option></select></label>
    <label class="a1-field"><span class="a1-label">Redosled</span><input class="a1-input" name="sort_order" type="number" min="0" step="1" value="{{ old('sort_order',$item?->sort_order??0) }}" data-sort-order-input></label>

    @if($resource==='categories')
        <label class="a1-field"><span class="a1-label">Nadređena kategorija</span><select class="a1-select" name="parent_id"><option value="">Bez roditelja</option>@foreach($categories as $category)@if(!$item || $category->id!==$item->id)<option value="{{ $category->id }}" @selected((int)old('parent_id',$item?->parent_id)===$category->id)>{{ $category->name }}</option>@endif @endforeach</select></label>
        <label class="a1-field a1-pc-span2"><span class="a1-label">Opis</span><textarea class="a1-textarea" name="description">{{ old('description',$item?->description) }}</textarea></label>
    @endif

    @if($resource==='brands')
        <label class="a1-field"><span class="a1-label">Web sajt</span><input class="a1-input" name="website_url" value="{{ old('website_url',$item?->website_url) }}"></label>
        <label class="a1-field a1-pc-span2"><span class="a1-label">Opis</span><textarea class="a1-textarea" name="description">{{ old('description',$item?->description) }}</textarea></label>
    @endif

    @if($resource==='product-lines')
        <label class="a1-field"><span class="a1-label">Brend *</span><select class="a1-select" name="brand_id" required><option value="">Izaberi</option>@foreach($brands as $brand)<option value="{{ $brand->id }}" @selected((int)old('brand_id',$item?->brand_id)===$brand->id)>{{ $brand->name }}</option>@endforeach</select></label>
    @endif

    @if($resource==='product-types')
        <div class="a1-pc-note a1-pc-span2">
            <span class="a1-label">Automatska sistemska kategorija</span>
            <strong>{{ $item?->category?->name ?? 'Biće kreirana pri čuvanju' }}</strong>
            <small class="a1-help">Kategorija se više ne bira ručno. Sistem je automatski pronalazi ili kreira iz naziva tipa i zatim je dodeljuje svim artiklima tog tipa.</small>
        </div>

        @unless($isCompactProductType)
            <label class="a1-field a1-pc-span2"><span class="a1-label">Opis</span><textarea class="a1-textarea" name="description">{{ old('description',$item?->description) }}</textarea></label>
            <label class="a1-field"><span class="a1-label">Podrazumevani status novog artikla</span><select class="a1-select" name="default_product_status">@foreach(['draft'=>'Nacrt','active'=>'Aktivan','inactive'=>'Neaktivan'] as $value=>$label)<option value="{{ $value }}" @selected(old('default_product_status',$item?->default_product_status??'draft')===$value)>{{ $label }}</option>@endforeach</select></label>
            <label class="a1-field"><span class="a1-label">Minimum kompletnosti za aktivaciju</span><input class="a1-input" type="number" name="minimum_completeness_percent" min="0" max="100" step="1" value="{{ old('minimum_completeness_percent',$item?->minimum_completeness_percent??0) }}"><small class="a1-help">Artikal ispod ovog procenta ne može postati aktivan.</small></label>
            <label class="a1-check"><input class="a1-check__input" type="checkbox" name="auto_name_enabled" value="1" @checked(old('auto_name_enabled',$item?->auto_name_enabled))><span class="a1-check__box" aria-hidden="true"></span><span>Automatski formiraj naziv kada je polje prazno</span></label>
            <label class="a1-field a1-pc-span2"><span class="a1-label">Šablon naziva</span><input class="a1-input" name="name_template" maxlength="500" value="{{ old('name_template',$item?->name_template) }}" placeholder="{brand} {line} {model} {cpu_family} {cpu_detail} {ram} {storage}"><small class="a1-help">Koristi osnovne promenljive {brand}, {line}, {model}, {type}, {sku} i slug svakog dodeljenog polja.</small></label>

            <fieldset class="a1-fieldset a1-pc-span2">
                <legend class="a1-label">Obavezni osnovni podaci</legend>
                <div class="a1-pc-grid">
                    @php($requiredCore=(array)old('required_core_fields',$item?->required_core_fields_json??[]))
                    @foreach(['brand'=>'Brend','line'=>'Linija proizvoda','model'=>'Model proizvoda','description'=>'Opis','price'=>'Cena'] as $value=>$label)
                        <label class="a1-check"><input class="a1-check__input" type="checkbox" name="required_core_fields[]" value="{{ $value }}" @checked(in_array($value,$requiredCore,true))><span class="a1-check__box" aria-hidden="true"></span><span>{{ $label }}</span></label>
                    @endforeach
                </div>
                <small class="a1-help">Kategorija se ne bira ručno i zato nije zaseban uslov kompletnosti.</small>
            </fieldset>

            <div class="a1-pc-span2 a1-stack">
                <div><h3 class="a1-h3">Specifikacije za {{ $item?->name }}</h3><p class="a1-muted">Uključi polje i podesi njegov prikaz. Redosled kartica menja se u režimu „Uredi raspored“.</p></div>
                <div class="a1-pc-fieldrows" data-template-field-table data-dictionary-sortable data-reorder-url="{{ $item ? route('admin.dictionary.product-type.fields.reorder',$item) : '' }}" data-sort-scope="product-type-fields">
                    @foreach($orderedFields as $field)
                        @php($assigned=$item?->fields?->firstWhere('id',$field->id))
                        @php($pivot=$assigned?->pivot)
                        <article class="a1-pc-fieldrow {{ $assigned ? '' : 'is-disabled' }}" data-template-field-row data-sort-item data-sort-id="{{ $field->id }}">
                            <button class="a1-pc-handle" type="button" data-sort-handle aria-label="Prevuci polje {{ $field->name }}"><x-icon name="grip" size="18" /><span>Prevuci</span></button>
                            <label class="a1-check"><input class="a1-check__input" type="checkbox" name="field_config[{{ $field->id }}][enabled]" value="1" @checked(old('field_config.'.$field->id.'.enabled',(bool)$assigned))><span class="a1-check__box" aria-hidden="true"></span><span><strong data-field-name>{{ $field->name }}</strong><small class="a1-help">{{ $field->slug }} · {{ $field->data_type }}@if($field->unit) · {{ $field->unit }}@endif</small></span></label>
                            <label class="a1-field"><span class="a1-label">Redosled</span><input class="a1-input" type="number" min="0" step="1" name="field_config[{{ $field->id }}][sort_order]" value="{{ old('field_config.'.$field->id.'.sort_order',$pivot?->sort_order??(($loop->index+1)*10)) }}" data-sort-order-input></label>
                            <label class="a1-field"><span class="a1-label">Težina</span><input class="a1-input" type="number" min="1" max="100" step="1" name="field_config[{{ $field->id }}][completeness_weight]" value="{{ old('field_config.'.$field->id.'.completeness_weight',$pivot?->completeness_weight??1) }}"></label>
                            <label class="a1-field"><span class="a1-label">Podrazumevana vrednost</span><input class="a1-input" name="field_config[{{ $field->id }}][default_value]" value="{{ old('field_config.'.$field->id.'.default_value',$pivot?->default_value) }}"></label>
                            @if($field->detail_input_enabled)<label class="a1-field"><span class="a1-label">Podrazumevani detalj</span><input class="a1-input" name="field_config[{{ $field->id }}][default_detail]" value="{{ old('field_config.'.$field->id.'.default_detail',$pivot?->default_detail) }}"></label>@endif
                            <div class="a1-pc-flags"><label class="a1-check"><input class="a1-check__input" type="checkbox" name="field_config[{{ $field->id }}][is_required]" value="1" @checked(old('field_config.'.$field->id.'.is_required',$pivot?->is_required))><span class="a1-check__box" aria-hidden="true"></span><span>Obavezno</span></label><label class="a1-check"><input class="a1-check__input" type="checkbox" name="field_config[{{ $field->id }}][is_filterable]" value="1" @checked(old('field_config.'.$field->id.'.is_filterable',$pivot?->is_filterable))><span class="a1-check__box" aria-hidden="true"></span><span>Filter</span></label><label class="a1-check"><input class="a1-check__input" type="checkbox" name="field_config[{{ $field->id }}][show_in_summary]" value="1" @checked(old('field_config.'.$field->id.'.show_in_summary',$pivot?->show_in_summary))><span class="a1-check__box" aria-hidden="true"></span><span>Sažetak</span></label><label class="a1-check"><input class="a1-check__input" type="checkbox" name="field_config[{{ $field->id }}][include_in_name]" value="1" @checked(old('field_config.'.$field->id.'.include_in_name',$pivot?->include_in_name))><span class="a1-check__box" aria-hidden="true"></span><span>Naziv</span></label></div>
                        </article>
                    @endforeach
                </div>
            </div>
        @endunless
    @endif

    @if($resource==='specification-fields')
        <label class="a1-field"><span class="a1-label">Tip podatka</span><select class="a1-select" name="data_type" data-spec-data-type>@foreach(['text','integer','decimal','select','boolean'] as $v)<option @selected(old('data_type',$item?->data_type??'text')===$v)>{{ $v }}</option>@endforeach</select></label>
        <label class="a1-field"><span class="a1-label">Tip filtera</span><select class="a1-select" name="filter_type">@foreach(['none','select','range','boolean','text'] as $v)<option @selected(old('filter_type',$item?->filter_type??'none')===$v)>{{ $v }}</option>@endforeach</select></label>
        <label class="a1-field"><span class="a1-label">Jedinica</span><input class="a1-input" name="unit" value="{{ old('unit',$item?->unit) }}" placeholder="npr. GB"></label>
        <label class="a1-field"><span class="a1-label">Placeholder</span><input class="a1-input" name="placeholder" value="{{ old('placeholder',$item?->placeholder) }}"></label>
        @php($numericStep = $item?->requiresWholeGigabytes() ? '1' : '0.0001')
        <label class="a1-field"><span class="a1-label">Minimum</span><input class="a1-input" type="number" step="{{ $numericStep }}" name="min_value" value="{{ old('min_value',$item?->min_value) }}"></label>
        <label class="a1-field"><span class="a1-label">Maksimum</span><input class="a1-input" type="number" step="{{ $numericStep }}" name="max_value" value="{{ old('max_value',$item?->max_value) }}"></label>
        <label class="a1-field a1-pc-span2" data-select-setting><span class="a1-label">Opcije (jedna po redu)</span><textarea class="a1-textarea" name="options_text" rows="10">{{ old('options_text',$item?->options_text) }}</textarea><small class="a1-help">Opcije se čuvaju kao strukturirane vrednosti i koriste za filtriranje i korelacije.</small></label>
        <label class="a1-field" data-select-setting><span class="a1-label">Zavisi od polja</span><select class="a1-select" name="parent_field_id"><option value="">Nema roditeljskog polja</option>@foreach($selectableFields as $parentField)@if(!$item || $parentField->id !== $item->id)<option value="{{ $parentField->id }}" @selected((int)old('parent_field_id',$item?->parent_field_id)===$parentField->id)>{{ $parentField->name }}</option>@endif @endforeach</select></label>
        <label class="a1-check" data-select-setting><input class="a1-check__input" type="checkbox" name="detail_input_enabled" value="1" @checked(old('detail_input_enabled',$item?->detail_input_enabled))><span class="a1-check__box" aria-hidden="true"></span><span>Prikaži dodatno tekstualno polje</span></label>
        <label class="a1-field" data-select-setting><span class="a1-label">Naziv dodatnog polja</span><input class="a1-input" name="detail_label" value="{{ old('detail_label',$item?->detail_label) }}" placeholder="npr. Tačan model procesora"></label>
        <label class="a1-field" data-select-setting><span class="a1-label">Placeholder dodatnog polja</span><input class="a1-input" name="detail_placeholder" value="{{ old('detail_placeholder',$item?->detail_placeholder) }}" placeholder="npr. 1135G7 ili PRO 5625U"></label>
        <label class="a1-field a1-pc-span2" data-select-setting><span class="a1-label">Korelacije opcija</span><textarea class="a1-textarea" name="dependency_map_text" rows="8" placeholder="Intel => Intel Core i3 | Intel Core i5&#10;AMD => AMD Ryzen 5 | AMD Ryzen 7">{{ old('dependency_map_text',$item ? ($dependencyMaps[$item->id] ?? '') : '') }}</textarea><small class="a1-help">Format: Roditeljska opcija =&gt; Zavisna opcija 1 | Zavisna opcija 2.</small></label>
        <label class="a1-field a1-pc-span2"><span class="a1-label">Pomoćni tekst</span><input class="a1-input" name="help_text" value="{{ old('help_text',$item?->help_text) }}"></label>
    @endif
</div>
