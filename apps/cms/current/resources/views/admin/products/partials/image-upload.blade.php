@php
    $inputId = $inputId ?? 'product-images-'.uniqid();
    $required = (bool) ($required ?? false);
@endphp
<div class="image-upload-widget" data-image-upload-widget>
    <input
        id="{{ $inputId }}"
        class="image-upload-input"
        type="file"
        name="images[]"
        accept="image/jpeg,image/png,image/webp"
        multiple
        data-image-upload-input
        @required($required)
    >
    <label class="image-upload-dropzone" for="{{ $inputId }}" data-image-upload-dropzone>
        <span class="image-upload-icon"><x-icon name="image" size="28" /></span>
        <strong>Izaberi fotografije</strong>
        <span>Dodirni ovde ili prevuci fajlove · JPEG, PNG, WebP · do 10 MB po slici</span>
    </label>

    <div class="image-selection" data-image-selection hidden>
        <div class="image-selection-heading">
            <strong data-image-selection-count>0 fotografija</strong>
            <button class="button button-small button-ghost" type="button" data-image-selection-clear>Ukloni izbor</button>
        </div>
        <div class="image-selection-grid" data-image-selection-grid></div>
    </div>

    <div class="image-upload-progress" data-image-upload-progress hidden aria-live="polite">
        <div class="image-upload-progress-heading">
            <strong data-image-upload-status>Spremno za slanje</strong>
            <span data-image-upload-percent>0%</span>
        </div>
        <div class="image-upload-progress-track"><span data-image-upload-bar class="ux-product-image-upload-width-zero"></span></div>
        <div class="image-upload-errors" data-image-upload-errors hidden></div>
    </div>
</div>
