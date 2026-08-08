@extends('layouts.app')
@section('title', 'Novi postprodajni zahtev')
@section('content')
<a class="back-link" href="{{ route('orders.show', $order) }}">← Porudžbina {{ $order->order_number }}</a>
<div class="page-heading"><div><span class="eyebrow">Postprodajna podrška</span><h1>Otvori reklamaciju ili servis</h1><p>Opišite problem što preciznije i označite pogođene artikle.</p></div></div>
<form class="settings-grid after-sales-create-grid" method="post" action="{{ route('after-sales.store', $order) }}" enctype="multipart/form-data">
@csrf
<section class="panel form-section order-main-column">
    <h2>Vrsta i opis zahteva</h2>
    <div class="form-grid two-columns">
        <label><span>Vrsta zahteva</span><select name="case_type" required>@foreach($labels['types'] as $value=>$label)<option value="{{ $value }}" @selected(old('case_type')===$value)>{{ $label }}</option>@endforeach</select></label>
        <label><span>Prioritet</span><select name="priority" required>@foreach($labels['priorities'] as $value=>$label)<option value="{{ $value }}" @selected(old('priority','normal')===$value)>{{ $label }}</option>@endforeach</select></label>
        <label class="full-width"><span>Naslov</span><input name="subject" maxlength="190" required value="{{ old('subject') }}" placeholder="Npr. Oštećen naslon nakon isporuke"></label>
        <label class="full-width"><span>Detaljan opis</span><textarea name="description" minlength="20" maxlength="10000" required placeholder="Kada je problem primećen, gde se nalazi i kako izgleda?">{{ old('description') }}</textarea></label>
        <label class="full-width"><span>Željeno rešenje</span><select name="requested_resolution"><option value="">Bez unapred izabranog rešenja</option>@foreach($labels['resolutions'] as $value=>$label)@if($value!=='rejected')<option value="{{ $value }}" @selected(old('requested_resolution')===$value)>{{ $label }}</option>@endif @endforeach</select></label>
    </div>

    <h2 class="after-sales-section-title">Pogođene stavke</h2>
    <div class="after-sales-item-grid">
        @foreach($order->items as $item)
            <article class="after-sales-item-card">
                <label class="after-sales-item-check"><input type="checkbox" name="items[{{ $item->id }}][selected]" value="1" @checked(old('items.'.$item->id.'.selected'))><strong>{{ $item->product_name }}</strong></label>
                <small>{{ $item->product_sku ?: 'Bez SKU' }} · poručeno {{ $item->quantity }} kom.</small>
                <label><span>Količina</span><input type="number" min="1" max="{{ max(1,(int)$item->quantity) }}" name="items[{{ $item->id }}][quantity]" value="{{ old('items.'.$item->id.'.quantity',1) }}"></label>
                <label><span>Opis problema na stavci</span><textarea name="items[{{ $item->id }}][issue_description]" maxlength="2000">{{ old('items.'.$item->id.'.issue_description') }}</textarea></label>
            </article>
        @endforeach
    </div>
</section>
<aside class="form-side">
    <section class="panel form-section">
        <h2>Prilozi</h2><p class="muted">Fotografije ili PDF dokumentacija. Najviše 6 fajlova, do 10 MB po fajlu.</p>
        <input type="file" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png,.webp">
    </section>
    <section class="panel form-section"><h2>Podaci isporuke</h2><dl class="detail-list"><dt>Kupac</dt><dd>{{ $order->shipping_full_name ?: 'Kupac' }}</dd><dt>Telefon</dt><dd>{{ $order->shipping_phone ?: '—' }}</dd><dt>Adresa</dt><dd>{{ $order->shipping_address }}, {{ $order->shipping_postal_code }} {{ $order->shipping_city }}</dd></dl></section>
    <button class="button button-primary button-large" type="submit"><x-icon name="alert" /> Otvori slučaj</button>
</aside>
</form>
@endsection
