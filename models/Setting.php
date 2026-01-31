<?php
/**
 * Model Setting - Configuración global del sitio
 */

class Setting extends Model
{
    protected string $table = 'settings';
    private static array $cache = [];

    /**
     * Obtener valor de configuración
     */
    public function get(string $key, $default = null)
    {
        // Usar cache si existe
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $setting = $this->findBy('setting_key', $key);

        if (!$setting) {
            return $default;
        }

        $value = $this->castValue($setting['setting_value'], $setting['setting_type']);
        self::$cache[$key] = $value;

        return $value;
    }

    /**
     * Establecer valor de configuración
     */
    public function set(string $key, $value, string $type = 'string'): bool
    {
        $existing = $this->findBy('setting_key', $key);

        // Convertir valor a string para guardar
        $stringValue = $this->valueToString($value, $type);

        if ($existing) {
            $result = $this->update($existing['id'], [
                'setting_value' => $stringValue,
                'setting_type' => $type
            ]);
        } else {
            $result = $this->create([
                'setting_key' => $key,
                'setting_value' => $stringValue,
                'setting_type' => $type
            ]);
        }

        // Actualizar cache
        if ($result) {
            self::$cache[$key] = $value;
        }

        return (bool) $result;
    }

    /**
     * Obtener múltiples configuraciones
     */
    public function getMultiple(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }
        return $result;
    }

    /**
     * Establecer múltiples configuraciones
     */
    public function setMultiple(array $settings): bool
    {
        foreach ($settings as $key => $value) {
            $type = is_bool($value) ? 'boolean' : (is_int($value) ? 'integer' : 'string');
            $this->set($key, $value, $type);
        }
        return true;
    }

    /**
     * Obtener todas las configuraciones
     */
    public function getAll(): array
    {
        $settings = $this->all('setting_key', 'ASC');
        $result = [];

        foreach ($settings as $setting) {
            $result[$setting['setting_key']] = [
                'value' => $this->castValue($setting['setting_value'], $setting['setting_type']),
                'type' => $setting['setting_type'],
                'description' => $setting['description']
            ];
        }

        return $result;
    }

    /**
     * Obtener configuraciones por grupo (prefijo)
     */
    public function getByPrefix(string $prefix): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE setting_key LIKE ? ORDER BY setting_key";
        $settings = $this->query($sql, [$prefix . '%']);

        $result = [];
        foreach ($settings as $setting) {
            $key = str_replace($prefix, '', $setting['setting_key']);
            $result[$key] = $this->castValue($setting['setting_value'], $setting['setting_type']);
        }

        return $result;
    }

    /**
     * Convertir valor según tipo
     */
    private function castValue($value, string $type)
    {
        switch ($type) {
            case 'integer':
                return (int) $value;
            case 'boolean':
                return $value === '1' || $value === 'true' || $value === true;
            case 'json':
                return json_decode($value, true) ?? [];
            default:
                return $value;
        }
    }

    /**
     * Convertir valor a string para guardar
     */
    private function valueToString($value, string $type): string
    {
        switch ($type) {
            case 'boolean':
                return $value ? '1' : '0';
            case 'json':
                return json_encode($value);
            default:
                return (string) $value;
        }
    }

    /**
     * Limpiar cache
     */
    public function clearCache(): void
    {
        self::$cache = [];
    }
}
