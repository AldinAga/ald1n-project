@php
$specValues=$variant?->specificationValues?->mapWithKeys(fn($value)=>[$value->field_id=>$value->value_text ?? ($value->value_number !== null && $value->field?->requiresWholeGigabytes() ? (int)$value->value_number : $value->value_number) ?? ($value->value_boolean===null?null:(int)$value->value_boolean)])->all() ?? [];
$specDetails=$variant?->specificationValues?->mapWithKeys(fn($value)=>[$value->field_id=>$value->value_detail])->all() ?? [];
$specStructured=$variant?->specificationValues?->mapWithKeys(fn($value)=>[$value->field_id=>is_array($value->value_json)?$value->value_json:[]])->all() ?? [];
@endphp
<div class="field-grid">
<label><span>SKU *</span><input name="sku" required maxlength="100" value="{{ old('sku',$variant?->sku) }}" placeholder="{{ $product->sku }}-16-512"></label>
<label><span>Naziv varijante</span><input name="name" maxlength="190" value="{{ old('name',$variant?->name) }}" placeholder="Automatski iz specifikacija"></label>
<label><span>Prodajna cena *</span><input type="number" step="0.01" min="0" name="price_amount" required value="{{ old('price_amount',$variant?->price_amount ?? $product->price_amount) }}"></label>
<label><span>Valuta *</span><select name="price_currency"><option value="RSD" @selected(old('price_currency',$variant?->price_currency ?? $product->price_currency)==='RSD')>RSD</option><option value="EUR" @selected(old('price_currency',$variant?->price_currency ?? $product->price_currency)==='EUR')>EUR</option></select></label>
<label><span>Nabavna cena RSD</span><input type="number" step="0.01" min="0" name="purchase_price_rsd" value="{{ old('purchase_price_rsd',$variant?->purchase_price_rsd) }}"></label>
<label><span>Ručna provizija EUR</span><input type="number" step="0.01" min="0" name="manual_commission_eur" value="{{ old('manual_commission_eur',$variant?->manual_commission_eur) }}"></label>
@if(!$variant)<label><span>Početni lager *</span><input type="number" min="0" name="stock_quantity" required value="{{ old('stock_quantity',0) }}"></label>@else<label><span>Trenutni lager</span><input value="{{ $variant->stock_quantity }}" disabled><small class="muted">Menja se kroz korekciju lagera.</small></label>@endif
<label><span>Prag niskog lagera *</span><input type="number" min="0" name="low_stock_threshold" required value="{{ old('low_stock_threshold',$variant?->low_stock_threshold ?? 1) }}"></label>
<label><span>Status *</span><select name="status"><option value="draft" @selected(old('status',$variant?->status ?? 'draft')==='draft')>Nacrt</option><option value="active" @selected(old('status',$variant?->status)==='active')>Aktivna</option><option value="inactive" @selected(old('status',$variant?->status)==='inactive')>Neaktivna</option></select></label>
<label><span>Garantno pravilo</span><select name="warranty_rule_id"><option value="">Nasledi od proizvoda</option>@foreach($warrantyRules as $rule)<option value="{{ $rule->id }}" @selected((int)old('warranty_rule_id',$variant?->warranty_rule_id)===$rule->id)>{{ $rule->name }}</option>@endforeach</select></label>
<label><span>Redosled</span><input type="number" min="0" name="sort_order" value="{{ old('sort_order',$variant?->sort_order ?? 0) }}"></label>
<label class="check-card"><input type="checkbox" name="is_default" value="1" @checked(old('is_default',$variant?->is_default ?? false))><span>Podrazumevana varijanta</span></label>
</div>
@if($product->type)
<h3>Specifikacije varijante</h3><div class="field-grid">
@foreach($product->type->fields as $field)
@continue($field->isDerivedStorageTotalField())
@php
$value=old('specs.'.$field->id,$specValues[$field->id]??'');
$isRepeatableStorage=$field->isRepeatableStorageField() && !$product->type->fields->contains(fn($candidate)=>(int)$candidate->parent_field_id===(int)$field->id);
$storageTotalField=$isRepeatableStorage ? $product->type->fields->first(fn($candidate)=>$candidate->isStorageTotalField() && (int)$candidate->storage_source_field_id===(int)$field->id) : null;
$storageRows=old('spec_structured.'.$field->id,$specStructured[$field->id]??null);
if($isRepeatableStorage && (!is_array($storageRows)||$storageRows===[])){
    $storageRows=[];
    foreach(preg_split('/\s*\+\s*/u',trim((string)$value),-1,PREG_SPLIT_NO_EMPTY)?:[] as $storedDisk){
        preg_match('/^(.*?)(?:\s+(\d+)\s*GB)?$/iu',trim($storedDisk),$storedMatch);
        $storageRows[]=['type'=>trim((string)($storedMatch[1]??$storedDisk)),'capacity_gb'=>($storedMatch[2]??'')!==''?(int)$storedMatch[2]:null];
    }
}
if($isRepeatableStorage && $storageRows===[]) $storageRows=[['type'=>'','capacity_gb'=>null]];
$hasStorageCapacity=$isRepeatableStorage && collect($storageRows)->contains(fn($row)=>isset($row['capacity_gb'])&&$row['capacity_gb']!==''&&$row['capacity_gb']!==null);
$storageCalculatedTotal=$isRepeatableStorage ? collect($storageRows)->sum(fn($row)=>max(0,(int)($row['capacity_gb']??0))) : 0;
$storageStoredTotal=$storageTotalField ? old('specs.'.$storageTotalField->id,$specValues[$storageTotalField->id]??0) : 0;
$storageTotalValue=$hasStorageCapacity?$storageCalculatedTotal:max(0,(int)$storageStoredTotal);
@endphp
@if($isRepeatableStorage)
<div class="field-span-2 repeatable-storage-field variant-storage-field" data-repeatable-storage data-storage-initial-total="{{ $storageTotalValue }}">
    <div class="repeatable-storage-heading"><span>{{ $field->name }}</span><button class="button button-small button-ghost" type="button" data-repeatable-add><x-icon name="plus-circle" size="16" /> Dodaj još jedan disk</button></div>
    <input type="hidden" name="specs[{{ $field->id }}]" value="{{ $value }}" data-repeatable-value>
    <div class="repeatable-storage-list" data-repeatable-list>
        @foreach($storageRows as $storageRow)
        @php($storageType=trim((string)($storageRow['type']??'')))
        @php($storageCapacity=($storageRow['capacity_gb']??'')===null?'':(string)($storageRow['capacity_gb']??''))
        <div class="repeatable-storage-row" data-repeatable-row>
            <label class="repeatable-storage-type"><span>Tip diska</span>
                @if($field->data_type==='select')<select name="spec_lists[{{ $field->id }}][]" data-repeatable-input><option value="">Izaberi disk</option>@foreach($field->options->where('status','active') as $option)<option value="{{ $option->value }}" @selected($storageType===(string)$option->value)>{{ $option->label }}</option>@endforeach</select>
                @else<input name="spec_lists[{{ $field->id }}][]" maxlength="255" value="{{ $storageType }}" data-repeatable-input>@endif
            </label>
            <label class="repeatable-storage-capacity"><span>Kapacitet (GB)</span><input name="spec_capacities[{{ $field->id }}][]" type="number" min="0" max="10000000" step="1" inputmode="numeric" value="{{ $storageCapacity }}" data-repeatable-capacity></label>
            <button class="repeatable-remove" type="button" data-repeatable-remove aria-label="Ukloni disk"><x-icon name="x" size="17" /></button>
        </div>
        @endforeach
    </div>
    <template data-repeatable-template><div class="repeatable-storage-row" data-repeatable-row>
        <label class="repeatable-storage-type"><span>Tip diska</span>
            @if($field->data_type==='select')<select name="spec_lists[{{ $field->id }}][]" data-repeatable-input><option value="">Izaberi disk</option>@foreach($field->options->where('status','active') as $option)<option value="{{ $option->value }}">{{ $option->label }}</option>@endforeach</select>
            @else<input name="spec_lists[{{ $field->id }}][]" maxlength="255" data-repeatable-input>@endif
        </label>
        <label class="repeatable-storage-capacity"><span>Kapacitet (GB)</span><input name="spec_capacities[{{ $field->id }}][]" type="number" min="0" max="10000000" step="1" inputmode="numeric" data-repeatable-capacity></label>
        <button class="repeatable-remove" type="button" data-repeatable-remove aria-label="Ukloni disk"><x-icon name="x" size="17" /></button>
    </div></template>
    @if($storageTotalField)<div class="storage-total-card"><label><span>Ukupan kapacitet diskova (GB)</span><input type="number" value="{{ $storageTotalValue }}" readonly data-storage-total-display></label><input type="hidden" name="specs[{{ $storageTotalField->id }}]" value="{{ $storageTotalValue }}" data-storage-total-value><small class="muted">Automatski zbir svih diskova ove varijante.</small></div>@endif
</div>
@else
<label data-variant-field-wrap="{{ $field->id }}"><span>{{ $field->name }} @if($field->unit)({{ $field->unit }})@endif</span>
@if($field->data_type==='select')<select name="specs[{{ $field->id }}]" data-variant-spec-field="{{ $field->id }}" data-parent-field="{{ $field->parent_field_id }}"><option value="">Nasledi / nije razlika</option>@foreach($field->options->where('status','active') as $option)<option value="{{ $option->value }}" data-option-id="{{ $option->id }}" data-parent-option-ids="{{ $option->parentOptions->pluck('id')->implode(',') }}" @selected((string)$value===(string)$option->value)>{{ $option->label }}</option>@endforeach</select>
@elseif($field->data_type==='boolean')<select name="specs[{{ $field->id }}]"><option value="">Nasledi / nije razlika</option><option value="1" @selected((string)$value==='1')>Da</option><option value="0" @selected((string)$value==='0')>Ne</option></select>
@else<input name="specs[{{ $field->id }}]" type="{{ in_array($field->data_type,['integer','decimal'])?'number':'text' }}" step="{{ $field->requiresWholeGigabytes() ? '1' : ($field->data_type==='decimal'?'0.0001':'1') }}" inputmode="{{ $field->requiresWholeGigabytes() ? 'numeric' : 'decimal' }}" value="{{ $value }}">@endif
@if($field->detail_input_enabled)<input name="spec_details[{{ $field->id }}]" maxlength="500" value="{{ old('spec_details.'.$field->id,$specDetails[$field->id]??'') }}" placeholder="{{ $field->detail_placeholder ?: 'Tačan model / detalj' }}">@endif
</label>
@endif
@endforeach
</div>
@endif
