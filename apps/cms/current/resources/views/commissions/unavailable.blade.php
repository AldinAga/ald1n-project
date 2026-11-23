@extends('layouts.app')
@section('title', 'Provizije trenutno nisu dostupne')
@section('content')
<div class="a1-ops">
    <section class="a1-card" aria-labelledby="cu-title">
        <span class="a1-eyebrow">Porudžbine i provizije</span>
        <h1 class="a1-title" id="cu-title">Provizije trenutno nisu spremne.</h1>
        <p class="a1-lead">Operativna šema nije potpuno primenjena. Podaci nisu menjani.</p>
        @if(!empty($issues))
        <div class="a1-alert a1-alert--warning" role="status"><div class="a1-alert__body"><ul>@foreach($issues as $issue)<li>{{ $issue }}</li>@endforeach</ul></div></div>
        @endif
        @if(!empty($incident))<p class="a1-muted">Incident: <code>{{ $incident }}</code></p>@endif
        @if(!empty($isAdmin))
        <p class="a1-muted">Pokreni na serveru:</p>
        <pre class="a1-ol-code"><code>php artisan app:operations-doctor --repair --render</code></pre>
        @else
        <p class="a1-muted">Administrator je obavešten da završi produkcionu migraciju.</p>
        @endif
    </section>
</div>
@endsection
