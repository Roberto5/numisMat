const isMockup = true;
/**
 * @type {object} templates
 * @property {string} coin
 */
let templates;
// [id,nome,anno,valore,grado,image]
let coins=[];
const grade = ['g', 'vg', 'f', 'vf', 'xf', 'au', 'unc'];
let coinsData=[];
/**
 * @var {HTMLElement} catalogEl
 */
let catalogEl;

let formEl;
let addDialog;
//load templates when the DOM is ready.
document.addEventListener('DOMContentLoaded', async () => {
    try {
        templates = await loadTemplates();
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
    catalogEl= document.getElementById('catalog');
    const tabs = document.querySelectorAll('.tab');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', filters);
    });

    // Il template è ora pronto per essere usato dal renderer delle monete.
    //console.log('Template caricati:', templates);
    if (isMockup===true) {
        coins = mockup();
    }

    renderCoinCards();
    document.getElementById('forms').innerHTML= templates.form;
    formEl=document.getElementById('coin-form');
    addDialog=document.getElementById('addDialog');
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
function mockup(n=5) {
    const valute=["euro","lire","dolari"];
    const nazioni=['Italia','Francia','Germania','Spagna','stati uniti'];
    let coins=[];
    for(let i=0;i<n;i++){
        let coin={
            id:i,
            name:valute[randomInt(0,valute.length-1)],
            issuer:nazioni[randomInt(0,nazioni.length-1)],
            grade:1,
            value:randomInt(0,10,coins.map(c=>c.value)),
            defaultImg:'reverse'
        };
        if (coin.value!==null) coins.push(coin);
    }
    return coins;
}
function renderCoinCards() {
    if (coins.length>0) {
        catalogEl.innerHTML = '';
        let htmltemp=''
        for (let coin of coins) {
            let template = templates.coin;
            for(let key of ['id','name','issuer','grade','value','image']){
                let value='';
                switch (key) {
                    case 'grade':value=grade[coin[key]].toUpperCase();break;
                    case 'image' : value=getCoinImage(coin.id);break;
                    default: value=String(coin[key]?? '');
                }

                template=template.replaceAll(`[${key}]`,value);
            }
            htmltemp += template;
        }
        catalogEl.innerHTML=htmltemp;
    }
    else {
        
        catalogEl.innerHTML = '<p>Nessuna moneta trovata</p>';
    }
}
function addCoinForm() {
    //@todo add populate select
    addDialog.open=true;
}