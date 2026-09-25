@extends('layouts.app')
@section('title', $workOrder->work_order_number)
@section('content')
<a class="back-link" href="{{ route('admin.field-operations.index') }}">← Terenske operacije</a>
<div class="page-heading"><div><span class="eyebrow">Radni nalog</span><h1>{{ $workOrder->work_order_number }}</h1><p>{{ $workOrder->action?->case?->case_number }} · {{ $workOrder->action?->action_number }}</p></div><span class="status-badge field-status-{{ $workOrder->status }}">{{ $statuses[$workOrder->status] ?? $workOrder->status }}</span></div>
<div class="settings-grid operational-order-grid">
<section class="order-main-column">
    <section class="panel form-section"><div class="section-heading-row"><div><h2>Termin i raspored</h2><p class="muted">Promena termina proverava preklapanje sa drugim aktivnim nalozima iste ekipe.</p></div><a class="button button-ghost button-small" href="{{ route('admin.after-sales.show',$workOrder->action->case) }}">Otvori reklamaciju</a></div>
        @unless($workOrder->isTerminal())
        <form method="post" action="{{ route('admin.field-operations.schedule',$workOrder) }}" class="form-grid two-columns">@csrf @method('PATCH')
            <label><span>Terenska ekipa / partner</span><select name="field_service_team_id"><option value="">Još nije dodeljeno</option>@foreach($teams as $team)<option value="{{ $team->id }}" @selected((int)old('field_service_team_id',$workOrder->field_service_team_id)===(int)$team->id)>{{ $team->name }} @if(!$team->is_active)· neaktivna@endif</option>@endforeach</select></label>
            <label><span>Ruta / tracking / vozilo</span><input name="route_reference" maxlength="190" value="{{ old('route_reference',$workOrder->route_reference) }}"></label>
            <label><span>Početak termina</span><input type="datetime-local" name="planned_start_at" value="{{ old('planned_start_at',$workOrder->planned_start_at?->format('Y-m-d\TH:i')) }}"></label>
            <label><span>Kraj termina</span><input type="datetime-local" name="planned_end_at" value="{{ old('planned_end_at',$workOrder->planned_end_at?->format('Y-m-d\TH:i')) }}"></label>
            <label class="full-width"><span>Poruka kupcu</span><textarea name="public_note" maxlength="5000">{{ old('public_note',$workOrder->public_note) }}</textarea></label>
            <label class="full-width"><span>Interna napomena</span><textarea name="internal_note" maxlength="5000">{{ old('internal_note',$workOrder->internal_note) }}</textarea></label>
            <button class="button button-primary full-width" type="submit">Sačuvaj raspored</button>
        </form>
        @else
        <dl class="detail-list"><dt>Ekipa</dt><dd>{{ $workOrder->team?->name ?? 'Nije dodeljeno' }}</dd><dt>Termin</dt><dd>{{ $workOrder->planned_start_at?->format('d.m.Y H:i') ?? '—' }} @if($workOrder->planned_end_at)– {{ $workOrder->planned_end_at->format('H:i') }}@endif</dd><dt>Referenca</dt><dd>{{ $workOrder->route_reference ?: '—' }}</dd></dl>
        @endunless
    </section>

    <section class="panel form-section"><h2>Operativni status</h2><div class="field-status-timeline"><span class="{{ $workOrder->status==='planned'?'active':'' }}">Planiran</span><span class="{{ in_array($workOrder->status,['en_route','on_site','completed'])?'active':'' }}">Na putu</span><span class="{{ in_array($workOrder->status,['on_site','completed'])?'active':'' }}">Na lokaciji</span><span class="{{ $workOrder->status==='completed'?'active':'' }}">Završen</span></div>
        @unless($workOrder->isTerminal())<div class="header-button-row field-status-actions">
            @if($workOrder->status==='planned')<form method="post" action="{{ route('admin.field-operations.en-route',$workOrder) }}">@csrf<button class="button button-ghost" type="submit"><x-icon name="truck" /> Ekipa je krenula</button></form>@endif
            @if(in_array($workOrder->status,['planned','en_route']))<form method="post" action="{{ route('admin.field-operations.on-site',$workOrder) }}">@csrf<button class="button button-primary" type="submit"><x-icon name="check-circle" /> Ekipa je stigla</button></form>@endif
        </div>@endunless
        <dl class="detail-list compact"><dt>Krenula</dt><dd>{{ $workOrder->en_route_at?->format('d.m.Y H:i') ?? '—' }}</dd><dt>Na lokaciji</dt><dd>{{ $workOrder->on_site_at?->format('d.m.Y H:i') ?? '—' }}</dd><dt>Završeno</dt><dd>{{ $workOrder->completed_at?->format('d.m.Y H:i') ?? '—' }}</dd></dl>
    </section>

    @can('service_parts.view')
    <section class="panel form-section"><div class="section-heading-row"><div><h2>Rezervni delovi</h2><p class="muted">Lokalni delovi se rezervišu pre intervencije i skidaju sa lagera tek po stvarnom utrošku.</p></div><a class="button button-ghost button-small" href="{{ route('admin.service-parts.index') }}">Servisni lager</a></div>
        @if($workOrder->parts->isNotEmpty())<div class="table-wrap"><table><thead><tr><th>Deo</th><th>Izvor</th><th>Traženo</th><th>Rezervisano</th><th>Utrošeno</th>@unless($workOrder->isTerminal())<th></th>@endunless</tr></thead><tbody>@foreach($workOrder->parts as $line)<tr><td><strong>{{ $line->part_name_snapshot }}</strong><small>{{ $line->part_sku_snapshot }}</small></td><td>{{ $line->supply_mode==='local_stock'?'Servisni lager':'Spoljna nabavka / doneto' }}</td><td>{{ number_format((float)$line->requested_quantity,2,',','.') }} {{ $line->unit_snapshot }}</td><td>{{ number_format((float)$line->reserved_quantity,2,',','.') }}</td><td>{{ number_format((float)$line->consumed_quantity,2,',','.') }}</td>@unless($workOrder->isTerminal())<td>@can('service_parts.manage')<form method="post" action="{{ route('admin.field-operations.parts.destroy',[$workOrder,$line]) }}">@csrf @method('DELETE')<button class="button button-danger button-small" type="submit" data-confirm="Ukloniti deo i osloboditi rezervaciju?">Ukloni</button></form>@endcan</td>@endunless</tr>@endforeach</tbody></table></div>@else<div class="alpha-note">Za ovaj radni nalog još nisu planirani rezervni delovi.</div>@endif
        @unless($workOrder->isTerminal()) @can('service_parts.manage')
        <form method="post" action="{{ route('admin.field-operations.parts.store',$workOrder) }}" class="form-grid two-columns service-part-add-form">@csrf<label><span>Rezervni deo</span><select name="service_part_id" required><option value="">Izaberi deo</option>@foreach($serviceParts as $part)<option value="{{ $part->id }}">{{ $part->sku }} · {{ $part->name }} · rasp. {{ number_format($part->availableQuantity(),2,',','.') }} {{ $part->unit }}</option>@endforeach</select></label><label><span>Količina</span><input type="number" name="requested_quantity" min="0.001" step="0.001" required></label><label><span>Izvor</span><select name="supply_mode"><option value="local_stock">Lokalni servisni lager</option><option value="external">Spoljna nabavka / deo donosi ekipa</option></select></label><label><span>Napomena</span><input name="notes" maxlength="3000"></label><button class="button button-ghost full-width" type="submit">Dodaj ili izmeni deo</button></form>
        @if($workOrder->parts->where('supply_mode','local_stock')->isNotEmpty())<form method="post" action="{{ route('admin.field-operations.parts.reserve',$workOrder) }}">@csrf<button class="button button-primary full-width" type="submit">Rezerviši sve lokalne delove</button></form>@endif
        @endcan @endunless
    </section>
    @endcan

    @unless($workOrder->isTerminal())
    <section class="panel form-section"><h2>Završi radni nalog</h2><p class="muted">Završavanje ovog naloga istovremeno završava povezanu postprodajnu radnju i primenjuje odobrene lager/finansijske efekte.</p>
        <form method="post" action="{{ route('admin.field-operations.complete',$workOrder) }}" enctype="multipart/form-data" class="form-grid two-columns">@csrf
            <label class="full-width"><span>Rezultat intervencije</span><textarea name="completion_result" required minlength="5" maxlength="10000">{{ old('completion_result') }}</textarea></label>
            @if($workOrder->parts->isNotEmpty())<div class="full-width service-part-consumption"><h3>Stvarni utrošak delova</h3><p class="muted">Neiskorišćena rezervisana količina automatski se vraća u raspoloživo stanje.</p>@foreach($workOrder->parts as $line)<label><span>{{ $line->part_name_snapshot }} (traženo {{ number_format((float)$line->requested_quantity,2,',','.') }} {{ $line->unit_snapshot }})</span><input type="number" name="part_consumption[{{ $line->id }}]" min="0" max="{{ $line->requested_quantity }}" step="0.001" value="{{ old('part_consumption.'.$line->id,$line->requested_quantity) }}"></label>@endforeach</div>@endif
            <label><span>Završna referenca</span><input name="route_reference" maxlength="190" value="{{ old('route_reference',$workOrder->route_reference) }}"></label>
            <label><span>Pređeni kilometri</span><input type="number" step="0.01" min="0" name="travel_km" value="{{ old('travel_km') }}"></label>
            <label><span>Trošak puta RSD</span><input type="number" step="0.01" min="0" name="travel_cost_rsd" value="{{ old('travel_cost_rsd',0) }}"></label>
            <label><span>Trošak rada RSD</span><input type="number" step="0.01" min="0" name="labor_cost_rsd" value="{{ old('labor_cost_rsd',0) }}"></label>
            <label><span>Dodatni trošak delova RSD</span><input type="number" step="0.01" min="0" name="parts_cost_rsd" value="{{ old('parts_cost_rsd',0) }}"><small>Utrošak iz servisnog lagera obračunava se automatski.</small></label>
            <label><span>Vidljivost dokaza</span><select name="attachment_visibility"><option value="internal">Samo interno</option><option value="public">Vidljivo kupcu</option></select></label>
            <label><span>Fotografije / zapisnik</span><input type="file" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png,.webp"><small>Do 8 fajlova, najviše 10 MB po fajlu.</small></label>
            <button class="button button-primary button-large full-width" type="submit">Završi nalog i postprodajnu radnju</button>
        </form>
    </section>
    <section class="panel form-section danger-zone"><h2>Otkaži radni nalog</h2><form method="post" action="{{ route('admin.field-operations.cancel',$workOrder) }}" class="form-grid">@csrf<label><span>Obavezan razlog</span><textarea name="cancellation_reason" required minlength="5" maxlength="3000"></textarea></label><button class="button button-danger" type="submit">Otkaži nalog i povezanu radnju</button></form></section>
    @endunless

    @if($workOrder->attachments->isNotEmpty())<section class="panel form-section"><h2>Dokazi i dokumentacija</h2><div class="after-sales-attachments">@foreach($workOrder->attachments as $attachment)<a href="{{ route('field-work-order-attachments.show',$attachment) }}"><x-icon name="download" />{{ $attachment->original_name }} <small>· {{ $attachment->visibility==='public'?'vidljivo kupcu':'interno' }}</small></a>@endforeach</div></section>@endif
</section>
<aside class="form-side">
    <section class="panel form-section"><h2>Lokacija i kontakt</h2><dl class="detail-list"><dt>Kupac</dt><dd>{{ $workOrder->customer_name_snapshot ?: 'Kupac' }}</dd><dt>Telefon</dt><dd>{{ $workOrder->customer_phone_snapshot ?: '—' }}</dd><dt>Adresa</dt><dd>{{ $workOrder->service_address_snapshot ?: '—' }}</dd></dl></section>
    <section class="panel form-section"><h2>Ekipa</h2><dl class="detail-list"><dt>Naziv</dt><dd>{{ $workOrder->team?->name ?? 'Nije dodeljeno' }}</dd><dt>Kontakt</dt><dd>{{ $workOrder->team?->contact_person ?: '—' }}</dd><dt>Telefon</dt><dd>{{ $workOrder->team?->phone ?: '—' }}</dd><dt>Vozilo</dt><dd>{{ $workOrder->team?->vehicle_registration ?: '—' }}</dd></dl></section>
    <section class="panel form-section"><h2>Troškovi</h2><dl class="detail-list"><dt>Put</dt><dd>{{ number_format((float)$workOrder->travel_cost_rsd,2,',','.') }} RSD</dd><dt>Rad</dt><dd>{{ number_format((float)$workOrder->labor_cost_rsd,2,',','.') }} RSD</dd><dt>Delovi</dt><dd>{{ number_format((float)$workOrder->parts_cost_rsd,2,',','.') }} RSD</dd><dt>Ukupno</dt><dd><strong>{{ number_format((float)$workOrder->total_cost_rsd,2,',','.') }} RSD</strong></dd></dl></section>
    @if($workOrder->completion_result)<section class="panel form-section"><h2>Rezultat</h2><p>{{ $workOrder->completion_result }}</p></section>@endif
    @if($workOrder->cancellation_reason)<section class="panel form-section danger-zone"><h2>Razlog otkazivanja</h2><p>{{ $workOrder->cancellation_reason }}</p></section>@endif
</aside>
</div>
@endsection
