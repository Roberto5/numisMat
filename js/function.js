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
 * 
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
    img.setAttribute('onerror', 'this.remove();');
    for (const [key, value] of Object.entries(params)) {
        img.setAttribute(key, value);
    }

    return img.outerHTML;
}
function getCoinImage(id) {
    if (parseInt(id) < 0) return null;
    let coin =coins.find(v=>v.id==id);
    return getImage(`CoinImg/${coin.defaultImg}-${id}.png`,coin.name,{height:'140px'});
}