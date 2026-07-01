<?php
declare(strict_types=1);

namespace App\Validators;

class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    /** @param array<string, mixed> $data */
    public function required(array $data, array $fields): self
    {
        foreach ($fields as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                $this->errors[$field] = "{$field} is required";
            }
        }

        return $this;
    }

    /** @param array<string, mixed> $data */
    public function email(array $data, string $field = 'email'): self
    {
        if (isset($data[$field]) && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = 'Invalid email format';
        }

        return $this;
    }

    /** @param array<string, mixed> $data */
    public function minLength(array $data, string $field, int $min): self
    {
        if (isset($data[$field]) && is_string($data[$field]) && strlen($data[$field]) < $min) {
            $this->errors[$field] = "{$field} must be at least {$min} characters";
        }

        return $this;
    }

    /** @param array<string, mixed> $data */
    public function in(array $data, string $field, array $allowed): self
    {
        if (isset($data[$field]) && !in_array($data[$field], $allowed, true)) {
            $this->errors[$field] = "{$field} has an invalid value";
        }

        return $this;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }
}
