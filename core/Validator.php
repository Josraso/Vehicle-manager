<?php
/**
 * Clase Validator - Validación de datos
 */

class Validator
{
    private array $errors = [];
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Validar campo requerido
     */
    public function required(string $field, string $message = null): self
    {
        if (!isset($this->data[$field]) || trim($this->data[$field]) === '') {
            $this->errors[$field] = $message ?? "El campo {$field} es obligatorio";
        }
        return $this;
    }

    /**
     * Validar email
     */
    public function email(string $field, string $message = null): self
    {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $message ?? "El email no es válido";
        }
        return $this;
    }

    /**
     * Validar longitud mínima
     */
    public function min(string $field, int $length, string $message = null): self
    {
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $length) {
            $this->errors[$field] = $message ?? "El campo {$field} debe tener al menos {$length} caracteres";
        }
        return $this;
    }

    /**
     * Validar longitud máxima
     */
    public function max(string $field, int $length, string $message = null): self
    {
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $length) {
            $this->errors[$field] = $message ?? "El campo {$field} no puede tener más de {$length} caracteres";
        }
        return $this;
    }

    /**
     * Validar que sea numérico
     */
    public function numeric(string $field, string $message = null): self
    {
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field] = $message ?? "El campo {$field} debe ser numérico";
        }
        return $this;
    }

    /**
     * Validar valor mínimo
     */
    public function minValue(string $field, float $min, string $message = null): self
    {
        if (isset($this->data[$field]) && floatval($this->data[$field]) < $min) {
            $this->errors[$field] = $message ?? "El campo {$field} debe ser mayor o igual a {$min}";
        }
        return $this;
    }

    /**
     * Validar que coincidan dos campos
     */
    public function matches(string $field, string $matchField, string $message = null): self
    {
        if (isset($this->data[$field], $this->data[$matchField]) && $this->data[$field] !== $this->data[$matchField]) {
            $this->errors[$field] = $message ?? "Los campos no coinciden";
        }
        return $this;
    }

    /**
     * Validar fecha
     */
    public function date(string $field, string $format = 'Y-m-d', string $message = null): self
    {
        if (isset($this->data[$field])) {
            $date = DateTime::createFromFormat($format, $this->data[$field]);
            if (!$date || $date->format($format) !== $this->data[$field]) {
                $this->errors[$field] = $message ?? "La fecha no es válida";
            }
        }
        return $this;
    }

    /**
     * Validar año
     */
    public function year(string $field, int $minYear = 1900, string $message = null): self
    {
        if (isset($this->data[$field])) {
            $year = (int) $this->data[$field];
            $currentYear = (int) date('Y');
            if ($year < $minYear || $year > $currentYear + 1) {
                $this->errors[$field] = $message ?? "El año debe estar entre {$minYear} y " . ($currentYear + 1);
            }
        }
        return $this;
    }

    /**
     * Verificar si hay errores
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Verificar si es válido
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Obtener errores
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Obtener primer error
     */
    public function firstError(): ?string
    {
        return !empty($this->errors) ? reset($this->errors) : null;
    }

    /**
     * Obtener valor validado
     */
    public function get(string $field, $default = null)
    {
        return $this->data[$field] ?? $default;
    }

    /**
     * Obtener todos los datos
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Sanitizar string
     */
    public static function sanitize(string $value): string
    {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitizar array de datos
     */
    public static function sanitizeAll(array $data): array
    {
        return array_map(function ($value) {
            return is_string($value) ? self::sanitize($value) : $value;
        }, $data);
    }
}
