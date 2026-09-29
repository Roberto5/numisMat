<?php

declare(strict_types=1);

use NumisMat\CoinCard;
use NumisMat\DB;
use NumisMat\Coin;

require_once __DIR__ . '/DB.php';
require_once __DIR__ . '/coinCard.php';
require_once __DIR__ . '/Coin.php';

/// ********* debug *********
$debug = array_key_exists('debug', $_GET) ? true : false;
if ($debug) {
    $_POST = $_POST + $_GET;
}
//*/
/**
 * Backend API contract.
 *
 * Each command explicitly declares the method and parameters.
 * Parameters are read from the request source declared below.
 *
 * @var array<string, array{function: string, parameters: array<string, array{
 *  required: bool, type: string
 * }>} $commands
 */
$commands = [
    'coins' => [
        'function' => 'getCoins',
        'parameters' => []
    ],
    'typeCoin' => [
        'function' => 'getTypeCoin',
        'parameters' => [],
    ],
    'currency' => [
        'function' => 'getCurrency',
        'parameters' => []
    ],
    'category' => [
        'function' => 'getCategory',
        'parameters' => []
    ],
    'insert' => [
        'function' => 'insert',
        'parameters' => [
            //'id' => ['type' => 'positive_int']
            'typeID' => ['type' => 'positive_int', 'required' => true],
            'number' => ['type' => 'positive_int', 'required' => true],
            'year' => ['type' => 'int'],
            'grade' => ['type' => 'grade'],
            'value' => ['type' => 'float'],
            'position' => ['type' => 'string'],
        ],
    ],
    'update' => [
        'function' => 'update',
        'parameters' => [
            'id' => ['type' => 'positive_int']
        ],
    ],
    'insertType' => [
        'function' => 'insertType',
        'parameters' => [
            'comments' => ['type' => 'string'],
            'desc_obverse' => ['type' => 'string'],
            'desc_reverse' => ['type' => 'string'],
            'img_obverse' => ['type' => 'file_img'],
            'img_obverse_url' => ['type' => 'url_img'],
            'img_reverse' => ['type' => 'file_img'],
            'img_reverse_url' => ['type' => 'url_img'],
            'issuer' => ['type' => 'string'],
            'max_year' => ['type' => 'positive_int'],
            'min_year' => ['type' => 'positive_int'],
            'name' => ['type' => 'string'],
            'numeric_value' => ['type' => 'positive_int'],
            'numista_id' => ['type' => 'positive_int'],
            'type_id' => ['type' => 'positive_int'],
            'value_id' => ['type' => 'positive_int'],
            'defaultImg' => ['type' => 'enum', 'values' => ['reverse', 'obverse']],
        ]
    ]
];
/**
 * 400 parametri mancanti o non validi
 * 404 comando o moneta non trovati
 * 405 metodo HTTP errato
 * 500 errore interno
 */
header('Content-Type: application/json; charset=utf-8');

/**
 * Send a JSON response and stop processing the request.
 *
 * @param array<string, mixed> $payload
 */
function respond(array $payload, int $statusCode = 200): never
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}
/**
 * controlla se il comando esite
 * @param array $source
 * @param string $name
 * @return string|null
 */
function requestScalar(array $source, string $name): ?string
{
    if (!array_key_exists($name, $source)) {
        return null;
    }

    if (!is_string($source[$name])) {
        respond(['error' => sprintf('Parametro "%s" non valido.', $name)], 400);
    }

    return trim($source[$name]);
}
//controllo del servizio richiesto
$service = requestScalar($_GET, 'service');
if ($service === null || $service === '') {
    respond(['error' => 'Il parametro GET "service" è obbligatorio.'], 400);
}

if (!array_key_exists($service, $commands)) {
    respond(['error' => sprintf('Comando "%s" non supportato.', $service)], 404);
}
$command = $commands[$service];
$parameters = [];
foreach ($command['parameters'] as $name => $definition) {
    $value = requestScalar($_POST, $name);
    if ($definition['type'] === 'file_img' && array_key_exists($name, $_FILES)) {
        $uploadedFile = $_FILES[$name];
        $value = is_array($uploadedFile) && isset($uploadedFile['name']) ? (string) $uploadedFile['name'] : null;
    }

    if ($value === null || $value === '') {
        if (array_key_exists('required', $definition) && $definition['required']) {
            respond(['error' => sprintf('Il parametro "%s" è obbligatorio.', $name)], 400);
        }
    }

    if ($definition['type'] === 'positive_int') {
        $validatedValue = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($validatedValue === false) {
            respond([
                'error' => sprintf('Il parametro "%s" deve essere un intero positivo.', $name),
            ], 400);
        }
        $parameters[$name] = $validatedValue;
        continue;
    }
    if ($definition['type'] === 'int') {
        $validatedValue = filter_var($value, FILTER_VALIDATE_INT);
        if ($validatedValue === false) {
            respond([
                'error' => sprintf('Il parametro "%s" deve essere un intero.', $name),
            ], 400);
        }
        $parameters[$name] = $validatedValue;
        continue;
    }
    if ($definition['type'] === 'grade') {
        $VALID_GRADES = ['g', 'vg', 'f', 'vf', 'xf', 'au', 'unc'];
        if (!in_array($value, $VALID_GRADES)) {
            respond([
                'error' => sprintf('Il parametro "%s" deve essere un grado valido.', $name),
            ], 400);
        }
        $parameters[$name] = $value;
        continue;
    }
    if ($definition['type'] === 'float') {
        $validatedValue = filter_var($value, FILTER_VALIDATE_FLOAT);
        if ($validatedValue === false) {
            respond([
                'error' => sprintf('Il parametro "%s" deve essere un numero float.', $name),
            ], 400);
        }
        $parameters[$name] = $validatedValue;
        continue;
    }
    if ($definition['type'] === 'string') {
        $validatedValue = htmlspecialchars($value, ENT_QUOTES);
        if ($validatedValue === false) {
            respond([
                'error' => sprintf('Il parametro "%s" deve essere una stringa.', $name),
            ], 400);
        }
        $parameters[$name] = $validatedValue;
        continue;
    }
    if ($definition['type'] === 'file_img') {
        $file = $_FILES[$name] ?? null;

        if ($file === null || !is_array($file) || trim((string) ($file['name'] ?? '')) === '') {
            $parameters[$name] = null;
            continue;
        }

        $errorCode = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($errorCode !== UPLOAD_ERR_OK) {
            respond([
                'error' => sprintf('Il parametro "%s" contiene un file immagine non valido.', $name),
            ], 400);
        }

        $tmpName = $file['tmp_name'] ?? '';
        if (!is_uploaded_file($tmpName) || !is_file($tmpName)) {
            respond([
                'error' => sprintf('Il parametro "%s" non contiene un file caricato correttamente.', $name),
            ], 400);
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo !== false ? finfo_file($finfo, $tmpName) : false;
        if ($finfo !== false) {
            finfo_close($finfo);
        }

        if ($mime === false || strpos((string) $mime, 'image/') !== 0) {
            respond([
                'error' => sprintf('Il parametro "%s" deve essere un file immagine.', $name),
            ], 400);
        }

        $parameters[$name] = $file['name'];
        continue;
    }
    if ($definition['type'] === 'url_img') {
        if ($value === null || $value === '') {
            $parameters[$name] = null;
            continue;
        }

        $validatedValue = filter_var($value, FILTER_VALIDATE_URL);
        if ($validatedValue === false) {
            respond([
                'error' => sprintf('Il parametro "%s" deve essere un URL di un file immagine valido.', $name),
            ], 400);
        }

        $path = parse_url($validatedValue, PHP_URL_PATH) ?: '';
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
        if ($extension !== '' && !in_array($extension, $allowedExtensions, true)) {
            respond([
                'error' => sprintf('Il parametro "%s" deve puntare a un URL di immagine valido. estensioni ammesse %s', $name, implode(',', $allowedExtensions)),
            ], 400);
        }

        $parameters[$name] = $validatedValue;
        continue;
    }
    if ($definition['type'] === 'enum') {
        $allowedValues = $definition['values'] ?? $definition['value'] ?? [];
        if (!in_array($value, $allowedValues, true)) {
            respond([
                'error' => sprintf('Il parametro "%s" deve essere %s', $name, implode(',', $allowedValues)),
            ], 400);
        }
        $parameters[$name] = $value;
        continue;
    }

    respond(['error' => sprintf('Tipo di parametro "%s" non configurato.', $name)], 500);
}
// Call the command function with the validated parameters.
try {
    $command['function']($parameters);
} catch (\Exception $e) {
    respond(['error' => $e->getMessage() . ' on file ' . $e->getFile() . ' line ' . $e->getLine()], 500);
}
function getTypeCoin($parameters = [])
{
    $db = new DB();
    $data = $db->fetchAll("SELECT * FROM `coin_type`");
    respond(['data' => $data]);
}
function getCurrency($parameters = [])
{
    $db = new DB();
    $data = $db->fetchAll("SELECT * FROM `currency`");
    respond(['data' => $data]);
}

function getCategory($parameters = [])
{
    $db = new DB();
    $data = $db->fetchAll('SELECT * FROM `type`');
    respond(['data' => $data]);
}
function getCoins($parameters = [])
{
    $db = new DB();
    $data = $db->fetchAll(CoinCard::getQuery());
    respond(['data' => $data]);
}
function insert($parameters = [])
{
    $db = new DB();
    $sql = '
    INSERT INTO coin (typeID, number, year, grade, value, position)
    VALUES (:typeID, :number, :year, :grade, :value, :position)
';
    /*echo $sql;
    print_r($parameters);*/
    $db->execute($sql, $parameters);
    $id = $db->lastInsertId();
    $sql = CoinCard::getQuery() . " WHERE c.id='" . $id . "'";
    $data = $db->fetch($sql);
    respond(['data' => $data]);
}
function update($parameters = [])
{
    $db = new DB();
}
function persistImageForType(string $fieldName, ?string $sourceUrl, array $uploadedFile, int $typeId, string $side): ?string
{
    $destinationDir = __DIR__ . '/../CoinImg';
    if (!is_dir($destinationDir) && !mkdir($destinationDir, 0777, true) && !is_dir($destinationDir)) {
        throw new RuntimeException(sprintf('Impossibile creare la cartella "%s".', $destinationDir));
    }

    $fileName = sprintf('%s-%d.png', $side, $typeId);
    $destination = $destinationDir . DIRECTORY_SEPARATOR . $fileName;

    if (is_array($uploadedFile) && !empty($uploadedFile['tmp_name']) && is_uploaded_file($uploadedFile['tmp_name'])) {
        if (!move_uploaded_file($uploadedFile['tmp_name'], $destination)) {
            throw new RuntimeException(sprintf('Impossibile salvare il file "%s".', $fieldName));
        }

        return $fileName;
    }

    if ($sourceUrl !== null && trim($sourceUrl) !== '') {
        $imageData = @file_get_contents($sourceUrl);
        if ($imageData === false) {
            throw new RuntimeException(sprintf('Impossibile scaricare l\'immagine "%s".', $fieldName));
        }

        if (file_put_contents($destination, $imageData) === false) {
            throw new RuntimeException(sprintf('Impossibile salvare l\'immagine "%s".', $fieldName));
        }

        return $fileName;
    }

    return null;
}

function insertType($parameters = [])
{
    $db = new DB();
    /*/ non serve a niente
        $tableColumns = $db->fetchAll('SHOW COLUMNS FROM coin_type');
        $knownColumns = array_map(static fn (array $column): string => (string) ($column['Field'] ?? ''), $tableColumns);

        $insertFields = [
            'name',
            'numista_id',
            'value_id',
            'numeric_value',
            'min_year',
            'max_year',
            'type_id',
            'desc_obverse',
            'desc_reverse',
            'comments',
            'issuer',
        ];
        if (in_array('defaultImg', $knownColumns, true)) {
            $insertFields[] = 'defaultImg';
        }
    //*/
    $insertData = [];
    foreach ($parameters as $key => $value) {
        if (!str_starts_with($key, "img")) {
            if ($value === null || $value === '') {
                $insertData[$key] = null;
            } else
                $insertData[$key] = $value;
        }
    }

    $placeholders = [];
    foreach (array_keys($insertData) as $field) {
        $placeholders[] = ':' . $field;
    }

    $sql = sprintf(
        'INSERT INTO coin_type (%s) VALUES (%s)',
        implode(', ', array_keys($insertData)),
        implode(', ', $placeholders)
    );

    if ($insertData !== []) {
        $db->execute($sql, $insertData);
    }

    $id = (int) $db->lastInsertId();

    foreach (['obverse' => ['file' => 'img_obverse', 'url' => 'img_obverse_url'], 'reverse' => ['file' => 'img_reverse', 'url' => 'img_reverse_url']] as $side => $fieldMap) {
        $uploadedFile = $_FILES[$fieldMap['file']] ?? null;
        $sourceUrl = $parameters[$fieldMap['url']] ?? null;
        $savedFile = persistImageForType($fieldMap['file'], $sourceUrl, is_array($uploadedFile) ? $uploadedFile : [], $id, $side);

        if ($savedFile === null) {
            continue;
        }
    }

    $coinType = $db->fetch('SELECT * FROM coin_type WHERE id = :id', ['id' => $id]);
    respond(['data' => $coinType ?? ['id' => $id]]);
}