/**
 * Genera un numero casuale tra min e max esclusi gli elementi presenti in exclude. 
 * @param {Int} min 
 * @param {int} max 
 * @param {Array} exclude 
 */
function randomNumber(min, max, exclude) {
    let rand = 0;
    let pool = [];
    for (let i = min; i < max; i++) {
        if (!exclude.includes(i))
            pool.push(i);
    }
    if (pool.length > 1) return pool[randomInt(0,pool.length - 1)];
    else {
        if (pool.length === 1) return pool[0];
        else return null; // se non ci
    }
}
/**
 * genera un numero intero casuale tra min e max 
 * @param {int} min 
 * @param {int} max 
 * @returns int
 */
function randomInt(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}
/**
 * genera un elemento img 
 * @param {string} src 
 * @param {string} alt 
 * @param {Record<string, string>} params
 * @returns {string}
 */
function getImage(src, alt, params = {}) {
    const img = document.createElement('img');
    img.src = src;
    img.alt = alt;
    img.title = alt;
    //img.setAttribute('onerror', 'this.remove();');
    
    img.setAttribute('onerror', "this.setAttribute('src','img/default_coin.png');");
    //img.onerror
    for (const [key, value] of Object.entries(params)) {
        img.setAttribute(key, value);
    }
    return img.outerHTML;
}
/**
 * genera un elemento img per le monete
 * @param {int} id 
 * @returns 
 */
function getCoinImage(id) {
    if (parseInt(id) < 0) return null;
    let coin =coins.find(v=>v.id==id);
    return getImage(`CoinImg/${coin.defaultImg}-${id}.png`,coin.name,{height:'140px',with:'140px'});
}
/**
 * formatta un array di oggetti nel formato id=>oggetto più facile da indicizzare
 * @param {Array} arraySource 
 * @param {string} idTemplate = 'id'
 * @returns 
 */
function formatArray(arraySource,idTemplate='id') {
    let result=[];
    for (let v of arraySource) 
        result[v[idTemplate]]=v;
    return result;
}
/**
 * popola i select con i dati delle monete
 */
function populateSelect() {
   
    //populate typecoin
    for(let i in typeCoin) {
        let v = typeCoin[i];
        let opt = document.createElement('option'); 
        opt.value = v.id;  
        opt.innerHTML = v.name;  
        selectTypeEl.appendChild(opt);

    }
    //populate currency
    for(let i in currency) {
        let v = currency[i];
        let opt = document.createElement('option'); 
        opt.value = v.id;  
        opt.innerHTML = v.name;  
        selectCurrencyEl.appendChild(opt);

    }
    //populate category
    for(let i in category) {
        let v = category[i];
        let opt = document.createElement('option'); 
        opt.value = v.id;  
        opt.innerHTML = v.name;  
        selectCategoryEl.appendChild(opt);

    }
}