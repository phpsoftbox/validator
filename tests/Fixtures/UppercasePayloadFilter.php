<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Tests\Fixtures;

use function is_string;
use function strtoupper;

final class UppercasePayloadFilter
{
    public function __invoke(mixed $value): mixed
    {
        return is_string($value) ? strtoupper($value) : $value;
    }
}
