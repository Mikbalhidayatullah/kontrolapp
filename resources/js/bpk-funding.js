export function initializeBpkFunding(document) {
    const sync = (form) => {
        const type = form.querySelector('[data-bpk-filter-type]');
        const fieldset = form.querySelector('[data-bpk-funding-options]');
        const checkboxes = [...fieldset.querySelectorAll('input[type="checkbox"]')];
        const filtering = type.value === 'funding';
        fieldset.disabled = !filtering;
        fieldset.classList.toggle('hidden', !filtering);
        const missing = filtering && !checkboxes.some((checkbox) => checkbox.checked);
        checkboxes.forEach((checkbox, index) => {
            checkbox.setCustomValidity(missing && index === 0 ? 'Pilih minimal satu kategori pendanaan.' : '');
        });
        form.querySelector('button[type="submit"]').disabled = filtering && checkboxes.length === 0;
    };

    document.addEventListener('change', (event) => {
        const form = event.target.closest('[data-bpk-funding-form]');
        if (form) sync(form);
    });
    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-bpk-funding-all], [data-bpk-funding-clear]');
        if (!button) return;
        const form = button.closest('[data-bpk-funding-form]');
        form.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
            checkbox.checked = button.hasAttribute('data-bpk-funding-all');
        });
        sync(form);
    });
}
