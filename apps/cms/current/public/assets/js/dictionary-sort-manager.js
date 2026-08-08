(() => {
    'use strict';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const setOrderInputs = (container) => {
        Array.from(container.querySelectorAll(':scope > [data-sort-item]')).forEach((item, index) => {
            item.querySelectorAll('[data-sort-order-input]').forEach((input) => {
                input.value = String((index + 1) * 10);
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
    };

    const initContainer = (container) => {
        if (container.dataset.sortReady === '1') return;
        container.dataset.sortReady = '1';

        const endpoint = String(container.dataset.reorderUrl || '').trim();
        const startButtons = Array.from(document.querySelectorAll('[data-sort-edit-start]'));
        const finishBars = Array.from(document.querySelectorAll('[data-sort-finish-bar]'));
        const finishButtons = Array.from(document.querySelectorAll('[data-sort-edit-finish]'));
        const statusNodes = Array.from(document.querySelectorAll('[data-sort-status]'));
        let editing = false;
        let changed = false;
        let active = null;
        let pointerId = null;

        const items = () => Array.from(container.querySelectorAll(':scope > [data-sort-item]'));
        const status = (message, state = '') => {
            statusNodes.forEach((node) => {
                node.textContent = message;
                node.dataset.state = state;
            });
        };

        const setEditing = (enabled) => {
            editing = Boolean(enabled);
            container.classList.toggle('is-reordering', editing);
            document.documentElement.classList.toggle('dictionary-reorder-active', editing);
            startButtons.forEach((button) => {
                button.hidden = editing;
                button.setAttribute('aria-pressed', editing ? 'true' : 'false');
            });
            finishBars.forEach((bar) => { bar.hidden = !editing; });
            if (editing) {
                status('Prevuci kartice pomoću ručice, zatim klikni „Završi uređivanje“.');
                container.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        };

        const release = () => {
            if (active) active.classList.remove('is-dragging');
            active = null;
            pointerId = null;
            document.body.classList.remove('dictionary-item-dragging');
            setOrderInputs(container);
        };

        const findTarget = (x, y) => {
            const element = document.elementFromPoint(x, y);
            return element?.closest?.('[data-sort-item]') || null;
        };

        container.querySelectorAll('[data-sort-handle]').forEach((handle) => {
            handle.addEventListener('pointerdown', (event) => {
                if (!editing || event.button > 0) return;
                const item = handle.closest('[data-sort-item]');
                if (!item) return;
                event.preventDefault();
                active = item;
                pointerId = event.pointerId;
                changed = true;
                active.classList.add('is-dragging');
                document.body.classList.add('dictionary-item-dragging');
                handle.setPointerCapture?.(event.pointerId);
            });

            handle.addEventListener('pointermove', (event) => {
                if (!editing || !active || pointerId !== event.pointerId) return;
                event.preventDefault();
                const target = findTarget(event.clientX, event.clientY);
                if (!target || target === active || target.parentElement !== container) return;

                const rect = target.getBoundingClientRect();
                const sameRow = event.clientY >= rect.top && event.clientY <= rect.bottom;
                const before = sameRow
                    ? event.clientX < rect.left + (rect.width / 2)
                    : event.clientY < rect.top + (rect.height / 2);
                container.insertBefore(active, before ? target : target.nextSibling);
                setOrderInputs(container);
            });

            const finishPointer = (event) => {
                if (pointerId !== null && event.pointerId !== pointerId) return;
                try { handle.releasePointerCapture?.(event.pointerId); } catch (_) {}
                release();
            };
            handle.addEventListener('pointerup', finishPointer);
            handle.addEventListener('pointercancel', finishPointer);
            handle.addEventListener('lostpointercapture', () => release());
        });

        startButtons.forEach((button) => button.addEventListener('click', () => setEditing(true)));

        const save = async () => {
            if (!editing) return;
            if (!endpoint || !changed) {
                setEditing(false);
                return;
            }

            finishButtons.forEach((button) => { button.disabled = true; });
            status('Čuvanje novog rasporeda…', 'saving');
            const ids = items().map((item) => Number.parseInt(String(item.dataset.sortId || ''), 10)).filter(Number.isFinite);

            try {
                const response = await fetch(endpoint, {
                    method: 'PATCH',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ ids }),
                });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);
                changed = false;
                status(payload.message || 'Novi raspored je sačuvan.', 'saved');
                setEditing(false);
            } catch (error) {
                status(`Raspored nije sačuvan: ${error instanceof Error ? error.message : 'nepoznata greška'}`, 'error');
            } finally {
                finishButtons.forEach((button) => { button.disabled = false; });
            }
        };
        finishButtons.forEach((button) => button.addEventListener('click', save));

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && editing && !active) {
                setEditing(false);
                status('Uređivanje rasporeda je zatvoreno. Promene redosleda nisu poslate serveru.', '');
            }
        });

        setOrderInputs(container);
    };

    const boot = () => document.querySelectorAll('[data-dictionary-sortable]').forEach(initContainer);
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, { once: true });
    else boot();
})();
