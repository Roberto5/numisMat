// init
document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('.tab');

    tabs.forEach(tab => {
        tab.addEventListener('click', filters);
    });
});

/**
 * evento click sui tab per filtrare le monete
 * @param {Event} e 
 */
function filters(e) {
    // Deseleziona tutti i tab
    tabs.forEach(t => t.classList.remove('active'));
    // @todo Mostra il contenuto corrispondente

    this.classList.add('active');
}
