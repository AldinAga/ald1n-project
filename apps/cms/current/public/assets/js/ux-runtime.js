(() => {
    'use strict';

    const ready = (callback) => {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
        } else {
            callback();
        }
    };

    const textOf = (element) => (element?.textContent || '').replace(/\s+/g, ' ').trim();
    const isVisible = (element) => Boolean(element && element.getClientRects().length && getComputedStyle(element).visibility !== 'hidden');

    const improveAlerts = () => {
        document.querySelectorAll('.alert').forEach((alert) => {
            const isError = alert.classList.contains('error');
            alert.setAttribute('role', isError ? 'alert' : 'status');
            alert.setAttribute('aria-live', isError ? 'assertive' : 'polite');
            if (isError) alert.setAttribute('tabindex', '-1');
        });

        const errorAlert = document.querySelector('.alert.error');
        if (errorAlert) {
            window.requestAnimationFrame(() => {
                errorAlert.focus({ preventScroll: true });
                errorAlert.scrollIntoView({ block: 'start', behavior: 'smooth' });
            });
        }
    };

    const improveFields = () => {
        let invalidHandled = false;

        document.querySelectorAll('input, select, textarea').forEach((field, index) => {
            if (field.type === 'hidden') return;
            if (!field.id) field.id = `ux-field-${index + 1}`;

            if (field.type === 'number' && !field.inputMode) {
                field.inputMode = field.step === '1' || field.step === '' ? 'numeric' : 'decimal';
            }
            if (field.hasAttribute('required')) field.setAttribute('aria-required', 'true');

            const label = field.closest('label');
            const fieldError = label?.parentElement?.querySelector(':scope > .field-error') || label?.querySelector('.field-error');
            if (fieldError) {
                if (!fieldError.id) fieldError.id = `${field.id}-error`;
                const describedBy = new Set((field.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean));
                describedBy.add(fieldError.id);
                field.setAttribute('aria-describedby', Array.from(describedBy).join(' '));
                field.setAttribute('aria-invalid', 'true');
                label?.classList.add('has-field-error');
            }

            field.addEventListener('input', () => {
                if (field.getAttribute('aria-invalid') === 'true' && field.checkValidity()) {
                    field.removeAttribute('aria-invalid');
                    label?.classList.remove('has-field-error');
                }
            });
        });

        document.addEventListener('invalid', (event) => {
            const field = event.target;
            if (!(field instanceof HTMLElement)) return;
            field.setAttribute('aria-invalid', 'true');
            field.closest('label')?.classList.add('has-field-error');
            if (invalidHandled) return;
            invalidHandled = true;
            window.requestAnimationFrame(() => {
                field.scrollIntoView({ block: 'center', behavior: 'smooth' });
                field.focus({ preventScroll: true });
                window.setTimeout(() => { invalidHandled = false; }, 500);
            });
        }, true);
    };

    const protectForms = () => {
        document.querySelectorAll('form').forEach((form) => {
            if (form.dataset.uxSubmitReady === '1') return;
            form.dataset.uxSubmitReady = '1';
            const allowMultipleSubmit = form.hasAttribute('data-ux-allow-multiple-submit');
            let submitting = false;

            form.addEventListener('submit', (event) => {
                if (allowMultipleSubmit) return;
                if (submitting) {
                    event.preventDefault();
                    return;
                }
                if (!form.checkValidity()) return;

                submitting = true;
                form.classList.add('is-submitting');
                form.setAttribute('aria-busy', 'true');
                form.dataset.uxSubmitted = '1';
                const submitter = event.submitter instanceof HTMLElement ? event.submitter : null;
                form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((button) => {
                    if (button !== submitter) button.disabled = true;
                });
                if (submitter) {
                    submitter.dataset.uxOriginalLabel = textOf(submitter);
                    submitter.classList.add('is-loading');
                    submitter.setAttribute('aria-disabled', 'true');
                }
            });
        });
    };

    const protectUnsavedChanges = () => {
        const guardedForms = Array.from(document.querySelectorAll('form[data-ux-sticky-actions], form[data-ux-dirty-guard]'))
            .filter((form) => !form.hasAttribute('data-ux-no-dirty-guard'));
        if (guardedForms.length === 0) return;

        guardedForms.forEach((form) => {
            form.dataset.uxDirty = '0';
            const markDirty = (event) => {
                const field = event.target;
                if (field instanceof HTMLInputElement && field.type === 'hidden') return;
                if (form.dataset.uxSubmitted === '1') return;
                form.dataset.uxDirty = '1';
            };
            form.addEventListener('input', markDirty);
            form.addEventListener('change', markDirty);
            form.addEventListener('submit', () => { form.dataset.uxDirty = '0'; });
        });

        window.addEventListener('beforeunload', (event) => {
            const hasUnsavedChanges = guardedForms.some((form) => form.dataset.uxDirty === '1' && form.dataset.uxSubmitted !== '1');
            if (!hasUnsavedChanges) return;
            event.preventDefault();
            event.returnValue = '';
        });
    };

    const findPrimarySubmit = (form) => {
        const candidates = Array.from(form.querySelectorAll('button[type="submit"], input[type="submit"]'));
        return candidates.find((button) => isVisible(button) && !button.disabled && button.classList.contains('button-primary'))
            || candidates.find((button) => isVisible(button) && !button.disabled && !button.classList.contains('button-danger'))
            || null;
    };

    const findBackAction = () => {
        const candidates = Array.from(document.querySelectorAll('.back-link, .page-heading-actions a.button-secondary, .page-heading-actions a.button-ghost'));
        return candidates.find(isVisible) || null;
    };

    const createMobileActionDock = () => {
        if (document.querySelector('[data-ux-action-dock]')) return;

        const forms = Array.from(document.querySelectorAll('form')).filter((candidate) => candidate.matches('[data-ux-sticky-actions]') || candidate.querySelector('.admin-form-grid, .after-sales-create-grid'));
        const form = forms.find((candidate) => candidate.scrollHeight > window.innerHeight * 1.15 && findPrimarySubmit(candidate));
        if (!form) return;

        const submit = findPrimarySubmit(form);
        if (!submit) return;

        const dock = document.createElement('div');
        dock.className = 'ux-mobile-action-dock';
        dock.dataset.uxActionDock = '1';
        dock.setAttribute('role', 'region');
        dock.setAttribute('aria-label', 'Brze akcije formulara');

        const back = findBackAction();
        if (back) {
            const backButton = document.createElement('a');
            backButton.href = back.href;
            backButton.className = 'button button-secondary ux-dock-back';
            backButton.textContent = textOf(back) || 'Nazad';
            dock.appendChild(backButton);
        }

        const saveButton = document.createElement('button');
        saveButton.type = 'button';
        saveButton.className = 'button button-primary ux-dock-submit';
        saveButton.textContent = textOf(submit) || 'Sačuvaj';
        saveButton.addEventListener('click', () => {
            submit.scrollIntoView({ block: 'center', behavior: 'smooth' });
            window.setTimeout(() => submit.click(), 180);
        });
        dock.appendChild(saveButton);

        document.body.appendChild(dock);
        document.body.classList.add('has-ux-action-dock');

        const sync = () => {
            const disabled = submit.disabled || form.classList.contains('is-submitting');
            saveButton.disabled = disabled;
            saveButton.textContent = disabled ? 'Čuvanje…' : (textOf(submit) || 'Sačuvaj');
        };
        new MutationObserver(sync).observe(form, { attributes: true, subtree: true, attributeFilter: ['disabled', 'class'] });
        sync();
    };

    const addKeyboardSave = () => {
        document.addEventListener('keydown', (event) => {
            if (!(event.ctrlKey || event.metaKey) || event.key.toLowerCase() !== 's') return;
            const target = event.target;
            if (target instanceof HTMLElement && target.closest('[contenteditable="true"]')) return;

            const form = target instanceof HTMLElement ? target.closest('form') : null;
            const candidate = form || Array.from(document.querySelectorAll('form')).find((item) => isVisible(item) && findPrimarySubmit(item));
            const submit = candidate ? findPrimarySubmit(candidate) : null;
            if (!submit) return;

            event.preventDefault();
            submit.click();
        });
    };

    const improveTables = () => {
        document.querySelectorAll('.admin-table-wrap, .table-wrap').forEach((wrapper) => {
            wrapper.setAttribute('tabindex', '0');
            wrapper.setAttribute('role', 'region');
            wrapper.setAttribute('aria-label', wrapper.getAttribute('aria-label') || 'Tabela sa horizontalnim pomeranjem');
        });
    };

    const improveDetails = () => {
        document.querySelectorAll('details > summary').forEach((summary) => {
            summary.setAttribute('role', 'button');
            summary.setAttribute('aria-expanded', summary.parentElement?.open ? 'true' : 'false');
            summary.parentElement?.addEventListener('toggle', () => {
                summary.setAttribute('aria-expanded', summary.parentElement.open ? 'true' : 'false');
            });
        });
    };

    ready(() => {
        document.documentElement.classList.add('ux-runtime-ready');
        improveAlerts();
        improveFields();
        protectForms();
        protectUnsavedChanges();
        improveTables();
        improveDetails();
        addKeyboardSave();
        createMobileActionDock();
    });
})();
