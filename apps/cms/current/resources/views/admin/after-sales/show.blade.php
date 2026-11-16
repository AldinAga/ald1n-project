@extends('layouts.app')
@section('title', $case->case_number)
@section('content')
@php
    $statusTone = static fn (string $status): string => match ($status) {
        'resolved' => ' a1-pill--ok',
        'rejected' => ' a1-pill--danger',
        'open', 'under_review', 'awaiting_customer' => ' a1-pill--warning',
        'approved', 'in_service' => ' a1-pill--info',
        default => '',
    };
    $priorityTone = static fn (string $priority): string => match ($priority) {
        'urgent' => ' a1-pill--danger',
        'high' => ' a1-pill--warning',
        default => '',
    };
    $actionTone = static fn (string $status): string => match ($status) {
        'completed' => ' a1-pill--ok',
        'cancelled' => ' a1-pill--danger',
        'planned' => ' a1-pill--warning',
        'in_progress' => ' a1-pill--info',
        default => '',
    };
    $activeActions = $case->actions->whereNotIn('status', ['completed', 'cancelled'])->count();
    $initialAttachments = $case->attachments->whereNull('message_id');
@endphp
<div class="a1-ops" data-after-sales-admin-show="1">
    <nav aria-label="Putanja">
        <ol class="a1-crumbs">
            <li><a href="{{ route('admin.after-sales.index') }}">Reklamacije i servisi</a></li>
            <li aria-current="page">{{ $case->case_number }}</li>
        </ol>
    </nav>

    <section class="a1-card" aria-labelledby="case-title">
        <div class="a1-card__head">
            <div class="a1-pagehead__text">
                <span class="a1-eyebrow">{{ $labels['types'][$case->case_type] ?? $case->case_type }}</span>
                <h1 class="a1-title" id="case-title">{{ $case->case_number }}</h1>
                <p class="a1-lead">{{ $case->subject }} · {{ $case->order?->order_number }}</p>
            </div>
            <div class="a1-pagehead__actions">
                <span class="a1-os-pills">
                    <span class="a1-pill{{ $priorityTone((string) $case->priority) }}">{{ $labels['priorities'][$case->priority] ?? $case->priority }}</span>
                    <span class="a1-pill{{ $statusTone((string) $case->status) }}">{{ $labels['statuses'][$case->status] ?? $case->status }}</span>
                </span>
            </div>
        </div>
    </section>

    <div class="a1-split">
        <div class="a1-stack">
            <section class="a1-card" aria-labelledby="case-problem-title">
                <div class="a1-card__head">
                    <div class="a1-os-block">
                        <h2 class="a1-os-heading" id="case-problem-title">Prijavljeni problem</h2>
                        <p class="a1-os-lead">Otvorio {{ $case->opener?->displayName() ?? 'Korisnik' }} · {{ $case->created_at?->format('d.m.Y H:i') }}</p>
                    </div>
                    <a class="a1-btn a1-btn--sm" href="{{ route('admin.orders.show',$case->order) }}">Otvori porudžbinu</a>
                </div>
                <p class="a1-os-text">{{ $case->description }}</p>
                @if($case->items->isNotEmpty())
                <div class="a1-os-items">
                    @foreach($case->items as $item)
                    <article class="a1-os-item">
                        <strong class="a1-os-item__name">{{ $item->product_name_snapshot }}</strong>
                        <p class="a1-os-item__meta">{{ $item->sku_snapshot ?: 'Bez SKU' }} · {{ $item->quantity }} kom.</p>
                        @if($item->issue_description)<p class="a1-os-text">{{ $item->issue_description }}</p>@endif
                    </article>
                    @endforeach
                </div>
                @endif
            </section>

            <section class="a1-card" aria-labelledby="case-actions-title">
                <div class="a1-card__head">
                    <div class="a1-os-block">
                        <h2 class="a1-os-heading" id="case-actions-title">Izvršne postprodajne radnje</h2>
                        <p class="a1-os-lead">Servis, zamena, prijem vraćene robe i refundacija izvršavaju se kroz kontrolisane, auditovane radnje.</p>
                    </div>
                    <span class="a1-pill">{{ $activeActions }} aktivnih</span>
                </div>
                @if($case->actions->isNotEmpty())
                <ul class="a1-os-actions">
                    @foreach($case->actions as $action)
                    <li class="a1-os-action">
                        <div class="a1-os-action__head">
                            <div class="a1-pagehead__text">
                                <span class="a1-eyebrow">{{ $actionLabels['types'][$action->action_type] ?? $action->action_type }}</span>
                                <h3 class="a1-os-action__title">{{ $action->action_number }}</h3>
                            </div>
                            <span class="a1-pill{{ $actionTone((string) $action->status) }}">{{ $actionLabels['statuses'][$action->status] ?? $action->status }}</span>
                        </div>
                        <dl class="a1-dl">
                            <div class="a1-dl__row"><dt>Odgovorno lice</dt><dd>{{ $action->assignee?->displayName() ?? 'Nije dodeljeno' }}</dd></div>
                            <div class="a1-dl__row"><dt>Termin</dt><dd>{{ $action->scheduled_at?->format('d.m.Y H:i') ?? 'Nije zakazan' }}</dd></div>
                            <div class="a1-dl__row"><dt>Rok</dt><dd>{{ $action->due_at?->format('d.m.Y H:i') ?? 'Nije postavljen' }}</dd></div>
                            @if($action->amount_rsd)<div class="a1-dl__row"><dt>Iznos</dt><dd>{{ number_format((float)$action->amount_rsd,2,',','.') }} RSD</dd></div>@endif
                            @if($action->reference)<div class="a1-dl__row"><dt>Referenca</dt><dd>{{ $action->reference }}</dd></div>@endif
                        </dl>
                        @if($action->items->isNotEmpty())
                        <ul class="a1-os-action__lines">
                            @foreach($action->items as $item)<li><strong>{{ $item->product_name_snapshot }}</strong> · {{ $item->quantity }} kom. · {{ $actionLabels['dispositions'][$item->disposition] ?? $item->disposition }}</li>@endforeach
                        </ul>
                        @endif
                        @if($action->public_note)<p class="a1-os-note"><strong>Poruka kupcu:</strong> {{ $action->public_note }}</p>@endif
                        @if($action->internal_note)<p class="a1-os-note a1-os-note--internal"><strong>Interno:</strong> {{ $action->internal_note }}</p>@endif
                        @if($action->completion_note)<p class="a1-os-note"><strong>Završna napomena:</strong> {{ $action->completion_note }}</p>@endif
                        @if($action->cancellation_reason)<p class="a1-os-note a1-os-note--danger"><strong>Razlog otkazivanja:</strong> {{ $action->cancellation_reason }}</p>@endif
                        @if($action->workOrder)
                        <div class="a1-os-wo">
                            <div class="a1-os-wo__text">
                                <span class="a1-eyebrow">Radni nalog</span>
                                <strong>{{ $action->workOrder->work_order_number }}</strong>
                                <span class="a1-os-item__meta">{{ $action->workOrder->team?->name ?? 'Bez dodeljene ekipe' }} · {{ $action->workOrder->planned_start_at?->format('d.m.Y H:i') ?? 'bez termina' }}</span>
                            </div>
                            <a class="a1-btn a1-btn--sm" href="{{ route('admin.field-operations.show',$action->workOrder) }}"><x-icon name="truck" :size="18" :shell="true" /> Otvori radni nalog</a>
                        </div>
                        @endif
                        @can('after_sales.execute')
                            @unless($action->isTerminal())
                                @if($action->workOrder)
                                <div class="a1-os-controls">
                                    <a class="a1-btn a1-btn--sm" href="{{ route('admin.field-operations.show',$action->workOrder) }}">Upravljaj terenskim nalogom</a>
                                </div>
                                @else
                                <div class="a1-os-controls">
                                    @if($action->status === 'planned')
                                    <form method="post" action="{{ route('admin.after-sales.actions.start',[$case,$action]) }}">@csrf<button class="a1-btn a1-btn--sm" type="submit">Pokreni radnju</button></form>
                                    @endif
                                    <details class="a1-acc">
                                        <summary>Označi kao izvršenu</summary>
                                        <div class="a1-acc__body">
                                            <form class="a1-form" method="post" action="{{ route('admin.after-sales.actions.complete',[$case,$action]) }}">
                                                @csrf
                                                <div class="a1-field">
                                                    <label class="a1-label" for="act-{{ $action->id }}-ref">Referenca / broj dokumenta</label>
                                                    <input class="a1-input" id="act-{{ $action->id }}-ref" name="reference" value="{{ $action->reference }}" maxlength="190">
                                                </div>
                                                <div class="a1-field">
                                                    <label class="a1-label" for="act-{{ $action->id }}-note">Završna napomena</label>
                                                    <textarea class="a1-textarea" id="act-{{ $action->id }}-note" name="completion_note" maxlength="5000"></textarea>
                                                </div>
                                                <div><button class="a1-btn" type="submit">Potvrdi izvršenje</button></div>
                                            </form>
                                        </div>
                                    </details>
                                </div>
                                <div class="a1-os-danger">
                                    <div class="a1-alert a1-alert--danger">
                                        <x-icon name="alert" :size="20" :shell="true" />
                                        <div class="a1-alert__body">
                                            <strong class="a1-alert__title">Opasna zona</strong>
                                            <span>Otkazivanje radnje zahteva obavezan razlog.</span>
                                        </div>
                                    </div>
                                    <details class="a1-acc">
                                        <summary>Otkaži radnju</summary>
                                        <div class="a1-acc__body">
                                            <form class="a1-form" method="post" action="{{ route('admin.after-sales.actions.cancel',[$case,$action]) }}">
                                                @csrf
                                                <div class="a1-field">
                                                    <label class="a1-label" for="act-{{ $action->id }}-reason">Obavezan razlog <span class="a1-req" aria-hidden="true">*</span></label>
                                                    <textarea class="a1-textarea" id="act-{{ $action->id }}-reason" name="cancellation_reason" required minlength="5" maxlength="3000"></textarea>
                                                </div>
                                                <div><button class="a1-btn a1-btn--danger" type="submit">Potvrdi otkazivanje</button></div>
                                            </form>
                                        </div>
                                    </details>
                                </div>
                                @endif
                            @endunless
                        @endcan
                    </li>
                    @endforeach
                </ul>
                @else
                <p class="a1-os-muted">Još nema planiranih izvršnih radnji.</p>
                @endif

                @can('after_sales.execute')
                @unless(in_array($case->status, ['rejected','closed'], true))
                <details class="a1-acc a1-os-planner" @if($errors->has('action_type') || $errors->has('items') || $errors->has('amount_rsd')) open @endif>
                    <summary>Planiraj novu radnju</summary>
                    <div class="a1-acc__body">
                        <form class="a1-form" method="post" action="{{ route('admin.after-sales.actions.store',$case) }}">
                            @csrf
                            <div class="a1-os-formgrid">
                                <div class="a1-field">
                                    <label class="a1-label" for="plan-type">Vrsta radnje <span class="a1-req" aria-hidden="true">*</span></label>
                                    <select class="a1-select" id="plan-type" name="action_type" required aria-describedby="plan-type-help">@foreach($actionLabels['types'] as $value=>$label)<option value="{{ $value }}" @selected(old('action_type')===$value)>{{ $label }}</option>@endforeach</select>
                                    <p class="a1-help" id="plan-type-help">Servisna poseta može se planirati tokom obrade; zamena, povrat i refundacija nakon odobrenja slučaja.</p>
                                </div>
                                <div class="a1-field">
                                    <label class="a1-label" for="plan-inventory">Način obrade lagera</label>
                                    <select class="a1-select" id="plan-inventory" name="inventory_handling"><option value="none">Bez promene lagera</option><option value="automatic" @selected(old('inventory_handling')==='automatic')>Automatski kroz lokalni lager</option><option value="external" @selected(old('inventory_handling')==='external')>Eksterno / bez automatske promene</option></select>
                                </div>
                                <div class="a1-field">
                                    <label class="a1-label" for="plan-assignee">Odgovorno lice</label>
                                    <select class="a1-select" id="plan-assignee" name="assigned_to"><option value="">Odgovorno lice slučaja</option>@foreach($assignees as $assignee)<option value="{{ $assignee->id }}" @selected((int)old('assigned_to')===(int)$assignee->id)>{{ $assignee->displayName() }}</option>@endforeach</select>
                                </div>
                                <div class="a1-field">
                                    <label class="a1-label" for="plan-start">Početak terenskog termina</label>
                                    <input class="a1-input" id="plan-start" type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}">
                                </div>
                                <div class="a1-field">
                                    <label class="a1-label" for="plan-end">Kraj terenskog termina</label>
                                    <input class="a1-input" id="plan-end" type="datetime-local" name="scheduled_end_at" value="{{ old('scheduled_end_at') }}" aria-describedby="plan-end-help">
                                    <p class="a1-help" id="plan-end-help">Za fizičke radnje koristi se u kalendaru ekipa.</p>
                                </div>
                                <div class="a1-field">
                                    <label class="a1-label" for="plan-team">Terenska ekipa / partner</label>
                                    <select class="a1-select" id="plan-team" name="field_service_team_id"><option value="">Dodeli kasnije</option>@foreach($fieldTeams as $team)<option value="{{ $team->id }}" @selected((int)old('field_service_team_id')===(int)$team->id)>{{ $team->name }}</option>@endforeach</select>
                                </div>
                                <div class="a1-field">
                                    <label class="a1-label" for="plan-due">Rok izvršenja</label>
                                    <input class="a1-input" id="plan-due" type="datetime-local" name="due_at" value="{{ old('due_at') }}">
                                </div>
                                <div class="a1-field">
                                    <label class="a1-label" for="plan-amount">Iznos refundacije RSD</label>
                                    <input class="a1-input" id="plan-amount" type="number" step="0.01" min="0.01" name="amount_rsd" value="{{ old('amount_rsd') }}" aria-describedby="plan-amount-help">
                                    <p class="a1-help" id="plan-amount-help">Popunjava se samo za refundaciju.</p>
                                </div>
                                <div class="a1-field">
                                    <label class="a1-label" for="plan-reference">Referenca / tracking / servisni nalog</label>
                                    <input class="a1-input" id="plan-reference" name="reference" maxlength="190" value="{{ old('reference') }}">
                                </div>
                                <div class="a1-field a1-os-wide">
                                    <label class="a1-label" for="plan-public">Poruka vidljiva kupcu</label>
                                    <textarea class="a1-textarea" id="plan-public" name="public_note" maxlength="5000">{{ old('public_note') }}</textarea>
                                </div>
                                <div class="a1-field a1-os-wide">
                                    <label class="a1-label" for="plan-internal">Interna napomena</label>
                                    <textarea class="a1-textarea" id="plan-internal" name="internal_note" maxlength="5000">{{ old('internal_note') }}</textarea>
                                </div>
                                <div class="a1-os-wide a1-os-pick">
                                    <h3 class="a1-os-subheading">Stavke obuhvaćene radnjom</h3>
                                    <div class="a1-os-items">
                                        @foreach($case->items as $item)
                                        <article class="a1-os-item">
                                            <label class="a1-check">
                                                <input class="a1-check__input" type="checkbox" name="items[{{ $item->id }}][selected]" value="1" @checked(old('items.'.$item->id.'.selected'))>
                                                <span class="a1-check__box" aria-hidden="true"></span>
                                                <strong class="a1-os-item__name">{{ $item->product_name_snapshot }}</strong>
                                            </label>
                                            <p class="a1-os-item__meta">{{ $item->sku_snapshot ?: 'Bez SKU' }} · prijavljeno {{ $item->quantity }} kom.</p>
                                            <div class="a1-field">
                                                <label class="a1-label" for="plan-item-{{ $item->id }}-qty">Količina</label>
                                                <input class="a1-input" id="plan-item-{{ $item->id }}-qty" type="number" min="1" max="{{ $item->quantity }}" name="items[{{ $item->id }}][quantity]" value="{{ old('items.'.$item->id.'.quantity',1) }}">
                                            </div>
                                            <div class="a1-field">
                                                <label class="a1-label" for="plan-item-{{ $item->id }}-disp">Postupak sa artiklom</label>
                                                <select class="a1-select" id="plan-item-{{ $item->id }}-disp" name="items[{{ $item->id }}][disposition]">@foreach($actionLabels['dispositions'] as $value=>$label)<option value="{{ $value }}" @selected(old('items.'.$item->id.'.disposition')===$value)>{{ $label }}</option>@endforeach</select>
                                            </div>
                                        </article>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div><button class="a1-btn" type="submit">Sačuvaj plan radnje</button></div>
                        </form>
                    </div>
                </details>
                @endunless
                @endcan

                @if($case->actions->isNotEmpty() && $case->actions->whereNotIn('status',['completed','cancelled'])->isEmpty())
                <div class="a1-alert a1-alert--ok" role="status">
                    <x-icon name="check-circle" :size="20" :shell="true" />
                    <div class="a1-alert__body">
                        <strong class="a1-alert__title">Sve planirane radnje su završene.</strong>
                        <span>Slučaj je spreman za konačno označavanje kao rešen ili zatvoren.</span>
                    </div>
                </div>
                @endif
            </section>

            <section class="a1-card" aria-labelledby="case-thread-title">
                <div class="a1-os-block">
                    <h2 class="a1-os-heading" id="case-thread-title">Komunikacija i interne napomene</h2>
                    <p class="a1-os-lead">Javne poruke vidi korisnik. Interne napomene ostaju samo u administraciji.</p>
                </div>
                @if($case->messages->isNotEmpty())
                <ol class="a1-os-thread">
                    @foreach($case->messages as $message)
                    <li class="a1-os-msg {{ $message->visibility==='internal' ? 'a1-os-msg--internal' : ($message->user?->hasRole('admin','superadmin') ? 'a1-os-msg--staff' : 'a1-os-msg--customer') }}">
                        <div class="a1-os-msg__head">
                            <span class="a1-os-msg__who">{{ $message->user?->displayName() ?? 'Korisnik' }} @if($message->visibility==='internal')<span class="a1-pill a1-pill--warning">Interno</span>@endif</span>
                            <time class="a1-os-msg__time">{{ $message->created_at?->format('d.m.Y H:i') }}</time>
                        </div>
                        <p class="a1-os-text">{{ $message->body }}</p>
                        @if($message->attachments->isNotEmpty())
                        <ul class="a1-os-files">
                            @foreach($message->attachments as $attachment)<li><a class="a1-os-file" href="{{ route('after-sales.attachments.show',$attachment) }}"><x-icon name="download" :size="16" :shell="true" /> {{ $attachment->original_name }}</a></li>@endforeach
                        </ul>
                        @endif
                    </li>
                    @endforeach
                </ol>
                @else
                <p class="a1-os-muted">Još nema dodatnih poruka.</p>
                @endif

                @unless($case->isClosed())
                <form class="a1-form" method="post" action="{{ route('admin.after-sales.messages.store',$case) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="a1-os-formgrid">
                        <div class="a1-field a1-os-wide">
                            <label class="a1-label" for="case-body">Poruka / interna napomena <span class="a1-req" aria-hidden="true">*</span></label>
                            <textarea class="a1-textarea" id="case-body" name="body" required maxlength="10000" @if($errors->has('body')) aria-invalid="true" aria-describedby="case-body-err" @endif></textarea>
                            @if($errors->has('body'))<p class="a1-error" id="case-body-err"><x-icon name="alert" :size="16" :shell="true" /> {{ $errors->first('body') }}</p>@endif
                        </div>
                        <div class="a1-field">
                            <label class="a1-label" for="case-visibility">Vidljivost</label>
                            <select class="a1-select" id="case-visibility" name="visibility"><option value="public">Javno korisniku</option><option value="internal">Samo interno</option></select>
                        </div>
                        <label class="a1-drop" for="case-files">
                            <input class="a1-drop__input" id="case-files" type="file" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png,.webp" aria-describedby="case-files-help">
                            <x-icon name="plus-circle" :size="24" :shell="true" />
                            <span class="a1-drop__title">Prilozi</span>
                            <span id="case-files-help">PDF, JPG, PNG ili WebP</span>
                        </label>
                    </div>
                    <div><button class="a1-btn" type="submit">Sačuvaj poruku</button></div>
                </form>
                @endunless
            </section>

            <section class="a1-card" aria-labelledby="case-history-title">
                <h2 class="a1-os-heading" id="case-history-title">Istorija statusa</h2>
                @if($case->history->isNotEmpty())
                <ol class="a1-os-timeline">
                    @foreach($case->history as $entry)
                    <li class="a1-os-timeline__item">
                        <div class="a1-os-timeline__head">
                            <strong>{{ $labels['statuses'][$entry->to_status] ?? $entry->to_status }}</strong>
                            <time class="a1-os-timeline__time">{{ $entry->created_at?->format('d.m.Y H:i') }}</time>
                        </div>
                        @if($entry->note)<p class="a1-os-text">{{ $entry->note }}</p>@endif
                        <span class="a1-os-item__meta">{{ $entry->actor?->displayName() ?? 'Sistem' }}</span>
                    </li>
                    @endforeach
                </ol>
                @else
                <p class="a1-os-muted">Još nema promena statusa.</p>
                @endif
            </section>
        </div>

        <aside class="a1-stack" aria-label="Obrada i podaci o slučaju">
            <section class="a1-card" aria-labelledby="case-process-title">
                <h2 class="a1-os-heading" id="case-process-title">Obrada slučaja</h2>
                <form class="a1-form" method="post" action="{{ route('admin.after-sales.update',$case) }}">
                    @csrf @method('PATCH')
                    <div class="a1-field">
                        <label class="a1-label" for="case-status">Status <span class="a1-req" aria-hidden="true">*</span></label>
                        <select class="a1-select" id="case-status" name="status" required>@foreach($labels['statuses'] as $value=>$label)<option value="{{ $value }}" @selected(old('status',$case->status)===$value)>{{ $label }}</option>@endforeach</select>
                    </div>
                    <div class="a1-field">
                        <label class="a1-label" for="case-priority">Prioritet <span class="a1-req" aria-hidden="true">*</span></label>
                        <select class="a1-select" id="case-priority" name="priority" required>@foreach($labels['priorities'] as $value=>$label)<option value="{{ $value }}" @selected(old('priority',$case->priority)===$value)>{{ $label }}</option>@endforeach</select>
                    </div>
                    <div class="a1-field">
                        <label class="a1-label" for="case-assignee">Odgovorno lice</label>
                        <select class="a1-select" id="case-assignee" name="assigned_to"><option value="">Zadrži trenutno</option>@foreach($assignees as $assignee)<option value="{{ $assignee->id }}" @selected((int)old('assigned_to',$case->assigned_to)===(int)$assignee->id)>{{ $assignee->displayName() }} · {{ $assignee->roleName() }}</option>@endforeach</select>
                    </div>
                    <div class="a1-field">
                        <label class="a1-label" for="case-due">Rok odgovora/rešenja</label>
                        <input class="a1-input" id="case-due" type="datetime-local" name="due_at" value="{{ old('due_at',$case->due_at?->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div class="a1-field">
                        <label class="a1-label" for="case-resolution-type">Vrsta odluke</label>
                        <select class="a1-select" id="case-resolution-type" name="resolution_type"><option value="">Još nije odlučeno</option>@foreach($labels['resolutions'] as $value=>$label)<option value="{{ $value }}" @selected(old('resolution_type',$case->resolution_type)===$value)>{{ $label }}</option>@endforeach</select>
                    </div>
                    <div class="a1-field">
                        <label class="a1-label" for="case-resolution-summary">Obrazloženje odluke</label>
                        <textarea class="a1-textarea" id="case-resolution-summary" name="resolution_summary" maxlength="10000">{{ old('resolution_summary',$case->resolution_summary) }}</textarea>
                    </div>
                    <div class="a1-field">
                        <label class="a1-label" for="case-note">Napomena uz promenu statusa</label>
                        <textarea class="a1-textarea" id="case-note" name="note" maxlength="3000"></textarea>
                    </div>
                    <div><button class="a1-btn a1-btn--primary" type="submit">Sačuvaj obradu</button></div>
                </form>
            </section>

            <section class="a1-card" aria-labelledby="case-customer-title">
                <h2 class="a1-os-heading" id="case-customer-title">Kupac i porudžbina</h2>
                <dl class="a1-dl">
                    <div class="a1-dl__row"><dt>Kupac</dt><dd>{{ $case->customer_name_snapshot ?: 'Kupac' }}</dd></div>
                    <div class="a1-dl__row"><dt>Telefon</dt><dd>{{ $case->customer_phone_snapshot ?: '—' }}</dd></div>
                    <div class="a1-dl__row"><dt>Adresa</dt><dd>{{ $case->customer_address_snapshot ?: '—' }}</dd></div>
                    <div class="a1-dl__row"><dt>Željeno rešenje</dt><dd>{{ $labels['resolutions'][$case->requested_resolution] ?? 'Nije navedeno' }}</dd></div>
                    <div class="a1-dl__row"><dt>Prvi odgovor</dt><dd>{{ $case->first_response_at?->format('d.m.Y H:i') ?? 'Još nije odgovoreno' }}</dd></div>
                </dl>
            </section>

            @if($initialAttachments->isNotEmpty())
            <section class="a1-card" aria-labelledby="case-files-title">
                <h2 class="a1-os-heading" id="case-files-title">Početni prilozi</h2>
                <ul class="a1-os-files">
                    @foreach($initialAttachments as $attachment)<li><a class="a1-os-file" href="{{ route('after-sales.attachments.show',$attachment) }}"><x-icon name="download" :size="16" :shell="true" /> {{ $attachment->original_name }}</a></li>@endforeach
                </ul>
            </section>
            @endif
        </aside>
    </div>
</div>
@endsection
