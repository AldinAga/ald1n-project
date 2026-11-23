{{-- Back to top: one partial for layouts.app, layouts.guest and errors.minimal (the public shop has its own .shop-back-to-top). Styles: .a1-btt in ald1n-admin.css. Needs id="a1-top" on <body>. Author: Ald1n <https://ald1n.com> --}}
<a class="a1-btt" id="a1-btt" href="#a1-top" aria-label="Nazad na vrh" title="Nazad na vrh">
    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 19V5"/><path d="M5 12l7-7 7 7"/></svg>
</a>
<script nonce="{{ request()->attributes->get('_csp_nonce') }}">
(() => {
    const button = document.getElementById('a1-btt');
    if (!button) return;
    button.setAttribute('data-js', '');
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
    let queued = false;
    const update = () => {
        queued = false;
        button.classList.toggle('is-shown', window.scrollY > window.innerHeight);
    };
    window.addEventListener('scroll', () => {
        if (!queued) { queued = true; window.requestAnimationFrame(update); }
    }, { passive: true });
    window.addEventListener('resize', update, { passive: true });
    button.addEventListener('click', (event) => {
        event.preventDefault();
        window.scrollTo({ top: 0, behavior: reduced.matches ? 'auto' : 'smooth' });
        // The button hides itself at the top: hand focus to the page start so keyboard users do not lose their place.
        document.body.setAttribute('tabindex', '-1');
        document.body.focus({ preventScroll: true });
    });
    update();
})();
</script>
