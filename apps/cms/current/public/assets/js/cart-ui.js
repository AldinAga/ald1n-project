(() => {
    const clampQuantity = (input, nextValue) => {
        if (!(input instanceof HTMLInputElement)) return;
        const minimum = Math.max(1, Number(input.min || 1));
        const maximum = Math.max(minimum, Number(input.max || 1000));
        const normalized = Math.min(maximum, Math.max(minimum, Number(nextValue) || minimum));
        input.value = String(Math.trunc(normalized));
        input.dispatchEvent(new Event('input', { bubbles: true }));
    };

    document.addEventListener('click', (event) => {
        const target = event.target instanceof Element ? event.target : null;
        if (!target) return;

        const toggle = target.closest('[data-cart-quantity-toggle]');
        if (toggle) {
            const root = toggle.closest('[data-cart-add-control]');
            const form = root?.querySelector('[data-cart-quantity-form]');
            if (form instanceof HTMLFormElement) {
                const open = form.hidden;
                form.hidden = !open;
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                if (open) {
                    window.requestAnimationFrame(() => form.querySelector('[data-cart-quantity-input]')?.focus());
                }
            }
            return;
        }

        const minus = target.closest('[data-cart-quantity-minus]');
        const plus = target.closest('[data-cart-quantity-plus]');
        if (!minus && !plus) return;

        const form = target.closest('form');
        const input = form?.querySelector('[data-cart-quantity-input]');
        if (!(input instanceof HTMLInputElement)) return;

        event.preventDefault();
        const current = Number(input.value || 1);
        clampQuantity(input, current + (plus ? 1 : -1));
    });

    document.addEventListener('change', (event) => {
        const input = event.target instanceof HTMLInputElement
            ? event.target.closest('[data-cart-quantity-input]')
            : null;
        if (input instanceof HTMLInputElement) {
            clampQuantity(input, input.value);
        }
    });
})();