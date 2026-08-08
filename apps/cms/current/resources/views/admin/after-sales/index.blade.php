@extends('layouts.app')
@section('title', 'Reklamacije i servisi')
@section('content')
<div class="page-heading"><div><span class="eyebrow">Postprodajna podrška</span><h1>Reklamacije, povrati i servisi</h1><p>Centralni red za obradu problema nakon isporuke.</p></div></div>
<section class="filter-panel after-sales-filter-panel">
<form method="get" action="{{ route('admin.after-sales.index') }}" class="filter-grid">
    <label><span>Pretraga</span><input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Broj slučaja, porudžbina, naslov"></label>
    <label><span>Status</span><select name="status"><option value="">Svi statusi</option>@foreach($labels['statuses'] as $value=>$label)<option value="{{ $value }}" @selected(($filters['status']??'')===$value)>{{ $label }}</option>@endforeach</select></label>
    <label><span>Prioritet</span><select name="priority"><option value="">Svi prioriteti</option>@foreach($labels['priorities'] as $value=>$label)<option value="{{ $value }}" @selected(($filters['priority']??'')===$value)>{{ $label }}</option>@endforeach</select></label>
    <label><span>Tip</span><select name="case_type"><option value="">Svi tipovi</option>@foreach($labels['types'] as $value=>$label)<option value="{{ $value }}" @selected(($filters['case_type']??'')===$value)>{{ $label }}</option>@endforeach</select></label>
    <label class="checkbox-inline"><input type="checkbox" name="overdue" value="1" @checked(($filters['overdue']??'')==='1')><span>Samo probijeni rokovi</span></label>
    <label class="checkbox-inline"><input type="checkbox" name="execution_pending" value="1" @checked(($filters['execution_pending']??'')==='1')><span>Čeka izvršnu radnju</span></label>
    <div class="filter-actions"><button class="button button-primary" type="submit">Primeni</button><a class="button button-ghost" href="{{ route('admin.after-sales.index') }}">Resetuj</a></div>
</form>
</section>
<section class="panel form-section">
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Slučaj</th><th>Porudžbina / kupac</th><th>Tip</th><th>Prioritet</th><th>Status</th><th>Radnje</th><th>Odgovorno lice</th><th>Rok</th><th></th></tr></thead><tbody>
@forelse($cases as $case)
<tr class="{{ $case->due_at && $case->due_at->isPast() && !in_array($case->status,['resolved','rejected','closed'],true) ? 'row-overdue' : '' }}">
<td><strong>{{ $case->case_number }}</strong><small>{{ $case->subject }}</small></td>
<td><strong>{{ $case->order?->order_number ?? '—' }}</strong><small>{{ $case->customer_name_snapshot ?: 'Kupac' }}</small></td>
<td>{{ $labels['types'][$case->case_type] ?? $case->case_type }}</td>
<td><span class="priority-badge priority-{{ $case->priority }}">{{ $labels['priorities'][$case->priority] ?? $case->priority }}</span></td>
<td><span class="status-badge after-sales-status-{{ $case->status }}">{{ $labels['statuses'][$case->status] ?? $case->status }}</span></td>
<td>@if($case->pending_actions_count)<span class="status-badge action-badge-in_progress">{{ $case->pending_actions_count }} aktivnih</span>@else<span class="muted">—</span>@endif</td>
<td>{{ $case->assignee?->displayName() ?? 'Nije dodeljeno' }}</td>
<td>{{ $case->due_at?->format('d.m.Y H:i') ?? '—' }}</td>
<td><a class="button button-ghost button-small" href="{{ route('admin.after-sales.show',$case) }}">Obradi</a></td>
</tr>
@empty<tr><td colspan="9">Nema slučajeva za izabrane filtere.</td></tr>@endforelse
</tbody></table></div>{{ $cases->links() }}
</section>
@endsection
