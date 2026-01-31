<?php
/**
 * Clase Model Base - Métodos CRUD comunes
 */

abstract class Model
{
    protected PDO $db;
    protected string $table;
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Encontrar por ID
     */
    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Obtener todos los registros
     */
    public function all(string $orderBy = 'id', string $direction = 'DESC'): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY {$orderBy} {$direction}";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Buscar por columna
     */
    public function findBy(string $column, $value): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$column} = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$value]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Buscar todos por columna
     */
    public function findAllBy(string $column, $value, string $orderBy = 'id', string $direction = 'DESC'): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$column} = ? ORDER BY {$orderBy} {$direction}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$value]);
        return $stmt->fetchAll();
    }

    /**
     * Crear registro
     */
    public function create(array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));

        return (int) $this->db->lastInsertId();
    }

    /**
     * Actualizar registro
     */
    public function update(int $id, array $data): bool
    {
        $sets = implode(' = ?, ', array_keys($data)) . ' = ?';

        $sql = "UPDATE {$this->table} SET {$sets} WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);

        $values = array_values($data);
        $values[] = $id;

        return $stmt->execute($values);
    }

    /**
     * Eliminar registro
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Contar registros
     */
    public function count(string $column = null, $value = null): int
    {
        if ($column && $value !== null) {
            $sql = "SELECT COUNT(*) FROM {$this->table} WHERE {$column} = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$value]);
        } else {
            $sql = "SELECT COUNT(*) FROM {$this->table}";
            $stmt = $this->db->query($sql);
        }

        return (int) $stmt->fetchColumn();
    }

    /**
     * Query personalizada
     */
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Query que retorna una fila
     */
    public function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }
}
