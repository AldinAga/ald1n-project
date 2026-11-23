@extends('layouts.app')
@section('title','Uvoz artikala iz CSV-a')
@section('content')
@include('admin.products.partials.styles')
<div class="a1-ops">
<nav aria-label="Putanja">
    <ol class="a1-crumbs">
        <li><a class="back-link" href="{{ route('catalog.index') }}">Nazad na artikle</a></li>
        <li aria-current="page">Uvoz iz CSV-a</li>
    </ol>
</nav>
<header class="a1-pagehead">
    <div class="a1-pagehead__text">
        <span class="a1-eyebrow">Pametno upravljanje katalogom</span>
        <h1 class="a1-title">Uvoz artikala iz CSV-a</h1>
        <p class="a1-lead">Uvezeni artikli uvek ulaze kao nacrt sa lagerom 0. Prvo se mapiraju kolone i pregleda uvoz, a tek zatim potvrđuje.</p>
    </div>
</header>

@if(!empty($result))
<section class="a1-alert a1-alert--ok" role="status" data-import-result>
    <div class="a1-alert__body">
        <p class="a1-alert__title">Uvoz je završen</p>
        <p>Uvezeno artikala: <strong>{{ $result['created'] }}</strong>. Preskočeno duplikata: <strong>{{ $result['skipped'] }}</strong>. Redova sa greškom: <strong>{{ count($result['errors']) }}</strong>.@if(!empty($result['resumed'])) Već uvezeno u prekinutom pokušaju: <strong>{{ $result['resumed'] }}</strong>.@endif</p>
        @if(!empty($result['errors']))
            <ul class="a1-keylist">@foreach(array_slice($result['errors'], 0, 50) as $error)<li>Red {{ $error['line'] }}: {{ $error['message'] }}</li>@endforeach</ul>
        @endif
        <p><a class="a1-btn a1-btn--primary" href="{{ route('catalog.index', ['ownership' => 'mine']) }}">Otvori moje artikle</a></p>
    </div>
</section>
@endif

@if($errors->has('file'))<div class="a1-alert a1-alert--danger" role="alert"><div class="a1-alert__body"><p>{{ $errors->first('file') }}</p></div></div>@endif

@if(empty($token))
<form method="post" action="{{ route('admin.products.import.upload') }}" enctype="multipart/form-data" class="a1-form">@csrf
    <section class="a1-card" aria-labelledby="im-file">
        <h2 class="a1-h3" id="im-file">1. Izaberi fajl</h2>
        <p class="a1-text">CSV ili TXT do 1 MB i najviše {{ \App\Services\ProductImportService::MAX_ROWS }} redova. Prvi red je zaglavlje. Razdvajač (; , ili tab) i kodiranje (UTF-8 ili Windows-1250) prepoznaju se automatski.</p>
        <label class="a1-field"><span class="a1-label">Fajl</span><input class="a1-input" type="file" name="file" accept=".csv,.txt,text/csv,text/plain" required></label>
        <div><button class="a1-btn a1-btn--primary" type="submit">Otpremi i mapiraj kolone</button></div>
    </section>
</form>
@else
<form method="post" action="{{ route('admin.products.import.process') }}" class="a1-form">@csrf
    <input type="hidden" name="token" value="{{ $token }}">
    @if(!empty($analysis))<input type="hidden" name="mapping_hash" value="{{ $mappingHash }}" data-import-mapping-hash>@endif
    @if($errors->has('mapping'))<div class="a1-alert a1-alert--danger" role="alert"><div class="a1-alert__body"><p>{{ $errors->first('mapping') }}</p></div></div>@endif
    <div class="a1-stack">
        <section class="a1-card" aria-labelledby="im-map">
            <h2 class="a1-h3" id="im-map">2. Mapiranje kolona</h2>
            <p class="a1-text">Fajl ima {{ $rowCount }} redova. Za svako polje izaberi kolonu iz fajla ili ostavi „Ne uvozi“. Šifra artikla (SKU) se uvek generiše automatski.</p>
            <p class="a1-help" data-import-price-rule>Pravilo za cene: tačka iza koje slede tačno tri cifre je separator hiljada („12.500“ je 12500, „1.234,50“ je 1234,50), a decimalni separator je zarez („12,5“ je 12,5). Engleski zapis sa zarezom kao hiljadama („1,234“) se čita kao 1,234; takve dvosmislene cene označene su upozorenjem u pregledu.</p>
            <div class="a1-pc-grid">
                @foreach(\App\Services\ProductImportService::FIELDS as $field => $label)
                    <label class="a1-field"><span class="a1-label">{{ $label }}</span>
                        <select class="a1-select" name="mapping[{{ $field }}]">
                            <option value="">Ne uvozi</option>
                            @foreach($headers as $index => $header)<option value="{{ $index }}" @selected((string) ($mapping[$field] ?? '') === (string) $index)>{{ $header !== '' ? $header : 'Kolona '.($index + 1) }}</option>@endforeach
                        </select>
                    </label>
                @endforeach
                <label class="a1-field"><span class="a1-label">Podrazumevana valuta</span><select class="a1-select" name="default_currency">@foreach(['EUR','RSD'] as $cur)<option value="{{ $cur }}" @selected($currency === $cur)>{{ $cur }}</option>@endforeach</select></label>
                <label class="a1-check"><input class="a1-check__input" type="checkbox" name="skip_duplicates" value="1" checked><span class="a1-check__box" aria-hidden="true"></span><span>Preskoči duplikate pri uvozu</span></label>
            </div>
            @if(!empty($sample))
                <h3 class="a1-h3">Primer prvih redova</h3>
                <div class="a1-tablewrap" role="region" aria-label="Primer prvih redova" tabindex="0"><table class="a1-table"><caption class="a1-sr">Primer prvih redova</caption><thead><tr>@foreach($headers as $header)<th scope="col">{{ $header }}</th>@endforeach</tr></thead><tbody>@foreach($sample as $row)<tr>@foreach($row as $value)<td>{{ $value }}</td>@endforeach</tr>@endforeach</tbody></table></div>
            @endif
        </section>
        @if(!empty($analysis))
        <section class="a1-card" aria-labelledby="im-preview" data-import-preview>
            <h2 class="a1-h3" id="im-preview">3. Pregled uvoza</h2>
            <p class="a1-text"><strong>{{ $analysis['valid'] }}</strong> redova je spremno, <strong>{{ $analysis['errors'] }}</strong> ima greške (biće preskočeno), <strong>{{ $analysis['duplicates'] }}</strong> upozorenja na duplikate.</p>
            <div class="a1-tablewrap" role="region" aria-label="Pregled uvoza" tabindex="0"><table class="a1-table"><caption class="a1-sr">Pregled uvoza</caption><thead><tr><th scope="col">Red</th><th scope="col">Artikal</th><th scope="col" class="a1-table__num">Cena</th><th scope="col">Napomene</th></tr></thead><tbody>
                @foreach(array_slice($analysis['rows'], 0, 200) as $row)
                    <tr><td>{{ $row['line'] }}</td><td>{{ $row['label'] }}</td><td class="a1-table__num" data-import-price>{{ $row['data']['price_amount'] }} {{ $row['data']['price_currency'] }}</td><td>@foreach($row['errors'] as $message)<p class="a1-error">{{ $message }}</p>@endforeach @foreach($row['warnings'] as $message)<p class="a1-muted-line">{{ $message }}</p>@endforeach @if(empty($row['errors']) && empty($row['warnings']))<span class="a1-pill a1-pill--ok">U redu</span> @endif</td></tr>
                @endforeach
            </tbody></table></div>
            @if(count($analysis['rows']) > 200)<p class="a1-muted">Prikazano je prvih 200 redova; izvršenje obrađuje sve.</p>@endif
        </section>
        @endif
    </div>
    <div class="a1-actionbar">
        <p class="a1-actionbar__status">Svaki uvezeni artikal dobija audit zapis. Redovi sa greškom se ne uvoze.</p>
        <div class="a1-actionbar__buttons">
        @if(!empty($analysis))
            <button class="a1-btn" name="mode" value="preview">Osveži pregled</button>
            <button class="a1-btn a1-btn--primary" name="mode" value="execute" data-confirm-submit="Uvesti {{ $analysis['valid'] }} artikala kao nacrte?">Uvezi artikle</button>
        @else
            <button class="a1-btn a1-btn--primary" name="mode" value="preview">Pregledaj uvoz</button>
        @endif
        </div>
    </div>
</form>
@endif
</div>
@endsection
