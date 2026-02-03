<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Support;

use PhpSoftBox\Collection\ArrayHelper;

use function array_key_exists;
use function explode;
use function is_array;

final class DataPath
{
    /**
     * @param array<string, mixed> $data
     */
    public static function get(array $data, string $path, mixed $default = null): mixed
    {
        $segments = self::segments($path);
        $current  = $data;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return $default;
            }
            $current = $current[$segment];
        }

        return $current;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function has(array $data, string $path): bool
    {
        $segments = self::segments($path);
        $current  = $data;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return false;
            }
            $current = $current[$segment];
        }

        return true;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function set(array &$data, string $path, mixed $value): void
    {
        $segments = self::segments($path);
        $current  = &$data;

        foreach ($segments as $segment) {
            if (!is_array($current)) {
                $current = [];
            }
            if (!array_key_exists($segment, $current) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }
            $current = &$current[$segment];
        }

        $current = $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function forget(array &$data, string|array $path): void
    {
        $data = ArrayHelper::forget($data, $path);
    }

    /**
     * @param array<string, mixed> $data
     * @return list<PathValue>
     */
    public static function extract(array $data, string $pattern): array
    {
        $values  = ArrayHelper::path($data, $pattern);
        $results = [];

        foreach ($values as $value) {
            $results[] = new PathValue($value['path'], $value['value'], $value['present']);
        }

        return $results;
    }

    public static function matches(string $pattern, string $path): bool
    {
        return ArrayHelper::pathMatches($pattern, $path);
    }

    /**
     * @return list<string>
     */
    private static function segments(string $path): array
    {
        if ($path === '') {
            return [];
        }

        return explode('.', $path);
    }
}
