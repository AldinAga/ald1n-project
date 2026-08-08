@extends('layouts.app')
@section('title', $case->case_number)
@section('content')
<a class="back-link" href="{{ route('admin.after-sales.index') }}">← Reklamacije i servisi</a>
<div class="page-heading"><div><span class="eyebrow">{{ $labels['types'][$case->case_type] ?? $case->case_type }}</span><h1>{{ $case->case_number }}</h1><p>{{ $case->subject }} · {{ $case->order?->order_number }}</p></div><div class="header-button-row"><span class="priority-badge priority-{{ $case->priority }}">{{ $labels['priorities'][$case->priority] ?? $case->priority }}</span><span class="status-badge after-sales-status-{{ $case->status }}">{{ $labels['statuses'][$case->status] ?? $case->status }}</span></div></div>
<div class="settings-grid operational-order-grid">
<section class="order-main-column">
    <section class="panel form-section"><div class="section-heading-row"><div><h2>Prijavljeni problem</h2><p class="muted">Otvorio {{ $case->opener?->displayName() ?? 'Korisnik' }} · {{ $case->created_at?->format('d.m.Y H:i') }}</p></div><a class="button button-ghost button-small" href="{{ route('admin.orders.show',$case->order) }}">Otvori porudžbinu</a></div><p class="after-sales-description">{{ $case->description }}</p><div class="after-sales-item-grid compact">@foreach($case->items as $item)<article class="after-sales-item-card"><strong>{{ $item->product_name_snapshot }}</strong><small>{{ $item->sku_snapshot ?: 'Bez SKU' }} · {{ $item->quantity }} kom.</small>@if($item->issue_description)<p>{{ $item->issue_description }}</p>@endif</article>@endforeach</div></section>


    <section class="panel form-section after-sales-actions-panel">
        <div class="section-heading-row"><div><h2>Izvršne postprodajne radnje</h2><p class="muted">Servis, zamena, prijem vraćene robe i refundacija izvršavaju se kroz kontrolisane, auditovane radnje.</p></div><span class="status-badge">{{ $case->actions->whereNotIn('status', ['completed','cancelled'])->count() }} aktivnih</span></div>
        <div class="after-sales-action-list">
            @forelse($case->actions as $action)
                <article class="after-sales-action-card action-status-{{ $action->status }}">
                    <div class="after-sales-action-head"><div><span class="eyebrow">{{ $actionLabels['types'][$action->action_type] ?? $action->action_type }}</span><h3>{{ $action->action_number }}</h3></div><span class="status-badge action-badge-{{ $action->status }}">{{ $actionLabels['statuses'][$action->status] ?? $action->status }}</span></div>
                    <dl class="detail-list compact"><dt>Odgovorno lice</dt><dd>{{ $action->assignee?->displayName() ?? 'Nije dodeljeno' }}</dd><dt>Termin</dt><dd>{{ $action->scheduled_at?->format('d.m.Y H:i') ?? 'Nije zakazan' }}</dd><dt>Rok</dt><dd>{{ $action->due_at?->format('d.m.Y H:i') ?? 'Nije postavljen' }}</dd>@if($action->amount_rsd)<dt>Iznos</dt><dd>{{ number_format((float)$action->amount_rsd,2,',','.') }} RSD</dd>@endif @if($action->reference)<dt>Referenca</dt><dd>{{ $action->reference }}</dd>@endif</dl>
                    <div class="after-sales-action-items">@foreach($action->items as $item)<span><strong>{{ $item->product_name_snapshot }}</strong> · {{ $item->quantity }} kom. · {{ $actionLabels['dispositions'][$item->disposition] ?? $item->disposition }}</span>@endforeach</div>
                    @if($action->public_note)<p class="action-public-note"><strong>Poruka kupcu:</strong> {{ $action->public_note }}</p>@endif
                    @if($action->internal_note)<p class="action-internal-note"><strong>Interno:</strong> {{ $action->internal_note }}</p>@endif
                    @if($action->completion_note)<p><strong>Završna napomena:</strong> {{ $action->completion_note }}</p>@endif
                    @if($action->cancellation_reason)<p class="danger-text"><strong>Razlog otkazivanja:</strong> {{ $action->cancellation_reason }}</p>@endif
                    @if($action->workOrder)<div class="field-work-order-inline"><div><span class="eyebrow">Radni nalog</span><strong>{{ $action->workOrder->work_order_number }}</strong><small>{{ $action->workOrder->team?->name ?? 'Bez dodeljene ekipe' }} · {{ $action->workOrder->planned_start_at?->format('d.m.Y H:i') ?? 'bez termina' }}</small></div><a class="button button-ghost button-small" href="{{ route('admin.field-operations.show',$action->workOrder) }}"><x-icon name="truck" /> Otvori radni nalog</a></div>@endif
                    @can('after_sales.execute')
                        @unless($action->isTerminal())
                        <div class="after-sales-action-controls">
                            @if($action->workOrder)
                                <a class="button button-primary button-small" href="{{ route('admin.field-operations.show',$action->workOrder) }}">Upravljaj terenskim nalogom</a>
                            @else
                                @if($action->status === 'planned')<form method="post" action="{{ route('admin.after-sales.actions.start',[$case,$action]) }}">@csrf<button class="button button-ghost button-small" type="submit">Pokreni radnju</button></form>@endif
                                <details><summary class="button button-primary button-small">Označi kao izvršenu</summary><form method="post" action="{{ route('admin.after-sales.actions.complete',[$case,$action]) }}" class="inline-action-form">@csrf<label><span>Referenca / broj dokumenta</span><input name="reference" value="{{ $action->reference }}" maxlength="190"></label><label><span>Završna napomena</span><textarea name="completion_note" maxlength="5000"></textarea></label><button class="button button-primary" type="submit">Potvrdi izvršenje</button></form></details>
                                <details><summary class="button button-danger button-small">Otkaži radnju</summary><form method="post" action="{{ route('admin.after-sales.actions.cancel',[$case,$action]) }}" class="inline-action-form">@csrf<label><span>Obavezan razlog</span><textarea name="cancellation_reason" required minlength="5" maxlength="3000"></textarea></label><button class="button button-danger" type="submit">Potvrdi otkazivanje</button></form></details>
                            @endif
                        </div>
                        @endunless
                    @endcan
                </article>
            @empty<p class="muted">Još nema planiranih izvršnih radnji.</p>@endforelse
        </div>
        @can('after_sales.execute')
        @unless(in_array($case->status, ['rejected','closed'], true))
        <details class="after-sales-action-planner" @if($errors->has('action_type') || $errors->has('items') || $errors->has('amount_rsd')) open @endif>
            <summary class="button button-primary">Planiraj novu radnju</summary>
            <form method="post" action="{{ route('admin.after-sales.actions.store',$case) }}" class="form-grid two-columns">@csrf
                <label><span>Vrsta radnje</span><select name="action_type" required>@foreach($actionLabels['types'] as $value=>$label)<option value="{{ $value }}" @selected(old('action_type')===$value)>{{ $label }}</option>@endforeach</select><small>Servisna poseta može se planirati tokom obrade; zamena, povrat i refundacija nakon odobrenja slučaja.</small></label>
                <label><span>Način obrade lagera</span><select name="inventory_handling"><option value="none">Bez promene lagera</option><option value="automatic" @selected(old('inventory_handling')==='automatic')>Automatski kroz lokalni lager</option><option value="external" @selected(old('inventory_handling')==='external')>Eksterno / bez automatske promene</option></select></label>
                <label><span>Odgovorno lice</span><select name="assigned_to"><option value="">Odgovorno lice slučaja</option>@foreach($assignees as $assignee)<option value="{{ $assignee->id }}" @selected((int)old('assigned_to')===(int)$assignee->id)>{{ $assignee->displayName() }}</option>@endforeach</select></label>
                <label><span>Početak terenskog termina</span><input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}"></label>
                <label><span>Kraj terenskog termina</span><input type="datetime-local" name="scheduled_end_at" value="{{ old('scheduled_end_at') }}"><small>Za fizičke radnje koristi se u kalendaru ekipa.</small></label>
                <label><span>Terenska ekipa / partner</span><select name="field_service_team_id"><option value="">Dodeli kasnije</option>@foreach($fieldTeams as $team)<option value="{{ $team->id }}" @selected((int)old('field_service_team_id')===(int)$team->id)>{{ $team->name }}</option>@endforeach</select></label>
                <label><span>Rok izvršenja</span><input type="datetime-local" name="due_at" value="{{ old('due_at') }}"></label>
                <label><span>Iznos refundacije RSD</span><input type="number" step="0.01" min="0.01" name="amount_rsd" value="{{ old('amount_rsd') }}"><small>Popunjava se samo za refundaciju.</small></label>
                <label><span>Referenca / tracking / servisni nalog</span><input name="reference" maxlength="190" value="{{ old('reference') }}"></label>
                <label class="full-width"><span>Poruka vidljiva kupcu</span><textarea name="public_note" maxlength="5000">{{ old('public_note') }}</textarea></label>
                <label class="full-width"><span>Interna napomena</span><textarea name="internal_note" maxlength="5000">{{ old('internal_note') }}</textarea></label>
                <div class="full-width"><h3>Stavke obuhvaćene radnjom</h3><div class="after-sales-item-grid compact">@foreach($case->items as $item)<article class="after-sales-item-card"><label class="after-sales-item-check"><input type="checkbox" name="items[{{ $item->id }}][selected]" value="1" @checked(old('items.'.$item->id.'.selected'))><strong>{{ $item->product_name_snapshot }}</strong></label><small>{{ $item->sku_snapshot ?: 'Bez SKU' }} · prijavljeno {{ $item->quantity }} kom.</small><label><span>Količina</span><input type="number" min="1" max="{{ $item->quantity }}" name="items[{{ $item->id }}][quantity]" value="{{ old('items.'.$item->id.'.quantity',1) }}"></label><label><span>Postupak sa artiklom</span><select name="items[{{ $item->id }}][disposition]">@foreach($actionLabels['dispositions'] as $value=>$label)<option value="{{ $value }}" @selected(old('items.'.$item->id.'.disposition')===$value)>{{ $label }}</option>@endforeach</select></label></article>@endforeach</div></div>
                <button class="button button-primary button-large full-width" type="submit">Sačuvaj plan radnje</button>
            </form>
        </details>
        @endunless
        @endcan
        @if($case->actions->isNotEmpty() && $case->actions->whereNotIn('status',['completed','cancelled'])->isEmpty())<div class="success-callout"><strong>Sve planirane radnje su završene.</strong><span>Slučaj je spreman za konačno označavanje kao rešen ili zatvoren.</span></div>@endif
    </section>

    <section class="panel form-section"><div class="section-heading-row"><div><h2>Komunikacija i interne napomene</h2><p class="muted">Javne poruke vidi korisnik. Interne napomene ostaju samo u administraciji.</p></div></div>
        <div class="after-sales-thread">
            @forelse($case->messages as $message)
                <article class="after-sales-message {{ $message->visibility==='internal' ? 'is-internal' : ($message->user?->hasRole('admin','superadmin') ? 'is-admin':'is-user') }}"><div class="after-sales-message-head"><strong>{{ $message->user?->displayName() ?? 'Korisnik' }} @if($message->visibility==='internal')<span>Interno</span>@endif</strong><time>{{ $message->created_at?->format('d.m.Y H:i') }}</time></div><p>{{ $message->body }}</p>@if($message->attachments->isNotEmpty())<div class="after-sales-attachments">@foreach($message->attachments as $attachment)<a href="{{ route('after-sales.attachments.show',$attachment) }}"><x-icon name="download" />{{ $attachment->original_name }}</a>@endforeach</div>@endif</article>
            @empty<p class="muted">Još nema dodatnih poruka.</p>@endforelse
        </div>
        @unless($case->isClosed())
        <form class="after-sales-message-form" method="post" action="{{ route('admin.after-sales.messages.store',$case) }}" enctype="multipart/form-data">@csrf<div class="form-grid two-columns"><label class="full-width"><span>Poruka / interna napomena</span><textarea name="body" required maxlength="10000"></textarea></label><label><span>Vidljivost</span><select name="visibility"><option value="public">Javno korisniku</option><option value="internal">Samo interno</option></select></label><label><span>Prilozi</span><input type="file" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png,.webp"></label></div><button class="button button-primary" type="submit">Sačuvaj poruku</button></form>
        @endunless
    </section>

    <section class="panel form-section"><h2>Istorija statusa</h2><div class="operation-timeline">@foreach($case->history as $entry)<article class="timeline-event"><span class="timeline-dot"></span><div><div class="timeline-event-head"><strong>{{ $labels['statuses'][$entry->to_status] ?? $entry->to_status }}</strong><time>{{ $entry->created_at?->format('d.m.Y H:i') }}</time></div>@if($entry->note)<p>{{ $entry->note }}</p>@endif<small>{{ $entry->actor?->displayName() ?? 'Sistem' }}</small></div></article>@endforeach</div></section>
</section>
<aside class="form-side">
    <section class="panel form-section after-sales-admin-card"><h2>Obrada slučaja</h2>
        <form method="post" action="{{ route('admin.after-sales.update',$case) }}">@csrf @method('PATCH')
        <div class="form-grid">
            <label><span>Status</span><select name="status" required>@foreach($labels['statuses'] as $value=>$label)<option value="{{ $value }}" @selected(old('status',$case->status)===$value)>{{ $label }}</option>@endforeach</select></label>
            <label><span>Prioritet</span><select name="priority" required>@foreach($labels['priorities'] as $value=>$label)<option value="{{ $value }}" @selected(old('priority',$case->priority)===$value)>{{ $label }}</option>@endforeach</select></label>
            <label><span>Odgovorno lice</span><select name="assigned_to"><option value="">Zadrži trenutno</option>@foreach($assignees as $assignee)<option value="{{ $assignee->id }}" @selected((int)old('assigned_to',$case->assigned_to)===(int)$assignee->id)>{{ $assignee->displayName() }} · {{ $assignee->roleName() }}</option>@endforeach</select></label>
            <label><span>Rok odgovora/rešenja</span><input type="datetime-local" name="due_at" value="{{ old('due_at',$case->due_at?->format('Y-m-d\TH:i')) }}"></label>
            <label><span>Vrsta odluke</span><select name="resolution_type"><option value="">Još nije odlučeno</option>@foreach($labels['resolutions'] as $value=>$label)<option value="{{ $value }}" @selected(old('resolution_type',$case->resolution_type)===$value)>{{ $label }}</option>@endforeach</select></label>
            <label><span>Obrazloženje odluke</span><textarea name="resolution_summary" maxlength="10000">{{ old('resolution_summary',$case->resolution_summary) }}</textarea></label>
            <label><span>Napomena uz promenu statusa</span><textarea name="note" maxlength="3000"></textarea></label>
            <button class="button button-primary button-large" type="submit">Sačuvaj obradu</button>
        </div></form>
    </section>
    <section class="panel form-section"><h2>Kupac i porudžbina</h2><dl class="detail-list"><dt>Kupac</dt><dd>{{ $case->customer_name_snapshot ?: 'Kupac' }}</dd><dt>Telefon</dt><dd>{{ $case->customer_phone_snapshot ?: '—' }}</dd><dt>Adresa</dt><dd>{{ $case->customer_address_snapshot ?: '—' }}</dd><dt>Željeno rešenje</dt><dd>{{ $labels['resolutions'][$case->requested_resolution] ?? 'Nije navedeno' }}</dd><dt>Prvi odgovor</dt><dd>{{ $case->first_response_at?->format('d.m.Y H:i') ?? 'Još nije odgovoreno' }}</dd></dl></section>
    @if($case->attachments->whereNull('message_id')->isNotEmpty())<section class="panel form-section"><h2>Početni prilozi</h2><div class="after-sales-attachments">@foreach($case->attachments->whereNull('message_id') as $attachment)<a href="{{ route('after-sales.attachments.show',$attachment) }}"><x-icon name="download" />{{ $attachment->original_name }}</a>@endforeach</div></section>@endif
</aside>
</div>
@endsection
