<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Support;

use function array_keys;
use function array_values;
use function is_array;
use function is_bool;
use function is_scalar;
use function json_encode;
use function str_replace;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class MessageFormatter
{
    /**
     * @param array<string, mixed> $params
     */
    public static function format(string $template, array $params): string
    {
        $replace = [];
        foreach ($params as $key => $value) {
            $replace['{' . $key . '}'] = self::stringify($value);
        }

        return str_replace(array_keys($replace), array_values($replace), $template);
    }

    private static function stringify(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if (is_array($value)) {
            return (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return '[complex]';
    }
}
