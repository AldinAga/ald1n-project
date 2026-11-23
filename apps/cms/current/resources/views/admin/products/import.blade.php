@extends('layouts.app')
@section('title','Uvoz artikala iz CSV-a')
@section('content')
<a class="back-link" href="{{ route('catalog.index') }}">← Nazad na artikle</a>
<div class="page-heading"><div><span class="eyebrow">Pametno upravljanje katalogom</span><h1>Uvoz artikala iz CSV-a</h1><p>Uvezeni artikli uvek ulaze kao nacrt sa lagerom 0. Prvo se mapiraju kolone i pregleda uvoz, a tek zatim potvrđuje.</p></div></div>

@if(!empty($result))
<section class="panel form-section" role="status" data-import-result>
    <h2>Uvoz je završen</h2>
    <p>Uvezeno artikala: <strong>{{ $result['created'] }}</strong>. Preskočeno duplikata: <strong>{{ $result['skipped'] }}</strong>. Redova sa greškom: <strong>{{ count($result['errors']) }}</strong>.@if(!empty($result['resumed'])) Već uvezeno u prekinutom pokušaju: <strong>{{ $result['resumed'] }}</strong>.@endif</p>
    @if(!empty($result['errors']))
        <ul>@foreach(array_slice($result['errors'], 0, 50) as $error)<li>Red {{ $error['line'] }}: {{ $error['message'] }}</li>@endforeach</ul>
    @endif
    <a class="button button-primary" href="{{ route('catalog.index', ['ownership' => 'mine']) }}">Otvori moje artikle</a>
</section>
@endif

@if($errors->has('file'))<div class="alert alert-error" role="alert">{{ $errors->first('file') }}</div>@endif

@if(empty($token))
<form method="post" action="{{ route('admin.products.import.upload') }}" enctype="multipart/form-data">@csrf
    <section class="panel form-section">
        <h2>1. Izaberi fajl</h2>
        <p class="muted">CSV ili TXT do 1 MB i najviše {{ \App\Services\ProductImportService::MAX_ROWS }} redova. Prvi red je zaglavlje. Razdvajač (; , ili tab) i kodiranje (UTF-8 ili Windows-1250) prepoznaju se automatski.</p>
        <label><span>Fajl</span><input type="file" name="file" accept=".csv,.txt,text/csv,text/plain" required></label>
        <button class="button button-primary" type="submit">Otpremi i mapiraj kolone</button>
    </section>
</form>
@else
<form method="post" action="{{ route('admin.products.import.process') }}">@csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div class="admin-form-grid"><div class="form-main">
        <section class="panel form-section">
            <h2>2. Mapiranje kolona</h2>
            <p class="muted">Fajl ima {{ $rowCount }} redova. Za svako polje izaberi kolonu iz fajla ili ostavi „Ne uvozi“. Šifra artikla (SKU) se uvek generiše automatski.</p>
            <div class="field-grid">
                @foreach(\App\Services\ProductImportService::FIELDS as $field => $label)
                    <label><span>{{ $label }}</span>
                        <select name="mapping[{{ $field }}]">
                            <option value="">Ne uvozi</option>
                            @foreach($headers as $index => $header)<option value="{{ $index }}" @selected((string) ($mapping[$field] ?? '') === (string) $index)>{{ $header !== '' ? $header : 'Kolona '.($index + 1) }}</option>@endforeach
                        </select>
                    </label>
                @endforeach
                <label><span>Podrazumevana valuta</span><select name="default_currency">@foreach(['EUR','RSD'] as $cur)<option value="{{ $cur }}" @selected($currency === $cur)>{{ $cur }}</option>@endforeach</select></label>
                <label class="check-card"><input type="checkbox" name="skip_duplicates" value="1" checked><span>Preskoči duplikate pri uvozu</span></label>
            </div>
            @if(!empty($sample))
                <h3>Primer prvih redova</h3>
                <div class="table-wrap"><table class="data-table"><thead><tr>@foreach($headers as $header)<th>{{ $header }}</th>@endforeach</tr></thead><tbody>@foreach($sample as $row)<tr>@foreach($row as $value)<td>{{ $value }}</td>@endforeach</tr>@endforeach</tbody></table></div>
            @endif
        </section>
        @if(!empty($analysis))
        <section class="panel form-section preview-panel" data-import-preview>
            <h2>3. Pregled uvoza</h2>
            <p><strong>{{ $analysis['valid'] }}</strong> redova je spremno, <strong>{{ $analysis['errors'] }}</strong> ima greške (biće preskočeno), <strong>{{ $analysis['duplicates'] }}</strong> upozorenja na duplikate.</p>
            <div class="table-wrap"><table class="data-table"><thead><tr><th>Red</th><th>Artikal</th><th>Cena</th><th>Napomene</th></tr></thead><tbody>
                @foreach(array_slice($analysis['rows'], 0, 200) as $row)
                    <tr><td>{{ $row['line'] }}</td><td>{{ $row['label'] }}</td><td data-import-price>{{ $row['data']['price_amount'] }} {{ $row['data']['price_currency'] }}</td><td>@foreach($row['errors'] as $message)<div class="text-danger">{{ $message }}</div>@endforeach @foreach($row['warnings'] as $message)<div class="muted">{{ $message }}</div>@endforeach @if(empty($row['errors']) && empty($row['warnings']))U redu @endif</td></tr>
                @endforeach
            </tbody></table></div>
            @if(count($analysis['rows']) > 200)<p class="muted">Prikazano je prvih 200 redova; izvršenje obrađuje sve.</p>@endif
        </section>
        @endif
    </div><aside class="form-side"><section class="panel form-section sticky-card">
        <h2>Izvršenje</h2>
        <p class="muted">Svaki uvezeni artikal dobija audit zapis. Redovi sa greškom se ne uvoze.</p>
        @if(!empty($analysis))
            <button class="button button-primary button-large" name="mode" value="execute" data-confirm-submit="Uvesti {{ $analysis['valid'] }} artikala kao nacrte?">Uvezi artikle</button>
            <button class="button button-ghost" name="mode" value="preview">Osveži pregled</button>
        @else
            <button class="button button-primary button-large" name="mode" value="preview">Pregledaj uvoz</button>
        @endif
    </section></aside></div>
</form>
@endif
@endsection
