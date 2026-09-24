<?php
namespace NumisMat;
use InvalidArgumentException;

final class CoinCard {
    private const VALID_GRADES = ['g', 'vg', 'f', 'vf', 'xf', 'au', 'unc'];
    // SELECT c.id,ct.name,ct.issuer,c.value,c.grade,ct.defaultImg FROM coin AS c INNER JOIN coin_type AS ct ON ct.id = c.typeID;

    private const ARRAY_FIELDS = [
        'id' => ['select' => ['c.id AS id'], 'aliases' => ['id'], 'type' => 'int', 'readonly' => true],
        'name' => ['select' => ['ct.name AS name'], 'aliases' => ['name'], 'type' => 'string'],
        'issuer' => ['select' => ['ct.issuer AS issuer'], 'aliases' => ['issuer'], 'type' => 'string'],
        'value' => ['select' => ['c.value AS value'], 'aliases' => ['value'], 'type' => 'int'],
        'grade' => ['select' => ['c.grade AS grade'], 'aliases' => ['grade'], 'type' => 'grade'],
        'defaultImg' => ['select'=>['ct.defaultImg'],['defaultImg'],'type'=>'image'],
    ];
    private array $property = [];
    public function __construct(array $data)
    {
        if ($data !== null) {
            foreach (self::ARRAY_FIELDS as $property => $field) {
                $this->property[$property] = $this->hydrateField($field['type'], $data, $field['aliases']);
            }
        }
    }
    private function hydrateField(string $type, array $data, array $aliases): mixed
    {
        if ((($type === 'descriptions')) && !is_array($data)) {
            throw new InvalidArgumentException(sprintf('Invalid data type for %s field. Expected array', $type));
        }
        return match ($type) {
            'int' => (int) $data[$aliases[0]],
            'grade' => $this->hydrateGrade((string) $data[$aliases[0]]),
            'image' => (($data[$aliases[0]]=== 'reverse')|| ($data[$aliases[0]] === 'obverse')) ? $data[$aliases[0]] :'',
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
    /**
     * restituisce la query per DB
     * @return string
     */
    public static function getQuery(): string
    {
        // ha senso implementare un $page e un $limit?  
        // ed aggiungere in coda alla query LIMIT $page, $limit ?
        $select = [];
        foreach (self::ARRAY_FIELDS as $field) {
            $select = array_merge($select, $field['select']);
        }
        return 'SELECT ' . implode(', ', $select) . '
            FROM coin AS c 
            INNER JOIN coin_type AS ct ON ct.id = c.typeID';
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
    public function __get(string $key): mixed
    {
        return array_key_exists($key, $this->property) ? $this->property[$key] : null;
    }
    public function __set(string $key, mixed $value): void
    {
        // c'è modo di discriminare se il set è publico o privato?
        

        $type = self::ARRAY_FIELDS[$key]['type'];
        if (array_key_exists($key, self::ARRAY_FIELDS)) {
            // chiavi in sola lettura
            if (self::ARRAY_FIELDS[$key]['readonly'] === true) {
                throw new InvalidArgumentException(sprintf('Cannot set readonly property %s', $key));
            }
            if (( ($type === 'descriptions')) && !is_array($value)) {
                throw new InvalidArgumentException(sprintf('Invalid data type for %s field. Expected array', $type));
            }
            $this->property[$key] = match ($type) {
                'int' => (int) $value,
                'grade' => $this->hydrateGrade($value),
                'image' => 
                     (($value === 'reverse') || ($value === 'obverse')) ? $value : ''
                ,
                default => (string) $value,
            };
        }
    }
    public function __isset(string $key): bool
    {
        return array_key_exists($key,$this->property);
    }
}
?>