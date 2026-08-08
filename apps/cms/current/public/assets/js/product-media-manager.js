(() => {
    'use strict';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const formatBytes = (bytes) => {
        if (!Number.isFinite(bytes) || bytes <= 0) return '0 KB';
        const units = ['B', 'KB', 'MB', 'GB'];
        const index = Math.min(units.length - 1, Math.floor(Math.log(bytes) / Math.log(1024)));
        return `${(bytes / (1024 ** index)).toFixed(index > 1 ? 1 : 0)} ${units[index]}`;
    };

    const setStatus = (message, state = 'saved') => {
        document.querySelectorAll('[data-image-sort-status]').forEach((element) => {
            element.textContent = message;
            element.dataset.state = state;
        });
    };

    const parseJson = (text) => {
        try { return JSON.parse(text); } catch (_) { return null; }
    };

    const initUploadForm = (form) => {
        const input = form.querySelector('[data-image-upload-input]');
        const widget = form.querySelector('[data-image-upload-widget]');
        if (!input || !widget) return;

        const selection = widget.querySelector('[data-image-selection]');
        const count = widget.querySelector('[data-image-selection-count]');
        const grid = widget.querySelector('[data-image-selection-grid]');
        const clear = widget.querySelector('[data-image-selection-clear]');
        const dropzone = widget.querySelector('[data-image-upload-dropzone]');
        const progress = widget.querySelector('[data-image-upload-progress]');
        const progressBar = widget.querySelector('[data-image-upload-bar]');
        const percent = widget.querySelector('[data-image-upload-percent]');
        const status = widget.querySelector('[data-image-upload-status]');
        const errors = widget.querySelector('[data-image-upload-errors]');
        let objectUrls = [];
        let uploading = false;

        const cleanupUrls = () => {
            objectUrls.forEach((url) => URL.revokeObjectURL(url));
            objectUrls = [];
        };

        const setProgress = (value, message, errorMessages = []) => {
            const safe = Math.max(0, Math.min(100, Math.round(value)));
            progress.hidden = false;
            progressBar.style.width = `${safe}%`;
            percent.textContent = `${safe}%`;
            status.textContent = message;
            errors.hidden = errorMessages.length === 0;
            errors.innerHTML = errorMessages.map((item) => `<div>${String(item).replace(/[&<>"']/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]))}</div>`).join('');
            progress.dataset.state = errorMessages.length ? 'error' : (safe === 100 ? 'complete' : 'uploading');
        };

        const renderSelection = () => {
            cleanupUrls();
            const files = Array.from(input.files || []);
            selection.hidden = files.length === 0;
            grid.innerHTML = '';
            count.textContent = `${files.length} ${files.length === 1 ? 'fotografija je izabrana' : 'fotografija je izabrano'}`;

            files.forEach((file, index) => {
                const card = document.createElement('article');
                card.className = 'image-selection-card';
                const url = URL.createObjectURL(file);
                objectUrls.push(url);
                card.innerHTML = `<img src="${url}" alt=""><div><strong>${index + 1}. ${file.name.replace(/[&<>"']/g, '')}</strong><small>${formatBytes(file.size)}</small></div>`;
                grid.appendChild(card);
            });

            if (files.length) setProgress(0, 'Fotografije su izabrane i spremne za slanje.');
            else progress.hidden = true;
        };

        input.addEventListener('change', renderSelection);
        clear?.addEventListener('click', () => {
            input.value = '';
            renderSelection();
        });

        ['dragenter', 'dragover'].forEach((eventName) => dropzone?.addEventListener(eventName, (event) => {
            event.preventDefault();
            dropzone.classList.add('is-dragover');
        }));
        ['dragleave', 'drop'].forEach((eventName) => dropzone?.addEventListener(eventName, (event) => {
            event.preventDefault();
            dropzone.classList.remove('is-dragover');
        }));
        dropzone?.addEventListener('drop', (event) => {
            const files = Array.from(event.dataTransfer?.files || []).filter((file) => file.type.startsWith('image/'));
            if (!files.length) return;
            try {
                const transfer = new DataTransfer();
                files.slice(0, 20).forEach((file) => transfer.items.add(file));
                input.files = transfer.files;
                renderSelection();
            } catch (_) {
                // Na starijim browserima ostaje standardni izbor fajlova.
            }
        });

        form.addEventListener('submit', (event) => {
            const files = Array.from(input.files || []);
            if (!files.length || uploading) return;
            event.preventDefault();
            uploading = true;

            const submitter = event.submitter || form.querySelector('button[type="submit"]');
            submitter?.setAttribute('disabled', 'disabled');
            setProgress(0, 'Priprema slanja fotografija...');

            const xhr = new XMLHttpRequest();
            xhr.open((form.getAttribute('method') || 'POST').toUpperCase(), form.action, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('Accept', 'application/json');
            if (csrfToken) xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);

            xhr.upload.addEventListener('progress', (progressEvent) => {
                if (!progressEvent.lengthComputable) {
                    setProgress(10, 'Slanje fotografija...');
                    return;
                }
                const value = (progressEvent.loaded / progressEvent.total) * 100;
                setProgress(value, value >= 100 ? 'Fotografije su poslate. Čuvanje podataka...' : 'Slanje fotografija...');
            });

            xhr.addEventListener('load', () => {
                const payload = parseJson(xhr.responseText) || {};
                if (xhr.status >= 200 && xhr.status < 300) {
                    setProgress(100, payload.message || 'Fotografije i artikal su uspešno sačuvani.');
                    window.setTimeout(() => {
                        if (payload.redirect_url) window.location.assign(payload.redirect_url);
                        else if (payload.reload || form.dataset.imageUploadMode === 'reload') window.location.reload();
                        else window.location.reload();
                    }, 350);
                    return;
                }

                const messages = [];
                if (payload.errors && typeof payload.errors === 'object') {
                    Object.values(payload.errors).flat().forEach((message) => messages.push(message));
                }
                if (!messages.length) messages.push(payload.message || `Slanje nije uspelo (HTTP ${xhr.status}).`);
                setProgress(0, 'Slanje nije uspelo.', messages);
                uploading = false;
                submitter?.removeAttribute('disabled');
            });

            xhr.addEventListener('error', () => {
                setProgress(0, 'Mrežna greška tokom slanja.', ['Proveri internet vezu i pokušaj ponovo.']);
                uploading = false;
                submitter?.removeAttribute('disabled');
            });
            xhr.addEventListener('abort', () => {
                setProgress(0, 'Slanje je prekinuto.', ['Fotografije nisu sačuvane.']);
                uploading = false;
                submitter?.removeAttribute('disabled');
            });

            xhr.send(new FormData(form));
        });
    };

    const initRepeatableStorage = (root) => {
        const list = root.querySelector('[data-repeatable-list]');
        const template = root.querySelector('[data-repeatable-template]');
        const hidden = root.querySelector('[data-repeatable-value]');
        const totalDisplay = root.querySelector('[data-storage-total-display]');
        const totalInput = root.querySelector('[data-storage-total-value]');
        const initialTotal = Math.max(0, Number.parseInt(root.dataset.storageInitialTotal || totalInput?.value || '0', 10) || 0);
        let userTouchedStorage = false;
        if (!list || !template || !hidden) return;

        const sync = () => {
            let totalCapacity = 0;
            let hasEnteredCapacity = false;
            const values = Array.from(list.querySelectorAll('[data-repeatable-row]'))
                .map((row) => {
                    const type = String(row.querySelector('[data-repeatable-input]')?.value || '').trim();
                    const capacity = String(row.querySelector('[data-repeatable-capacity]')?.value || '').trim();
                    if (/^\d+$/.test(capacity)) {
                        totalCapacity += Math.max(0, Number.parseInt(capacity, 10));
                        hasEnteredCapacity = true;
                    }
                    if (!type && !capacity) return '';
                    return `${type}${capacity ? ` ${capacity} GB` : ''}`.trim();
                })
                .filter(Boolean);
            if (!userTouchedStorage && !hasEnteredCapacity && initialTotal > 0) totalCapacity = initialTotal;
            hidden.value = values.join(' + ');
            if (totalDisplay) totalDisplay.value = String(totalCapacity);
            if (totalInput) {
                totalInput.value = String(totalCapacity);
                totalInput.dispatchEvent(new Event('input', { bubbles: true }));
                totalInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
            hidden.dispatchEvent(new Event('input', { bubbles: true }));
            hidden.dispatchEvent(new Event('change', { bubbles: true }));
            const singleRow = list.querySelectorAll('[data-repeatable-row]').length <= 1;
            list.querySelectorAll('[data-repeatable-remove]').forEach((button) => {
                button.disabled = singleRow;
            });
        };

        const bindRow = (row) => {
            ['[data-repeatable-input]', '[data-repeatable-capacity]'].forEach((selector) => {
                row.querySelector(selector)?.addEventListener('input', () => { userTouchedStorage = true; sync(); });
                row.querySelector(selector)?.addEventListener('change', () => { userTouchedStorage = true; sync(); });
            });
            row.querySelector('[data-repeatable-remove]')?.addEventListener('click', () => {
                if (list.querySelectorAll('[data-repeatable-row]').length <= 1) return;
                userTouchedStorage = true;
                row.remove();
                sync();
            });
        };

        list.querySelectorAll('[data-repeatable-row]').forEach(bindRow);

        root.querySelector('[data-repeatable-add]')?.addEventListener('click', () => {
            userTouchedStorage = true;
            if (list.querySelectorAll('[data-repeatable-row]').length >= 8) {
                window.alert('Možeš dodati najviše 8 diskova.');
                return;
            }
            const fragment = template.content.cloneNode(true);
            const row = fragment.querySelector('[data-repeatable-row]');
            list.appendChild(fragment);
            if (row) {
                bindRow(row);
                row.querySelector('[data-repeatable-input]')?.focus();
            }
            sync();
        });
        sync();
    };

    const initSortable = (container) => {
        const endpoint = container.dataset.reorderUrl;
        if (!endpoint) return;
        let dragging = null;
        let pointerId = null;
        let changed = false;

        const cards = () => Array.from(container.querySelectorAll('[data-image-card]'));
        const refreshFallbackInputs = () => {
            cards().forEach((card, index) => {
                const input = document.querySelector(`[data-order-input="${card.dataset.imageId}"]`);
                if (input) input.value = String((index + 1) * 10);
            });
        };

        const save = async () => {
            const ids = cards().map((card) => card.dataset.imageId).filter(Boolean);
            const data = new FormData();
            data.append('_method', 'PUT');
            ids.forEach((id) => data.append('image_ids[]', id));
            setStatus('Čuvanje rasporeda...', 'saving');
            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    body: data,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        ...(csrfToken ? {'X-CSRF-TOKEN': csrfToken} : {}),
                    },
                });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(payload.message || 'Raspored nije sačuvan.');
                setStatus(payload.message || 'Raspored je sačuvan', 'saved');
                refreshFallbackInputs();
            } catch (error) {
                setStatus(error.message || 'Raspored nije sačuvan', 'error');
            }
        };

        container.querySelectorAll('[data-image-drag-handle]').forEach((handle) => {
            handle.addEventListener('pointerdown', (event) => {
                if (event.button !== undefined && event.button !== 0) return;
                const card = handle.closest('[data-image-card]');
                if (!card || card.dataset.imagePrimary === '1') return;
                dragging = card;
                pointerId = event.pointerId;
                changed = false;
                card.classList.add('is-dragging');
                container.classList.add('is-sorting');
                handle.setPointerCapture?.(event.pointerId);
                event.preventDefault();
            });
        });

        const move = (event) => {
            if (!dragging || pointerId !== event.pointerId) return;
            const target = document.elementFromPoint(event.clientX, event.clientY)?.closest('[data-image-card]');
            if (!target || target === dragging || target.dataset.imagePrimary === '1' || !container.contains(target)) return;
            const currentCards = cards();
            const from = currentCards.indexOf(dragging);
            const to = currentCards.indexOf(target);
            if (from < to) target.after(dragging);
            else target.before(dragging);
            changed = true;
            refreshFallbackInputs();
            event.preventDefault();
        };

        const finish = async (event) => {
            if (!dragging || pointerId !== event.pointerId) return;
            dragging.classList.remove('is-dragging');
            container.classList.remove('is-sorting');
            dragging = null;
            pointerId = null;
            if (changed) await save();
        };

        document.addEventListener('pointermove', move, { passive: false });
        document.addEventListener('pointerup', finish);
        document.addEventListener('pointercancel', finish);
        refreshFallbackInputs();
    };

    const initAjaxImageForm = (form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const buttons = Array.from(document.querySelectorAll('[form]')).filter((button) => button.getAttribute('form') === form.id);
            buttons.forEach((button) => button.setAttribute('disabled', 'disabled'));
            setStatus(form.dataset.imageAction === 'rotate' ? 'Rotacija slike...' : 'Čuvanje...', 'saving');
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        ...(csrfToken ? {'X-CSRF-TOKEN': csrfToken} : {}),
                    },
                });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(payload.message || 'Akcija nad slikom nije uspela.');

                if (form.dataset.imageAction === 'rotate' && payload.image?.url) {
                    document.querySelectorAll(`[data-product-image-id="${payload.image.id}"]`).forEach((image) => {
                        image.src = payload.image.url;
                    });
                    setStatus(payload.message || 'Slika je rotirana.', 'saved');
                    if (payload.reload) {
                        window.setTimeout(() => window.location.reload(), 350);
                        return;
                    }
                } else if (payload.reload || form.dataset.imageAction === 'primary') {
                    window.location.reload();
                    return;
                } else {
                    setStatus(payload.message || 'Izmena je sačuvana.', 'saved');
                }
            } catch (error) {
                setStatus(error.message || 'Akcija nad slikom nije uspela.', 'error');
                window.alert(error.message || 'Akcija nad slikom nije uspela.');
            } finally {
                buttons.forEach((button) => button.removeAttribute('disabled'));
            }
        });
    };

    document.querySelectorAll('[data-image-upload-form]').forEach(initUploadForm);
    document.querySelectorAll('[data-repeatable-storage]').forEach(initRepeatableStorage);
    document.querySelectorAll('[data-image-sortable]').forEach(initSortable);
    document.querySelectorAll('[data-image-ajax-form]').forEach(initAjaxImageForm);
})();
