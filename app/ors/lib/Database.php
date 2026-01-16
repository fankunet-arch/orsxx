<?php
namespace ORS;

use PDO;
use PDOException;
use PDOStatement;

/**
 * Database Connection Manager
 * Singleton pattern for database connections
 */
class Database
{
    private static ?PDO $instance = null;
    private static array $config = [];

    /**
     * Initialize database connection
     */
    public static function init(array $config): void
    {
        self::$config = $config;
    }

    /**
     * Get PDO instance
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                self::$config['host'],
                self::$config['port'],
                self::$config['dbname'],
                self::$config['charset']
            );

            try {
                self::$instance = new PDO(
                    $dsn,
                    self::$config['username'],
                    self::$config['password'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                    ]
                );
            } catch (PDOException $e) {
                throw new \Exception('Database connection failed: ' . $e->getMessage());
            }
        }

        return self::$instance;
    }

    /**
     * Execute a query and return statement
     */
    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch all rows
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    /**
     * Fetch single row
     */
    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $result = self::query($sql, $params)->fetch();
        return $result ?: null;
    }

    /**
     * Fetch single column value
     */
    public static function fetchColumn(string $sql, array $params = [], int $column = 0)
    {
        return self::query($sql, $params)->fetchColumn($column);
    }

    /**
     * Insert record and return last insert ID
     */
    public static function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s)',
            $table,
            implode('`, `', $columns),
            implode(', ', $placeholders)
        );

        self::query($sql, array_values($data));
        return (int) self::getInstance()->lastInsertId();
    }

    /**
     * Update records
     */
    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $setParts = [];
        $params = [];

        foreach ($data as $column => $value) {
            $setParts[] = "`{$column}` = ?";
            $params[] = $value;
        }

        $sql = sprintf(
            'UPDATE `%s` SET %s WHERE %s',
            $table,
            implode(', ', $setParts),
            $where
        );

        $params = array_merge($params, $whereParams);
        return self::query($sql, $params)->rowCount();
    }

    /**
     * Delete records
     */
    public static function delete(string $table, string $where, array $params = []): int
    {
        $sql = sprintf('DELETE FROM `%s` WHERE %s', $table, $where);
        return self::query($sql, $params)->rowCount();
    }

    /**
     * Begin transaction
     */
    public static function beginTransaction(): bool
    {
        return self::getInstance()->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public static function commit(): bool
    {
        return self::getInstance()->commit();
    }

    /**
     * Rollback transaction
     */
    public static function rollback(): bool
    {
        return self::getInstance()->rollBack();
    }

    /**
     * Get last insert ID
     */
    public static function lastInsertId(): string
    {
        return self::getInstance()->lastInsertId();
    }
}
