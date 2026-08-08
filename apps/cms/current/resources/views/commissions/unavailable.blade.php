@extends('layouts.app')
@section('title', 'Provizije trenutno nisu dostupne')
@section('content')
<section class="panel operation-unavailable-panel">
    <span class="eyebrow">Operational Orders & Commissions</span>
    <h1>Provizije trenutno nisu spremne.</h1>
    <p>Operativna šema nije potpuno primenjena. Podaci nisu menjani.</p>
    @if(!empty($issues))
        <ul class="validation-list">@foreach($issues as $issue)<li>{{ $issue }}</li>@endforeach</ul>
    @endif
    @if(!empty($incident))<p class="muted">Incident: <code>{{ $incident }}</code></p>@endif
    @if(!empty($isAdmin))
        <pre class="command-box">php artisan app:operations-doctor --repair --render</pre>
    @else
        <p class="muted">Administrator je obavešten da završi produkcionu migraciju.</p>
    @endif
</section>
@endsection
