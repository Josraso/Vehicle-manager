<?php
/**
 * Clase Database - Singleton para conexión PDO
 */

class Database
{
    private static ?PDO $instance = null;

    /**
     * Obtener instancia de conexión PDO
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../config/database.php';

            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                $config['host'],
                $config['database'],
                $config['charset']
            );

            self::$instance = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $config['options']
            );
        }

        return self::$instance;
    }

    /**
     * Evitar clonación
     */
    private function __clone() {}

    /**
     * Evitar deserialización
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}
