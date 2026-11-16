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
    $actionTone = static fn (string $status): string => match ($status) {
        'completed' => ' a1-pill--ok',
        'cancelled' => ' a1-pill--danger',
        'planned' => ' a1-pill--warning',
        'in_progress' => ' a1-pill--info',
        default => '',
    };
    $initialAttachments = $case->attachments->whereNull('message_id');
@endphp
<div class="a1-ops" data-after-sales-show="1">
    <nav aria-label="Putanja">
        <ol class="a1-crumbs">
            <li><a href="{{ route('after-sales.index') }}">Moje reklamacije i servisi</a></li>
            <li aria-current="page">{{ $case->case_number }}</li>
        </ol>
    </nav>

    <section class="a1-card" aria-labelledby="case-title">
        <div class="a1-card__head">
            <div class="a1-pagehead__text">
                <span class="a1-eyebrow">{{ $labels['types'][$case->case_type] ?? $case->case_type }}</span>
                <h1 class="a1-title" id="case-title">{{ $case->case_number }}</h1>
                <p class="a1-lead">{{ $case->subject }} · porudžbina {{ $case->order?->order_number }}</p>
            </div>
            <span class="a1-pill{{ $statusTone((string) $case->status) }}">{{ $labels['statuses'][$case->status] ?? $case->status }}</span>
        </div>
    </section>

    <div class="a1-split">
        <div class="a1-stack">
            <section class="a1-card" aria-labelledby="case-description-title">
                <h2 class="a1-os-heading" id="case-description-title">Opis zahteva</h2>
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

            @if($case->actions->isNotEmpty())
            <section class="a1-card" aria-labelledby="case-actions-title">
                <div class="a1-os-block">
                    <h2 class="a1-os-heading" id="case-actions-title">Planirane i izvršene radnje</h2>
                    <p class="a1-os-lead">Ovde možete pratiti servis, zamenu, povrat ili refundaciju povezanu sa slučajem.</p>
                </div>
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
                        @if($action->scheduled_at || $action->due_at || $action->reference || $action->amount_rsd)
                        <dl class="a1-dl">
                            @if($action->scheduled_at)<div class="a1-dl__row"><dt>Termin</dt><dd>{{ $action->scheduled_at->format('d.m.Y H:i') }}</dd></div>@endif
                            @if($action->due_at)<div class="a1-dl__row"><dt>Rok</dt><dd>{{ $action->due_at->format('d.m.Y H:i') }}</dd></div>@endif
                            @if($action->reference)<div class="a1-dl__row"><dt>Referenca</dt><dd>{{ $action->reference }}</dd></div>@endif
                            @if($action->amount_rsd)<div class="a1-dl__row"><dt>Iznos</dt><dd>{{ number_format((float)$action->amount_rsd,2,',','.') }} RSD</dd></div>@endif
                        </dl>
                        @endif
                        @if($action->items->isNotEmpty())
                        <ul class="a1-os-action__lines">
                            @foreach($action->items as $item)<li><strong>{{ $item->product_name_snapshot }}</strong> · {{ $item->quantity }} kom.</li>@endforeach
                        </ul>
                        @endif
                        @if($action->public_note)<p class="a1-os-note">{{ $action->public_note }}</p>@endif
                        @if($action->completion_note)<p class="a1-os-note"><strong>Završna napomena:</strong> {{ $action->completion_note }}</p>@endif
                        @if($action->workOrder)
                        <div class="a1-os-wo">
                            <div class="a1-os-wo__text">
                                <strong>Terenski nalog {{ $action->workOrder->work_order_number }}</strong>
                                <span>{{ \App\Models\FieldWorkOrder::statusLabels()[$action->workOrder->status] ?? $action->workOrder->status }}</span>
                                @if($action->workOrder->planned_start_at)<span class="a1-os-item__meta">Termin: {{ $action->workOrder->planned_start_at->format('d.m.Y H:i') }}@if($action->workOrder->planned_end_at)–{{ $action->workOrder->planned_end_at->format('H:i') }}@endif</span>@endif
                                @if($action->workOrder->team)<span class="a1-os-item__meta">Ekipa: {{ $action->workOrder->team->name }}@if($action->workOrder->team->phone) · {{ $action->workOrder->team->phone }}@endif</span>@endif
                                @if($action->workOrder->completion_result)<p class="a1-os-text">{{ $action->workOrder->completion_result }}</p>@endif
                            </div>
                            @php $publicWorkOrderFiles = $action->workOrder->attachments->where('visibility', 'public'); @endphp
                            @if($publicWorkOrderFiles->isNotEmpty())
                            <ul class="a1-os-files">
                                @foreach($publicWorkOrderFiles as $attachment)
                                <li><a class="a1-os-file" href="{{ route('field-work-order-attachments.show',$attachment) }}"><x-icon name="download" :size="16" :shell="true" /> {{ $attachment->original_name }}</a></li>
                                @endforeach
                            </ul>
                            @endif
                        </div>
                        @endif
                    </li>
                    @endforeach
                </ul>
            </section>
            @endif

            <section class="a1-card" aria-labelledby="case-thread-title">
                <div class="a1-os-block">
                    <h2 class="a1-os-heading" id="case-thread-title">Komunikacija</h2>
                    <p class="a1-os-lead">Sve poruke u ovoj sekciji vidljive su vama i odgovornom administratoru.</p>
                </div>
                @if($case->messages->isNotEmpty())
                <ol class="a1-os-thread">
                    @foreach($case->messages as $message)
                    <li class="a1-os-msg {{ $message->user?->hasRole('admin','superadmin') ? 'a1-os-msg--staff' : 'a1-os-msg--customer' }}">
                        <div class="a1-os-msg__head">
                            <span class="a1-os-msg__who">{{ $message->user?->displayName() ?? 'Korisnik' }}</span>
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
                    <p class="a1-os-muted">Još nema poruka.</p>
                @endif

                @unless($case->isClosed())
                <form class="a1-form" method="post" action="{{ route('after-sales.messages.store',$case) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="a1-field">
                        <label class="a1-label" for="case-body">Nova poruka <span class="a1-req" aria-hidden="true">*</span></label>
                        <textarea class="a1-textarea" id="case-body" name="body" required maxlength="10000" @if($errors->has('body')) aria-invalid="true" aria-describedby="case-body-err" @endif></textarea>
                        @if($errors->has('body'))<p class="a1-error" id="case-body-err"><x-icon name="alert" :size="16" :shell="true" /> {{ $errors->first('body') }}</p>@endif
                    </div>
                    <label class="a1-drop" for="case-files">
                        <input class="a1-drop__input" id="case-files" type="file" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png,.webp" aria-describedby="case-files-help">
                        <x-icon name="plus-circle" :size="24" :shell="true" />
                        <span class="a1-drop__title">Prilozi</span>
                        <span id="case-files-help">PDF, JPG, PNG ili WebP</span>
                    </label>
                    <div>
                        <button class="a1-btn a1-btn--primary" type="submit">Pošalji poruku</button>
                    </div>
                </form>
                @endunless
            </section>
        </div>

        <aside class="a1-stack" aria-label="Pregled slučaja">
            <section class="a1-card" aria-labelledby="case-summary-title">
                <h2 class="a1-os-heading" id="case-summary-title">Pregled slučaja</h2>
                <dl class="a1-dl">
                    <div class="a1-dl__row"><dt>Status</dt><dd>{{ $labels['statuses'][$case->status] ?? $case->status }}</dd></div>
                    <div class="a1-dl__row"><dt>Prioritet</dt><dd>{{ $labels['priorities'][$case->priority] ?? $case->priority }}</dd></div>
                    <div class="a1-dl__row"><dt>Odgovorno lice</dt><dd>{{ $case->assignee?->displayName() ?? 'Nije dodeljeno' }}</dd></div>
                    <div class="a1-dl__row"><dt>Rok odgovora</dt><dd>{{ $case->due_at?->format('d.m.Y H:i') ?? '—' }}</dd></div>
                    <div class="a1-dl__row"><dt>Željeno rešenje</dt><dd>{{ $labels['resolutions'][$case->requested_resolution] ?? 'Nije navedeno' }}</dd></div>
                </dl>
            </section>
            @if($case->resolution_summary)
            <section class="a1-card" aria-labelledby="case-resolution-title">
                <h2 class="a1-os-heading" id="case-resolution-title">Odluka / rešenje</h2>
                <strong>{{ $labels['resolutions'][$case->resolution_type] ?? 'Rešenje' }}</strong>
                <p class="a1-os-text">{{ $case->resolution_summary }}</p>
            </section>
            @endif
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
