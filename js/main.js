const isMockup = false;
/**
 * @type {object} templates
 * @property {string} coin
 */
const cardParam = ['id', 'name', 'year', 'issuer', 'grade', 'value', 'image', "numeric_value", "currency"];
let templates;
// [id,nome,emittente,valore,grado,defaultImg]
let coins = [];
const grade = ['g', 'vg', 'f', 'vf', 'xf', 'au', 'unc'];
let coinsData = [];
let typeCoin = [];
let currency = [];
let category = [];

/**
 * @var {HTMLElement} catalogEl
 */
let catalogEl;
let formEl;
let addDialog;
let selectTypeEl;
let selectCurrencyEl;
let selectCategoryEl;
let coinTypeForm;

/**
 * Esegue una richiesta al backend e restituisce i dati della risposta.
 *
 * Il comando viene inviato nella query string, mentre i parametri vengono
 * inviati nel body POST, come previsto dal contratto di backend.php.
 *
 * @param {string} service Comando backend da eseguire.
 * @param {Record<string, string|number|boolean>} parameters Parametri del comando.
 * @returns {Promise<*>} Dati restituiti dal backend, oppure null in caso di errore.
 */
async function requestBackend(service, parameters = {}) {
    if (typeof service !== 'string' || service.trim() === '') {
        showBackendError('Il comando backend deve essere una stringa non vuota.');
        return null;
    }

    if (parameters === null || typeof parameters !== 'object' || Array.isArray(parameters)) {
        showBackendError('I parametri backend devono essere un oggetto.');
        return null;
    }

    const query = new URLSearchParams({ service: service.trim() });
    const hasFiles = Object.values(parameters).some((value) => value instanceof File);
    const body = hasFiles ? new FormData() : new URLSearchParams();

    for (const [key, value] of Object.entries(parameters)) {
        if (value === null || value === undefined) {
            continue;
        }
        if (hasFiles) {
            if (value instanceof File) {
                body.append(key, value);
                continue;
            }
            body.append(key, String(value));
            continue;
        }
        body.append(key, String(value));
    }

    try {
        const headers = hasFiles ? { 'Accept': 'application/json' } : {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Accept': 'application/json',
        };

        const response = await fetch(`php/backend.php?${query.toString()}`, {
            method: 'POST',
            headers,
            body,
        });

        let payload;
        try {
            payload = await response.json();
        } catch {
            throw new Error(`Risposta non valida dal backend (HTTP ${response.status}).`);
        }

        if (!response.ok) {
            throw new Error(payload.error || `Errore del backend (HTTP ${response.status}).`);
        }

        if (payload.error) {
            throw new Error(payload.error);
        }
        //console.log('data', payload.data);
        return payload.data;
    } catch (error) {
        showBackendError(error instanceof Error ? error.message : 'Errore imprevisto del backend.');
        return null;
    }
}

/**
 * Mostra un errore backend nel dialogo dedicato.
 *
 * @param {string} message
 */
function showBackendError(message) {
    const dialog = document.getElementById('backend-error-dialog');
    const messageElement = document.getElementById('backend-error-message');

    if (!dialog || !messageElement) {
        console.error(message);
        return;
    }

    messageElement.textContent = message;
    if (typeof dialog.showModal === 'function') {
        dialog.showModal();
    } else {
        dialog.open = true;
    }
}

//load templates when the DOM is ready.
document.addEventListener('DOMContentLoaded', async () => {
    try {
        templates = await loadTemplates();
        typeCoin = formatArray(await requestBackend('typeCoin', {}));
        currency = formatArray(await requestBackend('currency', {}));
        category = formatArray(await requestBackend('category', {}));
        coinsData = await requestBackend('coins', {});
        //inizializza la pag
        init(templates);
    } catch (error) {
        console.error('Impossibile inizializzare l’interfaccia.', error);

    }
});

/**
 * Carica tutti i template necessari prima di inizializzare la pagina.
 *
 * @returns {Object} 
 */
async function loadTemplates() {
    const paths = {
        coin: 'template/card.html',
        //for example:
        form: 'template/coin-form.html',
        // header: 'template/header.html',
    };

    const entries = await Promise.all(
        Object.entries(paths).map(async ([name, path]) => {
            const response = await fetch(path);

            if (!response.ok) {
                throw new Error(`Template ${path}: HTTP ${response.status}`);
            }

            return [name, await response.text()];
        })
    );
    const result = Object.fromEntries(entries);
    return result;
}

/**
 * Inizializza gli eventi dopo che DOM e template sono disponibili.
 *
 * @param {{coin: string}} templates
 */
function init(templates) {
    catalogEl = document.getElementById('catalog');
    const tabs = document.querySelectorAll('.tab');

    tabs.forEach(tab => {
        tab.addEventListener('click', filters);
    });

    // Il template è ora pronto per essere usato dal renderer delle monete.
    //console.log('Template caricati:', templates);
    if (isMockup === true) {
        coins = mockup();
    }
    else {
        coins = coinsData;
    }

    renderCoinCards();
    document.getElementById('forms').innerHTML = templates.form;
    formEl = document.getElementById('coin-form');
    formEl.addEventListener('submit', addCoin);
    addDialog = document.getElementById('addDialog');
    selectTypeEl = document.getElementById('coin-type-id');
    selectCurrencyEl = document.getElementById('coin-type-value-id');
    selectCategoryEl = document.getElementById('coin-type-type-id');
    coinTypeForm = document.getElementById('coin-type-form');
    coinTypeForm.addEventListener('submit', addCoinType);
}

/**
 * evento click sui tab per filtrare le monete
 * @param {Event} e 
 */
function filters(e) {
    const tabs = document.querySelectorAll('.tab');

    // Deseleziona tutti i tab
    tabs.forEach(t => t.classList.remove('active'));
    // @todo Mostra il contenuto corrispondente

    this.classList.add('active');
}
function mockup(n = 5) {
    const valute = ["euro", "lire", "dolari"];
    const nazioni = ['Italia', 'Francia', 'Germania', 'Spagna', 'stati uniti'];
    let coins = [];
    for (let i = 0; i < n; i++) {
        let coin = {
            id: i,
            name: valute[randomInt(0, valute.length - 1)],
            issuer: nazioni[randomInt(0, nazioni.length - 1)],
            grade: grade[randomInt(0, grade.length - 1)],
            value: randomInt(0, 10, coins.map(c => c.value)),
            defaultImg: 'reverse'
        };
        if (coin.value !== null) coins.push(coin);
    }
    return coins;
}
function renderCoinCards() {
    if (coins.length > 0) {
        catalogEl.innerHTML = '';
        let htmltemp = ''
        for (let coin of coins) {
            let template = templates.coin;
            for (let key of cardParam) {
                let value = '';
                switch (key) {
                    case 'grade': value = coin[key].toUpperCase(); break;
                    case 'image': value = getCoinImage(coin.id); break;
                    default: value = escapeHtml(String(coin[key] ?? ''));
                }

                template = template.replaceAll(`[${key}]`, value);
            }
            htmltemp += template;
        }
        catalogEl.innerHTML = htmltemp;
    }
    else {

        catalogEl.innerHTML = '<p>Nessuna moneta trovata</p>';
    }
}
function addCoinForm() {
    addDialog.open = true;
    populateSelect();
}
function openNumistaDialog() {

}
async function addCoin(event) {
    event.preventDefault();
    let data = new FormData(formEl);
    data = Object.fromEntries(data.entries());
    let coin = await requestBackend('insert', data);
    if (!coin) return false;


    coinsData.push(coin);
    renderCoinCards();
    formEl.reset();
    addDialog.close();
    return false;
}
async function addCoinType(event) {
    event.preventDefault();
    let data = new FormData(coinTypeForm);
    data = Object.fromEntries(data.entries());
    let data2={}
    for (k in data) 
        if ((k!='default_reverse')||(k!='default_obverse')) data2[k]=data[k];
    data2.defaultImg=data.default_reverse==1 ? 'reverse' : 'obverse';
    let coinType = await requestBackend('insertType',data2);

    typeCoin[coinType.id]=coinType;
    populateSelect();
    coinTypeForm.reset();
    coinTypeDialog.close();
    //console.log(data);
}