@extends('layouts.app')
@section('title', $case->case_number)
@section('content')
<a class="back-link" href="{{ route('after-sales.index') }}">← Moje reklamacije i servisi</a>
<div class="page-heading"><div><span class="eyebrow">{{ $labels['types'][$case->case_type] ?? $case->case_type }}</span><h1>{{ $case->case_number }}</h1><p>{{ $case->subject }} · porudžbina {{ $case->order?->order_number }}</p></div><span class="status-badge after-sales-status-{{ $case->status }}">{{ $labels['statuses'][$case->status] ?? $case->status }}</span></div>
<div class="settings-grid operational-order-grid">
<section class="order-main-column">
    <section class="panel form-section"><h2>Opis zahteva</h2><p class="after-sales-description">{{ $case->description }}</p><div class="after-sales-item-grid compact">@foreach($case->items as $item)<article class="after-sales-item-card"><strong>{{ $item->product_name_snapshot }}</strong><small>{{ $item->sku_snapshot ?: 'Bez SKU' }} · {{ $item->quantity }} kom.</small>@if($item->issue_description)<p>{{ $item->issue_description }}</p>@endif</article>@endforeach</div></section>

    @if($case->actions->isNotEmpty())
    <section class="panel form-section after-sales-actions-panel"><div class="section-heading-row"><div><h2>Planirane i izvršene radnje</h2><p class="muted">Ovde možete pratiti servis, zamenu, povrat ili refundaciju povezanu sa slučajem.</p></div></div><div class="after-sales-action-list">
        @foreach($case->actions as $action)<article class="after-sales-action-card action-status-{{ $action->status }}"><div class="after-sales-action-head"><div><span class="eyebrow">{{ $actionLabels['types'][$action->action_type] ?? $action->action_type }}</span><h3>{{ $action->action_number }}</h3></div><span class="status-badge action-badge-{{ $action->status }}">{{ $actionLabels['statuses'][$action->status] ?? $action->status }}</span></div><dl class="detail-list compact">@if($action->scheduled_at)<dt>Termin</dt><dd>{{ $action->scheduled_at->format('d.m.Y H:i') }}</dd>@endif @if($action->due_at)<dt>Rok</dt><dd>{{ $action->due_at->format('d.m.Y H:i') }}</dd>@endif @if($action->reference)<dt>Referenca</dt><dd>{{ $action->reference }}</dd>@endif @if($action->amount_rsd)<dt>Iznos</dt><dd>{{ number_format((float)$action->amount_rsd,2,',','.') }} RSD</dd>@endif</dl><div class="after-sales-action-items">@foreach($action->items as $item)<span><strong>{{ $item->product_name_snapshot }}</strong> · {{ $item->quantity }} kom.</span>@endforeach</div>@if($action->public_note)<p>{{ $action->public_note }}</p>@endif @if($action->completion_note)<p><strong>Završna napomena:</strong> {{ $action->completion_note }}</p>@endif @if($action->workOrder)<div class="customer-field-work-order"><strong>Terenski nalog {{ $action->workOrder->work_order_number }}</strong><span>{{ \App\Models\FieldWorkOrder::statusLabels()[$action->workOrder->status] ?? $action->workOrder->status }}</span>@if($action->workOrder->planned_start_at)<small>Termin: {{ $action->workOrder->planned_start_at->format('d.m.Y H:i') }}@if($action->workOrder->planned_end_at)–{{ $action->workOrder->planned_end_at->format('H:i') }}@endif</small>@endif @if($action->workOrder->team)<small>Ekipa: {{ $action->workOrder->team->name }}@if($action->workOrder->team->phone) · {{ $action->workOrder->team->phone }}@endif</small>@endif @if($action->workOrder->completion_result)<p>{{ $action->workOrder->completion_result }}</p>@endif @foreach($action->workOrder->attachments->where('visibility','public') as $attachment)<a class="button button-ghost button-small" href="{{ route('field-work-order-attachments.show',$attachment) }}"><x-icon name="download" /> {{ $attachment->original_name }}</a>@endforeach</div>@endif</article>@endforeach
    </div></section>
    @endif

    <section class="panel form-section"><div class="section-heading-row"><div><h2>Komunikacija</h2><p class="muted">Sve poruke u ovoj sekciji vidljive su vama i odgovornom administratoru.</p></div></div>
        <div class="after-sales-thread">
            @forelse($case->messages as $message)
                <article class="after-sales-message {{ $message->user?->hasRole('admin','superadmin') ? 'is-admin' : 'is-user' }}"><div class="after-sales-message-head"><strong>{{ $message->user?->displayName() ?? 'Korisnik' }}</strong><time>{{ $message->created_at?->format('d.m.Y H:i') }}</time></div><p>{{ $message->body }}</p>@if($message->attachments->isNotEmpty())<div class="after-sales-attachments">@foreach($message->attachments as $attachment)<a href="{{ route('after-sales.attachments.show',$attachment) }}"><x-icon name="download" /> {{ $attachment->original_name }}</a>@endforeach</div>@endif</article>
            @empty <p class="muted">Još nema poruka.</p> @endforelse
        </div>
        @unless($case->isClosed())
        <form class="after-sales-message-form" method="post" action="{{ route('after-sales.messages.store',$case) }}" enctype="multipart/form-data">@csrf<label><span>Nova poruka</span><textarea name="body" required maxlength="10000"></textarea></label><label><span>Prilozi</span><input type="file" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png,.webp"></label><button class="button button-primary" type="submit">Pošalji poruku</button></form>
        @endunless
    </section>
</section>
<aside class="form-side">
    <section class="panel form-section"><h2>Pregled slučaja</h2><dl class="detail-list"><dt>Status</dt><dd>{{ $labels['statuses'][$case->status] ?? $case->status }}</dd><dt>Prioritet</dt><dd>{{ $labels['priorities'][$case->priority] ?? $case->priority }}</dd><dt>Odgovorno lice</dt><dd>{{ $case->assignee?->displayName() ?? 'Nije dodeljeno' }}</dd><dt>Rok odgovora</dt><dd>{{ $case->due_at?->format('d.m.Y H:i') ?? '—' }}</dd><dt>Željeno rešenje</dt><dd>{{ $labels['resolutions'][$case->requested_resolution] ?? 'Nije navedeno' }}</dd></dl></section>
    @if($case->resolution_summary)<section class="panel form-section after-sales-resolution"><h2>Odluka / rešenje</h2><strong>{{ $labels['resolutions'][$case->resolution_type] ?? 'Rešenje' }}</strong><p>{{ $case->resolution_summary }}</p></section>@endif
    @if($case->attachments->whereNull('message_id')->isNotEmpty())<section class="panel form-section"><h2>Početni prilozi</h2><div class="after-sales-attachments">@foreach($case->attachments->whereNull('message_id') as $attachment)<a href="{{ route('after-sales.attachments.show',$attachment) }}"><x-icon name="download" />{{ $attachment->original_name }}</a>@endforeach</div></section>@endif
</aside>
</div>
@endsection
