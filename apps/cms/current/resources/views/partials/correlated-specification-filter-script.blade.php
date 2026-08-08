<script>
document.querySelectorAll('[data-correlated-spec-filter-form]').forEach((form) => {
    const brand = form.querySelector('[data-filter-brand]');
    const line = form.querySelector('[data-filter-line]');
    const type = form.querySelector('[data-filter-product-type]');

    const refreshLines = () => {
        if (!brand || !line) return;
        const brandId = brand.value;
        let visible = 0;
        Array.from(line.options).forEach((option, index) => {
            if (index === 0) return;
            const allowed = brandId === '' || option.dataset.brandId === brandId;
            option.hidden = !allowed;
            option.disabled = !allowed;
            if (allowed) visible += 1;
        });
        if (line.selectedOptions[0]?.disabled) line.value = '';
        line.disabled = brandId !== '' && visible === 0;
    };

    const optionId = (select) => select?.selectedOptions?.[0]?.dataset?.optionId || '';
    const refreshDependency = (select) => {
        const parentFieldId = select.dataset.parentFieldId || '';
        if (!parentFieldId) return;
        const parent = form.querySelector(`[data-filter-spec-field="${parentFieldId}"]`);
        const parentOptionId = optionId(parent);
        const wrapper = select.closest('[data-filter-spec-wrapper]');
        let visible = 0;
        let selectedValid = select.value === '';
        Array.from(select.options).forEach((option, index) => {
            if (index === 0) return;
            const parents = (option.dataset.parentOptionIds || '').split(',').filter(Boolean);
            const allowed = parentOptionId !== '' && parents.includes(parentOptionId);
            option.hidden = !allowed;
            option.disabled = !allowed;
            if (allowed) visible += 1;
            if (allowed && option.selected) selectedValid = true;
        });
        if (!selectedValid) select.value = '';
        select.disabled = parentOptionId === '' || visible === 0;
        wrapper?.classList.toggle('is-dependency-disabled', select.disabled);
        wrapper?.classList.toggle('is-dependency-empty', parentOptionId !== '' && visible === 0);
        const help = wrapper?.querySelector('[data-filter-dependency-help]');
        if (help) help.textContent = parentOptionId === '' ? 'Prvo izaberi povezanu roditeljsku opciju.' : (visible ? 'Prikazane su samo povezane opcije.' : 'Za ovaj izbor nema povezanih opcija.');
        wrapper?.querySelectorAll('input').forEach((input) => input.disabled = select.disabled);
    };

    const refreshSpecifications = () => {
        const selectedType = type?.value || '';
        form.querySelectorAll('[data-filter-spec-wrapper]').forEach((wrapper) => {
            const typeIds = (wrapper.dataset.filterTypeIds || '').split(',').filter(Boolean);
            const allowed = selectedType === '' || typeIds.length === 0 || typeIds.includes(selectedType);
            wrapper.hidden = !allowed;
            wrapper.querySelectorAll('input,select').forEach((input) => input.disabled = !allowed);
        });
        const dependent = Array.from(form.querySelectorAll('select[data-parent-field-id]:not([data-parent-field-id=""])'));
        for (let pass = 0; pass <= dependent.length; pass += 1) dependent.forEach(refreshDependency);
    };

    brand?.addEventListener('change', refreshLines);
    type?.addEventListener('change', refreshSpecifications);
    form.querySelectorAll('[data-filter-spec-field]').forEach((input) => input.addEventListener('change', refreshSpecifications));
    refreshLines();
    refreshSpecifications();
});
</script>
