@php
    $allowDelete = (bool) ($allowDelete ?? false);
    $iteration = (int) ($iteration ?? 1);
@endphp
<article
    class="a1-pc-img {{ $image->is_primary ? 'is-primary' : '' }}"
    data-image-card
    data-image-id="{{ $image->id }}"
    data-image-primary="{{ $image->is_primary ? '1' : '0' }}"
>
    <div class="a1-pc-img__preview">
        @if($image->url)
            <img src="{{ $image->url }}" alt="{{ $product->name }} — slika {{ $iteration }}" data-product-image-id="{{ $image->id }}" loading="lazy">
        @else
            <span>Slika nije dostupna</span>
        @endif

        <button
            class="a1-pc-img__star {{ $image->is_primary ? 'is-active' : '' }}"
            type="submit"
            form="primary-{{ $image->id }}"
            title="{{ $image->is_primary ? 'Ovo je glavna slika' : 'Postavi kao glavnu sliku' }}"
            aria-label="{{ $image->is_primary ? 'Glavna slika' : 'Postavi kao glavnu sliku' }}"
            @disabled($image->is_primary)
        >
            <x-icon name="star" size="20" />
        </button>

        @if($image->is_primary)
            <span class="a1-pc-img__badge">Glavna</span>
        @endif
    </div>

    <div class="a1-pc-img__meta">
        <strong>{{ $image->is_primary ? 'Glavna slika' : 'Slika '.$iteration }}</strong>
        <small>{{ $image->storage_disk === 'legacy' ? 'Legacy · rotacija pravi lokalnu kopiju' : 'Laravel storage' }}</small>
    </div>

    <div>
        @if($image->is_primary)
            <span class="a1-pc-img__locked"><x-icon name="lock" size="16" /> Glavna slika je uvek prva</span>
        @else
            <button class="a1-pc-img__handle" type="button" data-image-drag-handle aria-label="Prevuci za promenu redosleda">
                <x-icon name="grip" size="18" /> Prevuci za raspored
            </button>
        @endif
    </div>

    <div class="a1-cluster">
        <button class="a1-btn a1-btn--sm" type="submit" form="rotate-left-{{ $image->id }}" title="Rotiraj ulevo 90°">↶ 90°</button>
        <button class="a1-btn a1-btn--sm" type="submit" form="rotate-right-{{ $image->id }}" title="Rotiraj udesno 90°">↷ 90°</button>
        <a class="a1-btn a1-btn--sm" href="{{ $image->download_url }}" title="Preuzmi originalnu fotografiju"><x-icon name="download" size="16" /> Preuzmi</a>
        @if($allowDelete && $image->storage_disk === 'public')
            <button class="a1-btn a1-btn--danger a1-btn--sm" type="submit" form="delete-{{ $image->id }}">Ukloni</button>
        @endif
    </div>
</article>
