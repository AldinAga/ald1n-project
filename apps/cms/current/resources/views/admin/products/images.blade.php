@extends('layouts.app')
@section('title', 'Slike artikla')
@section('content')
@include('admin.products.partials.styles')
<div class="a1-ops" data-product-images-ready="1">
    <nav aria-label="Putanja">
        <ol class="a1-crumbs">
            <li><a class="back-link" href="{{ route('admin.products.edit', $product) }}">Nazad na artikal</a></li>
            <li aria-current="page">Slike</li>
        </ol>
    </nav>
    <header class="a1-pagehead">
        <div class="a1-pagehead__text">
            <span class="a1-eyebrow">Galerija</span>
            <h1 class="a1-title">Slike artikla</h1>
            <p class="a1-lead">{{ $product->name }} · {{ $product->sku }}</p>
        </div>
    </header>

    <section class="a1-card" aria-labelledby="img-add">
        <h2 class="a1-h3" id="img-add">Dodaj fotografije</h2>
        <form
            method="post"
            enctype="multipart/form-data"
            action="{{ route('admin.products.images.store', $product) }}"
            class="a1-form"
            data-image-upload-form
            data-image-upload-mode="reload"
        >
            @csrf
            @include('admin.products.partials.image-upload', ['inputId' => 'gallery-images', 'required' => true])
            <div><button class="a1-btn a1-btn--primary" type="submit">Pošalji izabrane slike</button></div>
        </form>
    </section>

    <section class="a1-card" aria-labelledby="img-order">
        <div class="a1-card__head">
            <div class="a1-card__title">
                <h2 class="a1-h3" id="img-order">Glavna slika i raspored</h2>
                <p class="a1-text">Klikni zvezdicu preko fotografije da postane glavna. Ostale slike prevuci za promenu rasporeda na telefonu ili računaru.</p>
            </div>
            <span class="image-sort-status" data-image-sort-status role="status">Raspored je sačuvan</span>
        </div>

        <form method="post" action="{{ route('admin.products.images.reorder', $product) }}" class="a1-form" data-image-reorder-fallback>
            @csrf
            @method('PUT')
            <div class="a1-pc-imggrid image-sortable-grid" data-image-sortable data-reorder-url="{{ route('admin.products.images.reorder', $product) }}">
                @forelse($product->images as $image)
                    @include('admin.products.partials.image-card', ['product' => $product, 'image' => $image, 'iteration' => $loop->iteration, 'allowDelete' => true])
                    <input type="hidden" name="orders[{{ $image->id }}]" value="{{ $image->sort_order }}" data-order-input="{{ $image->id }}">
                @empty
                    <div class="a1-empty"><span class="a1-empty__icon"><x-icon name="image" size="28" /></span><p>Artikal nema slike.</p></div>
                @endforelse
            </div>
            @if($product->images->isNotEmpty())
                <div><button class="a1-btn" type="submit">Sačuvaj raspored ručno</button></div>
            @endif
        </form>
    </section>

    <div hidden data-product-aux-forms>
    @foreach($product->images as $image)
        <form id="primary-{{ $image->id }}" method="post" action="{{ route('admin.products.images.primary', [$product, $image]) }}" data-image-ajax-form data-image-action="primary">@csrf @method('PATCH')</form>
        <form id="rotate-left-{{ $image->id }}" method="post" action="{{ route('admin.products.images.rotate', [$product, $image]) }}" data-image-ajax-form data-image-action="rotate" data-image-id="{{ $image->id }}">@csrf @method('PATCH')<input type="hidden" name="degrees" value="270"></form>
        <form id="rotate-right-{{ $image->id }}" method="post" action="{{ route('admin.products.images.rotate', [$product, $image]) }}" data-image-ajax-form data-image-action="rotate" data-image-id="{{ $image->id }}">@csrf @method('PATCH')<input type="hidden" name="degrees" value="90"></form>
        @if($image->storage_disk === 'public')
            <form id="delete-{{ $image->id }}" data-confirm-submit="Ukloniti sliku?" method="post" action="{{ route('admin.products.images.destroy', [$product, $image]) }}">@csrf @method('DELETE')</form>
        @endif
    @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ request()->attributes->get('_csp_nonce') }}" src="{{ asset('assets/js/product-media-manager.js') }}?v={{ config('app.version') }}" defer></script>
@endpush
