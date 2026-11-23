{{-- Back to top: one partial for layouts.app, layouts.guest and errors.minimal (the public shop has its own .shop-back-to-top). Styles: .a1-btt in ald1n-admin.css. Needs id="a1-top" on <body>. Author: Ald1n <https://ald1n.com> --}}
<a class="a1-btt" id="a1-btt" href="#a1-top" aria-label="Nazad na vrh" title="Nazad na vrh">
    <span class="a1-btt__ico" aria-hidden="true"></span>
</a>
<script nonce="{{ request()->attributes->get('_csp_nonce') }}">
(() => {
    const button = document.getElementById('a1-btt');
    if (!button) return;
    button.setAttribute('data-js', '');
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
    // Fixed or sticky bars at the bottom of the screen; keep this list in step with the CSS (BackToTopContractTest).
    const bars = '.a1-actionbar,.product-editor-action-bar,.dictionary-sort-finish,.ux-mobile-action-dock';
    let queued = false;
    const lift = () => {
        const zone = window.innerHeight - 72;
        let extra = 0;
        document.querySelectorAll(bars).forEach((bar) => {
            const box = bar.getBoundingClientRect();
            if (box.height > 0 && box.top < window.innerHeight && box.bottom > zone && box.right > window.innerWidth - 72 && box.left < window.innerWidth - 16) {
                extra = Math.max(extra, window.innerHeight - box.top + 12 - 16);
            }
        });
        button.style.setProperty('--a1-btt-lift', Math.max(0, Math.round(extra)) + 'px');
    };
    const update = () => {
        queued = false;
        button.classList.toggle('is-shown', window.scrollY > window.innerHeight);
        lift();
    };
    const schedule = () => {
        if (!queued) { queued = true; window.requestAnimationFrame(update); }
    };
    window.addEventListener('scroll', schedule, { passive: true });
    window.addEventListener('resize', schedule, { passive: true });
    new MutationObserver(schedule).observe(document.body, { subtree: true, attributes: true, attributeFilter: ['hidden', 'class'] });
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
