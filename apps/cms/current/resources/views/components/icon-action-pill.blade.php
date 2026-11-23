@props(['tone' => 'ok', 'label'])
{{-- Ikonično dugme za potvrdu/odbijanje (✓ / ✕): bez vidljivog teksta, naziv ide u aria-label i title. Author: Ald1n <https://ald1n.com> --}}
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'a1-iconpill a1-iconpill--'.$tone, 'aria-label' => $label, 'title' => $label]) }}>
    <x-icon :name="$tone === 'danger' ? 'x' : 'check'" :size="20" :shell="true" />
</button>
