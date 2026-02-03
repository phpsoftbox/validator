<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator;

final readonly class ValidationOptions
{
    public function __construct(
        public ValidationStopMode $stopMode = ValidationStopMode::ALL,
    ) {
    }
}
