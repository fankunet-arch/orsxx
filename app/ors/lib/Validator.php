<?php
namespace ORS;

/**
 * Input Validator
 */
class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Create new validator instance
     */
    public static function make(array $data): self
    {
        return new self($data);
    }

    /**
     * Validate required field
     */
    public function required(string $field, string $message = null): self
    {
        if (!isset($this->data[$field]) || trim($this->data[$field]) === '') {
            $this->errors[$field] = $message ?? "{$field} is required";
        }
        return $this;
    }

    /**
     * Validate string max length
     */
    public function maxLength(string $field, int $max, string $message = null): self
    {
        if (isset($this->data[$field]) && mb_strlen($this->data[$field]) > $max) {
            $this->errors[$field] = $message ?? "{$field} must be at most {$max} characters";
        }
        return $this;
    }

    /**
     * Validate numeric value
     */
    public function numeric(string $field, string $message = null): self
    {
        if (isset($this->data[$field]) && $this->data[$field] !== '' && !is_numeric($this->data[$field])) {
            $this->errors[$field] = $message ?? "{$field} must be numeric";
        }
        return $this;
    }

    /**
     * Validate positive number
     */
    public function positive(string $field, string $message = null): self
    {
        if (isset($this->data[$field]) && $this->data[$field] !== '' && (float)$this->data[$field] <= 0) {
            $this->errors[$field] = $message ?? "{$field} must be positive";
        }
        return $this;
    }

    /**
     * Validate email
     */
    public function email(string $field, string $message = null): self
    {
        if (isset($this->data[$field]) && $this->data[$field] !== '' && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $message ?? "{$field} must be a valid email";
        }
        return $this;
    }

    /**
     * Validate date format
     */
    public function date(string $field, string $format = 'Y-m-d', string $message = null): self
    {
        if (isset($this->data[$field]) && $this->data[$field] !== '') {
            $d = \DateTime::createFromFormat($format, $this->data[$field]);
            if (!$d || $d->format($format) !== $this->data[$field]) {
                $this->errors[$field] = $message ?? "{$field} must be a valid date";
            }
        }
        return $this;
    }

    /**
     * Validate value is in list
     */
    public function in(string $field, array $values, string $message = null): self
    {
        if (isset($this->data[$field]) && $this->data[$field] !== '' && !in_array($this->data[$field], $values, true)) {
            $this->errors[$field] = $message ?? "{$field} must be one of: " . implode(', ', $values);
        }
        return $this;
    }

    /**
     * Validate integer
     */
    public function integer(string $field, string $message = null): self
    {
        if (isset($this->data[$field]) && $this->data[$field] !== '' && !filter_var($this->data[$field], FILTER_VALIDATE_INT)) {
            $this->errors[$field] = $message ?? "{$field} must be an integer";
        }
        return $this;
    }

    /**
     * Check if validation passed
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Check if validation failed
     */
    public function fails(): bool
    {
        return !$this->passes();
    }

    /**
     * Get validation errors
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get validated data (only fields that were validated)
     */
    public function validated(): array
    {
        return $this->data;
    }

    /**
     * Get single field value with default
     */
    public function get(string $field, $default = null)
    {
        return $this->data[$field] ?? $default;
    }

    /**
     * Get value or null if empty
     */
    public function getOrNull(string $field)
    {
        $value = $this->data[$field] ?? null;
        return ($value === '' || $value === null) ? null : $value;
    }

    /**
     * Get sanitized string
     */
    public function getString(string $field, string $default = ''): string
    {
        return trim($this->data[$field] ?? $default);
    }

    /**
     * Get integer value
     */
    public function getInt(string $field, ?int $default = null): ?int
    {
        $value = $this->data[$field] ?? null;
        return ($value === '' || $value === null) ? $default : (int) $value;
    }

    /**
     * Get float value
     */
    public function getFloat(string $field, ?float $default = null): ?float
    {
        $value = $this->data[$field] ?? null;
        return ($value === '' || $value === null) ? $default : (float) $value;
    }

    /**
     * Get boolean value
     */
    public function getBool(string $field, bool $default = false): bool
    {
        $value = $this->data[$field] ?? $default;
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
