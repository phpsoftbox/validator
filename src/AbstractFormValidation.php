<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator;

use PhpSoftBox\Validator\Support\DataPath;
use PhpSoftBox\Validator\Support\FilterPayloadApplier;
use PhpSoftBox\Validator\Support\FilterPayloadResult;
use UnexpectedValueException;

use function get_debug_type;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function sprintf;

abstract class AbstractFormValidation implements FormValidationInterface
{
    private ?ValidationResult $result = null;

    public function beforeValidation(): void
    {
    }

    /**
     * @return array<string, mixed>
     */
    abstract public function validate(?ValidationOptions $options = null): array;

    abstract public function validationResult(?ValidationOptions $options = null): ValidationResult;

    /**
     * @return array<string, mixed>
     */
    abstract public function rules(): array;

    /**
     * @return array<string, mixed>
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function validated(): array
    {
        return $this->result?->filteredData() ?? [];
    }

    public function get(string $key, mixed $defaultValue = null): mixed
    {
        return DataPath::get($this->validated(), $key, $defaultValue);
    }

    public function getString(string $key, string $defaultValue = ''): string
    {
        $value = $this->get($key, $defaultValue);

        if (!is_string($value)) {
            throw $this->unexpectedType($key, 'string', $value);
        }

        return $value;
    }

    public function getNullableString(string $key, ?string $defaultValue = null): ?string
    {
        $value = $this->get($key, $defaultValue);

        if ($value !== null && !is_string($value)) {
            throw $this->unexpectedType($key, 'string|null', $value);
        }

        return $value;
    }

    public function getInt(string $key, int $defaultValue = 0): int
    {
        $value = $this->get($key, $defaultValue);

        if (!is_int($value)) {
            throw $this->unexpectedType($key, 'int', $value);
        }

        return $value;
    }

    public function getNullableInt(string $key, ?int $defaultValue = null): ?int
    {
        $value = $this->get($key, $defaultValue);

        if ($value !== null && !is_int($value)) {
            throw $this->unexpectedType($key, 'int|null', $value);
        }

        return $value;
    }

    public function getBool(string $key, bool $defaultValue = false): bool
    {
        $value = $this->get($key, $defaultValue);

        if (!is_bool($value)) {
            throw $this->unexpectedType($key, 'bool', $value);
        }

        return $value;
    }

    public function getNullableBool(string $key, ?bool $defaultValue = null): ?bool
    {
        $value = $this->get($key, $defaultValue);

        if ($value !== null && !is_bool($value)) {
            throw $this->unexpectedType($key, 'bool|null', $value);
        }

        return $value;
    }

    public function getFloat(string $key, float $defaultValue = 0.0): float
    {
        $value = $this->get($key, $defaultValue);

        if (is_float($value)) {
            return $value;
        }

        if (is_int($value)) {
            return (float) $value;
        }

        throw $this->unexpectedType($key, 'float', $value);
    }

    public function getNullableFloat(string $key, ?float $defaultValue = null): ?float
    {
        $value = $this->get($key, $defaultValue);

        if ($value === null) {
            return null;
        }

        if (is_float($value)) {
            return $value;
        }

        if (is_int($value)) {
            return (float) $value;
        }

        throw $this->unexpectedType($key, 'float|null', $value);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     *
     * @param array<TKey, TValue> $defaultValue
     * @return array<TKey, TValue>
     */
    public function getArray(string $key, array $defaultValue = []): array
    {
        $value = $this->get($key, $defaultValue);

        if (!is_array($value)) {
            throw $this->unexpectedType($key, 'array', $value);
        }

        return $value;
    }

    protected function setValidationResult(ValidationResult $result): void
    {
        $this->result = $result;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, callable(mixed): mixed|list<callable(mixed): mixed>> $filters
     */
    protected function applyPayloadFilters(array $payload, array $filters): FilterPayloadResult
    {
        return new FilterPayloadApplier()->apply($payload, $filters);
    }

    private function unexpectedType(string $key, string $expectedType, mixed $value): UnexpectedValueException
    {
        return new UnexpectedValueException(sprintf(
            'Field "%s" expected %s, got %s.',
            $key,
            $expectedType,
            get_debug_type($value),
        ));
    }
}
