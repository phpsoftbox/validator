<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Exception;

use RuntimeException;
use Throwable;

final class FilterPayloadException extends RuntimeException
{
    public function __construct(
        string $message,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
