<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator;

use PhpSoftBox\Collection\ArrayHelper;

final readonly class ValidationResult
{
    private ValidationErrorBag $bag;
    /**
     * @param array<string, list<ValidationError>> $errors
     * @param array<string, mixed> $filteredData
     */
    public function __construct(
        private array $errors,
        private array $filteredData,
    ) {
        $this->bag = new ValidationErrorBag($this->errors);
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    /**
     * @return array<string, list<string>>
     */
    public function errors(): array
    {
        return $this->bag->all();
    }

    public function errorBag(): ValidationErrorBag
    {
        return $this->bag;
    }

    /**
     * @return array<string, mixed>
     */
    public function filteredData(): array
    {
        return $this->filteredData;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->filteredData;
    }

    /**
     * @param array<int, string> $keys
     * @return array<string, mixed>
     */
    public function only(array $keys): array
    {
        return ArrayHelper::only($this->filteredData, $keys);
    }

    /**
     * @param array<int, string> $keys
     * @return array<string, mixed>
     */
    public function except(array $keys): array
    {
        return ArrayHelper::except($this->filteredData, $keys);
    }
}
