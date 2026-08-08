@foreach($filterFields as $field)
    @php
        $typeIds = $field->productTypes->pluck('id')->implode(',');
        $selectedValue = request('spec_filters.'.$field->id, '');
        $selectedDetail = request('spec_details.'.$field->id, '');
    @endphp
    <div class="filter-spec-group" data-filter-spec-wrapper data-filter-field-id="{{ $field->id }}" data-filter-type-ids="{{ $typeIds }}">
        @if($field->filter_type === 'select')
            <label>
                <span>{{ $field->name }}</span>
                <select name="spec_filters[{{ $field->id }}]" data-filter-spec-field="{{ $field->id }}" data-parent-field-id="{{ $field->parent_field_id }}">
                    <option value="">Sve opcije</option>
                    @foreach($field->options as $option)
                        <option value="{{ $option->value }}" data-option-id="{{ $option->id }}" data-parent-option-ids="{{ $option->parentOptions->pluck('id')->implode(',') }}" @selected((string)$selectedValue === (string)$option->value)>{{ $option->label }}</option>
                    @endforeach
                </select>
                @if($field->parent_field_id)<small class="muted" data-filter-dependency-help>Prvo izaberi povezanu roditeljsku opciju.</small>@endif
            </label>
        @elseif($field->filter_type === 'range')
            <label><span>{{ $field->name }} od @if($field->unit)({{ $field->unit }})@endif</span><input name="spec_min[{{ $field->id }}]" type="number" step="0.0001" value="{{ request('spec_min.'.$field->id) }}"></label>
            <label><span>{{ $field->name }} do @if($field->unit)({{ $field->unit }})@endif</span><input name="spec_max[{{ $field->id }}]" type="number" step="0.0001" value="{{ request('spec_max.'.$field->id) }}"></label>
        @elseif($field->filter_type === 'boolean')
            <label><span>{{ $field->name }}</span><select name="spec_filters[{{ $field->id }}]" data-filter-spec-field="{{ $field->id }}"><option value="">Sve</option><option value="1" @selected((string)$selectedValue === '1')>Da</option><option value="0" @selected((string)$selectedValue === '0')>Ne</option></select></label>
        @else
            <label><span>{{ $field->name }}</span><input name="spec_filters[{{ $field->id }}]" value="{{ $selectedValue }}" placeholder="Pretraži vrednost"></label>
        @endif
        @if($field->detail_input_enabled)
            <label class="filter-spec-detail"><span>{{ $field->detail_label ?: 'Tačan model / detalj' }}</span><input name="spec_details[{{ $field->id }}]" value="{{ $selectedDetail }}" placeholder="{{ $field->detail_placeholder }}"></label>
        @endif
    </div>
@endforeach
