@extends('layouts.app')
@section('title', 'Slike artikla')
@section('content')
<div data-product-images-ready="1">
    <a class="back-link" href="{{ route('admin.products.edit', $product) }}">← Nazad na artikal</a>
    <div class="page-heading">
        <div><span class="eyebrow">Galerija</span><h1>Slike artikla</h1><p>{{ $product->name }} · {{ $product->sku }}</p></div>
    </div>

    <section class="panel form-section">
        <h2>Dodaj fotografije</h2>
        <form
            method="post"
            enctype="multipart/form-data"
            action="{{ route('admin.products.images.store', $product) }}"
            data-image-upload-form
            data-image-upload-mode="reload"
        >
            @csrf
            @include('admin.products.partials.image-upload', ['inputId' => 'gallery-images', 'required' => true])
            <button class="button button-primary" type="submit">Pošalji izabrane slike</button>
        </form>
    </section>

    <section class="panel form-section">
        <div class="section-heading-row">
            <div>
                <h2>Glavna slika i raspored</h2>
                <p class="muted">Klikni zvezdicu preko fotografije da postane glavna. Ostale slike prevuci za promenu rasporeda na telefonu ili računaru.</p>
            </div>
            <span class="image-sort-status" data-image-sort-status>Raspored je sačuvan</span>
        </div>

        <form method="post" action="{{ route('admin.products.images.reorder', $product) }}" data-image-reorder-fallback>
            @csrf
            @method('PUT')
            <div class="image-admin-grid image-sortable-grid" data-image-sortable data-reorder-url="{{ route('admin.products.images.reorder', $product) }}">
                @forelse($product->images as $image)
                    @include('admin.products.partials.image-card', ['product' => $product, 'image' => $image, 'iteration' => $loop->iteration, 'allowDelete' => true])
                    <input type="hidden" name="orders[{{ $image->id }}]" value="{{ $image->sort_order }}" data-order-input="{{ $image->id }}">
                @empty
                    <div class="empty-state">Artikal nema slike.</div>
                @endforelse
            </div>
            @if($product->images->isNotEmpty())
                <button class="button button-ghost" type="submit">Sačuvaj raspored ručno</button>
            @endif
        </form>
    </section>

    @foreach($product->images as $image)
        <form id="primary-{{ $image->id }}" method="post" action="{{ route('admin.products.images.primary', [$product, $image]) }}" data-image-ajax-form data-image-action="primary">@csrf @method('PATCH')</form>
        <form id="rotate-left-{{ $image->id }}" method="post" action="{{ route('admin.products.images.rotate', [$product, $image]) }}" data-image-ajax-form data-image-action="rotate" data-image-id="{{ $image->id }}">@csrf @method('PATCH')<input type="hidden" name="degrees" value="270"></form>
        <form id="rotate-right-{{ $image->id }}" method="post" action="{{ route('admin.products.images.rotate', [$product, $image]) }}" data-image-ajax-form data-image-action="rotate" data-image-id="{{ $image->id }}">@csrf @method('PATCH')<input type="hidden" name="degrees" value="90"></form>
        @if($image->storage_disk === 'public')
            <form id="delete-{{ $image->id }}" method="post" action="{{ route('admin.products.images.destroy', [$product, $image]) }}">@csrf @method('DELETE')</form>
        @endif
    @endforeach
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/product-media-manager.js') }}?v={{ config('app.version') }}" defer></script>
@endpush
