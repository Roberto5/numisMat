<?php

declare(strict_types=1);

use NumisMat\DB;

require_once __DIR__ . '/DB.php';

// ********* debug *********
$debug=array_key_exists('debug',$_GET)?true:false;
if ($debug) {$_POST=$_POST+$_GET;}
/**
 * Backend API contract.
 *
 * Each command explicitly declares the accepted HTTP method and parameters.
 * Parameters are read from the request source declared below.
 *
 * @var array<string, array{method: string, parameters: array<string, array{
 *     source: string, required: bool, type: string
 * }>} $commands
 */
$commands = [
    //inutile?
    /*'coin' => [
        'function' => 'getCoin',
        'parameters' => [
            'id' => [
                'required' => true,
                'type' => 'positive_int',
            ]
        ]
    ],*/
    'typeCoin' => [
        'function'=> 'getTypeCoin',
        'parameters'=> [],
    ],
    'currency' => [
        'function'=> 'getCurrency',
        'parameters'=> []
    ],
    'category' => [
        'function'=> 'getCategory',
        'parameters'=> []
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
        if ($definition['required']) {
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

    respond(['error' => sprintf('Tipo di parametro "%s" non configurato.', $name)], 500);
}
// Call the command function with the validated parameters.
$command['function']($parameters);
function getTypeCoin($parameters=[]) {
    $db = new DB();
    $data=$db->fetchAll("SELECT * FROM `coin_type`");
    respond(['data'=> $data]);
}
function getCurrency($parameters=[]) {
    $db = new DB();
    $data=$db->fetchAll("SELECT * FROM `currency`");
    respond(['data'=> $data]);
}

function getCategory($parameters=[]) {
    $db = new DB();
    $data=$db->fetchAll('SELECT * FROM `type`');
    respond(['data'=> $data]);
}
/*



try {
    $db = new DB();

    if (isset($parameters['id'])) {
        $coin = $db->fetch(
            'SELECT
                c.id,
                ct.name,
                ct.issuer,
                c.value,
                c.grade,
                c.number,
                c.year,
                c.position,
                ct.numista_id,
                ct.numeric_value,
                ct.min_year,
                ct.max_year,
                ct.desc_obverse,
                ct.desc_reverse,
                ct.comments
             FROM coin AS c
             INNER JOIN coin_type AS ct ON ct.id = c.typeID
             WHERE c.id = :id',
            ['id' => $parameters['id']]
        );

        if ($coin === null) {
            respond(['error' => 'Moneta non trovata.'], 404);
        }

        respond(['data' => $coin]);
    }

    $coins = $db->fetchAll(
        'SELECT
            c.id,
            ct.name,
            ct.issuer,
            c.value,
            c.grade,
            ct.defaultImg
         FROM coin AS c
         INNER JOIN coin_type AS ct ON ct.id = c.typeID
         ORDER BY c.id'
    );

    respond(['data' => $coins]);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    respond(['error' => 'Errore interno del server.'.$exception->getMessage().' at line '.$exception->getLine(). ' in file '.$exception->getFile()], 500);
}
*/