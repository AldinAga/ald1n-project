@extends('layouts.app')
@section('title', 'Novi postprodajni zahtev')
@section('content')
<div class="a1-ops" data-after-sales-create="1">
    <nav aria-label="Putanja">
        <ol class="a1-crumbs">
            <li><a href="{{ route('orders.show', $order) }}">Porudžbina {{ $order->order_number }}</a></li>
            <li aria-current="page">Novi postprodajni zahtev</li>
        </ol>
    </nav>

    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Postprodajna podrška</span>
            <h1 class="a1-title">Otvori reklamaciju ili servis</h1>
            <p class="a1-lead">Opišite problem što preciznije i označite pogođene artikle.</p>
        </div>
    </header>

    <form class="a1-form" method="post" action="{{ route('after-sales.store', $order) }}" enctype="multipart/form-data">
        @csrf
        <div class="a1-split">
            <div class="a1-stack">
                <section class="a1-card" aria-labelledby="create-details-title">
                    <h2 class="a1-os-heading" id="create-details-title">Vrsta i opis zahteva</h2>
                    <div class="a1-os-formgrid">
                        <div class="a1-field">
                            <label class="a1-label" for="case-type">Vrsta zahteva <span class="a1-req" aria-hidden="true">*</span></label>
                            <select class="a1-select" id="case-type" name="case_type" required>@foreach($labels['types'] as $value=>$label)<option value="{{ $value }}" @selected(old('case_type')===$value)>{{ $label }}</option>@endforeach</select>
                        </div>
                        <div class="a1-field">
                            <label class="a1-label" for="case-priority">Prioritet <span class="a1-req" aria-hidden="true">*</span></label>
                            <select class="a1-select" id="case-priority" name="priority" required>@foreach($labels['priorities'] as $value=>$label)<option value="{{ $value }}" @selected(old('priority','normal')===$value)>{{ $label }}</option>@endforeach</select>
                        </div>
                        <div class="a1-field a1-os-wide">
                            <label class="a1-label" for="case-subject">Naslov <span class="a1-req" aria-hidden="true">*</span></label>
                            <input class="a1-input" id="case-subject" name="subject" maxlength="190" required value="{{ old('subject') }}" placeholder="Npr. Oštećen naslon nakon isporuke" @if($errors->has('subject')) aria-invalid="true" aria-describedby="case-subject-err" @endif>
                            @if($errors->has('subject'))<p class="a1-error" id="case-subject-err"><x-icon name="alert" :size="16" :shell="true" /> {{ $errors->first('subject') }}</p>@endif
                        </div>
                        <div class="a1-field a1-os-wide">
                            <label class="a1-label" for="case-description">Detaljan opis <span class="a1-req" aria-hidden="true">*</span></label>
                            <textarea class="a1-textarea" id="case-description" name="description" minlength="20" maxlength="10000" required placeholder="Kada je problem primećen, gde se nalazi i kako izgleda?" @if($errors->has('description')) aria-invalid="true" aria-describedby="case-description-err" @endif>{{ old('description') }}</textarea>
                            @if($errors->has('description'))<p class="a1-error" id="case-description-err"><x-icon name="alert" :size="16" :shell="true" /> {{ $errors->first('description') }}</p>@endif
                        </div>
                        <div class="a1-field a1-os-wide">
                            <label class="a1-label" for="case-resolution">Željeno rešenje</label>
                            <select class="a1-select" id="case-resolution" name="requested_resolution"><option value="">Bez unapred izabranog rešenja</option>@foreach($labels['resolutions'] as $value=>$label)@if($value!=='rejected')<option value="{{ $value }}" @selected(old('requested_resolution')===$value)>{{ $label }}</option>@endif @endforeach</select>
                        </div>
                    </div>
                </section>

                <section class="a1-card" aria-labelledby="create-items-title">
                    <h2 class="a1-os-heading" id="create-items-title">Pogođene stavke</h2>
                    <div class="a1-os-items">
                        @foreach($order->items as $item)
                        <article class="a1-os-item">
                            <label class="a1-check">
                                <input class="a1-check__input" type="checkbox" name="items[{{ $item->id }}][selected]" value="1" @checked(old('items.'.$item->id.'.selected'))>
                                <span class="a1-check__box" aria-hidden="true"></span>
                                <strong class="a1-os-item__name">{{ $item->product_name }}</strong>
                            </label>
                            <p class="a1-os-item__meta">{{ $item->product_sku ?: 'Bez SKU' }} · poručeno {{ $item->quantity }} kom.</p>
                            <div class="a1-field">
                                <label class="a1-label" for="item-{{ $item->id }}-qty">Količina</label>
                                <input class="a1-input" id="item-{{ $item->id }}-qty" type="number" min="1" max="{{ max(1,(int)$item->quantity) }}" name="items[{{ $item->id }}][quantity]" value="{{ old('items.'.$item->id.'.quantity',1) }}">
                            </div>
                            <div class="a1-field">
                                <label class="a1-label" for="item-{{ $item->id }}-issue">Opis problema na stavci</label>
                                <textarea class="a1-textarea" id="item-{{ $item->id }}-issue" name="items[{{ $item->id }}][issue_description]" maxlength="2000">{{ old('items.'.$item->id.'.issue_description') }}</textarea>
                            </div>
                        </article>
                        @endforeach
                    </div>
                </section>
            </div>

            <aside class="a1-stack" aria-label="Prilozi i podaci isporuke">
                <section class="a1-card" aria-labelledby="create-files-title">
                    <h2 class="a1-os-heading" id="create-files-title">Prilozi</h2>
                    <p class="a1-os-lead" id="create-files-help">Fotografije ili PDF dokumentacija. Najviše 6 fajlova, do 10 MB po fajlu.</p>
                    <label class="a1-drop" for="create-files">
                        <input class="a1-drop__input" id="create-files" type="file" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png,.webp" aria-describedby="create-files-help">
                        <x-icon name="plus-circle" :size="24" :shell="true" />
                        <span class="a1-drop__title">Izaberite fajlove</span>
                        <span>PDF, JPG, PNG ili WebP</span>
                    </label>
                </section>
                <section class="a1-card" aria-labelledby="create-shipping-title">
                    <h2 class="a1-os-heading" id="create-shipping-title">Podaci isporuke</h2>
                    <dl class="a1-dl">
                        <div class="a1-dl__row"><dt>Kupac</dt><dd>{{ $order->shipping_full_name ?: 'Kupac' }}</dd></div>
                        <div class="a1-dl__row"><dt>Telefon</dt><dd>{{ $order->shipping_phone ?: '—' }}</dd></div>
                        <div class="a1-dl__row"><dt>Adresa</dt><dd>{{ $order->shipping_address }}, {{ $order->shipping_postal_code }} {{ $order->shipping_city }}</dd></div>
                    </dl>
                </section>
            </aside>
        </div>

        <div class="a1-actionbar">
            <p class="a1-actionbar__status">Polja označena zvezdicom (*) su obavezna.</p>
            <div class="a1-actionbar__buttons">
                <button class="a1-btn a1-btn--primary" type="submit"><x-icon name="alert" :size="18" :shell="true" /> Otvori slučaj</button>
            </div>
        </div>
    </form>
</div>
@endsection
