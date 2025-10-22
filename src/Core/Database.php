<?php

namespace SmartRestaurant\Core;

use PDO;
use PDOException;

/**
 * Database connection class using Singleton pattern
 * Ensures only one database connection throughout the application
 */
class Database
{
    private static ?Database $instance = null;
    private PDO $connection;
    private array $config;

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
    {
        // Load configuration from environment variables when available.
        // This prevents committing secrets to source control and allows runtime configuration.
        $this->config = [
            'host' => getenv('DB_HOST') ?: 'localhost',
            'port' => getenv('DB_PORT') ?: '5434',
            'dbname' => getenv('DB_NAME') ?: 'auth_system',
            'username' => getenv('DB_USER') ?: 'postgres',
            'password' => getenv('DB_PASS') ?: ''
        ];

        $this->connect();
    }

    /**
     * Get the singleton instance
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Establish database connection
     */
    private function connect(): void
    {
        try {
            $dsn = sprintf(
                "pgsql:host=%s;port=%s;dbname=%s",
                $this->config['host'],
                $this->config['port'],
                $this->config['dbname']
            );

            $this->connection = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );

            // Set timezone
            $this->connection->exec("SET timezone = 'Africa/Nairobi'");

        } catch (PDOException $e) {
            throw new \RuntimeException("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Get the PDO connection
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * Execute a prepared statement
     */
    public function execute(string $query, array $params = []): \PDOStatement
    {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new \RuntimeException("Query execution failed: " . $e->getMessage());
        }
    }

    /**
     * Fetch a single row
     */
    public function fetchOne(string $query, array $params = []): ?array
    {
        $stmt = $this->execute($query, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Fetch all rows
     */
    public function fetchAll(string $query, array $params = []): array
    {
        $stmt = $this->execute($query, $params);
        return $stmt->fetchAll();
    }

    /**
     * Get the last inserted ID
     */
    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit(): bool
    {
        return $this->connection->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback(): bool
    {
        return $this->connection->rollBack();
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}
