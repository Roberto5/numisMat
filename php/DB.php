<?php

declare(strict_types=1);

namespace NumisMat;

use PDO;
use PDOStatement;
use InvalidArgumentException;

/**
 * Small PDO wrapper for the MySQL database used by numisMat.
 *
 * Connection values can be passed to the constructor or read from:
 * DB_HOST, DB_PORT, DB_NAME, DB_USER and DB_PASSWORD.
 */
final class DB
{
    private ?PDO $connection = null;

    public function __construct(
        private readonly string $host = '',
        private readonly string $database = '',
        private readonly string $username = '',
        private readonly string $password = '',
        private readonly int $port = 3306,
        private readonly string $charset = 'utf8mb4'
    ) {
    }

    /**
     * Open and return the database connection.
     */
    public function connect(): PDO
    {
        if ($this->connection instanceof PDO) {
            return $this->connection;
        }

        $host = $this->configurationValue($this->host, 'DB_HOST', '127.0.0.1');
        $database = $this->configurationValue($this->database, 'DB_NAME', 'numismat');
        $username = $this->configurationValue($this->username, 'DB_USER', 'root');
        $password = $this->configurationValue($this->password, 'DB_PASSWORD', '');
        $port = (int) $this->configurationValue((string) $this->port, 'DB_PORT', '3306');

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $host,
            $port,
            $database,
            $this->charset
        );

        $this->connection = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return $this->connection;
    }

    /**
     * Execute a prepared SQL statement.
     *
     * @param array<string|int, mixed> $parameters
     */
    public function query(string $sql, array $parameters = []): PDOStatement
    {
        $statement = $this->connect()->prepare($sql);
        $statement->execute($parameters);

        return $statement;
    }

    /**
     * Return the first row produced by a query, or null when there are no rows.
     *
     * @param array<string|int, mixed> $parameters
     * @return array<string, mixed>|null
     */
    public function fetch(string $sql, array $parameters = []): ?array
    {
        $result = $this->query($sql, $parameters)->fetch();

        return $result === false ? null : $result;
    }

    /**
     * Return all rows produced by a query.
     *
     * @param array<string|int, mixed> $parameters
     * @return list<array<string, mixed>>
     */
    public function fetchAll(string $sql, array $parameters = []): array
    {
        return $this->query($sql, $parameters)->fetchAll();
    }

    /**
     * Execute an INSERT, UPDATE or DELETE statement.
     *
     * @param array<string|int, mixed> $parameters
     */
    public function execute(string $sql, array $parameters = []): int
    {
        return $this->query($sql, $parameters)->rowCount();
    }

    public function lastInsertId(): string
    {
        return $this->connect()->lastInsertId();
    }

    public function beginTransaction(): bool
    {
        return $this->connect()->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->connect()->commit();
    }

    public function rollBack(): bool
    {
        return $this->connect()->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->connect()->inTransaction();
    }

    public function disconnect(): void
    {
        $this->connection = null;
    }

    private function configurationValue(string $value, string $environmentVariable, string $default): string
    {
        if ($value !== '') {
            return $value;
        }

        $environmentValue = getenv($environmentVariable);

        return $environmentValue === false ? $default : $environmentValue;
    }
    /**
     * cerca una moneta nel database con id specificato
     * @param int $id
     * @throws InvalidArgumentException
     * @return Coin
     */
    function getCoin(int $id): Coin
    {
        if (is_numeric($id)) {

            $data = $this->fetch(Coin::getQuery(), ['id' => $id]);
            return new Coin($data);
        } else {
            throw new InvalidArgumentException('Invalid coin id');
        }
    }
}
