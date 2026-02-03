<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Exception;

use PhpSoftBox\Validator\ValidationResult;
use RuntimeException;
use Throwable;

final class ValidationException extends RuntimeException
{
    public function __construct(
        private readonly ValidationResult $result,
        string $message = 'Validation failed.',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function result(): ValidationResult
    {
        return $this->result;
    }

    /**
     * @return array<string, list<string>>
     */
    public function errors(): array
    {
        return $this->result->errors();
    }
}
