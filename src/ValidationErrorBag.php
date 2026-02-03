<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator;

use function array_key_exists;

final readonly class ValidationErrorBag
{
    /**
     * @param array<string, list<ValidationError>> $errors
     */
    public function __construct(
        private array $errors,
    ) {
    }

    public function has(string $field): bool
    {
        return array_key_exists($field, $this->errors);
    }

    /**
     * @return list<string>
     */
    public function get(string $field): array
    {
        if (!array_key_exists($field, $this->errors)) {
            return [];
        }

        $out = [];
        foreach ($this->errors[$field] as $error) {
            $out[] = $error->message;
        }

        return $out;
    }

    /**
     * @return array<string, list<string>>
     */
    public function all(): array
    {
        $out = [];
        foreach ($this->errors as $field => $errors) {
            $out[$field] = [];
            foreach ($errors as $error) {
                $out[$field][] = $error->message;
            }
        }

        return $out;
    }
}
