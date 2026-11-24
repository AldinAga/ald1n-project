@props(['tone' => 'ok', 'label'])
{{-- Ikonično dugme za potvrdu/odbijanje (✓ / ✕): bez vidljivog teksta, naziv ide u aria-label i title. Author: Ald1n <https://ald1n.com> --}}
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'a1-iconpill a1-iconpill--'.$tone, 'aria-label' => $label, 'title' => $label]) }}>
    <x-icon :name="$tone === 'danger' ? 'x' : 'check'" :size="20" />
</button>
@once
    <script nonce="{{ request()->attributes->get('_csp_nonce') }}">
        // Stanje slanja: dugme se ne onemogućava (forma se šalje normalno), samo dobija is-loading i aria-busy.
        document.addEventListener('submit', (event) => {
            const pill = event.target.querySelector && event.target.querySelector('.a1-iconpill');
            if (!pill) return;
            setTimeout(() => {
                if (event.defaultPrevented) return;
                pill.classList.add('is-loading');
                pill.setAttribute('aria-busy', 'true');
            }, 0);
        });
        window.addEventListener('pageshow', () => document.querySelectorAll('.a1-iconpill.is-loading').forEach((pill) => {
            pill.classList.remove('is-loading');
            pill.removeAttribute('aria-busy');
        }));
    </script>
@endonce
