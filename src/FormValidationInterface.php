<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator;

interface FormValidationInterface
{
    public function beforeValidation(): void;

    /**
     * @return array<string, mixed>
     */
    public function validate(?ValidationOptions $options = null): array;

    public function validationResult(?ValidationOptions $options = null): ValidationResult;
}
