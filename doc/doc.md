# api numista

## accesso al database

La classe `NumisMat\DB` usa PDO e query preparate. I parametri possono essere
passati al costruttore oppure configurati tramite `DB_HOST`, `DB_PORT`,
`DB_NAME`, `DB_USER` e `DB_PASSWORD`.

```php
require_once __DIR__ . '/../php/DB.php';

use NumisMat\DB;

$db = new DB();
$coins = $db->fetchAll(
    'SELECT * FROM coin WHERE year >= :year ORDER BY year DESC',
    ['year' => 1950]
);
```

Per scrivere nel database si usa `execute()`. Dopo un inserimento,
`lastInsertId()` restituisce l'id generato:

```php
$db->execute(
    'INSERT INTO coin (typeID, number, year, grade, value, position)
     VALUES (:typeId, :number, :year, :grade, :value, :position)',
    [
        'typeId' => 1,
        'number' => 1,
        'year' => 1950,
        'grade' => 'vf',
        'value' => 50,
        'position' => 'album A, pagina 1',
    ]
);

$coinId = $db->lastInsertId();
```

Per caricare una moneta esistente si passa `DB` e l'id al costruttore di
`Coin`. Se l'id non esiste viene sollevata una `RuntimeException`:

```php
require_once __DIR__ . '/../php/Coin.php';

use NumisMat\Coin;

$coin = new Coin($db, 1);
```


## ricerca per id
curl -X GET "https://api.numista.com/v3/types/[id]" \
-H "Numista-API-Key: YOUR_API_KEY"

## ricerca per testo

curl -X GET "https://api.numista.com/v3/q/[testo]" \
-H "Numista-API-Key: YOUR_API_KEY"



### RESPONSE SCHEMA: `application/json`

 source https://it.numista.com/api/doc/index.php#tag/Catalogue/operation/searchTypes 

* **`count`** *(integer, required)*: Total count of results
* **`types`** *(Array of objects, required)*: List of results on the given page

  ---

  #### Elementi dell'array `types`:

  * **`id`** *(integer, required)*: Unique ID of the type on Numista
  * **`title`** *(string, required)*: Title of the type
  * **`object_type`** *(object)*
    * **`id`** *(string, required)*: Unique ID of the object type
    * **`name`** *(string, required)*: Name of the object type
  * **`issuer`** *(object)*
    * **`code`** *(string, required)*: Unique ID of the issuer on Numista
    * **`name`** *(string, required)*: Name of the issuer
  * **`min_year`** *(integer)*: First year the type was produced (in the Gregorian calendar). This property was spelled "minYear" in the API v1.
  * **`max_year`** *(integer)*: Last year the type was produced (in the Gregorian calendar). This property was spelled "maxYear" in the API v1.
  * **`obverse_thumbnail`** *(string)*: URL to a thumbnail of the picture of the obverse
  * **`reverse_thumbnail`** *(string)*: URL to a thumbnail of the picture of the reverse
  * **`category`** *(string, **Deprecated**)*
    * **Enum**: `"coin"`, `"banknote"`, `"exonumia"`
    * Category

## errori
    
* 400 Invalid value for a parameter or missing value for a mandatory parameter
* 401 Invalid or missing API key
* 429 You sent too many simultaneous requests or you reached the limit of your monthly quota.

## prezzi

    /types/{type_id}/issues/{issue_id}/prices

# grado moneta
* 'g' - Good   bello
* 'vg' - Very Good bellissimo
* 'vg' - Very Fine molto bello
* 'f' - Fine spendente
* 'vf' - Very Fine molto splendente
* 'xf' - Extremely Fine 
* 'au' - almost Uncirculated fior di conio
* 'unc - Uncirculated

importazione csv
Paese,Emittente,Denominazione,Soggetto,Numero Krause,Anno,Segno di zecca,Valutazione,"Valore, EUR (CoinSnap) ",Composizione,Foto del dritto,Foto del rovescio,Valore (MY),Nota,Set personalizzati