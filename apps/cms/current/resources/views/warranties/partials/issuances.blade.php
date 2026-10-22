@if(count($issuedCertificates ?? []) > 0)
<section class="panel form-section" aria-label="Izdati garantni listovi pre isporuke">
<div class="card-header-row"><div><span class="eyebrow">Prva uplata potvrđena</span><h2>Garantni listovi — čeka se isporuka</h2></div></div>
<p>Broj i uslovi su sačuvani. Važenje garancije počinje evidentiranom isporukom.</p>
<div class="card-grid">
@foreach($issuedCertificates as $issued)
<article class="panel form-section"><small>{{ $issued['number'] }}</small><h3>{{ $issued['product'] }}</h3><p>{{ $issued['order_number'] }} · {{ $issued['issued_at'] }}</p>
<div class="header-button-row"><a class="button button-ghost" href="{{ $issued['show_url'] }}">Detalji</a><a class="button button-primary" href="{{ $issued['pdf_url'] }}" target="_blank" rel="noopener noreferrer">Garantni list PDF</a></div></article>
@endforeach
</div>
</section>
@endif
