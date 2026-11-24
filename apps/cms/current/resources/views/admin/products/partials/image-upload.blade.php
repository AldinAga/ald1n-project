@php
    $inputId = $inputId ?? 'product-images-'.uniqid();
    $required = (bool) ($required ?? false);
@endphp
<div class="a1-pc-upload" data-image-upload-widget>
    <input
        id="{{ $inputId }}"
        class="a1-pc-upload__input"
        type="file"
        name="images[]"
        accept="image/jpeg,image/png,image/webp"
        multiple
        data-image-upload-input
        @required($required)
    >
    <label class="a1-pc-upload__drop" for="{{ $inputId }}" data-image-upload-dropzone>
        <span class="a1-pc-upload__icon"><x-icon name="image" size="28" /></span>
        <strong>Izaberi fotografije</strong>
        <span>Dodirni ovde ili prevuci fajlove · JPEG, PNG, WebP · do 10 MB po slici</span>
    </label>

    <div class="a1-pc-sel" data-image-selection hidden>
        <div class="a1-pc-sel__head">
            <strong data-image-selection-count>0 fotografija</strong>
            <button class="a1-btn a1-btn--sm" type="button" data-image-selection-clear>Ukloni izbor</button>
        </div>
        <div class="a1-pc-sel__grid" data-image-selection-grid></div>
    </div>

    <div class="a1-pc-prog" data-image-upload-progress hidden aria-live="polite">
        <div class="a1-pc-sel__head">
            <strong data-image-upload-status>Spremno za slanje</strong>
            <span data-image-upload-percent>0%</span>
        </div>
        <div class="a1-pc-prog__track"><span data-image-upload-bar class="ux-product-image-upload-width-zero"></span></div>
        <div class="a1-pc-prog__errors" data-image-upload-errors hidden></div>
    </div>
</div>
