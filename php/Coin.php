<?php

declare(strict_types=1);

namespace NumisMat;

use InvalidArgumentException;
use RuntimeException;

/**
 * Domain model for a row in the coin table.
 */
final class Coin
{
    private const VALID_GRADES = ['g', 'vg', 'f', 'vf', 'xf', 'au', 'unc'];
    /**
     * nome della proprietà, espressione da usare nel sql, alias da usare nel sql, conversione da applicare
     * The `select` entries are SQL expressions and must expose the aliases
     * used by the corresponding hydration entry below.
     *
     * @var array<string, array{select: list<string>, aliases: list<string>, type: string}>
     */
    private const ARRAY_FIELDS = [
        'id' => ['select' => ['c.id AS id'], 'aliases' => ['id'], 'type' => 'int'],
        'typeId' => ['select' => ['c.typeID AS typeId'], 'aliases' => ['typeId'], 'type' => 'int'],
        'number' => ['select' => ['c.number AS number'], 'aliases' => ['number'], 'type' => 'int'],
        'year' => ['select' => ['c.year AS year'], 'aliases' => ['year'], 'type' => 'int'],
        'grade' => ['select' => ['c.grade AS grade'], 'aliases' => ['grade'], 'type' => 'grade'],
        'value' => ['select' => ['c.value AS value'], 'aliases' => ['value'], 'type' => 'int'],
        'position' => ['select' => ['c.position AS position'], 'aliases' => ['position'], 'type' => 'string'],
        'name' => ['select' => ['ct.name AS name'], 'aliases' => ['name'], 'type' => 'string'],
        'numista_id' => ['select' => ['ct.numista_id AS numista_id'], 'aliases' => ['numista_id'], 'type' => 'int'],
        'currency' => ['select' => ['currency.name AS currency'], 'aliases' => ['currency'], 'type' => 'string'],
        'numeric_value' => ['select' => ['ct.numeric_value AS numeric_value'], 'aliases' => ['numeric_value'], 'type' => 'int'],
        'min_year' => ['select' => ['ct.min_year AS min_year'], 'aliases' => ['min_year'], 'type' => 'int'],
        'max_year' => ['select' => ['ct.max_year AS max_year'], 'aliases' => ['max_year'], 'type' => 'int'],
        'type' => ['select' => ['object_type.name AS type'], 'aliases' => ['type'], 'type' => 'string'],
        'img' => [
            'select' => ['ct.img_obverse AS img_obverse', 'ct.img_reverse AS img_reverse'],
            'aliases' => ['img_obverse', 'img_reverse'],
            'type' => 'images',
        ],
        'desc' => [
            'select' => ['ct.desc_obverse AS desc_obverse', 'ct.desc_reverse AS desc_reverse'],
            'aliases' => ['desc_obverse', 'desc_reverse'],
            'type' => 'descriptions',
        ],
        'comments' => ['select' => ['ct.comments AS comments'], 'aliases' => ['comments'], 'type' => 'string'],
        'issuer' => ['select' => ['ct.issuer AS issuer'], 'aliases' => ['issuer'], 'type' => 'string'],
    ];
    private array $property = [];
    /**
     * @todo Gestione dello stato tramite $found: L'aggiunta di public bool $found = false;
     *  evita il lancio di eccezioni per monete inesistenti, ma genera un "oggetto zombie". 
     * Se la query fallisce, l'applicazione si ritrova in mano un'istanza di Coin completamente vuota. 
     * Questo costringe ogni porzione del tuo codice che crea una moneta a ricordarsi di controllare 
     * preventivamente l'istruzione if ($coin->found) prima di richiamare i dati, 
     * aumentando il rischio di bug invisibili. Rimuovere il DB dal costruttore e affidare la 
     * creazione a una classe esterna che restituisca ?Coin (l'oggetto o null) è strutturalmente 
     * più sicuro.
     * 
     * qualcosa tipo db->getCoin($id) ?
     * @var bool
     */
    public bool $found = false;

    public function __construct(DB $db, int $id)
    {
        $select = [];
        foreach (self::ARRAY_FIELDS as $field) {
            $select = array_merge($select, $field['select']);
        }
        $data = $db->fetch(
            'SELECT ' . implode(', ', $select) . '
            FROM coin AS c 
            INNER JOIN coin_type AS ct ON ct.id = c.typeID 
            INNER JOIN currency ON currency.id = ct.value_id 
            INNER JOIN type AS object_type ON object_type.id = ct.type_id 
            WHERE c.id = :id',
            ['id' => $id]
        );
        if ($data !== null) {
            $this->found = true;
            foreach (self::ARRAY_FIELDS as $property => $field) {
                $this->property[$property] = $this->hydrateField($field['type'], $data, $field['aliases']);
            }
        }
    }

    public function toArray(): array
    {
        $array = [];
        foreach (self::ARRAY_FIELDS as $property => $field) {
            $array[$property] = $this->{$property};
        }
        return $array;
    }
    public static function getQuery(): string
    {
        $select = [];
        foreach (self::ARRAY_FIELDS as $field) {
            $select = array_merge($select, $field['select']);
        }
        return 'SELECT ' . implode(', ', $select) . '
            FROM coin AS c 
            INNER JOIN coin_type AS ct ON ct.id = c.typeID 
            INNER JOIN currency ON currency.id = ct.value_id 
            INNER JOIN type AS object_type ON object_type.id = ct.type_id 
            WHERE c.id = :id';
    }
    /**
     * 
     * @param list<string> $aliases
     * @param array<string, mixed> $data
     */
    private function hydrateField(string $type, array $data, array $aliases): mixed
    {
        if ((($type === 'images') || ($type === 'descriptions')) && !is_array($data)) {
            throw new InvalidArgumentException(sprintf('Invalid data type for %s field. Expected array', $type));
        }
        return match ($type) {
            'int' => (int) $data[$aliases[0]],
            'grade' => $this->hydrateGrade((string) $data[$aliases[0]]),
            'images' => [
                'obverse' => (string) $data[$aliases[0]],
                'reverse' => (string) $data[$aliases[1]],
            ],
            'descriptions' => [
                'obverse' => (string) $data[$aliases[0]],
                'reverse' => (string) $data[$aliases[1]],
            ],
            default => (string) $data[$aliases[0]],
        };
    }
    /**
     * set grade and return it
     * @param string $grade
     * @return string
     */
    private function hydrateGrade(string $grade): string
    {
        $this->setGrade($grade);
        return $this->grade;
    }
    public function __get(string $key): mixed
    {
        return array_key_exists($key, $this->property) ? $this->property[$key] : null;
    }
    public function __set(string $key, mixed $value): void
    {
        $type = self::ARRAY_FIELDS[$key]['type'];
        if (array_key_exists($key, self::ARRAY_FIELDS)) {
            if ((($type === 'images') || ($type === 'descriptions')) && !is_array($value)) {
                throw new InvalidArgumentException(sprintf('Invalid data type for %s field. Expected array', $type));
            }
            $this->property[$key] = match ($type) {
                'int' => (int) $value,
                'grade' => $this->hydrateGrade($value),
                'images' => [
                    'obverse' => (string) $value[0],
                    'reverse' => (string) $value[1],
                ],
                'descriptions' => [
                    'obverse' => (string) $value[0],
                    'reverse' => (string) $value[1],
                ],
                default => (string) $value,
            };
        }
    }
    public function __isset(string $key): bool
    {
        return array_key_exists($key, self::ARRAY_FIELDS);
    }
    public function setGrade(string $grade): void
    {
        $normalizedGrade = strtolower($grade);
        if (!in_array($normalizedGrade, self::VALID_GRADES, true)) {
            throw new InvalidArgumentException(
                sprintf('Invalid coin grade "%s".', $grade)
            );
        }
        $this->property['grade'] = $normalizedGrade;
    }

}
