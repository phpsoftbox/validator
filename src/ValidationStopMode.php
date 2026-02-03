<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator;

enum ValidationStopMode: string
{
    case ALL             = 'all';
    case FIRST_PER_FIELD = 'first_per_field';
    case FIRST_ERROR     = 'first_error';
}
