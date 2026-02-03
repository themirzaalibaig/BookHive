<?php

namespace BookHive\Core;

class Validator
{
    private array $errors = [];
    private array $data = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function validate(array $rules): bool
    {
        foreach ($rules as $field => $ruleSet) {
            $ruleList = explode('|', $ruleSet);
            
            foreach ($ruleList as $rule) {
                $this->applyRule($field, $rule);
            }
        }

        return empty($this->errors);
    }

    private function applyRule(string $field, string $rule): void
    {
        $value = $this->data[$field] ?? null;

        if (str_contains($rule, ':')) {
            [$ruleName, $parameter] = explode(':', $rule, 2);
        } else {
            $ruleName = $rule;
            $parameter = null;
        }

        match($ruleName) {
            'required' => $this->validateRequired($field, $value),
            'email' => $this->validateEmail($field, $value),
            'numeric' => $this->validateNumeric($field, $value),
            'min' => $this->validateMin($field, $value, (int)$parameter),
            'max' => $this->validateMax($field, $value, (int)$parameter),
            'date' => $this->validateDate($field, $value),
            'unique' => $this->validateUnique($field, $value, $parameter),
            default => null,
        };
    }

    private function validateRequired(string $field, mixed $value): void
    {
        if (empty($value) && $value !== '0') {
            $this->errors[$field][] = ucfirst($field) . ' is required';
        }
    }

    private function validateEmail(string $field, mixed $value): void
    {
        if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = ucfirst($field) . ' must be a valid email';
        }
    }

    private function validateNumeric(string $field, mixed $value): void
    {
        if ($value && !is_numeric($value)) {
            $this->errors[$field][] = ucfirst($field) . ' must be numeric';
        }
    }

    private function validateMin(string $field, mixed $value, int $min): void
    {
        if ($value && strlen((string)$value) < $min) {
            $this->errors[$field][] = ucfirst($field) . " must be at least {$min} characters";
        }
    }

    private function validateMax(string $field, mixed $value, int $max): void
    {
        if ($value && strlen((string)$value) > $max) {
            $this->errors[$field][] = ucfirst($field) . " must not exceed {$max} characters";
        }
    }

    private function validateDate(string $field, mixed $value): void
    {
        if ($value && !strtotime($value)) {
            $this->errors[$field][] = ucfirst($field) . ' must be a valid date';
        }
    }

    private function validateUnique(string $field, mixed $value, ?string $table): void
    {
        if (!$value || !$table) return;

        $db = \BookHive\Config\Database::getInstance();
        $exists = $db->fetchColumn("SELECT COUNT(*) FROM {$table} WHERE {$field} = ?", [$value]);
        
        if ($exists > 0) {
            $this->errors[$field][] = ucfirst($field) . ' already exists';
        }
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    public static function sanitize(mixed $value): string
    {
        if (is_array($value)) {
            return '';
        }
        return htmlspecialchars(strip_tags((string)$value), ENT_QUOTES, 'UTF-8');
    }

    public static function sanitizeArray(array $data): array
    {
        return array_map([self::class, 'sanitize'], $data);
    }
}
