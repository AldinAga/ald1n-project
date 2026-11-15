@extends('layouts.app')
@section('title', $workOrder->work_order_number)
@section('content')
@php
    $status = $workOrder->status;
    $terminal = $workOrder->isTerminal();
    $tone = match ($status) {
        'completed' => 'ok',
        'cancelled' => 'danger',
        'en_route', 'on_site' => 'info',
        default => 'warning',
    };
    // Same reached-step logic as before the redesign.
    $steps = [
        ['Planiran', $status === 'planned'],
        ['Na putu', in_array($status, ['en_route', 'on_site', 'completed'])],
        ['Na lokaciji', in_array($status, ['on_site', 'completed'])],
        ['Završen', $status === 'completed'],
    ];
@endphp
<div class="a1-ops">
    <nav aria-label="Putanja">
        <ol class="a1-crumbs">
            <li><a href="{{ route('admin.field-operations.index') }}">Terenske operacije</a></li>
            <li aria-current="page">{{ $workOrder->work_order_number }}</li>
        </ol>
    </nav>

    <header class="a1-card">
        <div class="a1-pagehead">
            <div class="a1-pagehead__text">
                <span class="a1-eyebrow">Radni nalog</span>
                <div class="a1-cluster">
                    <h1 class="a1-title">{{ $workOrder->work_order_number }}</h1>
                    <span class="a1-pill a1-pill--{{ $tone }}">{{ $statuses[$status] ?? $status }}</span>
                </div>
                <p class="a1-text">{{ $workOrder->action?->case?->case_number }} · {{ $workOrder->action?->action_number }}</p>
            </div>
            @unless($terminal)
            <div class="a1-pagehead__actions">
                @if($status==='planned')<form class="a1-ow-inline" method="post" action="{{ route('admin.field-operations.en-route',$workOrder) }}">@csrf<button class="a1-btn a1-btn--primary" type="submit"><x-icon name="truck" :size="20" :shell="true" /> Ekipa je krenula</button></form>@endif
                @if(in_array($status,['planned','en_route']))<form class="a1-ow-inline" method="post" action="{{ route('admin.field-operations.on-site',$workOrder) }}">@csrf<button @class(['a1-btn', 'a1-btn--primary' => $status==='en_route']) type="submit"><x-icon name="check-circle" :size="20" :shell="true" /> Ekipa je stigla</button></form>@endif
            </div>
            @endunless
        </div>
    </header>

    <div class="a1-split">
        <div class="a1-stack">
            <section class="a1-card" aria-labelledby="wo-schedule-title">
                <div class="a1-card__head">
                    <div class="a1-card__title">
                        <h2 class="a1-h3" id="wo-schedule-title">Termin i raspored</h2>
                        <p class="a1-text">Promena termina proverava preklapanje sa drugim aktivnim nalozima iste ekipe.</p>
                    </div>
                    <a class="a1-btn a1-btn--sm" href="{{ route('admin.after-sales.show',$workOrder->action->case) }}">Otvori reklamaciju</a>
                </div>
                @unless($terminal)
                <form class="a1-form" method="post" action="{{ route('admin.field-operations.schedule',$workOrder) }}">@csrf @method('PATCH')
                    <div class="a1-row">
                        <div class="a1-field">
                            <label class="a1-label" for="wo-team">Terenska ekipa / partner</label>
                            <select class="a1-select" id="wo-team" name="field_service_team_id" @error('field_service_team_id') aria-invalid="true" aria-describedby="wo-team-err" @enderror><option value="">Još nije dodeljeno</option>@foreach($teams as $team)<option value="{{ $team->id }}" @selected((int)old('field_service_team_id',$workOrder->field_service_team_id)===(int)$team->id)>{{ $team->name }} @if(!$team->is_active)· neaktivna @endif</option>@endforeach</select>
                            @error('field_service_team_id')<p class="a1-error" id="wo-team-err"><x-icon name="alert" :size="16" :shell="true" />{{ $message }}</p>@enderror
                        </div>
                        <div class="a1-field">
                            <label class="a1-label" for="wo-route">Ruta / tracking / vozilo</label>
                            <input class="a1-input" id="wo-route" name="route_reference" maxlength="190" value="{{ old('route_reference',$workOrder->route_reference) }}">
                        </div>
                        <div class="a1-field">
                            <label class="a1-label" for="wo-start">Početak termina</label>
                            <input class="a1-input" id="wo-start" type="datetime-local" name="planned_start_at" value="{{ old('planned_start_at',$workOrder->planned_start_at?->format('Y-m-d\TH:i')) }}" @error('planned_start_at') aria-invalid="true" aria-describedby="wo-start-err" @enderror>
                            @error('planned_start_at')<p class="a1-error" id="wo-start-err"><x-icon name="alert" :size="16" :shell="true" />{{ $message }}</p>@enderror
                        </div>
                        <div class="a1-field">
                            <label class="a1-label" for="wo-end">Kraj termina</label>
                            <input class="a1-input" id="wo-end" type="datetime-local" name="planned_end_at" value="{{ old('planned_end_at',$workOrder->planned_end_at?->format('Y-m-d\TH:i')) }}" @error('planned_end_at') aria-invalid="true" aria-describedby="wo-end-err" @enderror>
                            @error('planned_end_at')<p class="a1-error" id="wo-end-err"><x-icon name="alert" :size="16" :shell="true" />{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="a1-field">
                        <label class="a1-label" for="wo-public">Poruka kupcu</label>
                        <textarea class="a1-textarea" id="wo-public" name="public_note" maxlength="5000">{{ old('public_note',$workOrder->public_note) }}</textarea>
                    </div>
                    <div class="a1-field">
                        <label class="a1-label" for="wo-internal">Interna napomena</label>
                        <textarea class="a1-textarea" id="wo-internal" name="internal_note" maxlength="5000">{{ old('internal_note',$workOrder->internal_note) }}</textarea>
                    </div>
                    <div><button class="a1-btn" type="submit">Sačuvaj raspored</button></div>
                </form>
                @else
                <dl class="a1-dl">
                    <div class="a1-dl__row"><dt>Ekipa</dt><dd>{{ $workOrder->team?->name ?? 'Nije dodeljeno' }}</dd></div>
                    <div class="a1-dl__row"><dt>Termin</dt><dd>{{ $workOrder->planned_start_at?->format('d.m.Y H:i') ?? '—' }} @if($workOrder->planned_end_at)– {{ $workOrder->planned_end_at->format('H:i') }}@endif</dd></div>
                    <div class="a1-dl__row"><dt>Referenca</dt><dd>{{ $workOrder->route_reference ?: '—' }}</dd></div>
                </dl>
                @endunless
            </section>

            <section class="a1-card" aria-labelledby="wo-status-title">
                <h2 class="a1-h3" id="wo-status-title">Operativni status</h2>
                <ol class="a1-ow-steps">
                    @foreach($steps as [$stepLabel, $reached])
                    <li @class(['a1-ow-step', 'a1-ow-step--on' => $reached]) @if($reached) aria-current="step" @endif>{{ $stepLabel }}</li>
                    @endforeach
                </ol>
                <dl class="a1-dl">
                    <div class="a1-dl__row"><dt>Krenula</dt><dd>{{ $workOrder->en_route_at?->format('d.m.Y H:i') ?? '—' }}</dd></div>
                    <div class="a1-dl__row"><dt>Na lokaciji</dt><dd>{{ $workOrder->on_site_at?->format('d.m.Y H:i') ?? '—' }}</dd></div>
                    <div class="a1-dl__row"><dt>Završeno</dt><dd>{{ $workOrder->completed_at?->format('d.m.Y H:i') ?? '—' }}</dd></div>
                </dl>
            </section>

            @can('service_parts.view')
            <section class="a1-card" aria-labelledby="wo-parts-title">
                <div class="a1-card__head">
                    <div class="a1-card__title">
                        <h2 class="a1-h3" id="wo-parts-title">Rezervni delovi</h2>
                        <p class="a1-text">Lokalni delovi se rezervišu pre intervencije i skidaju sa lagera tek po stvarnom utrošku.</p>
                    </div>
                    <a class="a1-btn a1-btn--sm" href="{{ route('admin.service-parts.index') }}">Servisni lager</a>
                </div>
                @if($workOrder->parts->isNotEmpty())
                <div class="a1-tablewrap a1-tablewrap--sticky" role="region" aria-label="Rezervni delovi radnog naloga {{ $workOrder->work_order_number }}" tabindex="0">
                    <table class="a1-table a1-table--cards" role="table">
                        <caption class="a1-sr">Rezervni delovi radnog naloga {{ $workOrder->work_order_number }}</caption>
                        <thead role="rowgroup"><tr role="row"><th scope="col" role="columnheader">Deo</th><th scope="col" role="columnheader">Izvor</th><th scope="col" role="columnheader" class="a1-table__num">Traženo</th><th scope="col" role="columnheader" class="a1-table__num">Rezervisano</th><th scope="col" role="columnheader" class="a1-table__num">Utrošeno</th>@unless($terminal)<th scope="col" role="columnheader"><span class="a1-sr">Radnje</span></th>@endunless</tr></thead>
                        <tbody role="rowgroup">
                        @foreach($workOrder->parts as $line)
                            <tr role="row">
                                <td class="a1-table__primary" role="cell" data-label="Deo"><strong>{{ $line->part_name_snapshot }}</strong><span class="a1-table__sub">{{ $line->part_sku_snapshot }}</span></td>
                                <td role="cell" data-label="Izvor">{{ $line->supply_mode==='local_stock'?'Servisni lager':'Spoljna nabavka / doneto' }}</td>
                                <td class="a1-table__num" role="cell" data-label="Traženo">{{ number_format((float)$line->requested_quantity,2,',','.') }} {{ $line->unit_snapshot }}</td>
                                <td class="a1-table__num" role="cell" data-label="Rezervisano">{{ number_format((float)$line->reserved_quantity,2,',','.') }}</td>
                                <td class="a1-table__num" role="cell" data-label="Utrošeno">{{ number_format((float)$line->consumed_quantity,2,',','.') }}</td>
                                @unless($terminal)
                                <td class="a1-table__actions" role="cell">@can('service_parts.manage')<form class="a1-ow-inline" method="post" action="{{ route('admin.field-operations.parts.destroy',[$workOrder,$line]) }}">@csrf @method('DELETE')<button class="a1-btn a1-btn--danger a1-btn--sm" type="submit" data-confirm="Ukloniti deo i osloboditi rezervaciju?" aria-label="Ukloni deo {{ $line->part_name_snapshot }} sa radnog naloga {{ $workOrder->work_order_number }}">Ukloni</button></form>@endcan</td>
                                @endunless
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="a1-empty"><span class="a1-empty__icon"><x-icon name="cube" :size="28" :shell="true" /></span><p>Za ovaj radni nalog još nisu planirani rezervni delovi.</p></div>
                @endif
                @unless($terminal) @can('service_parts.manage')
                <form class="a1-form" method="post" action="{{ route('admin.field-operations.parts.store',$workOrder) }}">@csrf
                    <div class="a1-row">
                        <div class="a1-field">
                            <label class="a1-label" for="wo-part">Rezervni deo <span class="a1-req" aria-hidden="true">*</span></label>
                            <select class="a1-select" id="wo-part" name="service_part_id" required><option value="">Izaberi deo</option>@foreach($serviceParts as $part)<option value="{{ $part->id }}">{{ $part->sku }} · {{ $part->name }} · rasp. {{ number_format($part->availableQuantity(),2,',','.') }} {{ $part->unit }}</option>@endforeach</select>
                        </div>
                        <div class="a1-field">
                            <label class="a1-label" for="wo-part-qty">Količina <span class="a1-req" aria-hidden="true">*</span></label>
                            <input class="a1-input" id="wo-part-qty" type="number" name="requested_quantity" min="0.001" step="0.001" required>
                        </div>
                        <div class="a1-field">
                            <label class="a1-label" for="wo-part-mode">Izvor</label>
                            <select class="a1-select" id="wo-part-mode" name="supply_mode"><option value="local_stock">Lokalni servisni lager</option><option value="external">Spoljna nabavka / deo donosi ekipa</option></select>
                        </div>
                        <div class="a1-field">
                            <label class="a1-label" for="wo-part-notes">Napomena</label>
                            <input class="a1-input" id="wo-part-notes" name="notes" maxlength="3000">
                        </div>
                    </div>
                    <div><button class="a1-btn" type="submit">Dodaj ili izmeni deo</button></div>
                </form>
                @if($workOrder->parts->where('supply_mode','local_stock')->isNotEmpty())
                <form class="a1-ow-inline" method="post" action="{{ route('admin.field-operations.parts.reserve',$workOrder) }}">@csrf<button class="a1-btn" type="submit">Rezerviši sve lokalne delove</button></form>
                @endif
                @endcan @endunless
            </section>
            @endcan

            @unless($terminal)
            <section class="a1-card" id="wo-complete" aria-labelledby="wo-complete-title">
                <div class="a1-card__title">
                    <h2 class="a1-h3" id="wo-complete-title">Završi radni nalog</h2>
                    <p class="a1-text">Završavanje ovog naloga istovremeno završava povezanu postprodajnu radnju i primenjuje odobrene lager/finansijske efekte.</p>
                </div>
                <p class="a1-help">Polja sa zvezdicom su obavezna.</p>
                <form class="a1-form" method="post" action="{{ route('admin.field-operations.complete',$workOrder) }}" enctype="multipart/form-data">@csrf
                    <div class="a1-field">
                        <label class="a1-label" for="wo-result">Rezultat intervencije <span class="a1-req" aria-hidden="true">*</span></label>
                        <textarea class="a1-textarea" id="wo-result" name="completion_result" required minlength="5" maxlength="10000" @error('completion_result') aria-invalid="true" aria-describedby="wo-result-err" @enderror>{{ old('completion_result') }}</textarea>
                        @error('completion_result')<p class="a1-error" id="wo-result-err"><x-icon name="alert" :size="16" :shell="true" />{{ $message }}</p>@enderror
                    </div>
                    @if($workOrder->parts->isNotEmpty())
                    <fieldset class="a1-fieldset">
                        <legend class="a1-legend">Stvarni utrošak delova</legend>
                        <p class="a1-help" id="wo-consume-help">Neiskorišćena rezervisana količina automatski se vraća u raspoloživo stanje.</p>
                        <div class="a1-row">
                            @foreach($workOrder->parts as $line)
                            <div class="a1-field">
                                <label class="a1-label" for="wo-consume-{{ $line->id }}">{{ $line->part_name_snapshot }} (traženo {{ number_format((float)$line->requested_quantity,2,',','.') }} {{ $line->unit_snapshot }})</label>
                                <input class="a1-input" id="wo-consume-{{ $line->id }}" type="number" name="part_consumption[{{ $line->id }}]" min="0" max="{{ $line->requested_quantity }}" step="0.001" value="{{ old('part_consumption.'.$line->id,$line->requested_quantity) }}" aria-describedby="wo-consume-help">
                            </div>
                            @endforeach
                        </div>
                    </fieldset>
                    @endif
                    <div class="a1-row">
                        <div class="a1-field">
                            <label class="a1-label" for="wo-final-route">Završna referenca</label>
                            <input class="a1-input" id="wo-final-route" name="route_reference" maxlength="190" value="{{ old('route_reference',$workOrder->route_reference) }}">
                        </div>
                        <div class="a1-field">
                            <label class="a1-label" for="wo-km">Pređeni kilometri</label>
                            <input class="a1-input" id="wo-km" type="number" step="0.01" min="0" name="travel_km" value="{{ old('travel_km') }}">
                        </div>
                        <div class="a1-field">
                            <label class="a1-label" for="wo-travel-cost">Trošak puta RSD</label>
                            <input class="a1-input" id="wo-travel-cost" type="number" step="0.01" min="0" name="travel_cost_rsd" value="{{ old('travel_cost_rsd',0) }}">
                        </div>
                        <div class="a1-field">
                            <label class="a1-label" for="wo-labor-cost">Trošak rada RSD</label>
                            <input class="a1-input" id="wo-labor-cost" type="number" step="0.01" min="0" name="labor_cost_rsd" value="{{ old('labor_cost_rsd',0) }}">
                        </div>
                        <div class="a1-field">
                            <label class="a1-label" for="wo-parts-cost">Dodatni trošak delova RSD</label>
                            <input class="a1-input" id="wo-parts-cost" type="number" step="0.01" min="0" name="parts_cost_rsd" value="{{ old('parts_cost_rsd',0) }}" aria-describedby="wo-parts-cost-help">
                            <p class="a1-help" id="wo-parts-cost-help">Utrošak iz servisnog lagera obračunava se automatski.</p>
                        </div>
                        <div class="a1-field">
                            <label class="a1-label" for="wo-visibility">Vidljivost dokaza</label>
                            <select class="a1-select" id="wo-visibility" name="attachment_visibility"><option value="internal">Samo interno</option><option value="public">Vidljivo kupcu</option></select>
                        </div>
                    </div>
                    <div class="a1-field">
                        <label class="a1-label" for="wo-files">Fotografije / zapisnik</label>
                        <input class="a1-input" id="wo-files" type="file" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png,.webp" aria-describedby="wo-files-help">
                        <p class="a1-help" id="wo-files-help">Do 8 fajlova, najviše 10 MB po fajlu.</p>
                    </div>
                    <div><button @class(['a1-btn', 'a1-btn--primary' => $status==='on_site']) type="submit">Završi nalog i postprodajnu radnju</button></div>
                </form>
            </section>
            @endunless

            @if($workOrder->attachments->isNotEmpty())
            <section class="a1-card" aria-labelledby="wo-files-title">
                <h2 class="a1-h3" id="wo-files-title">Dokazi i dokumentacija</h2>
                <ul class="a1-list">
                    @foreach($workOrder->attachments as $attachment)
                    <li><a class="a1-item a1-item--plain" href="{{ route('field-work-order-attachments.show',$attachment) }}"><span class="a1-item__main"><span class="a1-item__title">{{ $attachment->original_name }}</span><span class="a1-item__sub">{{ $attachment->visibility==='public'?'vidljivo kupcu':'interno' }}</span></span><x-icon name="download" :size="20" :shell="true" /></a></li>
                    @endforeach
                </ul>
            </section>
            @endif

            @unless($terminal)
            <section class="a1-card a1-ow-danger" aria-labelledby="wo-cancel-title">
                <div class="a1-alert a1-alert--danger"><x-icon name="alert" :size="20" :shell="true" /><div class="a1-alert__body"><h2 class="a1-alert__title" id="wo-cancel-title">Otkaži radni nalog</h2></div></div>
                <form class="a1-form" method="post" action="{{ route('admin.field-operations.cancel',$workOrder) }}">@csrf
                    <div class="a1-field">
                        <label class="a1-label" for="wo-cancel-reason">Obavezan razlog <span class="a1-req" aria-hidden="true">*</span></label>
                        <textarea class="a1-textarea" id="wo-cancel-reason" name="cancellation_reason" required minlength="5" maxlength="3000" @error('cancellation_reason') aria-invalid="true" aria-describedby="wo-cancel-reason-err" @enderror></textarea>
                        @error('cancellation_reason')<p class="a1-error" id="wo-cancel-reason-err"><x-icon name="alert" :size="16" :shell="true" />{{ $message }}</p>@enderror
                    </div>
                    <div><button class="a1-btn a1-btn--danger" type="submit" data-confirm="Otkazati radni nalog i povezanu postprodajnu radnju?">Otkaži nalog i povezanu radnju</button></div>
                </form>
            </section>
            @endunless
        </div>

        <aside class="a1-stack" aria-label="Podaci o radnom nalogu">
            <section class="a1-card" aria-labelledby="wo-site-title">
                <h2 class="a1-h3" id="wo-site-title">Lokacija i kontakt</h2>
                <dl class="a1-dl">
                    <div class="a1-dl__row"><dt>Kupac</dt><dd>{{ $workOrder->customer_name_snapshot ?: 'Kupac' }}</dd></div>
                    <div class="a1-dl__row"><dt>Telefon</dt><dd>{{ $workOrder->customer_phone_snapshot ?: '—' }}</dd></div>
                    <div class="a1-dl__row"><dt>Adresa</dt><dd>{{ $workOrder->service_address_snapshot ?: '—' }}</dd></div>
                </dl>
            </section>
            <section class="a1-card" aria-labelledby="wo-team-title">
                <h2 class="a1-h3" id="wo-team-title">Ekipa</h2>
                <dl class="a1-dl">
                    <div class="a1-dl__row"><dt>Naziv</dt><dd>{{ $workOrder->team?->name ?? 'Nije dodeljeno' }}</dd></div>
                    <div class="a1-dl__row"><dt>Kontakt</dt><dd>{{ $workOrder->team?->contact_person ?: '—' }}</dd></div>
                    <div class="a1-dl__row"><dt>Telefon</dt><dd>{{ $workOrder->team?->phone ?: '—' }}</dd></div>
                    <div class="a1-dl__row"><dt>Vozilo</dt><dd>{{ $workOrder->team?->vehicle_registration ?: '—' }}</dd></div>
                </dl>
            </section>
            <section class="a1-card" aria-labelledby="wo-costs-title">
                <h2 class="a1-h3" id="wo-costs-title">Troškovi</h2>
                <dl class="a1-dl">
                    <div class="a1-dl__row"><dt>Put</dt><dd class="a1-num">{{ number_format((float)$workOrder->travel_cost_rsd,2,',','.') }} RSD</dd></div>
                    <div class="a1-dl__row"><dt>Rad</dt><dd class="a1-num">{{ number_format((float)$workOrder->labor_cost_rsd,2,',','.') }} RSD</dd></div>
                    <div class="a1-dl__row"><dt>Delovi</dt><dd class="a1-num">{{ number_format((float)$workOrder->parts_cost_rsd,2,',','.') }} RSD</dd></div>
                    <div class="a1-dl__row"><dt>Ukupno</dt><dd class="a1-num"><strong>{{ number_format((float)$workOrder->total_cost_rsd,2,',','.') }} RSD</strong></dd></div>
                </dl>
            </section>
            @if($workOrder->completion_result)
            <section class="a1-card" aria-labelledby="wo-result-title">
                <h2 class="a1-h3" id="wo-result-title">Rezultat</h2>
                <p class="a1-text">{{ $workOrder->completion_result }}</p>
            </section>
            @endif
            @if($workOrder->cancellation_reason)
            <div class="a1-alert a1-alert--danger" role="status"><x-icon name="alert" :size="20" :shell="true" /><div class="a1-alert__body"><h2 class="a1-alert__title">Razlog otkazivanja</h2><p>{{ $workOrder->cancellation_reason }}</p></div></div>
            @endif
        </aside>
    </div>
</div>
@endsection
