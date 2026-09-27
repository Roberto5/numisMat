<?php

declare(strict_types=1);

use NumisMat\CoinCard;
use NumisMat\DB;
use NumisMat\Coin;

require_once __DIR__ . '/DB.php';
require_once __DIR__ . '/coinCard.php';
require_once __DIR__ . '/Coin.php';

// ********* debug *********
/*/$debug = array_key_exists('debug', $_GET) ? true : false;
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

    if ($value === null || $value === '') {
        if (array_key_exists('required', $definition) && $definition['required']) {
            respond(['error' => sprintf('Il parametro "%s" è obbligatorio.', $name)], 400);
        }
        continue;
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


    respond(['error' => sprintf('Tipo di parametro "%s" non configurato.', $name)], 500);
}
// Call the command function with the validated parameters.
try {
    $command['function']($parameters);
} catch (\Exception $e) {
    respond(['error' => $e->getMessage().' on file '.$e->getFile().' line '.$e->getLine()], 500);
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
    $data = [];
    $coin = new Coin($data);
}
function update($parameters = [])
{
    $db = new DB();
}