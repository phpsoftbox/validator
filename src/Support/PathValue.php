<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Support;

final readonly class PathValue
{
    public function __construct(
        public string $path,
        public mixed $value,
        public bool $present,
    ) {
    }
}
