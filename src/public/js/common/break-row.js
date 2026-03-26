document.addEventListener('input', function (e) {
    if (!e.target.matches('.js-break-in, .js-break-out')) return;

    const rows = document.querySelectorAll('.js-break-row');
    const lastRow = rows[rows.length - 1];
    const inAt = lastRow.querySelector('.js-break-in').value;
    const outAt = lastRow.querySelector('.js-break-out').value;

    if (!inAt || !outAt) return;

    const newRow = lastRow.cloneNode(true);
    newRow.querySelector('.break-col').textContent = `休憩${rows.length + 1}`;
    newRow.querySelector('.js-break-in').value = '';
    newRow.querySelector('.js-break-out').value = '';
    newRow.querySelector('.validate-error').textContent = '';
    document.querySelector('.js-note-row').before(newRow);
});
